<?php

class DbHandler
{

    private $conn;

    function __construct()
    {
        require_once 'dbConnect.php';
        // opening db connection
        $db = new dbConnect();
        $this->conn = $db->connect();
    }
    /**
     * Fetching single record
     */
    public function getOneRecord($query)
    {
        $r = $this->conn->query($query . ' LIMIT 1') or die($this->conn->error . __LINE__);
        return $result = $r->fetch_assoc();
    }
    /**
     * Creating new record
     */
    public function insertIntoTable($obj, $column_names, $table_name)
    {
        $c = (array) $obj;
        $keys = array_keys($c);
        $columns = '';
        $values = '';
        foreach ($column_names as $desired_key) { // Check the obj received. If blank insert blank into the array.
            if (!in_array($desired_key, $keys)) {
                $$desired_key = '';
            } else {
                $$desired_key = $c[$desired_key];
            }
            $columns = $columns . $desired_key . ',';
            $values = $values . "'" . mysqli_real_escape_string($this->conn, $$desired_key) . "',";
        }
        $query = "INSERT INTO " . $table_name . "(" . trim($columns, ',') . ") VALUES(" . trim($values, ',') . ")";
        $r = $this->conn->query($query) or die($this->conn->error . __LINE__);

        if ($r) {
            $new_row_id = $this->conn->insert_id;
            return $new_row_id;
        } else {
            return NULL;
        }
    }
    public function getSession()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        $sess = array();
        if (isset($_SESSION['uid'])) {
            $sess["uid"] = $_SESSION['uid'];
            $sess["name"] = $_SESSION['name'];
            $sess["email"] = $_SESSION['email'];
            $sess["mobile"] = $_SESSION['mobile'];
            $sess["organizer"] = $_SESSION['organizer'];
            $sess["userType"] = $_SESSION['userType'];
            $sess["companyName"] = $_SESSION['companyName'];
            $sess["tableName"] = $_SESSION['tableName'];
            $sess['orderId'] = $_SESSION['orderId'];
            $sess['link'] = $_SESSION['link'];
            $sess['countOfproduct'] = $_SESSION['countOfproduct'];
            $sess['packageName'] = $_SESSION['packageName'];
            $sess['interestArea'] = $_SESSION['interestArea'];
        } else {
            $sess["uid"] = '';
            $sess["name"] = 'Guest';
            $sess["email"] = '';
            $sess["userType"] = '';
            $sess["mobile"] = '';
            $sess["companyName"] = '';
            $sess["tableName"] = '';
            $sess["orderId"] = '';
            $sess["link"] = '';
            $sess["countOfproduct"] = '';
            $sess["packageName"] = '';
            $sess['organizer'] = '';
            $sess['interestArea'] = '';
        }
        return $sess;
    }
    public function destroySession()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        if (isset($_SESSION['uid'])) {
            if ($_SESSION['userType'] == "Visitor") {
                $this->updateTableValue("update `boothvisitshistory` set end_at =now() where user_id =" . $_SESSION['uid']);
            }
            unset($_SESSION['uid']);
            unset($_SESSION['name']);
            unset($_SESSION['email']);
            unset($_SESSION['userType']);
            unset($_SESSION['companyName']);
            unset($_SESSION['mobile']);
            unset($_SESSION['tableName']);
            unset($_SESSION['orderId']);
            unset($_SESSION['packageName']);
            unset($_SESSION['countOfproduct']);
            unset($_SESSION['link']);
            unset($_SESSION['organizer']);
            unset($_SESSION['interestArea']);
            $info = 'info';
            if (isset($_COOKIE[$info])) {
                setcookie($info, '', time() + (1), "/");
            }
            $msg = "Logged Out Successfully...";
        } else {
            $msg = "Not logged in...";
        }
        return $msg;
    }

    /**
     * Update  record 
     */
    // again where clause is left optional
    public function dbRowUpdate($table, $data, $where)
    {
        $cols = array();
        foreach ($data as $key => $val) {
            $val = mysqli_real_escape_string($this->conn, $val);

            if ($key == 'companyDescription') {
                $cols[] = 'companyDescription="' . $val . '"';
            } else
                $cols[] = $key . '="' . $val . '"';
        }
        $sql = "UPDATE $table SET " . implode(', ', $cols) . " WHERE $where";
        $r = $this->conn->query($sql) or die($this->conn->error . __LINE__);
        if ($r) {
            return true;
        } else {
            return false;
        }
    }

    public function updateTableValue($query)
    {
        $r = $this->conn->query($query) or die($this->conn->error . __LINE__);
        if ($r) {
            return true;
        } else {
            return false;
        }
    }

    public function getAllRecord($query)
    {
             $r = $this->conn->query($query) or die($this->conn->error . __LINE__);

            $data = array();
            while ($row = $r->fetch_assoc()) {
                $data[] = $row;
            }
            return $data;
    }


    public function deleteRecord($query)
    {
        $r = $this->conn->query($query) or die($this->conn->error . __LINE__);
        return $r;
    }
    public function getAllRecordByProperty($query)
    {
        $r = $this->conn->query($query) or die($this->conn->error . __LINE__);
        $data = array();
        while ($row = $r->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    function getGridRecords($params)
    {
        $rp = isset($params['rowCount']) ? $params['rowCount'] : 10;

        if (isset($params['current'])) {
            $page  = $params['current'];
        } else {
            $page = 1;
        };
        $start_from = ($page - 1) * $rp;

        $sql = $sqlRec = $sqlTot = $where = '';

        if (!empty($params['searchPhrase'])) {
            $where .= " WHERE ";
            $where .= " ( Name LIKE '" . $params['searchPhrase'] . "%' ";
            $where .= " OR email LIKE '" . $params['searchPhrase'] . "%' ";

            $where .= " OR mobile LIKE '" . $params['searchPhrase'] . "%' )";
        }
        if (!empty($params['sort'])) {
            $where .= " ORDER By " . key($params['sort']) . ' ' . current($params['sort']) . " ";
        }
        // getting total number records without any search
        $sql = "SELECT * FROM `users` ";
        $sqlTot .= $sql;
        $sqlRec .= $sql;

        //concatenate search sql if value exist
        if (isset($where) && $where != '') {

            $sqlTot .= $where;
            $sqlRec .= $where;
        }
        if ($rp != -1)
            $sqlRec .= " LIMIT " . $start_from . "," . $rp;


        $qtot = mysqli_query($this->conn, $sqlTot) or die("error to fetch tot employees data");
        $queryRecords = mysqli_query($this->conn, $sqlRec) or die("error to fetch employees data");

        while ($row = mysqli_fetch_assoc($queryRecords)) {
            $data[] = $row;
        }

        $json_data = array(
            "current"            => intval($params['current']),
            "rowCount"            => 10,
            "total"    => intval($qtot->num_rows),
            "rows"            => $data   // total data array
        );

        return $json_data;
    }


    function subscribe($email, $fname, $lname, $apiKey, $listID)
    {
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            // MailChimp API credentials
            // $apiKey = 'e51dbda9eb2a7aa8aa08e66b8b1d34cf-us11';
            // $listID = '2fa4f1b7e1';

            // MailChimp API URL
            $memberID = md5(strtolower($email));
            $dataCenter = substr($apiKey, strpos($apiKey, '-') + 1);
            $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/members/' . $memberID;

            // member information
            $json = json_encode([
                'email_address' => $email,
                'status'        => 'subscribed',
                'merge_fields'  => [
                    'FNAME'     => $fname,
                    'LNAME'     => $lname
                ]
            ]);

            // send a HTTP POST request with curl
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // store the status message based on response code
            if ($httpCode == 200) {
                return "You have successfully subscribed.";
            } else {
                switch ($httpCode) {
                    case 214:
                        return 'You are already subscribed.';
                        break;
                    default:
                        return 'Some problem occurred, please try again.';
                        break;
                }
            }
        } else {
            return 'Please enter valid email address';
        }
    }

    public function activity($uid, $textactivity, $date_at)
    {
        $activity = $this->getOneRecord("select * from user_activity where user_id='$uid' AND date_at='$date_at'");
        $r1 = array();
        $r1["user_id"] = $uid;
        $r1["date_at"] = $date_at;
        $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        if ($activity["activity"] == NULL) {
            $r1["activity"] = $date->format('H:i:s') . " : " . $textactivity;
            $tabble_name1 = "user_activity";
            $column_names1 = array('user_id', 'activity', 'date_at');
            $result1 = $this->insertIntoTable($r1, $column_names1, $tabble_name1);
        } else {
            $act = $activity["activity"] . "," . $date->format('H:i:s') . " : " . $textactivity;
            $query1 = "update user_activity set activity = '$act', date_at = '$date_at' where user_id='$uid';";
            $res = $this->updateTableValue($query1);
        }
    }
    public function booth_activity($uid, $textactivity, $date_at, $booth)
    {
        $activity = $this->getOneRecord("select * from booth_activity where user_id='$uid' AND date_at='$date_at' AND booth_url='$booth'");
        $r1 = array();
        $r1["user_id"] = $uid;
        $r1["date_at"] = $date_at;
        $r1["booth_url"] = $booth;
        $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        if ($activity["activity"] == NULL) {
            $r1["activity"] = $date->format('H:i:s') . " : " . $textactivity;
            $tabble_name1 = "booth_activity";
            $column_names1 = array('user_id', 'activity', 'date_at', 'booth_url');
            $result1 = $this->insertIntoTable($r1, $column_names1, $tabble_name1);
        } else {
            $act = $activity["activity"] . "," . $date->format('H:i:s') . " : " . $textactivity;
            $query1 = "update user_activity set activity = '$act', date_at = '$date_at' where user_id='$uid';";
            $res = $this->updateTableValue($query1);
        }
    }
}
