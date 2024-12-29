<?php
require("../../function/function.php");

$serversettings_file = "../../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);
if(safetext($serversettings["serverinfo"]["server_activitypub"]) == "true"){
    header("Content-Type: application/activity+json; charset=utf-8");
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

    $ueuse = safetext($_GET['ueuse']);

    if( !empty($pdo) ) {
            
        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));

        $messageQuery = $dbh->prepare("SELECT * FROM ueuse WHERE uniqid = :ueuse limit 1");
        $messageQuery->bindValue(':ueuse', $ueuse);
        $messageQuery->execute();
        $message_array = $messageQuery->fetchAll();
        
        $messages = array();
        foreach ($message_array as $row) {
            $messages[] = $row;
        }
    }

    if (!empty($messages)) {
        $orderedItems = array();
    
        foreach ($messages as $value) {
            if ($value["nsfw"] === "true") {
                $value["sensitive"] = true;
            } else {
                $value["sensitive"] = false;
            }
            $orderedItem = array(
                "@context" => "https://www.w3.org/ns/activitystreams",
                "id" => "https://" . $domain . "/ueuse/activity/?ueuse=" . $value["uniqid"],
                "actor" => "https://" . $domain . "/actor/?actor=@" . $value["account"],
                "type" => "Create",
                "published" => date(DATE_ATOM, strtotime($value["datetime"])),
                "to" => [
                    "https://" . $domain . "/followers",
                    "https://www.w3.org/ns/activitystreams#Public",
                ],
                "object" => array(
                    "type" => "Note",
                    "@context" => "https://www.w3.org/ns/activitystreams",
                    "id" => "https://" . $domain . "/ueuse/activity/?ueuse=" . $value["uniqid"],
                    "url" => "https://" . $domain . "/ueuse/activity/?ueuse=" . $value["uniqid"],
                    "published" => date(DATE_ATOM, strtotime($value["datetime"])),
                    "to" => [
                        "https://" . $domain . "/followers",
                        "https://www.w3.org/ns/activitystreams#Public",
                    ],
                    "attributedTo" => "https://" . $domain . "/@" . $value["account"],
                    "content" => nl2br($value["ueuse"]),
                    "inReplyTo" => null,
                    "attachment" => [],
                    "sensitive" => $value["sensitive"],
                    "tag" => [],
                ),
            );
    
            $orderedItems[] = $orderedItem;
        }
    
        echo json_encode($orderedItems, JSON_UNESCAPED_UNICODE);
    } else {
        $item = array(
            "type" => "item_not_found",
        );
        echo json_encode($item, JSON_UNESCAPED_UNICODE);
    }
    
}else{
    header("HTTP/1.1 410 Gone");
}
?>