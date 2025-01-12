<?php 
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);


require('../db.php');
require("../function/function.php");


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
session_regenerate_id(true);

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$authcode = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;

try {

    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

} catch(PDOException $e) {

    // 接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}

//ログイン認証---------------------------------------------------
blockedIP($_SERVER['REMOTE_ADDR']);
$is_login = uwuzuUserLogin($_SESSION, $_COOKIE, $_SERVER['REMOTE_ADDR'], "user");
if($is_login === false){
	header("Location: ../index.php");
	exit;
}else{
	$userid = safetext($is_login['userid']);
	$username = safetext($is_login['username']);
	$loginid = safetext($is_login["loginid"]);
	$role = safetext($is_login["role"]);
	$sacinfo = safetext($is_login["sacinfo"]);
	$myblocklist = safetext($is_login["blocklist"]);
	$myfollowlist = safetext($is_login["follow"]);
	$is_Admin = safetext($is_login["admin"]);
}
$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

require('../logout/logout.php');

if(isset($_SESSION["migration_code"]) && isset($_SESSION["encryption_key"]) && isset($_SESSION["encryption_ivkey"])){
	if(!(empty($_SESSION["migration_code"]) && empty($_SESSION["encryption_key"]) && empty($_SESSION["encryption_ivkey"]))){
		$migration_code = $_SESSION["migration_code"];
		$key1 = $_SESSION["encryption_key"];
		$key2 = $_SESSION["encryption_ivkey"];
		$key = $key1.$key2;
	}else{
		$migration_code = "再表示はできません。";
		$key = "再表示はできません。";
	}
	
	$_SESSION["migration_code"] = array();
	$_SESSION["encryption_key"] = array();
	$_SESSION["encryption_ivkey"] = array();
}else{
	$migration_code = "再表示はできません。";
	$key = "再表示はできません。";
}

?>


<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/home.css">
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>アカウント移行準備完了 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

</head>

<body>
<?php require('../require/leftbox.php');?>
	<main>

	<?php if( !empty($error_message) ): ?>
		<ul class="errmsg">
			<?php foreach( $error_message as $value ): ?>
				<p>・ <?php echo $value; ?></p>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

    <div class="emojibox">
    <h1>アカウントの移行準備完了</h1>
		<div class="formarea">
        <p>アカウントの移行準備が完了しました！</p>
		<p>以降に必要な情報は以下のものです。ここから先は移行先のサーバーで作業を行ってください。</p>
		<ul class="errmsg">
			<p>以下の情報はご自身以外の方に絶対に公開しないでください！<br>このページを離れるとこの情報を再度表示することはできません。</p>
		</ul>
		<p>識別コード : <?php echo safetext($migration_code);?><br>
			認証コード : <?php echo safetext($key);?><br>
		</p>
		</div>
        
        <a href="index" class="irobutton">戻る</a>
    </div>
    </main>

<?php require('../require/rightbox.php');?>
<?php require('../require/botbox.php');?>
<?php require('../require/noscript_modal.php');?>
</body>

</html>