<?php
$app->post('/GetSaleProducts', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $response["record"] = getSaleProductWithAdditionalInfo($r->uid);
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/GetSaleProductsByExhibition', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $exhibitorList = getExhibitorListByExhibitionId($r->eid);
    $finalArr = [];
    foreach ($exhibitorList as $key => $exhibitor) {
        $arr = getSaleProductWithAdditionalInfo($exhibitor['uid']);
        array_push($finalArr, ...$arr);
    }
    $response['record'] = $finalArr;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/DeleteDiscount', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $query = "update eco_discount set is_delete=1  where id='$r->id';";
    $db->updateTableValue($query);
    $response["status"] = "success";
    echoResponse(200, $response);
});


$app->get('/GetStates', function () use ($app) {
    $response = array();
    $response["states"] = GetStates();
    $response["status"] = "success";
    echoResponse(200, $response);
});
$app->get('/GetUnitTypes', function () use ($app) {
    $response = array();
    $response["record"] = GetUnitTypes();
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/UpdateSalesProductDetails', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $response = saveSaleProduct($r);
    echoResponse(200, $response);
});


$app->get('/GetExhibitorSaleProduct', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $exhibitorId = $app->request()->get('ExhibitorId');

    $response["record"] = getSaleProductById($Id, $exhibitorId);
    $UnitPricing = getSaleProductUnits($Id);
    $response["UnitPricing"] = $UnitPricing;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/DeleteSaleProduct', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = deleteSaleProduct($r->id);
    echoResponse(201, $response);
});

function getSaleProductWithAdditionalInfo($uid)
{
    $products = getSaleProductByUid($uid);
    foreach ($products as $key => $product) {
        $products[$key]['UnitPricing']  = getSaleProductUnits($product['id']);
        $products[$key]['Company'] = getUserByUId($product['exhibitorId']);
        $products[$key]['Exhibition'] = getExhibitionByExhibitorId($product['exhibitorId']);
    }
    return $products;
}

function getUserByUId($uid)
{
    $db = new DbHandler();
    $sql = "SELECT `uid`, `name`, `email`, `email1`, `email2`, `email3`, `mobile`, `secondary_mobile`, `mobile1`, `mobile2`, `mobile3`, `tawkto`, `address`, `city`, `created`, `userType`, `companyName`, `organizer`, `CompanyKey`, `ImagePath`, `custom_logo`, `companyWebsite`, `companyDescription`, `conMobile`, `concernedPersonName`, `postalCode`, `country`, `state`, `newTechnologies`, `newProductLaunch`, `interestArea`, `boothNumber`, `orderId`, `packageName`, `countOfproduct`, `skypeId`, `viewCount`, `sms_status`, `email_status`, `link`, `Bgcolor`, `BoothTemplate`, `ChairDesign`, `DeskDesign`, `PlantDesign`, `boothalignment`, `compvideo`, `ProductBrochure`, `ProductBrochureLink`, `keywords`, `status`, `BoothStatus`, `canLogin`, `ProductPriceList`, `ProductCatalog`, `ProductTechSpecSheet`, `is360View`, `FacebookUrl`, `InstagramUrl`, `TwitterUrl`, `LinkedInUrl`, `VisitingCard`, `meetingId`, `audienceId`, `Sponsor`, `isTemplateUser`, `PaymentStatus` FROM `users` WHERE uid=$uid";
    return  $db->getOneRecord($sql);
}

function getSaleProductUnitById($id)
{
    $db = new DbHandler();
    $sql = "SELECT up.*, ut.name as unit_type FROM `eco_product_units` up inner join eco_unit_type ut on ut.id = up.unit_type_id  WHERE up.is_delete<>1 and up.id=$id";
    $unitPrice = $db->getOneRecord($sql);
    $unitPrice['discount'] = getDiscountById($unitPrice['discount_id']);
    return $unitPrice;
}

function getSaleProductUnits($id)
{
    $db = new DbHandler();
    $sql = "SELECT up.*, ut.name as unit_type FROM `eco_product_units` up inner join eco_unit_type ut on ut.id = up.unit_type_id  WHERE  up.is_delete<>1 and `product_id`=$id";
    $unitPrices = $db->getAllRecord($sql);
    foreach ($unitPrices as $key => $unitPrice) {
        $unitPrices[$key]['discount'] = getDiscountById($unitPrice['discount_id']);
    }
    return $unitPrices;
}

function getSaleProductById($id, $exhibitorId)
{
    $db = new DbHandler();
    $sql = "SELECT * FROM `exhibitionproductdetail` WHERE `id`=$id and exhibitorId=$exhibitorId and 1=1 ";
    return $db->getOneRecord($sql);
}

