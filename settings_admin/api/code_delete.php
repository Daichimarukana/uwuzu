<?php
require('../../db.php');
require("../../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

if (safetext(isset($_POST['code'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey']))) {
    $postUserid = safetext($_POST['userid']);
    $postCode= safetext($_POST['code']);
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);

    $is_login = uwuzuUserLoginCheck($loginid, $loginkey, "admin");
    if ($is_login === false) {
        echo json_encode(['success' => false, 'error' => '認証に失敗しました。(AUTH_INVALID)']);
        exit;
    }

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

    $query = $pdo->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

    $query->execute(array(':userid' => $postUserid));

    $result2 = $query->fetch();

    if($result2["loginid"] === $loginid){
        if($result2["admin"] === "yes"){
            try {
                // 削除クエリを実行
                $deleteQuery = $pdo->prepare("DELETE FROM invitation WHERE code = :code");
                $deleteQuery->bindValue(':code', $postCode, PDO::PARAM_STR);
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
}else{
    echo json_encode(['success' => false, 'error' => '削除に失敗しました。(sess_err)']);
    exit;
}
?>
