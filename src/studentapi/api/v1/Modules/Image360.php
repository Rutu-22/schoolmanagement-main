<?php
$app->post('/AddOrUpdateImage360Upload', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    ;
    $tabble_name = "image360upload";

    $folderUrl = "api/v1/images-360/thumbnail/";
    $date = date('m-d-Y-h-i-s-m', time());
    $praImageName = "360_" . $r->uid . "_" . $date;
    $r->path = uploadImage($praImageName . "-sm", $folderUrl, "images-360/thumbnail/", $r->image_360);
    if (!isset($r->eid)) {
        $r->eid = 0;
    }
    $column_names = array("name", "path", "uid", "eid");
    $result = $db->insertIntoTable($r, $column_names, $tabble_name);
    if ($result != null) {
        $response["status"] = "success";
        $response['Id'] = $result;
        $id = "";
        $id .= strval($result);
        $response['savedImage'] = $db->getOneRecord("select * from image360upload where id=$id");
        $response["message"] = "We have uploaded selected image successfully. There is optimization process is going on for high resolution. It may take 1 min or more.";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to insert 360 Image. Please try again.";
        echoResponse(201, $response);
    }
});

$app->post('/add360ImagesFromTemplate', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($r->eid)) {
        $r->eid = 0;
    }
    foreach ($r->imageList as $index => $imageTemplate) {
        $tabble_name = "image360upload";
        $imageTemplate->eid = $r->eid;
        $column_names = array("name", "path", "uid", "image_360_lg", "eid");
        $result = $db->insertIntoTable($imageTemplate, $column_names, $tabble_name);
        $r[$index]->id = $result;
    }
    if ($result != null) {
        $response["status"] = "success";
        $response["addedImages"] = $r;
        $response["message"] = "Saved successfully.";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to insert 360 Image. Please try again.";
        echoResponse(201, $response);
    }
});


$app->post('/UpdateEditedIcon', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $r->uid = $_SESSION['uid'];
    $ids = "";
    $tabble_name = "booth_editor_icons";
    for ($index = 0; $index < count($r->image_Icon); $index++) {
        $folderUrl = "api/v1/images-360/thumbnail/";
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = $index . "-hotspot-image-" . $r->uid . "_" . $date;
        $r->path = uploadImage($praImageName . "-sm", $folderUrl, "images-360/thumbnail/", $r->image_Icon[$index]);
        $unConvertedImagePath = $r->path;
        // $r->base64 = $r->image_Icon[$index];
        $r->categoryId = "Custom";
        $r->hotspotGroup = "Custom";
        $column_names = array("name", "path", "uid", "base64", "categoryId", "hotspotGroup", "eid");
        $result = $db->insertIntoTable($r, $column_names, $tabble_name);
        if ($index == 0)
            $ids = $result;
        else
            $ids = $ids . "," . $result;

        try {
            $pathLocation = "images-360/thumbnail/" . $praImageName . "_converted.png";

            $r->path = compressImageUsingTinyPng($r->path, $pathLocation);
            $type = pathinfo($r->path, PATHINFO_EXTENSION);
            $data = file_get_contents($r->path);
            $r->base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            $result = $db->updateTableValue("update booth_editor_icons set path='" . $r->path . "', base64='" . $r->base64 . "' where id=" . $result);
            try {
                $unConvertedImagePath = str_replace(GetHostUrl() . "api/v1/", "", $unConvertedImagePath);
                unlink($unConvertedImagePath);
            } catch (Exception $ex) {
                $response["ErrorWhenDelete"] = "Failed to delete file " . $unConvertedImagePath . " " . $ex->getMessage();
            }
        } catch (Exception $ex) {
            $response["ErrorCompress"] = "Failed to Compress Image " . $r->path . " " . $ex->getMessage();
        }
    }

    if ($result != null) {
        $response["status"] = "success";
        $response['Id'] = $result;
        $id = "";
        $id .= strval($result);
        $response['savedImage'] = $db->getAllRecord("select * from booth_editor_icons where id in ($ids)");
        $response["message"] = "Upload successfully";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to insert hotspot Image. Please try again.";
        echoResponse(201, $response);
    }
});

