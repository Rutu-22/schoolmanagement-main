<?php
$app->get('/session', function () use ($app) {
    $db = new DbHandler();
    $session = $db->getSession();
    $response["uid"] = $session['uid'];
    $response["email"] = $session['email'];
    $response["mobile"] = $session['mobile'];
    $response["name"] = $session['name'];
    $response['userType'] = $session['userType'];
    $response['companyName'] = $session['companyName'];
    $response['tableName'] = $session['tableName'];
    $response['countOfproduct'] = $session['countOfproduct'];
    $response['packageName'] = $session['packageName'];
    $response['orderId'] = $session['orderId'];
    $response['link'] = $session['link'];
    $response['interestArea'] = $session['interestArea'];
    $response['organizer'] = $session['organizer'];
    if (!isset($_SESSION)) {
        session_start();
    }
    if (isset($_SESSION['authorizedExhibitors'])) {
        $session['authorizedExhibitors'] = $_SESSION['authorizedExhibitors'];
    } else {
        $session['authorizedExhibitors'] = [];
    }
    $link =  $app->request()->get('boothLink');

    $_SESSION['SearchEmail'] = "";
    if ($session['uid'] != "" && $session['userType'] == "Visitor" && isset($link)  && $link != "") {
        $session["onlineUsers"] = $db->getAllRecord("select u.uid,u.name, v.created_at from boothvisitshistory v inner join users u on u.uid = v.user_id where v.end_at is null and v.booth_url='$link' group by v.user_id");
    }
    echoResponse(200, $session);
});

$app->post('/setSearchSession', function () use ($app) {
    if (!isset($_SESSION)) {
        session_start();
    }

    $r = json_decode($app->request->getBody());
    $_SESSION['SearchEmail'] = $r->searchEmail;
    $response = array();
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->post('/setDashboardSessionForOrganizerOperator', function () use ($app) {
    if (!isset($_SESSION)) {
        session_start();
    }

    $r = json_decode($app->request->getBody());
    $_SESSION['uid'] = $r->uid;
    // $_SESSION['userType'] = "Manager";
    $response = array();
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->post('/setTableNameForAdminInSession', function () use ($app) {
    if (!isset($_SESSION)) {
        session_start();
    }
    $r = json_decode($app->request->getBody());
    $_SESSION['tableName'] = $r->tableName;
    $response = array();
    $response['success'] = "success";
    echoResponse(200, $response);
});



$app->post('/login', function () use ($app) {
    $db = new DbHandler();
    require_once 'passwordHash.php';
    $response = array();
    $r = json_decode($app->request->getBody());
    if (isset($r->customer->uid)) {
        //// Below code run when the auto login available
        $sql = "select * from users where uid=" . $r->customer->uid;
        $user = $db->getOneRecord($sql);
        if ($user) {
            $organizer = $user['organizer'];
            $response['status'] = "success";
            $response['message'] = 'Logged in successfully.';
            $response['name'] = $user['name'];
            $response['uid'] = $user['uid'];
            $response['email'] = $user['email'];
            $response['userType'] = $user['userType'];
            if ($response['userType'] == "Manager") {
                $sql =   "SELECT * from pacakgefeatures pf 
                inner join pacakgefeaturemap pfm on pf.id =pfm.featureId 
                inner join exhibitorpackagemap epm on epm.packageId = pfm.packageId and epm.exhibitorId = " . $user['uid'];
                $response['packageFeatures'] = $db->getAllRecord($sql);
            }
            $response['companyName'] = $user['companyName'];
            $response['createdAt'] = $user['created'];
            $response['ImagePath'] = $user['ImagePath'];
            $response['link'] = $user['link'];
            $response['interestArea'] = $user['interestArea'];
            $response['sms_status'] = $user['sms_status'];
            $response['email_status'] = $user['email_status'];
            $response['mobile'] = $user['mobile'];
            $response['mobile1'] = $user['mobile1'];
            $response['mobile2'] = $user['mobile2'];
            $response['mobile3'] = $user['mobile3'];
            $response['conMobile'] = $user['conMobile'];
            $response['secondary_mobile'] = $user['secondary_mobile'];
            $response['organizer'] = $organizer;
            $response['email1'] = $user['email1'];
            $response['email2'] = $user['email2'];
            $response['email3'] = $user['email3'];


            $uid = $user['uid'];
            $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
            $db->activity($uid, "Logged In", $date->format('Y-m-d'));


            if (!isset($_SESSION)) {
                session_start();
            }

            $_SESSION['uid'] = $user['uid'];
            $_SESSION['organizer'] = $organizer;
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['mobile'] = $user['mobile'];
            $_SESSION['userType'] = $user['userType'];
            $_SESSION['companyName'] = $user['companyName'];
            $_SESSION['orderId'] = $user['orderId'];
            $_SESSION['link'] = $user['link'];
            //set count of Product from the package
            $sql = "SELECT * from pacakgefeatures pf 
            inner join pacakgefeaturemap pfm on pf.id =pfm.featureId and pf.code='Add_Product_Quantity'
            inner join exhibitorpackagemap epm on epm.packageId = pfm.packageId and epm.exhibitorId = " . $user['uid'];
            $pacakgeProducts = $db->getOneRecord($sql);
            if ($pacakgeProducts != null && $pacakgeProducts['isApplied'] == '1') {
                if ($pacakgeProducts['acceptValue'] == '1') {
                    $value = $pacakgeProducts['value'];
                    $_SESSION['countOfproduct'] = intval($value);
                }
            } else {
                $_SESSION['countOfproduct'] = 10; //unlimited
            }
            $_SESSION['packageName'] = $user['packageName'];
            $_SESSION['interestArea'] = $user['interestArea'];
            $_SESSION['sms_status'] = $user['sms_status'];
            $_SESSION['email_status'] = $user['email_status'];
            $_SESSION['tableName'] = passwordHash::hash(date('m-d-Y-h-i-s-m', time()));

            $response['tableName'] = $_SESSION['tableName'];
        } else {
            $response['status'] = "error";
            $response['message'] = 'Login failed. Incorrect credentials';
        }
    } else {
        verifyRequiredParams(array('email', 'password'), $r->customer);
        $password = $r->customer->password;
        $email = $r->customer->email;
        if (isset($r->customer->mobile))
            $mobile = $r->customer->mobile;
        else
            $mobile = '';

        $organizer = $r->customer->organizer;

        $sql = "select * from users where (mobile='$mobile' or email='$email') AND organizer='$organizer' and canLogin=1";
        $allOrg = $db->getOneRecord($sql);
        if ((isset($r->customer->userType) && $r->customer->userType == "Visitor") || $allOrg['userType'] == 'Visitor') {
            $sql = "select *, ''as tableName from users where (mobile='$email' or email='$email') AND organizer='$organizer' and usertype='Visitor' ";
        } else if (($allOrg['organizer'] == 'All')) {
            $sql = "select *, ''as tableName from users where (mobile='$email' or email='$email') AND organizer='$organizer'";
        } else {
            $sql = "select u.email1,u.email2,u.email3,u.mobile,u.mobile1,u.mobile2,u.mobile3,u.secondary_mobile,u.conMobile,u.organizer,u.uid,u.name,'' as tableName,u.password,u.email,u.interestArea,u.created,u.link, u.companyName,u.userType,u.ImagePath,u.countOfproduct,u.packageName,u.orderId,u.email_status,u.sms_status 
    from users u  where  uid in (select uid from usermap where exhibitionId in(SELECT id FROM `exhibition`  where isDelete<>1 and link ='$organizer')
    and email='$email' OR mobile='$email') and u.canLogin=1";
        }
        $user = $db->getOneRecord($sql);
        if ($user != NULL) {
            $response['password'] = $user['password'];
            if (passwordHash::check_password($user['password'], $password) || ($mobile == $password)) {
                $response['status'] = "success";
                $response['message'] = 'Logged in successfully.';
                $response['name'] = $user['name'];
                $response['uid'] = $user['uid'];
                $response['email'] = $user['email'];
                $response['userType'] = $user['userType'];
                if ($response['userType'] == "Manager") {
                    $sql =   "SELECT * from pacakgefeatures pf 
                inner join pacakgefeaturemap pfm on pf.id =pfm.featureId 
                inner join exhibitorpackagemap epm on epm.packageId = pfm.packageId and epm.exhibitorId = " . $user['uid'];
                    $response['packageFeatures'] = $db->getAllRecord($sql);
                }
                $response['companyName'] = $user['companyName'];
                $response['createdAt'] = $user['created'];
                $response['ImagePath'] = $user['ImagePath'];
                $response['link'] = $user['link'];
                $response['interestArea'] = $user['interestArea'];
                $response['sms_status'] = $user['sms_status'];
                $response['email_status'] = $user['email_status'];
                $response['mobile'] = $user['mobile'];
                $response['mobile1'] = $user['mobile1'];
                $response['mobile2'] = $user['mobile2'];
                $response['mobile3'] = $user['mobile3'];
                $response['conMobile'] = $user['conMobile'];
                $response['secondary_mobile'] = $user['secondary_mobile'];
                $response['organizer'] = $organizer;
                $response['email1'] = $user['email1'];
                $response['email2'] = $user['email2'];
                $response['email3'] = $user['email3'];


                $uid = $user['uid'];
                $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                $db->activity($uid, "Logged In", $date->format('Y-m-d'));


                if (!isset($_SESSION)) {
                    session_start();
                }

                $_SESSION['uid'] = $user['uid'];
                $_SESSION['organizer'] = $organizer;
                $_SESSION['email'] = $user['email'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['mobile'] = $user['mobile'];
                $_SESSION['userType'] = $user['userType'];
                $_SESSION['companyName'] = $user['companyName'];
                $_SESSION['orderId'] = $user['orderId'];
                $_SESSION['link'] = $user['link'];
                //set count of Product from the package
                $sql = "SELECT * from pacakgefeatures pf 
            inner join pacakgefeaturemap pfm on pf.id =pfm.featureId and pf.code='Add_Product_Quantity'
            inner join exhibitorpackagemap epm on epm.packageId = pfm.packageId and epm.exhibitorId = " . $user['uid'];
                $pacakgeProducts = $db->getOneRecord($sql);
                if ($pacakgeProducts != null && $pacakgeProducts['isApplied'] == '1') {
                    if ($pacakgeProducts['acceptValue'] == '1') {
                        $value = $pacakgeProducts['value'];
                        $_SESSION['countOfproduct'] = intval($value);
                    }
                } else {
                    $_SESSION['countOfproduct'] = 10; //unlimited
                }
                $_SESSION['packageName'] = $user['packageName'];
                $_SESSION['interestArea'] = $user['interestArea'];
                $_SESSION['sms_status'] = $user['sms_status'];
                $_SESSION['email_status'] = $user['email_status'];
                $_SESSION['tableName'] = passwordHash::hash(date('m-d-Y-h-i-s-m', time()));

                $response['tableName'] = $_SESSION['tableName'];
            } else {
                $response['status'] = "error";
                $response['message'] = 'Login failed. Incorrect credentials';
            }
        } else {
            $user = $db->getOneRecord("select * from exhibitoruser where email='$email' and uid in (select uid from `users` WHERE userType ='Manager' and organizer='$organizer')");
            if ($user != NULL) {
                $response['password'] = $user['password'];
                if (passwordHash::check_password($user['password'], $password) || ($mobile == $password)) {
                    $response['status'] = "success";
                    $response['message'] = 'Logged in successfully.';
                    $response['name'] = $user['name'];
                    $response['uid'] = $user['uid'];
                    $response['id'] = $user['id'];
                    $response['email'] = $user['email'];
                    $response['userType'] = "ExhibitorUser";
                    $response['companyName'] = '';
                    $response['createdAt'] = '';
                    $response['ImagePath'] = '';
                    $response['link'] = '';
                    $response['interestArea'] = '';
                    $response['sms_status'] = '';
                    $response['email_status'] = '';
                    $response['mobile'] = $user['mobile'];
                    $response['mobile1'] = '';
                    $response['mobile2'] = '';
                    $response['mobile3'] = '';
                    $response['conMobile'] = '';
                    $response['secondary_mobile'] = '';
                    $response['organizer'] = $organizer;
                    $response['email1'] = '';
                    $response['email2'] = '';
                    $response['email3'] = '';


                    $uid = $user['uid'];
                    $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                    $db->activity($uid, "Logged In", $date->format('Y-m-d'));


                    if (!isset($_SESSION)) {
                        session_start();
                    }
                    if ($response['userType'] == "ExhibitorUser") {
                        $sql =   "SELECT * from pacakgefeatures pf 
                    inner join pacakgefeaturemap pfm on pf.id =pfm.featureId 
                    inner join exhibitorpackagemap epm on epm.packageId = pfm.packageId and epm.exhibitorId = " . $user['uid'];
                        $response['packageFeatures'] = $db->getAllRecord($sql);
                    }
                    $sql = "SELECT * from pacakgefeatures pf 
                inner join pacakgefeaturemap pfm on pf.id =pfm.featureId and pf.code='Add_Product_Quantity'
                inner join exhibitorpackagemap epm on epm.packageId = pfm.packageId and epm.exhibitorId = " . $user['uid'];
                    $pacakgeProducts = $db->getOneRecord($sql);
                    if ($pacakgeProducts != null && $pacakgeProducts['isApplied'] == '1') {
                        if ($pacakgeProducts['acceptValue'] == '1') {
                            $value = $pacakgeProducts['value'];
                            $_SESSION['countOfproduct'] = intval($value);
                        }
                    } else {
                        $_SESSION['countOfproduct'] = 1000; //unlimited
                    }
                    $_SESSION['uid'] = $user['uid'];
                    $_SESSION['organizer'] = $organizer;
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['mobile'] = $user['mobile'];
                    $_SESSION['userType'] = "ExhibitorUser";
                    $_SESSION['companyName'] = '';
                    $_SESSION['orderId'] = $user['id'];
                    $_SESSION['link'] = '';
                    //set count of Product from the package

                    $_SESSION['packageName'] = '';
                    $_SESSION['interestArea'] = '';
                    $_SESSION['sms_status'] = '';
                    $_SESSION['email_status'] = '';
                    $token = passwordHash::hash(date('m-d-Y-h-i-s-m', time()));
                    $_SESSION['tableName'] = $token;
                    $response['tableName'] = $token;
                }
            } else if ($db->getOneRecord("select * from organizer_operator where email='$email'") != NULL) {
                $user = $db->getOneRecord("select * from organizer_operator where email='$email'");
                $response['password'] = $user['password'];
                if (passwordHash::check_password($user['password'], $password) || ($mobile == $password)) {
                    $response['status'] = "success";
                    $response['message'] = 'Logged in successfully.';
                    $response['name'] = $user['name'];
                    $response['uid'] = $user['exhibitorList'];
                    $response['id'] = $user['id'];
                    $response['email'] = $user['email'];
                    $response['userType'] = "OrganizerOperator";
                    $response['companyName'] = '';
                    $response['createdAt'] = '';
                    $response['ImagePath'] = '';
                    $response['link'] = '';
                    $response['interestArea'] = '';
                    $response['sms_status'] = '';
                    $response['email_status'] = '';
                    $response['mobile'] = $user['mobile'];
                    $response['mobile1'] = '';
                    $response['mobile2'] = '';
                    $response['mobile3'] = '';
                    $response['conMobile'] = '';
                    $response['secondary_mobile'] = '';
                    $response['organizer'] = $organizer;
                    $response['email1'] = '';
                    $response['email2'] = '';
                    $response['email3'] = '';
                    $uid = $user['exhibitorList'];
                    $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                    // $db->activity($uid, "Logged In", $date->format('Y-m-d'));
                    if (!isset($_SESSION)) {
                        session_start();
                    }
                    $_SESSION['countOfproduct'] = 1000; //unlimited
                    // }
                    $_SESSION['uid'] = $user['exhibitorList'];
                    $_SESSION['organizer'] = $organizer;
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['mobile'] = $user['mobile'];
                    $_SESSION['userType'] = "OrganizerOperator";
                    $_SESSION['companyName'] = '';
                    $_SESSION['orderId'] = $user['id'];
                    $_SESSION['link'] = '';
                    //set count of Product from the package

                    $_SESSION['packageName'] = '';
                    $_SESSION['interestArea'] = '';
                    $_SESSION['sms_status'] = '';
                    $_SESSION['email_status'] = '';
                    $token = passwordHash::hash(date('m-d-Y-h-i-s-m', time()));
                    $_SESSION['tableName'] = $token;
                    $response['tableName'] = $token;
                    if ($user['exhibitorList'] != '' && $user['exhibitorList'] != null) {
                        $response['authorizedExhibitors'] = $db->getAllRecord("select * from users where uid in (" . $user['exhibitorList'] . ")");
                        $_SESSION['authorizedExhibitors'] = $response['authorizedExhibitors'];
                    } else {
                        $_SESSION['authorizedExhibitors'] = '';
                        $response['authorizedExhibitors'] = [];
                    }
                }
            } else {
                $response['status'] = "error";
                $response['message'] = 'User is either locked or not registered with us';
            }
        }
    }
    echoResponse(200, $response);
});


$app->post('/login_operator', function () use ($app) {
    $db = new DbHandler();
    require_once 'passwordHash.php';
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email', 'password'), $r);
    $response = array();
    $password = $r->password;
    $email = $r->email;
    if ($db->getOneRecord("select * from organizer_operator where email='$email'") != NULL) {
        $user = $db->getOneRecord("select * from organizer_operator where email='$email'");
        $response['password'] = $user['password'];
        if (passwordHash::check_password($user['password'], $password)) {
            $response['status'] = "success";
            $response['message'] = 'Logged in successfully.';
            $response['name'] = $user['name'];
            $response['uid'] = $user['exhibitorList'];
            $response['id'] = $user['id'];
            $response['email'] = $user['email'];
            $response['userType'] = "OrganizerOperator";
            $response['companyName'] = '';
            $response['createdAt'] = '';
            $response['ImagePath'] = '';
            $response['link'] = '';
            $response['interestArea'] = '';
            $response['sms_status'] = '';
            $response['email_status'] = '';
            $response['mobile'] = $user['mobile'];
            $response['mobile1'] = '';
            $response['mobile2'] = '';
            $response['mobile3'] = '';
            $response['conMobile'] = '';
            $response['secondary_mobile'] = '';
            $response['organizer'] = 'Operator';
            $response['email1'] = '';
            $response['email2'] = '';
            $response['email3'] = '';
            $uid = $user['exhibitorList'];
            $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
            //$db->activity($uid, "Logged In", $date->format('Y-m-d'));
            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['countOfproduct'] = 1000; //unlimited
            $_SESSION['uid'] = $user['exhibitorList'];
            $_SESSION['organizer'] = 'Operator';
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['mobile'] = $user['mobile'];
            $_SESSION['userType'] = "OrganizerOperator";
            $_SESSION['companyName'] = '';
            $_SESSION['orderId'] = $user['id'];
            $_SESSION['link'] = '';
            //set count of Product from the package

            $_SESSION['packageName'] = '';
            $_SESSION['interestArea'] = '';
            $_SESSION['sms_status'] = '';
            $_SESSION['email_status'] = '';
            $token = passwordHash::hash(date('m-d-Y-h-i-s-m', time()));
            $_SESSION['tableName'] = $token;
            $response['tableName'] = $token;
            if ($user['exhibitorList'] != '' && $user['exhibitorList'] != null) {
                $exhibitors = $db->getAllRecord("select * from users where uid in (" . $user['exhibitorList'] . ")");
                foreach ($exhibitors as $key => $com) {
                    $discount = getDiscounts($com['uid']);
                    $exhibitors[$key]['discount'] = $discount;
                    $exhibitors[$key]['product_cateogry'] = getProductCategoriesAndSubCategory($com['organizer']);
                }

                $response['authorizedExhibitors'] = $exhibitors;
                $_SESSION['authorizedExhibitors'] = $response['authorizedExhibitors'];
            } else {
                $_SESSION['authorizedExhibitors'] = '';
                $response['authorizedExhibitors'] = [];
            }
        }
    } else {
        $response['status'] = "error";
        $response['message'] = 'User is either locked or not registered with us';
    }

    echoResponse(200, $response);
});

$app->get('/getOnlineUsersOnBooth', function () use ($app) {
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $sessionId =  $app->request()->get('sessionId');
    $response["onlineUser"] =  $_SESSION[$sessionId];
    echoResponse(200, $response);
});


$app->post('/signUp', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email', 'name'), $r->customer);
    require_once 'passwordHash.php';
    $db = new DbHandler();
    $mobile = $r->customer->mobile;
    $name = $r->customer->name;
    $email = $r->customer->email;
    $password = $r->customer->password;
    $organizer = $r->customer->organizer;
    $r->customer->userType = "Manager";

    $isUserExists = $db->getOneRecord("select * from users where (mobile='$mobile' or email='$email') AND (organizer='$organizer' or usertype='Visitor')");
    if (!$isUserExists) {
        $r->customer->password = passwordHash::hash($password);
        if ($organizer = "Dibex2020") {
            $tabble_name = "users";
            $column_names = array('mobile', 'name', 'email', 'password', 'city', 'userType', 'organizer', 'orderId', 'countOfproduct');
            $result = $db->insertIntoTable($r->customer, $column_names, $tabble_name);
        } else {
            $tabble_name = "users";
            $column_names = array('mobile', 'name', 'email', 'password', 'city', 'userType', 'organizer');
            $result = $db->insertIntoTable($r->customer, $column_names, $tabble_name);
        }
        if ($result != NULL) {
            $response["status"] = "success";
            $response["message"] = "User account created successfully";
            $response["uid"] = $result;

            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to create customer. Please try again";
            echoResponse(201, $response);
        }
    } else {
        $response["status"] = "error";
        if ($isUserExists["userType"] == 'Visitor')
            $response["message"] = "An user with the provided mobile or email exists as Visitor!";
        else
            $response["message"] = "An user with the provided mobile or email exists!";
        echoResponse(201, $response);
    }
});



$app->post('/visitorSignUp', function () use ($app) {
    $r = json_decode($app->request->getBody());
    echoResponse(200, registerAsVisitor($r));
});

function registerAsVisitor($r, $autoLogin = true)
{
    $response = array();
    verifyRequiredParams(array('email', 'name', 'mobile'), $r->customer);
    require_once 'passwordHash.php';
    $db = new DbHandler();
    $mobile = $r->customer->mobile;
    $name = $r->customer->name;
    $email = $r->customer->email;
    $password = $r->customer->mobile;
    $organizer = $r->customer->organizer;
    $r->customer->userType = "Visitor";
    $isUserExists = $db->getOneRecord("select mobile,email,uid from users where (mobile='$mobile' or email='$email') and organizer='$organizer' AND userType='Visitor' ");
    if (!$isUserExists) {
        $r->customer->password = passwordHash::hash($password);
        $tabble_name = "users";
        $column_names = array('mobile', 'name', 'email', 'companyName', 'password', 'city', 'address', 'userType', 'interestArea', 'organizer');
        $result = $db->insertIntoTable($r->customer, $column_names, $tabble_name);
        if ($result != NULL) {
            $response["status"] = "success";
            $response["message"] = "User account created successfully";
            /**Create session */

            $user = $db->getOneRecord("select u.uid,u.name,u.password,u.email,u.mobile,u.created,u.interestArea,ac.tableName,u.link, u.companyName,u.userType,u.ImagePath,u.countOfproduct,u.packageName,u.orderId from users u left join appcompanydetails ac on ac.NameValue = u.companyName where uid=$result");
            if ($user != NULL && $autoLogin) {
                $response['status'] = "success";
                $response['message'] = 'Logged in successfully.';
                $response['name'] = $user['name'];
                $response['uid'] = $user['uid'];
                $response['email'] = $user['email'];
                $response['mobile'] = $user['mobile'];

                $response['userType'] = $user['userType'];
                $response['companyName'] = $user['companyName'];
                $response['tableName'] = "Visitor";
                $response['createdAt'] = $user['created'];
                $response['ImagePath'] = $user['ImagePath'];
                $response['link'] = $user['link'];
                $response['interestArea'] = $user['interestArea'];
                $response['organizer'] = $organizer;


                if (!isset($_SESSION)) {
                    session_start();
                }

                $_SESSION['uid'] = $user['uid'];
                $_SESSION['email'] = $email;
                $_SESSION['name'] = $user['name'];
                $_SESSION['userType'] = $user['userType'];
                $_SESSION['companyName'] = $user['companyName'];
                $_SESSION['orderId'] = $user['orderId'];
                $_SESSION['mobile'] = $user['mobile'];

                $_SESSION['link'] = $user['link'];
                $_SESSION['countOfproduct'] = $user['countOfproduct'];
                $_SESSION['packageName'] = $user['packageName'];
                $_SESSION['tableName'] = "Visitor";
                $_SESSION['interestArea'] = $user['interestArea'];
                $_SESSION['organizer'] = $organizer;
            }
            /*End create Session */
            $response["uid"] = $result;
            return  $response;
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to create customer. Please try again";
            return $response;
        }
    } else {
        $response["status"] = "error";
        $response['existingUser'] = $isUserExists;
        $response["message"] = "An user with the provided mobile or email exists!";
        return $response;
    }
}
$app->post('/updateUserConfig', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $isExist = $db->getOneRecord("select 1 from userconfiguration where uid='$r->uid' and configkey='$r->configkey'");
    $configSuccess = $r->configSuccess;
    unset($r->configSuccess);
    if ($isExist) {
        $db->dbRowUpdate("userconfiguration", $r, "uid='$r->uid' and configkey='$r->configkey' ");
    } else {
        $tabble_name = "userconfiguration";
        $column_names = array('uid', 'configkey', 'value', 'meeting_room_id', 'exhibitorUserId');
        $db->insertIntoTable($r, $column_names, $tabble_name);
    }
    $response["status"] = "success";
    $response["message"] = "Configuration " . $configSuccess . " Saved successfully.";
    $response['userConfig'] = $db->getOneRecord("select * from userconfiguration where uid='$r->uid' and configkey='$r->configkey'");
    echoResponse(200, $response);
});

$app->post('/AddOffer', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($r->id)) {
        $isOfferNameExists = $db->getOneRecord("select 1 from offers where addoffers='$r->addoffers' and exhibitorId='$r->exhibitorId'");

        if ($isOfferNameExists) {
            $response["status"] = "error";
            $response["message"] = "An Offer name already exists. Please try another name.";
            echoResponse(201, $response);
        } else {
            $folderUrl = "api/v1/OfferImages/";
            $date = date('m-d-Y-h-i-s-m', time());
            $praImageName = "offer_" . $r->exhibitorId . "_" . $date;
            if ($r->ImageData != "")
                $r->image = uploadImage($praImageName, $folderUrl, "OfferImages/", $r->ImageData);
            else
                $r->image = "";

            $tabble_name = "offers";
            $column_names = array('highlightsofexhibition', 'addoffers', 'image', 'launchedexcon', 'exhibitorId');
            $result = $db->insertIntoTable($r, $column_names, $tabble_name);

            if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "Offer created successfully";
                $response["Id"] = $result;
                echoResponse(200, $response);
            } else {
                $response["status"] = "error";
                $response["message"] = "Failed to create Offer.";
                echoResponse(201, $response);
            }
        }
    } else {
        if ($r->ImageDataUpload) {
            $folderUrl = "api/v1/OfferImages/";
            $date = date('m-d-Y-h-i-s-m', time());
            $praImageName = "offer_" . $r->id . "_" . $date;
            if ($r->ImageData != "")
                $r->image = uploadImage($praImageName, $folderUrl, "OfferImages/", $r->ImageData);
            else
                $r->image = "";
        } else {
            unset($r->image);
        }
        unset($r->ImageData);
        unset($r->ImageDataUpload);
        if ($db->dbRowUpdate("offers", $r, "id='$r->id'")) {
            $response["status"] = "success";
            $response["message"] = "offers updated successfully.";
            echoResponse(200, $response);
        }
    }
});