function saveSaleProduct($r)
{
    $db = new DbHandler();
    if ($r->Action == "Edit") {
        $id = $r->id;
        $exhibitorId = $r->exhibitorId;
        unset($r->Action);

        $folderUrl = "api/v1/ProductImages/";
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "p1_" . $r->exhibitorId . "_" . $date;
        if ($r->image_1_upload == true) {
            $r->image_sm_1 = uploadImage($praImageName . "-sm", $folderUrl, "ProductImages/", $r->image_sm_1);
            $r->image_lg_1 = uploadImage($praImageName . "-md", $folderUrl, "ProductImages/", $r->image_lg_1);
        } else {
            unset($r->image_sm_1);
            unset($r->image_lg_1);
        }

        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "p2_" . $r->exhibitorId . "_" . $date;
        if ($r->image_2_upload == true) {
            $r->image_sm_2 = uploadImage($praImageName . "-sm", $folderUrl, "ProductImages/", $r->image_sm_2);
            $r->image_lg_2 = uploadImage($praImageName . "-md", $folderUrl, "ProductImages/", $r->image_lg_2);
        } else {
            unset($r->image_sm_2);
            unset($r->image_lg_2);
        }
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "p3_" . $r->exhibitorId . "_" . $date;
        if ($r->image_3_upload == true) {
            $r->image_sm_3 = uploadImage($praImageName . "-sm", $folderUrl, "ProductImages/", $r->image_sm_3);
            $r->image_lg_3 = uploadImage($praImageName . "-md", $folderUrl, "ProductImages/", $r->image_lg_3);
        } else {
            unset($r->image_sm_3);
            unset($r->image_lg_3);
        }
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "p4_" . $r->exhibitorId . "_" . $date;
        if ($r->image_4_upload == true) {
            $r->image_sm_4 = uploadImage($praImageName . "-sm", $folderUrl, "ProductImages/", $r->image_sm_4);
            $r->image_lg_4 = uploadImage($praImageName . "-md", $folderUrl, "ProductImages/", $r->image_lg_4);
        } else {
            unset($r->image_sm_4);
            unset($r->image_lg_4);
        }
        unset($r->image_1_upload); // Remove field as it not useful.
        unset($r->image_2_upload); // Remove field as it not useful.
        unset($r->image_3_upload); // Remove field as it not useful.
        unset($r->image_4_upload); // Remove field as it not useful.
        unset($r->exhibitorId); // Remove field as it not useful.
        $UnitPricing = $r->UnitPricing;
        unset($r->UnitPricing); // Remove field as it not useful.

        if ($db->dbRowUpdate("eco_products", $r, "`id`=$id and uid=$exhibitorId")) {
            $response["status"] = "success";
            $response["message"] = "Product/Service details updated successfully.";
            addUnitPrices($UnitPricing, $id);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to update product. Please try again.";
        }
    } else {
        unset($r->Action);
        $folderUrl = "api/v1/ProductImages/";
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "p1_" . $r->exhibitorId . "_" . $date;
        if ($r->image_1_upload == true) {
            $r->image_sm_1 = uploadImage($praImageName . "sm", $folderUrl, "ProductImages/", $r->image_sm_1);
            $r->image_lg_1 = uploadImage($praImageName . "lg", $folderUrl, "ProductImages/", $r->image_lg_1);
        } else {
            $r->image_lg_1 = "";
            $r->image_sm_1 = "";
        }

        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "p2_" . $r->exhibitorId . "_" . $date;
        if ($r->image_2_upload == true) {
            $r->image_sm_2 = uploadImage($praImageName . "sm", $folderUrl, "ProductImages/", $r->image_sm_2);
            $r->image_lg_2 = uploadImage($praImageName . "lg", $folderUrl, "ProductImages/", $r->image_lg_2);
        } else {
            $r->image_lg_2 = "";
            $r->image_sm_2 = "";
        }

        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "p3_" . $r->exhibitorId . "_" . $date;
        if ($r->image_3_upload == true) {
            $r->image_sm_3 = uploadImage($praImageName . "sm", $folderUrl, "ProductImages/", $r->image_sm_3);
            $r->image_lg_3 = uploadImage($praImageName . "lg", $folderUrl, "ProductImages/", $r->image_lg_3);
        } else {
            $r->image_lg_3 = "";
            $r->image_sm_3 = "";
        }
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "p4_" . $r->exhibitorId . "_" . $date;
        if ($r->image_4_upload == true) {
            $r->image_sm_4 = uploadImage($praImageName . "sm", $folderUrl, "ProductImages/", $r->image_sm_4);
            $r->image_lg_4 = uploadImage($praImageName . "lg", $folderUrl, "ProductImages/", $r->image_lg_4);
        } else {
            $r->image_lg_4 = "";
            $r->image_sm_4 = "";
        }
        unset($r->image_1_upload); // Remove field as it not useful.
        unset($r->image_2_upload); // Remove field as it not useful.
        unset($r->image_3_upload); // Remove field as it not useful.
        unset($r->image_4_upload); // Remove field as it not useful.
        $r->uid = $r->exhibitorId;
        $tabble_name = "eco_products";
        $column_names = array(
            'uid', 'description', 'name', 'product_video',  'image_sm_1', 'image_lg_1', 'image_sm_2', 'image_lg_2', 'image_sm_3', 'image_lg_3', 'image_sm_4', 'image_lg_4'
        );
        $productId = $db->insertIntoTable($r, $column_names, $tabble_name);
        addUnitPrices($r->UnitPricing, $productId);

        if ($productId != null) {
            $response["status"] = "success";
            $response["message"] = "Product/Service details inserted successfully.";
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to insert product. Please try again.";
        }
    }
    return $response;
}

