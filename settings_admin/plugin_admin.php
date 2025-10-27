<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$serverstopfile = "../server/serverstop.txt";

$domain = $_SERVER['HTTP_HOST'];

require('../db.php');
require("../function/function.php");

if(!empty(file_get_contents($serverstopfile))){
    $serverstop = safetext(file_get_contents($serverstopfile)); 
}else{
    $serverstop = "";
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



//phpmailer--------------------------------------------
require('plugin_settings/phpmailer_settings.php');
require('plugin_settings/phpmailer_sender.php');
//------------------------------------------------------

//AIBlockWaterMark--------------------------------------------
require('plugin_settings/aiblockwatermark_settings.php');
//------------------------------------------------------

//AmazonS3--------------------------------------------
require('plugin_settings/amazons3_settings.php');
//------------------------------------------------------

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
//ログイン認証---------------------------------------------------
blockedIP($_SERVER['REMOTE_ADDR']);
$is_login = uwuzuUserLogin($_SESSION, $_COOKIE, $_SERVER['REMOTE_ADDR'], "admin");
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
	$is_Admin = safetext($is_login["admin"]);
}

$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

if( !empty($_POST['btn_submit']) ) {
	$N_MAIL_ONOFF = safetext($_POST['mailchks_onoff']);

	$N_MAIL_ADDS = safetext($_POST['N_MAIL_ADDS']);
	$N_MAIL_HOST = safetext($_POST['N_MAIL_HOST']);
	$N_MAIL_PORT = safetext($_POST['N_MAIL_PORT']);
	$N_MAIL_USER = safetext($_POST['N_MAIL_USER']);
	$N_MAIL_PASS = safetext($_POST['N_MAIL_PASS']);

	$N_MAIL_SSL_ = safetext($_POST['ssl_tls_none']);

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

	//----------------------------------------------------------------------

	$N_AIBWM_ONOFF = safetext($_POST['aibwmchk_onoff']);

	$New_AIBWM_Settings = "
	<?php // AIBlockWaterMarkの設定
	define( 'AIBWM_CHK', '".$N_AIBWM_ONOFF."');// trueならAIBlockWaterMarkが有効
	?>
	";

	//設定上書き
	$file = fopen('plugin_settings/aiblockwatermark_settings.php', 'w');
	$data = $New_AIBWM_Settings;
	fputs($file, $data);
	fclose($file);

	//----------------------------------------------------------------------

	$N_AMS3_CHKS = safetext($_POST['ams3chk_onoff']);

	$N_AMS3_BASE_URLS = safetext($_POST['N_AMS3_BASE_URLS']);
	$N_AMS3_BUCKET_NM = safetext($_POST['N_AMS3_BUCKET_NM']);
	$N_AMS3_PREFIX_NM = safetext($_POST['N_AMS3_PREFIX_NM']);
	$N_AMS3_ENDPOINTS = safetext($_POST['N_AMS3_ENDPOINTS']);
	$N_AMS3_REGION_NM = safetext($_POST['N_AMS3_REGION_NM']);
	$N_AMS3_ACCESSKEY = safetext($_POST['N_AMS3_ACCESSKEY']);
	$N_AMS3_SECRETKEY = safetext($_POST['N_AMS3_SECRETKEY']);
	$N_AMS3_IS_S3FPS_ = safetext($_POST['N_AMS3_IS_S3FPS_']);

	$New_AMS3_Settings = "
	<?php // S3の設定
	define('AMS3_CHKS', '".$N_AMS3_CHKS."'); // trueならオブジェクトストレージが有効

	define('AMS3_BASE_URLS', '".$N_AMS3_BASE_URLS."');
	define('AMS3_BUCKET_NM', '".$N_AMS3_BUCKET_NM."');
	define('AMS3_PREFIX_NM', '".$N_AMS3_PREFIX_NM."');
	define('AMS3_ENDPOINTS', '".$N_AMS3_ENDPOINTS."');
	define('AMS3_REGION_NM', '".$N_AMS3_REGION_NM."');
	define('AMS3_ACCESSKEY', '".$N_AMS3_ACCESSKEY."');
	define('AMS3_SECRETKEY', '".$N_AMS3_SECRETKEY."');
	define('AMS3_IS_S3FPS_', '".$N_AMS3_IS_S3FPS_."');
	?>
	";

	//設定上書き
	$file = fopen('plugin_settings/amazons3_settings.php', 'w');
	$data = $New_AMS3_Settings;
	fputs($file, $data);
	fclose($file);

	$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	header("Location:".$url."");
	exit;  
}

