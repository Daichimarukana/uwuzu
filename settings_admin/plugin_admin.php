<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$serverstopfile = "../server/serverstop.txt";

$domain = $_SERVER['HTTP_HOST'];

if(!empty(file_get_contents($serverstopfile))){
    $serverstop = htmlentities(file_get_contents($serverstopfile), ENT_QUOTES, 'UTF-8', false); 
}else{
    $serverstop = "";
}

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}
function random_code($length = 8){
    return substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}
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
require('../db.php');

//phpmailer--------------------------------------------
require('plugin_settings/phpmailer_settings.php');
require('plugin_settings/phpmailer_sender.php');
//------------------------------------------------------

session_name('uwuzu_s_id');
session_set_cookie_params(0, '', '', true, true);
session_start();
session_regenerate_id(true);

try {

    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=UTF8;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

} catch(PDOException $e) {

    // 接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}
if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] == true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', htmlentities($_SESSION['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_SESSION['loginid'] === $res["loginid"] && $_SESSION['userid'] == $res["userid"]){
	// セッションに値をセット
	$userid = htmlentities($res['userid']); // セッションに格納されている値をそのままセット
	$username = htmlentities($res['username']); // セッションに格納されている値をそのままセット
	$loginid = htmlentities($res["loginid"]);
	$role = htmlentities($res["role"]);
	$sacinfo = htmlentities($res["sacinfo"]);
	$myblocklist = htmlentities($res["blocklist"]);
	$myfollowlist = htmlentities($res["follow"]);
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
	$passQuery->bindValue(':userid', htmlentities($_COOKIE['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_COOKIE['loginid'] === $res["loginid"] && $_COOKIE['userid'] == $res["userid"]){
	// セッションに値をセット
	$userid = htmlentities($res['userid']); // クッキーから取得した値をセット
	$username = htmlentities($res['username']); // クッキーから取得した値をセット
	$loginid = htmlentities($res["loginid"]);
	$role = htmlentities($res["role"]);
	$sacinfo = htmlentities($res["sacinfo"]);
	$myblocklist = htmlentities($res["blocklist"]);
	$myfollowlist = htmlentities($res["follow"]);
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

if( !empty($_POST['btn_submit']) ) {
	$N_MAIL_ONOFF = htmlentities($_POST['mailchks_onoff'], ENT_QUOTES, 'UTF-8', false);

	$N_MAIL_ADDS = htmlentities($_POST['N_MAIL_ADDS'], ENT_QUOTES, 'UTF-8', false);
	$N_MAIL_HOST = htmlentities($_POST['N_MAIL_HOST'], ENT_QUOTES, 'UTF-8', false);
	$N_MAIL_PORT = htmlentities($_POST['N_MAIL_PORT'], ENT_QUOTES, 'UTF-8', false);
	$N_MAIL_USER = htmlentities($_POST['N_MAIL_USER'], ENT_QUOTES, 'UTF-8', false);
	$N_MAIL_PASS = htmlentities($_POST['N_MAIL_PASS'], ENT_QUOTES, 'UTF-8', false);

	$N_MAIL_SSL_ = htmlentities($_POST['ssl_tls_none'], ENT_QUOTES, 'UTF-8', false);

	$New_Mail_Settings = "
	<?php // メールサーバーの情報
	define( 'MAIL_CHKS', '".$N_MAIL_ONOFF."');// trueならPHPMailerが有効

	define( 'MAIL_ADDS', '".$N_MAIL_ADDS."');
	define( 'MAIL_HOST', '".$N_MAIL_HOST."');
	define( 'MAIL_PORT', '".$N_MAIL_PORT."');
	define( 'MAIL_USER', '".$N_MAIL_USER."');
	define( 'MAIL_PASS', '".$N_MAIL_PASS."');
	define( 'MAIL_SSL_', '".$N_MAIL_SSL_."');
	?>
	";

	//設定上書き
	$file = fopen('plugin_settings/phpmailer_settings.php', 'w');
	$data = $New_Mail_Settings;
	fputs($file, $data);
	fclose($file);

	$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	header("Location:".$url."");
	exit;  
}

if( !empty($_POST['testmail_send_btn_submit']) ) {
	$test_mail_adds = htmlentities($_POST['test_sender_adds'], ENT_QUOTES, 'UTF-8', false);
	
	$mail_title = "Test email";
	$mail_text = "これはuwuzuのテストメールです。  問題なく受信できていますか？";

	$error_message = send_html_mail($test_mail_adds,$mail_title,$mail_text,"../");
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
<title>プラグイン - <?php echo htmlentities($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8', false);?></title>

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
					<h1>プラグイン</h1>
					<p>PHPMailerなどとの連携が可能です。</p>
					<div>
						<p>自動メールプラグイン</p>
						<div class="p2">PHPMailerと連携し、パスワードリセット時やログイン通知などを自動送信することができます。<br>SMTP送信のみ対応です。<br><b>pluginフォルダに解凍済みのPHPMailerのファイル一式が入っていることが必須要件になります。</b><br>plugin/PHPMailer/README.MDなど一式</div>
						<p>自動メールプラグインのオンオフ</p>
						<div class="switch_button">
							<?php if(!empty(MAIL_CHKS && MAIL_CHKS == "true")){?>
								<input id="mailchks_onoff" class="switch_input" type='checkbox' name="mailchks_onoff" value="true" checked/>
								<label for="mailchks_onoff" class="switch_label"></label>
							<?php }else{?>
								<input id="mailchks_onoff" class="switch_input" type='checkbox' name="mailchks_onoff" value="true" />
								<label for="mailchks_onoff" class="switch_label"></label>
							<?php }?>
						</div>
						<div id="mail_plugin">
							<p>PHPMailer - メールサーバー設定</p>
							<div class="p2">メールアドレス</div>
							<input id="mail_plugin" placeholder="user@localhost" class="inbox" type="text" name="N_MAIL_ADDS" value="<?php if( !empty(MAIL_ADDS) ){ echo htmlentities(MAIL_ADDS, ENT_QUOTES, 'UTF-8', false); } ?>">
							<div class="p2">ホスト名</div>
							<input id="mail_plugin" placeholder="smtp.mailserver.com" class="inbox" type="text" name="N_MAIL_HOST" value="<?php if( !empty(MAIL_HOST) ){ echo htmlentities(MAIL_HOST, ENT_QUOTES, 'UTF-8', false); } ?>">
							<div class="p2">ポート番号</div>
							<input id="mail_plugin" placeholder="465" class="inbox" type="text" name="N_MAIL_PORT" value="<?php if( !empty(MAIL_PORT) ){ echo htmlentities(MAIL_PORT, ENT_QUOTES, 'UTF-8', false); } ?>">
							<div class="p2">ユーザー名</div>
							<input id="mail_plugin" placeholder="from@localhost" class="inbox" type="text" name="N_MAIL_USER" value="<?php if( !empty(MAIL_USER) ){ echo htmlentities(MAIL_USER, ENT_QUOTES, 'UTF-8', false); } ?>">
							<div class="p2">パスワード</div>
							<input id="mail_plugin" placeholder="password" class="inbox" type="text" name="N_MAIL_PASS" style="-webkit-text-security:disc;" value="<?php if( !empty(MAIL_PASS) ){ echo htmlentities(MAIL_PASS, ENT_QUOTES, 'UTF-8', false); } ?>">
							
							<div class="p2">暗号化設定</div>
							<div class="radio_btn_zone">
								<input type="radio" name="ssl_tls_none" value="SSL" id="SSL" class="radiobtn_input" <?php if(!empty(MAIL_SSL_ && MAIL_SSL_ == "SSL")){echo "checked";}?>>
								<label for="SSL" class="radiobtn_label">SSL</label>

								<input type="radio" name="ssl_tls_none" value="TLS" id="TLS" class="radiobtn_input" <?php if(!empty(MAIL_SSL_ && MAIL_SSL_ == "TLS")){echo "checked";}?>>
								<label for="TLS" class="radiobtn_label">TLS</label>

								<input type="radio" name="ssl_tls_none" value="NONE" id="NONE" class="radiobtn_input" <?php if(!empty(MAIL_SSL_ && MAIL_SSL_ == "NONE")){echo "checked";}?>>
								<label for="NONE" class="radiobtn_label">なし</label>
							</div>
							

						</div>
					</div>
					<input type="submit" class = "irobutton" name="btn_submit" value="保存&更新">
				</form>
				<?php if(!empty(MAIL_CHKS && MAIL_CHKS == "true")){?>
					<form class="formarea" enctype="multipart/form-data" method="post">
						<div id="mail_plugin_chk">
							<p>メールテスト送信</p>
							<input id="mail_plugin" placeholder="admin@localhost" class="inbox" type="text" name="test_sender_adds" value="">
							<input type="submit" class = "irobutton" name="testmail_send_btn_submit" value="テスト送信">
						</div>
					</form>
				<?php }?>

			</div>
		</div>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>

</body>

</html>
<script>
    $(function(){
        $("input"). keydown(function(e) {
            if ((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
                return false;
            } else {
                return true;
            }
        });
    });

	if ($("#mailchks_onoff").prop("checked")) {
		$('#mail_plugin').show();
		$('#mail_plugin_chk').show();
	}else{
		$('#mail_plugin').hide();
		$('#mail_plugin_chk').hide();
	}
	$('#mailchks_onoff').change(function(){
		$('#mail_plugin').toggle();
		$('#mail_plugin_chk').toggle();
	});
</script>