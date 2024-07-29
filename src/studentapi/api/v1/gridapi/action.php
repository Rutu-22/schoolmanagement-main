<?php
 
    //if(!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL) === false){
        // MailChimp API credentials
        $apiKey = 'e51dbda9eb2a7aa8aa08e66b8b1d34cf-us11';
        $listID = '2fa4f1b7e1';
        
        // MailChimp API URL
       // $memberID = md5(strtolower($email));
        $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
       
        $data = array("recipients" => array("list_id" => "2fa4f1b7e1"), 
        "type" => "regular", 
        "settings" => array("subject_line" => "Subject",
         "title" => "Title",
         "reply_to" => "test@gmail.com",
          "from_name" => "Ajax", 
          "folder_id" => "8888969b77"));

        $data = json_encode($data);
        $curl = curl_init();
        curl_setopt_array($curl, array(    
           //Sample url
           CURLOPT_URL => 'https://' . $dataCenter . '.api.mailchimp.com/3.0/campaigns',
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_TIMEOUT => 30,
           CURLOPT_CUSTOMREQUEST => "POST",
           CURLOPT_POSTFIELDS => $data,
           CURLOPT_HTTPHEADER => array(
              "authorization: apikey <your_apikey>"
           ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
           $response = $err;
        }
        
 ?>