$app->post('/AddExhibitor', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    require_once 'passwordHash.php';
    $db = new DbHandler();
    $email = $r->email;
    $password = $r->password;
    if (!isset($_SESSION)) {
        session_start();
    }
    if (isset($r->Sponsor) && $r->Sponsor == true) {
        $r->Sponsor = 1;
    } else {
        $r->Sponsor = 0;
    }

    $r->organizerId = $_SESSION["uid"];
    $r->createdById = $_SESSION["uid"];
    $sql = "select 1 from users where email='$email' and uid in (select uid from usermap where createdById =$r->organizerId and exhibitionId =" . $r->exhibitionId . ")";
    $isUserExists = $db->getOneRecord($sql);
    $exhibition = $db->getOneRecord("select * from exhibition where id = $r->exhibitionId");
    if (!$isUserExists && $exhibition != null) {
        $r->password = passwordHash::hash($password);
        $tabble_name = "users";
        $r->organizer = $exhibition["link"]; //set exhibition link for this

        if (isset($r->PaymentStatus) && $r->PaymentStatus == '') {
            $r->PaymentStatus = 0;
        } else if (!isset($r->PaymentStatus)) {
            $r->PaymentStatus = 0;
        }

        $column_names = array('mobile', 'name', 'email', 'password', 'address', 'userType', 'orderId', 'organizer', 'Sponsor', 'PaymentStatus','brandColor1','brandColor2'); //, 'packageName', 'companyName', 'orderId', 'organizer', 'countOfproduct');
        $result = $db->insertIntoTable($r, $column_names, $tabble_name);
        //insert in to user map table
        $tabble_name = "usermap";
        $r->uid = $result;
        $r->exhibitorId = $result;
        $r->boothOrder = 0;
        $r->brandColor1=$result;
        $r->brandColor2=$result;
        $column_names = array("createdById", "uid", "exhibitionId", "boothOrder");
        $result1 = $db->insertIntoTable($r, $column_names, $tabble_name,);
        //Insert into pacakge table where exhibitor allocated package.
        $tabble_name = "exhibitorpackagemap";
        $column_names = array('exhibitorId', 'packageId');
        $result1 = $db->insertIntoTable($r, $column_names, $tabble_name);

        $booth_360view = new stdClass();
        $booth_360view->exhibitor_id = $r->exhibitorId;
        $booth_360view->{'360_view'} = "";
        $booth_360view->video_type = "360Editor";
        $booth_360view->isAutoStart = 0;

        $response = set360ViewSettingForExhibitor($response, $booth_360view);
        if ($result != NULL) {
            $response["status"] = "success";
            $response["message"] = "exhibitor account created successfully";
            $response["uid"] = $result;
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to create exhibitor. Please try again";
            echoResponse(201, $response);
        }
    } else {
        $response["status"] = "error";
        $response["message"] = "An exhibitor with the provided mobile or email exists!";
        echoResponse(201, $response);
    }
});

$app->post('/AddExhibitorUser', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    require_once 'passwordHash.php';
    $db = new DbHandler();
    $email = $r->email;
    $password = $r->password;
    if (!isset($_SESSION)) {
        session_start();
    }
    $r->uid = $_SESSION["uid"];
    $sql = "select 1 from exhibitoruser where email='$email' and uid =" . $r->uid;
    $sql = $sql . " UNION select 1 from organizer_operator where email='$email'";

    $isUserExists = $db->getOneRecord($sql);
    if (!$isUserExists != null) {
        $r->password = passwordHash::hash($password);
        $tabble_name = "exhibitoruser";
        $column_names = array('mobile', 'name', 'email', 'password', 'designation', 'uid');
        $result = $db->insertIntoTable($r, $column_names, $tabble_name);
        if ($result != NULL) {
            $response["status"] = "success";
            $response["message"] = "User account created successfully";
            $response["id"] = $result;
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to create user. Please try again";
            echoResponse(201, $response);
        }
    } else {
        $response["status"] = "error";
        $response["message"] = "An user with the provided mobile or email exists!";
        echoResponse(201, $response);
    }
});

$app->post('/SendSMS', function () use ($app) {
    $response = array();
    $request = json_decode($app->request->getBody());
    if (isset($request->exhibitorId)) {
        $response["smsSatus"] = sendSMS($request->text, $request->mobile, $request->exhibitorId, 0);
    } else if (isset($request->exhibitionId)) {
        $response["smsSatus"] = sendSMS($request->text, $request->mobile, 0, $request->exhibitionId);
    }
    $response["status"] = "Success";
    $response["message"] = "SMS Sent!";
    echoResponse(200, $response);
});

$app->post('/AddOrganizerOperator', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    require_once 'passwordHash.php';
    $db = new DbHandler();

    $email = $r->email;
    $password = $r->password;

    if (!isset($_SESSION)) {
        session_start();
    }
    if ($r->isAdd == true) {
        $r->uid = $_SESSION["uid"];
        $sql = "select 1 from organizer_operator where email='$email' and organizer_uid =" . $r->uid;
        $sql = $sql . " UNION select 1 from exhibitoruser where email='$email'";
        $isUserExists = $db->getOneRecord($sql);

        if (!$isUserExists != null) {
            $r->password = passwordHash::hash($password);
            $tabble_name = "organizer_operator";
            $column_names = array('mobile', 'name', 'email', 'password', 'designation', 'organizer_uid', 'exhibitorList');
            $result = $db->insertIntoTable($r, $column_names, $tabble_name);
            if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "User account created successfully";
                $response["id"] = $result;
                echoResponse(200, $response);
            } else {
                $response["status"] = "error";
                $response["message"] = "Failed to create user. Please try again";
                echoResponse(201, $response);
            }
        } else {
            $response["status"] = "error";
            $response["message"] = "An user with the provided mobile or email exists!";
            echoResponse(201, $response);
        }
    } else {
        unset($r->password);
        unset($r->exhibitionId);
        unset($r->isAdd);
        if ($db->dbRowUpdate("organizer_operator", $r, "id='$r->id'")) {
            $response["status"] = "success";
            $response["message"] = "updated successfully.";
            echoResponse(200, $response);
        }
    }
});

$app->get('/logout', function () {
    $db = new DbHandler();
    $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $session = $db->getSession();
    $db->activity($session['uid'], "Logged Out", $date->format('Y-m-d'));
    $session = $db->destroySession();
    $response["status"] = "info";
    $response["message"] = "Logged out successfully";
    echoResponse(200, $response);
});

$app->post('/changePassword', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('password', 'newPassword'), $r->User);
    require_once 'passwordHash.php';
    $db = new DbHandler();
    $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $session = $db->getSession();
    $db->activity($session['uid'], "Password Changed", $date->format('Y-m-d'));
    $password = $r->User->newPassword;
    $r->User->password = passwordHash::hash($password);
    $newPassword = $r->User->password;
    $userId = $r->User->UserId;
    $query = "update users set password = '$newPassword' where uid='$userId';";
    $result = $db->updateTableValue($query);
    if ($result) {
        $response["status"] = "success";
        $response["message"] = "Password change successfully.";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to change Password. Please try again.";
        echoResponse(201, $response);
    }
});

$app->post('/sendPassword', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();

    $email = $r->User->email;
    $subject = "Reset Password";
    $result = $db->getOneRecord("SELECT * from users where email='$email'");
    $password = $result['password'];
    $uid = $result['uid'];
    $body = "Hi User, \n\n Click on reset password link  :https://www.myVspace.in/#/resetNewPassword?code=" . $password . "&id=" . $uid . "  \n\n Regards,\n Team Hindushegar";

    sendMail($email, $subject, $body);
    if ($result) {
        $response["status"] = "success";
        $response["message"] = "Password link send to your provided mail id.";
        $response["result"] = $result;
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Email Id not match to our system.Please try again.";
        echoResponse(201, $response);
    }
});

$app->post('/GetSMSTemplateByCompany', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $companyName = $r->companyName;

    $db = new DbHandler();
    $record = $db->getOneRecord("select * from template where CompanyName ='$companyName'");
    $response = array();
    $response['record'] = $record;
    $response['status'] = "success";
    $response['message'] = 'record recived successfully.';
    echoResponse(200, $response);
});

$app->post('/GetDataCollectionCount', function () use ($app) {
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    if ($_SESSION["tableName"] == null) {
        $count = array();
        $count["count"] = -1;
        $response = array();
        $response['count'] = $count;
        $response['status'] = "success";
        $response['message'] = 'Count recived successfully.';
        echoResponse(200, $response);
    } else {
        $count = $db->getOneRecord("select count(*) as count from " . $_SESSION["tableName"]);
        $response = array();
        $response['count'] = $count;
        $response['status'] = "success";
        $response['message'] = 'Count recived successfully.';
        echoResponse(200, $response);
    }
});

$app->post('/UpdateInterestCategory', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if ($r->uid != 0) {
        $db->updateTableValue("update users set interestArea = '$r->interestArea' where uid=" . $r->uid);
    }
    $response["status"] = "success";
    $response["message"] = "Update successfully.";
    echoResponse(201, $response);
});

