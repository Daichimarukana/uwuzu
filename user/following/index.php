<?php
require("../../function/function.php");

$serversettings_file = "../../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);
if(safetext($serversettings["serverinfo"]["server_activitypub"]) === "true"){
    header("Content-Type: application/activity+json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");

    $domain = $_SERVER['HTTP_HOST'];

    $user = safetext($_GET['actor']);

    $userid = str_replace('@','', str_replace('@'.$domain.'', '', $user));

    $item = array(
        "@context" => "https://www.w3.org/ns/activitystreams",
        "id" => "https://".$domain."/user/following/?actor=".$userid,
        "type" => "OrderedCollection",
        "totalItems" => 0,
        "orderedItems" => [],
    );

    echo json_encode($item, JSON_UNESCAPED_UNICODE);
}else{
    header("HTTP/1.1 410 Gone");
}
?>