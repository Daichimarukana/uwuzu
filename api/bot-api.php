<?php
$mojisizefile = "../server/textsize.txt";

$banurldomainfile = "../server/banurldomain.txt";
$banurl_info = file_get_contents($banurldomainfile);
$banurl = preg_split("/\r\n|\n|\r/", $banurl_info);

header("Content-Type: application/json; charset=utf-8");

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
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

if(isset($_GET['token'])&&isset($_GET['ueuse'])) { 

    $token = htmlentities($_GET['token']);
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

    if($token === 'ice'){
        $err = "input_error";
        $response = array(
            'error_code' => $err,
        );
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }elseif($token === ''){
        $err = "input_error";
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
        
            $userQuery = $dbh->prepare("SELECT username, userid FROM account WHERE token = :token");
            $userQuery->bindValue(':token', $token);
            $userQuery->execute();
            $userData = $userQuery->fetch();

            if(empty($userData["userid"])){
                $err = "token_invalid";
                $response = array(
                    'error_code' => $err,
                );
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }else{
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
                    $stmt = $pdo->prepare("INSERT INTO ueuse (username, account, uniqid, ueuse, photo1, photo2, video1, datetime, abi) VALUES (:username, :account, :uniqid, :ueuse, :photo1, :photo2, :video1, :datetime, :abi)");
            
                    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                    $stmt->bindParam(':account', $userid, PDO::PARAM_STR);
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

                    $mentionedUsers = get_mentions_userid($ueuse);

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


                            $stmt->bindParam(':touserid', $touserid, PDO::PARAM_STR);
                            $stmt->bindParam(':msg', $msg, PDO::PARAM_STR);
                            $stmt->bindParam(':url', $url, PDO::PARAM_STR);
                            $stmt->bindParam(':userchk', $userchk, PDO::PARAM_STR);
                            $stmt->bindParam(':title', $title, PDO::PARAM_STR);

                            $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

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