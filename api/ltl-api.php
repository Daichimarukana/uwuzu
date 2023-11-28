<?php
header("Content-Type: application/json; charset=utf-8; Access-Control-Allow-Origin: *;");

if(isset($_GET['limit'])) { 

    $itemsPerPage = (int)$_GET['limit']; // 1ページあたりの投稿数
    if(isset($_GET['page'])) { 
        $pageNumber = (int)$_GET['page'];
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
            $sql = "SELECT account, username, uniqid, rpuniqid, ueuse, datetime, photo1, photo2, video1, favorite, abi, abidate FROM ueuse WHERE rpuniqid = '' ORDER BY datetime DESC LIMIT " . intval($offset) . ", " . intval($itemsPerPage);
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
                        'account' => htmlentities($ueusedata["account"]),
                        'username' => htmlentities($ueusedata["username"]),
                        'uniqid' => htmlentities($ueusedata["uniqid"]),
                        'ueuse' => htmlentities($ueusedata["ueuse"]),
                        'photo1' => htmlentities(str_replace('../', '' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo1"])),
                        'photo2' => htmlentities(str_replace('../', '' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo2"])),
                        'video1' => htmlentities(str_replace('../', '' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["video1"])),
                        'favorite' => htmlentities($ueusedata["favorite"]),
                        'favorite_cnt' => htmlentities($ueusedata["favorite_cnt"]),
                        'datetime' => htmlentities($ueusedata["datetime"]),
                        'abi' => htmlentities($ueusedata["abi"]),
                        'abidatetime' => htmlentities($ueusedata["abidate"]),
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