$app->post('/GetCountSummary', function () use ($app) {
    $db = new DbHandler();
    $response = array();
    if (!isset($_SESSION)) {
        session_start();
    }
    $r = json_decode($app->request->getBody());
    $id = 0;
    $userType = '';
    if (isset($r->id)) {
        $id = $r->id;
        $userType = $r->userType;
    } else {
        $id = $_SESSION["uid"];
        $userType = $_SESSION["userType"];
    }

    if ($userType == 'Manager' || $userType == 'Organizer' || $userType == 'ExhibitorUser' || $userType == 'OrganizerOperator') {
        $sql = "SELECT count(1) as LeadCount
     FROM leadsform l 
    left join exhibitionproductdetail p on p.id= l.source_id and l.lead_from != 'Offers'
    left join webinar w on w.id= l.source_id and l.isWebinar = 1
    left join offers o on o.id= l.source_id and l.lead_from = 'Offers'
     WHERE l.exhibitorId=" . $id . " and l.isWebinar<>1 and  l.lead_from in('Product','WhatsApp')";

        $OrderAmount = $db->getOneRecord("SELECT sum(eo.finalAmt) as OrderAmount FROM eco_order o inner join eco_exhibitor_order eo on o.id= eo.orderId and o.payment_status ='Success'  WHERE eo.exhibitorId =" . $id);
        $LeadCount = $db->getOneRecord($sql);
        $WLeadCount = $db->getOneRecord("select count(1) as WLeadCount from leadsform where isWebinar=1 and exhibitorId=" . $id);
        $NewMeetingsCount = $db->getOneRecord("select count(*) as NewMeetingsCount from leadsform where scheduleDate<>'' and (scheduleDate is not null or scheduleTime is not null) and exhibitorId=" . $id . " and IsRead=0 and IsEnquiry=0");

        $MeetingsCount = $db->getOneRecord("select count(1) as MeetingsCount from leadsform where scheduleDate<>'' and (scheduleDate is not null or scheduleTime is not null) and exhibitorId=" . $id . " and IsEnquiry=0");
        $OfferCount = $db->getOneRecord("SELECT count(1) as OfferCount  FROM `leadsform` l 
    left join users u on u.uid = l.userId 
    left join offers ex on l.source_id = ex.id
    WHERE  l.exhibitorId =$id and l.lead_from = 'Offers'");

        $NewOfferCount = $db->getOneRecord("SELECT count(*) as NewOfferCount  FROM `leadsform` l 
        left join users u on u.uid = l.userId 
        left join offers ex on l.source_id = ex.id
        WHERE  l.exhibitorId =$id and l.lead_from = 'Offers' and l.IsRead=0");

        $OfferACount = $db->getOneRecord("select count(1) as OfferACount from leadsform where lead_from='Offers' and exhibitorId=" . $id);
        $VideosCount = $db->getOneRecord("select count(1)as VideosCount from exhibitionproductdetail where exhibitorId=" . $id);
        $LikeCount = $db->getOneRecord("select count(1)as LikeCount from product_likes where exhibitorId=" . $id);
        $NewShareCount = $db->getOneRecord("SELECT COUNT(*) as NewShareCount FROM `is_share_on` WHERE IsRead=0 AND exhibitorId=" . $id);
        $NewLikeCount = $db->getOneRecord("SELECT COUNT(*) as NewLikeCount FROM `product_likes` WHERE IsRead=0 AND exhibitorId=" . $id);
        $NewAnalytics_downloadfiles = $db->getOneRecord("SELECT COUNT(*) as NewAnalytics_downloadfiles FROM `analytics_downloadfiles` WHERE IsRead=0 AND exhibitorId=" . $id);
        $NewOrderCount = $db->getOneRecord("SELECT COUNT(*) as NewOrderCount FROM `eco_exhibitor_order` WHERE IsRead=0 AND exhibitorId=" . $id);
        $NewLeadCount = $db->getOneRecord("SELECT COUNT(*) as NewLeadCount FROM `leadsform` l left join exhibitionproductdetail p on p.id= l.source_id and l.lead_from != 'Offers'
        left join webinar w on w.id= l.source_id and l.isWebinar = 1
        left join offers o on o.id= l.source_id and l.lead_from = 'Offers'
         WHERE l.exhibitorId=" . $id . " and l.isWebinar<>1 and l.lead_from in('Product','WhatsApp') and l.IsRead=0");

        $NewCallDetailsSeenCount = $db->getOneRecord("SELECT COUNT(*) as NewCallDetailsSeenCount FROM `call_details_seen` WHERE IsRead=0 AND uid=" . $id);
        $NewVisitorCount = $db->getOneRecord("SELECT count(*) as NewVisitorCount FROM `visitordetail` v 
        left join users u on u.uid=v.userId 
        where ExhibitorId = " . $id . " and u.email is not null and v.IsRead=0");
        $NewContactDetailViewerCount = $db->getOneRecord("SELECT COUNT(*) as NewContactDetailViewerCount FROM `contact_detail_viewer` WHERE IsRead=0 AND uid=" . $id);

        $CallDetailsSeenCount = $db->getOneRecord("select count(1)as CallDetailsSeenCount from call_details_seen where exhibitorId=" . $id);
        $ProductDetailsSeenCount = $db->getOneRecord("select count(1)as ProductDetailsSeenCount from contact_detail_viewer where exhibitorId=" . $id);
        $shareCount = $db->getOneRecord("select count(1)as shareCount from is_share_on where exhibitorId=" . $id);
        $downloadCount = $db->getOneRecord("select count(1)as downloadCount from analytics_downloadfiles where exhibitorId=" . $id);
        $visitorCount = $db->getOneRecord("SELECT count(1) as visitorCount, sum(visitcount) as visitorsum FROM `visitordetail` v 
       left join users u on u.uid=v.userId 
       where ExhibitorId = " . $id . " and u.email is not null");
        $AllUniqueVisitorCount = $db->getOneRecord("SELECT count(1)  as AllUniqueVistors  FROM `visitordetail` v 
        where ExhibitorId =" . $id);
        $AllNewUniqueVisitorCount = $db->getOneRecord("SELECT count(*)  as AllNewUniqueVisitorCount  FROM `visitordetail` where ExhibitorId =" . $id . " and IsRead=0");
        $exhibitionCount = $db->getOneRecord("SELECT count(1) as exhibitionCount FROM exhibition  where isDelete<>1 and organizerId=" . $id);
        $allowedExhibitionCount = $db->getOneRecord("SELECT allowExhibitorPerExhibition,allowExhibition FROM usersetting  where uid=" . $id);
        $exhibitorCount = $db->getOneRecord("SELECT count(1) as exhibitorCount   FROM users s inner join usermap um on s.uid = um.uid inner join exhibition e on e.id =um.exhibitionId and e.isDelete<>1  where um.createdById=" . $id);
        $packageCount = $db->getOneRecord("SELECT count(1) as packageCount   FROM exhibitorpackage ep  where ep.organizerId=" . $id);
        $WebinarCount = $db->getOneRecord("SELECT count(1) as WebinarCount   FROM webinar ep  where ep.exhibitorId=" . $id);

        $NoAreaOfInterest = $db->getOneRecord("select count(1) as NoAreaOfInterest from exhibition where organizerId=$id and id not in (select categoryId from areaofinterestsubcategory)");
        $response['user'] = $db->getOneRecord("select `audienceId`,`meetingId`, `uid`, `name`, `email`, `mobile`,  `address`, `city`, `created`, `userType`, `companyName`, `ImagePath`, `companyWebsite`, `companyDescription`, `concernedPersonName`, `postalCode`, `country`, `state`, `newTechnologies`, `newProductLaunch`, `interestArea`, `boothNumber`, `orderId`, `packageName`, `countOfproduct`, `skypeId`, `viewCount`, `link` from users where uid=" . $id);

        $response['allowedExhibitionCount'] = $allowedExhibitionCount['allowExhibition'];
        $response['allowedExhibitorCount'] = $allowedExhibitionCount['allowExhibitorPerExhibition'];
        $response['packageCount'] = $packageCount['packageCount'];
        $response['exhibitionCount'] = $exhibitionCount['exhibitionCount'];
        $response['exhibitorCount'] = $exhibitorCount['exhibitorCount'];
        $response['visitorCount'] = $visitorCount['visitorCount'];
        $response['visitorsum'] = $visitorCount['visitorsum'];
        $response['AllUniqueVistors'] = $AllUniqueVisitorCount['AllUniqueVistors'];
        $response['AllNewUniqueVisitorCount'] = $AllNewUniqueVisitorCount['AllNewUniqueVisitorCount'];
        $response['downloadCount'] = $downloadCount["downloadCount"];
        $response['LeadCount'] = $LeadCount["LeadCount"];
        $response['OrderAmount'] = $OrderAmount["OrderAmount"];

        $response['WLeadCount'] = $WLeadCount["WLeadCount"];
        $response['MeetingsCount'] = $MeetingsCount["MeetingsCount"];
        $response['NewMeetingsCount'] = $NewMeetingsCount["NewMeetingsCount"];

        $response['OfferCount'] = $OfferCount["OfferCount"];
        $response['NewOfferCount'] = $NewOfferCount["NewOfferCount"];

        $response['OfferACount'] = $OfferACount["OfferACount"];
        $response['WebinarCount'] = $WebinarCount["WebinarCount"];
        $response['LikeCount'] = $LikeCount["LikeCount"];
        $response['NewLikeCount'] = $NewLikeCount["NewLikeCount"];
        $response['NewVisitorCount'] = $NewVisitorCount["NewVisitorCount"];
        $response['NewLeadCount'] = $NewLeadCount["NewLeadCount"];
        $response['NewOrderCount'] = $NewOrderCount["NewOrderCount"];
        $response['NewShareCount'] = $NewShareCount["NewShareCount"];
        $response['NewAnalytics_downloadfiles'] = $NewAnalytics_downloadfiles["NewAnalytics_downloadfiles"];
        $response['NewCallDetailsSeenCount'] = $NewCallDetailsSeenCount["NewCallDetailsSeenCount"];
        $response['CallDetailsSeenCount'] = $CallDetailsSeenCount["CallDetailsSeenCount"];
        $response['NewContactDetailViewerCount'] = $NewContactDetailViewerCount["NewContactDetailViewerCount"];

        $response['ProductDetailsSeenCount'] = $ProductDetailsSeenCount["ProductDetailsSeenCount"];
        $response['shareCount'] = $shareCount["shareCount"];
        $response['VideosCount'] = $VideosCount["VideosCount"];
        $response['NoAreaOfInterest'] = $NoAreaOfInterest["NoAreaOfInterest"];
        $response['template'] = getTemplateDetailsByExhibitorId($id);

        if ($userType == 'Organizer') {
            $record = $db->getAllRecord("SELECT e.*, count(exhibitionid) as ExhibitiorCount FROM exhibition e left join usermap um on um.exhibitionid=e.id where e.isDelete<>1 and e.organizerId=$id group by e.id");
            $response["exhibitions"] = $record;
        } else if ($userType == 'OrganizerOperator') {
            $record =  getExhibitionsByExhibitorIdForOrganizerOperator($id);
            $response["exhibitions"] = $record;
            if (isset($_SESSION['authorizedExhibitors'])) {
                $response['authorizedExhibitors'] = $_SESSION['authorizedExhibitors'];
            } else {
                $response['authorizedExhibitors'] = [];
            }
        }
    } else if ($userType == "Visitor") {

        $MeetingsCount = $db->getOneRecord("SELECT count(1) as MeetingsCount from leadsform l 
        LEFT JOIN users u on l.exhibitorId = u.uid
        WHERE  l.scheduleDate<>'' and (l.scheduleDate is not null or l.scheduleTime is not null) and  l.userId=$id");

        $OfferCount = $db->getOneRecord("SELECT count(1) as OfferCount from leadsform l
                LEFT JOIN users u ON l.exhibitorId= u.uid
                LEFT JOIN offers o ON u.uid = o.exhibitorId
                where l.userId = $id and l.lead_from ='Offers'");

        $WebinarCount = $db->getOneRecord("SELECT count(1) as WebinarCount from leadsform l
                LEFT JOIN users u ON l.exhibitorId= u.uid
                LEFT JOIN webinar w ON u.uid = w.exhibitorId
                where l.userId = $id and l.isWebinar='1'");
        $BookMarkCount = $db->getOneRecord("SELECT count(1) as BookMarkCount from  briefcase
                where visitorId = $id");

        $VisitedBoothCount = $db->getOneRecord("SELECT count(1) as VisitedBoothCount from visitordetail v
        LEFT JOIN users u on v.exhibitorId = u.uid
         WHERE   v.userId= '$id'");

        $MustVisitCount = $db->getOneRecord("SELECT count(1) as MustVisitCount  from users where userType ='Manager' and organizer = (select organizer from users where uid ='$id')
          and uid not in (select exhibitorId from visitordetail where userid = '$id') ");

        $response['WebinarCount'] = $WebinarCount["WebinarCount"];
        $response['OfferCount'] = $OfferCount["OfferCount"];
        $response['BookMarkCount'] = $BookMarkCount["BookMarkCount"];
        $response['MeetingsCount'] = $MeetingsCount["MeetingsCount"];
        $response['VisitedBoothCount'] = $VisitedBoothCount["VisitedBoothCount"];
        $response['MustVisitCount'] = $MustVisitCount["MustVisitCount"];

        $response['user'] = $db->getOneRecord("select `audienceId`,`meetingId`, `uid`, `name`, `email`, `mobile`,  `address`, `city`, `created`, `userType`, `companyName`, `ImagePath`, `companyWebsite`, `companyDescription`, `concernedPersonName`, `postalCode`, `country`, `state`, `newTechnologies`, `newProductLaunch`, `interestArea`, `boothNumber`, `orderId`, `packageName`, `countOfproduct`, `skypeId`, `viewCount`, `link` from users where uid=" . $id);
    }
    $response['userType'] = $userType;
    $response['status'] = "success";
    $response['message'] = 'Count received successfully.';
    echoResponse(200, $response);
});

function getExhibitionsByExhibitorIdForOrganizerOperator($exhibitorId)
{
    $db = new DbHandler();
    return  $db->getAllRecord("select * from exhibition where organizerId in (select createdById from usermap um  where uid = $exhibitorId)");
}
$app->post('/getSalesmanByTableName', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $r = json_decode($app->request->getBody());

    $tableName = $r->tableName;
    $list = $db->getAllRecord("select SalesmanMail from $tableName group by SalesmanMail");

    $response['list'] = $list;
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->post('/getCompanyRecordDetails', function () use ($app) {
    $db = new DbHandler();
    $r = json_decode($app->request->getBody());
    $tableName = $r->tableName;
    $recordIds = $r->recordIds;
    $sql = "select * from $tableName where Id in  ($recordIds)";

    $list = $db->getAllRecordByProperty($sql);
    $response = array();
    $response['list'] = $list;
    $response['success'] = "success";
    echoResponse(200, $response);
});


$app->get('/getExhibitorUserConfigurations', function () use ($app) {
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $uid =  $app->request()->get('uid');
    $exhibitorUserId =  $app->request()->get('exhibitorUserId');
    $configkey =  $app->request()->get('configkey');
    $sql = "select * from userconfiguration where exhibitorUserId=$exhibitorUserId and uid=" . $uid;
    if ($configkey) {
        $sql .= " and configkey='" . $configkey . "'";
    }
    $metaResult = $db->getAllRecord($sql);
    $response = array();
    $response['data'] = $metaResult;
    $response['success'] = "success";
    echoResponse(200, $response);
});
$app->get('/getOfferCount', function () use ($app) {
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $exhibitorId = $_SESSION["uid"];
    $sql = "select count(1) as count from offers where exhibitorId=" . $exhibitorId;
    $metaResult = $db->getOneRecord($sql);
    $response = array();
    $response['count'] = $metaResult['count'];
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->get('/getExhibitionsWithCount', function () use ($app) {
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $exhibitorId = $_SESSION["uid"];
    $sql = "select count(1) as count from offers where exhibitorId=" . $exhibitorId;
    $result = $db->getOneRecord($sql);
    $response = array();
    $response['data'] = $result;
    $sql1 = "select count(1) as count from offers where exhibitorId=" . $exhibitorId;
    $result1 = $db->getOneRecord($sql1);
    $response['data1'] = $result1;

    $sql = "SELECT e.*, count(exhibitionid) as ExhibitiorCount
    FROM exhibition e left join usermap um on um.exhibitionid=e.id where e.isDelete<>1 and e.organizerId=$exhibitorId group by e.id";

    $record = $db->getAllRecord($sql);
    $response["exhibitions"] = $record;

    $response['success'] = "success";
    echoResponse(200, $response);
});


$app->get('/getCompanyRecordMetaData', function () use ($app) {
    $db = new DbHandler();
    $tableName =  $app->request()->get('tableName');
    $sql = "select * from $tableName";
    $metaResult = $db->getOneRecord($sql);
    $response = array();
    $response['metaResult'] = $metaResult;
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->post('/getCompanyTemplate', function () use ($app) {
    $db = new DbHandler();
    $r = json_decode($app->request->getBody());
    $list = $db->getAllRecordByProperty("select * from template");
    $response = array();
    $response['list'] = $list;
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->post('/getCompanyTemplate', function () use ($app) {
    $db = new DbHandler();
    $r = json_decode($app->request->getBody());
    $list = $db->getAllRecordByProperty("select * from template");
    $response = array();
    $response['list'] = $list;
    $response['success'] = "success";
    echoResponse(200, $response);
});


$app->post('/sendEmail', function () use ($app) {
    $r = json_decode($app->request->getBody());

    sendEmail($r->toEmail, $r->subject, $r->message, $r->from, $r->fromName, $r->sendemail, $r->sendmethod);
});

$app->post('/resetNewPassword', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('newPassword'), $r->User);
    require_once 'passwordHash.php';
    $db = new DbHandler();
    $password = $r->User->newPassword;
    $r->User->password = passwordHash::hash($password);
    $newPassword = $r->User->password;
    $userId = $r->User->UserId;
    $query = "update users set password = '$newPassword' where uid=$userId;";
    $result = $db->updateTableValue($query);
    if ($result) {
        $response["status"] = "success";
        $response["message"] = "Password change successfully.";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to change Password. Please try again.";
        echoResponse(201, $response);
    }
});


$app->post('/resetForgotPassword', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('newPassword'), $r->User);
    require_once 'passwordHash.php';
    $db = new DbHandler();
    $password = $r->User->newPassword;
    $r->User->password = passwordHash::hash($password);
    $newPassword = $r->User->password;
    $Email = $r->User->Email;
    $query = "update users set password = '$newPassword' where email='$Email';";
    $result = $db->updateTableValue($query);
    if ($result) {
        $response["status"] = "success";
        $response["message"] = "Password change successfully.";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to change Password. Please try again.";
        echoResponse(201, $response);
    }
});

$app->post('/SaveIconSetting', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $tabble_name = "booth_editor_icons";
    if (!isset($r->eid)) {
        $r->eid = 0;
    }
    $column_names = array('path', 'base64', 'name', 'categoryId', 'eid');
    $result = $db->insertIntoTable($r, $column_names, $tabble_name);
    $response["status"] = "success";
    $response["message"] = "Insert successfully.";
    echoResponse(201, $response);
});

$app->post('/DeleteOffer', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if ($r->id != 0) {
        $db->deleteRecord("delete from offers where id=" . $r->id);
    }
    $response["status"] = "success";
    $response["message"] = "Delete successfully.";
    echoResponse(201, $response);
});

$app->post('/DeleteProduct', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if ($r->id != 0) {
        $db->deleteRecord("delete from exhibitionproductdetail where id=" . $r->id);
        deleteUnitPricesByProductId($r->id);
    }
    $response["status"] = "success";
    $response["message"] = "Delete successfully.";
    echoResponse(201, $response);
});

$app->post('/DeleteWebinar', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if ($r->id != 0) {
        $db->deleteRecord("delete from webinar where id=" . $r->id);
    }
    $response["status"] = "error";
    $response["message"] = "Delete successfully.";
    echoResponse(201, $response);
});

$app->post('/UpdateWebinarDetails', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $r->exhibitorId = $_SESSION["uid"];
    if ($r->Action == 'Edit') {
        unset($r->Action);
        unset($r->exhibitorId);
        if ($db->dbRowUpdate("webinar", $r, "id='$r->id'")) {
            $response["status"] = "success";
            $response["message"] = "Webinar updated successfully.";
            echoResponse(200, $response);
        }
    } else {
        $tabble_name = "webinar";
        $column_names = array('Title', 'Description', 'Link', 'Password', 'exhibitorId', 'WDate', 'WTime', 'TDate', 'TTime');
        $result = $db->insertIntoTable($r, $column_names, $tabble_name);
        if ($result) {
            $response["status"] = "success";
            $response["message"] = "Webinar created successfully.";
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to create Webinar. Please try again.";
            echoResponse(201, $response);
        }
    }
});
$app->post('/UpdateBoothLink', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $url = $r->Url;
    if (!isset($_SESSION)) {
        session_start();
    }
    if (isset($_SESSION["uid"])) {
        $uid = $_SESSION["uid"];

        $query = "update users set link ='$url' where uid=$uid;";
        $result = $db->updateTableValue($query);
        if ($result) {
            $_SESSION['link'] = $url;
            $response["status"] = "success";
            $response["message"] = "Booth link created successfully.";
            $exhibition = getExhibitionByExhibitorId($uid);
            createShortCutLinkForExhibitor($exhibition['link'], $url);
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to create booth link. Please try again.";
            echoResponse(201, $response);
        }
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to create booth link. Please try again.";
    }
});

function createShortCutLinkForExhibitor($exhibitionName, $boothLink)
{
    try {
        mkdir($_SERVER['DOCUMENT_ROOT'] . "/" . $exhibitionName);
    } catch (Exception $ex) {
    }
    try {
        mkdir($_SERVER['DOCUMENT_ROOT'] . "/" . $exhibitionName . "/" . $boothLink);
    } catch (Exception $ex) {
    } finally {
        $source = $_SERVER['DOCUMENT_ROOT'] . '/realexhibitorTemplate/index.html';
        $destination = $_SERVER['DOCUMENT_ROOT'] . '/' . $exhibitionName . "/" . $boothLink . '/index.html';
        if (!copy($source, $destination)) {
            return false;
        } else {
            return true;
        }
    }
}

$app->post('/DeleteAnyUploadedFile', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    if (!isset($_SESSION)) {
        session_start();
    }
    if (isset($_SESSION["uid"])); {
        $db = new DbHandler();
        $sql = "select $r->c  from $r->tt where $r->wc=" . $r->wcv;

        $result = $db->getOneRecord($sql);
        if ($result != null) {
            $data = $result[$r->c];
            $ImageData = new
                stdClass();
            $ImageData->{$r->c} = '';
            if ($db->dbRowUpdate($r->tt, $ImageData, "$r->wc='$r->wcv'")) {
                $strR = str_replace(GetHostUrl() . "api/v1/", "", $data);
                try {
                    unlink($strR);
                } catch (Exception $ex) {
                    $response["Error"] = "Failed to delete file " . $ex->getMessage();
                }
                $response["status"] = "success";
                $response["message"] = "File Deleted successfully.";
                echoResponse(201, $response);
            } else {
                $response["status"] = "error";
                $response["message"] = "Failed to update.";
                echoResponse(201, $response);
            }
        }
    }
});

$app->post('/DeleteUploadedFile', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    if (!isset($_SESSION)) {
        session_start();
    }
    $uid = $_SESSION["uid"];
    $db = new DbHandler();
    $sql = "select $r->c  from $r->tt where uid=" . $uid;
    $result = $db->getOneRecord($sql);
    if ($result != null) {
        $data = $result[$r->c];
        $ImageData = new stdClass();
        $ImageData->{$r->c} = '';
        if ($db->dbRowUpdate("users", $ImageData, "uid='$uid'")) {
            $strR = str_replace(GetHostUrl() . "api/v1/", "", $data);
            unlink($strR);
            $response["status"] = "success";
            $response["message"] = "Updated successfully.";
            echoResponse(201, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to update.";
            echoResponse(201, $response);
        }
    }
});

$app->post('/Update360BoothSetting', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $uid = $_SESSION["uid"];
    $boothType = new stdClass();
    $boothType->is360View = true;
    if ($db->dbRowUpdate("users", $boothType, "uid='$uid'")) {
        $response["status"] = "success";
        $response["message"] = "Updated successfully.";
        echoResponse(201, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to update.";
        echoResponse(201, $response);
    }
});

$app->post('/UpdateBoothSetting', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());

    $db = new DbHandler();
    unset($r->Image360);
    unset($r->packageId);

    if ($r->uid != 0) {
        if ($db->dbRowUpdate("users", $r, "uid='$r->uid'")) {
            $response["status"] = "success";
            $response["message"] = "Updated successfully.";
            echoResponse(201, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to update.";
            echoResponse(201, $response);
        }
    } else {

        $response["status"] = "error";
        $response["message"] = "something went wrong.";
        echoResponse(201, $response);
    }
});

$app->post('/saveCompanydetails', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();

    $template = new stdClass();

    $template->Message = $r->SMSMessage;
    $template->CompanyName = $r->NameValue;
    $template->SMSSenderId = $r->SMSSenderId;
    $template->Username = $r->Username;
    $template->FromName = $r->FromName;
    $template->EmailBody = $r->EmailBody;
    $template->Subject = $r->Subject;

    $appCompanyDetails = new stdClass();
    $appCompanyDetails->Name = $r->CompanyName;
    $appCompanyDetails->NameValue = $r->NameValue;
    $appCompanyDetails->TableName = $r->TableName;

    $users = new stdClass();
    $users->name = $r->Name;
    $users->email = $r->Email;
    $users->mobile = $r->Mobile;
    $users->city = $r->City;
    $users->address = $r->Address;
    $users->companyName = $r->NameValue;

    if (!$r->AppId) {
        $tabble_name = "appcompanydetails";
        $column_names = array('NameValue', 'TableName', 'Name');
        $result = $db->insertIntoTable($appCompanyDetails, $column_names, $tabble_name);
    } else {
        if ($db->dbRowUpdate("appcompanydetails", $appCompanyDetails, "Id='$r->AppId'")) {
            $response["status"] = "success";
            $response["table"] = "app company";
            $response["message"] = "Company information updated successfully";
        } else {
            $response["status"] = "error";
            $response["table"] = "app company";
            $response["message"] = "Company information failed to update";
        }
    }

    if ($db->dbRowUpdate("users", $users, "uid='$r->uid'")) {
        $response["status"] = "success";
        $response["table"] = "users";
        $response["message"] = "Company information updated successfully";
    } else {
        $response["status"] = "error";
        $response["table"] = "template";
        $response["message"] = "Company information failed to update";
    }

    if (!$r->TemplateId) {
        $tabble_name = "template";
        $template->Message = $r->SMSMessage;
        $template->CompanyName = $r->NameValue;
        $template->SMSSenderId = $r->SMSSenderId;
        $template->Username = $r->Username;
        $template->FromName = $r->FromName;
        $template->EmailBody = $r->EmailBody;
        $template->Subject = $r->Subject;

        $column_names = array('Message', 'CompanyName', 'SMSSenderId', 'Username', 'FromName', 'EmailBody', 'Subject');
        $result = $db->insertIntoTable($template, $column_names, $tabble_name);
    } else {
        if ($db->dbRowUpdate("template", $template, "Id='$r->TemplateId'")) {
            $response["status"] = "success";
            $response["table"] = "template";
            $response["message"] = "Company information updated successfully";
        } else {
            $response["status"] = "error";
            $response["table"] = "template";
            $response["message"] = "Company information failed to update";
        }
    }
    echoResponse(200, $response);
});

$app->post('/saveTemplate', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('MessageSubject', 'MessageType', 'Message', 'CompanyName'), $r->Template);
    $db = new DbHandler();

    $MessageSubject = $r->Template->MessageSubject;
    $companyName = $r->Template->CompanyName;

    $isTemplateSubjectExists =
        $db->getOneRecord("select 1 from Template where MessageSubject='$MessageSubject' and CompanyName='$companyName'");

    if ($isTemplateSubjectExists) {
        $response["status"] = "error";
        $response["message"] = "An Template Subject already exists!";
        echoResponse(201, $response);
    } else {
        $tabble_name = "template";
        $column_names = array('MessageSubject', 'MessageType', 'Message', 'CompanyName');
        $result = $db->insertIntoTable($r->Template, $column_names, $tabble_name);

        if ($result != NULL) {
            $response["status"] = "success";
            $response["message"] = "Template created successfully";
            $response["Id"] = $result;
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to create Template.";
            echoResponse(201, $response);
        }
    }
});

$app->get('/OrganizerOperatorGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('OrganizerId');
    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;
    if (!isset($_SESSION)) {
        session_start();
    }

    $columns = array(
        0 => 'name',
        1 => 'mobile',
        2 => 'email',
        3 => 'designation',
    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count FROM organizer_operator where organizer_uid =$Id";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT * from organizer_operator WHERE organizer_uid =$Id";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( name LIKE '%" . $requestData['search']['value'] . "%') ";
        $sql .= " AND ( mobile LIKE '%" . $requestData['search']['value'] . "%') ";
        $sql .= " AND ( email LIKE '%" . $requestData['search']['value'] . "%') ";
        $sql .= " AND ( designation LIKE '%" . $requestData['search']['value'] . "%') ";
    }

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

$app->get('/OfferAnalyticsGrid', function () use ($app) {
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
        0 => 'companyName',
        1 => 'email',
        2 => 'product_name',
        3 => 'file_path',
        4 => 'file_type'
    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM leadsform where exhibitorId =$Id";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT case when ex.addoffers is null then 'Special Offer' else ex.addoffers end addoffers ,u.companyName,u.name,u.email,u.mobile,l.source_id, l.userId, l.exhibitorId, l.CurrentDateTime,l.comments 
    FROM `leadsform` l 
       left join users u on u.uid = l.userId
       left join offers ex on l.source_id = ex.id
       WHERE  l.exhibitorId =$Id and l.lead_from = 'Offers' ";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( u.mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.email LIKE '%" . $requestData['search']['value'] . "%') ";
    }

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
$app->get('/CatloagDownloadGrid', function () use ($app) {
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
        1 => 'email',
        2 => 'product_name',
        3 => 'file_path',
        4 => 'file_type'
    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM analytics_downloadfiles where exhibitorId =$Id";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT ex.product_name,l.file_type,SUBSTRING_INDEX(l.file_path, '/', -1),l.id,u.name,u.email, l.product_id, l.user_id, l.exhibitorId, l.CurrentDateTime  FROM `analytics_downloadfiles` l 
        left join users u on u.uid = l.user_id
        left join exhibitionproductdetail ex on l.product_id = ex.id
        WHERE  l.exhibitorId =$Id";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( ex.product_name LIKE '%" . $requestData['search']['value'] . "%') ";
    }

    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => checkIspaidExhibitor($Id) ? $query : decriptDataIfUserIsUnpaid($query)   // total data array
        //     "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});

$app->get('/sharesGrid', function () use ($app) {
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
        0 => 'product_name',
        1 => 'name',
        2 => 'email',

    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM is_share_on where exhibitorId =$Id";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT ex.product_name,l.sharedOn, l.id,u.companyName,u.name,u.email, l.product_id, l.user_id, l.exhibitorId, l.CurrentDateTime  FROM `is_share_on` l inner join users u on u.uid = l.user_id
        left join exhibitionproductdetail ex on l.product_id = ex.id
        WHERE  l.exhibitorId =$Id";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( ex.product_name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.email LIKE '%" . $requestData['search']['value'] . "%') ";
    }

    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => checkIspaidExhibitor($Id) ? $query : decriptDataIfUserIsUnpaid($query)   // total data array
        //     "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});

$app->post('/LoadGrid', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        0 => 'Email',
        2 => 'FirstName',
        2 => 'LastName',
        3 => 'Mobile',
        5 => 'Address'
    );
    if (!isset($_SESSION)) {
        session_start();
    }

    $DataTableName = $_SESSION['tableName'];

    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM " . $DataTableName;
    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT * ";
    $sql .= " FROM " . $DataTableName . " WHERE 1=1 ";

    if ($_SESSION["SearchEmail"]) {
        $sql .= "  " . $_SESSION["SearchEmail"];
    }

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( FirstName LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR LastName LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR Mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR Email LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR Address LIKE '%" . $requestData['search']['value'] . "%' )";
    }


    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "ORDER BY  `CurrentDateTime` DESC LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);


    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        //  "data"            => checkIspaidExhibitor($Id) ? $query : decriptDataIfUserIsUnpaid($query)   // total data array
        "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});

$app->get('/GetExhibitorWebinar', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();

    $sql = "SELECT * from webinar where exhibitorId=$Id ";
    $record = $db->getAllRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";

    echoResponse(200, $response);
});

$app->get('/GetExhibitorWebinareach', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('id');
    $db = new DbHandler();
    $sql = "SELECT * from webinar where id=$Id";
    $record = $db->getOneRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetManagerProfile', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();

    $sql = "SELECT u.uid, u.Email, u.Name, u.Mobile,u.Address,u.City,u.CompanyName as NameValue,
    u.ImagePath, t.Message as SMSMessage,
      ac.TableName,ac.Name as CompanyName,t.SMSSenderId,t.UserName as SenderEmail, t.Username,
       t.FromName, t.Subject, t.EmailBody, ac.Id as AppId, t.Id as TemplateId
    FROM users  u left join template t on u.companyName = t.CompanyName 
    left join appcompanydetails ac on ac.NameValue = u.companyName
    WHERE u.UserType<>'Admin' and u.uid=$Id and 1=1 ";

    $record = $db->getOneRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";

    echoResponse(200, $response);
});


$app->get('/GetUserPhotoDetails', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();

    $sql = "SELECT u.uid,u.ImagePath
    FROM users  u where u.uid=$Id and 1=1 ";

    $record = $db->getOneRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";

    echoResponse(200, $response);
});

$app->post('/saveUserPhoto', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());

    $db = new DbHandler();
    $folderUrl = "/api/v1/UserImages/";

    if ($r->uid != 0) {
        if ($r->ImageData != "") {
            $praImageName = "user" . $r->uid . "1";
            $r->ImagePath = uploadImage($praImageName, $folderUrl, "UserImages/", $r->ImageData);

            $users = new stdClass();
            $users->ImagePath = $r->ImagePath;
            $db->dbRowUpdate("users", $users, "uid='$r->uid'");

            $response["status"] = "success";
            $response["message"] = "Image change successfully.";
            echoResponse(201, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "No image selected.";
            echoResponse(201, $response);
        }
    } else {

        $response["status"] = "error";
        $response["message"] = "something went wrong.";
        echoResponse(201, $response);
    }
});

$app->get('/contactDetailViewerGrid', function () use ($app) {
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
        0 => 'product_name',
        1 => 'name',
        2 => 'mobile',
        3 => 'email',
        4 => 'address',
        5 => 'type',
        6 => 'datetime'
    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM contact_detail_viewer where exhibitorId =$Id";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT ep.product_name,u.name,u.mobile,u.email,u.address, CASE WHEN c.type = 1 THEN 'Product Detail' WHEN c.type = 2 THEN 'Video Detail' WHEN c.type = 3 THEN 'Image Detail' WHEN c.type = 4 THEN 'Salesmanager Detail' ELSE 'Not Available' END as type,c.datetime FROM `contact_detail_viewer` c inner join exhibitionproductdetail ep on c.product_id = ep.id and c.exhibitorid = ep.exhibitorId inner join users u on u.uid = c.uid and c.exhibitorid=$Id ";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( ep.product_name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.email LIKE '%" . $requestData['search']['value'] . "%') ";
    }
    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  " LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => checkIspaidExhibitor($Id) ? $query : decriptDataIfUserIsUnpaid($query)   // total data array
        //  "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});

$app->get('/LeadsGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('exhibitorId');

    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        0 => 'email',
        2 => 'name',
        3 => 'companyName',
        3 => 'phonenumber',
        5 => 'comments',
        6 => 'lead_from',
        7 => 'quantity',
        8 => 'product_name',
        9 => 'scheduleDate',
        10 => 'scheduleTime'
    );
    if (!isset($_SESSION)) {
        session_start();
    }


    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM leadsform";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;


    $sql = "";
    if ($_SESSION['userType'] == "Manager")
        $sql .= "SELECT u.companyName,l.id,u.name,u.email,l.comments,u.mobile as phonenumber,l.exhibitorId,l.scheduleDate,l.scheduleTime,case when isWebinar=1 then 'Webinar' else l.lead_from end as lead_from,p.product_name,o.addoffers,w.Title,case when l.isWebinar=1 then w.Title when p.product_name is null then o.addoffers  else p.product_name end as product_name,l.quantity FROM leadsform l 
        left join users u on l.userId = u.uid
        left join exhibitionproductdetail p on p.id= l.source_id and l.lead_from != 'Offers'
        left join webinar w on w.id= l.source_id and l.isWebinar = 1
        left join offers o on o.id= l.source_id and l.lead_from = 'Offers'
        WHERE l.exhibitorId=" . $_SESSION['uid'] . " and l.isWebinar<>1 and  l.lead_from in('Product','WhatsApp')";
    else
        $sql .= "SELECT u.companyName,l.id,u.name,u.email,l.comments,u.mobile as phonenumber,l.exhibitorId,l.scheduleDate,l.scheduleTime,case when isWebinar=1 then 'Webinar' else l.lead_from end as lead_from,p.product_name,o.addoffers,w.Title,case when l.isWebinar=1 then w.Title when p.product_name is null then o.addoffers  else p.product_name end as product_name,l.quantity FROM leadsform l 
        left join users u on l.userId = u.uid
        left join exhibitionproductdetail p on p.id= l.source_id and l.lead_from != 'Offers'
        left join webinar w on w.id= l.source_id and l.isWebinar = 1
        left join offers o on o.id= l.source_id and l.lead_from = 'Offers'
        WHERE l.exhibitorId=" . $Id . " and l.isWebinar<>1 and l.lead_from in('Product','WhatsApp')";
    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( l.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.phonenumber LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.email LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.comments LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR p.product_name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR o.addoffers LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.lead_from LIKE '%" . $requestData['search']['value'] . "%') ";
    }
    $sql .= " order by l.CurrentDateTime desc ";
    $query = $db->getAllRecord($sql);
    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => checkIspaidExhibitor($Id) ? $query : decriptDataIfUserIsUnpaid($query)   // total data array
    );
    echoResponse(200, $json_data);
});

$app->get('/WebinarLeadsGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('exhibitorId');

    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        0 => 'email',
        1 => 'name',
        2 => 'companyName',
        3 => 'phonenumber',
        5 => 'comments',
        6 => 'lead_from'
    );
    if (!isset($_SESSION)) {
        session_start();
    }


    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM leadsform where isWebinar=1";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;


    $sql = "SELECT *";
    if ($_SESSION['userType'] == "Manager")
        $sql .= " FROM leadsform l
        left join users u on l.userId = u.uid
        inner join webinar w on l.source_id = w.id 
        WHERE  l.isWebinar=1 and l.exhibitorId=" . $_SESSION['uid'] . " and 1=1 ";
    else
        $sql .= " FROM leadsform l
        left join users u on l.userId = u.uid
        WHERE l.isWebinar=1 and l.exhibitorId=$Id";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( l.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.phonenumber LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.email LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.comments LIKE '" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.lead_from LIKE '" . $requestData['search']['value'] . "%') ";
    }
    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => checkIspaidExhibitor($Id) ? $query : decriptDataIfUserIsUnpaid($query)   // total data array
        //     "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});


$app->get('/AreaOfInterestAnalyticsGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('exhibitorId');

    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        0 => 'email',
        2 => 'name',
        3 => 'phonenumber',
        5 => 'areaofinterest'
    );
    if (!isset($_SESSION)) {
        session_start();
    }


    // getting total number records without any search
    $sql = "SELECT  count(1) as Count 
    FROM `visitordetail` v
    inner join users u on v.userId = u.uid and u.userType='Visitor' and v.exhibitorId =$Id";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $interest = '';
    if ($requestData["selectedCategory"] == 'undefined') {
        $interest = $requestData["selectedCategory"];
    }

    $sql = "";
    if ($_SESSION['userType'] == "Manager")
        $sql = "SELECT u.interestArea,u.name,u.mobile,u.email 
        FROM `visitordetail` v
        inner join users u on v.userId = u.uid and u.userType='Visitor' and v.exhibitorId =$Id";
    if ($interest == '')
        $sql .= " where u.interestArea in (" . $requestData["selectedCategory"] . ")";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( u.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.email LIKE '%" . $requestData['search']['value'] . "%') ";
    }
    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $queryAreaOfInterest = $db->getAllRecord("select * from areaofinterest where organizerId =( select u.uid 
    from usermap um inner join users u on u.uid = um.createdById where um.uid =$Id)");
    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => checkIspaidExhibitor($Id) ? $query : decriptDataIfUserIsUnpaid($query), // total data array
        //    "data"            => $query,
        "queryAreaOfInterest" => $queryAreaOfInterest   // total data array
    );
    echoResponse(200, $json_data);
});

