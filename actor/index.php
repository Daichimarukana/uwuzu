<?php
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);
if(htmlspecialchars($serversettings["serverinfo"]["server_activitypub"], ENT_QUOTES, 'UTF-8') === "true"){
    header("Content-Type: application/json; charset=utf-8");

    $domain = $_SERVER['HTTP_HOST'];

    require('../db.php');

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

    $user = htmlentities($_GET['actor']);

    $userid = str_replace('@','', str_replace('@'.$domain.'', '', $user));
    if( !empty($pdo) ) {
            
        // データベース接続の設定
        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));
        $userQuery = $dbh->prepare("SELECT username, userid, profile, follow, follower, iconname, headname,datetime FROM account WHERE userid = :userid");
        $userQuery->bindValue(':userid', $userid);
        $userQuery->execute();
        $userData = $userQuery->fetch();
    }
    if(!empty($userData)){

        $icon_kakucho_ci = pathinfo($userData["iconname"], PATHINFO_EXTENSION);
        $head_kakucho_ci = pathinfo($userData["headname"], PATHINFO_EXTENSION);

        $item = array(
            "@context" => [
                "https://www.w3.org/ns/activitystreams",
                "https://w3id.org/security/v1",
            ],
            "id" => "https://".$domain."/actor/?actor=@".$userid."",
            "type" => "Person",
            "preferredUsername" => "".$userData["userid"]."",
            "name" => "".$userData["username"]."",
            "summary" => "".nl2br($userData["profile"])."",
            "inbox" => "https://".$domain."/user/inbox/?actor=@".$userid."",
            "outbox" => "https://".$domain."/user/outbox/?actor=@".$userid."",
            "published" => "".date(DATE_ATOM, strtotime($userData["datetime"]))."",

            "discoverable" => true,

            "url" => "https://".$domain."/@".$userid."",

            "icon" => array(
                "type" => "Image",
                "mediaType" => "image/".$icon_kakucho_ci."",
                "url" => "https://".$domain."/".$userData["iconname"]."",
            ),

            "image" => array(
                "type" => "Image",
                "mediaType" => "image/".$icon_kakucho_ci."",
                "url" => "https://".$domain."/".$userData["headname"]."",
            ),
        );
        echo json_encode($item, JSON_UNESCAPED_UNICODE);
    }else{
        $item = array(
            "user_not_found",
        );
        echo json_encode($item, JSON_UNESCAPED_UNICODE);
    }
}else{
    header("HTTP/1.1 410 Gone");
}
?>