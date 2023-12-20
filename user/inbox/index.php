<?php
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);
if(htmlspecialchars($serversettings["serverinfo"]["server_activitypub"], ENT_QUOTES, 'UTF-8') === "true"){
    header("Content-Type: application/json; charset=utf-8");

    $domain = $_SERVER['HTTP_HOST'];

    $user = htmlentities($_GET['actor']);

    $userid = str_replace('@','', str_replace('@'.$domain.'', '', $user));

    $item = array(
        "@context" => "https://www.w3.org/ns/activitystreams",
        "summary" => "inbox of ".$userid."",
        "type" => "OrderedCollection",
        "totalItems" => 0,
        "orderedItems" => [],
    );

    echo json_encode($item, JSON_UNESCAPED_UNICODE);
}else{
    header("HTTP/1.1 410 Gone");
}
?>