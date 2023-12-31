<?php

//------------------------------------------
// データベースに接続
try {

    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

} catch(PDOException $e) {

    // 接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}

$sql = "SELECT title, note, account, datetime FROM notice ORDER BY datetime DESC";
$notice_array = $pdo->query($sql);

while ($row = $notice_array->fetch(PDO::FETCH_ASSOC)) {

    $notices[] = $row;
}

?>
