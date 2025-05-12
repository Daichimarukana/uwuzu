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


//------------------------

$serverinfofile = 'server/info.txt';
$serverinfo = file_get_contents($serverinfofile);

//-------------------------------------

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

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$result = $mysqli->query("SELECT userid FROM account ORDER BY datetime");

/* 結果セットの行数を取得します */
$count1 = $result->num_rows;

$result2 = $mysqli->query("SELECT uniqid FROM ueuse ORDER BY datetime");

/* 結果セットの行数を取得します */
$count2 = $result2->num_rows;

?>

<html lang="ja">
<head prefix="og:http://ogp.me/ns#">
<meta charset="utf-8">
<!--OGPはじまり-->
<meta property="og:title" content="<?php echo safetext($serversettings["serverinfo"]["server_name"]);?>">
<meta property="og:description" content="<?php echo safetext(preg_replace('/\r\n|\r|\n/', '', $serverinfo));?>">
<meta property="og:url" content="https://<?php echo safetext($domain); ?>/">
<meta property="og:image" content="<?php echo safetext($serversettings["serverinfo"]["server_icon"]);?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo safetext($serversettings["serverinfo"]["server_name"]);?>">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo safetext($serversettings["serverinfo"]["server_name"]);?>"/>
<meta name="twitter:description" content="<?php echo safetext(preg_replace('/\r\n|\r|\n/', '', $serverinfo));?>"/>
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
<title><?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
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
        <h1><?php echo safetext($serversettings["serverinfo"]["server_name"]);?>へようこそ！</h1>

            <!--(サーバーアイコン)-->
                <?php if( !empty($serversettings["serverinfo"]["server_head"]) ){ ?>
                    <div class="serverhead">
                        <img src="<?php echo safetext($serversettings["serverinfo"]["server_head"]); ?>">
                    </div>
                <?php }?>
                <?php if( !empty($serversettings["serverinfo"]["server_icon"]) ){ ?>
                    <div class="servericon">
                        <?php if( !empty($serversettings["serverinfo"]["server_head"]) ){ ?>
                            <div class="up">
                                <img src="<?php echo safetext($serversettings["serverinfo"]["server_icon"]); ?>">
                                <div class="p3"><?php echo safetext($serversettings["serverinfo"]["server_name"]);?></div>
                                <div class="p2c"><?php echo $domain;?></div>
                            </div>
                        <?php }else{?>
                            <img src="<?php echo safetext($serversettings["serverinfo"]["server_icon"]); ?>">
                            <div class="textzone">
                                <div class="p3"><?php echo safetext($serversettings["serverinfo"]["server_name"]);?></div>
                                <div class="p2c"><?php echo $domain;?></div>
                            </div>
                        <?php }?>
                    </div>
                <?php }else{?>
                    <div class="p3"><?php echo safetext($serversettings["serverinfo"]["server_name"]);?></div>
                    <div class="p2c"><?php echo $domain;?></div>
                <?php }?>
            <!--(サーバーアイコンここまで)-->

        <p><?php
        $sinfo = explode("\n", $serverinfo);
        foreach ($sinfo as $info) {
            echo $info.'<br>';
        }?></p>

        <a class="maillink" href="mailto:<?php echo safetext($serversettings["serverinfo"]["server_admin_mailadds"]);?>">お問い合わせ : <?php echo safetext($serversettings["serverinfo"]["server_admin_mailadds"]);?></a>

        <?php if(safetext($serversettings["serverinfo"]["server_invitation"]) === "true"){?>
            <p>このサーバーには招待コードがないと登録できません。<br>招待コードはお手元にありますか？</p>
        <?php }else{?>
    
        <div class="cntzone">
            <div class="usercnt">
                <div class="p1">ユーザー数</div>
                <p><?php echo $count1."<br>"?></p>
            </div>
            <div class="usercnt">
                <div class="p1">投稿数</div>
                <p><?php echo $count2."<br>"?></p>
            </div>
        </div>
        
        <?php }?>
        <div class="btnbox">
            <a href="new_select.php" class="irobutton">アカウント登録</a>
            <a href="login.php" class="sirobutton">ログイン</a>
        </div>
        
        <div class="p2" style="margin-top:8px;margin-bottom:0px;"><?php echo $uwuzuinfo[0];?> Version <?php echo $uwuzuinfo[1];?></div>
    </div>
</div>

</body>

</html>