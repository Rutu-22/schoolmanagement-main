<?php


$app->get('/getExhibitionByName', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $name =  $app->request()->get('name');
    $response["exhibition"] =  $db->getOneRecord("SELECT e.*,u.uid,u.email as organizerEmail,u.email1 as organizerEmail1,u.email2 as organizerEmail2,u.email3 as organizerEmail3,u.name as organizerName  FROM exhibition e 
    inner join users u on u.uid = e.organizerId 
    where e.link='$name'");
    $response["status"] = "success";
    echoResponse(201, $response);
});

$app->post('/AddOrganizer', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    require_once 'passwordHash.php';
    $db = new DbHandler();
    $email = $r->email;
    $password = $r->password;
    $r->userType = 'Organizer';
    $r->organizer = 'All';
    if (isset($r->OldEmail) && isset($r->uid)) {
        $oldEmail = $r->OldEmail;
        unset($r->OldEmail);
        $userSetting = new stdClass();
        $userSetting->allowExhibition = $r->allowExhibition;
        $userSetting->allowExhibitorPerExhibition = $r->allowExhibitorPerExhibition;
        $userSetting->uid = $r->uid;
        unset($r->allowExhibition);
        unset($r->allowExhibitorPerExhibition);
        unset($r->is360View);
        unset($r->Sponsor);
        if ($oldEmail == $r->email) {
            if ($db->dbRowUpdate("users", $r, "uid=$r->uid")) {
                $response["status"] = "success";
                $response["message"] = "Organizer account updated successfully";
                $response["uid"] = $r->uid;
            } else {
                $response["status"] = "error";
                $response["message"] = "Failed to create Organizer. Please try again";
            }
        } else {
            $isUserExists = $db->getOneRecord("select 1 from users where userType='Organizer' and organizer='All' and  email='$email'");
            if (!$isUserExists) {

                if ($db->dbRowUpdate("users", $r, "uid=$r->uid")) {
                    $response["status"] = "success";
                    $response["message"] = "Organizer account updated successfully";
                    $response["uid"] = $r->uid;
                } else {
                    $response["status"] = "error";
                    $response["message"] = "Failed to create Organizer. Please try again";
                }
            } else {
                $response["status"] = "error";
                $response["message"] = "An Organizer with the provided mobile or email exists!";
            }
        }
        $isUserExists = $db->getOneRecord("select 1 from usersetting where  uid=$r->uid");
        if (!$isUserExists) {
            $tabble_name = "usersetting";
            $column_names = array('uid', 'allowExhibition', 'allowExhibitorPerExhibition');
            $result1 = $db->insertIntoTable($userSetting, $column_names, $tabble_name);
        } else
            $db->dbRowUpdate("usersetting", $userSetting, "uid=$userSetting->uid");

    } else {
        $isUserExists = $db->getOneRecord("select 1 from users where  email='$email'");
        if (!$isUserExists) {
            $r->password = passwordHash::hash($password);
            $tabble_name = "users";
            $column_names = array('mobile', 'name', 'email', 'password', 'address', 'userType', 'organizer');
            $result = $db->insertIntoTable($r, $column_names, $tabble_name);

            $r->uid = $result;
            $tabble_name = "usersetting";
            $column_names = array('uid', 'allowExhibition', 'allowExhibitorPerExhibition');
            $result1 = $db->insertIntoTable($r, $column_names, $tabble_name);

            if ($result1 != NULL) {
                $response["status"] = "success";
                $response["message"] = "Organizer account created successfully";
                $response["uid"] = $result;
                $response['userSettingId'] = $result1;
            } else {
                $response["status"] = "error";
                $response["message"] = "Failed to create Organizer. Please try again";
            }
        } else {
            $response["status"] = "error";
            $response["message"] = "An Organizer with the provided mobile or email exists!";
        }
    }
    echoResponse(200, $response);
});

$app->post('/DeleteOrganizer', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if ($r->id != 0) {
        $db->deleteRecord("delete from users where uid=" . $r->id);
    }
    $response["status"] = "success";
    $response["message"] = "Delete successfully.";
    echoResponse(201, $response);
});

$app->post('/OrganizerGrid', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        0 => 'Email',
        1 => 'Name',
        2 => 'Mobile',
        2 => 'allowExhibition',
        2 => 'allowExhibitorPerExhibition'
    );
    if (!isset($_SESSION)) {
        session_start();
    }


    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM users WHERE  `userType` = 'Organizer'";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT uss.allowExhibitorPerExhibition,uss.allowExhibition,e.name as Name,e.email as Email,e.address,e.userType,e.mobile as Mobile,e.created,  e.uid, (select count(1) from exhibition where e.uid=organizerId) as Count";

    if ($_SESSION['userType'] == "Organizer")
        $sql .= " FROM users e left join usersetting uss on e.uid=uss.uid
         WHERE e.Uid=" . $_SESSION['uid'];
    else
        $sql .= " FROM users e left join usersetting uss on e.uid=uss.uid
         WHERE e.userType='Organizer' ";

    if ($_SESSION["SearchEmail"]) {
        $sql .= "  " . $_SESSION["SearchEmail"];
    }

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( e.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR e.mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR e.email LIKE '%" . $requestData['search']['value'] . "%')";
    }
    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});
