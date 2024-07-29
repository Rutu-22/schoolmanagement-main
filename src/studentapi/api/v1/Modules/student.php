<?php

$app->post('/addStudent', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = addStudent($r);
    echoResponse(200, $response);
});

function addStudent($request)
{
    $db = new DbHandler();
    $table_name = "stu_reg";
    $column_names = array(
        'generalRegisterNumber', 'studentId', 'adharCard', 'fullName', 'motherName',
        'nationality', 'mobileNo', 'inputEmailAddress', 'dateOfBirth', 'placeOfBirth',
        'Gender', 'previousSchool', 'reasonForLeaving', 'leftStandard', 'admissionDate',
        'academicYear', 'classOfAdmission', 'division', 'cast', 'religion'
    );
    $id = $db->insertIntoTable($request, $column_names, $table_name);
    return $id;
}

$app->post('/updateStudent', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = updateStudent($r);
    echoResponse(200, $response);
});

function updateStudent($request)
{
    $db = new DbHandler();
    if ($db->dbRowUpdate("stu_reg", $request, "studentId=$request->studentId")) {
        $response["status"] = "success";
        $response["message"] = "Student updated successfully";
        $response["studentId"] = $request->studentId;
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to update student. Please try again";
    }
    return $response;
}

$app->post('/deleteStudent', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $response = deleteStudent($r->studentId);
    echoResponse(200, $response);
});

function deleteStudent($studentId)
{
    $db = new DbHandler();
    $table_name = "stu_reg";
    $condition = "studentId=$studentId";
    
    $query = "DELETE FROM $table_name WHERE $condition";
    
    if ($db->deleteRecord($query)) {
        $response["status"] = "success";
        $response["message"] = "Student deleted successfully";
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to delete student. Please try again";
    }
    return $response;
}

$app->get('/getStudents', function () use ($app) {
    $response = array();
    $students = getStudents();
    
    $response['students'] = $students;
    $response['status'] = 'success';
    echoResponse(200, $response);
});

function getStudents()
{
    $db = new DbHandler();
    $sql = "SELECT * FROM stu_reg";
    return $db->getAllRecord($sql);
}
?>



