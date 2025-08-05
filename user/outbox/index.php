<?php
require('../../db.php');
require("../../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

$serversettings_file = "../../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);
if (safetext($serversettings["serverinfo"]["server_activitypub"]) === "true") {
    header("Content-Type: application/activity+json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");

    $domain = $_SERVER['HTTP_HOST'];

    try {
        $pdo = new PDO('mysql:charset=utf8mb4;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }

    $user = safetext($_GET['actor'] ?? '');
    $userid = str_replace('@', '', str_replace('@' . $domain, '', $user));
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
    $itemsPerPage = 10;
    $offset = ($page > 0 ? ($page - 1) : 0) * $itemsPerPage;

    $dbh = new PDO('mysql:charset=utf8mb4;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ]);

    $userQuery = $dbh->prepare("SELECT * FROM account WHERE userid = :userid");
    $userQuery->bindValue(':userid', $userid);
    $userQuery->execute();
    $userData = $userQuery->fetch();

    if (!$userData) {
        echo json_encode(["type" => "user_not_found"]);
        exit;
    }

    // 全投稿数取得
    $countQuery = $dbh->prepare("SELECT COUNT(*) as cnt FROM ueuse WHERE account = :userid AND rpuniqid = ''");
    $countQuery->bindValue(':userid', $userid);
    $countQuery->execute();
    $totalItems = (int)$countQuery->fetch()['cnt'];

    if ($page === 0) {
        // OrderedCollection（firstのみ）
        echo json_encode([
            "@context" => "https://www.w3.org/ns/activitystreams",
            "id" => "https://{$domain}/user/outbox/?actor=@{$userid}",
            "type" => "OrderedCollection",
            "totalItems" => $totalItems,
            "first" => "https://{$domain}/user/outbox/?actor=@{$userid}&page=1"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ページ投稿取得
    $messageQuery = $dbh->prepare("SELECT * FROM ueuse WHERE account = :userid AND rpuniqid = '' ORDER BY datetime DESC LIMIT :offset, :limit");
    $messageQuery->bindValue(':userid', $userid);
    $messageQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
    $messageQuery->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $messageQuery->execute();
    $messages = $messageQuery->fetchAll();

    $orderedItems = [];
    foreach ($messages as $value) {
        $id = $value["uniqid"];
        $url = "https://{$domain}/ueuse/activity/?id={$id}";
        $orderedItems[] = [
            "type" => "Create",
            "id" => $url,
            "url" => $url,
            "published" => date(DATE_ATOM, strtotime($value["datetime"])),
            "to" => ["https://www.w3.org/ns/activitystreams#Public"],
            "actor" => "https://{$domain}/actor/?actor=@{$userid}",
            "object" => [
                "type" => "Note",
                "@context" => "https://www.w3.org/ns/activitystreams",
                "id" => $url,
                "url" => $url,
                "published" => date(DATE_ATOM, strtotime($value["datetime"])),
                "to" => ["https://www.w3.org/ns/activitystreams#Public"],
                "attributedTo" => "https://{$domain}/@{$value["account"]}",
                "content" => preg_replace('/\r\n/i', "", nl2br($value["ueuse"])),
            ]
        ];
    }

    // prev/next リンク
    $base_url = "https://{$domain}/user/outbox/?actor=@{$userid}";
    $prev_page = $page > 1 ? $base_url . "&page=" . ($page - 1) : null;
    $next_page = ($offset + $itemsPerPage < $totalItems) ? $base_url . "&page=" . ($page + 1) : null;

    $response = [
        "@context" => "https://www.w3.org/ns/activitystreams",
        "id" => "{$base_url}&page={$page}",
        "type" => "OrderedCollectionPage",
        "partOf" => $base_url,
        "totalItems" => $totalItems,
        "orderedItems" => $orderedItems,
    ];

    if ($prev_page) $response["prev"] = $prev_page;
    if ($next_page) $response["next"] = $next_page;

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} else {
    header("HTTP/1.1 410 Gone");
}
?>
