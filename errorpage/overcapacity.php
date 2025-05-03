<?php
http_response_code(503);
require_once(__DIR__.'/../db.php');
require_once(__DIR__."/../function/function.php");

$custom503file = __DIR__."/../server/503imagepath.txt";
$serversettings_file = __DIR__."/../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$domain = $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="/css/home.css">
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>503 Service Unavailable - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

</head>
<body>
<div class="topbox">
    <?php if(!empty(safetext($serversettings["serverinfo"]["server_logo_login"]))){ ?>
        <div class="logo">
            <a href="/index.php"><img src=<?php echo safetext($serversettings["serverinfo"]["server_logo_login"]);?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="/index.php"><img src="/img/uwuzulogo.svg"></a>
        </div>
    <?php }?>
</div>

<div class="terms">
    <div class="p3"><?php echo safetext($serversettings["serverinfo"]["server_name"]);?></div>
    <div class="p2c"><?php echo $domain;?></div>
    <div class="err404">
    <?php if(!(empty(file_get_contents($custom503file)))){?>
            <img src="<?php echo file_get_contents($custom503file);?>">
    <?php } ?>
    <h1><?php echo safetext($serversettings["serverinfo"]["server_name"]);?>の処理能力の限界を超えました</h1>
    <p>ごめんなさい...！<br>
        現在<?php echo safetext($serversettings["serverinfo"]["server_name"]);?>の処理能力では対応できないほどの負荷がかかっています。<br>
        時間をおいてから再度アクセスをお願いいたします。<br>
        (503 Service Unavailable)</p>
    </div>

</div>
</body>
</html>