$app->get('/ExhibitorUsersGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('exhibitorId');

    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        0 => 'email',
        1 => 'name',
        2 => 'mobile',
        3 => 'designation'
    );
    if (!isset($_SESSION)) {
        session_start();
    }


    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM exhibitoruser where uid=" . $Id;

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;


    $sql = "SELECT *";
    if (isset($Id))
        $sql .= " FROM exhibitoruser l WHERE l.uid=" . $Id;

    if (!empty($requestData['search']['value'])) {
        $sql .= " AND ( l.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.email LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.designation LIKE '" . $requestData['search']['value'] . "%') ";
    }
    $sql .= " order by createdDate desc ";
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

$app->get('/LeadsMeetingGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('exhibitorId');

    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        0 => 'email',
        2 => 'name',
        3 => 'phonenumber',
        5 => 'comments',
        6 => 'scheduleDate',
        7 => 'scheduleTime',
        8 => 'lead_from'
    );
    if (!isset($_SESSION)) {
        session_start();
    }


    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM leadsform where (scheduleDate is not null or scheduleTime is not null)";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;


    $sql = "SELECT *";
    if ($_SESSION['userType'] == "Manager")
        $sql .= " FROM leadsform l 
        left join users u on l.userId = u.uid  
        WHERE  l.scheduleDate<>'' and (l.scheduleDate is not null or l.scheduleTime is not null) and l.exhibitorId=" . $_SESSION['uid'] . " and 1=1 and l.IsEnquiry=0";
    else
        $sql .= " FROM leadsform l 
        left join users u on l.userId = u.uid  
        WHERE  l.scheduleDate<>'' and (l.scheduleDate is not null or l.scheduleTime is not null) and l.IsEnquiry=0 and l.exhibitorId=" . $Id . " l.IsEnquiry=0";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( l.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.phonenumber LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.email LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.lead_from LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.comments LIKE '" . $requestData['search']['value'] . "%') ";
    }
    $sql .= " order by CurrentDateTime desc ";
    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => checkIspaidExhibitor($Id) ? $query : decriptDataIfUserIsUnpaid($query)   // total data array
        //     "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});

$app->get('/LeadsGridAdmin', function () use ($app) {
    $response = array();

    $Id =  $app->request()->get('exhibitorId');

    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        0 => 'email',
        2 => 'name',
        3 => 'phonenumber',
        5 => 'comments'
    );
    if (!isset($_SESSION)) {
        session_start();
    }


    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM leadsform";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT *";
    $sql .= " FROM leadsform l WHERE l.exhibitorId=$Id and 1=1 ";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( l.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.phonenumber LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.email LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.comments LIKE '" . $requestData['search']['value'] . "%') ";
    }
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

$app->get('/callDetailsSeenGrid', function () use ($app) {
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
        1 => 'mobile',
        2 => 'email',
        3 => 'address',
        4 => 'datetime',

    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM call_details_seen where exhibitorId =$Id";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT u.name,u.email,u.mobile,u.address,u.userType,c.exhibitorid,c.datetime FROM `users` as u JOIN `call_details_seen` as c on u.uid=c.uid WHERE c.exhibitorid=$Id ";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( u.mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.email LIKE '%" . $requestData['search']['value'] . "%') ";
    }

    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  " order by c.id desc  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => checkIspaidExhibitor($Id) ? $query : decriptDataIfUserIsUnpaid($query)   // total data array
        //  "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});


$app->get('/ExhibitorGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $paymentStatus =  $app->request()->get('paymentStatus');

    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        0 => 'Email',
        2 => 'Name',
        3 => 'companyName',
        4 => 'exhibitionName',
        5 => 'packageName'
    );
    if (!isset($_SESSION)) {
        session_start();
    }
    $paymentStatusSql = '';
    if ($paymentStatus != 'All') {
        $paymentStatusSql = $paymentStatus == 'UnPaid' ? " And PaymentStatus=0 " : " And PaymentStatus=1 ";
    }
    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM users WHERE  `userType` = 'Manager' and uid in (select uid from usermap where createdById =" . $_SESSION['uid'] . ") " . $paymentStatusSql;
    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "select u.uid,u.name as Name,u.created, u.email as Email,u.address as Address, u.mobile as Mobile,e.name as exhibitionName,e.startDate, e.endDate,ep.name as packageName,ep.price as Price, ep.currency as Currency,
     (SELECT count(1) as visitorCount FROM visitordetail  where exhibitorId=u.uid)as visitorCount,(select count(1) as LeadCount from leadsform where exhibitorId=u.uid) as LeadCount
    from users u
    inner join usermap um on u.uid = um.uid
    inner join exhibition e on e.id = um.exhibitionId and e.isDelete<>1
    LEFT join exhibitorpackagemap epm on epm.exhibitorId = u.uid
    LEFT join exhibitorpackage ep on ep.id = epm.packageId
    where u.uid in (SELECT uid FROM usermap ";

    if ($Id != 0)
        $sql .= " WHERE exhibitionId=" . $Id . " and createdById =" . $_SESSION['uid'] . ") ";
    else
        $sql .= "WHERE createdById =" . $_SESSION['uid'] . ") ";
    $sql = $sql . $paymentStatusSql;

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( u.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.email LIKE '" . $requestData['search']['value'] . "%') ";
    }
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

$app->get('/GetExhibitorPacakage', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();
    $sql = "SELECT DISTINCT e.name as Name,e.email as Email,e.orderId,e.name as companyName,e.created, v.Packages as packageName, v.counter as TotalVideos, c.360_view as 360view, e.uid, COUNT( d.product_video ) AS VideoAdded , (SELECT count(*) FROM `leadsform` WHERE `status`=0 AND `exhibitorId`=e.uid) AS UnreadLeads ,(SELECT count(*) FROM `leadsform` WHERE  `exhibitorId`=e.uid) AS Leads
    FROM users e 
    LEFT JOIN veorderregistration v ON e.name = v.CompanyName
    LEFT JOIN 360_view_details c ON e.uid = c.exhibitor_id
    LEFT JOIN exhibitionproductdetail d ON e.uid = d.exhibitorId
    where uid=$Id
    GROUP BY e.uid";
    $record = $db->getOneRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/OffersGrid', function () use ($app) {
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
        0 => 'addoffers',
        5 => 'highlightsofexhibition'
    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM leadsform";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT * ";
    if ($_SESSION['userType'] == "Manager")
        $sql .= " FROM offers l WHERE l.exhibitorId=" . $_SESSION['uid'] . " ";
    else
        $sql .= " FROM offers l WHERE  l.exhibitorId=$Id";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( l.highlightsofexhibition LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.addoffers LIKE '" . $requestData['search']['value'] . "%') ";
    }

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

$app->get('/ProductsGrid', function () use ($app) {
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
        0 => 'addoffers',
        5 => 'highlightsofexhibition'
    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM leadsform";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT * ";
    if ($_SESSION['userType'] == "Manager")
        $sql .= " FROM offers l WHERE l.exhibitorId=" . $_SESSION['uid'] . " ";
    else
        $sql .= " FROM offers l WHERE  l.exhibitorId=$Id";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( l.highlightsofexhibition LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.addoffers LIKE '" . $requestData['search']['value'] . "%') ";
    }

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




$app->get('/LikesGrid', function () use ($app) {
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
        0 => 'product_name',
        1 => 'name',
        2 => 'email',

    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM product_likes where exhibitorId =$Id";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    /*$sql = "SELECT ex.product_name, l.id,u.name,u.email, l.product_id, l.user_id, l.exhibitorId, l.timestamp  FROM `product_likes` l inner join users u on u.uid = l.user_id
        left join exhibitionproductdetail ex on l.product_id = ex.id
        WHERE  l.exhibitorId =$Id ";*/
    $sql = "SELECT ex.product_name,u.companyName, l.id,u.name,u.email, l.product_id, l.user_id, l.exhibitorId, l.timestamp  
        FROM `product_likes` l LEFT join users u on u.uid = l.user_id
        left join exhibitionproductdetail ex on l.product_id = ex.id
        WHERE  l.exhibitorId =$Id ";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( ex.product_name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.email LIKE '%" . $requestData['search']['value'] . "%') ";
    }

    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => checkIspaidExhibitor($Id) ? $query : decriptDataIfUserIsUnpaid($query)   // total data array
        //  "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});



$app->get('/VisitorAnalyticsGrid', function () use ($app) {
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
        0 => 'companyName',
        1 => 'name',
        2 => 'email',
        2 => 'mobile',

    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count FROM `visitordetail` v 
   left join users u on u.uid=v.userId 
   where ExhibitorId = $Id and u.email is not null";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT u.email ,v.`id`, v.`BrowserId`,
    v.`BrowserName`, v.`ExhibitorId`, v.`CurrentDateTime`, v.`userId`,v.visitcount as visit_count,
    u.mobile,u.companyName,u.name as username, v.LatestVisitedDate as created_at FROM `visitordetail` v 
   left join users u on u.uid=v.userId 
   where ExhibitorId = $Id and u.email is not null";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( u.mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.companyName LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.email LIKE '%" . $requestData['search']['value'] . "%') ";
    }

    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => checkIspaidExhibitor($Id) ? $query : decriptDataIfUserIsUnpaid($query)   // total data array
        //       "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});


$app->get('/AnalyticsGrid', function () use ($app) {
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
        0 => 'BrowserName',
        1 => 'DeviceName',
        2 => 'DeviceType',
        3 => 'Language',
        4 => 'ClientOs',

    );

    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM visitordetail where ExhibitorId =$Id";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT * FROM visitordetail l WHERE  l.ExhibitorId=$Id ";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( l.BrowserName LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.DeviceName LIKE '" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.DeviceType LIKE '" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.Language LIKE '" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.ClientOs LIKE '" . $requestData['search']['value'] . "%' )";
    }

    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval($totalData),  // total number of records
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => checkIspaidExhibitor($Id) ? $query : decriptDataIfUserIsUnpaid($query)   // total data array
        //        "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);
});

$app->post('/GetManagers', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        0 => 'Email',
        2 => 'Name',
        3 => 'Mobile',
        5 => 'Address'
    );
    if (!isset($_SESSION)) {
        session_start();
    }


    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM users";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;


    $sql = "SELECT u.uid, u.Email, u.Name, u.Mobile,u.Address,u.City,u.CompanyName, t.Message as SMSMessage,
        t.SMSSenderId,t.UserName as SenderEmail, t.Username, t.FromName, t.Subject, t.EmailBody";

    if ($_SESSION['userType'] == "Manager")
        $sql .= " FROM users  u left join template t on u.companyName = t.CompanyName
              WHERE u.UserType<>'Admin' and u.Uid=" . $_SESSION['uid'] . " and 1=1 ";
    else
        $sql .= " FROM users  u left join template t on u.companyName = t.CompanyName
              WHERE u.UserType<>'Admin' and 1=1 ";

    if ($_SESSION["SearchEmail"]) {
        $sql .= "  " . $_SESSION["SearchEmail"];
    }

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( u.Name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.Mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.Email LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.Address LIKE '" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.companyName LIKE '" . $requestData['search']['value'] . "%' )";
    }

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


$app->post('/SaveCasappa', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $folderUrl = "http://exhibitionz.com/DataCollectionApp/api/v1/CasappaImage/";

    $response = array();
    $response["ImageUrl1"] = "";
    $response["ImageUrl2"] = "";
    $response["ImageUrl3"] = "";
    $response["ImageUrl4"] = "";
    $response["ImageUrl"] = "";

    if ($r->ImageData != "") {
        $response["ImageUrl"] = uploadImage($r->Mobile . "_" . $r->FirstName . $r->LastName, $folderUrl, "CasappaImage/", $r->ImageData);
    }
    if ($r->ImageData1 != "") {
        $response["ImageUrl1"] = uploadImage($r->Mobile . "_" . $r->FirstName . $r->LastName . "Img1", $folderUrl, "CasappaImage/", $r->ImageData1);
    }

    if ($r->ImageData2 != "") {
        $response["ImageUrl2"] = uploadImage($r->Mobile . "_" . $r->FirstName . $r->LastName . "Img2", $folderUrl, "CasappaImage/", $r->ImageData2);
    }

    if ($r->ImageData3 != "") {
        $response["ImageUrl3"] = uploadImage($r->Mobile . "_" . $r->FirstName . $r->LastName . "Img3", $folderUrl, "CasappaImage/", $r->ImageData3);
    }
    if ($r->ImageData4 != "") {
        $response["ImageUrl4"] = uploadImage($r->Mobile . "_" . $r->FirstName . $r->LastName . "Img4", $folderUrl, "CasappaImage/", $r->ImageData4);
    }

    $r->ImageName = $response["ImageUrl"];
    $r->ImageName1 = $response["ImageUrl1"];
    $r->ImageName2 = $response["ImageUrl2"];
    $r->ImageName3 = $response["ImageUrl3"];
    $r->ImageName4 = $response["ImageUrl4"];

    $db = new DbHandler();
    $tabble_name = "Table_casappa";
    $column_names = array('FirstName', 'LastName', 'Mobile', 'Website', 'Organization', 'Designation', 'Address', 'Email', 'Type', 'Application', 'Products_of_Interest', 'Action', 'ImageName', 'ImageName1', 'ImageName2', 'ImageName3', 'ImageName4', 'DeviceId', 'SalesmanMail');
    $result = $db->insertIntoTable($r, $column_names, $tabble_name);

    if ($result != NULL) {
        $response["status"] = "success";
        $response["message"] = "Data sucessfully saved.";
        $response["id"] = $result;
        $response["email"] = $r->Email;
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to add insert. Please try again.";
    }
    echoResponse(200, $response);
});

$app->get('/getRoles', function () use ($app) {
    $db = new DbHandler();
    $response = array();
    $CompanyValue =  $app->request()->get('CompanyValue');
    $sql = "select id as RoleId,Name as Name,'false' as Action from roles";
    $roles = $db->getAllRecord($sql);
    $userRoles = $db->getAllRecord("Select * from userroles ur where CompanyValue='$CompanyValue'");
    $response['roles'] = $roles;
    $response['userRoles'] = $userRoles;
    $response['status'] = "success";
    echoResponse(200, $response);
});


$app->get('/getSearchContent', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $content =  $app->request()->get('content');
    $query = "SELECT  CONCAT(u.companyName, ' - ',n.product_name,' - ',u.companyDescription,' - ',n.product_technologies,' - ',u.newProductLaunch,'~#~',n.id) as suggestion,u.link as url 
   FROM  exhibitionproductdetail n LEFT JOIN users u on n.exhibitorId = u.uid";
    $result = $db->getAllRecord($query);
    $response['list'] = $result;
    $response['success'] = "success";
    echoResponse(200, $response);
});


$app->get('/ProductTimeLine', function () use ($app) {
    $db = new DbHandler();
    $response = array();
    $Id =  $app->request()->get('Id');
    $sql = "select u.uid,ex.product_image,ex.id,u.ImagePath, u.companyName,ex.product_decription,ex.product_name,ex.product_video,ex.product_image from users u inner join exhibitionproductdetail ex on u.uid=ex.exhibitorId ORDER BY RAND() LIMIT 0,100";
    $allProducts = $db->getAllRecord($sql);

    $sql = "select * from product_likes where user_id=$Id";
    $UsersLike = $db->getAllRecord($sql);
    $response['Products'] = $allProducts;
    $response['Likes'] = $UsersLike;

    $response['success'] = "success";
    echoResponse(200, $response);
});


$app->post('/SaveUserSetting', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();

    $companyName = $r[0]->CompanyValue;
    $db->deleteRecord("delete from userroles where CompanyValue='$companyName'");

    foreach ($r as $row) {
        $tabble_name = "userroles";
        $column_names = array('RoleId', 'CompanyValue', 'Action');
        $result = $db->insertIntoTable($row, $column_names, $tabble_name);
    }
    $response['message'] = "Company permissions updated Successfully.";
    $response['status'] = "success";
    echoResponse(201, $response);
});

$app->get('/getPermissions', function () use ($app) {
    $db = new DbHandler();
    $response = array();

    $CompanyValue =  $app->request()->get('CompanyValue');
    if (!isset($_SESSION)) {
        session_start();
    }
    $sql = "";
    $userType = $_SESSION['userType'];
    if ($userType == "Manager") {
        $sql = "select * from roles r left join userroles ur on r.Id = ur.RoleId where CompanyValue='$CompanyValue' and Action='true'";
    } else {
        $sql = "select Name,Id as RoleId,'true' as Action from roles";
    }
    $roles = $db->getAllRecord($sql);
    $response['Permission'] = $roles;
    $response['status'] = "success";
    echoResponse(200, $response);
});



$app->get('/getRecordById', function () use ($app) {
    $db = new DbHandler();
    $response = array();
    $CompanyValue =  $app->request()->get('CompanyTable');
    $Id =  $app->request()->get('Id');
    $sql = "select * from $CompanyValue where Id=$Id";
    $record = $db->getOneRecord($sql);
    $response['record'] = $record;
    $response['status'] = "success";
    echoResponse(200, $response);
});

$app->post('/updateDataCollectionByIdAndTable', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $tableName = $r->TableName;
    $tableName = $tableName . "";
    $id = $r->Record->Id;
    if ($db->dbRowUpdate($tableName, $r->Record, "Id=$id")) {
        $response["status"] = "success";
        $response["message"] = "Data updated successfully.";
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to update data please try again.";
    }
    echoResponse(200, $response);
});

$app->get('/GetExhibitor', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $record = getExhibitorId($Id);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

function getExhibitorId($Id)
{
    $db = new DbHandler();
    $sql = "SELECT u.*,um.exhibitionId,e.name as ExhibitionName,e.link as exhibitionLink
    FROM users  u 
    inner join usermap um on u.uid=um.uid 
    inner join exhibition e on e.id = um.exhibitionId and e.isDelete<>1
    where u.uid=$Id";
    return $db->getOneRecord($sql);
}
function getPackageByUid($uid)
{
    $db = new DbHandler();
    return $db->getOneRecord("SELECT ep.* FROM  exhibitorpackage ep inner join  `exhibitorpackagemap` epm on ep.id = epm.packageId  WHERE epm.exhibitorid =$uid");
}
$app->get('/GetExhibitionByExhibitorId', function () use ($app) {
    $uid =  $app->request()->get('uid');
    echoResponse(200,  getExhibitionByExhibitorId($uid));
});
$app->get('/GetUserDetails', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $Id = $_SESSION['uid'];

    $sql = "SELECT *
    FROM users  u where u.uid=$Id and 1=1 ";

    $record = $db->getOneRecord($sql);
    if ($record) {
        $record['validity'] = checkIsPackageValidity($record['uid']);
        $record['pacakage'] = getPackageByUid($record['uid']);
        $record['extendedPrice'] = getPendingPaymentInformation($record['uid']);
        $record['exhibition'] = getExhibitionByExhibitorId($record['uid']);
    }

    $response["record"] = $record;
    $response['template'] = getTemplateDetailsByExhibitorId($Id);
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/UpdateProductVideo', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $product_name = $r->product_name;
    $product_video = $r->product_video;
    $id = $r->id;
    $query = "update exhibitionproductdetail set product_name = '$product_name', product_video='$product_video' where id=$id;";
    $result = $db->updateTableValue($query);
    if ($result) {
        $response["status"] = "success";
        $response["message"] = "Product video updated successfully.";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to update product. Please try again.";
        echoResponse(201, $response);
    }
});


$app->post('/SaveOrganizer', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if ($r->IsAdd) {
        unset($r->IsAdd);
        $tabble_name = "organizerdetails";
        $column_names = array('organizer', 'organizerLink');
        $result = $db->insertIntoTable($r, $column_names, $tabble_name);
        if ($result) {
            $response["status"] = "success";
            $response["message"] = "Saved successfully.";
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to Create Organizer. Please try again.";
            echoResponse(201, $response);
        }
    } else {
        if ($db->dbRowUpdate("organizerdetails", $r, "id=$r->id")) {
            $response["status"] = "success";
            $response["message"] = "Organizer updated successfully.";
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to update Organizer. Please try again.";
            echoResponse(201, $response);
        }
    }
});

$app->post('/Save360View', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if ($r->IsAdd) {
        $sql = "select id from 360_view_details where exhibitor_id='" . $r->exhibitor_id . "'";
        $record = $db->getAllRecord($sql);
        if ($record) {
            $r->id = $record[0]["id"];
            $r->IsAdd = false;
        }
    }
    if ($r->IsAdd) {
        unset($r->IsAdd);
        $response = set360ViewSettingForExhibitor($response, $r);
        echoResponse(200, $response);
    } else {
        unset($r->view_360);
        unset($r->IsAdd);
        if ($db->dbRowUpdate("360_view_details", $r, "id=$r->id")) {
        }
        $query = "UPDATE `users` SET `is360View`=1 WHERE uid=" . $r->exhibitor_id;
        $result = $db->updateTableValue($query);
        if ($result) {
            $response["status"] = "success";
            $response["message"] = "360 view updated successfully.";
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to update 360 View. Please try again.";
            echoResponse(201, $response);
        }
    }
});

function set360ViewSettingForExhibitor($response, $r)
{
    $db = new DbHandler();
    $tabble_name = "360_view_details";
    $column_names = array('360_view', 'video_type', 'exhibitor_id', 'isAutoStart');
    $result = $db->insertIntoTable($r, $column_names, $tabble_name);
    if ($result) {
        $response["status"] = "success";
        $response["message"] = "360 view Saved successfully.";
        $query = "UPDATE `users` SET `is360View`=1 WHERE uid=" . $r->exhibitor_id;
        $result = $db->updateTableValue($query);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to Create 360 View. Please try again.";
    }
    return $response;
}

$app->post('/GetAllVideoOfProductExhibitor', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $response = array();

    $session = $db->getSession();
    $uid = $r->uid;
    $sql = "select * from exhibitionproductdetail where exhibitorId='" . $uid . "'";
    $record = $db->getAllRecord($sql);

    $sql1 = "SELECT `id`, `360_view`, `video_type`, `exhibitor_id` FROM `360_view_details` WHERE exhibitor_id='" . $uid . "'";
    $image360 = $db->getOneRecord($sql1);

    $response["record"] = $record;
    $response["Image360"] = $image360;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/Getuser', function () use ($app) {
    $r = json_decode($app->request->getBody());

    $db = new DbHandler();
    $response = array();

    $session = $db->getSession();
    $uid = $r->uid;
    $sql = "SELECT *
    FROM users  u where u.uid=$uid and 1=1 ";
    $record = $db->getOneRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/GetExhibitor', function () use ($app) {
    $r = json_decode($app->request->getBody());

    $db = new DbHandler();
    $response = array();
    $uid = $r->uid;

    $sql = "SELECT u.*,ep.packageId
    FROM users  u left join exhibitorpackagemap ep on u.uid = ep.exhibitorId  where u.uid=$uid and 1=1 ";

    $record = $db->getOneRecord($sql);

    $response["record"] = $record;
    $response["boothType"] = $db->getOneRecord("select * from 360_view_details where exhibitor_id = " . $uid);
    $response["status"] = "success";
    echoResponse(200, $response);
});

