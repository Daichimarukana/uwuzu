<?php 

$servericonfile = "server/servericon.txt";

//-------------------------

$servernamefile = "server/servername.txt";

//------------------------

$serverinfofile = 'server/info.txt';
$serverinfo = file_get_contents($serverinfofile);

//-------------------------------------

$domain = $_SERVER['HTTP_HOST'];

//------------------------

$contactfile = "server/contact.txt";

$softwarefile = "server/uwuzuinfo.txt";
$softwaredata = file_get_contents($softwarefile);

$softwaredata = explode( "\n", $softwaredata );
$cnt = count( $softwaredata );
for( $i=0;$i<$cnt;$i++ ){
    $uwuzuinfo[$i] = ($softwaredata[$i]);
}

if(isset($_GET['errcode']) || isset($_GET['browser']) || isset($_GET['os']) || isset($_GET['cookie']) || isset($_GET['ssl'])){
	if(isset($_GET['errcode'])){$errcode = htmlentities($_GET['errcode']);}else{$errcode = "NULL";};
    if(isset($_GET['browser'])){$browser = htmlentities($_GET['browser']);}else{$browser = "NULL";};
    if(isset($_GET['os'])){$os = htmlentities($_GET['os']);}else{$os = "NULL";};
    if(isset($_GET['cookie'])){$cookie = htmlentities($_GET['cookie']);}else{$cookie = "NULL";};
    if(isset($_GET['ssl'])){$ssl = htmlentities($_GET['ssl']);}else{$ssl = "NULL";};
}else{
    $errcode = "NULL";
    $browser = "NULL";
    $os = "NULL";
    $cookie = "NULL";
    $ssl = "NULL";
}

if($errcode == "UNSUPPORTED_BROWSER"){
    $errabout = "対応していないブラウザです。";
}elseif($errcode == "UNSUPPORTED_OS"){
    $errabout = "対応していないOS・端末です。";
}elseif($errcode == "PLEASE_COOKIE_ON"){
    $errabout = "Cookieが無効になっています。";
}elseif($errcode == "NONE_SSL"){
    $errabout = "http通信で表示されていません。";
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
<title><?php echo file_get_contents($servernamefile);?></title>
</head>

<body>

<main>
    <div class="server_icon_zone">
        <img src=<?php echo htmlentities(file_get_contents($servericonfile));?>>
    </div>
    <h1>お使いの環境での利用はできません</h1>
    <div class="maintext">
        <p>申し訳ございませんが、uwuzuをお使いの環境で使用されますとバグやエラーなどの問題が発生する可能性が非常に高いためこのページにリダイレクトさせていただきました。
        <br>引き続きuwuzuを使用するには推奨されている環境をご利用ください。
        <br>詳細は下記のリンクよりご確認ください。</p>
    </div>

        <a href="https://uwuzu.com/support" class="infobtn">詳細</a>
    
    <div class="maintext">
        <h3>エラー内容</h3>
        <p>エラーコード:<?php echo htmlentities($errcode);?></p>
        <p>エラーの原因:<?php echo htmlentities($errabout);?></p>
        <hr>
        <h3>アクセス環境</h3>
        <p>ブラウザ:<?php echo htmlentities($browser);?></p>
        <p>OS:<?php echo htmlentities($os);?></p>
        <p>Cookie:<?php if(htmlentities($cookie) == "cookie_off"){echo "無効";}elseif(htmlentities($cookie) == "cookie_on"){echo "有効";}else{echo "不明";};?></p>
        <p>通信環境:<?php if(htmlentities($ssl) == "not_ssl"){echo "非SSL通信";}elseif(htmlentities($ssl) == "ssl"){echo "SSL通信";}elseif(htmlentities($ssl) == "Other"){echo "非http通信";}else{echo "不明";};?></p>
    </div>  

</main>
<hr>
<div class="center_text">
    <p><?php echo htmlentities(file_get_contents($servernamefile));?></p>
    <p><?php echo $domain;?></p>
    <div class="p2"><?php echo htmlentities($uwuzuinfo[0]);?><br>Version <?php echo htmlentities($uwuzuinfo[1]);?></div>
</div>
</body>
