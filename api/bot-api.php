<?php

$domain = $_SERVER['HTTP_HOST'];

$mojisizefile = "../server/textsize.txt";

$banurldomainfile = "../server/banurldomain.txt";
$banurl_info = file_get_contents($banurldomainfile);
$banurl = preg_split("/\r\n|\n|\r/", $banurl_info);

//投稿及び返信レート制限↓(分):デフォで60件/分まで
$max_ueuse_rate_limit = 60;

header("Content-Type: application/json");
header("charset=utf-8");
header("Access-Control-Allow-Origin: *");

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}
function decode_yajirushi($postText){
    $postText = str_replace('&larr;', '←', $postText);
    $postText = str_replace('&darr;', '↓', $postText);
    $postText = str_replace('&uarr;', '↑', $postText);
    $postText = str_replace('&rarr;', '→', $postText);
    return $postText;
}
function get_mentions_userid($postText) {
    // @useridを検出する
    $usernamePattern = '/@(\w+)/';
    $mentionedUsers = [];

    preg_replace_callback($usernamePattern, function($matches) use (&$mentionedUsers) {
        $mention_username = $matches[1];

        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));
    
        $mention_userQuery = $dbh->prepare("SELECT username, userid FROM account WHERE userid = :userid");
        $mention_userQuery->bindValue(':userid', $mention_username);
        $mention_userQuery->execute();
        $mention_userData = $mention_userQuery->fetch();   
        
        if (!empty($mention_userData)) {
            $mentionedUsers[] = $mention_username;
        }
    }, $postText);

    return $mentionedUsers;
}