$app->post('/AddOrUpdateImageIconUpload', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $r->uid = $_SESSION['uid'];
    $ids = "";
    $tabble_name = "booth_editor_icons";
    for ($index = 0; $index < count($r->image_Icon); $index++) {
        $folderUrl = "api/v1/images-360/thumbnail/";
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = $index . "-hotspot-image-" . $r->uid . "_" . $date;
        $r->path = uploadImage($praImageName . "-sm", $folderUrl, "images-360/thumbnail/", $r->image_Icon[$index]);
        $r->base64 = $r->image_Icon[$index];
        $r->categoryId = "Custom";
        $r->hotspotGroup = "Custom";
        if (!isset($r->eid)) {
            $r->eid = 0;
        }
        $column_names = array("name", "path", "uid", "base64", "categoryId", "hotspotGroup", "eid");
        $result = $db->insertIntoTable($r, $column_names, $tabble_name);
        if ($index == 0)
            $ids = $result;
        else
            $ids = $ids . "," . $result;
    }

    if ($result != null) {
        $response["status"] = "success";
        $response['Id'] = $result;
        $id = "";
        $id .= strval($result);
        $response['savedImage'] = $db->getAllRecord("select * from booth_editor_icons where id in ($ids)");
        $response["message"] = "Upload successfully";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to insert hotspot Image. Please try again.";
        echoResponse(201, $response);
    }
});

$app->post('/DeleteHotspotIconImages', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $r->uid = $_SESSION['uid'];
    if (isset($r->iconId) && $r->iconId != 0) {
        $db->deleteRecord("delete from booth_editor_icons where id=" . $r->iconId);
    }
    if ($r->path != "") {
        $arr = explode('/api/v1/', $r->path, 2);
        if (count($arr) == 2) {
            try {
                unlink($arr[1]);
            } catch (Exception $ex) {
                $response["Error"] = "Failed to delete file " . $ex->getMessage();
            }
        }
    }
    // if ($r->image_icon_lg != "") {
    //     $arr = explode('/api/v1/', $r->image_icon_lg, 2);
    //     if (count($arr) == 2) {
    //         try {
    //             unlink($arr[1]);
    //         } catch (Exception $ex) {
    //             $response["Error"] = "Failed to delete file " . $ex->getMessage();
    //         }
    //     }
    // }

    $response["status"] = "success";
    $response["message"] = "Delete successfully.";
    echoResponse(201, $response);
});

$app->post('/Delete360Image', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $r->uid = $_SESSION['uid'];
    if ($r->id != 0) {
        $result = $db->deleteRecord("delete from image360upload where id=" . $r->id);
        $response["delete"] = $result;
    }
    if ($r->path != "") {
        $arr = explode('/api/v1/', $r->path, 2);
        if (count($arr) == 2) {
            try {
                unlink($arr[1]);
            } catch (Exception $ex) {
                $response["Error"] = "Failed to delete file " . $ex->getMessage();
            }
        }
    }
    if ($r->image_360_lg != "") {
        $arr = explode('/api/v1/', $r->image_360_lg, 2);
        if (count($arr) == 2) {
            try {
                unlink($arr[1]);
            } catch (Exception $ex) {
                $response["Error"] = "Failed to delete file " . $ex->getMessage();
            }
        }
    }

    $response["status"] = "success";
    $response["message"] = "Delete successfully.";
    echoResponse(200, $response);
});
$app->post('/CompressAndUploadImage360', function () use ($app) {
    $response = array();
    ini_set('memory_limit', '-1');
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $r->uid = $_SESSION['uid'];
    if (isset($r->id) && $r->id != "") {

        $fileName = strval($r->uid);
        $fileName .= "-";
        $fileName .= strval($r->id);

        $folderUrl = "api/v1/images-360/original/";
        $r->image_360_lg = uploadImage($fileName, $folderUrl, "images-360/original/", $r->image_360_lg);
        $db->dbRowUpdate("image360upload", $r, "id=$r->id");
        try {
            $pathLocation = "images-360/original/" . $fileName . ".jpg";
            // //Convert
            $r->image_360_lg = compressImageUsingTinyPng($r->image_360_lg, $pathLocation);
            $db->dbRowUpdate("image360upload", $r, "id=$r->id");
            try {
                unlink("images-360/original/" . $fileName . ".jpeg");
            } catch (Exception $ex) {
                $response["Error"] = "Failed to delete file " . $ex->getMessage();
            }

            $response['compressedImage'] = $r->image_360_lg;
            $response["status"] = "success";
            $response["message"] = "Image Tiled and Optimized successfully.";
        } catch (Exception $ex) {
            $response["message"] = "Error occured: " . $ex->getMessage();
        }
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to create Tile and Optimize. Please try again.";
        echoResponse(201, $response);
    }
});

