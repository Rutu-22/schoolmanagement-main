<?php

require_once 'dbHandler.php';
require_once 'passwordHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

/* $app = new \Slim\Slim(); */

$app = new \Slim\Slim(array(
    'debug' => true
));

// User id from db - Global Variable
$user_id = NULL;

require_once 'authentication.php';
require_once 'Modules/Organizer.php';
require_once 'Modules/student.php';
require_once 'Modules/schools.php';
require_once 'Modules/address.php';
require_once 'Modules/caste.php';
require_once 'Modules/division.php';
require_once 'Modules/Exhibition.php';
require_once 'Modules/AreaOfInterest.php';
require_once 'Modules/Image360.php';
require_once 'Modules/AreaOfInterestCategory.php';
require_once 'Modules/Package.php';
require_once 'Modules/order.php';
require_once 'Modules/Discount.php';
require_once 'Modules/Offer.php';
require_once 'Modules/SaleProducts.php';
require_once 'Modules/GarbageCollection.php';
require_once("lib/Tinify/Exception.php");
require_once("lib/Tinify/ResultMeta.php");
require_once("lib/Tinify/Result.php");
require_once("lib/Tinify/Source.php");
require_once("lib/Tinify/Client.php");
require_once("lib/Tinify.php");
\Tinify\setKey("WMs5KYBXZcdslFBgl7fDY5rMYQyC94x0");
/**
 * Verifying required params posted or not
 */

function getOrderReceivedTemplate()
{
    return "Dear ##username##, You have received an Order on your Booth, click here to view ##link##,%n- Team myVspace";
}

function getYouGotALeadTemplate()
{
    return "Dear ##username##, You got a Lead,%nName: ##visitorname##%nPh: ##mobile##%neM: ##email##%nV. Dtls: ##link##%n-Team myVspace";
}

function getOrderConfirmTemplate()
{
    return "Order Confirmed%nHi ##username##,%nThank you for your Order,%nYour order will be shipped shortly.%nView Order Details: ##link##%n- myVspace";
    //"Dear ##username##, You have received an Order on your Booth, click here to view ##link##,%n- Team myVspace";
}

    /**
     * Send SMS to all 
        $text = 'SMS text'
        $numbers = array('8998899889','9998879898')
    */
    function sendSMS($text, $numbers, $exhibitorId = 0, $exhibitionId = 0)
    {
        $db = new DbHandler();
        $allowToSend = 0;
        $response = "SMS Notification not active";
        if ($exhibitionId == 0 && $exhibitorId == 0) {
            $allowToSend = 1;

        } else {
            if ($exhibitionId != 0) {
                $exhibition = $db->getOneRecord("select * from exhibition where SMSNotification = 1 and  id = $exhibitionId");
                if ($exhibition != null) {
                    $allowToSend = 1;
                } else {
                    $allowToSend = 0;
                }
            } else {
                $ex = getExhibitionByExhibitorId($exhibitorId);
                if ($ex['SMSNotification'] == 1 || $ex['SMSNotification'] == true || $ex['SMSNotification'] == "1") {
                    $allowToSend = 1;
                } else {
                    $allowToSend = 0;
                }
            }
        }
    
        if ($allowToSend ==1) {
            $apiKey = urlencode('NzM3OTMyNDU1MzZmNTQ0MjRjNTkzNjUzNTg3NjM4NTk=');
            $sender = urlencode('MYVSPC');
            $messagesms = rawurlencode($text);
            $datasms = array('apikey' => $apiKey, 'numbers' => $numbers, "sender" => $sender, "message" => $messagesms);
            
            $ch = curl_init('https://api.textlocal.in/send/');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $datasms);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
        }

        return $response." SMS=".$text;
    }


