<?php
$activitypub_file = "../../server/activitypub.txt";
if(file_get_contents($activitypub_file) === "true"){
    header("Content-Type: application/xml; charset=UTF-8");

    $domain = $_SERVER['HTTP_HOST'];

    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0">';
    echo '<Link rel="lrdd" type="application/xrd+xml" template="https://'.$domain.'/.well-known/webfinger?resource={uri}"/>';
    echo '</XRD>';
}else{
    header("HTTP/1.1 410 Gone");
}
?>