function getTemplateDetailsByExhibitorId($exhibitorId)
{
    $db = new DbHandler();
    return  $db->getOneRecord('select u.uid,u.name,u.email,p.id as packageId,p.name as pacakgeName, u.is360View, p.TemplateId
    from exhibitorpackagemap emap 
    inner join exhibitorpackage p on emap.packageId =p.id and emap.exhibitorId = ' . $exhibitorId . '
    inner join booth_editor b on b.id = p.TemplateId 
    inner join users u on u.uid = b.uid');
}
//
$app->get('/GetAllVideoOfExhibitor', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();

    $sql = "select * from exhibitionproductdetail where exhibitorId=" . $Id;
    $record = $db->getAllRecord($sql);

    $sql = "SELECT `id`, `360_view`, `video_type`, `exhibitor_id` FROM `360_view_details` WHERE exhibitor_id=" . $Id;
    $image360 = $db->getOneRecord($sql);

    $sql = "select u.uid,u.name,u.email,u.created,u.link, u.companyName,u.userType,u.ImagePath,u.countOfproduct,u.packageName,u.orderId from users u where u.uid=" . $Id;
    $exhibitorDetail = $db->getOneRecord($sql);

    $response["record"] = $record;
    $response["Image360"] = $image360;
    $response["exhibitorDetail"] = $exhibitorDetail;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/GetAllBoothInfoForTool', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $eid = 0;
    $subSql = "";
    if (isset($r->eid)) {
        $eid = $r->eid;
        $subSql = "eid=" . $eid . " and ";
    }
    $Id =  $r->Id;
    $typeOfEditor =  $r->typeOfEditor;
    $db = new DbHandler();

    if ($typeOfEditor == "Booth") {
        $response["offers"] = $db->getAllRecord("select * from offers where exhibitorId=" . $Id);
        $response["hotspots"] = $db->getAllRecord("select * from hotspots where usertype='Exhibitor'");
        $response["webinars"] = $db->getAllRecord("SELECT * FROM webinar WHERE exhibitorId=$Id");
        $exhibitorDetail = $db->getOneRecord("select u.*,e.exhibitiontypeId,e.id as exhibitionId from users u left join usermap um on um.uid = u.uid left join exhibition e on e.id = um.exhibitionId where u.uid=" . $Id);
        $response["exhibitorDetail"] = $exhibitorDetail;
        if ($exhibitorDetail != null && $exhibitorDetail['exhibitiontypeId'] == 1) {
            $response["record"] = getSaleProductWithAdditionalInfo($Id);
        } else {
            $sql = "select * from exhibitionproductdetail where exhibitorId='" . $Id . "'";
            $response["record"] =  $db->getAllRecord($sql);
        }
        $response['Exhibition'] = getExhibitionByExhibitorId($Id);

        $response["exhibitorList"] = [];

        $pacakgeInfo = $db->getOneRecord("SELECT ep.* FROM  exhibitorpackage ep inner join  `exhibitorpackagemap` epm on ep.id = epm.packageId  WHERE epm.exhibitorid =$Id");
        if ($pacakgeInfo && $pacakgeInfo['TemplateId'] != 0) {
            $response['pacakge'] = $pacakgeInfo['TemplateId'];;
            $boothEditorId = $pacakgeInfo['TemplateId'];
            $response["tools"] = $db->getOneRecord("SELECT * FROM booth_editor WHERE id=" . $boothEditorId);
            $response["images"] = $db->getAllRecord("SELECT * FROM image360upload i  WHERE i.uid in (select uid from booth_editor where id=$boothEditorId)");
        } else {
            $response["tools"] = $db->getOneRecord("SELECT * FROM booth_editor WHERE uid=$Id");
            $response["images"] = $db->getAllRecord("SELECT * FROM image360upload WHERE " . $subSql . "  uid=$Id");
        }
    } else if ($typeOfEditor == "organizerHomePage") {
        $response["offers"] = [];
        $response["hotspots"] = $db->getAllRecord("select * from hotspots where usertype='Organizer'");
        $response["webinars"] = [];
        $response["tools"] = $db->getOneRecord("SELECT * FROM booth_editor WHERE uid=$Id and exhibitionId = $eid");
        $response["images"] = $db->getAllRecord("SELECT * FROM image360upload WHERE " . $subSql . "  uid=$Id");
        $response["record"] = [];

        $exhibition = $db->getOneRecord("select * from exhibition where id = $eid");
        $response['Exhibition'] = $exhibition;
        $response["exhibitorDetail"] = $db->getOneRecord("select u.* from users u where u.uid=" . $Id);
        $response["exhibitorList"] = $db->getAllRecord("select u.uid,u.name,u.link,u.email,u.organizer,um.exhibitionId, (select name from exhibition where id =um.exhibitionId) as ExhibitionName  from users u inner join  usermap um on u.uid =um.uid where um.exhibitionid in (SELECT id FROM `exhibition` where organizerid = $Id and id= $eid)");
    }
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/GetAllBoothInfoForEditor', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $eid = 0;
    $subSql = "";
    if (isset($r->eid)) {
        $eid = $r->eid;
        $subSql = "eid=" . $eid . " and ";
    }

    $Id =  $r->Id;
    $typeOfEditor =  $r->typeOfEditor;
    $db = new DbHandler();
    if ($typeOfEditor == "Booth") {
        $response["offers"] = $db->getAllRecord("select * from offers where exhibitorId=" . $Id);
        $response["hotspots"] = $db->getAllRecord("select * from hotspots where usertype='Exhibitor'");
        $response["webinars"] = $db->getAllRecord("SELECT * FROM webinar WHERE exhibitorId=$Id");
        $response["tools"] = $db->getOneRecord("SELECT * FROM booth_editor WHERE uid=$Id");
        $response["images"] = $db->getAllRecord("SELECT * FROM image360upload WHERE " . $subSql . " uid=" . $Id);
        $exhibition = getExhibitionByExhibitorId($Id);
        $response['Exhibition'] = $exhibition;
        if ($exhibition['exhibitiontypeId'] == '1') {
            $response["record"] = getSaleProductWithAdditionalInfo($Id);
        } else {
            $response["record"] =  GetProductsByExhibitorId($Id);
        }
        $response["exhibitorDetail"] = $db->getOneRecord("select u.* from users u where u.uid=" . $Id);
        $response["exhibitorList"] = [];
    } else if ($typeOfEditor == "organizerHomePage" && $eid > 0) {
        $response["offers"] = [];
        $response["hotspots"] = $db->getAllRecord("select * from hotspots where usertype='Organizer'");
        $response["webinars"] = [];
        $response["tools"] = $db->getOneRecord("SELECT * FROM booth_editor WHERE uid=$Id and exhibitionId = $eid");
        $response["images"] = $db->getAllRecord("SELECT * FROM image360upload WHERE " . $subSql . "  uid=$Id");
        $response["record"] = [];

        $exhibition = $db->getOneRecord("select * from exhibition where id = $eid");
        $response['Exhibition'] = $exhibition;

        $response["exhibitorDetail"] = $db->getOneRecord("select u.* from users u where u.uid=" . $Id);
        $response["exhibitorList"] = $db->getAllRecord("select u.uid,u.name,u.link,u.email,u.organizer,um.exhibitionId, (select name from exhibition where id =um.exhibitionId) as ExhibitionName  from users u inner join  usermap um on u.uid =um.uid where um.exhibitionid in (SELECT id FROM `exhibition` where organizerid = $Id and id= $eid)");
    } else if ($typeOfEditor == "templates") {
    }
    $response["status"] = "success";
    echoResponse(200, $response);
});

function getNextBoothForExploreButtonByExhibitorId($exhibitorId)
{
    $db = new DbHandler();
    $exhibitorList = $db->getAllRecord("select u.uid,u.name,u.email,u.link,um.boothOrder from users u inner join usermap um on u.uid = um.uid  and u.link is not null and u.link <>'' and um.exhibitionId =( select exhibitionid from usermap where  uid = $exhibitorId)  order by boothOrder");
    foreach ($exhibitorList as $key => $exhibitor) {
        if ($exhibitor['uid'] == $exhibitorId) {
            if (($key + 1) == count($exhibitorList)) {
                return $exhibitorList[0];
            } else {
                return $exhibitorList[$key + 1];
            }
        }
    }
}
function GetProductsByExhibitorId($exhibitorId)
{
    $db = new DbHandler();
    return $db->getAllRecord("SELECT * FROM `exhibitionproductdetail` where exhibitorId =$exhibitorId");
}

$app->post('/GetAllBoothIconsForEditor', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $Id =  $r->Id;
    $eid = 0;
    $subSql = "";
    if (isset($r->eid)) {
        $eid = $r->eid;
        $subSql = "eid=" . $eid . " and ";
    }
    $cachedIds = $r->CachedIcons;
    $sql = "select iconId, `path`, '' as base64, `name`, `categoryId`,`hotspotGroup`,`isCommon`,uid,eid from ( ";
    if ($cachedIds == "") {
        $sql = "SELECT `id` as iconId, `path`, '' as base64, `name`, `categoryId`,`hotspotGroup`,`isCommon`,uid,eid FROM `booth_editor_icons` where " . $subSql . " categoryId in ('Grey','Green') or (categoryId='Custom' and uid=$Id)";
    } else {
        $sql .= "SELECT `id` as iconId, `path`, '' as base64, `name`, `categoryId`,`hotspotGroup`,`isCommon`,uid,eid FROM `booth_editor_icons` where   " . $subSql . " categoryId in ('Grey','Green') or (categoryId='Custom' and uid=$Id)";
    }

    $sql = $sql . " UNION
     SELECT `id` as iconId, `path`, '' as base64, `name`, `categoryId`,`hotspotGroup`,`isCommon`,uid,eid FROM
      `booth_editor_icons` where eid=0 and uid=0 ";
    if ($cachedIds != "") {
        $sql .= " ) n where (n.iconId not in ($cachedIds))";
    }

    $response["icons"] = $db->getAllRecord($sql);
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/GetAllBoothIcons', function () use ($app) {
    $response = array();
    $response["icons"] = [];
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $cachedIds = $r->CachedIcons;
    $Id =  $r->Id;
    $eid = '';
    if (isset($r->eid)) {
        $eid =  $r->eid;
    }

    $typeOfEditor =  $r->typeOfEditor;
    $db = new DbHandler();
    $sql = "";
    if ($typeOfEditor == "") {
        $sql = "select iconList,toolList from booth_editor where uid=$Id";
    } else if ($typeOfEditor == 'organizerHomePage') {
        $sql = "select iconList,toolList from booth_editor where  uid=$Id and exhibitionId = $eid  and typeOfEditor='$typeOfEditor'";
    } else {
        $sql = "select iconList,toolList from booth_editor where  uid=$Id  and typeOfEditor='$typeOfEditor'";
    }
    $boothEditor = $db->getOneRecord($sql);
    if ($boothEditor != null) {
        $iconList = $boothEditor['iconList'];
        $boothConfig = json_decode($boothEditor['toolList']);
        if (isset($r->imageId)) {
            $GLOBALS['ImageId'] = $r->imageId;
            $images =  $boothConfig[0]->images;
            $iconsOnInitialImage = array_filter($images, function ($row) {
                $ids = preg_split("/\,/", $GLOBALS['ImageId']);
                return in_array($row->currentPanoramaImage, $ids);
            });
            $iconList = array_unique(array_column($iconsOnInitialImage, 'iconId'));
            $iconList = array_diff($iconList, array("", 0, null));
            $iconList = implode(',', $iconList);
            $iconList = rtrim($iconList, ',');
            $iconList = ltrim($iconList, ',');
            $iconList = str_replace(',,', ",", $iconList);
            $iconList = str_replace(',,', ",", $iconList);
            $sql = "";
            if ($iconList != "") {
                $sql = "SELECT `id` as iconId, `path`, `base64`, `name`, `categoryId`,`hotspotGroup`,`isCommon`,eid FROM `booth_editor_icons` where name <> 'Initial View' and  id in($iconList)";
                if ($cachedIds != "")
                    $sql = "SELECT `id` as iconId, `path`, `base64`, `name`, `categoryId`,`hotspotGroup`,`isCommon`,eid FROM `booth_editor_icons` where name <> 'Initial View' and id in($iconList) and (id not in ($cachedIds))";
                $response["icons"] = $db->getAllRecord($sql);
            } else
                $response["icons"] = [];
            $response["ImageId"] = $GLOBALS['ImageId'];
        }
    } else {
        $response["icons"] = [];
    }

    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/UpdateProductDetails', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    if ($r->Action == "Edit") {
        $response =  EditProduct($r);
    } else {
        $response =  AddProduct($r);
    }
    echoResponse(200, $response);
});

$app->post('/uploadProductImage', function () use ($app) {
    $r = new stdClass();
    $db = new DbHandler();
    $r->product_id = $_POST['product_id'];
    if (isset($_FILES["product_imagelg"])) {
        $file = $_FILES["product_imagelg"]['name'];
        $tempname = $_FILES["product_imagelg"]["tmp_name"];
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "img_0_p_" . $r->product_id . "_" . $date . "." . (explode(".", $file)[1]);
        $folder = "ProductImages/" . $praImageName;
        if (move_uploaded_file($tempname, $folder)) {
            $r->status = 'success';
            $r->product_imagelg = GetHostUrl() . "api/v1/ProductImages/" . $praImageName;
        }
        $arr = explode('/api/v1/', $r->product_imagelg, 2);

        $r->product_image = resize_image($arr[1], "ProductImages/sm_" . "img_0_p_" . $r->product_id . "_" . $date, 200, 200);
        $r->product_imagemd = resize_image($arr[1], "ProductImages/md_" . "img_0_p_" . $r->product_id . "_" . $date, 400, 400);
        //       $query = "update exhibitionproductdetail set product_image ='" . $r->product_image . "',product_imagelg ='" . $r->product_imagelg . "', product_imagemd='" . $r->product_imagemd . "' where id=$r->product_id";
        //     $db->updateTableValue($query);
    } else {
        $r->product_imagelg = "";
        $r->product_image = "";
        $r->product_imagemd = "";
    }
    if (isset($_FILES["product_image1lg"])) {
        $file = $_FILES["product_image1lg"]['name'];
        $tempname = $_FILES["product_image1lg"]["tmp_name"];
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName =  "img_1_p_" . $r->product_id . "_" . $date . "." . (explode(".", $file)[1]);
        $folder = "ProductImages/" . $praImageName;
        if (move_uploaded_file($tempname, $folder)) {
            $r->status = 'success';
            $r->product_image1lg = GetHostUrl() . "api/v1/ProductImages/" . $praImageName;
        }
        $arr = explode('/api/v1/', $r->product_image1lg, 2);
        $praImageName = "p0_" . $date . "." . (explode(".", $praImageName)[1]);
        $r->product_image1 = resize_image($arr[1], "ProductImages/sm_" . "img_1_p_" . $r->product_id . "_" . $date, 200, 200);
        $r->product_image1md = resize_image($arr[1], "ProductImages/md_" . "img_1_p_" . $r->product_id . "_" . $date, 400, 400);
        //$query = "update exhibitionproductdetail set product_image1 ='" . $r->product_image1 . "',product_image1lg ='" . $r->product_image1lg . "', product_image1md='" . $r->product_image1md . "' where id=$r->product_id";
        //$db->updateTableValue($query);
    } else {
        $r->product_image1lg = "";
        $r->product_image1 = "";
        $r->product_image1md = "";
    }
    if (isset($_FILES["product_image2lg"])) {
        $file = $_FILES["product_image2lg"]['name'];
        $tempname = $_FILES["product_image2lg"]["tmp_name"];
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "img_2_p_" . $r->product_id . "_" . $date . "." . (explode(".", $file)[1]);
        $folder = "ProductImages/" . $praImageName;
        if (move_uploaded_file($tempname, $folder)) {
            $r->status = 'success';
            $r->product_image2lg = GetHostUrl() . "api/v1/ProductImages/" . $praImageName;
        }
        $arr = explode('/api/v1/', $r->product_image2lg, 2);
        $praImageName = "img_2_p_" . $r->product_id . "_" . $date . "." . (explode(".", $praImageName)[1]);
        $r->product_image2 = resize_image($arr[1], "ProductImages/sm_" . "img_2_p_" . $r->product_id . "_" . $date, 200, 200);
        $r->product_image2md = resize_image($arr[1], "ProductImages/md_" . "img_2_p_" . $r->product_id . "_" . $date, 400, 400);
        // $query = "update exhibitionproductdetail set product_image2 ='" . $r->product_image2 . "',product_image2lg ='" . $r->product_image2lg . "', product_image2md='" . $r->product_image2md . "' where id=$r->product_id";
        // $db->updateTableValue($query);
    } else {
        $r->product_image2lg = "";
        $r->product_image2 = "";
        $r->product_image2md = "";
    }
    if (isset($_FILES["product_image3lg"])) {
        $file = $_FILES["product_image3lg"]['name'];
        $tempname = $_FILES["product_image3lg"]["tmp_name"];
        $date = date('m-d-Y-h-i-s-m', time());

        $praImageName = "img_3_p_" . $r->product_id . "_" . $date . "." . (explode(".", $file)[1]);
        $folder = "ProductImages/" . $praImageName;
        if (move_uploaded_file($tempname, $folder)) {
            $r->status = 'success';
            $r->product_image3lg = GetHostUrl() . "api/v1/ProductImages/" . $praImageName;
        }
        $arr = explode('/api/v1/', $r->product_image3lg, 2);
        $praImageName = "img_3_p_" . $r->product_id . "_" . $date . "." . (explode(".", $praImageName)[1]);
        $r->product_image3 = resize_image($arr[1], "ProductImages/sm_" . "img_3_p_" . $r->product_id . "_" . $date, 200, 200);
        $r->product_image3md = resize_image($arr[1], "ProductImages/md_" . "img_3_p_" . $r->product_id . "_" . $date, 400, 400);
        $query = "update exhibitionproductdetail set product_image3 ='" . $r->product_image3 . "',product_image3lg ='" . $r->product_image3lg . "', product_image3md='" . $r->product_image3md . "' where id=$r->product_id";
        $db->updateTableValue($query);
    } else {
        $r->product_image3lg = "";
        $r->product_image3 = "";
        $r->product_image3md = "";
    }
    if (isset($_FILES["product_image4lg"])) {
        $file = $_FILES["product_image4lg"]['name'];
        $tempname = $_FILES["product_image4lg"]["tmp_name"];
        $date = date('m-d-Y-h-i-s-m', time());
        $praImageName = "img_4_p_" . $r->product_id . "_" . $date . "." . (explode(".", $file)[1]);
        $folder = "ProductImages/" . $praImageName;
        if (move_uploaded_file($tempname, $folder)) {
            $r->status = 'success';
            $r->product_image4lg = GetHostUrl() . "api/v1/ProductImages/" . $praImageName;
        }
        $arr = explode('/api/v1/', $r->product_image4lg, 2);
        $praImageName = "p_" . $date . "." . (explode(".", $praImageName)[1]);
        $r->product_image4 = resize_image($arr[1], "ProductImages/sm_" . "img_4_p_" . $r->product_id . "_" . $date, 200, 200);
        $r->product_image4md = resize_image($arr[1], "ProductImages/md_" . "img_4_p_" . $r->product_id . "_" . $date, 400, 400);
    } else {
        $r->product_image4lg = "";
        $r->product_image4 = "";
        $r->product_image4md = "";
    }

    $query = "update exhibitionproductdetail 
    set product_image ='" . $r->product_image . "',
    product_imagelg ='" . $r->product_imagelg . "', 
    product_imagemd='" . $r->product_imagemd . "',
    product_image1 ='" . $r->product_image1 . "',
    product_image1lg ='" . $r->product_image1lg . "', 
    product_image1md='" . $r->product_image1md . "',
    product_image2 ='" . $r->product_image2 . "',
    product_image2lg ='" . $r->product_image2lg . "',
    product_image2md='" . $r->product_image2md . "',
    product_image3 ='" . $r->product_image3 . "',
    product_image3lg ='" . $r->product_image3lg . "',
    product_image3md='" . $r->product_image3md . "',
    product_image4 ='" . $r->product_image4 . "',
    product_image4lg ='" . $r->product_image4lg . "',
    product_image4md='" . $r->product_image4md . "' 
where id=$r->product_id";
    $db->updateTableValue($query);

    echoResponse(200, $r);
});

$app->post('/AddProductDetails', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    if ($r->Action == "Edit") {
        $response["status"] = "error";
        $response["message"] = "Action Must be Add.";
        echoResponse(200, $response);
    } else {
        $response =  AddProduct($r);
    }
    echoResponse(200, $response);
});

$app->post('/EditProductDetails', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    if ($r->Action == "Edit") {
        $response =  EditProduct($r);
    } else {
        $response["status"] = "error";
        $response["message"] = "Action Must be Add.";
    }
    echoResponse(200, $response);
});

function AddProduct($r)
{
    $response = array();
    $db = new DbHandler();
    unset($r->Action);
    $folderUrl = "api/v1/ProductImages/";
    $date = date('m-d-Y-h-i-s-m', time());
    $praImageName = "p0_" . $r->exhibitorId . "_" . $date;

    if ($r->product_ImageUpload == true || $r->product_ImageUpload == "true" || $r->product_ImageUpload == 1) {
        $r->product_imagelg = uploadImage($praImageName . "-lg", $folderUrl, "ProductImages/", $r->product_imagelg);
        $arr = explode('/api/v1/', $r->product_imagelg, 2);
        $r->product_imagemd = resize_image($arr[1], "ProductImages/" . $praImageName . 'md', 600, 600);
        $r->product_image = resize_image($arr[1], "ProductImages/" . $praImageName . 'sm', 200, 200);
    } else {
        $r->product_image = "";
        $r->product_imagemd = "";
        $r->product_imagelg = "";
    }
    $date = date('m-d-Y-h-i-s-m', time());
    $praImageName = "p1_" . $r->exhibitorId . "_" . $date;
    if ($r->product_ImageUpload1 == "true" || $r->product_ImageUpload1 == true || $r->product_ImageUpload1 == 1) {
        $r->product_image1lg = uploadImage($praImageName . "-lg", $folderUrl, "ProductImages/", $r->product_image1lg);
        $arr = explode('/api/v1/', $r->product_image1lg, 2);
        $r->product_image1md = resize_image($arr[1], "ProductImages/" . $praImageName . 'md', 600, 600);
        $r->product_image1 = resize_image($arr[1], "ProductImages/" . $praImageName . 'sm', 200, 200);
    } else {
        $r->product_image1 = "";
        $r->product_image1md = "";
        $r->product_image1lg = "";
    }

    $date = date('m-d-Y-h-i-s-m', time());
    $praImageName = "p2_" . $r->exhibitorId . "_" . $date;
    if ($r->product_ImageUpload2 == "true" || $r->product_ImageUpload2 == true || $r->product_ImageUpload2 == 1) {
        $r->product_image2lg = uploadImage($praImageName . "-lg", $folderUrl, "ProductImages/", $r->product_image2lg);
        $arr = explode('/api/v1/', $r->product_image2lg, 2);
        $r->product_image2md = resize_image($arr[1], "ProductImages/" . $praImageName . 'md', 600, 600);
        $r->product_image2 = resize_image($arr[1], "ProductImages/" . $praImageName . 'sm', 200, 200);
    } else {
        $r->product_image2 = "";
        $r->product_image2md = "";
        $r->product_image2lg = "";
    }
    $date = date('m-d-Y-h-i-s-m', time());
    $praImageName = "p3_" . $r->exhibitorId . "_" . $date;
    if ($r->product_ImageUpload3 == "true" || $r->product_ImageUpload3 == true || $r->product_ImageUpload3 == 1) {
        $r->product_image3lg = uploadImage($praImageName . "-lg", $folderUrl, "ProductImages/", $r->product_image3lg);
        $arr = explode('/api/v1/', $r->product_image3lg, 2);
        $r->product_image3md = resize_image($arr[1], "ProductImages/" . $praImageName . 'md', 600, 600);
        $r->product_image3 = resize_image($arr[1], "ProductImages/" . $praImageName . 'sm', 200, 200);
    } else {
        $r->product_image3 = "";
        $r->product_image3md = "";
        $r->product_image3lg = "";
    }
    $date = date('m-d-Y-h-i-s-m', time());
    $praImageName = "p4_" . $r->exhibitorId . "_" . $date;
    if ($r->product_ImageUpload4 == "true" || $r->product_ImageUpload4 == true || $r->product_ImageUpload4 == 1) {
        $r->product_image4lg = uploadImage($praImageName . "-lg", $folderUrl, "ProductImages/", $r->product_image4lg);
        $arr = explode('/api/v1/', $r->product_image4lg, 2);
        $r->product_image4md = resize_image($arr[1], "ProductImages/" . $praImageName . 'md', 600, 600);
        $r->product_image4 = resize_image($arr[1], "ProductImages/" . $praImageName . 'sm', 200, 200);
    } else {
        $r->product_image4 = "";
        $r->product_image4md = "";
        $r->product_image4lg = "";
    }
    if (!isset($r->is_ecommerce)) {
        $r->is_ecommerce = 0;
    } else {
        if ($r->is_ecommerce == 0) {
            $r->UnitPricing = [];
        }
    }

    unset($r->product_ImageUpload1); // Remove field as it not useful.
    unset($r->product_ImageUpload2); // Remove field as it not useful.
    unset($r->product_ImageUpload3); // Remove field as it not useful.
    unset($r->product_ImageUpload4); // Remove field as it not useful.
    $tabble_name = "exhibitionproductdetail";
    $column_names = array(
        'Interstarea', 'exhibitorId', 'form_type', 'product_application', 'product_benefits', 'product_catelog',
        'product_customer', 'product_decription',
        'WhatsAppMobileNumber', 'SkypeId', 'SMSMobileNumber', 'MobileNumber', 'EmailId', 'salesManagerName',
        'product_technologies', 'product_name', 'product_3DModel', 'product_video', 'product_launched', 'product_image', 'product_image1', 'product_image2', 'product_image3', 'product_image4', 'product_imagemd', 'product_image1md', 'product_image2md', 'product_image3md', 'product_image4md', 'product_imagelg', 'product_image1lg', 'product_image2lg', 'product_image3lg', 'product_image4lg', 'product_featured', 'is_ecommerce'
    );
    $result = $db->insertIntoTable($r, $column_names, $tabble_name);
    if ($r->is_ecommerce == "true" || $r->is_ecommerce == true) {
        addUnitPrices($r->UnitPricing, $result);
    } else {
        deleteUnitPricesByProductId($result);
    }

    if ($result != null) {
        $response['product_id'] = $result;
        $response["status"] = "success";
        $response["message"] = "Product/Service details inserted successfully.";
    } else {
        $response['product_id'] = 0;
        $response["status"] = "error";
        $response["message"] = "Failed to insert product. Please try again.";
    }
    return $response;
}