if(isset($_GET['token'])&&isset($_GET['type'])) { 

    $token = htmlentities($_GET['token']);
    $type = htmlentities($_GET['type']);

    if($type === "post" || $type === "ueuse"){
        if(isset($_GET['ueuse'])) { 
            $ueuse = nl2br(htmlentities($_GET['ueuse']));

            if( empty($ueuse) ) {
                $err = "input_not_found";
                $response = array(
                    'error_code' => $err,
                ); 
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            } else {
                // 文字数を確認
                if( (int)htmlspecialchars(file_get_contents($mojisizefile), ENT_QUOTES, 'UTF-8') < mb_strlen($ueuse, 'UTF-8') ) {
                    $err = "content_to_".htmlspecialchars(file_get_contents($mojisizefile), ENT_QUOTES, 'UTF-8')."_characters";
                    $response = array(
                        'error_code' => $err,
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                // 禁止url確認
                for($i = 0; $i < count($banurl); $i++) {
                    if(!($banurl[$i] == "")){
                        if (false !== strpos($ueuse, 'https://'.$banurl[$i])) {
                            $err = "contains_prohibited_url";
                            $response = array(
                                'error_code' => $err,
                            );
                            
                            echo json_encode($response, JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                    }
                }
            }
        
            if($token === 'ice'){
                $err = "this_account_has_been_frozen";
                $response = array(
                    'error_code' => $err,
                );
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }elseif($token === ''){
                $err = "token_input_error";
                $response = array(
                    'error_code' => $err,
                );
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }
        
            require('../db.php');
        
            $datetime = array();
            $pdo = null;
        
            session_start();
        
            try {
                $option = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
                );
                $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
            } catch(PDOException $e) {
                // 接続エラーのときエラー内容を取得する
                $error_message[] = $e->getMessage();
            }

            if( !empty($pdo) ) {
        
                // データベース接続の設定
                $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ));
            
                $userQuery = $dbh->prepare("SELECT username, userid, role FROM account WHERE token = :token");
                $userQuery->bindValue(':token', $token);
                $userQuery->execute();
                $userData = $userQuery->fetch();

                if(empty($userData["userid"])){
                    $err = "token_invalid";
                    $response = array(
                        'error_code' => $err,
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }elseif($userData["role"] === "ice"){
                    $err = "this_account_has_been_frozen";
                    $response = array(
                        'error_code' => $err,
                    );
                    
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }else{

                    $old_datetime = date("Y-m-d H:i:00");
                    $now_datetime = date("Y-m-d H:i:00",strtotime("+1 minute"));
                    $rate_Query = $dbh->prepare("SELECT * FROM ueuse WHERE account = :userid AND TIME(datetime) BETWEEN :old_datetime AND :now_datetime");
                    $rate_Query->bindValue(':userid', $userData["userid"]);
                    $rate_Query->bindValue(':old_datetime', $old_datetime);
                    $rate_Query->bindValue(':now_datetime', $now_datetime);
                    $rate_Query->execute();
                    $rate_count = $rate_Query->rowCount();
                    if(!($rate_count > $max_ueuse_rate_limit-1)){
                    
                        // 書き込み日時を取得
                        $username = $userData["username"];
                        $userid = $userData["userid"];
                        $datetime = date("Y-m-d H:i:s");
                        $uniqid = createUniqId();
                        $abi = "none";
                        $nones = "none";

                        // トランザクション開始
                        $pdo->beginTransaction();

                        try {

                            // SQL作成
                            $stmt = $pdo->prepare("INSERT INTO ueuse (username, account, uniqid, ueuse, photo1, photo2, photo3, photo4, video1, datetime, abi) VALUES (:username, :account, :uniqid, :ueuse, :photo1, :photo2, :photo3, :photo4, :video1, :datetime, :abi)");
                    
                            $stmt->bindParam(':username', htmlspecialchars($username, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
                            $stmt->bindParam(':account', htmlspecialchars($userid, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
                            $stmt->bindParam(':uniqid', htmlspecialchars($uniqid, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
                            $stmt->bindParam(':ueuse', htmlspecialchars($ueuse, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);

                            $stmt->bindParam(':photo1', htmlspecialchars($nones, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
                            $stmt->bindParam(':photo2', htmlspecialchars($nones, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
                            $stmt->bindParam(':photo3', htmlspecialchars($nones, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
                            $stmt->bindParam(':photo4', htmlspecialchars($nones, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
                            $stmt->bindParam(':video1', htmlspecialchars($nones, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);

                            $stmt->bindParam(':datetime', htmlspecialchars($datetime, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);

                            $stmt->bindParam(':abi', htmlspecialchars($abi, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);

                            // SQLクエリの実行
                            $res = $stmt->execute();

                            // コミット
                            $res = $pdo->commit();

                            $mentionedUsers = array_unique(get_mentions_userid($ueuse));

                            foreach ($mentionedUsers as $mentionedUser) {
                            
                                $pdo->beginTransaction();

                                try {
                                    $fromuserid = $userid;
                                    $touserid = $mentionedUser;
                                    $datetime = date("Y-m-d H:i:s");
                                    $msg = "" . $ueuse . "";
                                    $title = "" . $username . "さんにメンションされました！";
                                    $url = "/!" . $uniqid . "~" . $userid . "";
                                    $userchk = 'none';

                                    // 通知用SQL作成
                                    $stmt = $pdo->prepare("INSERT INTO notification (fromuserid, touserid, msg, url, datetime, userchk, title) VALUES (:fromuserid, :touserid, :msg, :url, :datetime, :userchk, :title)");

                                    $stmt->bindParam(':fromuserid', htmlspecialchars($fromuserid, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
                                    $stmt->bindParam(':touserid', htmlspecialchars($touserid, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
                                    $stmt->bindParam(':msg', htmlspecialchars($msg, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
                                    $stmt->bindParam(':url', htmlspecialchars($url, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
                                    $stmt->bindParam(':userchk', htmlspecialchars($userchk, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
                                    $stmt->bindParam(':title', htmlspecialchars($title, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);

                                    $stmt->bindParam(':datetime', htmlspecialchars($datetime, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);

                                    // SQLクエリの実行
                                    $res = $stmt->execute();

                                    // コミット
                                    $res = $pdo->commit();

                                } catch(Exception $e) {

                                    // エラーが発生した時はロールバック
                                    $pdo->rollBack();
                                }
                        
                            }

                        } catch(Exception $e) {

                            // エラーが発生した時はロールバック
                            $pdo->rollBack();
                        }

                        if( $res ) {
                            $response = array(
                                'uniqid' => $uniqid,
                                'userid' => $userid,
                            );
                            
                            echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        } else {
                            $err = "db_error_".$e->getMessage();
                            $response = array(
                                'error_code' => $err,
                            );
                            
                            echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        }

                        // プリペアドステートメントを削除
                        $stmt = null;
                    }else{
                        $err = "over_rate_limit";
                        $response = array(
                            'error_code' => $err,
                        );
                        
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    }
                }
            }
        }else{
            $err = "input_not_found";
            $response = array(
                'error_code' => $err,
            ); 
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if($type === "reply"){
        if(isset($_GET['ueuse']) && isset($_GET['uniqid'])) { 
            $rpuniqid = htmlentities($_GET['uniqid']);
            $ueuse = nl2br(htmlentities($_GET['ueuse']));

            if( empty($ueuse) ) {
                $err = "input_not_found";
                $response = array(
                    'error_code' => $err,
                ); 
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            } else {
                // 文字数を確認
                if( (int)htmlspecialchars(file_get_contents($mojisizefile), ENT_QUOTES, 'UTF-8') < mb_strlen($ueuse, 'UTF-8') ) {
                    $err = "content_to_".htmlspecialchars(file_get_contents($mojisizefile), ENT_QUOTES, 'UTF-8')."_characters";
                    $response = array(
                        'error_code' => $err,
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                // 禁止url確認
                for($i = 0; $i < count($banurl); $i++) {
                    if(!($banurl[$i] == "")){
                        if (false !== strpos($ueuse, 'https://'.$banurl[$i])) {
                            $err = "contains_prohibited_url";
                            $response = array(
                                'error_code' => $err,
                            );
                            
                            echo json_encode($response, JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                    }
                }
            }
        
            if($token === 'ice'){
                $err = "this_account_has_been_frozen";
                $response = array(
                    'error_code' => $err,
                );
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }elseif($token === ''){
                $err = "token_input_error";
                $response = array(
                    'error_code' => $err,
                );
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }
        
            require('../db.php');
        
            $datetime = array();
            $pdo = null;
        
            session_start();
        
            try {
                $option = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
                );
                $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
            } catch(PDOException $e) {
                // 接続エラーのときエラー内容を取得する
                $error_message[] = $e->getMessage();
            }

            if( !empty($pdo) ) {
        
                // データベース接続の設定
                $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ));
            
                $userQuery = $dbh->prepare("SELECT username, userid, role FROM account WHERE token = :token");
                $userQuery->bindValue(':token', $token);
                $userQuery->execute();
                $userData = $userQuery->fetch();

                if(empty($userData["userid"])){
                    $err = "token_invalid";
                    $response = array(
                        'error_code' => $err,
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }elseif($userData["role"] === "ice"){
                    $err = "this_account_has_been_frozen";
                    $response = array(
                        'error_code' => $err,
                    );
                    
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }else{
                    
                    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    ));

                    $old_datetime = date("Y-m-d H:i:00");
                    $now_datetime = date("Y-m-d H:i:00",strtotime("+1 minute"));
                    $rate_Query = $dbh->prepare("SELECT * FROM ueuse WHERE account = :userid AND TIME(datetime) BETWEEN :old_datetime AND :now_datetime");
                    $rate_Query->bindValue(':userid', $userData["userid"]);
                    $rate_Query->bindValue(':old_datetime', $old_datetime);
                    $rate_Query->bindValue(':now_datetime', $now_datetime);
                    $rate_Query->execute();
                    $rate_count = $rate_Query->rowCount();
                    if(!($rate_count > $max_ueuse_rate_limit-1)){
                
                        $resultQuery = $dbh->prepare("SELECT * FROM ueuse WHERE uniqid = :uniqid");
                        $resultQuery->bindValue(':uniqid', $rpuniqid);
                        $resultQuery->execute();
                        $resultData = $resultQuery->fetch();

                        if($resultData > 0){

                            // 書き込み日時を取得
                            $username = $userData["username"];
                            $userid = $userData["userid"];
                            $datetime = date("Y-m-d H:i:s");
                            $uniqid = createUniqId();
                            $abi = "none";
                            $nones = "none";

                            $touserid2 = $resultData["account"];

                            // トランザクション開始
                            $pdo->beginTransaction();

                            try {

                                // SQL作成
                                $stmt = $pdo->prepare("INSERT INTO ueuse (username, account, rpuniqid, uniqid, ueuse, photo1, photo2, video1, datetime, abi) VALUES (:username, :account, :rpuniqid, :uniqid, :ueuse, :photo1, :photo2, :video1, :datetime, :abi)");
                        
                                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                                $stmt->bindParam(':account', $userid, PDO::PARAM_STR);
                                $stmt->bindParam(':rpuniqid', $rpuniqid, PDO::PARAM_STR);
                                $stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
                                $stmt->bindParam(':ueuse', $ueuse, PDO::PARAM_STR);

                                $stmt->bindParam(':photo1', $nones, PDO::PARAM_STR);
                                $stmt->bindParam(':photo2', $nones, PDO::PARAM_STR);
                                $stmt->bindParam(':video1', $nones, PDO::PARAM_STR);

                                $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

                                $stmt->bindParam(':abi', $abi, PDO::PARAM_STR);

                                // SQLクエリの実行
                                $res = $stmt->execute();

                                // コミット
                                $res = $pdo->commit();

                                $pdo->beginTransaction();

                                $msg = ''.$ueuse.'';
                                $title = ''.$username.'さんが返信しました！';
                                $url = "https://".$domain."/!".$uniqid."~".$userid."";
                                $userchk = 'none';
                                // 通知用SQL作成
                                $stmt = $pdo->prepare("INSERT INTO notification (touserid, msg, url, datetime, userchk, title) VALUES (:touserid, :msg, :url, :datetime, :userchk, :title)");
                        
                                $stmt->bindParam(':touserid', $touserid2, PDO::PARAM_STR);
                                $stmt->bindParam(':msg', $msg, PDO::PARAM_STR);
                                $stmt->bindParam(':url', $url, PDO::PARAM_STR);
                                $stmt->bindParam(':userchk', $userchk, PDO::PARAM_STR);
                                $stmt->bindParam(':title', $title, PDO::PARAM_STR);

                                $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

                                // SQLクエリの実行
                                $res = $stmt->execute();

                                // コミット
                                $res = $pdo->commit();

                                $mentionedUsers = array_unique(get_mentions_userid($ueuse));

                                foreach ($mentionedUsers as $mentionedUser) {
                                
                                    $pdo->beginTransaction();

                                    try {
                                        $touserid = $mentionedUser;
                                        $datetime = date("Y-m-d H:i:s");
                                        $msg = "" . $ueuse . "";
                                        $title = "" . $username . "さんにメンションされました！";
                                        $url = "/!" . $uniqid . "~" . $userid . "";
                                        $userchk = 'none';

                                        // 通知用SQL作成
                                        $stmt = $pdo->prepare("INSERT INTO notification (touserid, msg, url, datetime, userchk, title) VALUES (:touserid, :msg, :url, :datetime, :userchk, :title)");


                                        $stmt->bindParam(':touserid', decode_yajirushi(htmlspecialchars_decode($touserid), PDO::PARAM_STR));
                                        $stmt->bindParam(':msg', decode_yajirushi(htmlspecialchars_decode($msg), PDO::PARAM_STR));
                                        $stmt->bindParam(':url', decode_yajirushi(htmlspecialchars_decode($url), PDO::PARAM_STR));
                                        $stmt->bindParam(':userchk', decode_yajirushi(htmlspecialchars_decode($userchk), PDO::PARAM_STR));
                                        $stmt->bindParam(':title', decode_yajirushi(htmlspecialchars_decode($title), PDO::PARAM_STR));

                                        $stmt->bindParam(':datetime', decode_yajirushi(htmlspecialchars_decode($datetime), PDO::PARAM_STR));

                                        // SQLクエリの実行
                                        $res = $stmt->execute();

                                        // コミット
                                        $res = $pdo->commit();

                                    } catch(Exception $e) {

                                        // エラーが発生した時はロールバック
                                        $pdo->rollBack();
                                    }
                            
                                }

                            } catch(Exception $e) {

                                // エラーが発生した時はロールバック
                                $pdo->rollBack();
                            }

                            if( $res ) {
                                $response = array(
                                    'uniqid' => $uniqid,
                                    'userid' => $userid,
                                );
                                
                                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                            } else {
                                $err = "db_error_".$e->getMessage();
                                $response = array(
                                    'error_code' => $err,
                                );
                                
                                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                            }
                            // プリペアドステートメントを削除
                            $stmt = null;

                        }else{
                            $err = "post_not_found";
                            $response = array(
                                'error_code' => $err,
                            ); 
                            echo json_encode($response, JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                    }else{
                        $err = "over_rate_limit ";
                        $response = array(
                            'error_code' => $err,
                        );
                        
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    }
                }
            }
        }else{
            $err = "input_not_found";
            $response = array(
                'error_code' => $err,
            ); 
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if($type === "getuser"){

        if($token === 'ice'){
            $err = "this_account_has_been_frozen";
            $response = array(
                'error_code' => $err,
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }elseif($token === ''){
            $err = "token_input_error";
            $response = array(
                'error_code' => $err,
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    
        require('../db.php');
    
        $datetime = array();
        $pdo = null;
    
        session_start();
    
        try {
            $option = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
            );
            $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
        } catch(PDOException $e) {
            // 接続エラーのときエラー内容を取得する
            $error_message[] = $e->getMessage();
        }

        if( !empty($pdo) ) {
        
            // データベース接続の設定
            $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ));
        
            $userQuery = $dbh->prepare("SELECT username, userid,role FROM account WHERE token = :token");
            $userQuery->bindValue(':token', $token);
            $userQuery->execute();
            $userData = $userQuery->fetch();

            if(empty($userData["userid"])){
                $err = "token_invalid";
                $response = array(
                    'error_code' => $err,
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }elseif($userData["role"] === "ice"){
                $err = "this_account_has_been_frozen";
                $response = array(
                    'error_code' => $err,
                );
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }else{
                $userQuery = $pdo->prepare("SELECT username,userid,profile,datetime,follow,follower,iconname,headname FROM account WHERE userid = :userid");
                $userQuery->bindValue(':userid', $userData["userid"]);
                $userQuery->execute();
                $userdata = $userQuery->fetch();
                
                if (empty($userdata)){
                    $response = array(
                        'error_code' => "critical_error_userdata_not_found",
                    );
                }else{
                    $followcnts = explode(',', $userdata["follow"]);
                    $userdata["follow_cnt"] = count($followcnts)-1;

                    $followercnts = explode(',', $userdata["follower"]);
                    $userdata["follower_cnt"] = count($followercnts)-1;

                    $response = array(
                        'user_name' => decode_yajirushi(htmlspecialchars_decode($userdata["username"])),
                        'user_id' => decode_yajirushi(htmlspecialchars_decode($userdata["userid"])),
                        'profile' => decode_yajirushi(htmlspecialchars_decode($userdata["profile"])),
                        'user_icon' => decode_yajirushi(htmlspecialchars_decode("https://".$domain."/".$userdata["iconname"])),
                        'user_header' => decode_yajirushi(htmlspecialchars_decode("https://".$domain."/".$userdata["headname"])),
                        'registered_date' => decode_yajirushi(htmlspecialchars_decode($userdata["datetime"])),
                        'follow' => decode_yajirushi(htmlspecialchars_decode($userdata["follow"])),
                        'follow_cnt' => decode_yajirushi(htmlspecialchars_decode($userdata["follow_cnt"])),
                        'follower' => decode_yajirushi(htmlspecialchars_decode($userdata["follower"])),
                        'follower_cnt' => decode_yajirushi(htmlspecialchars_decode($userdata["follower_cnt"])),
                    );
                }
                echo json_encode($response, JSON_UNESCAPED_UNICODE);;
            }
        }
    }

    if($type === "getuser_from_userid"){

        if(isset($_GET['userid'])) { 
            $userid = htmlentities($_GET['userid']);
            
            if($token === 'ice'){
                $err = "this_account_has_been_frozen";
                $response = array(
                    'error_code' => $err,
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }elseif($token === ''){
                $err = "token_input_error";
                $response = array(
                    'error_code' => $err,
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }
        
            require('../db.php');
        
            $datetime = array();
            $pdo = null;
        
            session_start();
        
            try {
                $option = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
                );
                $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
            } catch(PDOException $e) {
                // 接続エラーのときエラー内容を取得する
                $error_message[] = $e->getMessage();
            }

            
            if (!empty($pdo)) {

                    // データベース接続の設定
                $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ));
            
                $userQuery = $dbh->prepare("SELECT username, userid,role FROM account WHERE token = :token");
                $userQuery->bindValue(':token', $token);
                $userQuery->execute();
                $userData = $userQuery->fetch();

                if(empty($userData["userid"])){
                    $err = "token_invalid";
                    $response = array(
                        'error_code' => $err,
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }elseif($userData["role"] === "ice"){
                    $err = "this_account_has_been_frozen";
                    $response = array(
                        'error_code' => $err,
                    );
                    
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }else{
                    $userQuery = $pdo->prepare("SELECT username,userid,profile,datetime,follow,follower,iconname,headname FROM account WHERE userid = :userid");
                    $userQuery->bindValue(':userid', $userid);
                    $userQuery->execute();
                    $userdata = $userQuery->fetch();
                
                    if (empty($userdata)){
                        $response = array(
                            'error_code' => "userid_not_found",
                        );
                    }else{
                        $followcnts = explode(',', $userdata["follow"]);
                        $userdata["follow_cnt"] = count($followcnts)-1;

                        $followercnts = explode(',', $userdata["follower"]);
                        $userdata["follower_cnt"] = count($followercnts)-1;

                        $response = array(
                            'user_name' => decode_yajirushi(htmlspecialchars_decode($userdata["username"])),
                            'user_id' => decode_yajirushi(htmlspecialchars_decode($userdata["userid"])),
                            'profile' => decode_yajirushi(htmlspecialchars_decode($userdata["profile"])),
                            'user_icon' => decode_yajirushi(htmlspecialchars_decode("https://".$domain."/".$userdata["iconname"])),
                            'user_header' => decode_yajirushi(htmlspecialchars_decode("https://".$domain."/".$userdata["headname"])),
                            'registered_date' => decode_yajirushi(htmlspecialchars_decode($userdata["datetime"])),
                            'follow' => decode_yajirushi(htmlspecialchars_decode($userdata["follow"])),
                            'follow_cnt' => decode_yajirushi(htmlspecialchars_decode($userdata["follow_cnt"])),
                            'follower' => decode_yajirushi(htmlspecialchars_decode($userdata["follower"])),
                            'follower_cnt' => decode_yajirushi(htmlspecialchars_decode($userdata["follower_cnt"])),
                        );
                    }
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                }
            }

        }else{
            $err = "input_not_found";
            $response = array(
                'error_code' => $err,
            ); 
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if($type === "getltl"){

        if(isset($_GET['limit'])) { 

            $itemsPerPage = htmlentities((int)$_GET['limit']); // 1ページあたりの投稿数
            if(isset($_GET['page'])) { 
                $pageNumber = htmlentities((int)$_GET['page']);
                if(!(is_int($pageNumber))){
                    $pageNumber = 1;
                }
            }else{
                $pageNumber = 1;
            }
            $offset = ($pageNumber - 1) * $itemsPerPage;
        
            $messages = array();
            
            if($token === 'ice'){
                $err = "this_account_has_been_frozen";
                $response = array(
                    'error_code' => $err,
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }elseif($token === ''){
                $err = "token_input_error";
                $response = array(
                    'error_code' => $err,
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }
        
            require('../db.php');
        
            $datetime = array();
            $pdo = null;
        
            session_start();
        
            try {
                $option = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
                );
                $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
            } catch(PDOException $e) {
                // 接続エラーのときエラー内容を取得する
                $error_message[] = $e->getMessage();
            }

            
            if (!empty($pdo)) {

                // データベース接続の設定
                $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ));
            
                $userQuery = $dbh->prepare("SELECT username, userid,role FROM account WHERE token = :token");
                $userQuery->bindValue(':token', $token);
                $userQuery->execute();
                $userData = $userQuery->fetch();

                if(empty($userData["userid"])){
                    $err = "token_invalid";
                    $response = array(
                        'error_code' => $err,
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }elseif($userData["role"] === "ice"){
                    $err = "this_account_has_been_frozen";
                    $response = array(
                        'error_code' => $err,
                    );
                    
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }else{
                
                    $sql = "SELECT ueuse.* 
                            FROM ueuse 
                            LEFT JOIN account ON ueuse.account = account.userid 
                            WHERE ueuse.rpuniqid = '' AND account.role != 'ice'
                            ORDER BY ueuse.datetime DESC 
                            LIMIT :offset, :itemsPerPage";

                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $stmt->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
                    $stmt->execute();
                    $message_array = $stmt;
                
                    while ($row = $message_array->fetch(PDO::FETCH_ASSOC)) {
                
                        $messages[] = $row;
                    }
                
                    // ユーザー情報を取得して、$messages内のusernameをuserDataのusernameに置き換える
                    foreach ($messages as &$message) {
                        $userQuery = $pdo->prepare("SELECT username, userid, profile, role FROM account WHERE userid = :userid");
                        $userQuery->bindValue(':userid', $message["account"]);
                        $userQuery->execute();
                        $userData = $userQuery->fetch();
                
                        if ($userData) {
                            $message['username'] = $userData['username'];
                            $message['role'] = $userData['role'];
                        }
                    }
                
                    if (!empty($messages)) {
                        $response = array(); // ループ外で $response を初期化
                    
                        foreach ($messages as $ueusedata) {
                            $favcnts = explode(',', $ueusedata["favorite"]);
                            $ueusedata["favorite_cnt"] = count($favcnts) - 1;
                    
                            $item = [
                                'account' => decode_yajirushi(htmlspecialchars_decode($ueusedata["account"])),
                                'username' => decode_yajirushi(htmlspecialchars_decode($ueusedata["username"])),
                                'uniqid' => decode_yajirushi(htmlspecialchars_decode($ueusedata["uniqid"])),
                                'ueuse' => decode_yajirushi(htmlspecialchars_decode($ueusedata["ueuse"])),
                                'photo1' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo1"]))),
                                'photo2' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo2"]))),
                                'photo3' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo3"]))),
                                'photo4' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo4"]))),
                                'video1' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["video1"]))),
                                'favorite' => decode_yajirushi(htmlspecialchars_decode($ueusedata["favorite"])),
                                'favorite_cnt' => decode_yajirushi(htmlspecialchars_decode($ueusedata["favorite_cnt"])),
                                'datetime' => decode_yajirushi(htmlspecialchars_decode($ueusedata["datetime"])),
                                'abi' => decode_yajirushi(htmlspecialchars_decode($ueusedata["abi"])),
                                'abidatetime' => decode_yajirushi(htmlspecialchars_decode($ueusedata["abidate"])),
                                'nsfw' => decode_yajirushi(htmlspecialchars_decode($ueusedata["nsfw"])),
                            ];
                    
                            $response[$ueusedata["uniqid"]] = $item; // ループ内で $response にデータを追加
                        }
                    
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    } else {
                        $err = "ueuse_not_found";
                        $response = array(
                            'error_code' => $err,
                        );
                    
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    }
                    
                    
                    $pdo = null;
                }
            }

        }else{
            $err = "input_not_found";
            $response = array(
                'error_code' => $err,
            ); 
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if($type === "getueuse"){

        if(isset($_GET['ueuseid'])) { 

            $ueuseid = htmlentities($_GET['ueuseid']);
            
            if($token === 'ice'){
                $err = "this_account_has_been_frozen";
                $response = array(
                    'error_code' => $err,
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }elseif($token === ''){
                $err = "token_input_error";
                $response = array(
                    'error_code' => $err,
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }
        
            require('../db.php');
        
            $datetime = array();
            $pdo = null;
        
            session_start();
        
            try {
                $option = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
                );
                $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
            } catch(PDOException $e) {
                // 接続エラーのときエラー内容を取得する
                $error_message[] = $e->getMessage();
            }

            
            if (!empty($pdo)) {

                // データベース接続の設定
                $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ));
            
                $userQuery = $dbh->prepare("SELECT username, userid,role FROM account WHERE token = :token");
                $userQuery->bindValue(':token', $token);
                $userQuery->execute();
                $userData = $userQuery->fetch();

                if(empty($userData["userid"])){
                    $err = "token_invalid";
                    $response = array(
                        'error_code' => $err,
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }elseif($userData["role"] === "ice"){
                    $err = "this_account_has_been_frozen";
                    $response = array(
                        'error_code' => $err,
                    );
                    
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }else{
                
                    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    ));   
            
                    $ueuseQuery = $pdo->prepare("SELECT * FROM ueuse WHERE uniqid = :ueuseid");
                    $ueuseQuery->bindValue(':ueuseid', $ueuseid);
                    $ueuseQuery->execute();
                    $ueusedata = $ueuseQuery->fetch();
                
                    if (empty($ueusedata)){
                        $response = array(
                            'error_code' => "ueuseid_not_found",
                        );
                    }else{
                        $userQuery = $pdo->prepare("SELECT username, userid, profile, role FROM account WHERE userid = :userid");
                        $userQuery->bindValue(':userid', $ueusedata["account"]);
                        $userQuery->execute();
                        $userData = $userQuery->fetch();
                        if ($userData) {
                            $ueusedata['username'] = $userData['username'];
                            $ueusedata['role'] = $userData['role'];
                        }
                    
                    
                        $favcnts = explode(',', $ueusedata["favorite"]);
                        $ueusedata["favorite_cnt"] = count($favcnts)-1;
                    
                        $response = array(
                                'account' => decode_yajirushi(htmlspecialchars_decode($ueusedata["account"])),
                                'username' => decode_yajirushi(htmlspecialchars_decode($ueusedata["username"])),
                                'uniqid' => decode_yajirushi(htmlspecialchars_decode($ueusedata["uniqid"])),
                                'ueuse' => decode_yajirushi(htmlspecialchars_decode($ueusedata["ueuse"])),
                                'photo1' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo1"]))),
                                'photo2' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo2"]))),
                                'photo3' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo3"]))),
                                'photo4' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["photo4"]))),
                                'video1' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', 'https://' . $_SERVER['HTTP_HOST'] . '/', $ueusedata["video1"]))),
                                'favorite' => decode_yajirushi(htmlspecialchars_decode($ueusedata["favorite"])),
                                'favorite_cnt' => decode_yajirushi(htmlspecialchars_decode($ueusedata["favorite_cnt"])),
                                'datetime' => decode_yajirushi(htmlspecialchars_decode($ueusedata["datetime"])),
                                'abi' => decode_yajirushi(htmlspecialchars_decode($ueusedata["abi"])),
                                'abidatetime' => decode_yajirushi(htmlspecialchars_decode($ueusedata["abidate"])),
                                'nsfw' => decode_yajirushi(htmlspecialchars_decode($ueusedata["nsfw"])),
                        );
                    }
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    
                    
                    $pdo = null;
                }
            }

        }else{
            $err = "input_not_found";
            $response = array(
                'error_code' => $err,
            ); 
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

}else{

    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
    );
     
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>