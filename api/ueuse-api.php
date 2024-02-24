<?php
header("Content-Type: application/json");
header("charset=utf-8");
header("Access-Control-Allow-Origin: *");
function decode_yajirushi($postText){
    $postText = str_replace('&larr;', '←', $postText);
    $postText = str_replace('&darr;', '↓', $postText);
    $postText = str_replace('&uarr;', '↑', $postText);
    $postText = str_replace('&rarr;', '→', $postText);
    return $postText;
}
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
        'userid' => decode_yajirushi(htmlspecialchars_decode($ueusedata["account"])),
        'user_name' => decode_yajirushi(htmlspecialchars_decode($ueusedata["username"])),
        'uniqid' => decode_yajirushi(htmlspecialchars_decode($ueusedata["uniqid"])),
        'ueuse' => decode_yajirushi(htmlspecialchars_decode($ueusedata["ueuse"])),
        'photo1' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', ''.$_SERVER['HTTP_HOST'].'/', $ueusedata["photo1"]))),
        'photo2' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', ''.$_SERVER['HTTP_HOST'].'/', $ueusedata["photo2"]))),
        'video1' => decode_yajirushi(htmlspecialchars_decode(str_replace('../', ''.$_SERVER['HTTP_HOST'].'/', $ueusedata["video1"]))),
        'favorite' => decode_yajirushi(htmlspecialchars_decode($ueusedata["favorite"])),
        'favorite_cnt' => decode_yajirushi(htmlspecialchars_decode($ueusedata["favorite_cnt"])),
        'datetime' => decode_yajirushi(htmlspecialchars_decode($ueusedata["datetime"])),
        'abi' => decode_yajirushi(htmlspecialchars_decode($ueusedata["abi"])),
        'abidatetime' => decode_yajirushi(htmlspecialchars_decode($ueusedata["abidate"])),
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