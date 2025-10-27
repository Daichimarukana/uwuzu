<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$serverstopfile = "../server/serverstop.txt";

$htaccessfile = "../.htaccess";

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
	$serverstop = safetext($_POST['serverstop']);

	//鯖停止
	$file = fopen($serverstopfile, 'w');
	$data = $serverstop;
	fputs($file, $data);
	fclose($file);

	$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	header("Location:".$url."");
	exit;  
}

if( !empty($_POST['serverstop_btn_submit']) ) {

    // htaccess用意
	$htaccess = "
ErrorDocument 403 /errorpage/serverstop.php
RewriteEngine On
RewriteCond %{REQUEST_URI} !=/function/function.php
RewriteCond %{REQUEST_URI} !=/errorpage/serverstop.php
RewriteCond %{REQUEST_URI} !=/css/home.css
RewriteCond %{REQUEST_URI} !=/css/color.css
RewriteCond %{REQUEST_URI} !=/js/console_notice.js
RewriteCond %{REQUEST_URI} !=/js/unsupported.js
RewriteCond %{REQUEST_URI} !=/img/uwuzulogo.svg
RewriteCond %{REQUEST_URI} !=/favicon/apple-touch-icon-180x180.png
RewriteCond %{REQUEST_URI} !=/favicon/icon-192x192.png
RewriteRule ^.*$ - [R=403,L]
";

	// 上書き保存
	$file = fopen($htaccessfile, 'w');
	$data = $htaccess;
	fputs($file, $data);
	fclose($file);

	actionLog($userid, "info", "maintenance_admin", null, "サーバーを停止しました", 0);

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
<title>メンテナンス - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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
					<h1>メンテナンス</h1>

					<div>
						<p>サーバー停止時表示メッセージ</p>
						<div class="p2">ここに入力してあるメッセージがサーバー停止時に表示されます。</div>
						<textarea id="serverstop" placeholder="現在サーバーは止まっておりません。" class="inbox" type="text" name="serverstop"><?php $s_stop = explode("\r", $serverstop); foreach ($s_stop as $info) { echo $info; }?></textarea>
					</div>

					<input type="submit" class = "irobutton" name="btn_submit" value="保存&更新">
				</form>

				<form class="formarea" enctype="multipart/form-data" method="post">
					<h1>サーバー停止</h1>
					<p>下のボタンを押すとサーバーへのアクセス時にすべてのアクセスがに対して上のサーバー停止時表示メッセージを表示します。<br>サーバーを止める必要がある際に使用してください。<br>復旧には現在の.htaccessファイルを上書きしていただく必要があります。<br>今現在の.htaccessファイルをFTPソフトなどからダウンロードすることを強く推奨します。</p>

					<p class="errmsg">サーバーを停止するとこの画面にもログインができなくなります。<br>また、復旧時に今現在の.htaccessファイルを上書きする必要があります。<br>.htaccessファイルとサーバー管理権限はお持ちですか？<br>お持ちでない方は作業を中断してください。</p>

					<div class="p2">サーバー停止</div>
					<input type="submit" class = "irobutton" name="serverstop_btn_submit" value="サーバー停止">
				</form>
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
</script>