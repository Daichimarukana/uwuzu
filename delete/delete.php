<?php
require('../db.php');
require("../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);
header('Content-Type: application/json');

if (safetext(isset($_POST['uniqid'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey']))) {
    $postUserid = safetext($_POST['userid']);
    $postUniqid = safetext($_POST['uniqid']);
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);

    $is_login = uwuzuUserLoginCheck($loginid, $loginkey, "user");
    if ($is_login === false) {
        echo json_encode(['success' => false, 'error' => '認証に失敗しました。(AUTH_INVALID)']);
        exit;
    }

    
    $result = delete_ueuse($postUniqid, $postUserid, $loginid);
    if($result[0] === true){
        echo json_encode(['success' => true]);
        exit;
    }else{
        echo json_encode(['success' => false, 'error' => '削除に失敗しました。']);
        exit;
    }
}else{
    echo json_encode(['success' => false, 'error' => '削除に失敗しました。(ERROR)']);
    exit;
}
?>
