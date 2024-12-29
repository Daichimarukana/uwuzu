<?php
require("../function/function.php");

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

$mojisizefile = "../server/textsize.txt";

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$serverinfofile = '../server/info.txt';
$serverinfo = file_get_contents($serverinfofile);

$domain = $_SERVER['HTTP_HOST'];

$softwarefile = "../server/uwuzuinfo.txt";
$softwaredata = file_get_contents($softwarefile);

$softwaredata = explode( "\n", $softwaredata );
$cnt = count( $softwaredata );
for( $i=0;$i<$cnt;$i++ ){
    $uwuzuinfo[$i] = ($softwaredata[$i]);
}


    require('../db.php');

    $datetime = array();
    $pdo = null;

    session_start();

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
    
    /*-------------------*/
    $sql = "SELECT title, note, account, datetime FROM notice ORDER BY datetime DESC";
    $notice_array = $pdo->query($sql);

    while ($row = $notice_array->fetch(PDO::FETCH_ASSOC)) {
    
        $notices[] = $row;
    }

    if(safetext($serversettings["serverinfo"]["server_invitation"]) === "true"){
        $invitation_code = true;
    }else{
        $invitation_code = false;
    }
    if(safetext($serversettings["serverinfo"]["server_account_migration"]) === "true"){
        $account_migration = true;
    }else{
        $account_migration = false;
    }

        if(!(empty($notices))){
            foreach ($notices as $value) {
                $notices = array(
                    "title" => decode_yajirushi(htmlspecialchars_decode($value['title'])),
                    "note" => decode_yajirushi(htmlspecialchars_decode($value['note'])),
                    "editor" => decode_yajirushi(htmlspecialchars_decode($value['account'])),
                    "datetime" => decode_yajirushi(htmlspecialchars_decode($value['datetime'])),
                );

                $notice[] = $notices;
            }
        }else{
            $notice[] = "";
        }

        $item = [
            "server_info" => array(
                "server_name" => safetext($serversettings["serverinfo"]["server_name"]),
                "server_icon" => safetext($serversettings["serverinfo"]["server_icon"]),
                "server_description" => $serverinfo,

                "adminstor" => array(
                    "name" => safetext($serversettings["serverinfo"]["server_admin"]),
                    "email" => safetext($serversettings["serverinfo"]["server_admin_mailadds"]),
                ),

                "terms_url" => "https://".$domain."/rule/terms",
                "privacy_policy_url" => "https://".$domain."/rule/privacypolicy",
                "max_ueuse_length" => (int)safetext(file_get_contents($mojisizefile)),

                "invitation_code" => $invitation_code,
                "account_migration" => $account_migration,

                "usage" => [
                    "users" => $count1,
                    "ueuse" => $count2,
                ],

            ),

            "software" => array(
                "name" => "uwuzu",
                "version" => "".str_replace("\r", '', $uwuzuinfo[1])."",
                "repository" => "https://github.com/Daichimarukana/uwuzu",
            ),

            "server_notice" => $notice,
        ];

        $response = $item; // ループ内で $response にデータを追加

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

?>