$app->post('/CompressAndUploadImageIcon', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $r->uid = $_SESSION['uid'];
    if (isset($r->id) && $r->id != "") {

        $fileName = strval($r->uid);
        $fileName .= "hotspot-image-";
        $fileName .= strval($r->id);

        $folderUrl = "api/v1/images-360/original/";
        $r->image_Icon_lg = uploadImage($fileName, $folderUrl, "images-360/original/", $r->image_Icon_lg);
        $db->dbRowUpdate("hotspot_images", $r, "id=$r->id");
        try {
            $pathLocation = "images-360/original/" . $fileName . ".png";
            // //Convert
            $r->image_Icon_lg = compressImageUsingTinyPng($r->image_Icon_lg, $pathLocation);
            $db->dbRowUpdate("hotspot_images", $r, "id=$r->id");
            try {
                unlink("images-360/original/" . $fileName . ".jpeg");
            } catch (Exception $ex) {
                $response["Error"] = "Failed to delete file " . $ex->getMessage();
            }

            $response['compressedImage'] = $r->image_Icon_lg;
            $response["status"] = "success";
            $response["message"] = "Image Tiled and Optimized successfully.";
        } catch (Exception $ex) {
            $response["message"] = "Error occured: " . $ex->getMessage();
        }
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to create Tile and Optimize. Please try again.";
        echoResponse(201, $response);
    }
});

$app->get('/GetImage360Upload', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();

    $sql = "SELECT *
    FROM image360upload  where Id=$Id and 1=1 ";

    $record = $db->getOneRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetImage360Upload', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $organizerId = $_SESSION['uid'];
    $sql = "SELECT *
    FROM image360upload  where uid=$organizerId";

    $record = $db->getAllRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});
$app->get('/Get360Templates', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $sql = "SELECT * FROM image360upload  where name in ('Template1','Template2','Template3','Template4','Template5','Template6')";
    $record = $db->getAllRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/DeleteImage360Upload', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if ($r->id != 0) {
        $db->deleteRecord("delete from image360upload where id=" . $r->id);
    }
    $response["status"] = "success";
    $response["message"] = "Delete successfully.";
    echoResponse(201, $response);
});

$app->get('/Image360UploadGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;
    if (!isset($_SESSION)) {
        session_start();
    }
    $columns = array(
        // datatable column index  => database column name
        0 => 'name',
        1 => 'createdDate'
    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM image360upload";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT * ";
    if ($_SESSION['userType'] == "Organizer")
        $sql .= " FROM image360upload l WHERE l.uid=" . $_SESSION['uid'] . " ";
    else
        $sql .= " FROM image360upload l WHERE  l.uid=$Id";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( l.name LIKE '%" . $requestData['search']['value'] . "%' )";
    }

    $sql .= " order by l.createdDate desc";
    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});

$app->get('/BoothEditorIconsGrid', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;
    if (!isset($_SESSION)) {
        session_start();
    }
    $Id =  $_SESSION['uid'];

    $columns = array(
        // datatable column index  => database column name
        0 => 'name',
        1 => 'id',
        2 => 'isCommon',
        3 => 'hotspotGroup',
        4 => 'categoryId'
    );

    $sql = "SELECT count(*) as Count ";

    // getting total number records without any search
    if ($_SESSION['userType'] == 'Admin') {
        $sql .= " FROM booth_editor_icons";
    } else {
        $sql .= " FROM booth_editor_icons where uid=" . $Id;
    }

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT `id`, `uid`, `eid`, `path`, '' as base64, `name`, `categoryId`, `hotspotGroup`, `isCommon` ";
    if ($_SESSION['userType'] == "Organizer")
        $sql .= " FROM booth_editor_icons l WHERE l.uid=" . $Id . " ";
    else if ($_SESSION['userType'] == "Admin")
        $sql .= " FROM booth_editor_icons l WHERE  l.uid<>" . $Id . " ";
    else
        $sql .= " FROM booth_editor_icons l WHERE  l.uid=" . $Id . " ";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( l.name LIKE '%" . $requestData['search']['value'] . "%' )";
        $sql .= " AND ( l.hotspotGroup LIKE '%" . $requestData['search']['value'] . "%' )";
        $sql .= " AND ( l.categoryId LIKE '%" . $requestData['search']['value'] . "%' )";
    }
    $sql .= " order by l.id desc ";
    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});

