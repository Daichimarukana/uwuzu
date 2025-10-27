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
                'success' => false
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    if($token == ""){
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
            'success' => false
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
            'success' => false
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
        $AuthData = APIAuth($pdo, $token, "read:ueuse");
        if($AuthData[0] === true){
            $userData = $AuthData[2];
            $sql = "SELECT * FROM ueuse WHERE uniqid = :ueuseid OR rpuniqid = :ueuseid ORDER BY datetime ASC LIMIT :offset, :itemsPerPage";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':ueuseid', $ueuseid, PDO::PARAM_STR);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':itemsPerPage', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $message_array = $stmt;
        
            while ($row = $message_array->fetch(PDO::FETCH_ASSOC)) {
                $messages[] = $row;
            }
        
            if (!empty($messages)) {
                $response = array(
                    'success' => true,
                ); // ループ外で $response を初期化
            
                foreach ($messages as $ueusedata) {
                    if(!(empty($ueusedata["favorite"]))){
                        $favorite = preg_split("/,/", decode_yajirushi(htmlspecialchars_decode($ueusedata["favorite"])));
                        array_shift($favorite);
                    }else{
                        $favorite = array();
                    }
                    $favcnts = explode(',', $ueusedata["favorite"]);
                    $ueusedata["favorite_cnt"] = count($favcnts) - 1;

                    $userData = getUserData($pdo, $ueusedata["account"]);
            
                    if ($userData) {
                        $now_userdata = array(
                            "username" => decode_yajirushi(htmlspecialchars_decode($userData['username'])),
                            "userid" => decode_yajirushi(htmlspecialchars_decode($userData['userid'])),
                            "user_icon" => decode_yajirushi(htmlspecialchars_decode(localcloudURLtoAPI(localcloudURL($userData['iconname'])))),
                            "user_header" => decode_yajirushi(htmlspecialchars_decode(localcloudURLtoAPI(localcloudURL($userData['headname'])))),
                            "is_bot" => $userData['sacinfo'] == 'bot' ? true : false,
                        );
                    }

                    if($ueusedata["nsfw"] == "true"){
                        $nsfw = true;
                    }else{
                        $nsfw = false;
                    }

                    if($ueusedata["abi"] == "none"){
                        $ueusedata["abi"] = "";
                    }

                    //リプライ数取得
                    $rpQuery = $pdo->prepare("SELECT COUNT(*) as reply_count FROM ueuse WHERE rpuniqid = :rpuniqid");
                    $rpQuery->bindValue(':rpuniqid', $ueusedata['uniqid']);
                    $rpQuery->execute();
                    $rpData = $rpQuery->fetch(PDO::FETCH_ASSOC);
                    
                    if ($rpData){
                        $ueusedata['reply_count'] = $rpData['reply_count'];
                    }

                    //リユーズ数取得
                    $ruQuery = $pdo->prepare("SELECT COUNT(*) as reuse_count FROM ueuse WHERE ruuniqid = :ruuniqid");
                    $ruQuery->bindValue(':ruuniqid', $ueusedata['uniqid']);
                    $ruQuery->execute();
                    $ruData = $ruQuery->fetch(PDO::FETCH_ASSOC);
                    
                    if ($ruData){
                        $ueusedata['reuse_count'] = $ruData['reuse_count'];
                    }
            
                    $item = [
                        'uniqid' => decode_yajirushi(htmlspecialchars_decode($ueusedata["uniqid"])),
                        'replyid' => decode_yajirushi(htmlspecialchars_decode($ueusedata["rpuniqid"])),
                        'reuseid' => decode_yajirushi(htmlspecialchars_decode($ueusedata["ruuniqid"])),
                        'text' => decode_yajirushi(htmlspecialchars_decode($ueusedata["ueuse"])),
                        'account' => $now_userdata,
                        'photo1' => decode_yajirushi(htmlspecialchars_decode(localcloudURLtoAPI(localcloudURL($ueusedata["photo1"])))),
                        'photo2' => decode_yajirushi(htmlspecialchars_decode(localcloudURLtoAPI(localcloudURL($ueusedata["photo2"])))),
                        'photo3' => decode_yajirushi(htmlspecialchars_decode(localcloudURLtoAPI(localcloudURL($ueusedata["photo3"])))),
                        'photo4' => decode_yajirushi(htmlspecialchars_decode(localcloudURLtoAPI(localcloudURL($ueusedata["photo4"])))),
                        'video1' => decode_yajirushi(htmlspecialchars_decode(localcloudURLtoAPI(localcloudURL($ueusedata["video1"])))),
                        'favorite' => $favorite,
                        'favorite_cnt' => $ueusedata["favorite_cnt"],
                        'reply_cnt' => $ueusedata["reply_count"],
                        'reuse_cnt' => $ueusedata["reuse_count"],
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
                    'success' => false
                );
            
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
        }else{
            $err = $AuthData[1];
            $response = array(
                'error_code' => $err,
                'success' => false
            );
            
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