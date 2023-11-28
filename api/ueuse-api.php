<?php
header("Content-Type: application/json; charset=utf-8; Access-Control-Allow-Origin: *;");

if(isset($_GET['ueuseid'])) { 

$ueuseid = htmlentities($_GET['ueuseid']);


require('../db.php');

$datetime = array();
$pdo = null;

session_start();

    try {

        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

    } catch(PDOException $e) {

        // 接続エラーのときエラー内容を取得する
        $error_message[] = $e->getMessage();
    }


    if (!empty($pdo)) {

        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));   

        $ueuseQuery = $pdo->prepare("SELECT account, ueuse, uniqid, rpuniqid, datetime, photo1, photo2, video1, favorite, abi, abidate FROM ueuse WHERE uniqid = :ueuseid");
        $ueuseQuery->bindValue(':ueuseid', $ueuseid);
        $ueuseQuery->execute();
        $ueusedata = $ueuseQuery->fetch();
    }

if (empty($ueusedata)){
    $response = array(
        'error_code' => "ueuseid_not_found",
    );
}else{
    $userQuery = $pdo->prepare("SELECT username, userid, profile, role FROM account WHERE userid = :userid");
    $userQuery->bindValue(':userid', $ueusedata["account"]);
    $userQuery->execute();
    $userData = $userQuery->fetch();
    if ($userData) {
        $ueusedata['username'] = $userData['username'];
        $ueusedata['role'] = $userData['role'];
    }


    $favcnts = explode(',', $ueusedata["favorite"]);
    $ueusedata["favorite_cnt"] = count($favcnts)-1;

    $response = array(
        'userid' => htmlentities($ueusedata["account"]),
        'user_name' => htmlentities($ueusedata["username"]),
        'uniqid' => htmlentities($ueusedata["uniqid"]),
        'ueuse' => htmlentities($ueusedata["ueuse"]),
        'photo1' => htmlentities(str_replace('../', ''.$_SERVER['HTTP_HOST'].'/', $ueusedata["photo1"])),
        'photo2' => htmlentities(str_replace('../', ''.$_SERVER['HTTP_HOST'].'/', $ueusedata["photo2"])),
        'video1' => htmlentities(str_replace('../', ''.$_SERVER['HTTP_HOST'].'/', $ueusedata["video1"])),
        'favorite' => htmlentities($ueusedata["favorite"]),
        'favorite_cnt' => htmlentities($ueusedata["favorite_cnt"]),
        'datetime' => htmlentities($ueusedata["datetime"]),
        'abi' => htmlentities($ueusedata["abi"]),
        'abidatetime' => htmlentities($ueusedata["abidate"]),
    );
}
echo json_encode($response, JSON_UNESCAPED_UNICODE);;

}else{

    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
    );
     
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>