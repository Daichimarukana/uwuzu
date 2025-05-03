<?php
header('Content-Type: application/json');

require('../db.php');
require("function.php");
blockedIP($_SERVER['REMOTE_ADDR']);
if (safetext(isset($_POST['ueuse'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey']))) {
    try {
        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);
    } catch (PDOException $e) {
        // 接続エラーのときエラー内容を取得する
        actionLog($userid, "error", "ueuse", null, $e, 4);
    }
    
    $userid = safetext($_POST['userid']);

    $ueuse = safetext($_POST['ueuse']);
    $nsfw_chk = safetext($_POST['nsfw_chk']);
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);

    $is_login = uwuzuUserLoginCheck($loginid, $loginkey, "user");
    if ($is_login === false) {
        echo json_encode(['success' => false, 'error' => '認証に失敗しました。(AUTH_INVALID)']);
        exit;
    }

    //ユーザーの認証情報を取得
    $query = $pdo->prepare('SELECT * FROM account WHERE userid = :userid limit 1');
    $query->execute(array(':userid' => $userid));
    $result2 = $query->fetch();
    if($result2["loginid"] === $loginid){
        if(!($result2["role"] == "ice")){
            if(!(empty($result2["other_settings"]))){
                $isAIBWM = val_OtherSettings("isAIBlockWaterMark", $result2["other_settings"]);
            }else{
                $isAIBWM = false;
            }

            if(isset($nsfw_chk) && $nsfw_chk == "true"){
                $nsfw_chk = "true";
            }else{
                $nsfw_chk = "false";
            }

            if(isset($_FILES['upload_images'])){
                $photo1 = $_FILES['upload_images'];
            }else{
                $photo1 = null;
            }
            if(isset($_FILES['upload_images2'])){
                $photo2 = $_FILES['upload_images2'];
            }else{
                $photo2 = null;
            }
            if(isset($_FILES['upload_images3'])){
                $photo3 = $_FILES['upload_images3'];
            }else{
                $photo3 = null;
            }
            if(isset($_FILES['upload_images4'])){
                $photo4 = $_FILES['upload_images4'];
            }else{
                $photo4 = null;
            }
            if(isset($_FILES['upload_videos1'])){
                $video1 = $_FILES['upload_videos1'];
            }else{
                $video1 = null;
            }

            if(isset($_POST['rpuniqid'])){
                $rpUniqid = safetext($_POST['rpuniqid']);
            }else{
                $rpUniqid = "";
            }

            $ruUniqid = "";
            $ueuse_result = send_ueuse($userid,$rpUniqid,$ruUniqid,$ueuse,$photo1,$photo2,$photo3,$photo4,$video1,$nsfw_chk,$isAIBWM);

            if($ueuse_result[0] == true){
                echo json_encode(['success' => true]);
                exit;
            }else{
                echo json_encode(['success' => false, 'error' => $ueuse_result[1]]);
                exit;
            }
        }else{
            echo json_encode(['success' => false, 'error' => 'お使いのアカウントではユーズができません。']);
            exit; 
        }
    }else{
        echo json_encode(['success' => false, 'error' => 'ユーズに失敗しました。']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => '必要なパラメータが提供されていません。']);
    exit;
}
 
?>