<?php
$app->post('/AddOrUpdateExhibition', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (isset($r->id) && $r->id != "0") {
        $folderUrl = "api/v1/ExhibitionImages/";
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "p0_" . $r->id . "_" . $date;
        $date = date('i-s-m', time());
        $praImageName = "p0_" . $r->organizerId . "_" . $date;
        if ($r->exhibition_ImageUpload == true) {
            $r->floorPlan = uploadImage($praImageName . "md", $folderUrl, "ExhibitionImages/", $r->exhibition_image);
        } else {
            unset($r->floorPlan);
        }
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "p1_" . $r->organizerId . "_" . $date;
        if ($r->exhibition_ImageUpload1 == true) {
            $r->brochure = uploadImage($praImageName . "md", $folderUrl, "ExhibitionImages/", $r->exhibition_image1);
        } else {
            unset($r->brochure);
        }
        unset($r->StartDate);
        unset($r->exhibition_ImageUpload); // Remove field as it not useful.
        unset($r->exhibition_ImageUpload1); // Remove field as it not useful.
        unset($r->exhibition_image1);
        unset($r->exhibition_image);
        if (!isset($r->IsGuestLoginAllowed) || $r->IsGuestLoginAllowed == "" || $r->IsGuestLoginAllowed == false) {
            $r->IsGuestLoginAllowed = 0;
        } else {
            $r->IsGuestLoginAllowed = 1;
        }

        if (!isset($r->SMSNotification) || $r->SMSNotification == "" || $r->SMSNotification == false) {
            $r->SMSNotification = 0;
        } else {
            $r->SMSNotification = 1;
        }

        if (!isset($r->EmailNotification) || $r->EmailNotification == "" || $r->EmailNotification == false) {
            $r->EmailNotification = 0;
        } else {
            $r->EmailNotification = 1;
        }
        if ($db->dbRowUpdate("exhibition", $r, "id=$r->id")) {
            $response["id"] = $r->id;
            $response["status"] = "success";

            $response["message"] = "Exhibition details updated successfully.";

            $query = "update users set organizer='$r->link' where organizer<>'All' and userType='Manager' and uid in (select uid from usermap where exhibitionId =" . $r->id . ")";
            $result = $db->updateTableValue($query);
            $response["guestUser"] =  insertGuestUser($r);
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to update Exhibition. Please try again.";
            echoResponse(201, $response);
        }
    } else {
        $folderUrl = "api/v1/ExhibitionImages/";
        $date = date('m-d-Y-h-i-s-m', time());
        if (!isset($_SESSION)) {
            session_start();
        }
        $r->organizerId = $_SESSION['uid'];

        $praImageName = "p0_" . $r->organizerId . "_" . $date;
        if ($r->exhibition_ImageUpload == true) {
            $r->floorPlan = uploadImage($praImageName . "md", $folderUrl, "ExhibitionImages/", $r->exhibition_image);
        } else {
            $r->floorPlan = "";
        }
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "p1_" . $r->organizerId . "_" . $date;
        if ($r->exhibition_ImageUpload1 == true) {
            $r->brochure = uploadImage($praImageName . "md", $folderUrl, "ExhibitionImages/", $r->exhibition_image1);
        } else {
            $r->brochure = "";
        }
        unset($r->exhibition_ImageUpload); // Remove field as it not useful.
        unset($r->exhibition_ImageUpload1); // Remove field as it not useful.
        unset($r->exhibition_image1);
        unset($r->exhibition_image);

        if ($r->exhibitiontypeId != '1') {
            $r->convenienceFee = 0;
            $r->minimumOrderAmount = 0;
            $r->deliveryFee = 0;
            $r->pincodes = 0;
        }

        if (!isset($r->IsGuestLoginAllowed) || $r->IsGuestLoginAllowed == "") {
            $r->IsGuestLoginAllowed = 0;
        }
        if (!isset($r->SMSNotification) || $r->SMSNotification == "") {
            $r->SMSNotification = 0;
        }
        if (!isset($r->EmailNotification) || $r->EmailNotification == "") {
            $r->EmailNotification = 0;
        }
        $tabble_name = "exhibition";
        $column_names = array("name", "link", "description", "Interstarea", "address", "startDate", "endDate", "floorPlan", "organizerId",  "brochure", "mobile", "email", "IsGuestLoginAllowed", "EmailNotification", "SMSNotification", "introductionUrl", "convenienceFee", "exhibitiontypeId", "minimumOrderAmount", "deliveryFee", "pincodes");
        $result = $db->insertIntoTable($r, $column_names, $tabble_name);
        if ($result != null) {
            $response["status"] = "success";
            createShortCutLinkForExhibition($r->link);
            $response["id"] = $result;
            $response["message"] = "Exhibition inserted successfully.";
            $response["guestUser"] =  insertGuestUser($r);
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to insert product. Please try again.";
            echoResponse(201, $response);
        }
    }
});

