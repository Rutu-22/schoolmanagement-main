$hostname = [System.Net.Dns]::GetHostName()
Import-Module WebAdministration -ErrorAction Stop -Verbose:$false

<# Ensure that verbose log statements show in console #>
$VerbosePreference = "Continue"

function Get-NextDirectory($basePath, $attempt) {
    $nextPath = "$basePath-$attempt"
    if ((Test-Path $nextPath) -eq $true) {
        $attempt++
        $nextPath = Get-NextDirectory $basePath $attempt
    }
    return $nextPath
}

function New-UniqueAppPath {
    [CmdletBinding()]
    param(
        [string]$BasePath,
        [string]$AppName,
        [string]$AppVersion
    )

    $baseAppPath = Join-Path -Path $BasePath -ChildPath $AppName
    $baseAppVersionPath = Join-Path -Path $baseAppPath -ChildPath $AppVersion
    $baseAppVersionUniquePath = Get-NextDirectory $baseAppVersionPath 1

    $appPath = (New-Item -ItemType Directory -Path $baseAppVersionUniquePath -Force).FullName

    Write-Verbose "$hostname- Creating unique path '$appPath'"

    return $appPath
}

Export-ModuleMember -Function 'New-UniqueAppPath' -Verbose:$false

function Copy-InstallationPackage {
    [CmdletBinding()]
    param(
        [string]$Source,
        [string]$Destination
    )

    if ((Get-Item $Source) -is [System.IO.DirectoryInfo]) {
        Write-Verbose "$hostname- Copying '$Source' to '$Destination'"
        $sourceFilter = Join-Path -Path $Source -ChildPath "*"
        Copy-Item -Path $sourceFilter -Recurse -Destination $Destination -Force -ErrorAction Stop
    }
    elseif (([System.IO.Path]::GetExtension($Source)) -eq ".zip") {
        Write-Verbose "$hostname- Extracting '$Source' to '$Destination"
        Expand-Archive $Source -DestinationPath $Destination -ErrorAction Stop
    }
    else {
        Write-Error "$hostname- Source is an unsupported type '$Source'"
        throw
    }
}

Export-ModuleMember -Function 'Copy-InstallationPackage' -Verbose:$false

function Register-Website {
    [CmdletBinding()]
    param(
        [string]$Path,
        [string]$Name,
        [string]$Port,
        [string]$Username,
        [string]$Password,
        [switch]$ManagedRuntime,
        [switch]$Enable32Bit,
        [switch]$UseWindowsAuthentication

    )

    $appPoolPath = "IIS:\AppPools\$Name"
    $sitePath = "IIS:\Sites\$Name"

    if ((Test-Path $appPoolPath) -eq $false) {
        Write-Verbose "$hostname- Creating app pool"
        New-Item -Path "IIS:\AppPools" -Name $Name -Type AppPool -ErrorAction Stop
    }
    else {
        Write-Verbose "$hostname- Updating app pool"
    }

    if ($PSBoundParameters.ContainsKey('ManagedRuntime')) {
        Write-Verbose "$hostname- App pool is using the managed runtime v4.0"
        Set-ItemProperty -Path $appPoolPath -Name "managedRuntimeVersion" -Value "v4.0" -ErrorAction Stop
    }
    else {
        Write-Verbose "$hostname- App pool is not using a managed runtime"
        Set-ItemProperty -Path $appPoolPath -Name "managedRuntimeVersion" -Value "" -ErrorAction Stop
    }

    if ($PSBoundParameters.ContainsKey('Enable32Bit')) {
        Write-Verbose "$hostname- App pool is running in 32 bit mode"
        Set-ItemProperty -Path $appPoolPath -Name "enable32BitAppOnWin64" -Value $true -ErrorAction Stop
    }
    else {
        Write-Verbose "$hostname- App pool is running in 64 bit mode"
        Set-ItemProperty -Path $appPoolPath -Name "enable32BitAppOnWin64" -Value $false -ErrorAction Stop
    }

    Set-ItemProperty -Path $appPoolPath -Name "autoStart" -Value $true -ErrorAction Stop
    Set-ItemProperty -Path $appPoolPath -Name "managedPipelineMode" -Value 0 -ErrorAction Stop
    Set-ItemProperty -Path $appPoolPath -Name "startMode" -Value "OnDemand" -ErrorAction Stop

	if ($Username -ne "" -and $Username -ne $null -and $Password -ne "" -and $Password -ne $null)
	{
		Write-Verbose "$hostname- App pool identity is running as $Username"
		Set-ItemProperty -Path $appPoolPath -Name "processModel" -Value @{identitytype = "SpecificUser"; username = $Username; password = $Password} -ErrorAction Stop
	}
	else
	{
		Write-Verbose "$hostname- App pool identity is running as ApplicationPoolIdentity"
		Set-ItemProperty -Path $appPoolPath -Name "processModel" -Value @{identitytype = "ApplicationPoolIdentity"} -ErrorAction Stop
	}

    if ((Test-Path $sitePath) -eq $false) {
        Write-Verbose "$hostname- Creating site on port $Port"
        New-Item -Path $sitePath -Type Site -Bindings @{protocol = "http"; bindingInformation = "*:${Port}:"} -ErrorAction Stop
    }
    else {
        Write-Verbose "$hostname- Updating site on port $Port"
        Set-ItemProperty -Path $sitePath -Name "bindings" -Value @{protocol = "http"; bindingInformation = "*:${Port}:"} -ErrorAction Stop
    }

    Write-Verbose "$hostname- Site is serving from '$Path'"
    Set-ItemProperty -Path $sitePath -Name "physicalPath" -Value $Path -ErrorAction Stop

    Set-ItemProperty -Path $sitePath -Name "applicationPool" -Value $Name -ErrorAction Stop

    if ($PSBoundParameters.ContainsKey('UseWindowsAuthentication')) {
        Write-Verbose "$hostname- Site is using windows authentication"
        Set-WebConfigurationProperty `
            -Filter "/system.webServer/security/authentication/windowsAuthentication" `
            -Name "enabled" `
            -Value $true `
            -Location $Name `
            -PSPath IIS:\ `
            -ErrorAction Stop
    }
    else {
        Write-Verbose "$hostname- Site is not using windows authentication"
        Set-WebConfigurationProperty `
            -Filter "/system.webServer/security/authentication/windowsAuthentication" `
            -Name "enabled" `
            -Value $false `
            -Location $Name `
            -PSPath IIS:\ `
            -ErrorAction Stop
    }
}

