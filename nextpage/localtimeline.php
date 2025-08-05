<?php
header('Content-Type: application/json');
require('../db.php');
require('../function/function.php');
blockedIP($_SERVER['REMOTE_ADDR']);

if (safetext(isset($_POST['page'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey']))) {
    $page = safetext($_POST['page']);
    $userId = safetext($_POST['userid']);
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);

    $is_login = uwuzuUserLoginCheck($loginid, $loginkey, "user");
    if ($is_login === false) {
        echo json_encode(['success' => false, 'error' => 'bad_request']);
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
        
        $sql = "SELECT ueuse.* 
                FROM ueuse 
                LEFT JOIN account ON ueuse.account = account.userid 
                WHERE ueuse.rpuniqid = '' AND account.role != 'ice'
                ORDER BY ueuse.datetime DESC 
                LIMIT :offset, :itemsPerPage";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
        $stmt->execute();
        $message_array = $stmt;

        while ($row = $message_array->fetch(PDO::FETCH_ASSOC)) {
            $messages[] = $row;
        }

        // ユーザー情報を取得して、$messages内のusernameをuserDataのusernameに置き換える
        foreach ($messages as &$message) {
            $userQuery = $pdo->prepare("SELECT username, userid, profile, role, iconname, headname, sacinfo FROM account WHERE userid = :userid");
            $userQuery->bindValue(':userid', $message["account"]);
            $userQuery->execute();
            $userData = $userQuery->fetch();

            if ($userData) {
                $message['iconname'] = $userData['iconname'];
                $message['headname'] = $userData['headname'];
                $message['username'] = $userData['username'];
                $message['sacinfo'] = $userData['sacinfo'];
                $message['role'] = $userData['role'];
            }

            //リプライ数取得
            $rpQuery = $pdo->prepare("SELECT COUNT(*) as reply_count FROM ueuse WHERE rpuniqid = :rpuniqid");
            $rpQuery->bindValue(':rpuniqid', $message['uniqid']);
            $rpQuery->execute();
            $rpData = $rpQuery->fetch(PDO::FETCH_ASSOC);
            
            if ($rpData){
                $message['reply_count'] = $rpData['reply_count'];
            }

            //リユーズ数取得
            $ruQuery = $pdo->prepare("SELECT COUNT(*) as reuse_count FROM ueuse WHERE ruuniqid = :ruuniqid");
            $ruQuery->bindValue(':ruuniqid', $message['uniqid']);
            $ruQuery->execute();
            $ruData = $ruQuery->fetch(PDO::FETCH_ASSOC);
            
            if ($ruData){
                $message['reuse_count'] = $ruData['reuse_count'];
            }

            $fav = $message['favorite'];
            $favIds = explode(',', $fav);
            $message["favorite_conut"] = count($favIds)-1;
        }
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
