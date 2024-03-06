<?php
require('../db.php');

if (htmlentities(isset($_POST['uniqid'])) && htmlentities(isset($_POST['userid'])) && htmlentities(isset($_POST['account_id']))){
    $postUserid = htmlentities($_POST['userid']);
    $postUniqid = htmlentities($_POST['uniqid']);
    $loginid = htmlentities($_POST['account_id']);

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
    // データベース接続の設定
    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ));

    $query = $dbh->prepare('SELECT * FROM ueuse WHERE uniqid = :uniqid limit 1');

    $query->execute(array(':uniqid' => $postUniqid));

    $result = $query->fetch();

    if($result["account"] === $postUserid){
        // データベース接続の設定
        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));

        $query = $dbh->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

        $query->execute(array(':userid' => $postUserid));

        $result2 = $query->fetch();

        if($result2["loginid"] === $loginid){
            $photo_query = $dbh->prepare("SELECT * FROM ueuse WHERE account = :userid AND uniqid = :uniqid");
            $photo_query->bindValue(':userid', $postUserid);
            $photo_query->bindValue(':uniqid', $postUniqid);
            $photo_query->execute();
            $photo_and_video = $photo_query->fetch();
			
            if(!($photo_and_video["photo1"] == "none")){
                $photoDelete1 = glob($photo_and_video["photo1"]); // 「-ユーザーID.拡張子」というパターンを検索
                foreach ($photoDelete1 as $photo1) {
                    if (is_file($photo1)) {
                        unlink($photo1);
                    }
                }
            }
            if(!($photo_and_video["photo2"] == "none")){
                $photoDelete2 = glob($photo_and_video["photo2"]); // 「-ユーザーID.拡張子」というパターンを検索
                foreach ($photoDelete2 as $photo2) {
                    if (is_file($photo2)) {
                        unlink($photo2);
                    }
                }
            }
            if(!($photo_and_video["photo3"] == "none")){
                $photoDelete3 = glob($photo_and_video["photo3"]); // 「-ユーザーID.拡張子」というパターンを検索
                foreach ($photoDelete3 as $photo3) {
                    if (is_file($photo3)) {
                        unlink($photo3);
                    }
                }
            }
            if(!($photo_and_video["photo4"] == "none")){
                $photoDelete4 = glob($photo_and_video["photo4"]); // 「-ユーザーID.拡張子」というパターンを検索
                foreach ($photoDelete4 as $photo4) {
                    if (is_file($photo4)) {
                        unlink($photo4);
                    }
                }
            }
            if(!($photo_and_video["video1"] == "none")){
                $videoDelete1 = glob($photo_and_video["video1"]); // 「-ユーザーID.拡張子」というパターンを検索
                foreach ($videoDelete1 as $video1) {
                    if (is_file($video1)) {
                        unlink($video1);
                    }
                }
            }
			

            try {

                $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS);

                // 削除クエリを実行
                $deleteQuery = $pdo->prepare("DELETE FROM ueuse WHERE uniqid = :uniqid AND account = :userid");
                $deleteQuery->bindValue(':uniqid', $postUniqid, PDO::PARAM_STR);
                $deleteQuery->bindValue(':userid', $postUserid, PDO::PARAM_STR);
                $res = $deleteQuery->execute();

                if ($res) {
                    echo json_encode(['success' => true]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'error' => '削除に失敗しました。']);
                    exit;
                }
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'データベースエラー：' . $e->getMessage()]);
                exit;
            }
        }
    }else{
        echo json_encode(['success' => false, 'error' => '削除に失敗しました。(userid_err)']);
        exit;
    }
}else{
    echo json_encode(['success' => false, 'error' => '削除に失敗しました。(sess_err)']);
    exit;
}
?>