function createShortCutLinkForExhibition($exhibitionName)
{
    mkdir($_SERVER['DOCUMENT_ROOT'] . "/" . $exhibitionName);
    $source = $_SERVER['DOCUMENT_ROOT'].'/realexhibitionTemplate/index.html';
    $destination = $_SERVER['DOCUMENT_ROOT'].'/'.$exhibitionName.'/index.html';
    if (!copy($source, $destination)) {
        return false;
    } else {
      return true;
    }
}
function insertGuestUser($exhibition)
{
    //check is guest user already exist or not
    if (isset($exhibition->IsGuestLoginAllowed) && ($exhibition->IsGuestLoginAllowed == '1' || $exhibition->IsGuestLoginAllowed == 1)) {
        $response = array();
        $db = new DbHandler();
        $sql = "SELECT count(1) as isExist FROM users  where link='$exhibition->link' and email='guest@myvspace.in'";
        $record = $db->getOneRecord($sql);

        if ($record['isExist'] == '0') {
            $userData =  '{"customer":{"email":"guest@myvspace.in","name":"Demo User","mobile":"9999999999","address":"Pune","companyName":"Demo Company","organizer":"' . $exhibition->link . '","interestArea":"5"}}';
            $r = json_decode($userData);
            return registerAsVisitor($r, false);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to insert. Please try again.";
            return $response;
        }
    }
}

