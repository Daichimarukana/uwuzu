<?php
$mojisizefile = "../server/textsize.txt";

$banurldomainfile = "../server/banurldomain.txt";
$banurl_info = file_get_contents($banurldomainfile);
$banurl = preg_split("/\r\n|\n|\r/", $banurl_info);

require('../db.php');

if (htmlentities(isset($_POST['uniqid'])) && htmlentities(isset($_POST['abitext'])) && htmlentities(isset($_POST['userid'])) && htmlentities(isset($_POST['account_id']))) {
    $userid = htmlentities($_POST['userid']);

    $postUniqid = htmlentities($_POST['uniqid']);
    $abitext = htmlentities($_POST['abitext']);
    $username = htmlentities($_POST['username']);
    $loginid = htmlentities($_POST['account_id']);

    $abidate = date("Y-m-d H:i:s");

    //-------------------------------------------
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

    // データベース接続の設定
    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ));

    $query = $dbh->prepare('SELECT * FROM ueuse WHERE uniqid = :uniqid limit 1');

    $query->execute(array(':uniqid' => $postUniqid));

    $result = $query->fetch();

    if($result["account"] === $userid){
        // データベース接続の設定
        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));

        $query = $dbh->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

        $query->execute(array(':userid' => $userid));

        $result2 = $query->fetch();

        if($result2["loginid"] === $loginid){

            // 文字数を確認
            if( (int)htmlspecialchars(file_get_contents($mojisizefile), ENT_QUOTES, 'UTF-8') < mb_strlen($abitext, 'UTF-8') ) {
                $err = "content_to_".htmlspecialchars(file_get_contents($mojisizefile), ENT_QUOTES, 'UTF-8')."_characters";
                $response = array(
                    'error_code' => $err,
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }
            // 禁止url確認
            for($i = 0; $i < count($banurl); $i++) {
                if (false !== strpos($abitext, 'https://'.$banurl[$i])) {
                    $err = "contains_prohibited_url";
                    $response = array(
                        'error_code' => $err,
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }

            try {
                $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS);

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE ueuse SET abi = :abi, abidate = :abidate WHERE uniqid = :uniqid AND account = :userid");
                $stmt->bindValue(':abi', $abitext, PDO::PARAM_STR);
                $stmt->bindValue(':abidate', $abidate, PDO::PARAM_STR);
                $stmt->bindValue(':uniqid', $postUniqid, PDO::PARAM_STR);

                $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
                
                // SQLクエリの実行
                $res = $stmt->execute();

                // コミット
                $pdo->commit();

                $mentionedUsers = get_mentions_userid($abitext);

                foreach ($mentionedUsers as $mentionedUser) {
                
                    $pdo->beginTransaction();

                    try {
                        $touserid = $mentionedUser;
                        $datetime = date("Y-m-d H:i:s");
                        $msg = "" . $abitext . "";
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
                        $res2 = $stmt->execute();

                        // コミット
                        $res2 = $pdo->commit();

                    } catch(Exception $e) {

                        // エラーが発生した時はロールバック
                        $pdo->rollBack();
                    }

                    if ($res2) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                        exit;
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => '追加に失敗しました。']);
                        exit;
                    }
            
                }

                if ($res) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => '追加に失敗しました。']);
                    exit;
                }
            } catch(PDOException $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'データベースエラー：' . $e->getMessage()]);
                exit;
            }
        }
    }
}
?>
