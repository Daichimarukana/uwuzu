<!DOCTYPE html>

<?php
require("../function/function.php");

$custom503file = "../server/503imagepath.txt";

//-------------------------

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);


//-------------------------------------

$domain = $_SERVER['HTTP_HOST'];

//------------------------

?>

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
        <?php if(!(empty($custom503file))){?>
            <img src="<?php echo file_get_contents($custom503file);?>">
        <?php }?>
        <h1>503 Service Unavailable</h1>
        <p>(´。＿。｀;)< サーバーに過負荷がかかっているようです...<br>時間をおいてから再度アクセスしてください！</p>
    </div>

    <a href="/home/" class="irobutton">ホームへ行く</a>

</div>


</body>

</html>