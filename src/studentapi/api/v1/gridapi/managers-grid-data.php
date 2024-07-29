<?php
   include_once '../../config.php';
   // Connecting to mysql database
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
/* Database connection end */


// storing  request (ie, get/post) global array to a variable  
$requestData= $_REQUEST;


$columns = array( 
// datatable column index  => database column name
	0 =>'Email', 
	1 => 'Name',
	2=> 'Mobile',
	3=> 'Address',
	4=> 'City'
);

if (!isset($_SESSION)) {
	session_start();
} 

// getting total number records without any search
$sql = "SELECT Email, Name, Mobile,Address,City, CompanyName ";
$sql.=" FROM users";

$query=mysqli_query($conn, $sql) or die("");
$totalData = mysqli_num_rows($query);
$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.


$sql = "SELECT Email, Name, Mobile,Address,City,CompanyName ";

if($_SESSION['userType']=="Manager")
    $sql.=" FROM users WHERE UserType<>'Admin' and Uid=".$_SESSION['uid']." and 1=1 ";
else
    $sql.=" FROM users WHERE UserType<>'Admin' and 1=1 ";

if( !empty($requestData['search']['value']) ) {   // if there is a search parameter, $requestData['search']['value'] contains search parameter
	$sql.=" AND ( Name LIKE '".$requestData['search']['value']."%' ";    
	$sql.=" OR Mobile LIKE '".$requestData['search']['value']."%' ";
	$sql.=" OR Email LIKE '".$requestData['search']['value']."%' )";
	$sql.=" OR Address LIKE '".$requestData['search']['value']."%' )";
	$sql.=" OR City LIKE '".$requestData['search']['value']."%' )";
}
$query=mysqli_query($conn, $sql) or die("employee-grid-data.php: get employees");
$totalFiltered = mysqli_num_rows($query); // when there is a search parameter then we have to modify total number filtered rows as per search result. 
//$sql.=" ORDER BY ". $columns[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."  LIMIT ".$requestData['start']." ,".$requestData['length']."   ";
/* $requestData['order'][0]['column'] contains colmun index, $requestData['order'][0]['dir'] contains order such as asc/desc  */	
$query=mysqli_query($conn, $sql) or die("No Order by ");

$data = array();
while( $row=mysqli_fetch_array($query) ) {  // preparing an array
	$data[] = $row;
}

$json_data = array(
			"draw"            => intval( $requestData['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
			"recordsTotal"    => intval( $totalData ),  // total number of records
			"recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
			"data"            => $data   // total data array
			);
echo json_encode($json_data);  // send data as json format
?>
