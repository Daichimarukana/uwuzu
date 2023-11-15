<!DOCTYPE html>

<?php
require('db.php');

session_name('uwuzu_s_id');
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
$servericonfile = "server/servericon.txt";

//-------------------------

$servernamefile = "server/servername.txt";

$serverlogofile = "server/serverlogo.txt";
$serverlogodata = file_get_contents($serverlogofile);
$serverlogodata = explode( "\n", $serverlogodata );
$cnt = count( $serverlogodata );
for( $i=0;$i<$cnt;$i++ ){
    $serverlogo_link[$i] = ($serverlogodata[$i]);
}

//------------------------

$serverinfofile = 'server/info.txt';
$serverinfo = file_get_contents($serverinfofile);

//-------------------------------------

$domain = $_SERVER['HTTP_HOST'];

//------------------------

$contactfile = "server/contact.txt";

//------------------------

$onlyuserfile = "server/onlyuser.txt";
$onlyuser = file_get_contents($onlyuserfile);

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
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="css/style.css">
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
<title><?php echo file_get_contents($servernamefile);?></title>
</head>

    
<script src="js/back.js"></script>



<body>

<div class="leftbox">
    <?php if(!empty($serverlogo_link[1])){ ?>
        <div class="logo">
            <a href="../index.php"><img src=<?php echo htmlspecialchars($serverlogo_link[1], ENT_QUOTES, 'UTF-8');?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="../index.php"><img src="../img/uwuzulogo.svg"></a>
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
        <h1><?php echo file_get_contents($servernamefile);;?>へようこそ！</h1>
        <?php if( !empty(file_get_contents($servericonfile)) ){ ?>
            <div class="servericon">
                <img src="<?php echo htmlspecialchars(file_get_contents($servericonfile), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="textzone">
                    <div class="p3"><?php echo file_get_contents($servernamefile);?></div>
                    <div class="p2c"><?php echo $domain;?></div>
                </div>
            </div>
        <?php }else{?>
            <div class="p3"><?php echo file_get_contents($servernamefile);?></div>
            <div class="p2c"><?php echo $domain;?></div>
        <?php }?>

        <p><?php
        $sinfo = explode("\n", $serverinfo);
        foreach ($sinfo as $info) {
            echo $info.'<br>';
        }?></p>

        <a class="maillink" href="mailto:<?php echo file_get_contents($contactfile);?>">お問い合わせ : <?php echo file_get_contents($contactfile);?></a>

        <?php if($onlyuser === "true"){?>
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
    </div>
</div>

</body>

</html>