<?php
$serversettings_file = "../../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

require('../../db.php');
require("../../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

if(safetext($serversettings["serverinfo"]["server_activitypub"]) === "true"){
    header("Content-Type: application/activity+json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");

    $domain = $_SERVER['HTTP_HOST'];

    $item = array(
        "links" => [
            array(
                "rel" => "http://nodeinfo.diaspora.software/ns/schema/2.1",
                "href" => "https://".$domain."/nodeinfo/2.1",
            ),
        ],
    );

    echo json_encode($item, JSON_UNESCAPED_UNICODE);
}else{
    header("HTTP/1.1 410 Gone");
}
?>