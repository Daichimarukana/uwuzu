<?php

header('Content-Type: application/json');
require('../db.php');
require('../function/function.php');
blockedIP($_SERVER['REMOTE_ADDR']);

if (safetext(isset($_POST['emoji'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey']))) {
    $emoji = safetext($_POST['emoji']);
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
        $emojis = array_unique(array_filter(explode(',', $emoji)));

        $results = [];

        if (count($emojis) > 0) {
            // プレースホルダを作成
            $placeholders = implode(',', array_fill(0, count($emojis), '?'));
            $stmt = $pdo->prepare("SELECT emojifile, emojiname FROM emoji WHERE emojiname IN ($placeholders)");
            $stmt->execute($emojis);

            $fetched = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $fetched[$row['emojiname']] = [
                    'emojipath' => filter_var($row["emojifile"], FILTER_VALIDATE_URL) ? $row["emojifile"] : "../" . $row["emojifile"],
                    'emojiname' => $row['emojiname']
                ];
            }

            foreach ($emojis as $name) {
                if (isset($fetched[$name])) {
                    $results[$name] = $fetched[$name];
                } else {
                    $results[$name] = null;
                }
            }
        }

        echo json_encode([
            "success" => true,
            "emojis" => $results
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $item = array(
            "success" => false,
            "emojipath" => null,
            "emojiname" => null,
        );
        echo json_encode($item, JSON_UNESCAPED_UNICODE);
    }
}else{
    $item = array(
        "success" => false,
        "emojipath" => null,
        "emojiname" => null,
    );
    echo json_encode($item, JSON_UNESCAPED_UNICODE);
}
?>
