<?php
require("../../function/function.php");
$serversettings_file = "../../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

if(safetext($serversettings["serverinfo"]["server_activitypub"]) === "true"){
    header("Content-Type: application/activity+json");
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

    $softwaredata = explode( "\n", $softwaredata );
    $cnt = count( $softwaredata );
    for( $i=0;$i<$cnt;$i++ ){
        $uwuzuinfo[$i] = ($softwaredata[$i]);
    }
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

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $result = $mysqli->query("SELECT userid FROM account ORDER BY datetime");

    /* 結果セットの行数を取得します */
    $count1 = $result->num_rows;

    $result2 = $mysqli->query("SELECT uniqid FROM ueuse ORDER BY datetime");

    /* 結果セットの行数を取得します */
    $count2 = $result2->num_rows;

    if($onlyuser === "true"){
        $openregit = false;
    }elseif($onlyuser === "false"){
        $openregit = true;
    }else{
        $openregit = false;
    }

    $item = array(
        "version" => "2.1",
        "software" => array(
            "name" => "uwuzu",
            "version" => "".str_replace("\r", '', $uwuzuinfo[1])."",
            "homepage" => "https://docs.uwuzu.xyz/",
            "repository" => "https://github.com/Daichimarukana/uwuzu",
        ),
        "protocols" => [
                "activitypub",
        ],
        "service" => [
            "inbound" => array(),
            "outbound" => array(),
        ],
        "openRegistrations" => $openregit,
        "usage" => [
            "users" => array(
                "total" => $count1,
            ),
            "localPosts" => $count2,
        ],
        "metadata" => [
            "nodeName" => $servernamefile,
            "nodeDescription" => $serverinfo,
            "maintainer" => array(
                "name" => $adminfile,
                "email" => $contactfile,
            ),
            "langs" => array(
                "ja",
            ),
            "tosUrl" => "https://".$domain."/rule/terms",
            "maxNoteTextLength" => (int)safetext(file_get_contents($mojisizefile)),
        ],
    );

    $item; // ループ内で $response にデータを追加

    echo json_encode($item, JSON_UNESCAPED_UNICODE);
}else{
    header("HTTP/1.1 410 Gone");
}
?>