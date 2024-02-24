<?php
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
if(isset($_GET['limit'])) { 

    $itemsPerPage = htmlentities((int)$_GET['limit']); // 1ページあたりの投稿数
    if(isset($_GET['page'])) { 
        $pageNumber = htmlentities((int)$_GET['page']);
    }else{
        $pageNumber = 1;
    }
    $offset = ($pageNumber - 1) * $itemsPerPage;

    $messages = array();

    require('../db.php');

    $datetime = array();
    $pdo = null;

    session_start();

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


        if (!empty($pdo)) {
            $sql = "SELECT account, username, uniqid, rpuniqid, ueuse, datetime, photo1, photo2, video1, favorite, abi, abidate, nsfw FROM ueuse WHERE rpuniqid = '' ORDER BY datetime DESC LIMIT " . intval($offset) . ", " . intval($itemsPerPage);
            $message_array = $pdo->query($sql);    
        
            while ($row = $message_array->fetch(PDO::FETCH_ASSOC)) {
        
                $messages[] = $row;
            }
        
            // ユーザー情報を取得して、$messages内のusernameをuserDataのusernameに置き換える
            foreach ($messages as &$message) {
                $userQuery = $pdo->prepare("SELECT username, userid, profile, role FROM account WHERE userid = :userid");
                $userQuery->bindValue(':userid', $message["account"]);
                $userQuery->execute();
                $userData = $userQuery->fetch();
        
                if ($userData) {
                    $message['username'] = $userData['username'];
                    $message['role'] = $userData['role'];
                }
            }
        
            if (!empty($messages)) {
                $response = array(); // ループ外で $response を初期化
            
                foreach ($messages as $ueusedata) {
                    $favcnts = explode(',', $ueusedata["favorite"]);
                    $ueusedata["favorite_cnt"] = count($favcnts) - 1;
            
                    $item = [
                        'account' => decode_yajirushi(htmlspecialchars_decode($ueusedata["account"])),
                        'username' => decode_yajirushi(htmlspecialchars_decode($ueusedata["username"])),
                        'uniqid' => decode_yajirushi(htmlspecialchars_decode($ueusedata["uniqid"])),
                        'ueuse' => decode_yajirushi(htmlspecialchars_decode($ueusedata["ueuse"])),
                        'photo1' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', '' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo1"]))),
                        'photo2' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', '' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo2"]))),
                        'video1' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', '' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["video1"]))),
                        'favorite' => decode_yajirushi(htmlspecialchars_decode($ueusedata["favorite"])),
                        'favorite_cnt' => decode_yajirushi(htmlspecialchars_decode($ueusedata["favorite_cnt"])),
                        'datetime' => decode_yajirushi(htmlspecialchars_decode($ueusedata["datetime"])),
                        'abi' => decode_yajirushi(htmlspecialchars_decode($ueusedata["abi"])),
                        'abidatetime' => decode_yajirushi(htmlspecialchars_decode($ueusedata["abidate"])),
                        'nsfw' => decode_yajirushi(htmlspecialchars_decode($ueusedata["nsfw"])),
                    ];
            
                    $response[$ueusedata["uniqid"]] = $item; // ループ内で $response にデータを追加
                }
            
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                $err = "ueuse_not_found";
                $response = array(
                    'error_code' => $err,
                );
            
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            
            
            $pdo = null;
        }

}else{

    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
    );
     
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>