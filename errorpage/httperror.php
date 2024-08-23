<!DOCTYPE html>

<?php
require("../function/function.php");

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$custom404file = "../server/404imagepath.txt";
$custom503file = "../server/503imagepath.txt";

//-------------------------------------

$domain = $_SERVER['HTTP_HOST'];

//------------------------

$error_code = (int)http_response_code();
$error_name = "200 OK";
$error_msg = "エラーはありません。";

switch ($error_code) {
    case 200:
        $error_name = "200 OK";
        $error_msg = "エラーはありません。\n正常に表示されています。";
        break;
    case 400:
        $error_name = "400 Bad Request";
        $error_msg = "<(＿　＿)>\nはいっ！エラーです！！！\n原因はわかりません！！！！！！！！";
        break;
    case 401:
        $error_name = "401 Unauthorized";
        $error_msg = "...(*￣０￣)ノ< アクセス権が無いようです\nサービス管理者によってアクセス権の変更をされた可能性がございます。";
        break;
    case 403:
        $error_name = "403 Forbidden";
        $error_msg = "...(*￣０￣)ノ< 閲覧権限が無いようです\nサービス管理者によって閲覧権限の変更をされた可能性がございます。";
        break;
    case 404:
        $error_name = "404 Not found";
        $error_msg = "申し訳ございませんがお探しのページは見つかりませんでした！\nページの移動や削除が行われた可能性がございます。";
        break;
    case 413:
        $error_name = "413 Payload Too Large";
        $error_msg = "アップロードするファイルサイズが大きすぎる可能性があります！\nファイルを圧縮するなどして再度お試しください。";
        break;
    case 500:
        $error_name = "500 Internal Server Error";
        $error_msg = "サーバーが死にました";
        break;
    case 502:
        $error_name = "502 Bad Gateway";
        $error_msg = "通信の中継機器でエラーが発生した可能性があります！\n再度お試しください！";
        break;
    case 503:
        $error_name = "503 Service Unavailable";
        $error_msg = "(´。＿。｀;)< サーバーに過負荷がかかっているようです...\n時間をおいてから再度アクセスしてください！";
        break;
    default:
        $error_name = "Other error";
        $error_msg = "エラーメッセージが用意されていません。\nHTTPステータスコード: ".$error_code;
        break;
}
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
<title><?php echo safetext($error_name)?> - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
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
        <?php 
        if($error_code === 404){
            if(!(empty($custom404file))){
        ?>
            <img src="<?php echo file_get_contents($custom404file);?>">
        <?php 
            }
        }elseif($error_code === 503){
            if(!(empty($custom503file))){?>
            <img src="<?php echo file_get_contents($custom503file);?>">
            <?php
            }
        }
        ?>


        <h1><?php echo safetext($error_name)?></h1>
        <p><?php echo nl2br(safetext($error_msg))?></p>
        <p><?php 
        if($error_code = 404){
            if (rand(1, 100) === 1) {
                echo "さがすのがんばれよ...";
            }
        }
        ?></p>
    </div>

    <a href="/home/" class="irobutton">ホームへ行く</a>

</div>


</body>

</html>

<?php 
if($error_code === 404){
?>
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
<?php
}
?>