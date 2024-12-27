<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$mojisizefile = "../server/textsize.txt";

$banuseridfile = "../server/banuserid.txt";
$banuserid_info = file_get_contents($banuseridfile);

$banurldomainfile = "../server/banurldomain.txt";
$banurldomain_info = file_get_contents($banurldomainfile);

function random_code($length = 8){
    return substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}

require('../db.php');
require("../function/function.php");

//hCaptcha--------------------------------------------
require('hCaptcha_settings/hCaptcha_settings.php');
//Cloudflare_Turnstile--------------------------------------------
require('CloudflareTurnstile_settings/CloudflareTurnstile_settings.php');
//----------------------------------------------------

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
		'expires' => time() + 60 * 60 * 24 * 28,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('username', $username,[
		'expires' => time() + 60 * 60 * 24 * 28,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('loginid', $res["loginid"],[
		'expires' => time() + 60 * 60 * 24 * 28,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('admin_login', true,[
		'expires' => time() + 60 * 60 * 24 * 28,
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
		'expires' => time() + 60 * 60 * 24 * 28,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('username', $username,[
		'expires' => time() + 60 * 60 * 24 * 28,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('loginid', $res["loginid"],[
		'expires' => time() + 60 * 60 * 24 * 28,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('admin_login', true,[
		'expires' => time() + 60 * 60 * 24 * 28,
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

if( !empty($pdo) ) {
	
	// データベース接続の設定
	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	));

	$userQuery = $dbh->prepare("SELECT username, userid, profile, role FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $userid);
	$userQuery->execute();
	$userData = $userQuery->fetch();

	$role = $userData["role"];

	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

	$rerole = $dbh->prepare("SELECT username, userid, password, mailadds, profile, iconname, headname, role, datetime FROM account WHERE userid = :userid");

    $rerole->bindValue(':userid', $userid);
    // SQL実行
    $rerole->execute();

    $userdata = $rerole->fetch(); // ここでデータベースから取得した値を $role に代入する

	
}

if (!empty($pdo)) {
    
    $sql = "SELECT code,used,datetime FROM invitation ORDER BY datetime DESC";
    $invcode = $pdo->query($sql);    

    while ($row = $invcode->fetch(PDO::FETCH_ASSOC)) {

        $codes[] = $row;
    }
}

if( !empty($_POST['btn_submit']) ) {

    // 空白除去
	$banuserid = safetext($_POST['banuserid']);

	$banurldomain = safetext($_POST['banurldomain']);

	$max_textsize = safetext($_POST['max_textsize']);

	if((int)$max_textsize > 16777216){
		$error_message[] = "投稿の最大文字数の限界値を超えています。";
	}

	if(empty($error_message)){
		//banuserid
		$file = fopen($banuseridfile, 'w');
		$data = $banuserid;
		fputs($file, $data);
		fclose($file);

		//banurldomain
		$file = fopen($banurldomainfile, 'w');
		$data = $banurldomain;
		fputs($file, $data);
		fclose($file);

		//maxtextsize
		$file = fopen($mojisizefile, 'w');
		$data = $max_textsize;
		fputs($file, $data);
		fclose($file);


		$hCaptcha_ONOFF = safetext($_POST['hCaptcha_onoff']);

		$hCaptcha_sitekey = safetext($_POST['hCaptcha_sitekey']);
		$hCaptcha_seackey = safetext($_POST['hCaptcha_seackey']);

		$New_hCaptcha_Settings = "
		<?php // Captchaの認証情報
		define( 'H_CAPTCHA_ONOFF', '".safetext($hCaptcha_ONOFF)."');// trueならhCaptchaが有効

		define( 'H_CAPTCHA_SITE_KEY', '".safetext($hCaptcha_sitekey)."');
		define( 'H_CAPTCHA_SEAC_KEY', '".safetext($hCaptcha_seackey)."');
		?>
		";

		//設定上書き
		$file = fopen('hCaptcha_settings/hCaptcha_settings.php', 'w');
		$data = $New_hCaptcha_Settings;
		fputs($file, $data);
		fclose($file);

		//CF_Turnstile

		$CF_Turnstile_ONOFF = safetext($_POST['CF_Turnstile_onoff']);

		$CF_Turnstile_sitekey = safetext($_POST['CF_Turnstile_sitekey']);
		$CF_Turnstile_seackey = safetext($_POST['CF_Turnstile_seackey']);

		$New_CF_Turnstile_Settings = "
		<?php // Captchaの認証情報
		define( 'CF_TURNSTILE_ONOFF', '".safetext($CF_Turnstile_ONOFF)."');// trueならCloudflareTurnstileが有効

		define( 'CF_TURNSTILE_SITE_KEY', '".safetext($CF_Turnstile_sitekey)."');
		define( 'CF_TURNSTILE_SEAC_KEY', '".safetext($CF_Turnstile_seackey)."');
		?>
		";

		//設定上書き
		$file = fopen('CloudflareTurnstile_settings/CloudflareTurnstile_settings.php', 'w');
		$data = $New_CF_Turnstile_Settings;
		fputs($file, $data);
		fclose($file);

		$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		header("Location:".$url."");
		exit; 
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
<script src="https://js.hcaptcha.com/1/api.js" async defer></script>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>モデレーション - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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
				<form class="formarea" enctype="multipart/form-data" method="post">
					<h1>モデレーション</h1>
					
					<div>
						<p>登録禁止ユーザーid</p>
						<div class="p2">ここに入力してあるユーザーidは登録できません。<br>改行で禁止するユーザーidを指定できます。<br>すでにあるアカウントは影響を受けません。</div>
						<textarea id="banuserid" placeholder="uwuzu" class="inbox" type="text" name="banuserid"><?php $sinfo = explode("\r", $banuserid_info); foreach ($sinfo as $info) { echo $info; }?></textarea>
					</div>
					<hr>
					<div>
						<p>投稿禁止URLドメイン</p>
						<div class="p2">ここに入力してあるドメインが含まれる投稿をしようとすると投稿が拒否されます。<br>なお、この機能はまだ確実な動作が保証されないためベータ版です。<br>位置情報特定サイトなどの対策等にご利用ください。</div>
						<textarea id="banurldomain" placeholder="" class="inbox" type="text" name="banurldomain"><?php $sinfo = explode("\r", $banurldomain_info); foreach ($sinfo as $info) { echo $info; }?></textarea>
					</div>
					<hr>
					<div>
						<p>投稿の最大文字数</p>
						<div class="p2">ここで設定した文字数までの投稿が可能です。<br>なお、データベースより最大文字数を設定している場合そちらが優先されて使用されます。<br>1文字から16777216文字の間で設定が可能です。<br>※uwuzu version 1.3.0以前にuwuzuを導入された方はuwuzuのDB内のtext型を全てmediumtext型にしてください。</div>
						<input id="max_textsize" placeholder="1024" class="inbox" type="number" min="1" max="16777216" name="max_textsize" value="<?php if( !empty(file_get_contents($mojisizefile)) ){ echo safetext(file_get_contents($mojisizefile)); } ?>">
					</div>
					<hr>
					<div>
						<p>hCaptcha認証</p>
						<div class="p2">hCaptchaを使用し、ログイン時とアカウント登録時に認証をすることができます。<br>もし人間でないと判断された場合はアカウント登録やログイン、パスワード変更を受け付けません。</div>
						<p>hCaptchaのオンオフ</p>
						<div class="switch_button">
							<?php if(!empty(H_CAPTCHA_ONOFF && H_CAPTCHA_ONOFF == "true")){?>
								<input id="hCaptcha_onoff" class="switch_input" type='checkbox' name="hCaptcha_onoff" value="true" checked/>
								<label for="hCaptcha_onoff" class="switch_label"></label>
							<?php }else{?>
								<input id="hCaptcha_onoff" class="switch_input" type='checkbox' name="hCaptcha_onoff" value="true" />
								<label for="hCaptcha_onoff" class="switch_label"></label>
							<?php }?>
						</div>
						<div id="hcaptcha">
							<p>hCaptcha - 認証情報設定</p>
							<div class="p2">サイトキー</div>
							<input id="hcaptcha" placeholder="" class="inbox" type="text" name="hCaptcha_sitekey" value="<?php if( !empty(H_CAPTCHA_SITE_KEY) ){ echo safetext(H_CAPTCHA_SITE_KEY); } ?>">
							<div class="p2">シークレットキー</div>
							<input id="hcaptcha" placeholder="" class="inbox" type="text" name="hCaptcha_seackey" value="<?php if( !empty(H_CAPTCHA_SEAC_KEY) ){ echo safetext(H_CAPTCHA_SEAC_KEY); } ?>">
							<p>デモ</p>
							<div class="h-captcha" data-sitekey="10000000-ffff-ffff-ffff-000000000001"></div>
						</div>
					</div>
					<hr>
					<div>
						<p>CloudflareTurnstile認証</p>
						<div class="p2">CloudflareTurnstileを使用し、ログイン時とアカウント登録時に認証をすることができます。<br>もし人間でないと判断された場合はアカウント登録やログイン、パスワード変更を受け付けません。<br>hCaptchaなどと二重に設定することが可能です。</div>
						<p>CloudflareTurnstileのオンオフ</p>
						<div class="switch_button">
							<?php if(!empty(CF_TURNSTILE_ONOFF && CF_TURNSTILE_ONOFF == "true")){?>
								<input id="CF_Turnstile_onoff" class="switch_input" type='checkbox' name="CF_Turnstile_onoff" value="true" checked/>
								<label for="CF_Turnstile_onoff" class="switch_label"></label>
							<?php }else{?>
								<input id="CF_Turnstile_onoff" class="switch_input" type='checkbox' name="CF_Turnstile_onoff" value="true" />
								<label for="CF_Turnstile_onoff" class="switch_label"></label>
							<?php }?>
						</div>
						<div id="CF_Turnstile">
							<p>CloudflareTurnstile - 認証情報設定</p>
							<div class="p2">サイトキー</div>
							<input id="CF_Turnstile" placeholder="" class="inbox" type="text" name="CF_Turnstile_sitekey" value="<?php if( !empty(CF_TURNSTILE_SITE_KEY) ){ echo safetext(CF_TURNSTILE_SITE_KEY); } ?>">
							<div class="p2">シークレットキー</div>
							<input id="CF_Turnstile" placeholder="" class="inbox" type="text" name="CF_Turnstile_seackey" value="<?php if( !empty(CF_TURNSTILE_SEAC_KEY) ){ echo safetext(CF_TURNSTILE_SEAC_KEY); } ?>">
							<p>デモ<p>
							<div class="cf-turnstile" data-sitekey="1x00000000000000000000AA" data-callback="javascriptCallback" data-language="ja"></div>
						</div>
					</div>

					<input type="submit" class = "irobutton" name="btn_submit" value="保存&更新">
				</form>
			</div>
		</div>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>

</body>
<script>
$(document).ready(function() {
    $(function(){
        $("input"). keydown(function(e) {
            if ((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
                return false;
            } else {
                return true;
            }
        });
    });

	if ($("#hCaptcha_onoff").prop("checked")) {
		$('#hcaptcha').show();
	}else{
		$('#hcaptcha').hide();
	}
	$('#hCaptcha_onoff').change(function(){
		$('#hcaptcha').toggle();
	});

	if ($("#CF_Turnstile_onoff").prop("checked")) {
		$('#CF_Turnstile').show();
	}else{
		$('#CF_Turnstile').hide();
	}
	$('#CF_Turnstile_onoff').change(function(){
		$('#CF_Turnstile').toggle();
	});
});
</script>

</html>