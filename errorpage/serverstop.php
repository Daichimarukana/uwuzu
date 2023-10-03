<?php
$serverstopfile = "../server/serverstop.txt";

$servernamefile = "../server/servername.txt";

$domain = $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="/css/home.css">
<script src="/js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>サーバー停止中 - </title>
</head>


<body>

<div class="topbox">
    <div class="logo">
        <img src="/img/uwuzulogo.svg">
    </div>
</div>

<div class="terms">

    <div class="p3"><?php echo file_get_contents($servernamefile);?></div>
    <div class="p2c"><?php echo $domain;?></div>
    <div class="err404">
    <h1>サーバー停止中</h1>
    <p>(/´･ヮ･`\)<br>現在サーバーが管理者によって停止されています...<br>停止の理由は以下の通りです。</p>

    <hr>
    <p><?php if( !empty(file_get_contents($serverstopfile)) ){ echo htmlspecialchars(file_get_contents($serverstopfile), ENT_QUOTES, 'UTF-8'); } ?></p>


    </div>

</div>


</body>

</html>