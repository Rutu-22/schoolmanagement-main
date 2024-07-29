<?php

$app->post('/PlaceOrder', function () use ($app) {

    $r = json_decode($app->request->getBody());
    $response = placeOrder($r);
    echoResponse(201, $response);
});

$app->post('/UpdateOrder', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $result = UpdateOrder($r);
    if ($result) {
        $response["order"] = $r;
        $response["status"] = "success";
    } else {
        $response["order"] = $r;
        $response["status"] = "failed";
    }
    echoResponse(201, $response);
});

$app->get('/GetOrderGrid', function () use ($app) {
    $Id =  $app->request()->get('uid');
    $userType =  $app->request()->get('utype');
    $exhibitionId =  $app->request()->get('eid');
    $db = new DbHandler();
    $requestData = $_REQUEST;
    $sql = "";
    if ($userType == 'Organizer' || $userType == 'OrganizerOperator') {
        $sql = "SELECT count(1) as Count FROM `eco_order` o where o.id in (SELECT eo.orderId FROM `eco_exhibitor_order` eo  inner join usermap um on eo.exhibitorId = um.uid and um.exhibitionId =$exhibitionId)";
    } else {
        $sql = "SELECT count(1) as Count FROM `eco_order` o inner join eco_delivery d on o.delivery_id = d.id inner join eco_order_details od on od.order_id = o.id and od.exhibitor_id = $Id group by o.id";
    }
    $NoOfRecords = $db->getOneRecord($sql);
    $totalFiltered = $NoOfRecords["Count"];
    $totalData = $NoOfRecords["Count"];
    $sql = "";
    if ($userType == 'Organizer' || $userType == 'OrganizerOperator') {
        $sql = "SELECT o.*,d.name,d.mobile,d.email, d.address, d.pincode, d.state, d.city FROM `eco_order` o inner join eco_delivery d on o.delivery_id = d.id inner join eco_order_details od on od.order_id = o.id where o.id in (SELECT eo.orderId FROM `eco_exhibitor_order` eo  inner join usermap um on eo.exhibitorId = um.uid and um.exhibitionId =$exhibitionId) group by o.id ";
    } else {
        $sql = "SELECT o.*,d.name,d.mobile,d.email, d.address, d.pincode, d.state, d.city FROM `eco_order` o inner join eco_delivery d on o.delivery_id = d.id inner join eco_order_details od on od.order_id = o.id and od.exhibitor_id = $Id group by o.id ";
    }

    if (!empty($requestData['search']['value'])) {
        $sql .= " AND ( d.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR d.mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR d.email LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR o.payment_status LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR d.address LIKE '" . $requestData['search']['value'] . "%') ";
    }
    $sql .= " order by o.id desc ";
    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => checkIspaidExhibitor($Id) ? $query : decriptDataIfUserIsUnpaid($query)   // total data array
        //   "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});
$app->get('/GetOrderById', function () use ($app) {
    $Id =  $app->request()->get('id');
    $uid =  $app->request()->get('uid');
    $uType =  $app->request()->get('uType');
    if ($uType) {
    }
    $response = array();
    $orders = getOrderInfoByOrderId($Id);
    if (!checkIspaidExhibitor($uid)) {
        $orders['mobile'] = encriptMobile($orders['mobile']);
        $orders['email'] = encriptMobile($orders['email']);
    }
    $response['order'] = $orders;
    $response['offer'] = getOfferById($orders['offer_id']);
    $response['exhibitor'] = getExhibitorId($uid);
    if ($uType == 'Manager') {
        $response['orderWithExhibitor'] = false;
        $response['orderSummary'] = getOrderSummaryByOrderIdAndExhibitorId($Id, $uid);
        $response['orderPriceSummary'] = getExhibitorOrderInfoByOrderId($Id, $uid);
    } else {
        $orderFromExhibitors = getExhibitorsByOrderId($Id);
        foreach ($orderFromExhibitors as $key => $oe) {
            $orderFromExhibitors[$key]['orderSummary'] = getOrderSummaryByOrderIdAndExhibitorId($Id, $oe['exhibitorId']);
        }
        $response['orderSummary'] = $orderFromExhibitors;
        $response['orderWithExhibitor'] = true;
        $response['orderPriceSummary'] = getExhibitorOrderSumForOrganizer($Id);
    }
    $response['status'] = 'success';
    echoResponse(201, $response);
});

$app->get('/GetUserOrders', function () use ($app) {
    $uid =  $app->request()->get('uid');
    $response = array();
    $orders = getOrderInfoByUserId($uid);
    foreach ($orders as $key => $order) {
        $orders[$key]['offer'] = getOfferById($order['offer_id']);
        $orders[$key]['order_exhibitor'] = getExhibitorsByOrderId($order['id']);
        foreach ($orders[$key]['order_exhibitor'] as $k => $oe) {
            $orders[$key]['order_exhibitor'][$k]['orderSummary'] = getOrderSummaryByOrderIdAndExhibitorId($order['id'], $oe['exhibitorId']);
        }
    }
    $response['orders'] = $orders;
    $response['status'] = 'success';
    echoResponse(201, $response);
});

function getExhibitorsByOrderId($orderId)
{
    $db = new DbHandler();
    $sql = "SELECT eo.*, u.name,u.uid,u.mobile,u.organizer FROM `eco_exhibitor_order` eo inner join users u on eo.exhibitorId = u.uid where eo.orderId = $orderId";
    return $db->getAllRecord($sql);
}
function getOrderInfoByUserId($id)
{
    $db = new DbHandler();
    $sql = "select o.*,d.name, d.landmark, d.pincode, d.address, d.mobile, d.email, d.city, d.state from eco_order o inner join eco_delivery d on d.id = o.delivery_id where o.uid = $id";
    return $db->getAllRecord($sql);
}

function getOrderInfoByOrderId($id)
{
    $db = new DbHandler();
    $sql = "select o.*,d.name, d.landmark, d.pincode, d.address, d.mobile, d.email, d.city, d.state from eco_order o inner join eco_delivery d on d.id = o.delivery_id where o.id = $id";
    return $db->getOneRecord($sql);
}

function getExhibitorOrderInfoByOrderId($id, $exhibitorId)
{
    $db = new DbHandler();
    $sql = "select  * from eco_exhibitor_order  where orderId = $id and exhibitorId=$exhibitorId";
    return $db->getOneRecord($sql);
}

function getExhibitorOrderSumForOrganizer($id)
{
    $db = new DbHandler();
    $sql = "select orderid, sum(finalAmt) as finalAmt,sum(totalDeliveryFee) as totalDeliveryFee,SUM(totalGST) as totalGST,sum(totalFee) as totalFee, sum(totalDiscount) as totalDiscount, sum(totalPayAmount)as totalPayAmount from eco_exhibitor_order where orderid = $id group by orderid";
    return $db->getOneRecord($sql);
}

function getExhibitorOrderInfoForOrganizer($id)
{
    $db = new DbHandler();
    $sql = "select eo.*, u.uid,u.name,u.email,u.mobile,u.address,u.ImagePath from eco_exhibitor_order eo inner join users u on eo.exhibitorId=u.uid where eo.orderid = $id";
    return $db->getAllRecord($sql);
}

function getOrderSummaryByOrderIdAndExhibitorId($orderId, $exhibitorId)
{
    $db = new DbHandler();
    $sql = "SELECT od.*,ut.name as unit_type,p.product_decription, p.product_name as product_name ,p.product_image as product_image, 
    pu.unit_value,pu.unit_price,pu.base_price,pu.igst_percentage,pu.sgst_percentage,pu.cgst_percentage, d.discount_type,d.discount_value,d.description as discount_description 
    FROM `eco_order_details` od 
    left join eco_product_units pu on od.product_unit_id = pu.id 
    left join exhibitionproductdetail p on od.product_id=p.id 
    left join eco_discount d on d.id= od.discount_id 
    left join eco_unit_type ut on ut.id = pu.unit_type_id  
    where od.order_id = $orderId and od.exhibitor_id = $exhibitorId";
    return $db->getAllRecord($sql);
}

function getOrderSummaryByOrderId($orderId)
{
    $db = new DbHandler();
    $sql = "SELECT od.*,ut.name as unit_type,p.product_decription, p.product_name as product_name,p.product_image as product_image, 
    pu.unit_value,pu.unit_price,pu.base_price,pu.igst_percentage,pu.sgst_percentage,pu.cgst_percentage, d.discount_type,d.discount_value,d.description as discount_description 
    FROM `eco_order_details` od 
    left join eco_product_units pu on od.product_unit_id = pu.id 
    left join exhibitionproductdetail p on od.product_id=p.id 
    left join eco_discount d on d.id= od.discount_id 
    left join eco_unit_type ut on ut.id = pu.unit_type_id  
    where od.order_id = $orderId";
    return $db->getAllRecord($sql);
}

function getDeliveryInfo($orderId)
{
    $db = new DbHandler();
    $sql = "select * from eco_order_details where order_id = $orderId";
    return $db->getAllRecord($sql);
}

function UpdateOrder($request)
{
    if (isset($request->order_id) && $request->order_id > 0) {
        $db = new DbHandler();
        $query = "update eco_order set razorpay_payment_id='$request->razorpay_payment_id', payment_status='$request->order_status',razorpay_order_id='$request->razorpay_order_id',razorpay_signature='$request->razorpay_signature'  where id='$request->order_id';";
        $db->updateTableValue($query);
        if ($request->order_status == 'Success') {
            sendOrderReceivedSMS($request->order_id);
            sendOrderConfirmSMS($request->order_id);
        }
        return true;
    }
    return false;
}

function sendOrderReceivedSMS($order_id)
{
    $response = array();
    $orderFromExhibitors = getExhibitorsByOrderId($order_id);
    foreach ($orderFromExhibitors as $k => $oe) {
        $text = getOrderReceivedTemplate();
        $text = str_replace("##username##", $oe['name'], $text);
        $text = str_replace("##link##", (GetHostUrl() . "/portal/#/" . $oe['organizer'] . "/login"), $text);
        $response['smsStatus_'.$oe['uid']]= sendSMS($text, $oe['mobile'], $oe['uid'], 0);
    }
    return $response;
}

function getExhibitionByOrderedUserId($userId)
{
    $db = new DbHandler();
    return $db->getOneRecord("SELECT e.* FROM `users` u  inner join  exhibition e on u.organizer = e.link WHERE uid = $userId");
}
function sendOrderConfirmSMS($order_id)
{
    $db = new DbHandler();
    $user = $db->getOneRecord("SELECT u.* FROM `eco_order` o inner join eco_delivery d on o.delivery_id = d.id inner join users u on u.uid = d.uid and o.id =" . $order_id);
    if ($user != null) {
        $exhibition = getExhibitionByOrderedUserId($user['uid']);
        if ($exhibition != null) {
            $exhibitionId = $exhibition['id'];
        } else {
            $exhibitionId = 0;
        }
        $text = getOrderConfirmTemplate();
        $text = str_replace("##username##", $user['name'], $text);
        $text = str_replace("##link##", (GetHostUrl() . "/portal/#/" . $user['organizer'] . "/login"), $text);
      return sendSMS($text, $user['mobile'], 0, $exhibitionId);
    }
}

function placeOrder($request)
{
    $response = array();
    if ($request->user->customer->uid == 0) {
        $request->user->customer->userType = "visitor";
        $user = registerAsVisitor($request->user);
        if (!isset($user['uid'])) {
            $request->delivery->uid = $user['existingUser']['uid'];
        } else {
            $request->delivery->uid = $user['uid'];
        }
    }

    $delivery_Id = saveDelivery($request->delivery);
    $request->order->delivery_id = $delivery_Id;
    $request->order->uid = $request->delivery->uid;

    $order_Id = saveOrder($request);
    saveOrderDetails($request->order_details, $order_Id);
    addExhibitorOrder($request->order_exhibitors, $order_Id);
    $response['order_id'] = $order_Id;
    $response['status'] = 'success';
    return $response;
}

function saveOrder($request)
{
    //eco_order
    $db = new DbHandler();
    $table_name = "eco_order";
    $column_names = array('total_amount', 'discount_amount', 'final_amount', 'offer_amount', 'offer_id', 'delivery_id', 'payment_status', 'uid', 'browser_id', 'exhibitor_id', 'gst_amount', 'fee_amount');
    $order_Id = $db->insertIntoTable($request->order, $column_names, $table_name);
    return $order_Id;
}

function addExhibitorOrder($exhibitorOrders, $order_Id)
{
    $db = new DbHandler();
    foreach ($exhibitorOrders as $exhibitorOrder) {
        $exhibitorOrder->orderId = $order_Id;
        $tabble_name = "eco_exhibitor_order";
        $column_names = array("exhibitorId", "finalAmt", "totalDeliveryFee", "totalGST", "totalPayAmount", "totalProduct", "orderId", "totalFee", "totalDiscount");
        $db->insertIntoTable($exhibitorOrder, $column_names, $tabble_name);
    }
    return true;
}

function saveOrderDetails($order_details, $order_Id)
{
    $db = new DbHandler();
    foreach ($order_details as $index => $order_detail) {
        $order_detail->order_id = $order_Id;
        $table_name = "eco_order_details";
        $column_names = array('product_id', 'product_unit_id', 'order_id', 'product_amount', 'discount_amount', 'quantity', 'discount_id', 'exhibitor_id');
        $db->insertIntoTable($order_detail, $column_names, $table_name);
    }
    return true;
}

function saveDelivery($delivery)
{
    $db = new DbHandler();
    $table_name = "eco_delivery";
    $column_names = array('name', 'landmark', 'pincode', 'address', 'mobile', 'email', 'uid', 'city', 'state');
    $delivery_Id = $db->insertIntoTable($delivery, $column_names, $table_name);
    return $delivery_Id;
}
