<?php

require('../db.php');
require("../function/function.php");


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
            $userid = safetext($_GET['userid']);

            $aduserinfoQuery = $pdo->prepare("SELECT username,userid,loginid,admin,role,sacinfo,blocklist,bookmark FROM account WHERE userid = :userid");
            $aduserinfoQuery->bindValue(':userid', safetext($userid));
            $aduserinfoQuery->execute();
            $res = $aduserinfoQuery->fetch();
            $myblocklist = safetext($res["blocklist"]);
            $mybookmark = safetext($res["bookmark"]);

            $ueuseid = safetext(isset($_GET['id'])) ? safetext($_GET['id']) : '';

            $itemsPerPage = 15; // 1ページあたりの投稿数
            $pageNumber = safetext(isset($_GET['page'])) ? safetext(intval($_GET['page'])) : 1;
            $offset = ($pageNumber - 1) * $itemsPerPage;

            $messages = array();

            if (!empty($pdo)) {


                // データベース接続の設定
                $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ));

                // 投稿内容の取得（新しい順に取得）
                $messageQuery = $dbh->prepare("SELECT * FROM ueuse WHERE uniqid = :ueuseid OR rpuniqid = :rpueuseid ORDER BY datetime ASC LIMIT :offset, :itemsPerPage");
                $messageQuery->bindValue(':ueuseid', $ueuseid, PDO::PARAM_STR);
                $messageQuery->bindValue(':rpueuseid', $ueuseid, PDO::PARAM_STR);
                $messageQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
                $messageQuery->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
                $messageQuery->execute();
                $message_array = $messageQuery->fetchAll();    
                    
                $messages = array();

                
                

                foreach ($message_array as $row) {
                    if(!(empty($row["rpuniqid"]))){
                        if(!($row["rpuniqid"] == $ueuseid)){
                            $up_messageQuery = $pdo->prepare("SELECT * FROM ueuse WHERE uniqid = :ueuseid ORDER BY datetime ASC LIMIT :offset, :itemsPerPage");
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

                if(!empty($messages)){
                    foreach ($messages as $value) {
                        if (false === strpos($myblocklist, ','.safetext($value['account']))) {
                            $value["bookmark"] = $mybookmark;

                            $fav = $value['favorite']; // コンマで区切られたユーザーIDを含む変数

                            // コンマで区切って配列に分割し、要素数を数える
                            $favIds = explode(',', $fav);
                            $value["favcnt"] = count($favIds)-1;

                            $messageDisplay = new MessageDisplay($value, $userid);
                            $messageDisplay->display();
                        }
                    }
                    if($message['ads'] === "true"){
                        echo '<div class="ads"><a href = "' . safetext($message['ads_url']) . '"><img src="' . safetext($message['ads_img_url']) . '" title="' . safetext($message['ads_memo']) . '"></a></div>';
                    }
                }else{
                    echo '<div class="tokonone" id="noueuse"><p>投稿がありません</p></div>';
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
