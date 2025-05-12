<!DOCTYPE html>

<?php
require('db.php');
require("function/function.php");

session_name('uwuzu_s_id');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

//ログイン認証---------------------------------------------------
blockedIP($_SERVER['REMOTE_ADDR']);
$is_login = uwuzuUserLogin($_SESSION, $_COOKIE, $_SERVER['REMOTE_ADDR'], "user");
if(!($is_login === false)){
	header("Location: /home/");
	exit;
}
//-------------------------------------------------------------

$serversettings_file = "server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

//-------------------------
$softwarefile = "server/uwuzuinfo.txt";
$softwaredata = file_get_contents($softwarefile);

$softwaredata = explode( "\n", $softwaredata );
$cnt = count( $softwaredata );
for( $i=0;$i<$cnt;$i++ ){
    $uwuzuinfo[$i] = ($softwaredata[$i]);
}
//-------------------------

$domain = $_SERVER['HTTP_HOST'];

//------------------------

// データベースに接続
try {
    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=utf8mb4;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);
} catch (PDOException $e) {
    // 接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}

?>

<html lang="ja">
<head prefix="og:http://ogp.me/ns#">
<meta charset="utf-8">
<!--OGPはじまり-->
<meta property="og:title" content="<?php echo safetext($serversettings["serverinfo"]["server_name"]);?>">
<meta property="og:description" content="<?php echo safetext($serverinfo);?>">
<meta property="og:url" content="https://<?php echo safetext($domain); ?>/">
<meta property="og:image" content="<?php echo safetext($serversettings["serverinfo"]["server_icon"]);?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo safetext($serversettings["serverinfo"]["server_name"]);?>">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo safetext($serversettings["serverinfo"]["server_name"]);?>"/>
<meta name="twitter:description" content="<?php echo safetext($serverinfo);?>"/>
<!--OGPここまで-->
<link rel="stylesheet" href="css/style.css">
<script src="js/jquery-min.js"></script>
<script src="js/unsupported.js"></script>
<script src="js/back.js"></script>
<link rel="apple-touch-icon" type="image/png" href="favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="favicon/icon-192x192.png">
<link rel="manifest" href="manifest/manifest.json" />
<script>
if ("serviceWorker" in navigator) {
	navigator.serviceWorker.register("sw.js").then(reg => {
		console.log("ServiceWorker OK", reg);
	}).catch(err => {
		console.log("ServiceWorker BAD", err);
	});
}
</script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>アカウント登録<?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
</head>

<body>

<div class="leftbox">
    <?php if(!empty(safetext($serversettings["serverinfo"]["server_logo_login"]))){ ?>
        <div class="logo">
            <a href="index.php"><img src=<?php echo safetext($serversettings["serverinfo"]["server_logo_login"]);?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="index.php"><img src="img/uwuzulogo.svg"></a>
        </div>
    <?php }?>

    <div class="textbox">

    <?php if( !empty($error_message) ): ?>
        <ul class="errmsg">
            <?php foreach( $error_message as $value ): ?>
                <p>・ <?php echo $value; ?></p>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
        <h1>アカウントの登録方法</h1>
        <p>アカウントの登録方法を選択してください！</p>
        <div class="p2">通常のアカウント登録では新規アカウント登録です！<br>他のuwuzuサーバーからアカウントを移行して登録する場合はアカウントの移行登録を選択してください。</div>
        <div class="formarea">
            <a href="new.php" class="irobutton">新規アカウント登録</a>
            <?php if(safetext($serversettings["serverinfo"]["server_account_migration"]) === "true"){?>
                <a href="migration/" class="sirobutton">アカウントの移行登録</a>
            <?php }?>
        </div>
        <div class="btnbox">
            <a href="index.php" class="sirobutton">戻る</a>
        </div>
        
        <div class="p2" style="margin-top:8px;margin-bottom:0px;"><?php echo $uwuzuinfo[0];?> Version <?php echo $uwuzuinfo[1];?></div>
    </div>
</div>

</body>

</html>