function EditProduct($r)
{
    $db = new DbHandler();
    $id = $r->id;
    $exhibitorId = $r->exhibitorId;
    $UnitPricing = $r->UnitPricing;
    unset($r->Action);

    $folderUrl = "api/v1/ProductImages/";
    $date = date('m-d-Y-h-i-s-m', time());
    $praImageName = "p0_" . $r->exhibitorId . "_" . $date;
    if ($r->product_ImageUpload == "true") {
        $r->product_imagelg = uploadImage($praImageName . "-lg", $folderUrl, "ProductImages/", $r->product_imagelg);
        $arr = explode('/api/v1/', $r->product_imagelg, 2);
        $r->product_imagemd = resize_image($arr[1], "ProductImages/" . $praImageName . 'md', 600, 600);
        $r->product_image = resize_image($arr[1], "ProductImages/" . $praImageName . 'sm', 200, 200);
    } else {
        unset($r->product_image);
        unset($r->product_imagemd);
        unset($r->product_imagelg);
    }
    unset($r->product_ImageUpload); // Remove field as it not useful.
    $date = date('m-d-Y-h-i-s-m', time());
    $praImageName = "p1_" . $r->exhibitorId . "_" . $date;
    if ($r->product_ImageUpload1 == "true") {
        $r->product_image1lg = uploadImage($praImageName . "-lg", $folderUrl, "ProductImages/", $r->product_image1lg);
        $arr = explode('/api/v1/', $r->product_image1lg, 2);
        $r->product_image1md = resize_image($arr[1], "ProductImages/" . $praImageName . 'md', 600, 600);
        $r->product_image1 = resize_image($arr[1], "ProductImages/" . $praImageName . 'sm', 200, 200);
    } else {
        unset($r->product_image1);
        unset($r->product_image1md);
        unset($r->product_image1lg);
    }

    $date = date('m-d-Y-h-i-s-m', time());
    $praImageName = "p2_" . $r->exhibitorId . "_" . $date;
    if ($r->product_ImageUpload2 == "true") {
        $r->product_image2lg = uploadImage($praImageName . "-lg", $folderUrl, "ProductImages/", $r->product_image2lg);
        $arr = explode('/api/v1/', $r->product_image2lg, 2);
        $r->product_image2md = resize_image($arr[1], "ProductImages/" . $praImageName . 'md', 600, 600);
        $r->product_image2 = resize_image($arr[1], "ProductImages/" . $praImageName . 'sm', 200, 200);
    } else {
        unset($r->product_image2);
        unset($r->product_image2md);
        unset($r->product_image2lg);
    }
    $date = date('m-d-Y-h-i-s-m', time());
    $praImageName = "p3_" . $r->exhibitorId . "_" . $date;
    if ($r->product_ImageUpload3 == "true") {
        $r->product_image3lg = uploadImage($praImageName . "-lg", $folderUrl, "ProductImages/", $r->product_image3lg);
        $arr = explode('/api/v1/', $r->product_image3lg, 2);
        $r->product_image3md = resize_image($arr[1], "ProductImages/" . $praImageName . 'md', 600, 600);
        $r->product_image3 = resize_image($arr[1], "ProductImages/" . $praImageName . 'sm', 200, 200);
    } else {
        unset($r->product_image3);
        unset($r->product_image3md);
        unset($r->product_image3lg);
    }
    $date = date('m-d-Y-h-i-s-m', time());
    $praImageName = "p4_" . $r->exhibitorId . "_" . $date;
    if ($r->product_ImageUpload4 == "true") {
        $r->product_image4lg = uploadImage($praImageName . "-lg", $folderUrl, "ProductImages/", $r->product_image4lg);
        $arr = explode('/api/v1/', $r->product_image4lg, 2);
        $r->product_image4md = resize_image($arr[1], "ProductImages/" . $praImageName . 'md', 600, 600);
        $r->product_image4 = resize_image($arr[1], "ProductImages/" . $praImageName . 'sm', 200, 200);
    } else {
        unset($r->product_image4);
        unset($r->product_image4md);
        unset($r->product_image4lg);
    }
    unset($r->product_ImageUpload1); // Remove field as it not useful.
    unset($r->product_ImageUpload2); // Remove field as it not useful.
    unset($r->product_ImageUpload3); // Remove field as it not useful.
    unset($r->product_ImageUpload4); // Remove field as it not useful.
    if ($r->is_ecommerce == "true" || $r->is_ecommerce == '1' || $r->is_ecommerce == 1 || $r->is_ecommerce == true) {
        addUnitPrices($UnitPricing, $id);
    } else {
        deleteUnitPricesByProductId($id);
    }
    unset($r->UnitPricing); // Remove field as it not useful.
    if ($db->dbRowUpdate("exhibitionproductdetail", $r, "`id`=$id and exhibitorId=$exhibitorId")) {
        $response["status"] = "success";
        $response["message"] = "Product/Service details updated successfully.";
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to update product. Please try again.";
    }
    return $response;
}

$app->get('/GetBoothDetails', function () use ($app) {
    $response = array();
    $link =  $app->request()->get('link');
    $organizer =  $app->request()->get('organizer');

    $db = new DbHandler();

    //Check package validity if validty end then lock booth.


    $sql = "SELECT * FROM `users` WHERE link='$link' and organizer = '$organizer' ";
    $companyInfo = $db->getOneRecord($sql);
    if ($companyInfo != null) {
        $uid = $companyInfo["uid"];
        $sql = "SELECT * FROM `exhibitionproductdetail` WHERE exhibitorId=$uid";
        $products = $db->getAllRecord($sql);
        foreach ($products as $key => $product) {
            if ($product['is_ecommerce'] == '1') {
                $products[$key]['UnitPricing']  = getSaleProductUnits($product['id']);
                $products[$key]['Company'] = getUserByUId($product['exhibitorId']);
            } else {
                $products[$key]['UnitPricing']  = [];
                $products[$key]['Company'] = new stdClass();
            }
        }
        $sql = "SELECT * FROM `360_view_details` WHERE exhibitor_id=$uid";
        $view360 = $db->getOneRecord($sql);

        $offers = $db->getAllRecord("select * from offers where exhibitorId=$uid");

        $exhibitorUsers = $db->getAllRecord("select uc.meeting_room_id, uc.value as isLive, uc.configkey, eu.name,eu.id as exhibitorUserId, eu.email,eu.designation from exhibitoruser eu left join userconfiguration uc on eu.uid= uc.uid   where uc.uid=$uid");
        $response["offers"] = $offers;
        $response["nextBooth"] = $companyInfo['PaymentStatus'] == '0' ? getNextBoothForExploreButtonByExhibitorId($uid) : null;
        $response["exhibitorUsers"] = $exhibitorUsers;
        $response["Booth"] = $products;
        $response["View360"] = $view360;
        $response['AppConfiguration'] = getMyvspaceAppConfiguration();
        $companyInfo['PaymentStatus'] = checkIspaidExhibitor($companyInfo['uid']) ? "1" : "0";
        $response["CompanyInfo"] = $companyInfo;
        $sql = "SELECT * FROM webinar WHERE exhibitorId=$uid";
        $response["Webinar"] = $db->getAllRecord($sql);

        $sql = "SELECT e.*,e.id as exhibitionId,ep.*  FROM usermap um 
        inner join exhibition e on um.exhibitionId = e.id and e.isDelete<>1  
        left join exhibitorpackagemap epm on epm.exhibitorId = um.uid
        left join exhibitorpackage ep on ep.id = epm.packageId
        WHERE um.uid=$uid";
        lockExhibitorBooth($uid);
        $response["exhibition"] = $db->getOneRecord($sql);
        $sql = "SELECT * FROM `users` WHERE link='$link' and organizer = '$organizer' ";
        $response["CompanyInfo"] = $db->getOneRecord($sql);
        $sql = "SELECT * FROM `userconfiguration` WHERE uid='$uid'";
        $response["configuration"] = $db->getAllRecord($sql);
        $response["status"] = "success";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "No booth found.";
        echoResponse(200, $response);
    }
});

function getMyvspaceAppConfiguration()
{
    $sql = "select * from app_configuration";
    $db = new DbHandler();
    return $db->getAllRecord($sql);
}

$app->get('/CheckIsUserCanGoLiveOrNot', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();
    $sql = "select live.* from livemeetingscheduler live inner join (SELECT um.createdById FROM `usermap` um 
    inner join users u on u.uid = um.uid and u.uid=$Id
    inner join users uu on uu.uid = um.createdById and uu.userType='Organizer') org on live.organizerId= org.createdById";

    $record = $db->getAllRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetExhibitorProduct', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $ExhibitorId = $app->request()->get('ExhibitorId');
    $db = new DbHandler();

    $sql = "SELECT * FROM `exhibitionproductdetail` WHERE `id`=$Id and exhibitorId=$ExhibitorId and 1=1 ";
    $UnitPricing = getSaleProductUnits($Id);
    $response["UnitPricing"] = $UnitPricing;

    $record = $db->getOneRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});
$app->get('/GetExhibitionType', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $sql = "SELECT * FROM `exhibition_type` ";
    $record = $db->getAllRecord($sql);
    $response["ExhibitionType"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetProductCategories', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $exhibitionName =  $app->request()->get('organizer');
    $sql = "SELECT * FROM `exhibition` e inner join areaofinterestsubcategory c on e.id = c.categoryId where e.link='$exhibitionName'";
    $record = $db->getAllRecord($sql);
    if ($record != null) {
    }

    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetProductSubCategories', function () use ($app) {
    $response = array();
    $link = $app->request()->get('organizer');
    $db = new DbHandler();
    $sql = "";
    $sql1 = "";
    if ($link != '') {
        $sql = "SELECT sc.name,sc.id,sc.description,sc.subCategoryId,sc.organizerId,sc.createdDate FROM `exhibition` e
            inner join areaofinterestsubcategory asub on e.id = asub.categoryId
            inner join areaofinterest sc on asub.id = sc.subCategoryId  
            where e.link='$link' group by sc.name,sc.id,sc.description,sc.subCategoryId,sc.organizerId,sc.createdDate ORDER BY sc.subCategoryId  asc ";
        $sql1 = "SELECT asub.* FROM `exhibition` e
        inner join areaofinterestsubcategory asub on e.id = asub.categoryId 
        where e.link='$link'";

        $sql2 = "SELECT * FROM `exhibition` e where e.link='$link'";
    }
    $record = $db->getAllRecord($sql);
    $response["record"] = $record;
    $response["categories"] =  $db->getAllRecord($sql1);
    $response["exhibitionDetail"] =  $db->getOneRecord($sql2);

    echoResponse(200, $response);
});


$app->get('/GetProductCategoriesAndSubCategory', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $exhibitionName =  $app->request()->get('organizer');
    $response["record"] = getProductCategoriesAndSubCategory($exhibitionName);
    echoResponse(200, $response);
});

function getProductCategoriesAndSubCategory($exhibitionName)
{
    $db = new DbHandler();
    $sql = "SELECT * FROM `exhibition` e inner join areaofinterestsubcategory c on e.id = c.categoryId where e.link='$exhibitionName'";
    $record = $db->getAllRecord($sql);
    foreach ($record as $key => $row) {
        $sql = "SELECT sc.name,sc.id,sc.description,sc.subCategoryId,sc.organizerId,sc.createdDate FROM `exhibition` e
        inner join areaofinterestsubcategory asub on e.id = asub.categoryId
        inner join areaofinterest sc on asub.id = sc.subCategoryId  
        where e.link='$exhibitionName' and sc.subCategoryId =" . $row["id"];
        $record[$key]["subcategorys"] = $db->getAllRecord($sql);
    }
    return  $record;
}

$app->post('/AddLead', function () use ($app) {
    $r = json_decode($app->request->getBody());
    echoResponse(200, AddLead($r));
});


function AddLead($r)
{
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $r->userId = 0;
    if (isset($_SESSION['uid'])) {
        $r->userId = $_SESSION['uid'];
    } else {
        $cust = new stdClass();
        $customerObject = new stdClass();
        $customerObject->mobile = $r->phonenumber;
        $customerObject->email = $r->email;
        $customerObject->name = $r->name;
        $customerObject->organizer = $r->organizer;
        $customerObject->userType = $r->userType;

        $cust->customer = $customerObject;
        $res = registerAsVisitor($cust);
        if ($res['status'] == 'error' && isset($res['existingUser'])) {
            $r->userId = $res['existingUser']['uid'];
            $response["AccountCreate"] = "LoggedIn";
            $response["existingUser"] = $res['existingUser'];
        } else {
            $r->userId = $res["uid"];
            $response["AccountCreate"] = $res["status"];
        }
    }
    $tabble_name = "leadsform";
    if (!isset($r->isWebinar) || $r->isWebinar == '') {
        $r->isWebinar = 0;
    }

    if (!isset($r->IsEnquiry) || $r->IsEnquiry == '') {
        $r->isWebinar = 0;
    }
    $column_names = array('name', 'email', 'phonenumber', 'comments', 'exhibitorId', 'lead_from', 'source_id', 'scheduleTime', 'scheduleDate', 'isWebinar', 'userId', 'quantity', 'IsEnquiry');
    $result = $db->insertIntoTable($r, $column_names, $tabble_name);

    if ($result != NULL) {
        $response["status"] = "success";
        $response["message"] = "Thank you. We will connect you shortly";
        $response["Id"] = $result;
        //Send notification 
        $exhibitor = getExhibitorId($r->exhibitorId);
        $response["smsStatus"] = sendSMSToExhibitorOnLeadReceived($exhibitor, $r->name, $r->phonenumber, $r->email);
        $notification = new stdClass();
        $notification->type = 'Leads';
        $notification->notificationMessage = "You have receieved new Lead from " . $r->name;
        $notification->product_id = 0;
        $notification->exhibitorId = $r->exhibitorId;
        $notification->user_id = 'Guest';
        $notification->lead_id = $result;
        $tabble_name = "notification";
        $column_names = array('type', 'notificationMessage', 'product_id', 'exhibitorId', 'user_id', 'lead_id');
        $result = $db->insertIntoTable($notification, $column_names, $tabble_name);
        $query = "";
        if (isset($r->visitorCompanyName) && isset($r->address))
            $query = "update users set companyName='" . $r->visitorCompanyName . "', address = '" . $r->address . "' where uid=$r->userId";
        else if (isset($r->address))
            $query = "update users set address = '" . $r->address . "' where uid=$r->userId";
        else if (isset($r->visitorCompanyName))
            $query = "update users set companyName='" . $r->visitorCompanyName . "' where uid=$r->userId";

        if ($query != "") {
            $result = $db->updateTableValue($query);
        }
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to generate lead.";
        echoResponse(201, $response);
    }
}

function sendSMSToExhibitorOnLeadReceived($exhibitor, $vname, $vmobile, $vemail)
{
    $text = getYouGotALeadTemplate();
    $text = str_replace("##username##", $exhibitor['name'], $text);
    $text = str_replace("##visitorname##", $vname, $text);
    $text = str_replace("##mobile##", $vmobile, $text);
    $text = str_replace("##email##", $vemail, $text);
    $text = str_replace("##link##", (GetHostUrl() . "a?s=" . CreateAutoLoginUrlForSMS($exhibitor['uid'])), $text);

    return sendSMS($text, $exhibitor['mobile'], $exhibitor['uid']);
}


$app->post('/SaveCallDetailsSeenAnalytics', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $isExist = $db->getOneRecord("Select 1 from call_details_seen where uid =$r->uid");
    if ($isExist == null) {
        $tabble_name = "call_details_seen";
        $column_names = array('uid', 'exhibitorId');
        $db->insertIntoTable($r, $column_names, $tabble_name);
        AddLead($r);
    }
    $response["status"] = "success";
    $response["message"] = "Call Details Saved!";
    echoResponse(200, $response);
});

$app->post('/SaveProductCallDetailsSeenAnalytics', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $isExist = $db->getOneRecord("Select 1 from contact_detail_viewer where uid =$r->uid and product_id =$r->product_id");
    if ($isExist == null) {
        $tabble_name = "contact_detail_viewer";
        $column_names = array('uid', 'exhibitorId', 'product_id', 'type');
        $db->insertIntoTable($r, $column_names, $tabble_name);
        AddLead($r);
    }
    $response["status"] = "success";
    $response["message"] = "Call Details Saved!";
    echoResponse(200, $response);
});

$app->post('/SaveAnalytics', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $sql = "";
    if (!isset($r->userId) || $r->userId == '') {
        $r->userId = 0;
        $sql = "select * from visitordetail where BrowserId='$r->BrowserId' and userId=0 and  ExhibitorId=$r->ExhibitorId";
    } else {
        $sql = "select * from visitordetail where ExhibitorId=$r->ExhibitorId and userId='$r->userId'";
    }
    SaveLastvisitedBooth($r->BrowserId, $r->ExhibitorId, $r->exhibitionId);

    $record = $db->getOneRecord($sql);
    if ($record == null) {
        $tabble_name = "visitordetail";
        $column_names = array('BrowserId', 'BrowserName', 'BrowserVersion', 'ExhibitorId', 'ProductId', 'Engine', 'ClientOs', 'DeviceName', 'DeviceType', 'TimeZone', 'Language', 'userId');
        $result = $db->insertIntoTable($r, $column_names, $tabble_name);
        if ($result != NULL) {
            $response["status"] = "success";
            $response["message"] = "Analytics successfully.";
            $response["Id"] = $result;

            //Send notification   
            $notification = new stdClass();
            $notification->type = 'View';
            $notification->notificationMessage = "Your booth has 1 new view.";
            $notification->product_id = 0;
            $notification->exhibitorId = $r->ExhibitorId;
            $notification->user_id = 0;
            $tabble_name = "notification";
            $column_names = array('type', 'notificationMessage', 'product_id', 'exhibitorId', 'user_id');
            $result = $db->insertIntoTable($notification, $column_names, $tabble_name);
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to generate view.";
            echoResponse(201, $response);
        }
    } else {
        $noofvisits = $record['visitcount'] + 1;
        if (!isset($r->userId) || $r->userId == '') {
            $query = "update visitordetail set visitcount=" . $noofvisits . ", LatestVisitedDate = current_timestamp where ExhibitorId=$r->ExhibitorId and BrowserId='$r->BrowserId' and userId=0";
        } else {
            $query = "update visitordetail set visitcount=" . $noofvisits . ", LatestVisitedDate = current_timestamp where ExhibitorId=$r->ExhibitorId and userId=$r->userId";
        }
        $result = $db->updateTableValue($query);
        $response["status"] = "success";
        $response["message"] = "This visitor have visited " . $noofvisits . " Times";
        echoResponse(200, $response);
    }
});

$app->post('/GetMasterData', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    if (isset($r->Id)) {
        $r->user_id = $r->Id;
    } else {
        $r->user_id = $_SESSION['uid'];
    }
    if ($r->user_id != 0) {

        $data = new stdClass();
        $key = 0;
        $data->visitors = $db->getAllRecord("SELECT u.companyName,u.email,v.`id`, v.`BrowserId`, v.`BrowserName`, v.`ExhibitorId`, v.`CurrentDateTime`, v.`userId`, v.visitcount as totalVisit, v.LatestVisitedDate as LatestVisitedDate,u.mobile,u.name FROM `visitordetail` v inner join users u on u.uid=v.userId 
        where ExhibitorId = $r->user_id
        GROUP by userId");
        $data->visitors = checkIspaidExhibitor($r->user_id) ? $data->visitors : decriptDataIfUserIsUnpaid($data->visitors); // total data array
        foreach ($data->visitors as $visitor) {
            $data->visitors[$key]["Products"] = $db->getAllRecord("select p.product_name,l.* FROM leadsform l inner join exhibitionproductdetail p on p.id=l.source_id where l.exhibitorId='" . $r->user_id . "' and l.email='" . $visitor["email"] . "' and l.isWebinar=0 and l.lead_from='Product'");
            $data->visitors[$key]["Offers"] = $db->getAllRecord("select l.*, o.addoffers FROM leadsform l inner join offers o on o.id = l.source_id where l.exhibitorId='" . $r->user_id . "' and l.email='" . $visitor["email"] . "' and l.lead_from='Offers'");
            $data->visitors[$key]["WhatsApp"] = $db->getAllRecord("select * FROM leadsform where exhibitorId='" . $r->user_id . "' and email='" . $visitor["email"] . "' and lead_from='WhatsApp'");
            $data->visitors[$key]["ScheduleMeeting"] = $db->getAllRecord("select * FROM leadsform where exhibitorId='" . $r->user_id . "' and email='" . $visitor["email"] . "' and scheduleDate <>''");
            $data->visitors[$key]["Webinar"] = $db->getAllRecord("select * FROM leadsform where exhibitorId='" . $r->user_id . "' and email='" . $visitor["email"] . "'  and isWebinar=1");
            $data->visitors[$key]["Downloads"] = $db->getAllRecord("select p.product_name,d.* FROM analytics_downloadfiles d inner join exhibitionproductdetail p on p.id=d.product_id where d.exhibitorId='" . $r->user_id . "' and d.user_id=" . $visitor["userId"]);
            $data->visitors[$key]["Likes"] = $db->getAllRecord("select * FROM product_likes where exhibitorId='" . $r->user_id . "' and user_id=" . $visitor["userId"]);
            $data->visitors[$key]["SocialShare"] = $db->getAllRecord("select s.*,p.product_name FROM is_share_on s left join exhibitionproductdetail p on p.id=s.product_id where s.exhibitorId='" . $r->user_id . "' and s.user_id=" . $visitor["userId"]);

            $key++;
        }
        $response["status"] = "success";
        $response["data"] = $data;
        echoResponse(200, $response);
    }
});

$app->post('/SaveSocialShare', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $r->user_id = $_SESSION['uid'];

    $tabble_name = "is_share_on";
    $column_names = array('sharedOn', 'product_id', 'exhibitorId', 'user_id');
    $result = $db->insertIntoTable($r, $column_names, $tabble_name);
    if ($result != NULL) {
        $response["status"] = "success";
        $response["message"] = "Shared successfully";
        $response["Id"] = $result;
        //Send notification 

        $notification = new stdClass();
        $notification->type = 'Share';
        if ($r->product_id == 0)
            $notification->notificationMessage = "Your virtual booth shared on " . $r->sharedOn;
        else
            $notification->notificationMessage = "Your product shared on " . $r->sharedOn;
        $notification->product_id = $r->product_id;
        $notification->exhibitorId = $r->exhibitorId;
        if (!isset($_SESSION)) {
            session_start();
        }
        $notification->user_id = $_SESSION['uid'];

        $tabble_name = "notification";
        $column_names = array('type', 'notificationMessage', 'product_id', 'exhibitorId', 'user_id');

        $result = $db->insertIntoTable($notification, $column_names, $tabble_name);
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to generate lead.";
        echoResponse(201, $response);
    }
});


$app->post('/UpdateBoothEditor', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $isExistSql = "SELECT * FROM `booth_editor` where uid =$r->uid";
    if (isset($r->exhibitionId) && $r->exhibitionId > 0) {
        $isExistSql = $isExistSql . " and exhibitionId=$r->exhibitionId";
    }

    $isExist = $db->getOneRecord($isExistSql);
    $tabble_name = "booth_editor";
    if ($isExist == null) {
        $column_names = array('toolList', 'uid', 'iconList', 'typeOfEditor', 'exhibitionId', 'bottomButtonList');
        $result = $db->insertIntoTable($r, $column_names, $tabble_name);
        if ($result != NULL) {
            $response["status"] = "success";
            $response["message"] = "Booth Editor Saved successfully.";
        }
    }
    if (isset($r->exhibitionId) && $r->exhibitionId > 0) {
        $db->dbRowUpdate($tabble_name, $r, "uid='$r->uid' and exhibitionId=$r->exhibitionId");
        $response["status"] = "success";
        $response["message"] = "Exhibition booth design setting saved successfully!!";
    } else if ($db->dbRowUpdate($tabble_name, $r, "uid='$r->uid'")) {
        $response["status"] = "success";
        $response["message"] = "Booth Editor Saved successfully!!";
    }
    logBoothEditorInformation($r);
    echoResponse(201, $response);
});
function logBoothEditorInformation($r)
{
    $db = new DbHandler();
    $tabble_name = "booth_editor_log";
    $column_names = array('toolList', 'uid', 'iconList', 'typeOfEditor', 'exhibitionId');
    $db->insertIntoTable($r, $column_names, $tabble_name);
}

$app->post('/SaveFileDownloadAnalytics', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    if (isset($_SESSION['uid']))
        $r->user_id = $_SESSION['uid'];
    else
        $r->user_id = '';

    $tabble_name = "analytics_downloadfiles";
    $column_names = array('file_path', 'product_id', 'exhibitorId', 'user_id', 'file_type');
    $result = $db->insertIntoTable($r, $column_names, $tabble_name);
    if ($result != NULL) {
        $response["status"] = "success";
        $response["message"] = "Save successfully";
        $response["Id"] = $result;
        //Send notification 

        $notification = new stdClass();
        $notification->type = 'Download';
        if ($r->product_id != 0)
            $notification->notificationMessage = "Your product .$r->file_type. has been downloaded by visitor.";
        $notification->product_id = $r->product_id;
        $notification->exhibitorId = $r->exhibitorId;
        if (!isset($_SESSION)) {
            session_start();
        }
        if (isset($_SESSION['uid']))
            $notification->user_id = $_SESSION['uid'];

        $tabble_name = "notification";
        $column_names = array('type', 'notificationMessage', 'product_id', 'exhibitorId', 'user_id');

        $result = $db->insertIntoTable($notification, $column_names, $tabble_name);
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to generate lead.";
        echoResponse(201, $response);
    }
});


$app->post('/doLike', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $tabble_name = "product_likes";

    if (!isset($_SESSION)) {
        session_start();
    }
    $r->user_id = $_SESSION['uid'];

    $column_names = array('product_id', 'user_id', 'exhibitorId', 'islike');
    $result = $db->insertIntoTable($r, $column_names, $tabble_name);

    if ($result != null) {
        $response['result'] = $result;
        $response['success'] = "success";
        $response['status'] = "success";
        $urecord = $db->getAllRecord("select * from product_likes where user_id=" . $r->user_id);
        $response['likes'] = $urecord;
        $response['message'] = "Liked successfully.";

        $notification = new stdClass();
        $notification->type = 'Like';
        if ($r->product_id != 0)
            $notification->notificationMessage = $_SESSION['name'] . " liked your product.";
        else
            $notification->notificationMessage = $_SESSION['name'] . " likes your booth.";

        $notification->product_id = $r->product_id;
        $notification->exhibitorId = $r->exhibitorId;

        if (!isset($_SESSION)) {
            session_start();
        }

        $notification->user_id = $_SESSION['uid'];

        $tabble_name = "notification";
        $column_names = array('type', 'notificationMessage', 'product_id', 'exhibitorId', 'user_id');
        $result = $db->insertIntoTable($notification, $column_names, $tabble_name);
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to like.";
        echoResponse(201, $response);
    }
});



$app->post('/updateIsRead', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $r = json_decode($app->request->getBody());
    $query = "";
    $user_id = $r->uid;
    switch ($r->page) {
        case "product_likes":
            $query = "update product_likes set isRead = 1 where exhibitorId=$user_id;";
            break;
        case "leadsform":
            $query = "update leadsform set isRead = 1 where exhibitorId=$user_id;";
            break;
        case "is_share_on":
            $query = "update is_share_on set isRead = 1 where exhibitorId=$user_id;";
            break;
        case "eco_exhibitor_order":
            $query = "update eco_exhibitor_order set isRead = 1 where exhibitorId=$user_id;";
            break;
        case "contact_detail_viewer":
            $query = "update contact_detail_viewer set isRead = 1 where uid=$user_id;";
            break;
        case "call_details_seen":
            $query = "update call_details_seen set isRead = 1 where uid=$user_id;";
            break;
        case "analytics_downloadfiles":
            $query = "update analytics_downloadfiles set isRead = 1 where exhibitorId=$user_id;";
            break;
        case "visitordetail":
            $query = "update visitordetail v left join users u on u.uid=v.userId set v.isRead = 1 where exhibitorId=688 and u.email is not null;";
            break;
        case "allvisitordetail":
            $query = "update visitordetail set isRead = 1 where exhibitorId=$user_id;";
            break;
        case "offers":
            $query = "update `leadsform` set IsRead=1 WHERE  exhibitorId =$user_id and lead_from = 'Offers'";
            break;
        case "meetings":
            $query = "update leadsform set IsRead=1 where scheduleDate<>'' and (scheduleDate is not null or scheduleTime is not null) and exhibitorId = $user_id;";
            break;
    }

    $result = $db->updateTableValue($query);
    $response['success'] = "success";
    echoResponse(200, $response);
});


