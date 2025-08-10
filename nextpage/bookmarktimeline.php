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

        $messages = array();
        
        $bookmarkQuery = $pdo->prepare("SELECT bookmark FROM account WHERE userid = :userid");
        $bookmarkQuery->bindValue(':userid', $userId);
        $bookmarkQuery->execute();
        $bookmarkData = $bookmarkQuery->fetch();

        $allBookmarks = explode(',', $bookmarkData['bookmark'] ?? '');
        $reversedBookmarks = array_reverse($allBookmarks);
        $bookmarkList = array_chunk($reversedBookmarks, $itemsPerPage);

        $list_Page = max(0, (int)$pageNumber - 1);
        $messages = [];

        if (!empty($bookmarkList[$list_Page])) {
            $currentPageUniqIds = $bookmarkList[$list_Page];

            // 名前付きプレースホルダ作成
            $placeholders = [];
            $params = [];
            foreach ($currentPageUniqIds as $i => $uniqid) {
                $key = ":uniqid$i";
                $placeholders[] = $key;
                $params[$key] = $uniqid;
            }
            $placeholderStr = implode(',', $placeholders);

            $sql = "SELECT ueuse.*
                    FROM ueuse
                    LEFT JOIN account ON ueuse.account = account.userid
                    WHERE ueuse.uniqid IN ($placeholderStr) AND account.role != 'ice'
                    ORDER BY FIELD(ueuse.uniqid, $placeholderStr)";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val, PDO::PARAM_STR);
            }
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
