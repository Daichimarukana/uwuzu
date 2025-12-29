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

    if(!(empty($post_json["limit"]))){
        $limit = (int)$post_json["limit"];
    }else{
        $limit = 50;
    }
    if($limit > 500){
        $limit = 500;
    }

    if(!(empty($post_json["page"]))){
        $page = (int)$post_json["page"];
    }else{
        $page = 1;
    }
    $offset = ($page - 1) * $limit;

    session_start();

    if (!empty($pdo)) {
        $AuthData = APIAuth($pdo, $token, "read:admin:reports");
        if ($AuthData[0] === true && $AuthData[2]["admin"] == "yes") {
            $sql = "SELECT * FROM report WHERE admin_chk = 'none' ORDER BY datetime DESC LIMIT :offset, :itemsPerPage";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':itemsPerPage', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $allreport = $stmt;

            while ($row = $allreport->fetch(PDO::FETCH_ASSOC)) {
                $reports[] = $row;
            }

            $groupedReports = [];

            if (!empty($reports)) {
                foreach ($reports as $row) {
                    $reportedUserId = $row['userid'];

                    if (!isset($groupedReports[$reportedUserId])) {
                        $groupedReports[$reportedUserId] = [
                            'reported_userid' => $reportedUserId,
                            'total_count'      => 0,
                            'details'          => []
                        ];
                    }
                    $groupedReports[$reportedUserId]['details'][] = [
                        'uniqid' => $row['uniqid'],
                        'reporter_userid'  => $row['report_userid'],
                        'message'   => $row['msg'],
                        'datetime'  => $row['datetime']
                    ];

                    $groupedReports[$reportedUserId]['total_count']++;
                }
            }

            echo json_encode([
                'success' => true,
                'data'   => array_values($groupedReports)
            ], JSON_UNESCAPED_UNICODE);
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