$app->get('/markAsRead', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $user_id = 0;
    if ($_SESSION['uid'])
        $user_id = $_SESSION['uid'];

    $query = "update notification set isRead = 1 where exhibitorId=$user_id;";
    $result = $db->updateTableValue($query);
    $response['success'] = "success";
    echoResponse(200, $response);
});


$app->get('/GetUserInterestArea', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $user_id = 0;
    if ($_SESSION['uid'])
        $user_id = $_SESSION['uid'];
    $ids = $db->getOneRecord("select interestArea from users where uid=" . $user_id);
    if ($ids != null) {
        $sql = "select * from sub_category_table where id in (" . $ids["interestArea"] . ")";
        $record = $db->getAllRecord($sql);
        $response['InterestedIn'] = $record;
    } else {
        $record = $db->getAllRecord("select * from sub_category_table");
        $response['InterestedIn'] = $record;
    }
    $response['success'] = "success";
    echoResponse(200, $response);
});
// referal Booth for visitor 
$app->get('/GetReferenceBooth', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $uid =  $app->request()->get('Id');
    $organizer = $app->request()->get('organizer');
    //$uid=474;
    $ids = $db->getOneRecord("select interestArea from users where uid='$uid' and organizer='$organizer'");
    if ($ids != Null && $ids["interestArea"] != '' && $ids["interestArea"] != null) {
        $interstid = $ids["interestArea"];
        $intarray = explode(",", $interstid);
        $exid = array();
        foreach ($intarray as $i) {
            $exrecord = $db->getAllRecord("SELECT Distinct(exhibitorId) FROM `exhibitionproductdetail` where FIND_IN_SET($i, Interstarea)");
            foreach ($exrecord as $eid) {
                array_push($exid, $eid["exhibitorId"]);
            }
        }
        $finalexid =  "'" . implode("', '", $exid) . "'";
        $record = $db->getAllRecord("SELECT link,name,companyName,uid,ImagePath FROM `users` WHERE uid IN($finalexid)");
        $response['ReferBooths'] = $record;
        $response['success'] = "success";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response['ReferBooths'] = [];
        $response["message"] = "No Reference Booth";
        echoResponse(201, $response);
    }
});

//
$app->get('/GetNotification', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $user_id = 0;
    if ($_SESSION['uid'])
        $user_id = $_SESSION['uid'];
    $record = $db->getAllRecord("select * from notification where exhibitorId=$user_id order by dateTime desc limit 60");
    $response['Notification'] = $record;
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->get('/GetVisitedBooth', function () use ($app) {

    $response = array();
    $Id =  $app->request()->get('userId');

    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        1 => 'email',
        0 => 'name',
        2 => 'mobile',
        3 => 'LatestVisitedDate',
        4 => 'visitcount',

    );
    if (!isset($_SESSION)) {
        session_start();
    }




    $sql = "SELECT u.name,u.email,u.mobile,u.companyName,u.organizer,u.link,v.ExhibitorId,v.LatestVisitedDate,v.visitcount from visitordetail v
    LEFT JOIN users u on v.exhibitorId = u.uid
     WHERE   v.userId= '$Id'";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( u.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.email LIKE '%" . $requestData['search']['value'] . "%' ";
    }

    $query = $db->getAllRecord($sql);

    $totalFiltered = count($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
    $sql .=  "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . " ";
    $query = $db->getAllRecord($sql);

    $json_data = array(
        "draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => $query   // total data array
    );
    echoResponse(200, $json_data);

    // when there is a search parameter then we have to modify total number filtered rows as per search result. 

});

$app->get('/GetUserBookmark', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('userId');
    $db = new DbHandler();
    $sql = "SELECT u.name,u.email,u.mobile,u.companyName,u.organizer,u.link,b.briefcaseName,b.type,b.filepath,b.targetId,b.exhibitorId,b.visitorId from briefcase b
    LEFT JOIN users u on b.exhibitorId = u.uid
    WHERE   b.visitorId='$Id' ";
    $record = $db->getAllRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetMustVisitedBooth', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('userId');
    $db = new DbHandler();
    $sql = "select * from users where userType ='Manager' and organizer = (select organizer from users where uid ='$Id')
     and uid not in (select exhibitorId from visitordetail where userid = '$Id') ";
    $record = $db->getAllRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/GetSponsers', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('organizerId');
    $db = new DbHandler();
    $sql = "SELECT * FROM `users` WHERE organizer ='$Id' and userType='Manager' and Sponsor=1";
    $record = $db->getAllRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->get('/getBookmarks', function () use ($app) {
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $response = array();
    if (isset($_SESSION["uid"])) {
        $userId = $_SESSION["uid"];
        $sql = "select * from briefcase where visitorId=" . $userId;
        $result = $db->getAllRecord($sql);
        $response['data'] = $result;
    } else {
        $response['data'] = array();
    }
    $response['success'] = "success";
    echoResponse(200, $response);
});



$app->get('/VisitorMeetingGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('userId');

    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        1 => 'email',
        0 => 'name',
        2 => 'mobile',
        3 => 'comments',
        4 => 'scheduleDate',
        5 => 'scheduleTime',

    );
    if (!isset($_SESSION)) {
        session_start();
    }


    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM leadsform where (scheduleDate is not null or scheduleTime is not null)";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;


    // $sql = "SELECT *";

    //     $sql .= " FROM leadsform l WHERE  l.scheduleDate<>'' and (l.scheduleDate is not null or l.scheduleTime is not null) and l.exhibitorId=" . $_SESSION['uid'] . " and 1=1 ";
    $sql = "SELECT u.name,u.email,u.mobile,u.companyName,l.comments,l.scheduleDate,l.scheduleTime from leadsform l 
      LEFT JOIN users u on l.exhibitorId = u.uid
      WHERE  l.scheduleDate<>'' and (l.scheduleDate is not null or l.scheduleTime is not null) and  l.userId=$Id";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( u.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.email LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.comments LIKE '" . $requestData['search']['value'] . "%') ";
    }

    $sql .= " order by l.CurrentDateTime desc ";

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

    // when there is a search parameter then we have to modify total number filtered rows as per search result. 


});


$app->get('/AllVisitorGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('exhibitorId');
    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        1 => 'email',
        0 => 'name',
        2 => 'mobile'

    );
    if (!isset($_SESSION)) {
        session_start();
    }

    // getting total number records without any search
    $sql = "SELECT count(*) as Count from visitordetail where ExhibitorId =" . $Id;

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT v.ExhibitorId,Date_FORMAT(v.LatestVisitedDate, '%m/%d/%Y %r')as LatestVisitedDate,Date_FORMAT(v.lastVisitSeen, '%m/%d/%Y %r')as lastVisitSeen,v.BrowserName,v.BrowserId,v.DeviceName,v.TimeZone,v.visitcount,v.ClientOs,v.Language,v.DeviceType,Date_FORMAT(v.CurrentDateTime,'%m/%d/%Y %r') as CurrentDateTime   from visitordetail v where v.ExhibitorId ='$Id'";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( v.BrowserName LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR v.DeviceName LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR v.DeviceType LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR v.ClientOs LIKE '%" . $requestData['search']['value'] . "%')";
    }

    $sql .= " order by v.LatestVisitedDate desc ";

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

    // when there is a search parameter then we have to modify total number filtered rows as per search result. 
});

$app->get('/LiveVisitorGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('exhibitorId');

    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        1 => 'email',
        0 => 'name',
        2 => 'mobile'

    );
    if (!isset($_SESSION)) {
        session_start();
    }

    // getting total number records without any search
    $sql = "SELECT count(*) as Count from visitordetail where ExhibitorId =" . $Id;

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;

    $sql = "SELECT u.companyName,u.uid,u.name,u.email, v.ExhibitorId,v.LatestVisitedDate,TIME_FORMAT(v.lastVisitSeen, '%r')as lastVisitSeen,v.BrowserName,v.DeviceName,v.TimeZone,v.visitcount,v.ClientOs  from visitordetail v inner join  users u on v.userId = u.uid where v.ExhibitorId =$Id and lastVisitSeen>current_timestamp-1000";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( u.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.email LIKE '%" . $requestData['search']['value'] . "%')";
    }

    $sql .= " order by v.lastVisitSeen desc ";

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

    // when there is a search parameter then we have to modify total number filtered rows as per search result. 


});

$app->get('/VisitorOfferGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('userId');

    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        1 => 'email',
        0 => 'name',
        2 => 'mobile',
        5 => 'comments',
        3 => 'highlightsofexhibition',
        4 => 'addoffers',

    );
    if (!isset($_SESSION)) {
        session_start();
    }


    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM leadsform where lead_from ='Offers'";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;


    $sql = "SELECT u.name,u.email,u.mobile,u.companyName,l.comments,o.highlightsofexhibition,o.addoffers from leadsform l
    LEFT JOIN users u ON l.exhibitorId= u.uid
    LEFT JOIN offers o ON u.uid = o.exhibitorId
    where l.userId = $Id and l.lead_from ='Offers'";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( u.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.email LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.comments LIKE '" . $requestData['search']['value'] . "%') ";
    }

    $sql .= " order by l.CurrentDateTime desc ";

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

    // when there is a search parameter then we have to modify total number filtered rows as per search result. 


});

$app->get('/VisitorWebinarGrid', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('userId');

    $db = new DbHandler();
    // storing  request (ie, get/post) global array to a variable  
    $requestData = $_REQUEST;

    $columns = array(
        // datatable column index  => database column name
        1 => 'email',
        0 => 'name',
        2 => 'mobile',
        7 => 'comments',
        3 => 'Title',
        4 => 'Description',
        5 => 'WDate',
        6 => 'WTime'

    );
    if (!isset($_SESSION)) {
        session_start();
    }


    // getting total number records without any search
    $sql = "SELECT count(*) as Count ";
    $sql .= " FROM leadsform where isWebinar='1'";

    $NoOfRecords = $db->getOneRecord($sql);
    $totalData = $NoOfRecords["Count"];
    $totalFiltered = $totalData;


    $sql = "SELECT u.name,u.email,u.mobile,u.companyName,l.comments,w.Title,w.Description,w.WDate,w.WTime from leadsform l
    LEFT JOIN users u ON l.exhibitorId= u.uid
    LEFT JOIN webinar w ON u.uid = w.exhibitorId
    where l.userId = $Id and l.isWebinar='1' ";

    if (!empty($requestData['search']['value'])) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
        $sql .= " AND ( u.name LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.mobile LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR u.email LIKE '%" . $requestData['search']['value'] . "%' ";
        $sql .= " OR l.comments LIKE '" . $requestData['search']['value'] . "%') ";
    }

    $sql .= " order by w.createdDate desc ";

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

    // when there is a search parameter then we have to modify total number filtered rows as per search result. 


});

$app->post('/AddBookMark', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (!isset($_SESSION)) {
        session_start();
    }
    $r->userId = $_SESSION['uid'];
    $tabble_name = "briefcase";
    $column_names = array('briefcaseName', 'type', 'targetId', 'exhibitorId', 'visitorId', 'filepath');
    $result = $db->insertIntoTable($r, $column_names, $tabble_name);

    if ($result != NULL) {
        $response["status"] = "success";
        $response["message"] = "Thank you. We will connect you shortly";
        $response["Id"] = $result;
        //Send notification 
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to generate lead.";
        echoResponse(201, $response);
    }
});


$app->get('/GetAllBooth', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $record = $db->getAllRecord("SELECT link,name,companyName,uid,ImagePath FROM `users` WHERE link <>'' ORDER BY RAND() LIMIT 0,100");
    $response['Booths'] = $record;
    $response['success'] = "success";
    echoResponse(200, $response);
});


$app->get('/GetExhibitorByBoothName', function () use ($app) {
    $response = array();
    $BoothName =  $app->request()->get('BoothName');
    $organizer =  $app->request()->get('organizer');
    $db = new DbHandler();
    $urecord = $db->getOneRecord("select * from users where link='$BoothName' and organizer= '$organizer'");
    if ($urecord != null) {
        $Id = $urecord["uid"];
        $response['udata'] = $urecord;
        $response['Id'] = $Id;
        $response['CompanyName'] = $urecord["companyName"];
        $response['ExhibitorEmailId'] = $urecord["email"];
        $response['CompanyName'] = $urecord["companyName"];
        $response['BoothStatus'] = $urecord["BoothStatus"];

        $sql =   "SELECT * from pacakgefeatures pf 
        inner join pacakgefeaturemap pfm on pf.id =pfm.featureId 
        inner join exhibitorpackagemap epm on epm.packageId = pfm.packageId and epm.exhibitorId = " . $Id;

        $response['packageFeatures'] = $db->getAllRecord($sql);

        $response['success'] = "success";

        if (!isset($_SESSION)) {
            session_start();
        }
        if (isset($_SESSION['uid'])) {
            $user_id = $_SESSION['uid'];
            $urecord = $db->getAllRecord("select * from product_likes where  exhibitorId=$Id");
            $response['likes'] = $urecord;
            echoResponse(200, $response);
        } else {
            $response['likes'] = [];
            echoResponse(200, $response);
        }
    } else {
        $response['message'] = "Booth not exist.";
        $response['boothNotExist'] = true;
        echoResponse(200, $response);
    }
});
$app->get('/getOffersById', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();
    $offer = $db->getOneRecord("select * from offers where id=$Id");
    $response['offer'] = $offer;
    $response['success'] = "success";
    echoResponse(200, $response);
});
$app->get('/getOrganizerOperatorById', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();
    $Operator = $db->getOneRecord("select * from organizer_operator where id=$Id");
    $response['Operator'] = $Operator;
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->get('/getoffersByExhibitorBooth', function () use ($app) {
    $response = array();
    $BoothName =  $app->request()->get('BoothName');
    $db = new DbHandler();

    $urecord = $db->getOneRecord("select * from users where link='$BoothName'");
    if ($urecord != null) {
        $Id = $urecord["uid"];
        $list = $db->getAllRecord("select * from offers where exhibitorId=$Id");
        $response['list'] = $list;
        $response['success'] = "success";
        echoResponse(200, $response);
    } else {
        $response['not-found'] = "failed";
        echoResponse(200, $response);
    }
});

$app->get('/GetProductDetails', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();

    if (!isset($_SESSION)) {
        session_start();
    }
    if (isset($_SESSION['uid'])) {
        $user_id = $_SESSION['uid'];
        $sql = "select * from product_likes where user_id=$user_id";
        $UsersLike = $db->getAllRecord($sql);
        $response['Likes'] = $UsersLike;
    }

    $urecord = $db->getOneRecord("select * from exhibitionproductdetail where id=$Id");

    if ($urecord != null) {
        $response['product'] = $urecord;
        $exhibitorId = $urecord['exhibitorId'];
        $response['companyInfo']  = $db->getOneRecord("select `uid`, `name`, `companyName`, `ImagePath`,`email`, `companyWebsite`, `companyDescription`,  `link`,`organizer` from users where uid=$exhibitorId");
        $response['success'] = "success";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Product detail not found.";
        echoResponse(201, $response);
    }
});



$app->post('/UpdateExhibitor', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());

    $db = new DbHandler();
    $folderUrl1 = "api/v1/UserImages/";
    $folderUrl = "api/v1/ProductBrochureImg/";
    if ($r->uid != 0) {
        if ($r->VisitingCardUpload) {
            if ($r->VisitingCard != "") {
                $praImageName =   rand(10, 100) . "visting-card" . $r->uid;
                $r->VisitingCard = uploadimage($praImageName, $folderUrl, "ProductBrochureImg/", $r->VisitingCard);
            }
        }
        unset($r->VisitingCardUpload);
        unset($r->Image360);

        if ($r->ImageData != "") {
            $praImageName = "user" . $r->uid . "1";
            $r->ImagePath = uploadImage($praImageName, $folderUrl1, "UserImages/", $r->ImageData);
        }
        unset($r->ImageData);
        $isExist = $db->getOneRecord("SELECT * FROM `exhibitorpackagemap` where exhibitorId =$r->uid");
        if ($isExist == null) {
            $pacakageFeatureMap = new stdClass();
            $pacakageFeatureMap->exhibitorId = $r->uid;
            $pacakageFeatureMap->packageId = $r->packageId;
            $tabble_name = "exhibitorpackagemap";
            $column_names = array('exhibitorId', 'packageId');
            $db->insertIntoTable($pacakageFeatureMap, $column_names, $tabble_name);
            //insert
        } else {
            //update
            $query = "update exhibitorpackagemap set  packageId= $r->packageId where exhibitorId =$r->uid";
            $db->updateTableValue($query);
        }
        unset($r->packageId);
        if ($db->dbRowUpdate("users", $r, "uid='$r->uid'")) {
            $response["status"] = "success";
            $response["message"] = "Updated successfully.";
            echoResponse(201, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to update.";
            echoResponse(201, $response);
        }
    } else {

        $response["status"] = "error";
        $response["message"] = "something went wrong.";
        echoResponse(201, $response);
    }
});


$app->post('/Activateaccount', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $orderId = $r->orderId;
    $packageName = $r->packageName;
    $id = $r->uid;

    if (!isset($_SESSION)) {
        session_start();
    }
    $Email = $_SESSION['email'];
    $sql = "SELECT 	orderId , orderCount FROM `orderpacakge` WHERE  `email`='$Email' and `packageName`='$packageName' and `orderId`='$orderId' and 1=1 ";

    $record = $db->getOneRecord($sql);
    if ($record == NUll) {
        $response["status"] = "error";
        $response["message"] = "Your Order Id and Package Name does not match with our database please contact administrator for activate account.";
        echoResponse(201, $response);
    } else {
        if ($record["orderId"] !== null) {
            $counter = $record["counter"];
            $query = "update users set orderId = '$orderId', packageName ='$packageName', countOfproduct='$counter' where uid=$id;";
            $result = $db->updateTableValue($query);
            if ($result) {
                $_SESSION['orderId'] = $record["order_id"];
                $_SESSION['countOfproduct'] = $record["counter"];
                $response["status"] = "success";
                $response["message"] = "Account activeted  successfully.";
                echoResponse(200, $response);
            } else {
                $response["status"] = "error";
                $response["message"] = "Failed to update acoount details. Please try again.";
                echoResponse(201, $response);
            }
        } else {
            $response["status"] = "error";
            $response["message"] = "Your Order Id and Package Name does not match with our database please contact administrator for activate account.";
            echoResponse(201, $response);
        }
    }
});


$app->get('/GetPacakageDetailes', function () use ($app) {
    $response = array();
    $Id =  $app->request()->get('Id');
    $db = new DbHandler();

    $sql = "select s.uid, s.name, s.email, p.isDuringExhibitionz, p.isPostExhibitionz, p.isPreExhbitionz 
    from users s
    left join package  p ON  s.uid = p.exhibitorId WHERE s.uid=" . $Id;
    $record = $db->getAllRecord($sql);
    $response["record"] = $record;
    $response["status"] = "success";
    echoResponse(200, $response);
});

$app->post('/visitor_history', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $r = json_decode($app->request->getBody());

    $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $date1 = $date->format('Y-m-d');
    $time = $date->format('H:i:s');

    //$r->history->user_id=$uid;
    if ($r->history->user_id == '' || $r->history->user_id == null) {
        $session = $db->getSession();
        $uid = $session['uid'];
    }
    $r->history->start_time = $time;

    $tabble_name = "boothvisitshistory";
    $column_names = array('start_time', 'user_id', 'exhibitor_id', 'booth_url');
    $id = $db->insertIntoTable($r->history, $column_names, $tabble_name);
    $recentVisitor = $db->getOneRecord("SELECT visitcount FROM `visitordetail` WHERE userId =" . $r->history->user_id);
    if ($recentVisitor["visitcount"] >= 1) {
        $response["alreadyVisited"] = 1;
    }
    $response["status"] = "success";
    $response['id'] = $id;
    echoResponse(200, $response);
});

$app->get('/getExhibitorsByExhibitionId', function () use ($app) {
    $id = $app->request()->get('id');

    $response = array();
    $response['exhibitorList'] = getExhibitorListByExhibitionId($id);
    $response['success'] = "success";
    echoResponse(200, $response);
});
function getExhibitorListByExhibitionId($id)
{
    $db = new DbHandler();
    $sql = "select u.*,um.boothOrder from users u inner join usermap um on u.uid=um.uid where um.exhibitionId='$id' order by um.boothOrder ";
    return $db->getAllRecord($sql);
}
$app->get('/getexhibitors', function () use ($app) {
    $db = new DbHandler();
    $organizer = $app->request()->get('organizer');
    $sql = "select * from users where organizer='$organizer'";
    $metaResult = $db->getAllRecord($sql);
    $response = array();
    $response['metaResult'] = $metaResult;
    $response['success'] = "success";
    echoResponse(200, $response);
});
$app->get('/getexhibitorsWithAreaOfInterest', function () use ($app) {
    $db = new DbHandler();
    $areaOfInterest = $app->request()->get('areaOfInterest');
    $sql = "SELECT p.Interstarea,u.link,u.uid,u.organizer,u.name,u.imagepath FROM `users` u inner join exhibitionproductdetail p on u.uid = p.exhibitorId WHERE u.link is not null and  u.usertype='Manager' and find_in_set('$areaOfInterest',p.Interstarea)";
    $metaResult = $db->getAllRecord($sql);
    $response = array();
    $response['metaResult'] = $metaResult;
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->post('/Add_categories', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $r = json_decode($app->request->getBody());
    $tabble_name = "category_table";
    $column_names = array('category_name', 'organizer');
    $id = $db->insertIntoTable($r->category, $column_names, $tabble_name);

    $response["status"] = "success";
    $response['id'] = $id;
    echoResponse(200, $response);
});

$app->post('/Add_subcategories', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $r = json_decode($app->request->getBody());
    $tabble_name = "sub_category_table";
    $column_names = array('sub_category_name', 'category_id');
    $id = $db->insertIntoTable($r->subcategory, $column_names, $tabble_name);

    $response["status"] = "success";
    $response['id'] = $id;
    echoResponse(200, $response);
});

$app->post('/updateStatus', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $r = json_decode($app->request->getBody());
    $id = $r->data->id;
    $Status = $r->data->status;
    $type = $r->data->type;
    if ($type == "mail") {
        $query = "update users set email_status = $Status where uid='$id';";
    } else {
        $query = "update users set sms_status = $Status where uid='$id';";
    }

    $result = $db->updateTableValue($query);
    $response["status"] = "success";
    $response["message"] = "Updated";
    echoResponse(200, $response);
});

