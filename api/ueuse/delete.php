<?php

$domain = $_SERVER['HTTP_HOST'];
require('../../db.php');
require("../../function/function.php");
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
if(isset($_GET['token']) || (!(empty($Get_Post_Json)))) { 
    //トークン取得
    if(!(empty($_GET['token']))){
        $token = safetext($_GET['token']);
    }else{
        $post_json = json_decode($Get_Post_Json, true);
        if(isset($post_json["token"])){
            $token = safetext($post_json["token"]);
        }else{
            $err = "input_not_found";
            $response = array(
                'error_code' => $err,
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    if($token == ""){
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if(!(empty($_GET['uniqid']))){
        $ueuseid = $_GET['uniqid'];
    }elseif(!(empty($post_json["uniqid"]))){
        $ueuseid = $post_json["uniqid"];
    }else{
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
        );
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    session_start();

    if( !empty($pdo) ) {
        $userQuery = $pdo->prepare("SELECT username, userid, role, loginid FROM account WHERE token = :token");
        $userQuery->bindValue(':token', $token);
        $userQuery->execute();
        $userData = $userQuery->fetch();

        if(empty($userData["userid"])){
            $err = "token_invalid";
            $response = array(
                'error_code' => $err,
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }elseif($userData["role"] === "ice"){
            $err = "this_account_has_been_frozen";
            $response = array(
                'error_code' => $err,
            );
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }else{
            if (safetext(isset($ueuseid)) && safetext(isset($userData["userid"])) && safetext(isset($userData["loginid"]))){
                $postUserid = safetext($userData["userid"]);
                $postUniqid = safetext($ueuseid);
                $loginid = safetext($userData["loginid"]);
            
                $result = delete_ueuse($postUniqid, $postUserid, $loginid);
                if($result[0] === true){
                    $response = array(
                        'uniqid' => decode_yajirushi(htmlspecialchars_decode($ueuseid)),
                        'userid' => decode_yajirushi(htmlspecialchars_decode($userData["userid"])),
                        'success' => true
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }else{
                    $response = array(
                        'uniqid' => decode_yajirushi(htmlspecialchars_decode($ueuseid)),
                        'userid' => decode_yajirushi(htmlspecialchars_decode($userData["userid"])),
                        'success' => false
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }else{
                $err = "input_not_found";
                $response = array(
                    'error_code' => $err,
                );
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
        }
    }
}else{
    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
    );
     
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>