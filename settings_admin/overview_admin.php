<?php
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$serverinfofile = '../server/info.txt';
$serverinfo = file_get_contents($serverinfofile);

$servertermsfile = '../server/terms.txt';
$serverterms = file_get_contents($servertermsfile);

$serverprvfile = '../server/privacypolicy.txt';
$serverprv = file_get_contents($serverprvfile);

function random_code($length = 8){
    return substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}
function mb_to_gb($megabyte){
	$n_mb = $megabyte / 1024;
    return round($n_mb, 1);
}


require('../db.php');
require("../function/function.php");


// 変数の初期化
$datetime = array();
$user_name = null;
$message = array();
$message_data = null;
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;

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
if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] == true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', safetext($_SESSION['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_SESSION['loginid'] === $res["loginid"] && $_SESSION['userid'] == $res["userid"]){
	// セッションに値をセット
	$userid = safetext($res['userid']); // セッションに格納されている値をそのままセット
	$username = safetext($res['username']); // セッションに格納されている値をそのままセット
	$loginid = safetext($res["loginid"]);
	$role = safetext($res["role"]);
	$sacinfo = safetext($res["sacinfo"]);
	$myblocklist = safetext($res["blocklist"]);
	$myfollowlist = safetext($res["follow"]);
	$_SESSION['admin_login'] = true;
	$_SESSION['userid'] = $userid;
	$_SESSION['username'] = $username;
	$_SESSION['loginid'] = $res["loginid"];
	setcookie('userid', $userid, [
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('username', $username,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('loginid', $res["loginid"],[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('admin_login', true,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	}else{
		header("Location: ../login.php");
		exit;
	}

		
} elseif (isset($_COOKIE['admin_login']) && $_COOKIE['admin_login'] == true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', safetext($_COOKIE['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_COOKIE['loginid'] === $res["loginid"] && $_COOKIE['userid'] == $res["userid"]){
	// セッションに値をセット
	$userid = safetext($res['userid']); // クッキーから取得した値をセット
	$username = safetext($res['username']); // クッキーから取得した値をセット
	$loginid = safetext($res["loginid"]);
	$role = safetext($res["role"]);
	$sacinfo = safetext($res["sacinfo"]);
	$myblocklist = safetext($res["blocklist"]);
	$myfollowlist = safetext($res["follow"]);
	$_SESSION['admin_login'] = true;
	$_SESSION['userid'] = $userid;
	$_SESSION['username'] = $username;
	$_SESSION['loginid'] = $res["loginid"];
	setcookie('userid', $userid,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('username', $username,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('loginid', $res["loginid"],[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('admin_login', true,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	}else{
		header("Location: ../login.php");
		exit;
	}


} else {
	// ログインが許可されていない場合、ログインページにリダイレクト
	header("Location: ../login.php");
	exit;
}
if(empty($userid)){
	header("Location: ../login.php");
	exit;
} 
if(empty($username)){
	header("Location: ../login.php");
	exit;
}

if(!($res["admin"] === "yes")){
	header("Location: ../login.php");
	exit;
}
$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

//User
$result = $mysqli->query("SELECT userid FROM account");
$count1 = $result->num_rows;
//ueuse
$result2 = $mysqli->query("SELECT uniqid FROM ueuse");
$count2 = $result2->num_rows;
//emoji
$result3 = $mysqli->query("SELECT sysid FROM emoji");
$count3 = $result3->num_rows;
//bot
$result4 = $mysqli->query("SELECT userid FROM account WHERE sacinfo = 'bot'");
$count4 = $result4->num_rows;

if(function_exists("disk_free_space")){
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		$disk = true;
		$diskFree = (int) disk_free_space('C:') / 1024 / 1024;
		$diskTotal = (int) disk_total_space('C:') / 1024 / 1024;
		$diskUmari = $diskTotal - $diskFree;
		if ($diskFree / $diskTotal < 0.1) {
			$disk_over90p = true;
		}else{
			$disk_over90p = false;
		}
	
		$loadAve = null;
	} else {
		$disk = true;
		$diskFree = (int) disk_free_space('/') / 1024 / 1024;
		$diskTotal = (int) disk_total_space('/') / 1024 / 1024;
		$diskUmari = $diskTotal - $diskFree;
		if ($diskFree / $diskTotal < 0.1) {
			$disk_over90p = true;
		}else{
			$disk_over90p = false;
		}
		if(function_exists("sys_getloadavg")){
			$loadAve = sys_getloadavg()[0];
		}else{
			$loadAve = null;
		}
	}
}else{
	$disk = false;
	$diskFree = 5000;
	$diskUmari = 5000;
	$diskTotal = 10000;
	$disk_over90p = false;
	if(function_exists("sys_getloadavg")){
		$loadAve = sys_getloadavg()[0];
	}else{
		$loadAve = null;
	}
}

require('../logout/logout.php');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/home.css">
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>サーバー概要 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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
		<div class="admin_settings">
			<?php require('settings_left_menu.php');?>
		
			<div class="admin_right">
				<div class="formarea">
					<h1>サーバー概要</h1>
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
							</div>
						<?php }else{?>
							<img src="<?php echo safetext($serversettings["serverinfo"]["server_icon"]); ?>">
						<?php }?>
					</div>
					<?php }?>
					<!--(サーバーアイコンここまで)-->
					<p>サーバー名</p>
					<p><?php if( !empty(safetext($serversettings["serverinfo"]["server_name"])) ){ echo safetext($serversettings["serverinfo"]["server_name"]); } ?></p>
					<hr>
					<p>サーバー紹介メッセージ</p>
					<p><?php $sinfo = explode("\n", $serverinfo); foreach ($sinfo as $info) { echo nl2br(safetext($info)); }?></p>
					<hr>
					<p>サーバー管理者の名前</p>
					<p><?php if( !empty(safetext($serversettings["serverinfo"]["server_admin"])) ){ echo safetext($serversettings["serverinfo"]["server_admin"]); } ?></p>
					<hr>
					<p>サーバーへのお問い合わせ用メールアドレス</p>
					<p><?php if( !empty(safetext($serversettings["serverinfo"]["server_admin_mailadds"])) ){ echo safetext($serversettings["serverinfo"]["server_admin_mailadds"]); } ?></p>
					<hr>
					<p>統計情報</p>
					<div class="overview">
						<div class="overview_cnt_l">
							<div class="p2">ユーザー数</div>
							<p><?php echo safetext($count1);?></p>
						</div>
						<div class="overview_cnt_r">
							<div class="p2">投稿数</div>
							<p><?php echo safetext($count2);?></p>
						</div>
					</div>
					<div class="overview">
						<div class="overview_cnt_l">
							<div class="p2">カスタム絵文字数</div>
							<p><?php echo safetext($count3);?></p>
						</div>
						<div class="overview_cnt_r">
							<div class="p2">Botアカウント数</div>
							<p><?php echo safetext($count4);?></p>
						</div>
					</div>
					<hr>
					<p>ディスク空き容量</p>
					<?php if($disk == true){?>
						<?php if($disk_over90p == true){?>
							<p class="errmsg">90%以上が使用済みです。<br>早急に容量拡張などの対応を考えてください！</p>
						<?php }else{?>
							<p>ディスク空き容量には余裕があります。</p>
						<?php };?>
						<div class="graph">
							<div class="per" style="width:calc(<?php echo round((int)mb_to_gb($diskUmari) / (int)mb_to_gb($diskTotal) * 100, 1);?>% - 8px);">
							</div>
						</div>
						<p>使用済み : <?php echo mb_to_gb($diskUmari)."GB/".mb_to_gb($diskTotal);?>GB<br>
						空き容量 : <?php echo mb_to_gb($diskFree);?>GB</p>
					<?php }else{?>
						<p>空き容量の取得ができませんでした。</p>
					<?php };?>
					<hr>
					<p>ロードアベレージ</p>
					<div class="p2">ロードアベレージはCPUのコア数と照らし合わせて活用してください。<br>
						"ロードアベレージ/CPUコア数"で計算をした時に1.0を超えると処理が重くなっています。<br>
						※Windows環境ではロードアベレージの取得はできません。</div>

					<?php if(empty($loadAve)){?>
						<p>ロードアベレージの取得ができませんでした。</p>
					<?php }else{?>
						<p>過去1分間のロードアベレージ : <?php echo $loadAve?></p>
					<?php };?>
				</div>
			</div>
		</div>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>
</body>

</html>