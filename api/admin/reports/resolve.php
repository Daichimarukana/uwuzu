<?php

$domain = $_SERVER['HTTP_HOST'];
require(__DIR__ . '/../../../db.php');
require(__DIR__ . "/../../../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

//----------------------------------------------------------------
//--------------------------管理者向けAPI---------------------------
//----------------------------------------------------------------

$pdo = null;
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error_code' => 'method_not_allowed',
        'success' => false
    ]);
    exit;
}

$Get_Post_Json = file_get_contents("php://input");
if ((!(empty($Get_Post_Json)))) {

    //トークン取得
    $post_json = json_decode($Get_Post_Json, true);
    if (isset($post_json["token"])) {
        $token = safetext($post_json["token"]);
    } else {
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
            'success' => false
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($token == "") {
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
            'success' => false
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if(!(empty($post_json["uniqid"]))){
        $uniqid = safetext($post_json["uniqid"]);
    }else{
        $uniqid = null;
    }

    if(!(empty($post_json["reported_userid"]))){
        $reported_userid = safetext($post_json["reported_userid"]);
    }else{
        $reported_userid = null;
    }    

    session_start();

    if (!empty($pdo)) {
        $AuthData = APIAuth($pdo, $token, "write:admin:reports");
        if ($AuthData[0] === true && $AuthData[2]["admin"] == "yes") {
            if(!(empty($uniqid))){
                $newchk = "done";
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("UPDATE report SET admin_chk = :adchk WHERE uniqid = :uniqid");
                    $stmt->bindValue(':adchk', $newchk, PDO::PARAM_STR);
                    $stmt->bindValue(':uniqid', $uniqid, PDO::PARAM_STR);
                    $res = $stmt->execute();

                    if ($res) {
                        $pdo->commit();
                        $response = array(
                            'success' => true,
                            'uniqid' => $uniqid
                        );
                    } else {
                        $response = array(
                            'error_code' => 'could_not_complete',
                            'success' => false
                        );
                        $pdo->rollBack();
                        actionLog($AuthData[2]["userid"], "error", "admin-reports-resolve-api", null, "通報の解決に失敗しました", 3);
                    }
                } catch (Exception $e) {
                    $response = array(
                        'error_code' => 'db_error_update',
                        'success' => false
                    );
                    $pdo->rollBack();
                    actionLog($AuthData[2]["userid"], "error", "admin-reports-resolve-api", null, $e, 4);
                }
            }elseif(!(empty($reported_userid))){
                $newchk = "done";
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("UPDATE report SET admin_chk = :adchk WHERE userid = :userid");
                    $stmt->bindValue(':adchk', $newchk, PDO::PARAM_STR);
                    $stmt->bindValue(':userid', $reported_userid, PDO::PARAM_STR);
                    $res = $stmt->execute();

                    if ($res) {
                        $pdo->commit();
                        $response = array(
                            'success' => true,
                            'reported_userid' => $reported_userid
                        );
                    } else {
                        $response = array(
                            'error_code' => 'could_not_complete',
                            'success' => false
                        );
                        $pdo->rollBack();
                        actionLog($AuthData[2]["userid"], "error", "admin-reports-resolve-api", null, "通報の解決に失敗しました", 3);
                    }
                } catch (Exception $e) {
                    $response = array(
                        'error_code' => 'db_error_update',
                        'success' => false
                    );
                    $pdo->rollBack();
                    actionLog($AuthData[2]["userid"], "error", "admin-reports-resolve-api", null, $e, 4);
                }
            }else{
                $response = array(
                    'error_code' => 'input_not_found',
                    'success' => false
                );
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            $err = $AuthData[1];
            $response = array(
                'error_code' => $err,
                'success' => false
            );

            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
    }
} else {
    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
        'success' => false
    );

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
