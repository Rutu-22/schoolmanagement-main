<?php
$app->get('/AllExhibitor', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $userType = $_SESSION['userType'];
    if ($userType == 'Admin') {
        $sql = "SELECT u.*,e.name as exhibitionName, e.startDate, e.endDate, e.address FROM users u inner join usermap um on u.uid=um.uid inner join exhibition e on e.id = um.exhibitionId and e.isDelete<>1 ";
        $record = $db->getAllRecord($sql);
        $response["list"] = $record;
    }else{
    $response["list"] = $userType;
    }
    $response["status"] = "success";
    echoResponse(200, $response);
});
?>