<?php
require("../../function/function.php");
$serversettings_file = "../../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

if(safetext($serversettings["serverinfo"]["server_activitypub"]) === "true"){
    header("Content-Type: application/json");
    header("charset=utf-8");
    header("Access-Control-Allow-Origin: *");

    $mojisizefile = "../../server/textsize.txt";

    $adminfile = safetext($serversettings["serverinfo"]["server_admin"]);

    $servernamefile = safetext($serversettings["serverinfo"]["server_name"]);

    $serverinfofile = '../../server/info.txt';
    $serverinfo = safetext(file_get_contents($serverinfofile));

    $contactfile = safetext($serversettings["serverinfo"]["server_admin_mailadds"]);

    $domain = $_SERVER['HTTP_HOST'];

    $softwarefile = "../../server/uwuzuinfo.txt";
    $softwaredata = safetext(file_get_contents($softwarefile));

    $onlyuser = safetext($serversettings["serverinfo"]["server_invitation"]);

    $server_head = safetext($serversettings["serverinfo"]["server_head"]);

    $softwaredata = explode( "\n", $softwaredata );
    $cnt = count( $softwaredata );
    for( $i=0;$i<$cnt;$i++ ){
        $uwuzuinfo[$i] = ($softwaredata[$i]);
    }

    if($onlyuser === "true"){
        $openregit = false;
    }elseif($onlyuser === "false"){
        $openregit = true;
    }else{
        $openregit = false;
    }

    $item = array(
        "uri" => $domain,
        "email" => $contactfile,
        "title" => "uwuzu",
        "version" =>str_replace("\r", '', $uwuzuinfo[1]),
        "thumbnail" => $server_head,
        "description" => $serverinfo,
    );

    echo json_encode($item, JSON_UNESCAPED_UNICODE);
}else{
    header("HTTP/1.1 410 Gone");
}
?>