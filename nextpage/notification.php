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
if (isset($_GET['userid']) && isset($_GET['account_id'])) {
    $userid = safetext($_GET['userid']);
    $loginid = safetext($_GET['account_id']);

    $query = $pdo->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

    $query->execute(array(':userid' => $userid));

    $result2 = $query->fetch();

    if(!(empty($result2["loginid"]))){
        if($result2["loginid"] === $loginid){
            
            $aduserinfoQuery = $pdo->prepare("SELECT username,userid,loginid,admin,role,sacinfo,blocklist,bookmark FROM account WHERE userid = :userid");
            $aduserinfoQuery->bindValue(':userid', safetext($userid));
            $aduserinfoQuery->execute();
            $res = $aduserinfoQuery->fetch();
            $myblocklist = safetext($res["blocklist"]);
            
            $userid = safetext($_GET['userid']);

            $itemsPerPage = 15; // 1ページあたりの投稿数
            $pageNumber = safetext(isset($_GET['page'])) ? safetext(intval($_GET['page'])) : 1;
            if($pageNumber <= 0 || (!(is_numeric($pageNumber)))){
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
                        if(!(empty($value['fromuserid']))){
                            if(!($value['fromuserid'] == "uwuzu-fromsys")){
                                $userQuery = $pdo->prepare("SELECT username,iconname FROM account WHERE userid = :userid");
                                $userQuery->bindValue(':userid', $value['fromuserid']);
                                $userQuery->execute();
                                $user_array = $userQuery->fetch();
                                if(!(empty($user_array))){
                                    $value['fromusericon'] = filter_var($user_array["iconname"], FILTER_VALIDATE_URL) ? $user_array["iconname"] : "../" . $user_array["iconname"];
                                    $value['fromusername'] = $user_array["username"];
                                }else{
                                    $value['fromusericon'] = "../img/deficon/icon.png";
                                    $value['fromusername'] = "でふぉると";
                                }
                            }
                        }
                        $messageDisplay = new MessageDisplay($value); // userid を渡さない
                        $messageDisplay->display();
                    }
                } else {
                    echo '<div class="tokonone" id="noueuse"><p>通知はありません</p></div>';
                }
                
                
                $pdo = null;

            }else{
                echo '<div class="tokonone" id="noueuse"><p>取得に失敗しました。</p></div>';
            }
        }else{
            echo '<div class="tokonone" id="noueuse"><p>取得に失敗しました。</p></div>';
        }
    }else{
        echo '<div class="tokonone" id="noueuse"><p>取得に失敗しました。</p></div>';
    }
}else{
    echo '<div class="tokonone" id="noueuse"><p>取得に失敗しました。</p></div>';
}
?>
