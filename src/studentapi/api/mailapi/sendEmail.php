<?php

use PHPMailer\PHPMailer\PHPMailer;

use PHPMailer\PHPMailer\Exception;



require 'src/Exception.php';

require 'src/PHPMailer.php';

require 'src/SMTP.php';



$from = "support@myvspace.in";//$_REQUEST['from'];

$toemail = $_REQUEST['toemail'];

$subject = $_REQUEST['subject'];

$message = $_REQUEST['message'];

$fromName =$_REQUEST['fromName'];  

$mail = new PHPMailer;

$mail->isSMTP();

$mail->Host = 'localhost';

$mail->SMTPAuth = false;

$mail->SMTPAutoTLS = false; 

$mail->Port = 25; 

$mail->SMTPDebug = 2; // 0 = off (for production use) - 1 = client messages - 2 = client and server messages

$mail->setFrom($from, $fromName);

$mail->addAddress($toemail, "User");

$mail->AddBCC('pavangawade91@gmail.com', 'Pavan Gawade');
$mail->AddBCC('exhibitionzvirtual@gmail.com', 'Myvspace Notification');
$mail->AddBCC('prasadrg2@gmail.com', 'Prasad');

$mail->Subject = $subject;

$mail->msgHTML($message);

 //$mail->msgHTML(file_get_contents('contents.html'), __DIR__); //Read an HTML message body from an external file, convert referenced images to embedded,

$mail->AltBody = 'HTML messaging not supported';

// $mail->addAttachment('images/phpmailer_mini.png'); //Attach an image file



if(!$mail->send()){

    echo "Mailer Error: " . $mail->ErrorInfo;

}else{

    echo "Message sent!";

}?>