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

            $aduserinfoQuery = $pdo->prepare("SELECT username,userid,loginid,admin,role,sacinfo,blocklist,bookmark FROM account WHERE userid = :userid");
            $aduserinfoQuery->bindValue(':userid', safetext($userid));
            $aduserinfoQuery->execute();
            $res = $aduserinfoQuery->fetch();
            $myblocklist = safetext($res["blocklist"]);
            $mybookmark = safetext($res["bookmark"]);

            $itemsPerPage = 15; // 1ページあたりのユーズ数
            $pageNumber = safetext(isset($_GET['page'])) ? safetext(intval($_GET['page'])) : 1;
            if($pageNumber <= 0 || (!(is_numeric($pageNumber)))){
                $pageNumber = 1;
            }
            $offset = ($pageNumber - 1) * $itemsPerPage;

            $messages = array();

            if (!empty($pdo)) {
                //------------------------------------------すべてのユーズを取得----------------------------------------------
                $all_sql = "SELECT ueuse.* 
                        FROM ueuse 
                        LEFT JOIN account ON ueuse.account = account.userid 
                        WHERE ueuse.rpuniqid = '' AND account.role != 'ice'
                        ORDER BY ueuse.datetime DESC 
                        LIMIT :offset, :itemsPerPage";

                $all_stmt = $pdo->prepare($all_sql);
                $all_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $all_stmt->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
                $all_stmt->execute();

                while ($row = $all_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $all_messages[] = $row;
                }
                if(empty($all_messages)){
                    $all_messages = [];
                }

                //------------------------------------------人気なユーズを取得(バズってるやつ)----------------------------------------------
                $day_count_sql = "SELECT ueuse.* 
                        FROM ueuse 
                        LEFT JOIN account ON ueuse.account = account.userid 
                        WHERE ueuse.datetime >= NOW() - INTERVAL 7 DAY AND ueuse.rpuniqid = '' AND account.role != 'ice'
                        ORDER BY ueuse.datetime DESC 
                        LIMIT 1000";
                $cnt_stmt = $pdo->prepare($day_count_sql);
                $cnt_stmt->execute();
                $Before7daysPosts = $cnt_stmt->fetchAll(PDO::FETCH_ASSOC);

                // 結果が15件に満たない場合
                if(count($Before7daysPosts) < 15){
                    $get_day = 90;
                }elseif(count($Before7daysPosts) > 15 && count($Before7daysPosts) < 150){
                    $get_day = 31;
                }elseif(count($Before7daysPosts) > 150 && count($Before7daysPosts) < 750){
                    $get_day = 7;
                }elseif(count($Before7daysPosts) > 750){
                    $get_day = 5;
                }else{
                    $get_day = 2;
                }

                $get_day = $get_day * (2 ** floor($pageNumber / 3));

                $pop_sql = "SELECT 
                            ueuse.*,
                            (LENGTH(ueuse.favorite) - LENGTH(REPLACE(ueuse.favorite, ',', '')) - 1) AS favorite_count,
                            (SELECT COUNT(*) FROM ueuse AS reuse WHERE reuse.ruuniqid = ueuse.uniqid) AS reuse_count,
                            ((LENGTH(ueuse.favorite) - LENGTH(REPLACE(ueuse.favorite, ',', '')) - 1) + 
                             (SELECT COUNT(*) FROM ueuse AS reuse WHERE reuse.ruuniqid = ueuse.uniqid)) AS total_score
                        FROM 
                            ueuse
                        LEFT JOIN account ON ueuse.account = account.userid 
                        WHERE 
                            ueuse.datetime >= NOW() - INTERVAL :getday DAY 
                        AND 
                            ueuse.rpuniqid = '' 
                        AND 
                            account.role != 'ice' 
                        ORDER BY 
                            total_score DESC
                        LIMIT :offset, :itemsPerPage;
                    ";

                $pop_stmt = $pdo->prepare($pop_sql);
                $pop_stmt->bindValue(':getday', $get_day, PDO::PARAM_INT);
                $pop_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $pop_stmt->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
                $pop_stmt->execute();

                while ($row = $pop_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $pop_messages[] = $row;
                }
                if(empty($flw_messages)){
                    $pop_messages = [];
                }

                //------------------------------------------フォローしているユーザーから取得----------------------------------------------
                $followList = explode(',', getUserData($pdo, $userid)["follow"]);

                foreach ($followList as $followUserId) {
                    $flw_sql = "SELECT ueuse.* 
                            FROM ueuse 
                            LEFT JOIN account ON ueuse.account = account.userid 
                            WHERE ueuse.rpuniqid = '' AND account.role != 'ice' AND ueuse.account = :follow_account AND ueuse.datetime >= NOW() - INTERVAL :getday DAY 
                            ORDER BY ueuse.datetime DESC 
                            LIMIT :offset, :itemsPerPage";

                    $flw_stmt = $pdo->prepare($flw_sql);
                    $flw_stmt->bindValue(':getday', $get_day, PDO::PARAM_INT);
                    $flw_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $flw_stmt->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
                    $flw_stmt->bindValue(':follow_account', $followUserId, PDO::PARAM_STR);
                    $flw_stmt->execute();

                    while ($row = $flw_stmt->fetch(PDO::FETCH_ASSOC)) {
                        $flw_messages[] = $row;
                    }
                }
                if(!(empty($flw_messages))){
                    usort($flw_messages, function($a, $b) {
                        return strtotime($b['datetime']) - strtotime($a['datetime']);
                    });
                }else{
                    $flw_messages = [];
                }

                //------------------------------------------いいねやリユーズを頻繁にするような好きっぽそうなユーザーの投稿を取得--------------------------------------
                $fav_sql = "SELECT ueuse.*,
                                    (LENGTH(ueuse.favorite) - LENGTH(REPLACE(ueuse.favorite, ',', '')) - 1) AS favorite_count
                                FROM ueuse
                                WHERE FIND_IN_SET(:userid, ueuse.favorite) > 0
                                ORDER BY ueuse.datetime DESC
                                LIMIT 100
                            ";
                $fav_stmt = $pdo->prepare($fav_sql);
                $fav_stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
                $fav_stmt->execute();
                while ($row = $fav_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $fav_ueuse_lists[] = $row;
                }
                if(!(empty($fav_ueuse_lists))){
                    $many_fav_accounts = array_column($fav_ueuse_lists, 'account');
                    $many_fav_account_counts = array_count_values($many_fav_accounts);
                    arsort($many_fav_account_counts);
                    $top_fav_accounts = array_slice($many_fav_account_counts, 0, 15, true); 

                    foreach ($top_fav_accounts as $favUserId => $count) {
                        $favget_sql = "SELECT ueuse.* 
                                FROM ueuse 
                                LEFT JOIN account ON ueuse.account = account.userid 
                                WHERE ueuse.rpuniqid = '' AND account.role != 'ice' AND ueuse.account = :fav_account AND ueuse.datetime >= NOW() - INTERVAL :getday DAY 
                                ORDER BY ueuse.datetime DESC 
                                LIMIT :offset, :itemsPerPage";
    
                        $favget_stmt = $pdo->prepare($favget_sql);
                        $favget_stmt->bindValue(':getday', $get_day, PDO::PARAM_INT);
                        $favget_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                        $favget_stmt->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
                        $favget_stmt->bindValue(':fav_account', $favUserId, PDO::PARAM_STR);
                        $favget_stmt->execute();
    
                        while ($row = $favget_stmt->fetch(PDO::FETCH_ASSOC)) {
                            $favget_messages[] = $row;
                        }
                    }
                    if(empty($favget_messages)){
                        $favget_messages = [];
                    }
                }else{
                    $favget_messages = [];
                }

                //基本的には人気・フォロー中・いいねする事が多いユーザーのユーズでTLを構成するけど全部出きったらLTLと同じにする
                if(empty($pop_messages) && empty($flw_messages) && empty($favget_messages)){
                    $messages = $all_messages;
                }elseif(count(array_merge($pop_messages, $flw_messages, $favget_messages)) < 15){
                    $total_messages = array_unique(array_merge($all_messages, $pop_messages, $flw_messages, $favget_messages), SORT_REGULAR);
                    shuffle($total_messages);
                    $messages = array_slice($total_messages, 0, 15);
                }else{
                    $total_messages = array_unique(array_merge($pop_messages, $flw_messages, $favget_messages), SORT_REGULAR);
                    shuffle($total_messages);
                    $messages = array_slice($total_messages, 0, 15);
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
                            if(!($value["role"] === "ice")){
                                $fav = $value['favorite']; // コンマで区切られたユーザーIDを含む変数
                        
                                // コンマで区切って配列に分割し、要素数を数える
                                $favIds = explode(',', $fav);
                                $value["favcnt"] = count($favIds)-1;

                                $value["bookmark"] = $mybookmark;
                        
                                $messageDisplay = new MessageDisplay($value, $userid); // $userid をコンストラクタに渡す
                                $messageDisplay->display();
                            }
                        }
                    }
                    if($message['ads'] === "true"){
                        echo '<div class="ads"><a href = "' . safetext($message['ads_url']) . '" target="_blank"><img src="' . safetext($message['ads_img_url']) . '" title="' . safetext($message['ads_memo']) . '"></a></div>';
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
