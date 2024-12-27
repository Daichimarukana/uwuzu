<?php
require("../function/function.php");

$serverstopfile = "../server/serverstop.txt";

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$domain = $_SERVER['HTTP_HOST'];

if(!empty(file_get_contents($serverstopfile))){
    $serverstop = safetext(file_get_contents($serverstopfile)); 
}else{
    $serverstop = "現在原因不明の問題によりサーバーを停止しております。";
}

?>
<!DOCTYPE html>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="/css/home.css">
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="/js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>サーバー停止中 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
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
    <h1>サーバー停止中</h1>
    <p>現在サーバーが管理者によって停止されています...<br>停止の理由は以下の通りです。<br>(ADMIN_SERVER_STOP)</p>

    <hr>
    <p><?php
    $s_stop = explode("\n", $serverstop);
    foreach ($s_stop as $info) {
        echo $info.'<br>';
    }?></p>

    </div>

</div>


</body>

</html>