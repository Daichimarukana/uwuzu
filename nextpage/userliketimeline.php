<?php
header('Content-Type: application/json');
require('../db.php');
require('../function/function.php');
blockedIP($_SERVER['REMOTE_ADDR']);
$domain = $_SERVER['HTTP_HOST'];
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

if (safetext(isset($_POST['page'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey'])) && safetext(isset($_POST['id']))) {
    $page = safetext($_POST['page']);
    $userId = safetext($_POST['userid']);
    $uwuzuid = safetext($_POST['id']) ? safetext($_POST['id']) : '';
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);

    if (safetext($serversettings["serverinfo"]["server_activitypub"]) === "true") {
        if (isset($_POST['activity_domain'])) {
            $activity_domain = safetext($_POST['activity_domain']) ? safetext($_POST['activity_domain']) : '';

            if (!($activity_domain == $domain)) {
                $domain_response = GetActivityPubUser($uwuzuid, $activity_domain);
                if (empty($domain_response) || array_key_exists("error", $domain_response)) {
                    $userData = null;
                } else {
                    $userData = $domain_response;
                }
                //var_dump($domain_response);
                $is_local = false;
                $item = array(
                    "success" => false,
                    "ueuses" => null,
                    "ads" => null,
                    "error" => "no_ueuse",
                );
                echo json_encode($item, JSON_UNESCAPED_UNICODE);
                exit;
            } else {
                $is_local = true;
            }
        }
    } else {
        $activity_domain = $domain;
        $is_local = true;
    }

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
        
        $userQuery = $pdo->prepare("SELECT username, userid, profile, role, follower FROM account WHERE userid = :userid");
        $userQuery->bindValue(':userid', $uwuzuid);
        $userQuery->execute();
        $userData = $userQuery->fetch();    
        $message_array = [];

        $sql = "SELECT ueuse.*  
                FROM ueuse  
                LEFT JOIN account ON ueuse.account = account.userid  
                WHERE FIND_IN_SET(:userid, REPLACE(ueuse.favorite, ' ', '')) > 0 
                AND account.role != 'ice'  
                ORDER BY ueuse.datetime DESC  
                LIMIT :offset, :itemsPerPage"; 

        $stmt = $pdo->prepare($sql); 
        $stmt->bindValue(':userid', $uwuzuid, PDO::PARAM_STR); 
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT); 
        $stmt->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT); 
        $stmt->execute(); 
        $results = $stmt->fetchAll();

        // 結果を追加
        $message_array = array_merge($message_array, $results);
        foreach ($message_array as $row) {
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
