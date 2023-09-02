<!DOCTYPE html>

<?php
require('db.php');

session_name('uwuzu_s_id');
session_start();

if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true) {

    header("Location: home/index.php");
	exit;
	
} elseif (isset($_COOKIE['admin_login']) && $_COOKIE['admin_login'] == true) {

    header("Location: home/index.php");
    exit;

}

$servernamefile = "server/servername.txt";

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

try {

    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

    $stmt = $pdo->prepare("SELECT COUNT(userid) FROM account");
    $stmt->execute();
    $count2 = $stmt->fetchColumn();
  

} catch(PDOException $e) {

    // 接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}

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
    <div class="logo">
        <img src="img/uwuzulogo.svg">
    </div>

    <div class="textbox">

    <?php if( !empty($error_message) ): ?>
        <ul class="errmsg">
            <?php foreach( $error_message as $value ): ?>
                <p>・ <?php echo $value; ?></p>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

        <h1><?php echo file_get_contents($servernamefile);;?>へようこそ！</h1>
        <div class="p3"><?php echo file_get_contents($servernamefile);?></div>
        <div class="p2c"><?php echo $domain;?></div>

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