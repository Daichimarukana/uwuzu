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

            $aduserinfoQuery = $pdo->prepare("SELECT username,userid,loginid,admin,role,sacinfo,blocklist,bookmark FROM account WHERE userid = :userid");
            $aduserinfoQuery->bindValue(':userid', htmlentities($userid));
            $aduserinfoQuery->execute();
            $res = $aduserinfoQuery->fetch();
            $myblocklist = htmlentities($res["blocklist"]);
            $mybookmark = htmlentities($res["bookmark"]);

            $itemsPerPage = 15; // 1ページあたりのユーズ数
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
                    $sql = "SELECT ueuse.* 
                            FROM ueuse 
                            LEFT JOIN account ON ueuse.account = account.userid 
                            WHERE ueuse.rpuniqid = '' AND account.role != 'ice' AND ueuse.account = :follow_account 
                            ORDER BY ueuse.datetime DESC 
                            LIMIT :offset, :itemsPerPage";

                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $stmt->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
                    $stmt->bindValue(':follow_account', $followUserId, PDO::PARAM_STR);
                    $stmt->execute();
                    $message_array = $stmt;

                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $messages[] = $row;
                    }
                }
                usort($messages, function($a, $b) {
                    return strtotime($b['datetime']) - strtotime($a['datetime']);
                });
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
                        if (false === strpos($myblocklist, ','.htmlentities($value['account'], ENT_QUOTES, 'UTF-8'))) {
                            if(!($value["role"] === "ice")){
                                $value["bookmark"] = $mybookmark;
                                
                                $fav = $value['favorite']; // コンマで区切られたユーザーIDを含む変数
                        
                                // コンマで区切って配列に分割し、要素数を数える
                                $favIds = explode(',', $fav);
                                $value["favcnt"] = count($favIds)-1;
                        
                                $messageDisplay = new MessageDisplay($value, $userid); // $userid をコンストラクタに渡す
                                $messageDisplay->display();
                            }
                        }
                    }
                    if($message['ads'] === "true"){
                        echo '<div class="ads"><a href = "' . htmlentities($message['ads_url']) . '"><img src="' . htmlentities($message['ads_img_url']) . '" title="' . htmlentities($message['ads_memo']) . '"></a></div>';
                    }
                }else{
                    echo '<div class="tokonone" id="noueuse"><p>ユーズがありません</p></div>';
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
