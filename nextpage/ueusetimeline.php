<?php
header('Content-Type: application/json');
require('../db.php');
require('../function/function.php');
blockedIP($_SERVER['REMOTE_ADDR']);

if (safetext(isset($_POST['page'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey'])) && safetext(isset($_POST['uniqid']))) {
    $page = safetext($_POST['page']);
    $userId = safetext($_POST['userid']);
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);
    $uniqid = safetext($_POST['uniqid']);

    $is_login = uwuzuUserLoginCheck($loginid, $loginkey, "user");
    if ($is_login === false) {
        echo json_encode(['success' => false, 'error' => 'bad_request']);
        exit;
    }

    if(empty($uniqid)){
        echo json_encode(['success' => false, 'error' => 'no_ueuse']);
        exit;
    }

    // データベースに接続
    try {
        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);
    } catch (PDOException $e) {
        // 接続エラーのときエラー内容を取得する
        $error_message[] = $e->getMessage();
    }

    if (!empty($pdo)) {
        $myUserData = getUserData($pdo, $userId);
        $myblocklist = safetext($myUserData["blocklist"]);
        $mybookmark = safetext($myUserData["bookmark"]);

        $itemsPerPage = 15; // 1ページあたりのユーズ数
        $pageNumber = $page;
        if($pageNumber <= 0 || (!(is_numeric($pageNumber)))){
            $pageNumber = 1;
        }
        $offset = ($pageNumber - 1) * $itemsPerPage;

        $messages = array();
        $blocked_accounts = sqlBlockAccountList('account', $myblocklist);
        
        $messageQuery = $pdo->prepare("SELECT * FROM ueuse WHERE (uniqid = :ueuseid OR rpuniqid = :rpueuseid) {$blocked_accounts['sql']} ORDER BY datetime ASC LIMIT :offset, :itemsPerPage");
        foreach ($blocked_accounts['params'] as $ph => $val) {
            $messageQuery->bindValue($ph, $val, PDO::PARAM_STR);
        }
        $messageQuery->bindValue(':ueuseid', $uniqid, PDO::PARAM_STR);
        $messageQuery->bindValue(':rpueuseid', $uniqid, PDO::PARAM_STR);
        $messageQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
        $messageQuery->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
        $messageQuery->execute();
        $message_array = $messageQuery->fetchAll();    
            
        $messages = array();

        foreach ($message_array as $row) {
            if(!(empty($row["rpuniqid"]))){
                if(!($row["rpuniqid"] == $uniqid)){
                    $up_messageQuery = $pdo->prepare("SELECT * FROM ueuse WHERE uniqid = :ueuseid {$blocked_accounts['sql']} ORDER BY datetime ASC LIMIT :offset, :itemsPerPage");
                    foreach ($blocked_accounts['params'] as $ph => $val) {
                        $up_messageQuery->bindValue($ph, $val, PDO::PARAM_STR);
                    }
                    $up_messageQuery->bindValue(':ueuseid', $row["rpuniqid"]);
                    $up_messageQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $up_messageQuery->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
                    $up_messageQuery->execute();
                    $up_messageData = $up_messageQuery->fetchAll();
                    if(!(empty($up_messageData))){
                        foreach ($up_messageData as $up_row) {
                            $up_row["up_uniqid"] = $up_row["uniqid"];
                            $messages[] = $up_row;
                        }
                    }
                }
            }
            $messages[] = $row;
        }

        // ユーザー情報を取得して、$messages内のusernameをuserDataのusernameに置き換える
        $messages = getDatasUeuse($pdo, $messages);
        //adsystem------------------

        $message['ads'] = "false";

        $today = date("Y-m-d H:i:s");

        $adsQuery = $pdo->prepare("SELECT * FROM ads WHERE start_date < :today AND limit_date > :today ORDER BY rand()");
        $adsQuery->bindValue(':today', $today);
        $adsQuery->execute();
        $adsresult = $adsQuery->fetch();
        if(!(empty($adsresult))){
            $message['ads'] = "true";
            $message['ads_url'] = $adsresult["url"];
            $message['ads_img_url'] = $adsresult["image_url"];
            $message['ads_memo'] = $adsresult["memo"];
        }
        //--------------------------

        $ueuseItems = array();
        if(!empty($messages)){
            foreach ($messages as $value) {
                $formatted = FormatUeuseItem($value, $myblocklist, $mybookmark, $pdo, $userId);
                if ($formatted !== null) {
                    $ueuseItems[] = $formatted;
                }
            }

            if($message['ads'] === "true"){
                $adsystem = array(
                    "type" => "Ads",
                    "url" => $message['ads_url'],
                    "imgurl" => $message['ads_img_url'],
                    "memo" => $message['ads_memo'],
                );
            }else{
                $adsystem = null;
            }

            $item = array(
                "success" => true,
                "ueuses" => $ueuseItems,
                "ads" => $adsystem,
            );
    
            echo json_encode($item, JSON_UNESCAPED_UNICODE);
        }else{
            $item = array(
                "success" => false,
                "ueuses" => null,
                "ads" => null,
                "error" => "no_ueuse",
            );
            echo json_encode($item, JSON_UNESCAPED_UNICODE);
        }
        
        $pdo = null;
    }
}else{
    $item = array(
        "success" => false,
        "ueuses" => null,
        "ads" => null,
        "error" => "bad_request",
    );
    echo json_encode($item, JSON_UNESCAPED_UNICODE);
}
?>
