<?php
header('Content-Type: application/json');

require('../db.php');
require("function.php");
blockedIP($_SERVER['REMOTE_ADDR']);
if (safetext(isset($_POST['uniqid'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey']))) {
    try {
        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);
    } catch (PDOException $e) {
        // 接続エラーのときエラー内容を取得する
        actionLog($userid, "error", "ueuse", null, $e, 4);
    }
    
    $userid = safetext($_POST['userid']);

    $uniqid = safetext($_POST['uniqid']);
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);

    $is_login = uwuzuUserLoginCheck($loginid, $loginkey, "user");
    if ($is_login === false) {
        echo json_encode(['success' => false, 'error' => '認証に失敗しました。(AUTH_INVALID)']);
        exit;
    }

    if(DelAPIToken($pdo, $uniqid)){
        echo json_encode(['success' => true, 'message' => 'アクセストークンが削除されました。']);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'アクセストークンの削除に失敗しました。']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => '必要なパラメータが提供されていません。']);
    exit;
}
 
?>