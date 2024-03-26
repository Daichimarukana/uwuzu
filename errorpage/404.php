<!DOCTYPE html>

<?php
$custom404file = "../server/404imagepath.txt";

//------------------------

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
<title>404 Not found - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>
</head>

<body>

<div class="topbox">
    <?php if(!empty(htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8'))){ ?>
        <div class="logo">
            <a href="../index.php"><img src=<?php echo htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8');?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="../index.php"><img src="../img/uwuzulogo.svg"></a>
        </div>
    <?php }?>
</div>

<div class="terms">

    <div class="p3"><?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></div>
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
<script>
    const nowTime = new Date().getHours();
    const $background = $("body");
    
    if(nowTime >= 0 && nowTime < 4){
        $background.addClass("night")
    }

</script>
<style>
.night .terms{
    padding: 12px;
    background:linear-gradient(#000315, #4c5f78);
    border-radius:12px;
}
.night .terms .err404 h1{
    margin-top: 64px;
    line-height:64px;
    font-family: 'BIZ UDPGothic', sans-serif;
    font-weight: bold;
    font-size: 64px;
    text-align: center;
    color: #f5f5f5;
}
.night .terms .err404 p{
    margin-top: 2px;
    margin-bottom: 2px;
    line-height:32px;
    font-family: 'BIZ UDPGothic', sans-serif;
    font-weight: normal;
    font-size: 16px;
    text-align: center;
    color: #f5f5f5;
}
.night .terms .p2c{
    margin-top: 0px;
    margin-bottom: 10px;
    text-align: left;
    word-wrap: break-word;
    line-height: 20px;
    color: #CCC;
    font-size: 12px;
    font-family: 'BIZ UDPGothic', sans-serif;
    font-weight: normal;
}
.night .terms .p3{
    margin-top: 24px;
    text-align: left;
    word-wrap: break-word;
    line-height: 24px;
    color: #f5f5f5;
    font-size: 22px;
    font-family: 'BIZ UDPGothic', sans-serif;
    font-weight: bold;
}
</style>