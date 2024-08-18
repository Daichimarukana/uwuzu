<?php
$mojisizefile = "../server/textsize.txt";

$banurldomainfile = "../server/banurldomain.txt";
$banurl_info = file_get_contents($banurldomainfile);
$banurl = preg_split("/\r\n|\n|\r/", $banurl_info);

require('../db.php');

require("../function/function.php");

if (safetext(isset($_POST['uniqid'])) && safetext(isset($_POST['abitext'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id']))) {
    $userid = safetext($_POST['userid']);

    $postUniqid = safetext($_POST['uniqid']);
    $abitext = safetext($_POST['abitext']);
    $loginid = safetext($_POST['account_id']);

    $abidate = date("Y-m-d H:i:s");

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
            if($result["abi"] == "none" && (!($result2["role"] == "ice"))){
                // 文字数を確認
                if( (int)safetext(file_get_contents($mojisizefile)) < mb_strlen($abitext, 'UTF-8') ) {
                    $err = "content_to_".safetext(file_get_contents($mojisizefile))."_characters";
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
                        $touserid = safetext($mentionedUser);
                        $datetime = date("Y-m-d H:i:s");
                        $msg = safetext("" . $abitext . "");
                        $title = safetext("" . $result2["username"] . "さんにメンションされました！");
                        $url = safetext("/!" . $postUniqid . "~" . $userid . "");
                        $userchk = 'none';
                
                        send_notification($touserid,$userid,$title,$msg,$url);
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
            }else{
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'すでに追記済みです。']);
                exit; 
            }

            
        }
    }
}
?>
