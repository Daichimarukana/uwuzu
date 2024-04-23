<?php

$domain = $_SERVER['HTTP_HOST'];
require('../../db.php');

header("Content-Type: application/json");
header("charset=utf-8");
header("Access-Control-Allow-Origin: *");

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}
function decode_yajirushi($postText){
    $postText = str_replace('&larr;', '←', $postText);
    $postText = str_replace('&darr;', '↓', $postText);
    $postText = str_replace('&uarr;', '↑', $postText);
    $postText = str_replace('&rarr;', '→', $postText);
    return $postText;
}
function get_mentions_userid($postText) {
    // @useridを検出する
    $usernamePattern = '/@(\w+)/';
    $mentionedUsers = [];

    preg_replace_callback($usernamePattern, function($matches) use (&$mentionedUsers) {
        $mention_username = $matches[1];

        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));
    
        $mention_userQuery = $dbh->prepare("SELECT username, userid FROM account WHERE userid = :userid");
        $mention_userQuery->bindValue(':userid', $mention_username);
        $mention_userQuery->execute();
        $mention_userData = $mention_userQuery->fetch();   
        
        if (!empty($mention_userData)) {
            $mentionedUsers[] = $mention_username;
        }
    }, $postText);

    return $mentionedUsers;
}

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
        $token = htmlentities($_GET['token'], ENT_QUOTES, 'UTF-8', false);
    }else{
        $post_json = json_decode($Get_Post_Json, true);
        if(isset($post_json["token"])){
            $token = htmlentities($post_json["token"], ENT_QUOTES, 'UTF-8', false);
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
        $userQuery = $pdo->prepare("SELECT username, userid, role FROM account WHERE token = :token");
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
            $query = $pdo->prepare('SELECT * FROM ueuse WHERE uniqid = :uniqid limit 1');
        
            $query->execute(array(':uniqid' => $ueuseid));
        
            $result = $query->fetch();
        
            if(!(empty($result))){
                if($result["account"] === $userData["userid"]){
        
                    $Userid = $userData["userid"];
                    $photo_query = $pdo->prepare("SELECT * FROM ueuse WHERE account = :userid AND uniqid = :uniqid");
                    $photo_query->bindValue(':userid', $Userid);
                    $photo_query->bindValue(':uniqid', $ueuseid);
                    $photo_query->execute();
                    $photo_and_video = $photo_query->fetch();
                    
                    if(!($photo_and_video["photo1"] == "none")){
                        $photoDelete1 = glob("../".$photo_and_video["photo1"]); // 「-ユーザーID.拡張子」というパターンを検索
                        foreach ($photoDelete1 as $photo1) {
                            if (is_file($photo1)) {
                                unlink($photo1);
                            }
                        }
                    }
                    if(!($photo_and_video["photo2"] == "none")){
                        $photoDelete2 = glob("../".$photo_and_video["photo2"]); // 「-ユーザーID.拡張子」というパターンを検索
                        foreach ($photoDelete2 as $photo2) {
                            if (is_file($photo2)) {
                                unlink($photo2);
                            }
                        }
                    }
                    if(!($photo_and_video["photo3"] == "none")){
                        $photoDelete3 = glob("../".$photo_and_video["photo3"]); // 「-ユーザーID.拡張子」というパターンを検索
                        foreach ($photoDelete3 as $photo3) {
                            if (is_file($photo3)) {
                                unlink($photo3);
                            }
                        }
                    }
                    if(!($photo_and_video["photo4"] == "none")){
                        $photoDelete4 = glob("../".$photo_and_video["photo4"]); // 「-ユーザーID.拡張子」というパターンを検索
                        foreach ($photoDelete4 as $photo4) {
                            if (is_file($photo4)) {
                                unlink($photo4);
                            }
                        }
                    }
                    if(!($photo_and_video["video1"] == "none")){
                        $videoDelete1 = glob("../".$photo_and_video["video1"]); // 「-ユーザーID.拡張子」というパターンを検索
                        foreach ($videoDelete1 as $video1) {
                            if (is_file($video1)) {
                                unlink($video1);
                            }
                        }
                    }
                    
        
                    try {
                        $deleteQuery = $pdo->prepare("DELETE FROM ueuse WHERE uniqid = :uniqid AND account = :userid");
                        $deleteQuery->bindValue(':uniqid', $ueuseid, PDO::PARAM_STR);
                        $deleteQuery->bindValue(':userid', $Userid, PDO::PARAM_STR);
                        $res = $deleteQuery->execute();
        
                        if ($res) {
                            $response = array(
                                'uniqid' => decode_yajirushi(htmlspecialchars_decode($ueuseid)),
                                'userid' => decode_yajirushi(htmlspecialchars_decode($userData["userid"])),
                                'success' => true
                            );
                            
                            echo json_encode($response, JSON_UNESCAPED_UNICODE);
                            exit;
                        } else {
                            $response = array(
                                'uniqid' => decode_yajirushi(htmlspecialchars_decode($ueuseid)),
                                'userid' => decode_yajirushi(htmlspecialchars_decode($Userid)),
                                'success' => false
                            );
                            
                            echo json_encode($response, JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                    } catch(PDOException $e) {
                        $response = array(
                            'uniqid' => decode_yajirushi(htmlspecialchars_decode($ueuseid)),
                            'userid' => decode_yajirushi(htmlspecialchars_decode($userData["userid"])),
                            'success' => false
                        );
                        
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                }

            } else {
                $err = "ueuse_not_found";
                $response = array(
                    'error_code' => $err,
                );
            
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
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