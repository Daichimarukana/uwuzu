<!DOCTYPE html>

<?php
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
<title>400 Bad Request - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>
</head>

<body>

<div class="topbox">
    <?php if(!empty(htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8'))){ ?>
        <div class="logo">
            <a href="/index.php"><img src=<?php echo htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8');?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="/index.php"><img src="/img/uwuzulogo.svg"></a>
        </div>
    <?php }?>
</div>

<div class="terms">

    <div class="p3"><?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></div>
    <div class="p2c"><?php echo $domain;?></div>

    <div class="err404">
        <h1>400 Bad Request</h1>
        <p><(＿　＿)><br>はいっ！エラーです！！！<br>原因はわかりません！！！！！！！！</p>
    </div>

    <a href="/home/" class="irobutton">ホームへ行く</a>

</div>


</body>

</html>