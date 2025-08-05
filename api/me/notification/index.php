<?php

$domain = $_SERVER['HTTP_HOST'];
require(__DIR__ . '/../../../db.php');
require(__DIR__ . "/../../../function/function.php");
$serversettings_file = __DIR__ . "/../../../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);
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
        $AuthData = APIAuth($pdo, $token, "read:notifications");
        if($AuthData[0] === true){
            $userData = $AuthData[2];

            $messageQuery = $pdo->prepare("SELECT fromuserid,title,msg,url,datetime,userchk,category FROM notification WHERE touserid = :userid ORDER BY datetime DESC LIMIT :offset, :itemsPerPage");
            $messageQuery->bindValue(':userid', $userData["userid"], PDO::PARAM_STR);
            $messageQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
            $messageQuery->bindValue(':itemsPerPage', $limit, PDO::PARAM_INT);
            $messageQuery->execute();

            // トランザクション開始
            $pdo->beginTransaction();
        
            while ($row = $messageQuery->fetch(PDO::FETCH_ASSOC)) {
                $messages[] = $row;
            }
        
            if (!empty($messages)) {
                $response = array(
                    'success' => true,
                ); // ループ外で $response を初期化
            
                foreach ($messages as $notificationdata) {
                    $userQuery = $pdo->prepare("SELECT username, userid, iconname, headname, role FROM account WHERE userid = :userid");
                    $userQuery->bindValue(':userid', $notificationdata["fromuserid"]);
                    $userQuery->execute();
                    $userData = $userQuery->fetch();
            
                    if ($userData) {
                        $now_userdata = array(
                            "username" => decode_yajirushi(htmlspecialchars_decode($userData['username'])),
                            "userid" => decode_yajirushi(htmlspecialchars_decode($userData['userid'])),
                            "user_icon" => decode_yajirushi(htmlspecialchars_decode(localcloudURLtoAPI(localcloudURL($userData['iconname'])))),
                            "user_header" => decode_yajirushi(htmlspecialchars_decode(localcloudURLtoAPI(localcloudURL($userData['headname'])))),
                        );
                    }else if($notificationdata["fromuserid"] === "uwuzu-fromsys"){
                        $now_userdata = array(
                            "username" => decode_yajirushi(htmlspecialchars_decode($serversettings["serverinfo"]["server_name"])),
                            "userid" => "uwuzu-fromsys",
                            "user_icon" => decode_yajirushi(htmlspecialchars_decode(localcloudURLtoAPI(localcloudURL($serversettings["serverinfo"]["server_icon"])))),
                            "user_header" => decode_yajirushi(htmlspecialchars_decode(localcloudURLtoAPI(localcloudURL($serversettings["serverinfo"]["server_head"])))),
                        );
                    }else{
                        $now_userdata = array();
                    }

                    if($notificationdata["userchk"] === "done"){
                        $userchk = true;
                    }else{
                        $userchk = false;
                    }
            
                    $item = [
                        'from' => $now_userdata,
                        'category' => decode_yajirushi(htmlspecialchars_decode($notificationdata["category"])),
                        'title' => decode_yajirushi(htmlspecialchars_decode($notificationdata["title"])),
                        'text' => decode_yajirushi(htmlspecialchars_decode($notificationdata["msg"])),
                        'datetime' => decode_yajirushi(htmlspecialchars_decode($notificationdata["datetime"])),
                        'is_checked' => $userchk,
                    ];
            
                    $response[] = $item; // ループ内で $response にデータを追加
                }
            
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                $err = "notification_not_found";
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