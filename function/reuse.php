<?php
header('Content-Type: application/json');

require('../db.php');
require("function.php");
blockedIP($_SERVER['REMOTE_ADDR']);
if (safetext(isset($_POST['uniqid'])) && safetext(isset($_POST['reusetext'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey']))) {
    try {
        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);
    } catch (PDOException $e) {
        // 接続エラーのときエラー内容を取得する
        $error_message[] = $e->getMessage();
    }
    
    $userid = safetext($_POST['userid']);

    $postUniqid = safetext($_POST['uniqid']);
    $reusetext = safetext($_POST['reusetext']);
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);

    $is_login = uwuzuUserLoginCheck($loginid, $loginkey, "user");
    if ($is_login === false) {
        echo json_encode(['success' => false, 'error' => '認証に失敗しました。(AUTH_INVALID)']);
        exit;
    }

    $reusedate = date("Y-m-d H:i:s");

    //ユーズの情報を取得
    $query = $pdo->prepare('SELECT * FROM ueuse WHERE uniqid = :uniqid limit 1');
    $query->execute(array(':uniqid' => $postUniqid));
    $result = $query->fetch();

    //ユーザーの認証情報を取得
    $query = $pdo->prepare('SELECT * FROM account WHERE userid = :userid limit 1');
    $query->execute(array(':userid' => $userid));
    $result2 = $query->fetch();
    if($result2["loginid"] === $loginid){
        if(!($result2["role"] == "ice")){
            $nsfw_chk = "false";
        
            $photo1 = "";
            $photo2 = "";
            $photo3 = "";
            $photo4 = "";
            $video1 = "";
        
            $rpUniqid = "";

            $AIBWM = false;
            if(!(empty($result["ueuse"]))){
                $ruUniqid = $postUniqid;
            }else{
                $ruUniqid = $result["ruuniqid"];
            }
            $ueuse_result = send_ueuse($userid,$rpUniqid,$ruUniqid,$reusetext,$photo1,$photo2,$photo3,$photo4,$video1,$nsfw_chk,$AIBWM);

            if($ueuse_result == null){
                echo json_encode(['success' => true]);
                exit;
            }else{
                echo json_encode(['success' => false, 'error' => $ueuse_result]);
                exit;
            }
        }else{
            echo json_encode(['success' => false, 'error' => 'お使いのアカウントではリユーズができません。']);
            exit; 
        }
    }else{
        echo json_encode(['success' => false, 'error' => 'リユーズに失敗しました。']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => '必要なパラメータが提供されていません。']);
    exit;
}
 
?>