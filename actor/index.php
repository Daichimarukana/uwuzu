<?php
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

require('../db.php');
require("../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

if(safetext($serversettings["serverinfo"]["server_activitypub"]) === "true"){
    header("Content-Type: application/activity+json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");

    $domain = $_SERVER['HTTP_HOST'];

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

    if(isset($_GET['actor'])){
        $user = safetext($_GET['actor']);
    }else{
        $user = null;
    }

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
                array(
                    "schema" => "http://schema.org#",
                    "PropertyValue" => "schema:PropertyValue",
                    "value" => "schema:value",
                ),
            ],
            "id" => "https://".$domain."/actor/?actor=@".$userid."",
            "type" => "Person",
            "preferredUsername" => "".$userData["userid"]."",
            "name" => "".$userData["username"]."",
            "summary" => "".nl2br($userData["profile"])."",
            "followers" => "https://".$domain."/user/followers/?actor=@".$userid."",
            "following" => "https://".$domain."/user/following/?actor=@".$userid."",
            "inbox" => "https://".$domain."/user/inbox/?actor=@".$userid."",
            "outbox" => "https://".$domain."/user/outbox/?actor=@".$userid."",
            "published" => "".date(DATE_ATOM, strtotime($userData["datetime"]))."",

            "discoverable" => true,

            "url" => "https://".$domain."/@".$userid."",

            "icon" => array(
                "type" => "Image",
                "mediaType" => "image/".$icon_kakucho_ci."",
                "url" => localcloudURLtoAPI(localcloudURL($userData["iconname"])),
            ),

            "image" => array(
                "type" => "Image",
                "mediaType" => "image/".$icon_kakucho_ci."",
                "url" => localcloudURLtoAPI(localcloudURL($userData["headname"])),
            ),

            /*"publicKey" => array(
                "id" => "https://".$domain."/actor/?actor=@".$userid."#main-key",
                "owner" => "https://".$domain."/actor/?actor=@".$userid."",
                "publicKeyPem" => "ここにHTTP-Signature",
            ),*/
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