[CmdletBinding()]
PARAM(
	[string]$Name = "Workstation Hub UI",
	[int]$Port = 8087,
	[string]$Version = "8.0.0",
	[string]$InstallPath = "C:\Program Files\APD Communications\WorkStationHubUI",
	[string]$LogPath = "C:\ProgramData\APD Communications\WorkStationHubUI",
	[string]$Username,
	[string]$Password,
	[string]$APDCommunications = "",
	[string]$EnvironmentName = "local"
)

$ErrorActionPreference = "Stop"
$VerbosePreference = "Continue"

$hostname = [System.Net.Dns]::GetHostName()

Write-Verbose "$hostname- Setup Starting"

if ((Test-Path "$PSScriptRoot\AspireInstallModule.psm1") -eq $true) {
	Import-Module "$PSScriptRoot\AspireInstallModule.psm1" -Verbose:$false
	Write-Verbose "$hostname- AspireInstallModule loaded"
}

$installationDirectory = New-UniqueAppPath `
	-BasePath $InstallPath `
	-AppName $Name `
	-AppVersion $Version

$loggingDirectory = New-UniqueAppPath `
	-BasePath $LogPath `
	-AppName $Name `
	-AppVersion $Version

Copy-InstallationPackage `
	-Source "C:\DevOpsInstalls\WorkStationHubUI" `
	-Destination $installationDirectory

<# If username and password supplied then use for the application pool identity #>
<# otherwise ApplicationPoolIdentity is used #>
if ($Username -ne "" -and $Username -ne $null -and $Password -ne "" -and $Password -ne $null)
{
	Register-Website `
		-Path $installationDirectory `
		-Name $Name `
		-Port $Port `
		-Username $Username `
		-Password $Password `

}
else
{
	Register-Website `
		-Path $installationDirectory `
		-Name $Name `
		-Port $Port `
}


Write-Verbose "$hostname- Setup Complete"