Export-ModuleMember -Function 'Register-Website' -Verbose:$false

function Set-XmlNode ($doc, $xpath, $value) {
    $nodes = $doc.SelectNodes($xpath)

    foreach ($node in $nodes) {
        if ($null -ne $node) {
            if ($node.NodeType -eq "Element") {
                $node.InnerXml = $value
            }
            else {
                $node.Value = $value
            }
        }
    }
}

function Set-WebConfigProperty {
    [CmdletBinding()]
    param(
        [string]$Name,
        [string]$XPath,
        [string]$Value
    )

    $configFile = (Get-WebConfigFile -PSPath "IIS:\Sites\$Name").FullName;

    Write-Verbose "$hostname- Updating web.config - Setting '$XPath' to '$Value'"

    [xml]$doc = Get-Content $configFile -ErrorAction Stop;

    Set-XmlNode $doc $XPath $Value

    $doc.Save($configFile)
}

Export-ModuleMember -Function 'Set-WebConfigProperty' -Verbose:$false

function Get-WebConfig {
    [CmdletBinding()]
    param(
        [string]$Name
    )

    $webConfigPath = Get-WebConfigPath -Name $Name;

    [xml]$webConfig = Get-Content $webConfigPath -ErrorAction Stop;

    return $webConfig;
}

Export-ModuleMember -Function 'Get-WebConfig' -Verbose:$false

function Save-WebConfig {
    [CmdletBinding()]
    param(
        [string]$Name,
        [xml]$WebConfig
    )

    $webConfigPath = Get-WebConfigPath -Name $Name;

    $WebConfig.Save($webConfigPath);
}

Export-ModuleMember -Function 'Save-WebConfig' -Verbose:$false

function Get-AppSettings {
    [CmdletBinding()]
    param(
        [string]$Name
    )

    $appSettingsPath = Get-AppSettingsPath -Name $Name

    $appSettings = (Get-Content $appSettingsPath) -join "`n" | ConvertFrom-Json

    return $appSettings
}

Export-ModuleMember -Function 'Get-AppSettings' -Verbose:$false

function Save-AppSettings {
    [CmdletBinding()]
    param(
        [string]$Name,
        [object]$AppSettings
    )

    $appSettingsPath = Get-AppSettingsPath -Name $Name

    Set-Content `
        -Path $appSettingsPath `
        -Value (ConvertTo-Json $AppSettings -Compress -Depth 10)
}

Export-ModuleMember -Function 'Save-AppSettings' -Verbose:$false

function Get-WebConfigPath {
    [CmdletBinding()]
    param (
        [string]$Name
    )

    $sitePath = "IIS:\Sites\$Name"

    $installationPath = (Get-WebFilePath $sitePath -ErrorAction Stop).FullName

    $webConfigPath = Join-Path -Path $installationPath -ChildPath "web.config"

    return $webConfigPath
}

function Get-AppSettingsPath {
    [CmdletBinding()]
    param(
        [string]$Name
    )

    $sitePath = "IIS:\Sites\$Name"

    $installationPath = (Get-WebFilePath $sitePath -ErrorAction Stop).FullName

    $appSettingsPath = Join-Path -Path $installationPath -ChildPath "appsettings.json"

    return $appSettingsPath
}
