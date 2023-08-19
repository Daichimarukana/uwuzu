<?php
require('../db.php');

if (htmlentities(isset($_POST['uniqid'])) && htmlentities(isset($_POST['abitext']))) {
    $postUniqid = htmlentities($_POST['uniqid']);
    $abitext = htmlentities($_POST['abitext']);
    $abidate = date("Y-m-d H:i:s");

    try {
        $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS);

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE ueuse SET abi = :abi, abidate = :abidate WHERE uniqid = :uniqid");
        $stmt->bindValue(':abi', $abitext, PDO::PARAM_STR);
        $stmt->bindValue(':abidate', $abidate, PDO::PARAM_STR);
        $stmt->bindValue(':uniqid', $postUniqid, PDO::PARAM_STR);
        
        // SQLクエリの実行
        $res = $stmt->execute();

        // コミット
        $pdo->commit();

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
?>
