<?php

function createUniqId() {
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec . floor($msec * 1000000);

    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime, 10, 36);
}

require('../db.php');

require('view.php');

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

$userid = htmlentities($_GET['userid']);

$itemsPerPage = 30; // 1ページあたりのユーズ数
$pageNumber = htmlentities(isset($_GET['page'])) ? htmlentities(intval($_GET['page'])) : 1;
$offset = ($pageNumber - 1) * $itemsPerPage;

$messages = array();

if (!empty($pdo)) {
    
    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ));

    // フォローしているユーザーIDを取得し、カンマで区切る
    $followQuery = $dbh->prepare("SELECT follow FROM account WHERE userid = :userid");
    $followQuery->bindValue(':userid', $userid);
    $followQuery->execute();
    $followData = $followQuery->fetch();
    $follow = $followData['follow'];
    $followList = explode(',', $follow);

    // フォローしているユーザーの投稿を取得し、日時順に並び替える
    $messages = array(); // 初期化

    foreach ($followList as $followUserId) {
        $sql = "SELECT account, username, uniqid, rpuniqid, ueuse, datetime, photo1, photo2, video1, favorite, abi, abidate FROM ueuse WHERE rpuniqid = '' AND account = :follow_account ORDER BY datetime DESC LIMIT $offset, $itemsPerPage";

        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':follow_account', $followUserId, PDO::PARAM_STR);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $messages[] = $row;
        }
    }
    usort($messages, function($a, $b) {
        return strtotime($b['datetime']) - strtotime($a['datetime']);
    });
    // ユーザー情報を取得して、$messages内のusernameをuserDataのusernameに置き換える
    foreach ($messages as &$message) {
        $userQuery = $pdo->prepare("SELECT username, userid, profile, role, iconname, headname FROM account WHERE userid = :userid");
        $userQuery->bindValue(':userid', $message["account"]);
        $userQuery->execute();
        $userData = $userQuery->fetch();

        if ($userData) {
            $message['iconname'] = $userData['iconname'];
            $message['headname'] = $userData['headname'];
            $message['username'] = $userData['username'];
            $message['role'] = $userData['role'];
        }

        $rpQuery = $pdo->prepare("SELECT COUNT(*) as reply_count FROM ueuse WHERE rpuniqid = :rpuniqid");
        $rpQuery->bindValue(':rpuniqid', $message['uniqid']);
        $rpQuery->execute();
        $rpData = $rpQuery->fetch(PDO::FETCH_ASSOC);
        
        if ($rpData){
            $message['reply_count'] = $rpData['reply_count'];
        }
    }

    if(!empty($messages)){
        foreach ($messages as $value) {
                
            $fav = $value['favorite']; // コンマで区切られたユーザーIDを含む変数
    
            // コンマで区切って配列に分割し、要素数を数える
            $favIds = explode(',', $fav);
            $value["favcnt"] = count($favIds)-1;
    
            $messageDisplay = new MessageDisplay($value, $userid); // $userid をコンストラクタに渡す
            $messageDisplay->display();
        }
    }else{
        echo '<div class="tokonone" id="noueuse"><p>ユーズがありません</p></div>';
    }
    
    $pdo = null;

}

?>
