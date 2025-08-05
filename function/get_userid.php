<?php

header('Content-Type: application/json');
require('../db.php');
require('../function/function.php');
blockedIP($_SERVER['REMOTE_ADDR']);

if (safetext(isset($_POST['get_account'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey']))) {
    $get_account = safetext($_POST['get_account']);
    $userId = safetext($_POST['userid']);
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);

    $is_login = uwuzuUserLoginCheck($loginid, $loginkey, "user");
    if ($is_login === false) {
        echo json_encode(['success' => false, 'error' => '認証に失敗しました。(AUTH_INVALID)']);
        exit;
    }

    // データベースに接続
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

    if (!empty($pdo)) {
        // カンマ区切りまたは1つのユーザーID文字列を処理
        $usernames = array_unique(array_filter(explode(',', $get_account)));
        $lower_usernames = array_map('mb_strtolower', $usernames);

        $results = [];

        if (count($lower_usernames) > 0) {
            $placeholders = implode(',', array_fill(0, count($lower_usernames), '?'));
            $stmt = $pdo->prepare("SELECT userid, username FROM account WHERE LOWER(userid) IN ($placeholders)");
            $stmt->execute($lower_usernames);

            $fetched = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $fetched[mb_strtolower($row['userid'])] = [
                    'userid' => $row['userid'],
                    'username' => $row['username']
                ];
            }

            foreach ($usernames as $name) {
                $lower = mb_strtolower($name);
                $results[$name] = $fetched[$lower] ?? null;
            }
        }
        
        echo json_encode([
            "success" => true,
            "users" => $results
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "success" => false,
            "users" => null
        ], JSON_UNESCAPED_UNICODE);
    }
}else{
    $item = array(
        "success" => false,
        "userid" => null,
        "username" => null,
    );
    echo json_encode($item, JSON_UNESCAPED_UNICODE);
}
?>
