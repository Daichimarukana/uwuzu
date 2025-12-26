<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

require('../db.php');
require("../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

require('notificationview.php');

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
if (safetext(isset($_POST['page'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey']))) {
    $userid = safetext($_POST['userid']);
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);

    $is_login = uwuzuUserLoginCheck($loginid, $loginkey, "user");
    if ($is_login === false) {
        echo json_encode(['success' => false, 'error' => 'bad_request']);
        exit;
    }

    $myblocklist = getUserData($pdo, $userid)["blocklist"];

    $itemsPerPage = 15; // 1ページあたりの投稿数
    $pageNumber = safetext(isset($_POST['page'])) ? safetext(intval($_POST['page'])) : 1;
    if ($pageNumber <= 0 || (!(is_numeric($pageNumber)))) {
        $pageNumber = 1;
    }
    $offset = ($pageNumber - 1) * $itemsPerPage;

    $messages = array();

    if (!empty($pdo)) {
        $messageQuery = $pdo->prepare("SELECT fromuserid,title,msg,url,datetime,userchk FROM notification WHERE touserid = :userid ORDER BY datetime DESC LIMIT :offset, :itemsPerPage");
        $messageQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
        $messageQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
        $messageQuery->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
        $messageQuery->execute();
        $message_array = $messageQuery->fetchAll();

        // トランザクション開始
        $pdo->beginTransaction();

        // SQL作成
        $stmt = $pdo->prepare("UPDATE notification SET userchk = 'done' WHERE touserid = :userid;");

        $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);

        $res = $stmt->execute();
        $res = $pdo->commit();

        if (!empty($message_array)) {
            foreach ($message_array as $value) {
                $value["servericon"] = safetext($serversettings["serverinfo"]["server_icon"]);
                if (!(empty($value['fromuserid']))) {
                    if (!($value['fromuserid'] == "uwuzu-fromsys")) {
                        $userQuery = $pdo->prepare("SELECT username,iconname FROM account WHERE userid = :userid");
                        $userQuery->bindValue(':userid', $value['fromuserid']);
                        $userQuery->execute();
                        $user_array = $userQuery->fetch();
                        if (!(empty($user_array))) {
                            $value['fromusericon'] = filter_var($user_array["iconname"], FILTER_VALIDATE_URL) ? $user_array["iconname"] : "../" . $user_array["iconname"];
                            $value['fromusername'] = $user_array["username"];
                        } else {
                            $value['fromusericon'] = "../img/deficon/icon.png";
                            $value['fromusername'] = "でふぉると";
                        }
                    }else{
                        if(!empty($value["servericon"])){
                            $value['fromusericon'] = safetext($value["servericon"]);
                            $value['fromusername'] = "uwuzu";
                        }else{
                            $value['fromusericon'] = "../img/uwuzuicon.png";
                            $value['fromusername'] = "uwuzu";
                        }
                    }
                }

                if($value["userchk"] === "done"){
                    $value["userchk"] = true;
                }else{
                    $value["userchk"] = false;
                }

                $formatted = [
                    "type" => "notification",
                    "datetime" => $value["datetime"],
                    "userid" => $value["fromuserid"],
                    "userdata" => [
                        "userid" => $value["fromuserid"],
                        "username" => $value["fromusername"],
                        "iconurl" => $value['fromusericon'],
                    ],
                    "message" => $value["msg"],
                    "url" => $value["url"],
                    "title" => $value["title"],
                    "is_read" => $value["userchk"],
                ];

                if ($formatted !== null) {
                    $notificationItems[] = $formatted;
                }
            }

            $item = array(
                "success" => true,
                "notifications" => $notificationItems
            );

            echo json_encode($item, JSON_UNESCAPED_UNICODE);
        } else {
            $item = array(
                "success" => false,
                "notifications" => null,
                "error" => "no_notification",
            );
            echo json_encode($item, JSON_UNESCAPED_UNICODE);
        }


        $pdo = null;
    } else {
        $item = array(
            "success" => false,
            "notifications" => null,
            "error" => "bad_request",
        );
        echo json_encode($item, JSON_UNESCAPED_UNICODE);
    }
} else {
    $item = array(
        "success" => false,
        "notifications" => null,
        "error" => "bad_request",
    );
    echo json_encode($item, JSON_UNESCAPED_UNICODE);
}
