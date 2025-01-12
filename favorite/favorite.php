<?php
require('../db.php');
require('../function/function.php');
blockedIP($_SERVER['REMOTE_ADDR']);

if (safetext(isset($_POST['uniqid'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id']))) {
    $postUniqid = safetext($_POST['uniqid']);
    $userId = safetext($_POST['userid']);
    $loginid = safetext($_POST['account_id']);

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
        $query = $pdo->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

        $query->execute(array(':userid' => $userId));

        $result2 = $query->fetch();

        if($result2["loginid"] === $loginid){
            $res = addFavorite($pdo, $postUniqid, $userId);

            if ($res[0] === true) {
                echo json_encode(['success' => true, 'newFavorite' => $res[2]]);
                exit;
            } else {
                echo json_encode(['success' => false, 'error' => 'いいねの更新に失敗しました。']);
                exit;
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => '必要なパラメータが提供されていません。']);
        exit;
    }
?>
