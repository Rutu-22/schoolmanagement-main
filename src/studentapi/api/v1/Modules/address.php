<?php
$app->post('/addAddress', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = addAddress($r);
    echoResponse(200, $response);
});

$app->get('/getAddresses', function () use ($app) {
    $response = array();
    $addresses = getAddresses();
    
    $response['addresses'] = $addresses;
    $response['status'] = 'success';
    echoResponse(200, $response);
});

$app->post('/updateAddress', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = updateAddress($r);
    echoResponse(200, $response);
});

function getAddresses() {
    $db = new DbHandler();
    $sql = "SELECT * from addresses";
    return $db->getAllRecord($sql);
}

function addAddress($request) {
    $db = new DbHandler();
    $table_name = "addresses";
    $column_names = array('street', 'city', 'state', 'postal_code');
    $id = $db->insertIntoTable($request, $column_names, $table_name);
    return $id;
}

function updateAddress($request) {
    $db = new DbHandler();
    if ($db->dbRowUpdate("addresses", $request, "id=$request->id")) {
        $response["status"] = "success";
        $response["message"] = "Address updated successfully";
        $response["uid"] = $request->id;
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to update address. Please try again";
    }
    return $response;
}

$app->post('/deleteAddress', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = deleteAddress($r->addressId);
    echoResponse(200, $response);
});

function deleteAddress($addressId)
{
    $db = new DbHandler();
    $table_name = "addresses";
    $condition = "id=$addressId";
    
    $query = "DELETE FROM $table_name WHERE $condition";
    
    if ($db->deleteRecord($query)) {
        $response["status"] = "success";
        $response["message"] = "Address deleted successfully";
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to delete address. Please try again";
    }
    return $response;
}

