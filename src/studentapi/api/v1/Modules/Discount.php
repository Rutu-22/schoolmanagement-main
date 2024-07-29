<?php
$app->post('/DeleteDiscount', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $query = "update eco_discount set is_delete=1  where id='$r->id';";
    $db->updateTableValue($query);
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetDiscounts', function () use ($app) {
    $uid =  $app->request()->get('uid');
    $response = array();
    $response["record"] = getDiscounts($uid);
    $response["status"] = "success";
    echoResponse(200, $response);
});
$app->post('/SaveDiscount', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    if($r->id == 0){
        $response = SaveDiscount($r);
        $response["msg"] = "Create successfully";
    }else{
        $response = UpdateDiscount ($r,$r->id);
        $response["status"] = "success";
        $response["msg"] = "Update successfully";
    }
    echoResponse(200, $response);
});

function SaveDiscount ($request){
    $response = array();
    $db = new DbHandler();
    $tabble_name = "eco_discount";
    $column_names = array('uid', 'discount_type', 'discount_value', 'description');
    $discountId = $db->insertIntoTable($request, $column_names, $tabble_name);
    $response["discount_id"] = $discountId;
    return $response;
}

function UpdateDiscount ($request,$id){
    $response = array();
    $db = new DbHandler();
    if ($db->dbRowUpdate("eco_discount", $request, "id=$id")) {
    }
    return $response;
}

function getDiscounts($uid)
{
    $db = new DbHandler();
    $sql = "select * from eco_discount where uid='" . $uid . "' and is_delete<>1";
    return $db->getAllRecord($sql);
}
function getDiscountById($id)
{
    $db = new DbHandler();
    $sql = "select * from eco_discount where id='" . $id . "'";
    return $db->getOneRecord($sql);
}
?>