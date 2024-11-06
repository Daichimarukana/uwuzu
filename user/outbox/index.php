<?php
require("../../function/function.php");

$serversettings_file = "../../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);
if(safetext($serversettings["serverinfo"]["server_activitypub"]) === "true"){
    header("Content-Type: application/activity+json");
    header("charset=utf-8");
    header("Access-Control-Allow-Origin: *");

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

    $user = safetext($_GET['actor']);

    $userid = str_replace('@','', str_replace('@'.$domain.'', '', $user));
    if( !empty($pdo) ) {
            
        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));
        $userQuery = $dbh->prepare("SELECT username, userid, profile, follower, iconname FROM account WHERE userid = :userid");
        $userQuery->bindValue(':userid', $userid);
        $userQuery->execute();
        $userData = $userQuery->fetch();

        $messageQuery = $dbh->prepare("SELECT * FROM ueuse WHERE account = :userid AND rpuniqid = '' ORDER BY datetime DESC");
        $messageQuery->bindValue(':userid', $userid);
        $messageQuery->execute();
        $message_array = $messageQuery->fetchAll();
        
        $messages = array();
        foreach ($message_array as $row) {
            $messages[] = $row;
        }
    }

    if (!empty($userData)) {
        if (!empty($messages)) {
            $orderedItems = array();
    
            foreach ($messages as $value) {
                $activity = array(
                    "type" => "Create",
                    "@context" => "https://www.w3.org/ns/activitystreams",
                    "id" => "https://" . $domain . "/ueuse/activity/?ueuse=" . $value["uniqid"],
                    "url" => "https://" . $domain . "/ueuse/activity/?ueuse=" . $value["uniqid"],
                    "published" => date(DATE_ATOM, strtotime($value["datetime"])),
                    "to" => [
                        "https://www.w3.org/ns/activitystreams#Public",
                    ],
                    "actor" => "https://" . $domain . "/actor/?actor=@" . $userid,
                    "object" => array(
                        "type" => "Note",
                        "@context" => "https://www.w3.org/ns/activitystreams",
                        "id" => "https://" . $domain . "/!" . $value["uniqid"],
                        "url" => "https://" . $domain . "/!" . $value["uniqid"],
                        "published" => date(DATE_ATOM, strtotime($value["datetime"])),
                        "to" => [
                            "https://www.w3.org/ns/activitystreams#Public",
                        ],
                        "attributedTo" => "https://" . $domain . "/@" . $value["account"],
                        "content" => "".nl2br($value["ueuse"])."",
                    ),
                );
    
                $orderedItems[] = $activity;
            }
    
            $item = array(
                "type" => "OrderedCollection",
                "@context" => "https://www.w3.org/ns/activitystreams",
                "id" => "https://" . $domain . "/user/outbox/?actor=@" . $userid . "&page=true",
                "partOf" => "https://" . $domain . "/user/outbox/?actor=@" . $userid,
                "summary" => "outbox of " . $userid,
                "totalItems" => count($messages),
                "orderedItems" => $orderedItems,
            );
    
            echo json_encode($item, JSON_UNESCAPED_UNICODE);
        } else {
            $item = array(
                "type" => "item_not_found",
            );
            echo json_encode($item, JSON_UNESCAPED_UNICODE);
        }
    } else {
        $item = array(
            "type" => "user_not_found",
        );
        echo json_encode($item, JSON_UNESCAPED_UNICODE);
    }        

}else{
    header("HTTP/1.1 410 Gone");
}
?>