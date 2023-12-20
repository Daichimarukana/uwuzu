<?php
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);
if(htmlspecialchars($serversettings["serverinfo"]["server_activitypub"], ENT_QUOTES, 'UTF-8') === "true"){
    header("Content-Type: application/json; charset=utf-8");

    $domain = $_SERVER['HTTP_HOST'];

    require('../../db.php');

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

    $user = htmlentities($_GET['resource']);

    $userid = str_replace('acct:','', str_replace('@'.$domain.'', '', $user));

    $item = array(
        "subject" => "acct:".$userid.'@'.$domain.'',
        "links" => [
            array(
                "rel" => "self",
                "type" => "application/activity+json",
                "href" => "https://".$domain."/actor/?actor=@".$userid.'',
            ),
        ],
    );

    echo json_encode($item, JSON_UNESCAPED_UNICODE);
}else{
    header("HTTP/1.1 410 Gone");
}
?>