<?php

$domain = $_SERVER['HTTP_HOST'];
require('../../db.php');
//関数呼び出し
//- Base64_mime
require('../../function/function.php');
//投稿及び返信レート制限↓(分):デフォで60件/分まで
$max_ueuse_rate_limit = 60;

$mojisizefile = "../../server/textsize.txt";

$banurldomainfile = "../../server/banurldomain.txt";
$banurl_info = file_get_contents($banurldomainfile);
$banurl = preg_split("/\r\n|\n|\r/", $banurl_info);

header("Content-Type: application/json");
header("charset=utf-8");
header("Access-Control-Allow-Origin: *");

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}
function decode_yajirushi($postText){
    $postText = str_replace('&larr;', '←', $postText);
    $postText = str_replace('&darr;', '↓', $postText);
    $postText = str_replace('&uarr;', '↑', $postText);
    $postText = str_replace('&rarr;', '→', $postText);
    return $postText;
}
function get_mentions_userid($postText) {
    // @useridを検出する
    $usernamePattern = '/@(\w+)/';
    $mentionedUsers = [];

    preg_replace_callback($usernamePattern, function($matches) use (&$mentionedUsers) {
        $mention_username = $matches[1];

        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));
    
        $mention_userQuery = $dbh->prepare("SELECT username, userid FROM account WHERE userid = :userid");
        $mention_userQuery->bindValue(':userid', $mention_username);
        $mention_userQuery->execute();
        $mention_userData = $mention_userQuery->fetch();   
        
        if (!empty($mention_userData)) {
            $mentionedUsers[] = $mention_username;
        }
    }, $postText);

    return $mentionedUsers;
}

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
        $token = htmlentities($_GET['token'], ENT_QUOTES, 'UTF-8', false);
    }else{
        $post_json = json_decode($Get_Post_Json, true);
        if(isset($post_json["token"])){
            $token = htmlentities($post_json["token"], ENT_QUOTES, 'UTF-8', false);
        }else{
            $err = "input_not_found";
            $response = array(
                'error_code' => $err,
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    if($token == ""){
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    session_start();

    if( !empty($pdo) ) {
        $userQuery = $pdo->prepare("SELECT username, userid, role, follow, follower FROM account WHERE token = :token");
        $userQuery->bindValue(':token', $token);
        $userQuery->execute();
        $userData = $userQuery->fetch();

        if(empty($userData["userid"])){
            $err = "token_invalid";
            $response = array(
                'error_code' => $err,
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }elseif($userData["role"] === "ice"){
            $err = "this_account_has_been_frozen";
            $response = array(
                'error_code' => $err,
            );
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }else{
            //本文取得
            if(!(empty($_GET['userid']))){
                $unfollow_userid = htmlentities($_GET['userid'], ENT_QUOTES, 'UTF-8', false);
            }elseif(!(empty($post_json["userid"]))){
                $unfollow_userid = htmlentities($post_json["userid"], ENT_QUOTES, 'UTF-8', false);
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
                        $followerList = explode(',', $Follow_userdata['follower']);
                        if (in_array($userid, $followerList)) {
                            // 自分が相手をフォローしている場合、相手のfollowerカラムと自分のfollowカラムを更新
                            $followerList = array_diff($followerList, array($userid));
                            $newFollowerList = implode(',', $followerList);

                            // UPDATE文を実行してフォロー情報を更新
                            $updateQuery = $pdo->prepare("UPDATE account SET follower = :follower WHERE userid = :userid");
                            $updateQuery->bindValue(':follower', $newFollowerList, PDO::PARAM_STR);
                            $updateQuery->bindValue(':userid', $Follow_userdata['userid'], PDO::PARAM_STR);
                            $res = $updateQuery->execute();

                            $myflwlist = explode(',', $myfollowlist);
                            $delfollowList = array_diff($myflwlist, array($Follow_userdata['userid']));
                            $deluserid = implode(',', $delfollowList);

                            // 自分のfollowカラムから相手のユーザーIDを削除
                            $updateQuery = $pdo->prepare("UPDATE account SET follow = :follow WHERE userid = :userid");
                            $updateQuery->bindValue(':follow', $deluserid, PDO::PARAM_STR);
                            $updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
                            $res_follow = $updateQuery->execute();

                            if ($res && $res_follow) {
                                $response = array(
                                    'userid' => decode_yajirushi(htmlspecialchars_decode($Follow_userdata["userid"])),
                                    'success' => true
                                );
                                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                                exit;
                            } else {
                                $err = "db_error_".$e->getMessage();
                                $response = array(
                                    'error_code' => $err,
                                );
                                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                                exit;
                            }

                            $stmt = null;
                        }else{
                            $err = "already_been_completed";
                            $response = array(
                                'error_code' => $err,
                            );
                            echo json_encode($response, JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                    }else{
                        $err = "you_cant_it_to_yourself";
                        $response = array(
                            'error_code' => $err,
                        );
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                }else{
                    $err = "critical_error_userdata_not_found";
                    $response = array(
                        'error_code' => $err,
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }else{
                $err = "input_not_found";
                $response = array(
                    'error_code' => $err,
                );
                 
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }
            
        }
    }
}else{
    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
    );
     
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
?>