$app->get('/getallvisitors', function () use ($app) {
    $db = new DbHandler();
    $id = $app->request()->get('Id');
    $user_id = array();
    $sql = "SELECT case when u.email is null then 'Unknown'else u.email end as email ,v.`id`, v.`BrowserId`,
     v.`BrowserName`, v.`ExhibitorId`, v.`CurrentDateTime`, v.`userId`,v.visitcount as visit_count,
     u.mobile,u.companyName,u.name as username, v.LatestVisitedDate as created_at FROM `visitordetail` v 
    left join users u on u.uid=v.userId 
    where ExhibitorId = $id and u.email is not null";
    $metaResult = $db->getAllRecord($sql);

    $response['metaResult'] = $metaResult;
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->get('/getallvisitorsAdmin', function () use ($app) {
    $db = new DbHandler();

    $user_details = array();
    $user = array();
    $user_details = $db->getAllRecord('select * from users where userType="Manager"');
    foreach ($user_details as $u) {
        $res["username"] = $u['name'];
        $res["mobile"] = $u['mobile'];
        $res["email"] = $u['email'];
        $count = array();
        $count = $db->getAllRecord("select count(*) as count FROM `boothvisitshistory` where  exhibitor_id='" . $u["uid"] . "'");
        $res['visit_count'] = $count[0]['count'];
        array_push($user, $res);
    }
    $response['metaResult'] = $user;
    $response['success'] = "success";
    echoResponse(200, $response);
});


$app->get('/createToken', function () use ($app) {
    $id = $app->request()->get('uid');
    $response['success'] = CreateAutoLoginUrlForSMS($id);
    echoResponse(200, $response);
});

$app->get('/UpdateVisitorLastSeen', function () use ($app) {
    $db = new DbHandler();
    $id = $app->request()->get('Id');
    $eid = $app->request()->get('ExhibitorId');
    $browserId = $app->request()->get('BrowserId');
    if (!isset($id) || $id == null) {
        $query = "update visitordetail set lastVisitSeen=current_timestamp where ExhibitorId =$eid and browserId=$browserId";
    } else {
        $query = "update visitordetail set lastVisitSeen=current_timestamp where ExhibitorId =$eid and userId = $id ";
    }
    $result = $db->updateTableValue($query);
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->get('/getLastBoothVisitForRedirect', function () use ($app) {
    $db = new DbHandler();
    $response = array();
    $e = $app->request()->get('e');
    $exhibition = getExhibitionByLink($e);
    if ($exhibition == null) {
        $response['status'] = "Failed";
        $response['message'] = "Link is invalid";
    } else {
        $query = "SELECT * FROM `lastvisitoronthebooth` where exhibitionId =" . $exhibition['id'] . " order by id desc";
        $result = $db->getOneRecord($query);
        if ($result != null) {
            $booth = getNextBoothByExhibitorId($result['boothId']);
            $response['booth'] = $booth;
            $response['condition1'] = "Visitor with existing last visit";
            $response['result'] = $result;
            $response['success'] = "success";
        } else {
            $query = "select um.*,u.link,u.organizer from usermap um  inner join users u on um.uid = u.uid   where u.organizer ='" . $exhibition['link'] . "'  ORDER by boothOrder";
            $result = $db->getOneRecord($query);
            $response['result'] = $result;
            $booth = new stdClass();
            $booth->link = $result['link'];
            $booth->organizer = $result['organizer'];
            $booth->uid = $result['uid'];
            $response['condition'] = "Visitor with first visit";
            $response['booth'] = $booth;
            $response['success'] = "success";
        }
    }
    echoResponse(200, $response);
});
function getNextBoothByExhibitorId($exhibitorId)
{
    $db = new DbHandler();
    $query = "SELECT um.*,u.link,u.organizer FROM usermap um  inner join users u on um.uid = u.uid  where um.uid =$exhibitorId";
    $lastVisited = $db->getOneRecord($query);
    $query = "select um.*,u.link,u.organizer from usermap um  inner join users u on um.uid = u.uid   where u.link is not null and um.exhibitionId =(SELECT exhibitionId FROM usermap where uid =$exhibitorId) ORDER by boothOrder";
    $result = $db->getAllRecord($query);
    foreach ($result as $key => $u) {
        if ($u['uid'] == $lastVisited['uid']) {
            if (($key + 1) == count($result)) {
                return $result[0];
            } else {
                return $result[$key + 1];
            }
        }
    }
    return false;
}

$app->post('/getvisitorscount', function () use ($app) {
    $db = new DbHandler();

    $r = json_decode($app->request->getBody());
    $id = $r->user_id;
    $user = array();
    $u = $db->getOneRecord("select * from users where uid='$id'");
    $res["username"] = $u['name'];
    $res["mobile"] = $u['mobile'];
    $res["email"] = $u['email'];
    $count = array();
    $count = $db->getAllRecord("select sum(visitcount) as count FROM `visitordetail` where  ExhibitorId='" . $u["uid"] . "'");
    $res['visit_count'] = $count[0]['count'];

    $response['metaResult'] = $res;
    $response['status'] = "success";
    echoResponse(200, $response);
});

$app->post('/updateanothermobileno', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $r = json_decode($app->request->getBody());
    $id = $r->data->user_id;
    $Status = $r->data->status;
    $mobile1 = $r->data->mobileno1;
    $mobile2 = $r->data->mobileno2;
    $mobile3 = $r->data->mobileno3;

    $query = "update users set sms_status = $Status, mobile1='$mobile1', mobile2='$mobile2', mobile3='$mobile3' where uid='$id'";
    $result = $db->updateTableValue($query);
    $response["status"] = "success";
    $response["message"] = "Updated";
    echoResponse(200, $response);
});

$app->post('/Updatewhatsapp', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $no = $r->whatsappFull;
    if (!isset($_SESSION)) {
        session_start();
    }
    $uid = $_SESSION["uid"];

    $query = "update users set secondary_mobile ='$no' where uid=$uid;";
    $result = $db->updateTableValue($query);
    if ($result) {
        $_SESSION['secondary_number'] = $no;
        $response["status"] = "success";
        $response["message"] = "update successfully.";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to create booth link. Please try again.";
        echoResponse(201, $response);
    }
});

$app->post('/updateChatSetting', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $tawkto = $r->tawkto;
    if (!isset($_SESSION)) {
        session_start();
    }
    $uid = $_SESSION["uid"];

    $query = "update users set tawkto ='$tawkto' where uid=$uid;";
    $result = $db->updateTableValue($query);
    if ($result) {
        $_SESSION['tawkto'] = $tawkto;
        $response["status"] = "success";
        $response["message"] = "Saved successfully.";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to create booth link. Please try again.";
        echoResponse(201, $response);
    }
});

$app->post('/updateLiveMeetingSetting', function () use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    if (isset($r->meetingId) && isset($r->audienceId)) {
        $meetingId = $r->meetingId;
        $audienceId = $r->audienceId;
    } else {
        $meetingId = '';
        $audienceId = '';
    }
    if (!isset($_SESSION)) {
        session_start();
    }
    $uid = $_SESSION["uid"];

    $query = "update users set meetingId ='$meetingId', audienceId= '$audienceId' where uid=$uid;";
    $result = $db->updateTableValue($query);
    if ($result) {
        $response["status"] = "success";
        $response["message"] = "Saved successfully.";
        echoResponse(200, $response);
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to update. Please try again.";
        echoResponse(201, $response);
    }
});

$app->post('/getnextprevbooths', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $r = json_decode($app->request->getBody());
    $id = $r->user_id;
    $count["next_record"] = $db->getOneRecord("SELECT * FROM `users` where userType='Manager' AND uid < '$id' AND organizer='Stona2020' ORDER BY countOfproduct ");
    $count["previos_record"] = $db->getOneRecord("SELECT * FROM `users` where userType='Manager' AND uid > '$id' AND organizer='Stona2020' ORDER BY countOfproduct ");
    $response['metaResult'] = $count;
    $response["status"] = "success";
    echoResponse(200, $response);
});


$app->post('/updateemails', function () use ($app) {
    $response = array();
    $db = new DbHandler();
    $r = json_decode($app->request->getBody());
    $id = $r->data->user_id;
    $email1 = $r->data->email1;
    $email2 = $r->data->email2;
    $email3 = $r->data->email3;

    $query = "update users set email1='$email1',email2='$email2',email3='$email3' where uid='$id'";
    $result = $db->updateTableValue($query);
    $response["status"] = "success";
    $response["message"] = "Updated";
    echoResponse(200, $response);
});



$app->post('/checkboothexits', function () use ($app) {
    $db = new DbHandler();

    $r = json_decode($app->request->getBody());
    $booth = $r->booth_link;
    $response = array();
    $count = array();
    $count = $db->getAllRecord("select count(*) as count FROM `users` where  link ='" . $booth . "'");
    //$res['visit_count']=$count[0]['count'];
    if ($count[0]['count'] == "1") {
        $response['status'] = "success";
    }
    if ($count[0]['count'] == "0") {
        $response['status'] = "false";
    }
    //['status']="Error";
    echoResponse(200, $response);
});

$app->get('/searchSuggestions', function () use ($app) {
    $db = new DbHandler();
    $searchterm = $app->request()->get('searchterm');
    $exhibitionid = $app->request()->get('exhibitionId');

    $query = "";
    if ($exhibitionid == 0) {
        $query = "SELECT u.uid,u.link,u.organizer,product_name as label1, product_decription as label2,id, 'Product Information' as resultFrom  FROM `exhibitionproductdetail` e INNER join users u on e.exhibitorId = u.uid  WHERE 
        u.link is not null and  exhibitorId in (select uid from usermap) and 
         (product_name like '%$searchterm%'
         or product_decription like '%$searchterm%')";
    } else {
        $query = "SELECT u.uid,u.link,u.organizer,product_name as label1, product_decription as label2,id, 'Product Information' as resultFrom  FROM `exhibitionproductdetail` e INNER join users u on e.exhibitorId = u.uid  WHERE 
        u.link is not null and  exhibitorId in (select uid from usermap where exhibitionId =$exhibitionid) and 
         (product_name like '%$searchterm%'
         or product_decription like '%$searchterm%')";
    }

    $result = $db->getAllRecord($query);
    $query = "";
    if ($exhibitionid == 0) {
        $query = "SELECT u.uid,u.name  as label1, u.companyName as label2, 'Company Information' as resultFrom, u.link,u.organizer,u.uid FROM `users` u inner join usermap um on u.uid= um.uid  
                  where u.link is not null and u.name like '%$searchterm%' or u.companyName like '%$searchterm%' ";
    } else {
        $query = "SELECT u.uid,u.name  as label1, u.companyName as label2, 'Company Information' as resultFrom, u.link,u.organizer,u.uid FROM `users` u inner join usermap um on u.uid= um.uid and um.exhibitionId = $exhibitionid
                    where u.link is not null and u.name like '%$searchterm%' or u.companyName like '%$searchterm%' ";
    }
    $resultFromCompany = $db->getAllRecord($query);
    $query = "";
    if ($exhibitionid == 0) {
        $response["AreaOfInterest"] = $db->getAllRecord("SELECT ai.id, ai.name as label1,ac.name  as label2,'Interested Category' as resultFrom FROM areaofinterest ai inner join  areaofinterestsubcategory ac on ai.subCategoryId = ac.id where (ai.name like '%$searchterm%' or ac.name like '%$searchterm%') ");
    } else {
        $response["AreaOfInterest"] = $db->getAllRecord("SELECT ai.id, ai.name as label1,ac.name  as label2,'Interested Category' as resultFrom FROM areaofinterest ai inner join  areaofinterestsubcategory ac on ai.subCategoryId = ac.id where categoryid =$exhibitionid and (ai.name like '%$searchterm%' or ac.name like '%$searchterm%') ");
    }
    $response['productList'] = $result;
    $response['companyResult'] = $resultFromCompany;
    $response['success'] = "success";
    echoResponse(200, $response);
});

$app->post('/updateExhibitorPaymentStatus', function () use ($app) {
    $r = json_decode($app->request->getBody());
    echoResponse(200, updateExhibitorPaymentStatus($r));
});

function updateExhibitorPaymentStatus($obj)
{
    $db = new DbHandler();
    $query = "";

    if ($obj->extendslot == 0) {
        $query = "update users set PaymentStatus='1'  where uid='$obj->uid'";
    } else if ($obj->extendslot == 1) {
        $query = "update users set PaymentStatus='1',PaymentStatus1='1'  where uid='$obj->uid'";
    } else if ($obj->extendslot == 2) {
        $query = "update users set PaymentStatus='1',PaymentStatus1='1', PaymentStatus2='1' where uid='$obj->uid'";
    } else if ($obj->extendslot == 3) {
        $query = "update users set PaymentStatus='1',PaymentStatus1='1', PaymentStatus2='1', PaymentStatus3='1' where uid='$obj->uid'";
    } else if ($obj->extendslot == 4) {
        $query = "update users set PaymentStatus='1',PaymentStatus1='1', PaymentStatus2='1', PaymentStatus3='1',PaymentStatus4='1' where uid='$obj->uid'";
    } else if ($obj->extendslot == 5) {
        $query = "update users set PaymentStatus='1',PaymentStatus1='1', PaymentStatus2='1', PaymentStatus3='1',PaymentStatus4='1',PaymentStatus5='1' where uid='$obj->uid'";
    }
    insertUpdateInBoothPaymentHistoryTable($obj);
    if ($obj->orderstatus == "Success") {
        $db->updateTableValue($query);
        $response["status"] = "success";
        $response["message"] = "Payment successfully completed";
    } else {
        $response["status"] = "error";
        $response["message"] = "Payment failed!";
    }
    return $response;
}

function insertUpdateInBoothPaymentHistoryTable($r)
{
    $db = new DbHandler();
    if (isset($r->id)) {
        $db->dbRowUpdate("booth_payment", $r, "id='$r->id'");
    } else {
        $tabble_name = "booth_payment";
        $column_names = array('uid', 'orderstatus', 'razorpaypaymentid', 'razorpaysignature', 'extendslot', 'amount', 'currency');
        $result = $db->insertIntoTable($r, $column_names, $tabble_name);
    }
}

function lockExhibitorBooth($uid)
{
    $exhibitionInfo = getExhibitionByExhibitorId($uid);
    $validity =  $exhibitionInfo["validity"];
    $totalObj = getTotalExtendedDays(getPackageByUid($uid));
    $startDate =  $exhibitionInfo['startDate'];
    if ($validity != null && $validity != "")
        $validDays = ((int)$validity) + $totalObj->exval1 + $totalObj->exval2 +   $totalObj->exval3 + $totalObj->exval4 + $totalObj->exval5;
    $length = strrpos($startDate, " ");
    $newDate = explode("/", substr($startDate, $length));
    if (count($newDate) >= 3) {
        $date = $newDate[2] . "-" . $newDate[1] . "-" . $newDate[0];
        $Date2 = date('d-m-Y', strtotime($date . " + " . $validDays . " day"));
        $date1 = date_create(date('d-m-Y', time()));
        $date2 = date_create($Date2);
        $diff = date_diff($date1, $date2);
        if ((int)$diff->format("%R%a") < 1) {
            lockUnlockBooth('lock', $uid);
            return true;
        } else {
            lockUnlockBooth('unlock', $uid);
            return false;
        }
    }
    return false;
}

function checkIsPackageValidity($uid)
{
    $exhibitionInfo = getExhibitionByExhibitorId($uid);
    $validity =  $exhibitionInfo["validity"];
    $startDate =  $exhibitionInfo['startDate'];
    if ($validity != null && $validity != "")
        $validDays = ((int)$validity);
    $length = strrpos($startDate, " ");
    $newDate = explode("/", substr($startDate, $length));
    if (count($newDate) >= 3) {
        $date = $newDate[2] . "-" . $newDate[1] . "-" . $newDate[0];
        $Date2 = date('d-m-Y', strtotime($date . " + " . $validDays . " day"));
        $date1 = date_create(date('d-m-Y', time()));
        $date2 = date_create($Date2);
        $diff = date_diff($date1, $date2);
        return (int)$diff->format("%R%a");
    } else {
        return 0;
    }
    return 0;
}
function lockUnlockBooth($status, $uid)
{
    $db = new DbHandler();
    $query = "update users set BoothStatus = '$status' where uid=$uid";
    $db->updateTableValue($query);
}
/**
 * GetExhibition by Exhibitor with additional details like Pacakge information
 * $uid = exhibitor Id 
 */
function getExhibitionByExhibitorId($uid)
{
    $db = new DbHandler();
    return  $db->getOneRecord(" SELECT u.*,e.*, e.id as exhibitionId,p.*
    FROM exhibition e 
        inner join usermap umap on e.id= umap.exhibitionId 
       inner join users u on u.uid = umap.uid 
       inner join exhibitorpackage p on p.organizerId = umap.createdById
       inner join  exhibitorpackagemap epack on epack.packageId = p.id and epack.exhibitorId = umap.uid  where u.uid ='$uid'");
}
function getExhibitionByLink($link)
{
    $db = new DbHandler();
    return  $db->getOneRecord(" SELECT * FROM exhibition where link ='$link'");
}
function checkIspaidExhibitor($uid)
{
    lockExhibitorBooth($uid);
    $db = new DbHandler();
    $validity = checkIsPackageValidity($uid);
    $package = getPackageByUid($uid);
    $isExist = $db->getOneRecord("select 1 from users where uid='$uid' and paymentStatus = 1");
    if ($isExist && $validity >= 0) {
        //First payment has paid and validity of first payment is not exided
        return true;
    }
    if ($validity <= -1) {
        $exValidity = -$validity;
        $exval1 = 0;
        $exval2 = 0;
        $exval3 = 0;
        $exval4 = 0;
        $exval5 = 0;
        if ($package['extendvalidity1'] != 0)
            $exval1 = (int)($package['extendvalidity1']);
        if ($package['extendvalidity2'] != 0)
            $exval2 = (int)($package['extendvalidity1']) + (int)($package['extendvalidity2']);
        if ($package['extendvalidity3'] != 0)
            $exval3 = (int)($package['extendvalidity1']) + (int)($package['extendvalidity2']) + (int)($package['extendvalidity3']);
        if ($package['extendvalidity4'] != 0)
            $exval4 = (int)($package['extendvalidity1']) + (int)($package['extendvalidity2']) + (int)($package['extendvalidity3']) + (int)($package['extendvalidity4']);
        if ($package['extendvalidity5'] != 0)
            $exval5 = (int)($package['extendvalidity1']) + (int)($package['extendvalidity2']) + (int)($package['extendvalidity3']) + (int)($package['extendvalidity4']) + (int)($package['extendvalidity5']);

        if ($exval1 != 0 && $exValidity >= 0 && $exValidity <= $exval1 && $db->getOneRecord("select 1 from users where uid='$uid' and PaymentStatus1 =1 ")) {
            return true;
        } else if ($exval2 != 0 && $exValidity >= $exval1  && $exValidity <= $exval2 && $db->getOneRecord("select 1 from users where uid='$uid' and PaymentStatus2 =1 ")) {
            return true;
        } else if ($exval3 != 0 && $exValidity >= $exval2  && $exValidity <= $exval3 && $db->getOneRecord("select 1 from users where uid='$uid' and PaymentStatus3 =1 ")) {
            return true;
        } else if ($exval4 != 0 && $exValidity >= $exval3  && $exValidity <= $exval4 && $db->getOneRecord("select 1 from users where uid='$uid' and PaymentStatus4 =1 ")) {
            return true;
        } else if ($exval5 != 0 && $exValidity >= $exval4  && $exValidity <= $exval5 && $db->getOneRecord("select 1 from users where uid='$uid' and PaymentStatus5 =1 ")) {
            return true;
        } else {
            return false;
        }
    } else {
        // Validity remain but payment is pending.
        return false;
    }
}

function getPendingPaymentInformation($uid)
{
    $db = new DbHandler();
    $validity = checkIsPackageValidity($uid);
    $package = getPackageByUid($uid);
    $isExist = $db->getOneRecord("select 1 from users where uid='$uid' and paymentStatus = 1");
    $obj = new stdClass();
    if ($isExist && $validity >= 0) {
        //First payment has paid 
        $obj->FirstPaymentStatus = true;
        $obj->FirstPayment = $package['price'];
    } else if ($isExist) {
        $obj->FirstPaymentStatus = true;
        $obj->FirstPayment = $package['price'];
        $obj->Currency = $package['currency'];
        $obj->ExtendDays = 0;
        $obj->ExtendSlot = 0;
    } else {
        $obj->FirstPaymentStatus = false;
        $obj->FirstPayment = $package['price'];
        $obj->Currency = $package['currency'];
        $obj->ExtendDays = 0;
        $obj->ExtendSlot = 0;
    }
    if ($validity <= -1) {
        $exValidity = -$validity;
        $exval1 = 0;
        $exval2 = 0;
        $exval3 = 0;
        $exval4 = 0;
        $exval5 = 0;
        if ($package['extendvalidity1'] != 0)
            $exval1 = (int)($package['extendvalidity1']);
        if ($package['extendvalidity2'] != 0)
            $exval2 = (int)($package['extendvalidity1']) + (int)($package['extendvalidity2']);
        if ($package['extendvalidity3'] != 0)
            $exval3 = (int)($package['extendvalidity1']) + (int)($package['extendvalidity2']) + (int)($package['extendvalidity3']);
        if ($package['extendvalidity4'] != 0)
            $exval4 = (int)($package['extendvalidity1']) + (int)($package['extendvalidity2']) + (int)($package['extendvalidity3']) + (int)($package['extendvalidity4']);
        if ($package['extendvalidity5'] != 0)
            $exval5 = (int)($package['extendvalidity1']) + (int)($package['extendvalidity2']) + (int)($package['extendvalidity3']) + (int)($package['extendvalidity4']) + (int)($package['extendvalidity5']);

        $exprice1 = 0;
        $exprice2 = 0;
        $exprice3 = 0;
        $exprice4 = 0;
        $exprice5 = 0;
        if ($package['extendprice1'] != 0)
            $exprice1 = (int)($package['extendprice1']);
        if ($package['extendprice2'] != 0)
            $exprice2 = (int)($package['extendprice1']) + (int)($package['extendprice2']);
        if ($package['extendprice3'] != 0)
            $exprice3 = (int)($package['extendprice1']) + (int)($package['extendprice2']) + (int)($package['extendprice3']);
        if ($package['extendprice4'] != 0)
            $exprice4 = (int)($package['extendprice1']) + (int)($package['extendprice2']) + (int)($package['extendprice3']) + (int)($package['extendprice4']);
        if ($package['extendprice5'] != 0)
            $exprice5 = (int)($package['extendprice1']) + (int)($package['extendprice2']) + (int)($package['extendprice3']) + (int)($package['extendprice4']) + (int)($package['extendprice5']);

        $obj->Validity = $exValidity;

        //                    11 > 0          && 11<5 
        if ($exval1 != 0 && $exValidity >= 0 && $exValidity <= $exval1) {
            $obj->ExtendValidityPaid = $db->getOneRecord("select 1 from users where uid='$uid' and PaymentStatus1 =1 ") != null ? true : false;
            $obj->ExtendPrice = $exprice1;
            $obj->Currency = $package['currency'];
            $obj->ExtendDays = $exval1;
            $obj->ExtendSlot = 1;
            return $obj;
        }
        //                        11> 5             && 11 < 10   
        if ($exval2 != 0 && $exValidity >= $exval1  && $exValidity <= $exval2) {
            $obj->ExtendPrice = $exprice2;
            $obj->ExtendValidityPaid = $db->getOneRecord("select 1 from users where uid='$uid' and PaymentStatus1 =1 ") != null ? true : false;
            $obj->ExtendDays = $exval2;
            $obj->Currency = $package['currency'];
            $obj->ExtendSlot = 2;
            return $obj;
        } else if ($exval3 != 0 && $exValidity >= $exval2  && $exValidity <= $exval3) {
            $obj->ExtendPrice = $exprice3;
            $obj->ExtendValidityPaid = $db->getOneRecord("select 1 from users where uid='$uid' and PaymentStatus3 =1 ") != null ? true : false;
            $obj->ExtendDays = $exval3;
            $obj->Currency = $package['currency'];
            $obj->ExtendSlot = 3;
            return $obj;
        } else if ($exval4 != 0 && $exValidity >= $exval3  && $exValidity <= $exval4) {
            $obj->ExtendPrice = $exprice4;
            $obj->ExtendValidityPaid = $db->getOneRecord("select 1 from users where uid='$uid' and PaymentStatus4 =1 ") != null ? true : false;
            $obj->Currency = $package['currency'];
            $obj->ExtendDays = $exval4;
            $obj->ExtendSlot = 4;
            return $obj;
        } //25 !=0 &&  11>20
        else if ($exval5 != 0 && $exValidity >= $exval4  && $exValidity <= $exval5) {
            $obj->ExtendPrice = $exprice5;
            $obj->ExtendValidityPaid = $db->getOneRecord("select 1 from users where uid='$uid' and PaymentStatus5 =1 ") != null ? true : false;
            $obj->ExtendDays = $exval5;
            $obj->Currency = $package['currency'];
            $obj->ExtendSlot = 5;
            return $obj;
        } else {
            $obj->Currency = $package['currency'];
            return $obj;
        }
    }
    $obj->Currency = $package['currency'];

    return $obj;
}

function getTotalExtendedDays($package)
{
    $obj = new stdClass();
    $obj->exval1 = 0;
    $obj->exval2 = 0;
    $obj->exval3 = 0;
    $obj->exval4 = 0;
    $obj->exval5 = 0;
    if ($package['extendvalidity1'] != 0)
        $obj->exval1 = (int)($package['extendvalidity1']);
    if ($package['extendvalidity2'] != 0)
        $obj->exval2 = (int)($package['extendvalidity2']);
    if ($package['extendvalidity3'] != 0)
        $obj->exval3 =  (int)($package['extendvalidity3']);
    if ($package['extendvalidity4'] != 0)
        $obj->exval4 =  (int)($package['extendvalidity4']);
    if ($package['extendvalidity5'] != 0)
        $obj->exval5 = (int)($package['extendvalidity5']);

    return $obj;
}

function getTotalExtendedPrice($package)
{
    $obj = new stdClass();
    $obj->exval1 = 0;
    $obj->exval2 = 0;
    $obj->exval3 = 0;
    $obj->exval4 = 0;
    $obj->exval5 = 0;
    if ($package['extendprice1'] != 0)
        $obj->price1 = (int)($package['extendprice1']);
    if ($package['extendprice2'] != 0)
        $obj->price2 = (int)($package['extendprice2']);
    if ($package['extendprice3'] != 0)
        $obj->price3 =  (int)($package['extendprice3']);
    if ($package['extendprice4'] != 0)
        $obj->price4 =  (int)($package['extendprice4']);
    if ($package['extendprice5'] != 0)
        $obj->price5 = (int)($package['extendprice5']);

    return $obj;
}

function decriptDataIfUserIsUnpaid($data)
{
    foreach ($data as $d => $item) {
        if (isset($item['phonenumber'])) {
            $data[$d]['phonenumber'] = encriptMobile($item['phonenumber']);
        }
        if (isset($item['mobile'])) {
            $data[$d]['mobile'] = encriptMobile($item['mobile']);
        }

        if (isset($item['email'])) {
            $data[$d]['email'] = encriptEmail($item['email']);
        }
        if (isset($item['comments'])) {
            $data[$d]['comments'] = encriptComments($item['comments']);
        }
    }
    return $data;
}

function encriptMobile($str)
{
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        if ($i >= 0 && $i < 3) {
            $str[$i] = "*";
        }
        if ($i >= 8 && $i <= 10) {
            $str[$i] = "*";
        }
    }
    return $str;
}

function encriptComments($str)
{
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        if ($i >= 0 && $i < 5) {
            $str[$i] = "*";
        }
        if ($i >= 30 && $i < 40) {
            $str[$i] = "*";
        }
        if ($i >= 0 && $i < 3) {
            $str[$i] = "*";
        }
        if ($i >= 22 && $i <= 26) {
            $str[$i] = "*";
        }
        if ($i >= 100 && $i <= 126) {
            $str[$i] = "*";
        }
    }
    return $str;
}

function encriptEmail($str)
{
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        if ($i >= 0 && $i <= 1) {
            $str[$i] = "*";
        }
        if ($i >= 6 && $i <= 8) {
            $str[$i] = "*";
        }
        if ($i >= 17 && $i <= 18) {
            $str[$i] = "*";
        }
        if ($i >= 30 && $i <= 35) {
            $str[$i] = "*";
        }
    }
    return $str;
}

function CreateAutoLoginUrlForSMS($uid)
{
    $dateStr =  substr(date("Y"), -2) . '%' . date('m') . '%' . date('d');
    return base64_encode($uid . "%" . $dateStr);
}

function SaveLastvisitedBooth($browserId, $boothId, $exhibitionId)
{
    $db = new DbHandler();
    $tabble_name = "lastvisitoronthebooth";
    $r = new stdClass();
    $r->browserId = $browserId;
    $r->boothId = $boothId;
    $r->exhibitionId = $exhibitionId;
    $column_names = array('browserId', 'boothId', 'exhibitionId');
    $result = $db->insertIntoTable($r, $column_names, $tabble_name);
    if ($result != NULL) {
        return true;
    }
    return false;
}
