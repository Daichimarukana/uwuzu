<?php
$activitypub_file = "../../server/activitypub.txt";
if(file_get_contents($activitypub_file) === "true"){
    header("Content-Type: application/json; charset=utf-8");

    $mojisizefile = "../../server/textsize.txt";

    $adminfile = "../../server/admininfo.txt";

    $servernamefile = "../../server/servername.txt";

    $serverinfofile = '../../server/info.txt';
    $serverinfo = file_get_contents($serverinfofile);

    $contactfile = "../../server/contact.txt";

    $domain = $_SERVER['HTTP_HOST'];

    $softwarefile = "../../server/uwuzuinfo.txt";
    $softwaredata = file_get_contents($softwarefile);

    $onlyuserfile = "../../server/onlyuser.txt";
    $onlyuser = file_get_contents($onlyuserfile);

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
            "nodeName" => file_get_contents($servernamefile),
            "nodeDescription" => $serverinfo,
            "maintainer" => array(
                "name" => file_get_contents($adminfile),
                "email" => file_get_contents($contactfile),
            ),
            "langs" => array(
                "ja",
            ),
            "tosUrl" => "https://".$domain."/rule/terms",
            "maxNoteTextLength" => (int)htmlspecialchars(file_get_contents($mojisizefile), ENT_QUOTES, 'UTF-8'),
        ],
    );

    $item; // ループ内で $response にデータを追加

    echo json_encode($item, JSON_UNESCAPED_UNICODE);
}
?>