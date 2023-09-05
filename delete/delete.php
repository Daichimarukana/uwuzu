<?php
require('../db.php');

if (isset($_POST['uniqid']) && isset($_POST['userid'])){
    session_name('uwuzu_s_id');
    session_start();
    session_regenerate_id(true);

    $userid = $_SESSION['userid'];
    
    $postUserid = htmlentities($_POST['userid']);
    $postUniqid = htmlentities($_POST['uniqid']);

    if($userid === $postUserid){
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
            try {
                $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS);

                // 削除クエリを実行
                $deleteQuery = $pdo->prepare("DELETE FROM ueuse WHERE uniqid = :uniqid AND account = :userid");
                $deleteQuery->bindValue(':uniqid', $postUniqid, PDO::PARAM_STR);
                $deleteQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
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
    }
}
?>
