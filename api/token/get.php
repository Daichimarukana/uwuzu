<?php

$domain = $_SERVER['HTTP_HOST'];
require(__DIR__ . '/../../db.php');
require(__DIR__ . "/../../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

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
    $error_message[] = $e->getMessage();
}

$Get_Post_Json = file_get_contents("php://input");
if(isset($_GET['session']) || (!(empty($Get_Post_Json)))) { 
    if(!(empty($_GET['session']))){
        $session_id = safetext($_GET['session']);
    }else{
        $post_json = json_decode($Get_Post_Json, true);
        if(isset($post_json["session"])){
            $session_id = safetext($post_json["session"]);
        }else{
            echo json_encode(['error_code' => "input_not_found", 'success' => false], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    if($session_id == ""){
        echo json_encode(['error_code' => "input_not_found", 'success' => false], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    session_start();

    if( !empty($pdo) ) {
        MigrateLegacyAPITokens($pdo);

        $tokenQuery = $pdo->prepare("SELECT uniqid, userid FROM api WHERE sessionid = :sessionid AND (token IS NULL OR token = '' OR keyid IS NULL OR keyid = '')");
        $tokenQuery->bindValue(':sessionid', $session_id, PDO::PARAM_STR);
        $tokenQuery->execute();
        $tokenData = $tokenQuery->fetch();

        if(empty($tokenData["userid"])){
            echo json_encode(['error_code' => "session_invalid", 'success' => false], JSON_UNESCAPED_UNICODE);
            exit;
        }else{
            $userdata = getUserData($pdo, $tokenData["userid"]);
            
            if (empty($userdata)){
                echo json_encode(['error_code' => "critical_error_userdata_not_found", 'success' => false], JSON_UNESCAPED_UNICODE);
            }else{
                // 新規トークンを生成
                $tokenInfo = GenAPIToken();
                $fullToken = $tokenInfo[0];
                $keyId = $tokenInfo[1];
                $tokenHash = $tokenInfo[2];

                $pdo->beginTransaction();
                try {
                    // keyid と tokenHash を保存
                    $updateQuery = $pdo->prepare("UPDATE api SET keyid = :keyid, token = :token WHERE uniqid = :uniqid");
                    $updateQuery->bindValue(':keyid', $keyId, PDO::PARAM_STR);
                    $updateQuery->bindValue(':token', $tokenHash, PDO::PARAM_STR);
                    $updateQuery->bindValue(':uniqid', $tokenData["uniqid"], PDO::PARAM_STR);
                    $res = $updateQuery->execute();

                    if($res){
                        $pdo->commit();

                        DelSessionidAPIToken($pdo, $session_id);

                        // 生のトークン ($fullToken) をアプリへ一度だけ返却
                        $response = array(
                            'success' => true,
                            'username' => decode_yajirushi(htmlspecialchars_decode($userdata["username"])),
                            'userid' => decode_yajirushi(htmlspecialchars_decode($userdata["userid"])),
                            'token' => $fullToken
                        );
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    }else{
                        $pdo->rollBack();
                        echo json_encode(['error_code' => "token_issue_failed", 'success' => false], JSON_UNESCAPED_UNICODE);
                    }
                } catch (Exception $e) {
                    $pdo->rollBack();
                    actionLog($tokenData["userid"], "error", "get_api_token", $tokenData["uniqid"], $e->getMessage(), 4);
                    echo json_encode(['error_code' => "token_issue_error", 'success' => false], JSON_UNESCAPED_UNICODE);
                }
            }
        }
    }
}else{
    echo json_encode(['error_code' => "input_not_found", 'success' => false], JSON_UNESCAPED_UNICODE);
}
?>