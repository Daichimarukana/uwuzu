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

function decode_yajirushi($postText){
    $postText = str_replace('&larr;', '←', $postText);
    $postText = str_replace('&darr;', '↓', $postText);
    $postText = str_replace('&uarr;', '↑', $postText);
    $postText = str_replace('&rarr;', '→', $postText);
    return $postText;
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
        $token = safetext($_GET['token']);
    }else{
        $post_json = json_decode($Get_Post_Json, true);
        if(isset($post_json["token"])){
            $token = safetext($post_json["token"]);
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
                $follow_userid = safetext($_GET['userid']);
            }elseif(!(empty($post_json["userid"]))){
                $follow_userid = safetext($post_json["userid"]);
            }

            if(!(empty($follow_userid))){
                $DataQuery = $pdo->prepare("SELECT username,userid,follow,follower FROM account WHERE userid = :userid");
                $DataQuery->bindValue(':userid', $follow_userid);
                $DataQuery->execute();
                $Follow_userdata = $DataQuery->fetch();

                $userid = $userData["userid"];

                if(!(empty($Follow_userdata))){
                    if(!($userid == $Follow_userdata['userid'])){
                        $followerList = explode(',', $Follow_userdata['follower']);
                        if (!(in_array($userid, $followerList))) {
                            // 自分が相手をフォローしていない場合、相手のfollowerカラムと自分のfollowカラムを更新
                            $followerList[] = $userid;
                            $newFollowerList = implode(',', $followerList);
        
                            // UPDATE文を実行してフォロー情報を更新
                            $updateQuery = $pdo->prepare("UPDATE account SET follower = :follower WHERE userid = :userid");
                            $updateQuery->bindValue(':follower', $newFollowerList, PDO::PARAM_STR);
                            $updateQuery->bindValue(':userid', $Follow_userdata['userid'], PDO::PARAM_STR);
                            $res = $updateQuery->execute();
        
                            // 自分のfollowカラムを更新
                            $updateQuery = $pdo->prepare("UPDATE account SET follow = CONCAT_WS(',', follow, :follow) WHERE userid = :userid");
                            $updateQuery->bindValue(':follow', $Follow_userdata["userid"], PDO::PARAM_STR);
                            $updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
                            $res_follow = $updateQuery->execute();
                            
                            $datetime = date("Y-m-d H:i:s");
                            $pdo->beginTransaction();
        
                            try {
                                $fromuserid = safetext($userid);
                                $touserid = safetext($Follow_userdata["userid"]);
                                $datetime = safetext(date("Y-m-d H:i:s"));
                                $msg = safetext("".$userid."さんにフォローされました。");
                                $title = safetext("🎉".$userid."さんにフォローされました！🎉");
                                $url = safetext("/@" . $userid . "");
                                $userchk = safetext('none');
        
                                // 通知用SQL作成
                                $stmt = $pdo->prepare("INSERT INTO notification (fromuserid, touserid, msg, url, datetime, userchk, title) VALUES (:fromuserid, :touserid, :msg, :url, :datetime, :userchk, :title)");
        
                                $stmt->bindParam(':fromuserid', $fromuserid, PDO::PARAM_STR);
                                $stmt->bindParam(':touserid', $touserid, PDO::PARAM_STR);
                                $stmt->bindParam(':msg', $msg, PDO::PARAM_STR);
                                $stmt->bindParam(':url', $url, PDO::PARAM_STR);
                                $stmt->bindParam(':userchk', $userchk, PDO::PARAM_STR);
                                $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        
                                $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);
        
                                // SQLクエリの実行
                                $res = $stmt->execute();
        
                                // コミット
                                $res = $pdo->commit();
        
                            } catch(Exception $e) {
        
                                // エラーが発生した時はロールバック
                                $pdo->rollBack();
                            }
        
                            if ($res && $res_follow) {
                                //フォロー完了
                                $response = array(
                                    'userid' => decode_yajirushi(htmlspecialchars_decode($Follow_userdata["userid"])),
                                );
                                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                            } else {
                                $err = "db_error_".$e->getMessage();
                                $response = array(
                                    'error_code' => $err,
                                );
                                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                            }
                            $stmt = null;
                        }else{
                            $err = "already_been_completed";
                            $response = array(
                                'error_code' => $err,
                            );
                            echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        }
                    }else{
                        $err = "you_cant_it_to_yourself";
                        $response = array(
                            'error_code' => $err,
                        );
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    }
                }else{
                    $err = "critical_error_userdata_not_found";
                    $response = array(
                        'error_code' => $err,
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                }
            }else{
                $err = "input_not_found";
                $response = array(
                    'error_code' => $err,
                );
                 
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
        }
    }
}else{
    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
    );
     
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>