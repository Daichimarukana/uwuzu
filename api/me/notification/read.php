<?php

$domain = $_SERVER['HTTP_HOST'];
require(__DIR__ . '/../../../db.php');
require(__DIR__ . "/../../../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");


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

$Get_Post_Json = file_get_contents("php://input");
if (isset($_GET['token']) || (!(empty($Get_Post_Json)))) {
    //トークン取得
    if (!(empty($_GET['token']))) {
        $token = safetext($_GET['token']);
    } else {
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

    if (!(empty($_GET['limit']))) {
        $limit = (int)$_GET['limit'];
    } elseif (!(empty($post_json["limit"]))) {
        $limit = (int)$post_json["limit"];
    } else {
        $limit = 25;
    }
    if ($limit > 100) {
        $limit = 100;
    }

    if (!(empty($_GET['page']))) {
        $page = (int)$_GET['page'];
    } elseif (!(empty($post_json["page"]))) {
        $page = (int)$post_json["page"];
    } else {
        $page = 1;
    }
    $offset = ($page - 1) * $limit;

    session_start();

    if (!empty($pdo)) {
        $AuthData = APIAuth($pdo, $token, "write:notifications");
        if ($AuthData[0] === true) {
            $userData = $AuthData[2];

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE notification SET userchk = 'done' WHERE touserid = :userid;");
                $stmt->bindValue(':userid', $userData["userid"], PDO::PARAM_STR);
                $res = $stmt->execute();
                if ($res) {
                    $pdo->commit();
                    $response = array(
                        'success' => true
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                } else {
                    $pdo->rollBack();
                    $err = "update_failed";
                    $response = array(
                        'error_code' => $err,
                        'success' => false
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }
            } catch (PDOException $e) {
                // 接続エラーのときエラー内容を取得する
                $error_message[] = $e->getMessage();
            }
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
