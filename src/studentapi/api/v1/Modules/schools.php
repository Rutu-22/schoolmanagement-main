<?php

$app->post('/addSchool', function () use ($app) {

    $r = json_decode($app->request->getBody());
    $response = add($r);
    echoResponse(200, $response);
});
$app->get('/Getschools', function () use ($app) {
    $response = array();
    $schools = getSchools();
    
    $response['schools'] = $schools;
    $response['status'] = 'success';
    echoResponse(200, $response);
});
$app->post('/updateSchool', function () use ($app) {

    $r = json_decode($app->request->getBody());
    $response = update($r);
    echoResponse(200, $response);
});
function getSchools(){
    $db = new DbHandler();
    $sql = "SELECT * from schools";
    return $db->getAllRecord($sql);
}
function add($request)
{
    //eco_order
    $db = new DbHandler();
    $table_name = "schools";
    $column_names = array('name','mobile','address','contact_person');
    $id = $db->insertIntoTable($request, $column_names, $table_name);
    return $id;
}
function update($request)
{
    $db = new DbHandler();
    if ($db->dbRowUpdate("schools", $request, "id=$request->id")) {
        $response["status"] = "success";
        $response["message"] = " updated successfully";
        $response["uid"] = $request->id;
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to update. Please try again";
    }
     return $response;
}
$app->post('/deleteSchool', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = deleteSchool($r->id);
    echoResponse(200, $response);
});
function deleteSchool($schoolId)
{
    $db = new DbHandler();
    $table_name = "schools";
    $condition = "id=$schoolId";
    
    $query = "DELETE FROM $table_name WHERE $condition";
    
    if ($db->deleteRecord($query)) {
        $response["status"] = "success";
        $response["message"] = "School deleted successfully";
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to delete school. Please try again";
    }
    return $response;
}
