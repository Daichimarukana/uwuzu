<?php
header("Content-Type: application/json; charset=utf-8");

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}

if(isset($_GET['token'])&&isset($_GET['ueuse'])) { 

    $token = htmlentities($_GET['token']);
    $ueuse = htmlentities($_GET['ueuse']);

    require('../db.php');

    $datetime = array();
    $pdo = null;

    session_start();

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


        if( !empty($pdo) ) {
	
            // データベース接続の設定
            $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ));
        
            $userQuery = $dbh->prepare("SELECT username, userid FROM account WHERE token = :token");
            $userQuery->bindValue(':token', $token);
            $userQuery->execute();
            $userData = $userQuery->fetch();

            if(empty($userData["userid"])){
                $err = "token_invalid";
                $response = array(
                    'error_code' => $err,
                );
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }else{
                // 書き込み日時を取得
                $username = $userData["username"];
                $userid = $userData["userid"];
                $datetime = date("Y-m-d H:i:s");
                $uniqid = createUniqId();
                $abi = "none";

                // トランザクション開始
                $pdo->beginTransaction();

                try {

                    // SQL作成
                    $stmt = $pdo->prepare("INSERT INTO ueuse (username, account, uniqid, ueuse, datetime, abi) VALUES (:username, :account, :uniqid, :ueuse, :datetime, :abi)");
            
                    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                    $stmt->bindParam(':account', $userid, PDO::PARAM_STR);
                    $stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
                    $stmt->bindParam(':ueuse', $ueuse, PDO::PARAM_STR);

                    $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

                    $stmt->bindParam(':abi', $abi, PDO::PARAM_STR);

                    // SQLクエリの実行
                    $res = $stmt->execute();

                    // コミット
                    $res = $pdo->commit();

                } catch(Exception $e) {

                    // エラーが発生した時はロールバック
                    $pdo->rollBack();
                }

                if( $res ) {
                    $response = array(
                        'uniqid' => $uniqid,
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