$app->get('/getBoothEditorIcon', function () use ($app) {
    $response = array();
    $id =  $app->request()->get('id');
    $db = new DbHandler();
    $sql = "SELECT * FROM booth_editor_icons  where id=$id";
    $record = $db->getOneRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/UpdateIconDetails', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();

    if (isset($r->id) && $r->id > 0) {
        $icon = $db->getOneRecord("select * from booth_editor_icons where id=" . $r->id);
        if ($icon != null) {
            if ($icon['path'] != "" && isset($r->image_Icon_lg_List) && count($r->image_Icon_lg_List) > 0) {
                $arr = explode('/api/v1/', $r->path, 2);
                if (count($arr) == 2) {
                    try {
                        unlink($arr[1]);
                        $fileName = explode('images-360/thumbnail/', $arr[1], 2);
                        $fileNameOnly = explode('.', end($fileName), 2);
                        $response["pathd"] = $fileNameOnly;

                        $path = uploadIconImage($r->image_Icon_lg_List[0], current($fileNameOnly));
                        $response["path"] = $path;
                        $response["uploadFromCatch"] = $path;
                        $response["newPath"] = $path;
                        $response["oldPath"] = $icon['path'];
                        updateBoothEditorIconPath($path, $icon['path'],$r->id);
                    } catch (Exception $ex) {
                        $response["Error"] = "Failed to delete file " . $ex->getMessage();
                        $fileName = explode('images-360/thumbnail/', $arr[1], 2);
                        $fileNameOnly = explode('.', end($fileName), 2);
                        $response["pathd"] = $fileNameOnly;

                        $path = uploadIconImage($r->image_Icon_lg_List[0], current($fileNameOnly));
                        $response["path"] = $path;
                        $response["newPath"] = $path;
                        $response["oldPath"] = $icon['path'];
                        $response["uploadFromCatch"] = $path;
                        updateBoothEditorIconPath($path, $icon['path'],$r->id);
                    }
                }
                $db->updateTableValue("update booth_editor_icons set base64='" . $r->base64 . "' where id=" . $r->id);
            }
        }
    }

    $sql = "update booth_editor_icons set  name='" . $r->name . "', uid=".$r->uid.",  categoryId='" . $r->categoryId . "' , hotspotGroup = '" . $r->hotspotGroup . "', isCommon=" . ($r->isCommon ? 1 : 0) . " where id=" . $r->id;
    $db->updateTableValue($sql);

    $response["status"] = "success";
    $response["message"] = "Icon update successfully.";
    echoResponse(201, $response);
});

$app->post('/Update360ImageOrder', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    Update360ImageOrder($r->imageList);
    $response["status"] = "success";
    $response["message"] = "Update successfully.";
    echoResponse(200, $response);
});

function Update360ImageOrder($list){
    $db = new DbHandler();
    foreach ($list as $index => $img) {
        $db->updateTableValue("update image360upload set sequence=$index where id=" . $img->id);
    }
}

function updateBoothEditorIconPath($newPath, $oldPath,$id)
{
    $db = new DbHandler();
    try {
        if($newPath != $oldPath){
            $sql = "update booth_editor_icons set  path='" . $newPath . "'  where id=" . $id;
            $db->updateTableValue($sql);
            $sql = "UPDATE booth_editor  SET toolList = REPLACE (toolList, '$oldPath', '$newPath')";
            $db->updateTableValue($sql);
                 
        }
    } catch (Exception $ex) {
    }
}

function uploadIconImage($imgData, $fileName)
{   ///api/v1/images-360/thumbnail
    $folderUrl = "api/v1/images-360/thumbnail/";
    return uploadImage($fileName, $folderUrl, "images-360/thumbnail/", $imgData);
}