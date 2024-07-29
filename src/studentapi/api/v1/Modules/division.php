<?php
$app->post('/addDivision', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = addDivision($r);
    echoResponse(200, $response);
});

$app->get('/getDivisions', function () use ($app) {
    $response = array();
    $divisions = getDivisions();
    
    $response['divisions'] = $divisions;
    $response['status'] = 'success';
    echoResponse(200, $response);
});

function getDivisions() {
    $db = new DbHandler();
    $sql = "SELECT * from divisions";
    return $db->getAllRecord($sql);
}

function addDivision($request) {
    $db = new DbHandler();
    $table_name = "divisions";
    $column_names = array('name', 'description');
    $id = $db->insertIntoTable($request, $column_names, $table_name);
    return $id;
}

$app->post('/updateDivision', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = updateDivision($r);
    echoResponse(200, $response);
});

$app->post('/deleteDivision', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = deleteDivision($r);
    echoResponse(200, $response);
});

function updateDivision($request) {
    $db = new DbHandler();
    if ($db->dbRowUpdate("divisions", $request, "id=$request->id")) {
        $response["status"] = "success";
        $response["message"] = "Division updated successfully";
        $response["uid"] = $request->id;
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to update division. Please try again";
    }
    return $response;
}



function deleteDivision($divisionId)
{
    $db = new DbHandler();
    $table_name = "divisions";
    $condition = "id=$divisionId";
    
    $query = "DELETE FROM $table_name WHERE $condition";
    
    if ($db->deleteRecord($query)) {
        $response["status"] = "success";
        $response["message"] = "Division deleted successfully";
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to delete division. Please try again";
    }
    return $response;
}

