<?php
header('Content-Type: application/json');
require('../db.php');
require('../function/function.php');
blockedIP($_SERVER['REMOTE_ADDR']);
$domain = $_SERVER['HTTP_HOST'];
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

if (safetext(isset($_POST['page'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey'])) && safetext(isset($_POST['id']))) {
    $page = safetext($_POST['page']);
    $userId = safetext($_POST['userid']);
    $uwuzuid = safetext($_POST['id']) ? safetext($_POST['id']) : '';
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);

    if (safetext($serversettings["serverinfo"]["server_activitypub"]) === "true") {
        if (isset($_POST['activity_domain'])) {
            $activity_domain = safetext($_POST['activity_domain']) ? safetext($_POST['activity_domain']) : '';

            if (!($activity_domain == $domain)) {
                $domain_response = GetActivityPubUser($uwuzuid, $activity_domain);
                if (empty($domain_response) || array_key_exists("error", $domain_response)) {
                    $userData = null;
                } else {
                    $userData = $domain_response;
                }
                //var_dump($domain_response);
                $is_local = false;
            } else {
                $is_local = true;
            }
        }
    } else {
        $activity_domain = $domain;
        $is_local = true;
    }


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
        if ($pageNumber <= 0 || (!(is_numeric($pageNumber)))) {
            $pageNumber = 1;
        }
        $offset = ($pageNumber - 1) * $itemsPerPage;

        $messages = array();

        if ($is_local === true) {
            $userQuery = $pdo->prepare("SELECT username, userid, profile, role, follower FROM account WHERE userid = :userid");
            $userQuery->bindValue(':userid', $uwuzuid);
            $userQuery->execute();
            $userData = $userQuery->fetch();

            $messageQuery = $pdo->prepare("SELECT * FROM ueuse WHERE account = :userid AND rpuniqid = ''ORDER BY datetime DESC LIMIT :offset, :itemsPerPage");
            $messageQuery->bindValue(':userid', $uwuzuid);
            $messageQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
            $messageQuery->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
            $messageQuery->execute();
            $message_array = $messageQuery->fetchAll();

            foreach ($message_array as $row) {
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

                if ($rpData) {
                    $message['reply_count'] = $rpData['reply_count'];
                }

                //リユーズ数取得
                $ruQuery = $pdo->prepare("SELECT COUNT(*) as reuse_count FROM ueuse WHERE ruuniqid = :ruuniqid");
                $ruQuery->bindValue(':ruuniqid', $message['uniqid']);
                $ruQuery->execute();
                $ruData = $ruQuery->fetch(PDO::FETCH_ASSOC);

                if ($ruData) {
                    $message['reuse_count'] = $ruData['reuse_count'];
                }

                $fav = $message['favorite'];
                $favIds = explode(',', $fav);
                $message["favorite_conut"] = count($favIds) - 1;
            }
        } elseif($userData != null) {
            $activity_base = GetActivityPubJson($userData['outbox']);
            $pageUrl = $activity_base['first'] ?? null;

            $pageNumber = max(1, (int)$page); // 1ページ目以上に固定
            $currentPageData = null;

            for ($i = 1; $i <= $pageNumber; $i++) {
                if (!$pageUrl) break;

                $currentPageData = GetActivityPubJson($pageUrl);

                // 目的のページに達していなければ next をたどる
                if ($i < $pageNumber) {
                    $pageUrl = $currentPageData['next'] ?? null;
                }
            }
            $orderedItems = $currentPageData['orderedItems'] ?? [];

            $createItems = array_filter($orderedItems, function ($item) {
                return isset($item['type']) && $item['type'] === 'Create';
            });
            $createItems = array_values($createItems);

            foreach ($createItems as $item) {
                // object がURLなら取得
                $object = $item['object'] ?? null;
                if (is_string($object)) {
                    $object = GetActivityPubJson($object);
                }

                // nullや不正なobjectはスキップ
                if (!is_array($object)) continue;

                $contentHtml = $object['content'] ?? '';
                $withNewlines = preg_replace('/<br\s*\/?>/i', "\n", $contentHtml);
                $plainContent = strip_tags($withNewlines);

                $photos = [];
                $video = null;

                if (!empty($object['attachment'])) {
                    $attachments = is_array($object['attachment']) ? $object['attachment'] : [$object['attachment']];

                    foreach ($attachments as $att) {
                        if (!is_array($att)) continue;

                        $mediaType = $att['mediaType'] ?? '';
                        $url = $att['url'] ?? ($att['href'] ?? null);

                        if (!$url) continue;

                        // 画像（mediaTypeで判定）
                        if (str_starts_with($mediaType, 'image/')) {
                            if (count($photos) < 4) {
                                $photos[] = $url;
                            }
                        }

                        // 動画（mediaTypeで判定）
                        if (str_starts_with($mediaType, 'video/') && !$video) {
                            $video = $url;
                        }
                    }
                }

                $messages[] = [
                    "rpuniqid" => "",
                    "ruuniqid" => "",
                    "uniqid" => "",
                    "datetime" => date("Y-m-d H:i:s", strtotime($object["published"] ?? "now")),
                    "account" => $userData["userid"] . "@" . $activity_domain,
                    "username" => $userData["username"],
                    "iconname" => $userData["iconname"],
                    "headname" => $userData["headname"] ?? null,
                    "role" => $userData["role"] ?? "user",
                    "sacinfo" => "",
                    "ueuse" => $plainContent,
                    "photo1" => $photos[0] ?? null,
                    "photo2" => $photos[1] ?? null,
                    "photo3" => $photos[2] ?? null,
                    "photo4" => $photos[3] ?? null,
                    "video1" => $video,
                    "nsfw" => $object["sensitive"] ?? false,
                    "favorite" => "",
                    "favorite_conut" => 0,
                    "reply_count" => 0,
                    "reuse_count" => 0,
                    "abi" => "",
                    "abidate" => null,
                    "activitypub" => true,
                ];
            }
        }else{
            $message = array();
        }


        //adsystem------------------

        $message['ads'] = "false";

        $today = date("Y-m-d H:i:s");

        $adsQuery = $pdo->prepare("SELECT * FROM ads WHERE start_date < :today AND limit_date > :today ORDER BY rand()");
        $adsQuery->bindValue(':today', $today);
        $adsQuery->execute();
        $adsresult = $adsQuery->fetch();
        if (!(empty($adsresult))) {
            $message['ads'] = "true";
            $message['ads_url'] = $adsresult["url"];
            $message['ads_img_url'] = $adsresult["image_url"];
            $message['ads_memo'] = $adsresult["memo"];
        }
        //--------------------------

        $ueuseItems = array();
        if (!empty($messages)) {
            foreach ($messages as $value) {
                $formatted = FormatUeuseItem($value, $myblocklist, $mybookmark, $pdo, $userId);
                if ($formatted !== null) {
                    $ueuseItems[] = $formatted;
                }
            }

            if ($message['ads'] === "true") {
                $adsystem = array(
                    "type" => "Ads",
                    "url" => $message['ads_url'],
                    "imgurl" => $message['ads_img_url'],
                    "memo" => $message['ads_memo'],
                );
            } else {
                $adsystem = null;
            }

            $item = array(
                "success" => true,
                "ueuses" => $ueuseItems,
                "ads" => $adsystem,
            );

            echo json_encode($item, JSON_UNESCAPED_UNICODE);
        } else {
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
} else {
    $item = array(
        "success" => false,
        "ueuses" => null,
        "ads" => null,
        "error" => "bad_request",
    );
    echo json_encode($item, JSON_UNESCAPED_UNICODE);
}
