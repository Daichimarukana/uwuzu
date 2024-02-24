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
if(isset($_GET['userid'])) { 

$search = htmlentities($_GET['userid']);

$domain = $_SERVER['HTTP_HOST'];

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

        $userQuery = $pdo->prepare("SELECT username,userid,profile,datetime,follow,follower,iconname,headname FROM account WHERE userid = :userid");
        $userQuery->bindValue(':userid', $search);
        $userQuery->execute();
        $userdata = $userQuery->fetch();
    }
if (empty($userdata)){
    $response = array(
        'error_code' => "userid_not_found",
    );
}else{
    $followcnts = explode(',', $userdata["follow"]);
    $userdata["follow_cnt"] = count($followcnts)-1;

    $followercnts = explode(',', $userdata["follower"]);
    $userdata["follower_cnt"] = count($followercnts)-1;

    $response = array(
        'user_name' => decode_yajirushi(htmlspecialchars_decode($userdata["username"])),
        'user_id' => decode_yajirushi(htmlspecialchars_decode($userdata["userid"])),
        'profile' => decode_yajirushi(htmlspecialchars_decode($userdata["profile"])),
        'user_icon' => decode_yajirushi(htmlspecialchars_decode("https://".$domain."/".$userdata["iconname"])),
        'user_header' => decode_yajirushi(htmlspecialchars_decode("https://".$domain."/".$userdata["headname"])),
        'registered_date' => decode_yajirushi(htmlspecialchars_decode($userdata["datetime"])),
        'follow' => decode_yajirushi(htmlspecialchars_decode($userdata["follow"])),
        'follow_cnt' => decode_yajirushi(htmlspecialchars_decode($userdata["follow_cnt"])),
        'follower' => decode_yajirushi(htmlspecialchars_decode($userdata["follower"])),
        'follower_cnt' => decode_yajirushi(htmlspecialchars_decode($userdata["follower_cnt"])),
    );
}
echo json_encode($response, JSON_UNESCAPED_UNICODE);

}else{

    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
    );
     
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>