if( !empty($_POST['testmail_send_btn_submit']) ) {
	$test_mail_adds = safetext($_POST['test_sender_adds']);
	
	$mail_title = "Test email";
	$mail_text = "これはuwuzuのテストメールです。  問題なく受信できていますか？";

	$error_message = send_html_mail($test_mail_adds,$mail_title,$mail_text,"../");
}

/*
$plugin_chk_result = glob('../plugin/*');
$plugin_data = array();
foreach ($plugin_chk_result as $plugin_path) {
    if (file_exists($plugin_path . "/plugin_config.json")) {
        $plugin_conf = json_decode(file_get_contents($plugin_path . "/plugin_config.json"), true);
        if ($plugin_conf) {
            $plugin_data[] = array(
                "name" => $plugin_conf["name"],
                "version" => $plugin_conf["version"],
                "author" => $plugin_conf["author"],
                "description" => $plugin_conf["description"]
            );
        }
    }
}
*/

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
<title>プラグイン - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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

					<!--
					<php if(!(empty($plugin_data))){?>
						<php foreach ($plugin_data as $value) {?>
							<div class="server_code">
								<details>
									<summary><php echo safetext($value["name"]);?></summary>
									<div class="p2">バージョン</div>
									<p><php echo safetext($value["version"]);?></p>
									<hr>
									<div class="p2">説明</div>
									<p><php echo nl2br(safetext($value["description"]));?></p>
									<hr>
									<div class="p2">制作者</div>
									<p><php echo safetext($value["author"]);?></p>
								</details>
							</div>
						<php }?>
					<php }?>
					-->

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
							<input id="mail_plugin" placeholder="user@localhost" class="inbox" type="text" name="N_MAIL_ADDS" value="<?php if( !empty(MAIL_ADDS) ){ echo safetext(MAIL_ADDS); } ?>">
							<div class="p2">ホスト名</div>
							<input id="mail_plugin" placeholder="smtp.mailserver.com" class="inbox" type="text" name="N_MAIL_HOST" value="<?php if( !empty(MAIL_HOST) ){ echo safetext(MAIL_HOST); } ?>">
							<div class="p2">ポート番号</div>
							<input id="mail_plugin" placeholder="465" class="inbox" type="text" name="N_MAIL_PORT" value="<?php if( !empty(MAIL_PORT) ){ echo safetext(MAIL_PORT); } ?>">
							<div class="p2">ユーザー名</div>
							<input id="mail_plugin" placeholder="from@localhost" class="inbox" type="text" name="N_MAIL_USER" value="<?php if( !empty(MAIL_USER) ){ echo safetext(MAIL_USER); } ?>">
							<div class="p2">パスワード</div>
							<input id="mail_plugin" placeholder="password" class="inbox" type="text" name="N_MAIL_PASS" style="-webkit-text-security:disc;" value="<?php if( !empty(MAIL_PASS) ){ echo safetext(MAIL_PASS); } ?>">
							
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

						<hr>

						<p>AIBlockWaterMarkプラグイン</p>
						<div class="p2">AI学習対策に、ユーザー単位で画像に透かしを自動挿入できるプラグインです。<b>pluginフォルダに解凍済みのAIBlockWaterMarkのファイル一式が入っていることが必須要件になります。</b><br>plugin/AIBlockWaterMark/README.MDなど一式</div>
						<p>AIBlockWaterMarkのオンオフ</p>
						<div class="switch_button">
							<?php if(!empty(AIBWM_CHK && AIBWM_CHK == "true")){?>
								<input id="aibwmchk_onoff" class="switch_input" type='checkbox' name="aibwmchk_onoff" value="true" checked/>
								<label for="aibwmchk_onoff" class="switch_label"></label>
							<?php }else{?>
								<input id="aibwmchk_onoff" class="switch_input" type='checkbox' name="aibwmchk_onoff" value="true" />
								<label for="aibwmchk_onoff" class="switch_label"></label>
							<?php }?>
						</div>

						<hr>

						<p>オブジェクトストレージプラグイン</p>
						<div class="p2">Amazon S3及びAmazon S3互換オブジェクトストレージが使用できるようになるプラグインです。<b>pluginフォルダに解凍済みのAWS SDK for PHPのファイル一式が入っていることが必須要件になります。</b><br>plugin/aws/README.MDなど一式</div>
						<p>オブジェクトストレージのオンオフ</p>
						<div class="switch_button">
							<?php if(!empty(AMS3_CHKS && AMS3_CHKS == "true")){?>
								<input id="ams3chk_onoff" class="switch_input" type='checkbox' name="ams3chk_onoff" value="true" checked/>
								<label for="ams3chk_onoff" class="switch_label"></label>
							<?php }else{?>
								<input id="ams3chk_onoff" class="switch_input" type='checkbox' name="ams3chk_onoff" value="true" />
								<label for="ams3chk_onoff" class="switch_label"></label>
							<?php }?>
						</div>
						<div id="ams3_plugin">
							<p>オブジェクトストレージ - 保存先設定</p>
							<div class="p2">BaseURL</div>
							<input id="ams3_plugin" placeholder="https://example.com" class="inbox" type="text" name="N_AMS3_BASE_URLS" value="<?php if( !empty(AMS3_BASE_URLS) ){ echo safetext(AMS3_BASE_URLS); } ?>">
							<div class="p2">Bucket</div>
							<input id="ams3_plugin" placeholder="uwuzu-bucket" class="inbox" type="text" name="N_AMS3_BUCKET_NM" value="<?php if( !empty(AMS3_BUCKET_NM) ){ echo safetext(AMS3_BUCKET_NM); } ?>">
							<div class="p2">Prefix</div>
							<input id="ams3_plugin" placeholder="files" class="inbox" type="text" name="N_AMS3_PREFIX_NM" value="<?php if( !empty(AMS3_PREFIX_NM) ){ echo safetext(AMS3_PREFIX_NM); } ?>">
							<div class="p2">Endpoint</div>
							<input id="ams3_plugin" placeholder="https://example.com" class="inbox" type="text" name="N_AMS3_ENDPOINTS" value="<?php if( !empty(AMS3_ENDPOINTS) ){ echo safetext(AMS3_ENDPOINTS); } ?>">
							<div class="p2">Region</div>
							<input id="ams3_plugin" placeholder="us-east-1" class="inbox" type="text" name="N_AMS3_REGION_NM" value="<?php if( !empty(AMS3_REGION_NM) ){ echo safetext(AMS3_REGION_NM); } ?>">
							<div class="p2">Access Key</div>
							<input id="ams3_plugin" placeholder="アクセスキー" class="inbox" type="text" name="N_AMS3_ACCESSKEY" value="<?php if( !empty(AMS3_ACCESSKEY) ){ echo safetext(AMS3_ACCESSKEY); } ?>">
							<div class="p2">Secret Key</div>
							<input id="ams3_plugin" placeholder="シークレットキー" class="inbox" type="text" name="N_AMS3_SECRETKEY" style="-webkit-text-security:disc;" value="<?php if( !empty(AMS3_SECRETKEY) ){ echo safetext(AMS3_SECRETKEY); } ?>">

							<div class="p2">s3ForcePathStyle設定</div>
							<div class="switch_button">
								<input id="ams3_plugin" class="switch_input" type='checkbox' name="N_AMS3_IS_S3FPS_" value="true" <?php if(!empty(AMS3_IS_S3FPS_ && AMS3_IS_S3FPS_ == "true")){?>checked<?php }?>/>
								<label for="N_AMS3_IS_S3FPS_" class="switch_label"></label>
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

	if ($("#ams3chk_onoff").prop("checked")) {
		$('#ams3_plugin').show();
	}else{
		$('#ams3_plugin').hide();
	}
	$('#ams3chk_onoff').change(function(){
		$('#ams3_plugin').toggle();
	});
</script>