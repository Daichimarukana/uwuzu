<?php

$domain = $_SERVER['HTTP_HOST'];
require('../../db.php');
require("../../function/function.php");

header("Content-Type: application/json");
header("charset=utf-8");
header("Access-Control-Allow-Origin: *");

function decode_yajirushi($postText){
    $postText = str_replace('&larr;', '←', $postText);
    $postText = str_replace('&darr;', '↓', $postText);
    $postText = str_replace('&uarr;', '↑', $postText);
    $postText = str_replace('&rarr;', '→', $postText);
    return $postText;
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

    if(!(empty($_GET['limit']))){
        $limit = (int)$_GET['limit'];
    }elseif(!(empty($post_json["limit"]))){
        $limit = (int)$post_json["limit"];
    }else{
        $limit = 25;
    }
    if($limit > 100){
        $limit = 100;
    }

    if(!(empty($_GET['page']))){
        $page = (int)$_GET['page'];
    }elseif(!(empty($post_json["page"]))){
        $page = (int)$post_json["page"];
    }else{
        $page = 1;
    }
    $offset = ($page - 1) * $limit;
    
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
            $sql = "SELECT * FROM ueuse WHERE uniqid = :ueuseid OR rpuniqid = :ueuseid ORDER BY datetime ASC LIMIT :offset, :itemsPerPage";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':ueuseid', $ueuseid, PDO::PARAM_STR);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':itemsPerPage', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $message_array = $stmt;
        
            while ($row = $message_array->fetchAll(PDO::FETCH_ASSOC)) {
        
                $messages[] = $row;
            }
        
            if (!empty($messages)) {
                $response = array(); // ループ外で $response を初期化
            
                foreach ($messages as $ueusedata) {
                    if(!(empty($ueusedata["favorite"]))){
                        $favorite = preg_split("/,/", decode_yajirushi(htmlspecialchars_decode($ueusedata["favorite"])));
                        array_shift($favorite);
                    }else{
                        $favorite = array();
                    }
                    $favcnts = explode(',', $ueusedata["favorite"]);
                    $ueusedata["favorite_cnt"] = count($favcnts) - 1;

                    $userQuery = $pdo->prepare("SELECT username, userid, iconname, headname, role FROM account WHERE userid = :userid");
                    $userQuery->bindValue(':userid', $ueusedata["account"]);
                    $userQuery->execute();
                    $userData = $userQuery->fetch();
            
                    if ($userData) {
                        $now_userdata = array(
                            "username" => decode_yajirushi(htmlspecialchars_decode($userData['username'])),
                            "userid" => decode_yajirushi(htmlspecialchars_decode($userData['userid'])),
                            "user_icon" => decode_yajirushi(htmlspecialchars_decode("https://".$domain."/".$userData['iconname'])),
                            "user_head" => decode_yajirushi(htmlspecialchars_decode("https://".$domain."/".$userData['headname'])),
                        );
                    }

                    if($ueusedata["nsfw"] == "true"){
                        $nsfw = true;
                    }else{
                        $nsfw = false;
                    }
            
                    $item = [
                        'uniqid' => decode_yajirushi(htmlspecialchars_decode($ueusedata["uniqid"])),
                        'replyid' => decode_yajirushi(htmlspecialchars_decode($ueusedata["rpuniqid"])),
                        'text' => decode_yajirushi(htmlspecialchars_decode($ueusedata["ueuse"])),
                        'account' => $now_userdata,
                        'photo1' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo1"]))),
                        'photo2' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo2"]))),
                        'photo3' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo3"]))),
                        'photo4' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo4"]))),
                        'video1' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["video1"]))),
                        'favorite' => $favorite,
                        'favorite_cnt' => decode_yajirushi(htmlspecialchars_decode($ueusedata["favorite_cnt"])),
                        'datetime' => decode_yajirushi(htmlspecialchars_decode($ueusedata["datetime"])),
                        'abi' => decode_yajirushi(htmlspecialchars_decode($ueusedata["abi"])),
                        'abidatetime' => decode_yajirushi(htmlspecialchars_decode($ueusedata["abidate"])),
                        'nsfw' => $nsfw,
                    ];
            
                    $response[] = $item; // ループ内で $response にデータを追加
                }
            
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                $err = "ueuse_not_found";
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