<?php
$app->get('/GetFeaturesWithCategory', function () use ($app) {
    $db = new DbHandler();
    $sql = "select * from pacakgefeatures";
    $features = $db->getAllRecord($sql);
    $sql = "select * from featurecategory";
    $featurecategory = $db->getAllRecord($sql);
    $response = array();
    $response['features'] = $features;
    $response['featureCategory'] = $featurecategory;
    $response['success'] = "success";
    echoResponse(200, $response);
});
$app->get('/GetPacakgeFeatures', function () use ($app) {
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    if( $_SESSION['userType']=='Manager' || $_SESSION['userType']=='ExhibitorUser')
    $sql =   "SELECT * from pacakgefeatures pf 
    inner join pacakgefeaturemap pfm on pf.id =pfm.featureId 
    inner join exhibitorpackagemap epm on epm.packageId = pfm.packageId and epm.exhibitorId = " . $_SESSION['uid'];
    else{
      $sql = "SELECT us.* from usersetting us where uid=".$_SESSION['uid']; 
    }
    $response['packageFeatures'] = $db->getAllRecord($sql);
    $response['success'] = "success";
    $response['userType'] = $_SESSION['userType'];
    echoResponse(200, $response);
});
$app->get('/GetTemplates', function () use ($app) {
    $db = new DbHandler();
    $sql = "SELECT u.name,u.email,b.id as TemplateId,us.allowedProductForTemplate FROM `users` u inner join booth_editor b on u.uid = b.uid inner join usersetting us on us.uid = u.uid   where u.isTemplateUser =1";
    $response['templates'] = $db->getAllRecord($sql);
    $response['success'] = "success";
    echoResponse(200, $response);
});


$app->get('/checkIsNameExist', function () use ($app) {
    $db = new DbHandler();
    $name =  $app->request()->get('name');

    if (!isset($_SESSION)) {
        session_start();
    }
    $organizerId = $_SESSION['uid'];
    $sql = "select count(1) as isExist from exhibitorpackage where name = '$name' and organizerId = " . $organizerId;
    $pack = $db->getOneRecord($sql);
    $response = array();
    $response['record'] = $pack;
    $response['success'] = "success";
    echoResponse(200, $response);
});
$app->get('/GetPackageNames', function () use ($app) {
    $db = new DbHandler();

    if (!isset($_SESSION)) {
        session_start();
    }
    $organizerId = $_SESSION['uid'];
    $sql = "select * from exhibitorpackage where organizerId = " . $organizerId;
    $pack = $db->getAllRecord($sql);
    $response = array();
    $response['packages'] = $pack;
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->get('/getPackages', function () use ($app) {
    $db = new DbHandler();
    $sql = "";
    $id =  $app->request()->get('id');
    if (!isset($_SESSION)) {
        session_start();
    }
    $organizerId = $_SESSION['uid'];

    if ($id) {
        $sql = "select * from exhibitorpackage where id =" . $id;
    } else {
        $sql = "select * from exhibitorpackage where organizerId =$organizerId";
    }
    $packages = $db->getAllRecord($sql);

    foreach ($packages as $key => $package) {
        $sql = "SELECT ep.id as packageId,ep.name as Name,ep.price as Price,ep.currency,pf.*,pfm.* 
        FROM exhibitorpackage ep 
                    inner join `pacakgefeaturemap` pfm on ep.id = pfm.packageId
                    inner join `pacakgefeatures` pf on pf.id= pfm.featureId
                        where ep.organizerId =$organizerId and ep.id =" . $package["id"];
        $featureList = $db->getAllRecord($sql);
        $packages[$key]['features'] = $featureList;
    }
    $response = array();
    $response['packages'] = $packages;
    $response['category'] = $db->getAllRecord("select * from featurecategory");
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->post('/DeletePackage', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $organizerId = $_SESSION['uid'];
    if (isset($r->id)) {
        $db->deleteRecord("delete from exhibitorpackage where id=" . $r->id);
        $db->deleteRecord("delete from pacakgefeaturemap where packageId=" . $r->id);
        $response["status"] = "success";
        $response["message"] = "Deleted successfully.";
        echoResponse(200, $response);
    } else {

        $response["status"] = "success";
        $response["message"] = "Saved successfully.";
        echoResponse(200, $response);
    }
});

$app->post('/AddOrUpdatePackage', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $organizerId = $_SESSION['uid'];
    if (isset($r->Package) && !isset($r->Package->id)) {
        $r->Package->organizerId = $organizerId;
        $tabble_name = "exhibitorpackage";
        $column_names = array("name", "price", "currency", "organizerId","validity","TemplateId","extendprice1","extendvalidity1","extendprice2","extendvalidity2","extendprice3","extendvalidity3","extendprice4","extendvalidity4","extendprice5","extendvalidity5");
        $pacakgeId = $db->insertIntoTable($r->Package, $column_names, $tabble_name);
        foreach ($r->FeatureList as $feature) {
            if (isset($feature->id)) {
                $tabble_name = "pacakgefeaturemap";
                $obj = new stdClass();
                $obj->featureId = $feature->id;
                $obj->value = isset($feature->value) ? $feature->value : '';
                $obj->isApplied = isset($feature->IsChecked) && $feature->IsChecked==true ? 1 : 0;
                if($obj->isApplied ==''){
                    $obj->isApplied =0;
                }

                $obj->packageId = $pacakgeId;
                $column_names = array("featureId", "packageId", "isApplied", "value");
                $db->insertIntoTable($obj, $column_names, $tabble_name);
            }
        }
        $response["status"] = "success";
        $response["message"] = "Saved successfully.";
        echoResponse(200, $response);
    } else {
        unset($r->features);
        $obj = new stdClass();
        $obj->id = $r->Package->id;
        $obj->name = $r->Package->name;
        $obj->price = $r->Package->price;
        $obj->currency = $r->Package->currency;
        $obj->validity = $r->Package->validity;
        $obj->extendprice1 = $r->Package->extendprice1;
        $obj->extendprice2 = $r->Package->extendprice2;
        $obj->extendprice3 = $r->Package->extendprice3;
        $obj->extendprice4 = $r->Package->extendprice4;
        $obj->extendprice5 = $r->Package->extendprice5;
        $obj->extendvalidity1 = $r->Package->extendvalidity1;
        $obj->extendvalidity2 = $r->Package->extendvalidity2;
        $obj->extendvalidity3 = $r->Package->extendvalidity3;
        $obj->extendvalidity4 = $r->Package->extendvalidity4;
        $obj->extendvalidity5 = $r->Package->extendvalidity5;
        $db->dbRowUpdate("exhibitorpackage", $obj, "id=" . $r->Package->id);
        $db->deleteRecord("delete from pacakgefeaturemap where packageId=" . $obj->id);

        foreach ($r->FeatureList as $feature) {
            if (isset($feature->id)) {
                $tabble_name = "pacakgefeaturemap";
                $obj = new stdClass();
                $obj->featureId = $feature->id;
                $obj->value = isset($feature->value) ? $feature->value : '';
                $obj->isApplied = isset($feature->IsChecked) && $feature->IsChecked==true ? 1 : 0;
                if($obj->isApplied == ''){
                    $obj->isApplied = 0;
                }
                $obj->packageId = $r->Package->id;
                $column_names = array("featureId", "packageId", "isApplied", "value");
                $db->insertIntoTable($obj, $column_names, $tabble_name);
            }
        }

        $response["status"] = "success";
        $response["message"] = "Updated successfully.";
        echoResponse(200, $response);
    }
});
