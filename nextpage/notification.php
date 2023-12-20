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
if (isset($_GET['userid']) && isset($_GET['account_id'])) {
    $userid = htmlentities($_GET['userid']);
    $loginid = htmlentities($_GET['account_id']);

    // データベース接続の設定
    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ));

    $query = $dbh->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

    $query->execute(array(':userid' => $userid));

    $result2 = $query->fetch();

    if(!(empty($result2["loginid"]))){
        if($result2["loginid"] === $loginid){
            $userid = htmlentities($_GET['userid']);

            $itemsPerPage = 15; // 1ページあたりの投稿数
            $pageNumber = htmlentities(isset($_GET['page'])) ? htmlentities(intval($_GET['page'])) : 1;
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

                // トランザクション開始
                $pdo->beginTransaction();

                // SQL作成
                $stmt = $pdo->prepare("UPDATE notification SET userchk = 'done' WHERE touserid = :userid;");

                $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);

                $res = $stmt->execute();
                $res = $pdo->commit();

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
        }else{
            echo '<div class="tokonone" id="noueuse"><p>取得に失敗しました。</p></div>';
        }
    }else{
        echo '<div class="tokonone" id="noueuse"><p>取得に失敗しました。</p></div>';
    }
}else{
    echo '<div class="tokonone" id="noueuse"><p>取得に失敗しました。</p></div>';
}
?>
