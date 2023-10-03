<?php

function createUniqId() {
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec . floor($msec * 1000000);

    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime, 10, 36);
}

require('../db.php');

require('view.php');

require('user_view.php');

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
    if (!empty($keyword)) {

        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));   

        $keywordPattern = '/from:@(\w+)\s+(.+)/';
        if (preg_match($keywordPattern, $keyword, $matches)) {
            $username = $matches[1];
            $searchKeyword = $matches[2];

            $messageQuery = $dbh->prepare("SELECT * FROM ueuse WHERE account = :username AND (ueuse LIKE :searchKeyword OR abi LIKE :searchKeyword) ORDER BY datetime DESC");
            $messageQuery->bindValue(':username', $username, PDO::PARAM_STR);
            $messageQuery->bindValue(':searchKeyword', '%' . $searchKeyword . '%', PDO::PARAM_STR);
            $messageQuery->execute();
            $message_array = $messageQuery->fetchAll();
        } else {
            $messageQuery = $dbh->prepare("SELECT * FROM ueuse WHERE ueuse LIKE :keyword OR abi LIKE :keyword ORDER BY datetime DESC");
            $messageQuery->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
            $messageQuery->execute();
            $message_array = $messageQuery->fetchAll();
        }

        $user_keyword = str_replace('@', '', $keyword);
        $usersQuery = $dbh->prepare("SELECT * FROM account WHERE username LIKE :keyword OR userid LIKE :keyword OR profile LIKE :keyword ORDER BY datetime DESC");
        $usersQuery->bindValue(':keyword', '%' . $user_keyword . '%', PDO::PARAM_STR);
        $usersQuery->execute();
        $users_array = $usersQuery->fetchAll();

        $users = array();
        foreach ($users_array as $row) {
            $users[] = $row;
        }
            
        $messages = array();
        foreach ($message_array as $row) {
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

            $rpQuery = $pdo->prepare("SELECT COUNT(*) as reply_count FROM ueuse WHERE rpuniqid = :rpuniqid");
            $rpQuery->bindValue(':rpuniqid', $message['uniqid']);
            $rpQuery->execute();
            $rpData = $rpQuery->fetch(PDO::FETCH_ASSOC);
            
            if ($rpData){
                $message['reply_count'] = $rpData['reply_count'];
            }
        }

        if(!empty($users)){
            foreach ($users as $uservalue) {
                $flw = $uservalue['follow']; 
                $flwIds = explode(',', $flw);
                $uservalue["follow_cnt"] = count($flwIds)-1;

                $flr = $uservalue['follower'];
                $flrIds = explode(',', $flr);
                $uservalue["follower_cnt"] = count($flrIds)-1;

                $messageDisplay = new UserdataDisplay($uservalue, $userid);
                $messageDisplay->display();
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
    }else{
        echo '<div class="tokonone" id="noueuse"><p>検索ワードを入力してください。</p></div>';
    }
}

?>
