<?php
$app->post('/AddOrUpdateAreaOfInterestCategory', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $r->organizerId = $_SESSION['uid'];
    if (isset($r->id) && $r->id != "0") {
        if ($db->dbRowUpdate("areaofinterestcategory", $r, "id=$r->id")) {
            $response["status"] = "success";
            $response["message"] = "Area Of Interest Category details updated successfully.";
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to update Area Of Interest Category. Please try again.";
            echoResponse(201, $response);
        }
    } else {
        $tabble_name = "areaofinterestcategory";
        $column_names = array("name", "organizerId");
        $result = $db->insertIntoTable($r, $column_names, $tabble_name);
        if ($result != null) {
            $response["status"] = "success";
            $response["message"] = "Area Of Interest Category inserted successfully.";
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to insert product. Please try again.";
            echoResponse(201, $response);
        }
    }
});

$app->get('/GetAreaOfInterestCategory', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();

    $sql = "SELECT *
    FROM areaofinterestcategory  where Id=$Id and 1=1 ";

    $record = $db->getOneRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetAreaOfInteretCategories', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $organizerId = $_SESSION['uid'];
    $sql = "SELECT *
    FROM areaofinterestcategory  where organizerId=$organizerId";

    $record = $db->getAllRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/DeleteAreaOfInterestCategory', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if ($r->id != 0) {
        $db->deleteRecord("delete from areaofinterestcategory where id=" . $r->id);
    }
    $response["status"] = "success";
    $response["message"] = "Delete successfully.";
    echoResponse(201, $response);
});


$app->get('/AreaOfInterestCategorysGrid', function () use ($app) {
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
        1 => 'createdDate'
    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM areaofinterestcategory";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT * ";
    if ($_SESSION['userType'] == "Organizer")
        $sql .= " FROM areaofinterestcategory l WHERE l.organizerId=" . $_SESSION['uid'] . " ";
    else
        $sql .= " FROM areaofinterestcategory l WHERE  l.organizerId=$Id";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( l.name LIKE '%" . $requestData['search']['value'] . "%' )";
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
