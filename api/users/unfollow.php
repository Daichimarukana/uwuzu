<?php

$domain = $_SERVER['HTTP_HOST'];
require(__DIR__ . '/../../db.php');
//関数呼び出し
//- Base64_mime
require(__DIR__ . '/../../function/function.php');
blockedIP($_SERVER['REMOTE_ADDR']);

$mojisizefile = __DIR__ . "/../../server/textsize.txt";

$banurldomainfile = __DIR__ . "/../../server/banurldomain.txt";
$banurl_info = file_get_contents($banurldomainfile);
$banurl = preg_split("/\r\n|\n|\r/", $banurl_info);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");




$pdo = null;
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

$Get_Post_Json = file_get_contents("php://input");
if(isset($_GET['token']) || (!(empty($Get_Post_Json)))) { 
    //トークン取得
    if(!(empty($_GET['token']))){
        $token = safetext($_GET['token']);
    }else{
        $post_json = json_decode($Get_Post_Json, true);
        if(isset($post_json["token"])){
            $token = safetext($post_json["token"]);
        }else{
            $err = "input_not_found";
            $response = array(
                'error_code' => $err,
                'success' => false
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    if($token == ""){
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
            'success' => false
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    session_start();

    if( !empty($pdo) ) {
        $AuthData = APIAuth($pdo, $token, "write:follow");
        if($AuthData[0] === true){
            $userData = $AuthData[2];
            //本文取得
            if(!(empty($_GET['userid']))){
                $unfollow_userid = safetext($_GET['userid']);
            }elseif(!(empty($post_json["userid"]))){
                $unfollow_userid = safetext($post_json["userid"]);
            }

            if(!(empty($unfollow_userid))){
                $DataQuery = $pdo->prepare("SELECT username,userid,follow,follower FROM account WHERE userid = :userid");
                $DataQuery->bindValue(':userid', $unfollow_userid);
                $DataQuery->execute();
                $Follow_userdata = $DataQuery->fetch();

                $userid = $userData["userid"];
                $myfollowlist = $userData["follow"];

                if(!(empty($Follow_userdata))){
                    if(!($userid == $Follow_userdata['userid'])){
                        $res = unfollow_user($pdo, $Follow_userdata['userid'], $userid);
                        if($res === true){
                            //フォロー完了
                            $response = array(
                                'userid' => decode_yajirushi(htmlspecialchars_decode($Follow_userdata["userid"])),
                                'success' => true
                            );
                            echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        }else{
                            $err = "could_not_complete";
                            $response = array(
                                'error_code' => $err,
                                'success' => false
                            );
                            echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        }
                    }else{
                        $err = "you_cant_it_to_yourself";
                        $response = array(
                            'error_code' => $err,
                            'success' => false
                        );
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                }else{
                    $err = "critical_error_userdata_not_found";
                    $response = array(
                        'error_code' => $err,
                        'success' => false
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }else{
                $err = "input_not_found";
                $response = array(
                    'error_code' => $err,
                    'success' => false
                );
                 
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }
        }else{
            $err = $AuthData[1];
            $response = array(
                'error_code' => $err,
                'success' => false
            );
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
    }
}else{
    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
        'success' => false
    );
     
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
?>