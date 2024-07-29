<?php

$app->post('/addCaste', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = addCaste($r);
    echoResponse(200, $response);
});

$app->get('/getCastes', function () use ($app) {
    $response = array();
    $castes = getCastes();
    
    $response['castes'] = $castes;
    $response['status'] = 'success';
    echoResponse(200, $response);
});

function getCastes() {
    $db = new DbHandler();
    $sql = "SELECT * from castes";
    return $db->getAllRecord($sql);
}

function addCaste($request) {
    $db = new DbHandler();
    $table_name = "castes";
    $column_names = array('id','caste');
    $id = $db->insertIntoTable($request, $column_names, $table_name);
    return $id;
}

// Update Caste
$app->post('/updateCaste', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = updateCaste($r);
    echoResponse(200, $response);
});

function updateCaste($request) {
    $db = new DbHandler();
    if ($db->dbRowUpdate("castes", $request, "id=$request->id")) {
        $response["status"] = "success";
        $response["message"] = "Caste updated successfully";
        $response["uid"] = $request->id;
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to update caste. Please try again";
    }
    return $response;
}

// Delete Caste
$app->post('/deleteCaste', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = deleteCaste($r->casteId);
    echoResponse(200, $response);
});

function deleteCaste($casteId)
{
    $db = new DbHandler();
    $table_name = "castes";
    $condition = "id=$casteId";
    
    $query = "DELETE FROM $table_name WHERE $condition";
    
    if ($db->deleteRecord($query)) {
        $response["status"] = "success";
        $response["message"] = "Caste deleted successfully";
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to delete caste. Please try again";
    }
    return $response;
}


