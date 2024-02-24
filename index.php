<!DOCTYPE html>

<?php
require('db.php');

session_name('uwuzu_s_id');
session_set_cookie_params(0, '', '', true, true);
session_start();


if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true && isset($_COOKIE['loginid']) && isset($_SESSION['userid'])) {
    $option = array(
        // SQL実行失敗時に例外をスルー
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // デフォルトフェッチモードを連想配列形式に設定
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // バッファードクエリを使う（一度に結果セットを全て取得し、サーバー負荷を軽減）
        // SELECTで得た結果に対してもrowCountメソッドを使えるようにする
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    );
    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
    $acck = $dbh->prepare("SELECT userid, loginid FROM account WHERE userid = :userid");
    $acck->bindValue(':userid', $_SESSION['userid']);
    $acck->execute();
    $acck_data = $acck->fetch();
    if(!empty($acck_data)){
        if($_COOKIE['loginid'] === $acck_data["loginid"] && $_SESSION['userid'] === $acck_data["userid"] ){
            header("Location: home/index.php");
            exit;
        }
    }
} elseif (isset($_COOKIE['admin_login']) && $_COOKIE['admin_login'] == true && isset($_COOKIE['loginid']) && isset($_COOKIE['userid'])) {
    $option = array(
        // SQL実行失敗時に例外をスルー
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // デフォルトフェッチモードを連想配列形式に設定
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // バッファードクエリを使う（一度に結果セットを全て取得し、サーバー負荷を軽減）
        // SELECTで得た結果に対してもrowCountメソッドを使えるようにする
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    );
    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
    $acck = $dbh->prepare("SELECT userid, loginid FROM account WHERE userid = :userid");
    $acck->bindValue(':userid', $_COOKIE['userid']);
    $acck->execute();
    $acck_data = $acck->fetch();
    if(!empty($acck_data)){
        if($_COOKIE['loginid'] === $acck_data["loginid"] && $_COOKIE['userid'] === $acck_data["userid"] ){
            header("Location: home/index.php");
            exit;
        }
    }
}

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
<meta property="og:title" content="<?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?>">
<meta property="og:description" content="<?php echo htmlentities($serverinfo);?>">
<meta property="og:url" content="https://<?php echo htmlentities($domain, ENT_QUOTES, 'UTF-8'); ?>/">
<meta property="og:image" content="<?php echo htmlspecialchars($serversettings["serverinfo"]["server_icon"], ENT_QUOTES, 'UTF-8');?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?>">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?>"/>
<meta name="twitter:description" content="<?php echo htmlentities($serverinfo);?>"/>
<!--OGPここまで-->
<link rel="stylesheet" href="css/style.css">
<script src="js/unsupported.js"></script>
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
<title><?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>
</head>

    
<script src="js/back.js"></script>



<body>

<div class="leftbox">
    <?php if(!empty(htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8'))){ ?>
        <div class="logo">
            <a href="../index.php"><img src=<?php echo htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8');?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="../index.php"><img src="img/uwuzulogo.svg"></a>
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
        <h1><?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?>へようこそ！</h1>

            <!--(サーバーアイコン)-->
                <?php if( !empty($serversettings["serverinfo"]["server_head"]) ){ ?>
                    <div class="serverhead">
                        <img src="<?php echo htmlspecialchars($serversettings["serverinfo"]["server_head"], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                <?php }?>
                <?php if( !empty($serversettings["serverinfo"]["server_icon"]) ){ ?>
                    <div class="servericon">
                        <?php if( !empty($serversettings["serverinfo"]["server_head"]) ){ ?>
                            <div class="up">
                                <img src="<?php echo htmlspecialchars($serversettings["serverinfo"]["server_icon"], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="p3"><?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></div>
                                <div class="p2c"><?php echo $domain;?></div>
                            </div>
                        <?php }else{?>
                            <img src="<?php echo htmlspecialchars($serversettings["serverinfo"]["server_icon"], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="textzone">
                                <div class="p3"><?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></div>
                                <div class="p2c"><?php echo $domain;?></div>
                            </div>
                        <?php }?>
                    </div>
                <?php }else{?>
                    <div class="p3"><?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></div>
                    <div class="p2c"><?php echo $domain;?></div>
                <?php }?>
            <!--(サーバーアイコンここまで)-->

        <p><?php
        $sinfo = explode("\n", $serverinfo);
        foreach ($sinfo as $info) {
            echo $info.'<br>';
        }?></p>

        <a class="maillink" href="mailto:<?php echo htmlspecialchars($serversettings["serverinfo"]["server_admin_mailadds"], ENT_QUOTES, 'UTF-8');?>">お問い合わせ : <?php echo htmlspecialchars($serversettings["serverinfo"]["server_admin_mailadds"], ENT_QUOTES, 'UTF-8');?></a>

        <?php if(htmlspecialchars($serversettings["serverinfo"]["server_invitation"], ENT_QUOTES, 'UTF-8') === "true"){?>
            <p>このサーバーには招待コードがないと登録できません。<br>招待コードはお手元にありますか？</p>

            <div class="btnbox">
                <a href="new.php" class="irobutton">アカウント登録</a>
                <a href="login.php" class="sirobutton">ログイン</a>
            </div>
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
        <div class="btnbox">
            <a href="new.php" class="irobutton">アカウント登録</a>
            <a href="login.php" class="sirobutton">ログイン</a>
        </div>
        <?php }?>
        <div class="p2" style="margin-top:8px;margin-bottom:0px;"><?php echo $uwuzuinfo[0];?> Version <?php echo $uwuzuinfo[1];?></div>
    </div>
</div>

</body>

</html>