<?php

function createUniqId() {
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec . floor($msec * 1000000);

    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime, 10, 36);
}

require('../db.php');

require('notificationview.php');

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

$userid = $_GET['userid'];

$itemsPerPage = 30; // 1ページあたりの投稿数
$pageNumber = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($pageNumber - 1) * $itemsPerPage;

$messages = array();

if (!empty($pdo)) {

    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	));

    $messageQuery = $dbh->prepare("SELECT title,msg,url,datetime,userchk FROM notification WHERE touserid = :userid ORDER BY datetime DESC LIMIT $offset, $itemsPerPage");
	$messageQuery->bindValue(':userid', $userid);
	$messageQuery->execute();
    $message_array = $messageQuery->fetchAll();

    if (!empty($message_array)) {
        foreach ($message_array as $value) {
            $messageDisplay = new MessageDisplay($value); // userid を渡さない
            $messageDisplay->display();
        }
    } else {
        echo '<div class="tokonone" id="noueuse"><p>通知はありません</p></div>';
    }
    
    
    $pdo = null;

}

?>
