<!DOCTYPE html>

<?php
$custom404file = "../server/404imagepath.txt";

//------------------------

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
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>404 Not found - <?php echo file_get_contents($servernamefile);?></title>
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
        <?php if(!(empty($custom404file))){?>
            <img src="<?php echo file_get_contents($custom404file);?>">
        <?php }?>
        <h1>404 Not found</h1>
        <p>申し訳ございませんがお探しのページは見つかりませんでした！<br>ページの移動や削除が行われた可能性がございます。</p>
        <p><?php if (rand(1, 100) === 1) {
            echo "さがすのがんばれよ...(?)";
        }
        ?></p>
    </div>

    <a href="/home/" class="irobutton">ホームへ行く</a>

</div>


</body>

</html>