function addUnitPrices($unitPrices, $productId)
{
    // try {
    $db = new DbHandler();
    $query = "update eco_product_units set is_delete=1  where product_id='$productId';";
    $db->updateTableValue($query);
    foreach ($unitPrices as $unitPrice) {
        $tabble_name = "eco_product_units";
        $obj = new stdClass();
        $obj->product_id = $productId;
        $obj->unit_value = $unitPrice->unit_value;
        $obj->unit_type_id = $unitPrice->unit_type_id;
        $obj->unit_price = $unitPrice->unit_price;
        $obj->base_price = $unitPrice->base_price;

        if (isset($obj->cgst_percentage)) {
            $obj->cgst_percentage = $unitPrice->cgst_percentage == '' || $unitPrice->cgst_percentage == null ? 0 : $unitPrice->cgst_percentage;
        } else {
            $obj->cgst_percentage = 0;
        }

        if (isset($obj->sgst_percentage)) {
            $obj->sgst_percentage = $unitPrice->sgst_percentage == '' || $unitPrice->sgst_percentage == null ? 0 : $unitPrice->sgst_percentage;
        } else {
            $obj->sgst_percentage = 0;
        }

        if (isset($obj->igst_percentage)) {
            $obj->igst_percentage = isset($obj->igst_percentage) && ($unitPrice->igst_percentage == '' || $unitPrice->igst_percentage == null) ? 0 : $unitPrice->igst_percentage;
        } else {
            $obj->igst_percentage = 0;
        }
        if(isset($unitPrice->discount_id))
        {
            $obj->discount_id = $unitPrice->discount_id == '' || $unitPrice->discount_id == null ? 0 : $unitPrice->discount_id;
        }else{
            $obj->discount_id = 0;
        }
        $obj->quantity = $unitPrice->quantity;
        $obj->is_delete = 0;
        $column_names = array("unit_type_id", "unit_value", "base_price", "unit_price", "discount_id", "quantity", "product_id", "cgst_percentage", "sgst_percentage", "igst_percentage", "is_delete");
        $db->insertIntoTable($obj, $column_names, $tabble_name);
    }
    return true;
    // } catch (Exception $ex) {
    //     echo 'exception';
    //     return false;
    // }
}

function deleteUnitPricesByProductId($productId)
{
    $response = array();
    $db = new DbHandler();
    if ($productId != 0) {
        $query = "update eco_product_units set is_delete=1  where product_id='$productId';";
        $db->updateTableValue($query);
    }
    $response["status"] = "success";
    $response["message"] = "Delete successfully.";
    return $response;
}
function deleteSaleProduct($id)
{
    $response = array();
    $db = new DbHandler();
    if ($id != 0) {
        $db->deleteRecord("delete from eco_products where id=" . $id);
    }
    $response["status"] = "success";
    $response["message"] = "Delete successfully.";
    return $response;
}

function getSaleProductByUid($uid)
{
    $db = new DbHandler();
    $sql = "select * from exhibitionproductdetail where is_ecommerce=1 and exhibitorId='" . $uid . "'";
    return $db->getAllRecord($sql);
}
function GetUnitTypes()
{
    $db = new DbHandler();
    $sql = "select * from eco_unit_type";
    return $db->getAllRecord($sql);
}
function GetStates()
{
    $db = new DbHandler();
    $sql = "select * from eco_states";
    return $db->getAllRecord($sql);
}
