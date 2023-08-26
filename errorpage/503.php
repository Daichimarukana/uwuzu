<!DOCTYPE html>

<?php
$servernamefile = "../server/servername.txt";

//-------------------------------------

$domain = $_SERVER['HTTP_HOST'];

//------------------------

$contactfile = "../server/contact.txt";

$termsfile = "../server/terms.txt";
$termsdata = file_get_contents($termsfile);

?>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="/css/home.css">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>503 Service Unavailable - <?php echo file_get_contents($servernamefile);?></title>
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
        <h1>503 Service Unavailable</h1>
        <p>(´。＿。｀;)< サーバーに過負荷がかかっているようです...<br>時間をおいてから再度アクセスしてください！</p>
    </div>

    <a href="/home/" class="irobutton">ホームへ行く</a>

</div>


</body>

</html>