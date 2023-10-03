<?php
header("Content-Type: application/json; charset=utf-8");

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
        'user_name' => htmlentities($userdata["username"]),
        'user_id' => htmlentities($userdata["userid"]),
        'profile' => htmlentities($userdata["profile"]),
        'user_icon' => htmlentities("https://".$domain."/".$userdata["iconname"]),
        'user_header' => htmlentities("https://".$domain."/".$userdata["headname"]),
        'registered_date' => htmlentities($userdata["datetime"]),
        'follow' => htmlentities($userdata["follow"]),
        'follow_cnt' => htmlentities($userdata["follow_cnt"]),
        'follower' => htmlentities($userdata["follower"]),
        'follower_cnt' => htmlentities($userdata["follower_cnt"]),
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