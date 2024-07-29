<?php
$app->post('/AddOrUpdateAreaOfInterest', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $organizerId = $_SESSION['uid'];
    foreach ($r->deleted->areaOfInterest as $delete) {
        if (isset($delete->id)) {
            $db->deleteRecord("delete from areaofinterest where id=" . $delete->id);
        }
    }
    foreach ($r->deleted->subCategory as $delete) {
        if (isset($delete->id)) {
            $db->deleteRecord("delete from areaofinterest where subCategoryId=" . $delete->id);
            $db->deleteRecord("delete from areaofinterestsubcategory where id=" . $delete->id);
        }
    }

    foreach ($r->save as $row) {
        if (!isset($row->id)) {
            $row->organizerId = $organizerId;
            $tabble_name = "areaofinterestsubcategory";
            $column_names = array("name", "categoryId", "organizerId");
            $subCategoryId = $db->insertIntoTable($row, $column_names, $tabble_name);
        } else {
            $query = "update areaofinterestsubcategory set name = '$row->name' where id=$row->id";
            $db->updateTableValue($query);
        }
        foreach ($row->nodes as $item) {
            // $isExist = $db->getOneRecord("select 1 from areaofinterest where organizerId=$organizerId and name='" . $item->name . "'"); 
            if (!isset($item->id)) {
                $item->organizerId = $organizerId;
                $item->subCategoryId = isset($subCategoryId) ? $subCategoryId : $row->id;
                $tabble_name = "areaofinterest";
                $column_names = array("name", "subCategoryId", "organizerId", "description");
                $db->insertIntoTable($item, $column_names, $tabble_name);
            } else {
                $query = "update areaofinterest set name = '$item->name', description='$item->description' where id=$item->id";
                $db->updateTableValue($query);
            }
        }
    }
    $response["status"] = "success";
    $response["message"] = "AreaOfInterest details Saved successfully.";
    echoResponse(200, $response);
});



$app->get('/getAreaOfInterestListByExhibitor', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $Id = $_SESSION['uid'];
    $queryAreaOfInterest = $db->getAllRecord("select * from areaofinterest where organizerId =( select u.uid 
    from usermap um inner join users u on u.uid = um.createdById where um.uid =$Id)");
    $response["AreaOfInterest"] = $queryAreaOfInterest;
    $sql = "SELECT * FROM areaofinterestsubcategory where organizerId=( select u.uid 
    from usermap um inner join users u on u.uid = um.createdById where um.uid =$Id) ";

    $record = $db->getAllRecord($sql);
    $response["categories"] = $record;

    $response["status"] = "success";
    echoResponse(200, $response);
});
$app->get('/getAreaOfInterestList', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $organizerId = $_SESSION['uid'];
    $sql = "SELECT * FROM areaofinterest where organizerId= $organizerId";

    $record = $db->getAllRecord($sql);
    $response["AreaOfInterest"] = $record;
    $sql = "SELECT * FROM areaofinterestsubcategory where organizerId= $organizerId";

    $record = $db->getAllRecord($sql);
    $response["CategoryList"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetAreaOfInterest', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();

    $sql = "SELECT *
    FROM areaofinterest  where Id=$Id and 1=1 ";

    $record = $db->getOneRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/DeleteAreaOfInterest', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if ($r->id != 0) {
        $db->deleteRecord("delete from areaofinterest where id=" . $r->id);
    }
    $response["status"] = "success";
    $response["message"] = "Delete successfully.";
    echoResponse(201, $response);
});


$app->get('/getAreaOfInterestAll', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $organizerId = $_SESSION['uid'];
    $response["Category"] =  $db->getAllRecord("SELECT * FROM exhibition where organizerId= $organizerId");
    $response["SubCategory"] =  $db->getAllRecord("SELECT sc.* FROM areaofinterestsubcategory sc inner join exhibition c on sc.categoryId = c.id  where c.organizerId= $organizerId");
    $response["AreaOfInterest"] = $db->getAllRecord("SELECT * FROM areaofinterest where organizerId= $organizerId");
    $response["status"] = "success";
    echoResponse(201, $response);
});