function authonticate($app)
{
    $token = $app->request()->get('t');
    if ($token && $token != "null") {
        if (!isset($_SESSION)) {
            session_start();
        }
        if (isset($_SESSION['tableName'])) {
            if ($token == $_SESSION['tableName']) {
                $app->run();
            } else {
                $app->run();
            }
        } else {

            $response = array();
            $app = \Slim\Slim::getInstance();
            $response["status"] = "error";
            $response["message"] = 'Unauthorized Request';
            echoResponse(200, $response);
        }
    } else {

        $app->run();
    }
}
function verifyRequiredParams($required_fields, $request_params)
{
    $error = false;
    $error_fields = "";
    foreach ($required_fields as $field) {
        if (!isset($request_params->$field) || strlen(trim($request_params->$field)) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["status"] = "error";
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(200, $response);
        $app->stop();
    }
}


function echoResponse($status_code, $response)
{
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}


function sendEmail($toemail, $subject, $message, $from, $fromName, $sendemail, $sendmethod)
{
    $response = array();
    include_once 'smtpMail.php';
    if (isset($_SERVER["OS"]) && $_SERVER["OS"] == "Windows_NT") {
        $hostname = strtolower($_SERVER["COMPUTERNAME"]);
    } else {
        $hostname = `hostname`;
        $hostnamearray = explode('.', $hostname);
        $hostname = $hostnamearray[0];
    }
    $response["toemail"] = $toemail;
    $response["subject"] = $subject;
    $response["message"] = $message;
    $response["from"] = $from;
    $response["fromName"] = $fromName;
    $response["sendemail"] = $sendemail;
    $response["sendmethod"] = $sendmethod;
    if (isset($sendemail)) {
        header("Content-Type: text/plain");
        header("X-Node: $hostname");
        $from = $from;
        if ($from == "" || $toemail == "") {
            //header("HTTP/1.1 500 WhatAreYouDoing");
            // header("Content-Type: text/plain");
            $response["message"] = 'FAIL: You must fill in From: and To: fields.';
            $response["status"] = 'error';
            echoResponse(201, $response);
            exit;
        }
        if ($sendmethod == "mail") {
            $result = mail($toemail, $subject, $message, "From: $from");
            if ($result) {
                $response["message"] = 'Email sent successfully.';
                $response["status"] = 'success';
                echoResponse(200, $response);
            } else {
                $response["message"] = 'Failed to send email please check details before send.';
                $response["status"] = 'error';
                echoResponse(201, $response);
            }
        } elseif ($sendmethod == "smtp") {
            ob_start();
            //start capturing output buffer because we want to change output to html

            $mail = new PHPMailer;
            $mail->SMTPDebug = 2;
            $mail->IsSMTP();
            if (strpos($hostname, 'cpnl') === FALSE) //if not cPanel
                $mail->Host = 'relay-hosting.secureserver.net';
            else
                $mail->Host = 'localhost';
            $mail->SMTPAuth = false;

            $mail->From = $from;
            $mail->FromName = $fromName;
            $mail->AddAddress($toemail);

            $mail->Subject = $subject;
            $mail->Body = $message;

            $mailresult = $mail->Send();
            $mailconversation = nl2br(htmlspecialchars(ob_get_clean())); //captures the output of PHPMailer and htmlizes it
            if (!$mailresult) {
                $response["message"] = 'FAIL: ' . $mail->ErrorInfo . '<br />' . $mailconversation;
                $response["status"] = 'error';
                echoResponse(201, $response);
            } else {
                $response["message"] = $mailconversation;
                $response["mailresult"] = $mailresult;

                $response["status"] = 'success';
                echoResponse(200, $response);
            }
        } elseif ($sendmethod == "sendmail") {
            $cmd = "cat - << EOF | /usr/sbin/sendmail -t 2>&1\nto:$toemail\nfrom:$from\nsubject:$subject\n\n$message\n\nEOF\n";
            $mailresult = shell_exec($cmd);
            if ($mailresult == '') {
                //A blank result is usually successful
                $response["message"] = 'Email send successfully.';
                $response["status"] = 'success';
                echoResponse(200, $response);
            } else {
                $response["message"] = "The sendmail command returned what appears to be an error: " . $mailresult . "<br />\n<br />";
                $response["status"] = 'error';
                echoResponse(201, $response);
            }
        } else {
            $response["message"] = 'FAIL (Invalid sendmethod variable in POST data)';
            $response["status"] = 'error';
            echoResponse(201, $response);
        }
    }
}

function uploadImage($fileName, $folderUrl, $folderPath, $fileData)
{
    $imageName = preg_replace("/[^a-zA-Z0-9.-]/", "", $fileName);
    $img = str_replace('data:image/jpeg;base64,', '', $fileData);
    $imageName = preg_replace("/[^a-zA-Z0-9.-]/", "", $fileName);
    $extension = ".jpg";
    $img = "";

    if (strlen($fileData) <= 50)
        return "";
    if (strstr($fileData, 'image/jpeg')) {
        $img = str_replace('data:image/jpeg;base64,', '', $fileData);
        $extension = ".jpeg";
    }

    if (strstr($fileData, 'image/jpg')) {
        $img = str_replace('data:image/jpg;base64,', '', $fileData);
        $extension = ".jpg";
    }

    if (strstr($fileData, 'image/png')) {
        $img = str_replace('data:image/png;base64,', '', $fileData);
        $extension = ".png";
    }

    if (strstr($fileData, 'image/gif')) {
        $img = str_replace('data:image/gif;base64,', '', $fileData);
        $extension = ".gif";
    }
    $decodedData = base64_decode($img);
    $fileName = $folderPath . "" . $imageName . "" . $extension;
    file_put_contents($fileName, $decodedData);
    $path = GetHostUrl() . $folderUrl . "" . $imageName . "" . $extension;
    return $path;
}

function uploadPdfFile($fileName, $folderUrl, $folderPath, $fileData)
{
    $imageName = preg_replace("/[^a-zA-Z0-9]/", "", $fileName);
    $img = str_replace('data:application/pdf;base64,', '', $fileData);
    $decodedData = base64_decode($img);
    $fileName = $folderPath . "" . $imageName . ".pdf";
    file_put_contents($fileName, $decodedData);
    $path = GetHostUrl() . $folderUrl . "" . $imageName . ".pdf";
    return $path;
}

function GetHostUrl()
{
    //local URL
    return "http://localhost:91/vertualExhibition/";
    //Server URL
    //  return "https://virtual.exhibitionz.com/v1/";
}

function compressImageUsingTinyPng($path, $pathLocation)
{
    $source = \Tinify\fromUrl($path);
    //Save converted
    $source->toFile($pathLocation);
    //Delete large size file from desk.

    //Set Image URL 
    $path = GetHostUrl();
    $path .= "api/v1/";
    $path .= $pathLocation;
    return $path;
}

function resize_image($file, $fileSaveAs, $w, $h, $crop = FALSE)
{
    try {
        $arr = explode('.', $file, 2);
        list($width, $height) = getimagesize($file);
        $r = $width / $height;
        if ($crop) {
            if ($width > $height) {
                $width = ceil($width - ($width * abs($r - $w / $h)));
            } else {
                $height = ceil($height - ($height * abs($r - $w / $h)));
            }
            $newwidth = $w;
            $newheight = $h;
        } else {
            if ($w / $h > $r) {
                $newwidth = $h * $r;
                $newheight = $h;
            } else {
                $newheight = $w / $r;
                $newwidth = $w;
            }
        }
        if ($arr[1] == 'jpg' || $arr[1] == 'jpeg') {
            $src = imagecreatefromjpeg($file);
        } else if ($arr[1] == 'png') {
            $src = imagecreatefrompng($file);
        }

        $dst = imagecreatetruecolor($newwidth, $newheight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        ob_start();
        if ($arr[1] == 'jpg' || $arr[1] == 'jpeg') {
            imagejpeg($dst);
        } else if ($arr[1] == 'png') {
            imagepng($dst);
        }

        $i = ob_get_clean();
        file_put_contents($fileSaveAs . "." . $arr[1], $i);
        return GetHostUrl() . "api/v1/" . $fileSaveAs . "." . $arr[1];
    } catch (Exception $Ex) {
        return GetHostUrl() . "api/v1/" . $file;
    }
}

authonticate($app);
