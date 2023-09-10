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
