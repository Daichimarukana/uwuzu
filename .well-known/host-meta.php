<?php
$serversettings_file = __DIR__ . "/../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

require(__DIR__ . '/../db.php');
require(__DIR__ . "/../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

if(safetext($serversettings["serverinfo"]["server_activitypub"]) === "true"){
    header("Content-Type: application/xml; charset=UTF-8");
    header("Access-Control-Allow-Origin: *");

    $domain = $_SERVER['HTTP_HOST'];

    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0">';
    echo '<Link rel="lrdd" type="application/xrd+xml" template="https://'.$domain.'/.well-known/webfinger?resource={uri}"/>';
    echo '</XRD>';
}else{
    header("HTTP/1.1 410 Gone");
}
?>