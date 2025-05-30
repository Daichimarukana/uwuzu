<?php
require('../db.php');
require("../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

function random_iv($length = 16){
    return substr(str_shuffle('1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 0, $length);
}

$domain = $_SERVER['HTTP_HOST'];

$datetime = array();
$pdo = null;

session_start();

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

if(isset($_GET['migration_code'])) { 
    if(isset($_GET['check'])) {
        //移行後-----------------------------------------------------------------------------------------------
        $migration_code = safetext($_GET['migration_code']);
        $check = urldecode($_GET['check']);
        $request_domain = safetext($_SERVER['REMOTE_ADDR']);

        $migrationQuery = $pdo->prepare("SELECT * FROM migration WHERE migration_code = :migration_code");
        $migrationQuery->bindValue(':migration_code', $migration_code);
        $migrationQuery->execute();
        $migrationData = $migrationQuery->fetch(PDO::FETCH_ASSOC);

        if(!(empty($migrationData))){
            $UserdataQuery = $pdo->prepare("SELECT userid FROM account WHERE userid = :userid");
            $UserdataQuery->bindValue(':userid', $migrationData['account'], PDO::PARAM_STR);
            $UserdataQuery->execute();
            $UserData = $UserdataQuery->fetch(PDO::FETCH_ASSOC);

            $done_chk = openssl_decrypt($check, "AES-256-CBC", $migrationData['encryption_key'], 0, $migrationData['encryption_ivkey']);
            //下の文字列はアカウント移行が完了しているかの確認用！変えないで！！！
            if($done_chk == "QYrLCSQIHqOLHuhJ"){
                $account = safetext($UserData["userid"]);
                $pdo->beginTransaction();
                try {
                    $deleteQuery = $pdo->prepare("DELETE FROM migration WHERE account = :account");
                    $deleteQuery->bindValue(':account',$account, PDO::PARAM_STR);
                    $res = $deleteQuery->execute();
                    $res = $pdo->commit();
                } catch(Exception $e) {
                    $pdo->rollBack();
                    actionLog($account, "error", "migration-api", null, $e, 4);
                }
                $newrole = "ice";
                $newtoken = "ice";
                $newadmin = "none";

                // トランザクション開始
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("UPDATE account SET role = :role,token = :newtoken,admin = :newadmin WHERE userid = :userid");

                    $stmt->bindValue(':role', $newrole, PDO::PARAM_STR);
                    $stmt->bindValue(':newtoken', $newtoken, PDO::PARAM_STR);
                    $stmt->bindValue(':newadmin', $newadmin, PDO::PARAM_STR);

                    $stmt->bindValue(':userid', $account, PDO::PARAM_STR);

                    // SQLクエリの実行
                    $res = $stmt->execute();

                    // コミット
                    $res = $pdo->commit();


                } catch (Exception $e) {

                    // エラーが発生した時はロールバック
                    $pdo->rollBack();
                    actionLog($account, "error", "migration-api", null, $e, 4);
                }
                //メール送信はナシ
                //------------
                
                $msg = "アカウントの移行が完了したためこのアカウントの不正コピーを防ぐためアカウントを凍結しました！\n引き続きこのアカウントを利用するには管理者に凍結を解除してもらってください！";
                $title = "✨アカウントの移行が完了しました！🔄️";
                $url = "/rule/serverabout";
                $from_userid = "uwuzu-fromsys";
                $category = "system";
                
                send_notification($from_userid,$account,$title,$msg,$url,$category);

                if ($res) {
                    $item = array(
                        'done' => 'success',
                    );
                    $noencriptjson = json_encode($item, JSON_UNESCAPED_UNICODE);
                    $encriptjson = openssl_encrypt($noencriptjson, "AES-256-CBC", $migrationData['encryption_key'], 0, $migrationData['encryption_ivkey']);
                    $response = array(
                        'data' => $encriptjson,
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                } else {
                    $err = "migration_bad_success";
                    $response = array(
                        'error_code' => $err,
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                }

            }else {
                $err = "migration_bad_success";
                $response = array(
                    'error_code' => $err,
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
        }else{
            $err = "migration_notfound";
            $response = array(
                'error_code' => $err,
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
    }else{
        //移行データ
        $migration_code = safetext($_GET['migration_code']);
        $request_domain = safetext($_SERVER['REMOTE_ADDR']);

        $migrationQuery = $pdo->prepare("SELECT * FROM migration WHERE migration_code = :migration_code");
        $migrationQuery->bindValue(':migration_code', $migration_code);
        $migrationQuery->execute();
        $migrationData = $migrationQuery->fetch(PDO::FETCH_ASSOC);

        if(!(empty($migrationData))){
            $UserdataQuery = $pdo->prepare("SELECT * FROM account WHERE userid = :userid");
            $UserdataQuery->bindValue(':userid', $migrationData['account'], PDO::PARAM_STR);
            $UserdataQuery->execute();
            $UserData = $UserdataQuery->fetch(PDO::FETCH_ASSOC);

            /*
            // 投稿内容の取得（新しい順に1000件取得）
            $ueuseQuery = $pdo->prepare("SELECT * FROM ueuse WHERE account = :userid AND rpuniqid = '' ORDER BY datetime DESC LIMIT 1000");
            $ueuseQuery->bindValue(':userid', $migrationData['account'], PDO::PARAM_STR);
            $ueuseQuery->execute();
            $ueuse_array = $ueuseQuery->fetchAll();  
            */
            if(!(empty($UserData))){

                if(!(empty($UserData["encryption_ivkey"]))){
                    $view_mailadds = DecryptionUseEncrKey($UserData["mailadds"], GenUserEnckey($UserData["datetime"]), $UserData["encryption_ivkey"]);
                }else{
                    $view_mailadds = $UserData["mailadds"];
                }

                /*
                if(!(empty($ueuse_array))){
                    foreach ($ueuse_array as $value) {
                        $ueuses = array(
                            "username" => decode_yajirushi(htmlentities($value['username'], ENT_QUOTES, 'UTF-8', false)),
                            "account" => decode_yajirushi(htmlentities($value['account'], ENT_QUOTES, 'UTF-8', false)),
                            "uniqid" => decode_yajirushi(htmlentities($value['uniqid'], ENT_QUOTES, 'UTF-8', false)),
                            "ueuse" => decode_yajirushi(htmlentities($value['ueuse'], ENT_QUOTES, 'UTF-8', false)),
                            "datetime" => decode_yajirushi(htmlentities($value['datetime'], ENT_QUOTES, 'UTF-8', false)),
                            "abi" => decode_yajirushi(htmlentities($value['abi'], ENT_QUOTES, 'UTF-8', false)),
                            "abidate" => decode_yajirushi(htmlentities($value['abidate'], ENT_QUOTES, 'UTF-8', false)),
                            "nsfw" => decode_yajirushi(htmlentities($value['nsfw'], ENT_QUOTES, 'UTF-8', false)),
                        );
        
                        $ueuse[] = $ueuses;
                    }
                }else{
                    $ueuse[] = "";
                }
                */
                $item = [
                    "userdata" => array(
                        "user_name" => safetext($UserData["username"]),
                        "user_id" => safetext($UserData["userid"]),
                        "user_icon" => (empty($_SERVER['HTTPS']) ? 'http://' : 'https://').$domain."/".safetext($UserData["iconname"]),
                        "user_header" => (empty($_SERVER['HTTPS']) ? 'http://' : 'https://').$domain."/".safetext($UserData["headname"]),
                        "user_profile" => safetext($UserData["profile"]),
                        "mail_adds" => safetext($view_mailadds),
                    ),
                ];
            
                $noencriptjson = json_encode($item, JSON_UNESCAPED_UNICODE);
                $encriptjson = openssl_encrypt($noencriptjson, "AES-256-CBC", $migrationData['encryption_key'], 0, $migrationData['encryption_ivkey']);

                $response = array(
                    'data' => $encriptjson,
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }else{
                $err = "data_notfound";
                $response = array(
                    'error_code' => $err,
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
        }else{
            $err = "migration_notfound";
            $response = array(
                'error_code' => $err,
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
    }

    
}else{
    $err = "migration_code_notfound";
    $response = array(
        'error_code' => $err,
    );
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>