$app->get('/IsExistLink', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $link =  $app->request()->get('link');
    $db = new DbHandler();
    if ($Id != 0)
        $sql = "SELECT count(1) as isExist FROM exhibition  where  Id<>$Id and link='$link' and isDelete<>1";
    else
        $sql = "SELECT count(1) as isExist FROM exhibition  where link='$link' and isDelete<>1";
    $record = $db->getOneRecord($sql);
    $response["Exist"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetOrganizerById', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();
    $sql = "select u.*, us.allowExhibition,us.allowExhibitorPerExhibition from  users u left join usersetting us on u.uid= us.uid where u.uid =" . $Id;
    $record = $db->getOneRecord($sql);
    $response["organizer"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetOrganizerByExhibitor', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();
    $sql = "select e.link as organizerCode,u.link,u.name,u.email,u.email1,u.email2,u.email3,u.uid,u.mobile,u.mobile1,u.mobile2,u.mobile3 
    from usermap um 
    inner join users u on u.uid = um.createdById
    inner join exhibition e on e.id = um.exhibitionId
    where um.uid =" . $Id;
    $record = $db->getOneRecord($sql);
    $response["organizer"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetExhibition', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();

    $sql = "SELECT *
    FROM exhibition  where isDelete<>1 and Id=$Id and 1=1 ";

    $record = $db->getOneRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/UpdateBoothOrder', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $r = json_decode($app->request->getBody());
    $sql = "update usermap set boothOrder = 0  where exhibitionId=" . $r->exhibitionId;
    $record = $db->updateTableValue($sql);
    foreach ($r->list as $index => $record) {
        $boothOrder = $index + 1;
        $sql = "update usermap set boothOrder = $boothOrder  where uid =$record->uid and  exhibitionId=" . $r->exhibitionId;
        $record = $db->updateTableValue($sql);
    }
    $response["message"] = "Exhibitor order saved successfully.";
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetNextPreviousBooth', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $Id =  $app->request()->get('exhibitionId');
    $sql = "SELECT um.*, u.name,u.link,u.organizer FROM users u inner join usermap um on u.uid=um.uid and um.exhibitionid=$Id and link is not null order by boothorder ";
    $records = $db->getAllRecord($sql);

    foreach ($records as $key => $record) {
        $sql = "SELECT GROUP_CONCAT(ep.Interstarea SEPARATOR ', ') as Interstarea 
        FROM  exhibitionproductdetail ep where ep.exhibitorId=" . $record["uid"];
        $interestArea = $db->getOneRecord($sql);
        $records[$key]['area'] = $interestArea;
    }
    $response["exhibitorList"] = $records;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetExhibitionsWithExhibitorList', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $organizerId = $_SESSION['uid'];

    $sql = "SELECT e.*, count(exhibitionid) as ExhibitiorCount
    FROM exhibition e left join usermap um on um.exhibitionid=e.id where e.isDelete<>1 and e.organizerId=$organizerId group by e.id";

    $record = $db->getAllRecord($sql);
    foreach ($record as $index => $item) {
        $record[$index]['exhibitorList'] = getExhibitorListByExhibitionId($item['id']);
    }
    $response["exhibitions"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});
$app->get('/GetExhibitions', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $organizerId = $_SESSION['uid'];

    $sql = "SELECT e.*, count(exhibitionid) as ExhibitiorCount
    FROM exhibition e left join usermap um on um.exhibitionid=e.id where e.isDelete<>1 and e.organizerId=$organizerId group by e.id";

    $record = $db->getAllRecord($sql);
    $response["exhibitions"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetAllExhibitions', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $sql = "SELECT *
    FROM exhibition e left join usermap um on um.exhibitionid=e.id where e.isDelete<>1 and e.enddate >= date('Y-m-d')";
    $record = $db->getAllRecord($sql);
    $response["exhibitions"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});
$app->get('/GetExhibitionsWithCount', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $organizerId = $_SESSION['uid'];

    $sql = "SELECT name,organizerId,id
    FROM exhibition  where isDelete<>1 and organizerId=$organizerId and 1=1 ";

    $record = $db->getAllRecord($sql);
    $sql1 = "select u.uid,u.name as Name,e.id as exhibitionId,e.name as exhibitionName,
    (SELECT COALESCE(sum(visitcount),0) as visitorCount FROM visitordetail  where exhibitorId=u.uid)as visitorCount
   from users u
   inner join usermap um on u.uid = um.uid
   inner join exhibition e on e.id = um.exhibitionId and e.isDelete<>1
   LEFT join exhibitorpackagemap epm on epm.exhibitorId = u.uid
   LEFT join exhibitorpackage ep on ep.id = epm.packageId
   where u.uid in (SELECT uid FROM usermap WHERE createdById = $organizerId) ";

    $record1 = $db->getAllRecord($sql1);

    $response["exhibitions"] = $record;
    $response["exhibitors"] = $record1;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/DeleteEditorSetting', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if ($r->uid) {
        $db->deleteRecord("delete from booth_editor where uid=" . $r->uid);
        $response["status"] = "success";
        $response["message"] = "Delete successfully.";
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to Delete successfully.";
    }
    echoResponse(201, $response);
});

$app->post('/DeleteExhibition', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if ($r->id != 0) {
        $db->updateTableValue("update exhibition set isDelete=1 where id=" . $r->id);
    }
    $response["status"] = "success";
    $response["message"] = "Delete successfully.";
    echoResponse(201, $response);
});


$app->get('/ExhibitionsGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;
    if (!isset($_SESSION)) {
        session_start();
    }
    $columns = array(
        // datatable column index  => database column name
        0 => 'name',
        1 => 'address',
        2 => 'description',
        3 => 'startDate',
        4 => 'endDate',
        5 => 'floorPlan',
        6 => 'createdDate'
    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM exhibition";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT * ";
    if ($_SESSION['userType'] == "Organizer")
        $sql .= " FROM exhibition l WHERE  l.isDelete<>1 and l.organizerId=" . $_SESSION['uid'] . " ";
    else
        $sql .= " FROM exhibition l WHERE  l.isDelete<>1 and l.organizerId=$Id";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( l.name LIKE '%" . $requestData['search']['value'] . "%' )";
        $sql .= " or ( l.description LIKE '%" . $requestData['search']['value'] . "%') ";
        $sql .= " or ( l.startDate LIKE '%" . $requestData['search']['value'] . "%' )";
        $sql .= " or ( l.endDate LIKE '%" . $requestData['search']['value'] . "%' )";
        $sql .= " OR (l.address LIKE '" . $requestData['search']['value'] . "%') ";
    }

    $sql .= " order by l.createdDate desc";
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
