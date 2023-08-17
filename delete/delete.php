<?php
require('../db.php');

if (isset($_POST['uniqid'])){
    $postUniqid = $_POST['uniqid'];

    try {
        $pdo = new PDO('mysql:charset=UTF8;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS);

        // 削除クエリを実行
        $deleteQuery = $pdo->prepare("DELETE FROM ueuse WHERE uniqid = :uniqid");
        $deleteQuery->bindValue(':uniqid', $postUniqid, PDO::PARAM_STR);
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
?>
