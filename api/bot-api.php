<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

$err = "API_has_been_deleted";
$response = array(
    'error_code' => $err,
);
    
echo json_encode($response, JSON_UNESCAPED_UNICODE);

?>