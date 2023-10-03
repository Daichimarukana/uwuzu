<?php
$activitypub_file = "../../server/activitypub.txt";
if(file_get_contents($activitypub_file) === "true"){
    header("Content-Type: application/json; charset=utf-8");

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
}
?>