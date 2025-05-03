<?php
header('Content-Type: application/json');
require('../db.php');
require('../function/function.php');
blockedIP($_SERVER['REMOTE_ADDR']);

if (safetext(isset($_POST['page'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey']))) {
    $page = safetext($_POST['page']);
    $userId = safetext($_POST['userid']);
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);

    $is_login = uwuzuUserLoginCheck($loginid, $loginkey, "user");
    if ($is_login === false) {
        echo json_encode(['success' => false, 'error' => 'bad_request']);
        exit;
    }

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

    if (!empty($pdo)) {
        $myUserData = getUserData($pdo, $userId);
        $myblocklist = safetext($myUserData["blocklist"]);
        $mybookmark = safetext($myUserData["bookmark"]);

        $itemsPerPage = 15; // 1ページあたりのユーズ数
        $pageNumber = $page;
        if($pageNumber <= 0 || (!(is_numeric($pageNumber)))){
            $pageNumber = 1;
        }
        $offset = ($pageNumber - 1) * $itemsPerPage;

        $messages = array();
        
        $sql = "SELECT ueuse.* 
                FROM ueuse 
                LEFT JOIN account ON ueuse.account = account.userid 
                WHERE ueuse.rpuniqid = '' AND account.role != 'ice'
                ORDER BY ueuse.datetime DESC 
                LIMIT :offset, :itemsPerPage";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
        $stmt->execute();
        $message_array = $stmt;

        while ($row = $message_array->fetch(PDO::FETCH_ASSOC)) {
            $messages[] = $row;
        }

        // ユーザー情報を取得して、$messages内のusernameをuserDataのusernameに置き換える
        foreach ($messages as &$message) {
            $userQuery = $pdo->prepare("SELECT username, userid, profile, role, iconname, headname, sacinfo FROM account WHERE userid = :userid");
            $userQuery->bindValue(':userid', $message["account"]);
            $userQuery->execute();
            $userData = $userQuery->fetch();

            if ($userData) {
                $message['iconname'] = $userData['iconname'];
                $message['headname'] = $userData['headname'];
                $message['username'] = $userData['username'];
                $message['sacinfo'] = $userData['sacinfo'];
                $message['role'] = $userData['role'];
            }

            //リプライ数取得
            $rpQuery = $pdo->prepare("SELECT COUNT(*) as reply_count FROM ueuse WHERE rpuniqid = :rpuniqid");
            $rpQuery->bindValue(':rpuniqid', $message['uniqid']);
            $rpQuery->execute();
            $rpData = $rpQuery->fetch(PDO::FETCH_ASSOC);
            
            if ($rpData){
                $message['reply_count'] = $rpData['reply_count'];
            }

            //リユーズ数取得
            $ruQuery = $pdo->prepare("SELECT COUNT(*) as reuse_count FROM ueuse WHERE ruuniqid = :ruuniqid");
            $ruQuery->bindValue(':ruuniqid', $message['uniqid']);
            $ruQuery->execute();
            $ruData = $ruQuery->fetch(PDO::FETCH_ASSOC);
            
            if ($ruData){
                $message['reuse_count'] = $ruData['reuse_count'];
            }

            $fav = $message['favorite'];
            $favIds = explode(',', $fav);
            $message["favorite_conut"] = count($favIds)-1;
        }
        //adsystem------------------

        $message['ads'] = "false";

        $today = date("Y-m-d H:i:s");

        $adsQuery = $pdo->prepare("SELECT * FROM ads WHERE start_date < :today AND limit_date > :today ORDER BY rand()");
        $adsQuery->bindValue(':today', $today);
        $adsQuery->execute();
        $adsresult = $adsQuery->fetch();
        if(!(empty($adsresult))){
            $message['ads'] = "true";
            $message['ads_url'] = $adsresult["url"];
            $message['ads_img_url'] = $adsresult["image_url"];
            $message['ads_memo'] = $adsresult["memo"];
        }
        //--------------------------

        $ueuseItems = array();
        if(!empty($messages)){
            foreach ($messages as $value) {
                if (!(in_array(safetext($value['account']), explode(",", $myblocklist)))){
                    if(!($value["role"] === "ice")){
                        if(filter_var($value['iconname'], FILTER_VALIDATE_URL)){
                            $value['iconname'] = $value['iconname'];
                        }else{
                            $value['iconname'] = "../" . $value['iconname'];
                        }

                        // ""や"none"をnullに変換
                        $value = to_null($value);
                        $value = to_array_safetext($value);

                        $value["role"] = explode(',', $value["role"]);

                        if(!empty($value['rpuniqid'])){
                            $value["type"] = "Reply";
                            //リユーズどうするから始める
                        }elseif(!empty($value['ruuniqid'])){
                            $value["type"] = "Reuse";
                            $reused = getUeuseData($pdo, $value['ruuniqid']); // 例：ruuniqidから元投稿を取得する関数
                            if ($reused) {
                                $reusedUserData = getUserData($pdo, $reused['account']); // 例：元投稿のユーザー情報を取得する関数
                                $reusedUserData["role"] = explode(',', $reusedUserData["role"]);
                                // ""や"none"をnullに変換
                                $reused = to_null($reused);
                                $reused = to_array_safetext($reused);
                                // Reusedataを作成
                                $value["reuse"] = array(
                                    "type" => "Reuse",
                                    "uniqid" => $reused["uniqid"],
                                    "datetime" => $reused["datetime"],
                                    "userid" => $reused["account"],
                                    "userdata" => array(
                                        "userid" => $reusedUserData["userid"],
                                        "username" => $reusedUserData["username"],
                                        "iconurl" => filter_var($reusedUserData['iconname'], FILTER_VALIDATE_URL) 
                                            ? $reusedUserData['iconname'] 
                                            : "../" . $reusedUserData['iconname'],
                                        "role" => $reusedUserData["role"],
                                    ),
                                    "ueuse" => $reused["ueuse"],
                                    "photo1" => $reused["photo1"],
                                    "photo2" => $reused["photo2"],
                                    "photo3" => $reused["photo3"],
                                    "photo4" => $reused["photo4"],
                                    "video1" => $reused["video1"],
                                    "rpuniqid" => $reused["rpuniqid"],
                                    "ruuniqid" => $reused["ruuniqid"],
                                    "nsfw" => filter_var($reused["nsfw"], FILTER_VALIDATE_BOOLEAN),
                                    "favoritecount" => $reused["favorite_conut"],
                                    "replycount" => $reused["reply_count"],
                                    "reusecount" => $reused["reuse_count"],
                                    "is_favorite" => in_array($userId, explode(',', $reused['favorite'])),
                                    "is_bookmark" => in_array($reused["uniqid"], explode(',', $mybookmark)),
                                    "abi" => array(
                                        "abi_text" => $reused["abi"],
                                        "abi_date" => $reused["abidate"],
                                    ),
                                );
                            }else{
                                $value["reuse"] = null;
                            }
                        }else{
                            $value["type"] = "Ueuse";
                        }

                        $ueuse = array(
                            "type" => $value["type"],
                            "uniqid" => $value["uniqid"],
                            "datetime" => $value["datetime"],
                            "userid" => $value["account"],
                            "userdata" => array(
                                "userid" => $value["account"],
                                "username" => $value["username"],
                                "iconurl" => $value['iconname'],
                                "role" => $value["role"],
                            ),
                            "ueuse" => $value["ueuse"],
                            "photo1" => $value["photo1"],
                            "photo2" => $value["photo2"],
                            "photo3" => $value["photo3"],
                            "photo4" => $value["photo4"],
                            "video1" => $value["video1"],
                            "rpuniqid" => $value["rpuniqid"],
                            "ruuniqid" => $value["ruuniqid"],
                            "nsfw" => filter_var($value["nsfw"], FILTER_VALIDATE_BOOLEAN),
                            "favoritecount" => $value["favorite_conut"],
                            "replycount" => $value["reply_count"],
                            "reusecount" => $value["reuse_count"],
                            "is_favorite" => in_array($userId, explode(',', $value['favorite'])),
                            "is_bookmark" => in_array($value["uniqid"], explode(',', $mybookmark)),
                            "abi" => array(
                                "abi_text" => $value["abi"],
                                "abi_date" => $value["abidate"],
                            ),
                        );

                        if ($value["type"] === "Reuse") {
                            $ueuse["reuse"] = $value["reuse"];
                        }
            
                        $ueuseItems[] = $ueuse;
                    }
                }
            }

            if($message['ads'] === "true"){
                $adsystem = array(
                    "type" => "Ads",
                    "url" => $message['ads_url'],
                    "imgurl" => $message['ads_img_url'],
                    "memo" => $message['ads_memo'],
                );
            }else{
                $adsystem = null;
            }

            $item = array(
                "success" => true,
                "ueuses" => $ueuseItems,
                "ads" => $adsystem,
            );
    
            echo json_encode($item, JSON_UNESCAPED_UNICODE);
        }else{
            $item = array(
                "success" => false,
                "ueuses" => null,
                "ads" => null,
                "error" => "no_ueuse",
            );
            echo json_encode($item, JSON_UNESCAPED_UNICODE);
        }
        
        $pdo = null;
    }
}else{
    $item = array(
        "success" => false,
        "ueuses" => null,
        "ads" => null,
        "error" => "bad_request",
    );
    echo json_encode($item, JSON_UNESCAPED_UNICODE);
}
?>
