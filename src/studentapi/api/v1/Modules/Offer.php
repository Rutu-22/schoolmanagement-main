<?php
$app->post('/DeleteOffer', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $query = "update eco_offer set is_delete=1  where id='$r->id';";
    $db->updateTableValue($query);
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetOffers', function () use ($app) {
    $uid =  $app->request()->get('uid');
    $response = array();
    $response["record"] = getOffers($uid);
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/CheckCouponCodeExistOrNot', function () use ($app) {
    $exhibitorId =  $app->request()->get('exhibitorId');
    $coupon =  $app->request()->get('code');
    $response = array();
    $response["record"] = checkCouponCodeExistOrNot($exhibitorId,$coupon);
    $response["status"] = "success";
    echoResponse(200, $response);
});
$app->post('/SaveOffer', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    if($r->id == 0){
        $response = SaveOffer($r);
        $response["msg"] = "Create successfully";
    }else{
        $response = UpdateOffer ($r,$r->id);
        $response["status"] = "success";
        $response["msg"] = "Update successfully";
    }
    echoResponse(200, $response);
});

function checkCouponCodeExistOrNot($exhibitorId,$coupon){
    $db = new DbHandler();
    $sql = "SELECT o.* FROM `eco_offer` o 
    INNER join exhibition e on o.exhibition_id = e.id and o.coupon = '$coupon' 
    inner join usermap um on um.exhibitionId = o.exhibition_id and um.uid = $exhibitorId 
      and o.is_delete<>1";
    return $db->getOneRecord($sql);
}

function SaveOffer ($request){
    $response = array();
    $db = new DbHandler();
    $tabble_name = "eco_offer";
    $column_names = array('uid', 'offer_type', 'offer_value', 'description','exhibition_id','coupon');
    $offerId = $db->insertIntoTable($request, $column_names, $tabble_name);
    $response["offer_id"] = $offerId;
    return $response;
}

function UpdateOffer ($request,$id){
    $response = array();
    $db = new DbHandler();
    if ($db->dbRowUpdate("eco_offer", $request, "id=$id")) {
    }
    return $response;
}

function getOffers($uid)
{
    $db = new DbHandler();
    $sql = "select * from eco_offer where uid='" . $uid . "' and is_delete<>1";
    return $db->getAllRecord($sql);
}
function getOfferById($id)
{
    $db = new DbHandler();
    $sql = "select * from eco_offer where id='" . $id . "'";
    return $db->getOneRecord($sql);
}
?>