<?php
$serversettings_file = "../../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);


if(htmlspecialchars($serversettings["serverinfo"]["server_activitypub"], ENT_QUOTES, 'UTF-8') === "true"){
    header("Content-Type: application/xml");
    header("charset=UTF-8");
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