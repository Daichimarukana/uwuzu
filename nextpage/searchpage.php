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


$keyword = htmlentities(isset($_GET['keyword'])) ? htmlentities($_GET['keyword']) : '';
$userid = htmlentities($_GET['userid']);

$messages = array();

if (!empty($pdo)) {

    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	));   
    
    $messageQuery = $dbh->prepare("SELECT account,username,ueuse,uniqid,rpuniqid,datetime,photo1,photo2,video1,favorite, abi, abidate FROM ueuse WHERE ueuse LIKE :keyword OR abi LIKE :keyword ORDER BY datetime DESC");
	$messageQuery->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
	$messageQuery->execute();
	$message_array = $messageQuery->fetchAll();
        
	$messages = array();
	foreach ($message_array as $row) {
		$messages[] = $row;
	}
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
            
            $messageDisplay = new MessageDisplay($value, $userid);
            $messageDisplay->display();
        }
    }else{
        echo '<div class="tokonone" id="noueuse"><p>ユーズがありません</p></div>';
    }
    
    $pdo = null;

}

?>
