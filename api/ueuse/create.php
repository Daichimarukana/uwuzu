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
        $userQuery = $pdo->prepare("SELECT username, userid, role FROM account WHERE token = :token");
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
            if(!(empty($_GET['text']))){
                $ueuse = safetext($_GET['text']);
            }elseif(!(empty($post_json["text"]))){
                $ueuse = safetext($post_json["text"]);
            }
            //リプライ先取得
            if(!(empty($_GET['replyid']))){
                $replyid = safetext($_GET['replyid']);
            }elseif(!(empty($post_json["replyid"]))){
                $replyid = safetext($post_json["replyid"]);
            }else{
                $replyid = "";
            }

            //NSFWの有無
            if(!(empty($_GET['nsfw']))){
                $nsfwchk = safetext($_GET['nsfw']);
                if($nsfwchk == "true"){
                    $nsfw = "true";
                }else{
                    $nsfw = "false";
                }
            }elseif(!(empty($post_json["nsfw"]))){
                $nsfwchk = safetext($post_json["nsfw"]);
                if($nsfwchk == true){
                    $nsfw = "true";
                }else{
                    $nsfw = "false";
                }
            }else{
                $nsfw = "false";
            }
            
            //Base64での画像送信の確認(POSTのみ&デコードは関数(Base64_mime)側でやってくれる)
            $img_uid = safetext($userData["userid"]);//UserID必須
            if(!(empty($post_json["image1"]))){
                $image1 = safetext($post_json["image1"]);
                $UploadPath1 = base64_mime($image1,$img_uid);
                if($UploadPath1 == false){
                    $UploadPath1 = "none";
                }
            }else{
                $UploadPath1 = "none";
            }
            if(!(empty($post_json["image2"]))){
                $image2 = safetext($post_json["image2"]);
                $UploadPath2 = base64_mime($image2,$img_uid);
                if($UploadPath2 == false){
                    $UploadPath2 = "none";
                }
            }else{
                $UploadPath2 = "none";
            }
            if(!(empty($post_json["image3"]))){
                $image3 = safetext($post_json["image3"]);
                $UploadPath3 = base64_mime($image3,$img_uid);
                if($UploadPath3 == false){
                    $UploadPath3 = "none";
                }
            }else{
                $UploadPath3 = "none";
            }
            if(!(empty($post_json["image4"]))){
                $image4 = safetext($post_json["image4"]);
                $UploadPath4 = base64_mime($image4,$img_uid);
                if($UploadPath4 == false){
                    $UploadPath4 = "none";
                }
            }else{
                $UploadPath4 = "none";
            }
            //ここまで-----------------------------------------

            $old_datetime = date("Y-m-d H:i:00");
            $now_datetime = date("Y-m-d H:i:00",strtotime("+1 minute"));
            $rate_Query = $pdo->prepare("SELECT * FROM ueuse WHERE account = :userid AND TIME(datetime) BETWEEN :old_datetime AND :now_datetime");
            $rate_Query->bindValue(':userid', $userData["userid"]);
            $rate_Query->bindValue(':old_datetime', $old_datetime);
            $rate_Query->bindValue(':now_datetime', $now_datetime);
            $rate_Query->execute();
            $rate_count = $rate_Query->rowCount();
            if(!($rate_count > $max_ueuse_rate_limit-1)){
                if( empty($ueuse) ) {
                    $err = "input_not_found";
                    $response = array(
                        'error_code' => $err,
                    ); 
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                } else {
                    // 文字数を確認
                    if( (int)safetext(file_get_contents($mojisizefile)) < mb_strlen($ueuse, 'UTF-8') ) {
                        $err = "content_to_".safetext(file_get_contents($mojisizefile))."_characters";
                        $response = array(
                            'error_code' => $err,
                        );
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    // 禁止url確認
                    for($i = 0; $i < count($banurl); $i++) {
                        if(!($banurl[$i] == "")){
                            if (false !== strpos($ueuse, 'https://'.$banurl[$i])) {
                                $err = "contains_prohibited_url";
                                $response = array(
                                    'error_code' => $err,
                                );
                                
                                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                                exit;
                            }
                        }
                    }
                }
                if(!(empty($replyid))){
                    $rpChkQuery = $pdo->prepare("SELECT * FROM ueuse WHERE uniqid = :rpuniqid");
                    $rpChkQuery->bindValue(':rpuniqid', $replyid);
                    $rpChkQuery->execute();
                    $rpChkcount = $rpChkQuery->rowCount();
                    if(empty($rpChkcount)){
                        $err = "no_reply_destination";
                        $response = array(
                            'error_code' => $err,
                        );
                        
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                }
        
                // 書き込み日時を取得
                $username = safetext($userData["username"]);
                $userid = safetext($userData["userid"]);
                $datetime = safetext(date("Y-m-d H:i:s"));
                $uniqid = safetext(createUniqId());
                $abi = "none";
                $nones = "none";

                // トランザクション開始
                $pdo->beginTransaction();

                try {

                    // SQL作成
                    $stmt = $pdo->prepare("INSERT INTO ueuse (username, account, uniqid, rpuniqid, ueuse, photo1, photo2, photo3, photo4, video1, datetime, abi, nsfw) VALUES (:username, :account, :uniqid, :rpuniqid, :ueuse, :photo1, :photo2, :photo3, :photo4, :video1, :datetime, :abi, :nsfw)");
            
                    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                    $stmt->bindParam(':account', $userid, PDO::PARAM_STR);
                    $stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
                    $stmt->bindParam(':rpuniqid', $replyid, PDO::PARAM_STR);
                    $stmt->bindParam(':ueuse', $ueuse, PDO::PARAM_STR);

                    $stmt->bindParam(':photo1', $UploadPath1, PDO::PARAM_STR);
                    $stmt->bindParam(':photo2', $UploadPath2, PDO::PARAM_STR);
                    $stmt->bindParam(':photo3', $UploadPath3, PDO::PARAM_STR);
                    $stmt->bindParam(':photo4', $UploadPath4, PDO::PARAM_STR);
                    $stmt->bindParam(':video1', $nones, PDO::PARAM_STR);

                    $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

                    $stmt->bindParam(':abi', $abi, PDO::PARAM_STR);
                    $stmt->bindParam(':nsfw', $nsfw, PDO::PARAM_STR);

                    // SQLクエリの実行
                    $res = $stmt->execute();

                    // コミット
                    $res = $pdo->commit();

                    $mentionedUsers = array_unique(get_mentions_userid($ueuse));

                    foreach ($mentionedUsers as $mentionedUser) {
                    
                        $pdo->beginTransaction();

                        try {
                            $fromuserid = safetext($userid);
                            $touserid = safetext($mentionedUser);
                            $datetime = safetext(date("Y-m-d H:i:s"));
                            $msg = "" . $ueuse . "";
                            $title = "" . safetext($username) . "さんにメンションされました！";
                            $url = "/!" . safetext($uniqid) . "";
                            $userchk = 'none';

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
                
                    }

                } catch(Exception $e) {

                    // エラーが発生した時はロールバック
                    $pdo->rollBack();
                }

                if( $res ) {
                    $response = array(
                        'uniqid' => decode_yajirushi(htmlspecialchars_decode($uniqid)),
                        'userid' => decode_yajirushi(htmlspecialchars_decode($userid)),
                    );
                    
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                } else {
                    $err = "db_error_".$e->getMessage();
                    $response = array(
                        'error_code' => $err,
                    );
                    
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                }

                // プリペアドステートメントを削除
                $stmt = null;
            }else{
                $err = "over_rate_limit";
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