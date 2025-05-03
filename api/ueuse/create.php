<?php

$domain = $_SERVER['HTTP_HOST'];
require('../../db.php');
//関数呼び出し
//- Base64_mime
require('../../function/function.php');
blockedIP($_SERVER['REMOTE_ADDR']);

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
            }else{
                $err = "input_not_found";
                $response = array(
                    'error_code' => $err,
                );
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }
            //リプライ先取得
            if(!(empty($_GET['replyid']))){
                $replyid = safetext($_GET['replyid']);
            }elseif(!(empty($post_json["replyid"]))){
                $replyid = safetext($post_json["replyid"]);
            }else{
                $replyid = "";
            }
            //リユーズ先取得
            if(!(empty($_GET['reuseid']))){
                $reuseid = safetext($_GET['reuseid']);
            }elseif(!(empty($post_json["reuseid"]))){
                $reuseid = safetext($post_json["reuseid"]);
            }else{
                $reuseid = "";
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
            
            if(!(empty($post_json["image1"]))){
                $imageData = base64_decode($post_json["image1"],true);
            
                $tmpFilePath = tempnam(sys_get_temp_dir(), 'upload_'.createUniqId());
                file_put_contents($tmpFilePath, $imageData);
            
                $Img1Files = [
                    'name' => 'upload.png',
                    'type' => check_mime($tmpFilePath),
                    'tmp_name' => $tmpFilePath,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($imageData),
                ];
            } else {
                $Img1Files = array();
            }

            if(!(empty($post_json["image2"]))){
                $imageData = base64_decode($post_json["image2"],true);
            
                $tmpFilePath = tempnam(sys_get_temp_dir(), 'upload_'.createUniqId());
                file_put_contents($tmpFilePath, $imageData);
            
                $Img2Files = [
                    'name' => 'upload.png',
                    'type' => check_mime($tmpFilePath),
                    'tmp_name' => $tmpFilePath,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($imageData),
                ];
            } else {
                $Img2Files = array();
            }

            if(!(empty($post_json["image3"]))){
                $imageData = base64_decode($post_json["image3"],true);
            
                $tmpFilePath = tempnam(sys_get_temp_dir(), 'upload_'.createUniqId());
                file_put_contents($tmpFilePath, $imageData);
            
                $Img3Files = [
                    'name' => 'upload.png',
                    'type' => check_mime($tmpFilePath),
                    'tmp_name' => $tmpFilePath,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($imageData),
                ];
            } else {
                $Img3Files = array();
            }

            if(!(empty($post_json["image4"]))){
                $imageData = base64_decode($post_json["image4"],true);
            
                $tmpFilePath = tempnam(sys_get_temp_dir(), 'upload_'.createUniqId());
                file_put_contents($tmpFilePath, $imageData);
            
                $Img4Files = [
                    'name' => 'upload.png',
                    'type' => check_mime($tmpFilePath),
                    'tmp_name' => $tmpFilePath,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($imageData),
                ];
            } else {
                $Img4Files = array();
            }
            
            $settingsJson = getUserData($pdo, $userData["userid"])["other_settings"];
            if(!(empty($settingsJson))){
                $isAIBWM = val_OtherSettings("isAIBlockWaterMark", $settingsJson);
            }else{
                $isAIBWM = false;
            }

            $video1 = array();

            $ueuse_result = send_ueuse($userData["userid"],$replyid,$reuseid,$ueuse,$Img1Files,$Img2Files,$Img3Files,$Img4Files,$video1,$nsfw,$isAIBWM, "../");


            if($ueuse_result[0] == true){
                $response = array(
                    'uniqid' => decode_yajirushi(htmlspecialchars_decode($ueuse_result[1])),
                    'userid' => decode_yajirushi(htmlspecialchars_decode($userData["userid"])),
                );
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }else{
                $errcode = $ueuse_result[1][0];
                switch (true) {
                    case strpos($errcode, 'ACCOUNT_HAS_BEEN_FROZEN' ) !==false:
                        $err = "this_account_has_been_frozen";
                        break;
                    case strpos($errcode, 'INPUT_PLEASE' ) !==false:
                        $err = "input_not_found";
                        break;
                    case strpos($errcode, 'INPUT_OVER_MAX_COUNT' ) !==false:
                        $err = "content_to_".safetext(file_get_contents($mojisizefile))."_characters";
                        break;
                    case strpos($errcode, 'INPUT_CONTAINS_PROHIBITED_URL' ) !==false: 
                        $err = "contains_prohibited_url";
                        break;
                    case strpos($errcode, 'FILE_DEKASUGUI_PHP_INI_KAKUNIN' ) !==false:
                        $err = "upload_error";
                        break;
                    case strpos($errcode, 'FILE_DEKASUGUI_HTML_KAKUNIN' ) !==false:
                        $err = "upload_error";
                        break;
                    case strpos($errcode, 'FILE_SUKOSHIDAKE_UPLOAD' ) !==false:
                        $err = "upload_error";
                        break;
                    case strpos($errcode, 'FILE_UPLOAD_DEKINAKATTA' ) !==false:
                        $err = "upload_error";
                        break;
                    case strpos($errcode, 'TMP_FOLDER_NAI' ) !==false:
                        $err = "upload_error";
                        break;
                    case strpos($errcode, 'FILE_KAKIKOMI_SIPPAI' ) !==false:
                        $err = "upload_error";
                        break;
                    case strpos($errcode, 'PHPINFO()_KAKUNIN' ) !==false:
                        $err = "upload_error";
                        break;
                    case strpos($errcode, 'S3ERROR' ) !==false:
                        $err = "upload_error";
                        break;
                    case strpos($errcode, 'SORRY_FILE_HITAIOU' ) !==false:
                        $err = "upload_error";
                        break;
                    case strpos($errcode, 'FILE_UPLOAD_DEKINAKATTA' ) !==false:
                        $err = "upload_error";
                        break;
                    case strpos($errcode, 'PHOTO_SELECT_PLEASE' ) !==false:
                        $err = "upload_error";
                        break;
                    case strpos($errcode, 'REGISTERED_DAME' ) !==false:
                        $err = "could_not_complete";
                        break;
                    case strpos($errcode, 'OVER_RATE_LIMIT' ) !==false:
                        $err = "over_rate_limit";
                        break;
                    case strpos($errcode, 'ERROR' ) !==false:
                        $err = "could_not_complete";
                        break;
                    default:
                        $err = "could_not_complete";
                        break;
                }

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