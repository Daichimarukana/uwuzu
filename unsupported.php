<?php 
require('db.php');
require("function/function.php");

$serversettings_file = "server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

//------------------------

$serverinfofile = 'server/info.txt';
$serverinfo = file_get_contents($serverinfofile);

//-------------------------------------

$domain = $_SERVER['HTTP_HOST'];

//------------------------

$softwarefile = "server/uwuzuinfo.txt";
$softwaredata = file_get_contents($softwarefile);

$softwaredata = explode( "\n", $softwaredata );
$cnt = count( $softwaredata );
for( $i=0;$i<$cnt;$i++ ){
    $uwuzuinfo[$i] = ($softwaredata[$i]);
}

if(isset($_GET['errcode']) || isset($_GET['browser']) || isset($_GET['os']) || isset($_GET['cookie']) || isset($_GET['ssl']) || isset($_GET['block'])){
	if(isset($_GET['errcode'])){$errcode = safetext($_GET['errcode']);}else{$errcode = "NULL";};
    if(isset($_GET['browser'])){$browser = safetext($_GET['browser']);}else{$browser = "NULL";};
    if(isset($_GET['os'])){$os = safetext($_GET['os']);}else{$os = "NULL";};
    if(isset($_GET['cookie'])){$cookie = safetext($_GET['cookie']);}else{$cookie = "NULL";};
    if(isset($_GET['ssl'])){$ssl = safetext($_GET['ssl']);}else{$ssl = "NULL";};
    if(isset($_GET['block'])){$block = safetext($_GET['block']);}else{$block = "NULL";};
}else{
    $errcode = "NULL";
    $browser = "NULL";
    $os = "NULL";
    $cookie = "NULL";
    $ssl = "NULL";
    $block = "NULL";
}

if($errcode == "UNSUPPORTED_BROWSER"){
    $errabout = "対応していないブラウザです。";
}elseif($errcode == "UNSUPPORTED_OS"){
    $errabout = "対応していないOS・端末です。";
}elseif($errcode == "PLEASE_COOKIE_ON"){
    $errabout = "Cookieが無効になっています。";
}elseif($errcode == "NONE_SSL"){
    $errabout = "http通信で表示されていません。";
}elseif($errcode == "NONE_SSL_SERVER"){
    $errabout = "サーバー側でSSLが設定されていません。サーバー管理者にuwuzuの動作にSSLの設定が必要であることを伝えてください。";
}elseif($errcode == "IP_BANNED"){
    $errabout = "お使いの環境のIPアドレスがブロックされています。".safetext($serversettings["serverinfo"]["server_name"])."を使用することはできません。";
}else{
    $errabout = "エラーコードの説明はありません。";
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="css/unsupported.css">
<link rel="apple-touch-icon" type="image/png" href="favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
</head>

<body>

<main>
    <div class="server_icon_zone">
        <?php if( !empty($serversettings["serverinfo"]["server_icon"]) ){ ?>
            <img src=<?php echo safetext($serversettings["serverinfo"]["server_icon"]);?>>
        <?php }else{?>
            <img src="/img/uwuzuicon.png">
        <?php }?>
    </div>
    <h1>お使いの環境での利用はできません</h1>
    <div class="maintext">
        <p>申し訳ございませんが、お使いの環境でuwuzuを利用すると問題が発生する恐れがあるため、リダイレクトさせていただきました。
        <br>引き続きuwuzuを使用するには推奨されている環境をご利用ください。
        <br>詳細は下記のリンクよりご確認ください。</p>
    </div>

        <a href="https://docs.uwuzu.xyz/docs/uwuzusupport" class="infobtn">詳細</a>
    
    <div class="maintext">
        <h3>エラー内容</h3>
        <p>エラーコード:<?php echo safetext($errcode);?></p>
        <p>エラーの原因:<?php echo safetext($errabout);?></p>
        <hr>
        <h3>アクセス環境</h3>
        <p>ブラウザ:<?php echo safetext($browser);?></p>
        <p>OS:<?php echo safetext($os);?></p>
        <p>Cookie:<?php if(safetext($cookie) == "cookie_off"){echo "無効";}elseif(safetext($cookie) == "cookie_on"){echo "有効";}else{echo "不明";};?></p>
        <p>通信環境:<?php if(safetext($ssl) == "not_ssl"){echo "非SSL通信";}elseif(safetext($ssl) == "ssl"){echo "SSL通信";}elseif(safetext($ssl) == "Other"){echo "非http通信";}else{echo "不明";};?></p>
    </div>  

</main>
<hr>
<div class="center_text">
    <p><?php echo safetext($serversettings["serverinfo"]["server_name"]);?></p>
    <p><?php echo $domain;?></p>
    <div class="p2"><?php echo safetext($uwuzuinfo[0]);?><br>Version <?php echo safetext($uwuzuinfo[1]);?></div>
</div>
</body>
