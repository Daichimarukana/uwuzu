<?php

$domain = $_SERVER['HTTP_HOST'];
require(__DIR__ . '/../../db.php');
require(__DIR__ . "/../../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");



$pdo = null;
try {
    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
} catch(PDOException $e) {
    // 接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}

$Get_Post_Json = file_get_contents("php://input");
if(isset($_GET['session']) || (!(empty($Get_Post_Json)))) { 
    //トークン取得
    if(!(empty($_GET['session']))){
        $session_id = safetext($_GET['session']);
    }else{
        $post_json = json_decode($Get_Post_Json, true);
        if(isset($post_json["session"])){
            $session_id = safetext($post_json["session"]);
        }else{
            $err = "input_not_found";
            $response = array(
                'error_code' => $err,
                'success' => false
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    if($session_id == ""){
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
            'success' => false
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    session_start();

    if( !empty($pdo) ) {
        $tokenQuery = $pdo->prepare("SELECT userid, token FROM api WHERE sessionid = :sessionid");
        $tokenQuery->bindValue(':sessionid', $session_id);
        $tokenQuery->execute();
        $tokenData = $tokenQuery->fetch();

        if(empty($tokenData["userid"])){
            $err = "session_invalid";
            $response = array(
                'error_code' => $err,
                'success' => false
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }else{
            $userdata = getUserData($pdo, $tokenData["userid"]);
            
            if (empty($userdata)){
                $response = array(
                    'error_code' => "critical_error_userdata_not_found",
                    'success' => false
                );
            }else{
                DelSessionidAPIToken($pdo, $session_id);

                $response = array(
                    'success' => true,
                    'username' => decode_yajirushi(htmlspecialchars_decode($userdata["username"])),
                    'userid' => decode_yajirushi(htmlspecialchars_decode($userdata["userid"])),
                    'token' => decode_yajirushi(htmlspecialchars_decode($tokenData["token"]))
                );
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
    }
}else{
    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
        'success' => false
    );
     
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>