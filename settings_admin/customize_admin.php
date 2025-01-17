<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$colorfile = "../css/color.css";
$color_info = file_get_contents($colorfile);

$fontfile = "../css/font.css";
$font_info = file_get_contents($fontfile);

$manifestfile = "../manifest/manifest.json";
$manifest_info = file_get_contents($manifestfile);

$err404imagefile = "../server/404imagepath.txt";

$robots = "../robots.txt";

function random_code($length = 8){
    return substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
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
	$myfollowlist = safetext($is_login["follow"]);
	$is_Admin = safetext($is_login["admin"]);
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

	//safetextで変換すると死ぬので注意
	$colordata = $_POST['colordata'];
	$fontdata = $_POST['fontdata'];
	$manifestdata = $_POST['manifestdata'];

	//色等変数
	$file = fopen($colorfile, 'w');
	$data = $colordata;
	fputs($file, $data);
	fclose($file);

	//フォント呼び出し
	$file = fopen($fontfile, 'w');
	$data = $fontdata;
	fputs($file, $data);
	fclose($file);

	//manifest
	$file = fopen($manifestfile, 'w');
	$data = $manifestdata;
	fputs($file, $data);
	fclose($file);

	actionLog($userid, "info", "customize_admin", null, "サーバーカスタマイズを更新しました", 0);

	$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	header("Location:".$url."");
	exit;  
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
<title>サーバーカスタマイズ - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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
					<h1>サーバーカスタマイズ</h1>
					
					<div>
						<p>Color & Fontname CSS</p>
						<div class="p2">ここで指定されている色が適用されます。<br>もし適用されなかった場合はキャッシュを削除し再読み込みしてください。<br>表示がおかしくなってしまった場合はカラーコードを再度確認してください。</div>
						<textarea id="colordata" placeholder="CSS" class="inbox" type="text" name="colordata"><?php $sinfo = explode("\r", $color_info); foreach ($sinfo as $info) { echo $info; }?></textarea>
					</div>

					<div>
						<p>FontRequire CSS</p>
						<div class="p2">ここで指定されている色が適用されます。<br>もし適用されなかった場合はキャッシュを削除し再読み込みしてください。<br>表示がおかしくなってしまった場合はカラーコードを再度確認してください。</div>
						<textarea id="fontdata" placeholder="FontRequireCSS" class="inbox" type="text" name="fontdata"><?php $sinfo = explode("\r", $font_info); foreach ($sinfo as $info) { echo $info; }?></textarea>
					</div>

					<div>
						<p>PWA(manifest)</p>
						<div class="p2">ここでPWAの設定を変更できます。<br>"short_name"、"name"が表示されるアプリ名、"theme_color"、"background_color"がテーマカラーとPWA起動時のスプラッシュ画面の背景色です。<br>いま記載したもの以外の設定は変更しないことをお勧めします。</div>
						<textarea id="manifestdata" placeholder="manifest.json" class="inbox" type="text" name="manifestdata"><?php $sinfo = explode("\r", $manifest_info); foreach ($sinfo as $info) { echo $info; }?></textarea>
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

</html>