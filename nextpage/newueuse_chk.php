<?php

require('../db.php');
require("../function/function.php");


// データベースに接続
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

if (isset($_POST['userid']) && isset($_POST['account_id'])) {
    $userid = safetext($_POST['userid']);
    $loginid = safetext($_POST['account_id']);

    // データベース接続の設定
    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ));

    $query = $dbh->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

    $query->execute(array(':userid' => $userid));

    $result2 = $query->fetch();

    if(!(empty($result2["loginid"]))){
        if($result2["loginid"] === $loginid){
            $loading_dt = safetext($_POST['loading_dt']);

            $messages = array();

            if (!empty($pdo)) {
                
                $sql = "SELECT * FROM ueuse WHERE rpuniqid = '' ORDER BY datetime DESC LIMIT 1";
                $message_array = $pdo->query($sql);    

                while ($row = $message_array->fetch(PDO::FETCH_ASSOC)) {

                    $messages[] = $row;
                }

                if(!empty($messages)){
                    foreach ($messages as $value) {
                        $now_time = strtotime($loading_dt);
                        $loadtime = strtotime($value["datetime"]);
                        $time_sa = $loadtime - $now_time;
                        if($time_sa > 0){
                            echo json_encode(['success' => true, 'info' => 'ueuse_true']);
                            exit;
                        }else{
                            echo json_encode(['success' => false, 'info' => 'ueuse_none']);
                            exit; 
                        }
                    }
                }else{
                    echo json_encode(['success' => false, 'info' => 'ueuse_none']);
                    exit;
                }
                
                $pdo = null;

            }
        }else{
            echo json_encode(['success' => false, 'info' => 'not_access1']);
            exit;
        }
    }else{
        echo json_encode(['success' => false, 'info' => 'not_access2']);
        exit;
    }
}else{
    echo json_encode(['success' => false, 'info' => 'not_access3']);
    exit;
}
?>
