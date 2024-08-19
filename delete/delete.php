<?php
require('../db.php');
require("../function/function.php");

if (safetext(isset($_POST['uniqid'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id']))){
    $postUserid = safetext($_POST['userid']);
    $postUniqid = safetext($_POST['uniqid']);
    $loginid = safetext($_POST['account_id']);

    $result = delete_ueuse($postUniqid, $postUserid, $loginid);
    if($result[0] === true){
        echo json_encode(['success' => true]);
        exit;
    }else{
        echo json_encode(['success' => false, 'error' => '削除に失敗しました。']);
        exit;
    }
}else{
    echo json_encode(['success' => false, 'error' => '削除に失敗しました。(sess_err)']);
    exit;
}
?>
