<?php

require('../db.php');
require("../function/function.php");


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

if (isset($_GET['userid']) && isset($_GET['account_id'])) {
    $userid = safetext($_GET['userid']);
    $loginid = safetext($_GET['account_id']);

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
            $keyword = safetext(isset($_GET['keyword'])) ? safetext($_GET['keyword']) : '';
            $userid = safetext($_GET['userid']);

            $itemsPerPage = 15; // 1ページあたりのユーズ数
            $pageNumber = safetext(isset($_GET['page'])) ? safetext(intval($_GET['page'])) : 1;
            if($pageNumber <= 0 || (!(is_numeric($pageNumber)))){
                $pageNumber = 1;
            }
            $offset = ($pageNumber - 1) * $itemsPerPage;

            $messages = array();

            if (!empty($pdo)) {
                if (!empty($keyword)) {
                    $aduserinfoQuery = $pdo->prepare("SELECT username,userid,loginid,admin,role,sacinfo,blocklist,bookmark FROM account WHERE userid = :userid");
                    $aduserinfoQuery->bindValue(':userid', safetext($userid));
                    $aduserinfoQuery->execute();
                    $res = $aduserinfoQuery->fetch();
                    $myblocklist = safetext($res["blocklist"]);
                    $mybookmark = safetext($res["bookmark"]);

                    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    ));   

                    $keywordPattern = '/from:@(\w+)\s+(.+)/';
                    if (preg_match($keywordPattern, $keyword, $matches)) {
                        $username = $matches[1];
                        $searchKeyword = $matches[2];

                        $messageQuery = $dbh->prepare("SELECT * FROM ueuse WHERE account = :username AND (ueuse LIKE :searchKeyword OR abi LIKE :searchKeyword) ORDER BY datetime DESC LIMIT :offset, :itemsPerPage");
                        $messageQuery->bindValue(':username', $username, PDO::PARAM_STR);
                        $messageQuery->bindValue(':searchKeyword', '%' . $searchKeyword . '%', PDO::PARAM_STR);
                        $messageQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
                        $messageQuery->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
                        $messageQuery->execute();
                        $message_array = $messageQuery->fetchAll();
                    } else {
                        $messageQuery = $dbh->prepare("SELECT * FROM ueuse WHERE ueuse LIKE :keyword OR abi LIKE :keyword ORDER BY datetime DESC LIMIT :offset, :itemsPerPage");
                        $messageQuery->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
                        $messageQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
                        $messageQuery->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
                        $messageQuery->execute();
                        $message_array = $messageQuery->fetchAll();
                    }

                    $user_keyword = str_replace('@', '', $keyword);
                    $usersQuery = $dbh->prepare("SELECT * FROM account WHERE username LIKE :keyword OR userid LIKE :keyword OR profile LIKE :keyword ORDER BY datetime DESC LIMIT :offset, :itemsPerPage");
                    $usersQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $usersQuery->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
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
                            $value["bookmark"] = $mybookmark;

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
