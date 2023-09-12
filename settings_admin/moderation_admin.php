<?php

$servernamefile = "../server/servername.txt";

$contactfile = "../server/contact.txt";

$adminfile = "../server/admininfo.txt";

$serverstopfile = "../server/serverstop.txt";

$mojisizefile = "../server/textsize.txt";

$banuseridfile = "../server/banuserid.txt";
$banuserid_info = file_get_contents($banuseridfile);

$banurldomainfile = "../server/banurldomain.txt";
$banurldomain_info = file_get_contents($banurldomainfile);

$err404imagefile = "../server/404imagepath.txt";

$robots = "../robots.txt";

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}
function random_code($length = 8){
    return substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}

require('../db.php');

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
if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,admin FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', $_SESSION['userid']);
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_SESSION['loginid'] === $res["loginid"] && $_SESSION['userid'] === $res["userid"]){
	// セッションに値をセット
	$userid = $_SESSION['userid']; // セッションに格納されている値をそのままセット
	$username = $_SESSION['username']; // セッションに格納されている値をそのままセット
	$loginid = $res["loginid"];
	$_SESSION['admin_login'] = true;
	$_SESSION['userid'] = $userid;
	$_SESSION['username'] = $username;
	$_SESSION['loginid'] = $res["loginid"];
	setcookie('userid', $userid, [
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	setcookie('username', $username,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	setcookie('loginid', $res["loginid"],[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	setcookie('admin_login', true,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	}else{
		header("Location: ../login.php");
		exit;
	}

		
} elseif (isset($_COOKIE['admin_login']) && $_COOKIE['admin_login'] == true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,admin FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', $_COOKIE['userid']);
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_COOKIE['loginid'] === $res["loginid"] && $_COOKIE['userid'] === $res["userid"]){
	// セッションに値をセット
	$userid = $_COOKIE['userid']; // クッキーから取得した値をセット
	$username = $_COOKIE['username']; // クッキーから取得した値をセット
	$loginid = $res["loginid"];
	$_SESSION['admin_login'] = true;
	$_SESSION['userid'] = $userid;
	$_SESSION['username'] = $username;
	$_SESSION['loginid'] = $res["loginid"];
	setcookie('userid', $userid,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	setcookie('username', $username,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	setcookie('loginid', $res["loginid"],[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	setcookie('admin_login', true,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
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
	$dbh = new PDO('mysql:charset=UTF8;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	));

	$userQuery = $dbh->prepare("SELECT username, userid, profile, role FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $userid);
	$userQuery->execute();
	$userData = $userQuery->fetch();

	$role = $userData["role"];

	$dbh = new PDO('mysql:charset=UTF8;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

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
	$banuserid = $_POST['banuserid'];

	$banurldomain = $_POST['banurldomain'];

	$max_textsize = $_POST['max_textsize'];

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
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>モデレーション - <?php echo file_get_contents($servernamefile);?></title>

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
						<textarea id="banuserid" placeholder="uwuzu" class="inbox" type="text" name="banuserid"><?php $sinfo = explode("\n", $banuserid_info); foreach ($sinfo as $info) { echo $info; }?></textarea>
					</div>

					<div>
						<p>投稿禁止URLドメイン</p>
						<div class="p2">ここに入力してあるドメインが含まれる投稿をしようとすると投稿が拒否されます。<br>なお、この機能はまだ確実な動作が保証されないためベータ版です。<br>位置情報特定サイトなどの対策等にご利用ください。</div>
						<textarea id="banurldomain" placeholder="" class="inbox" type="text" name="banurldomain"><?php $sinfo = explode("\n", $banurldomain_info); foreach ($sinfo as $info) { echo $info; }?></textarea>
					</div>

					<div>
						<p>投稿の最大文字数</p>
						<div class="p2">ここで設定した文字数までの投稿が可能です。<br>なお、データベースより最大文字数を設定している場合そちらが優先されて使用されます。<br>1文字から16777216文字の間で設定が可能です。</div>
						<input id="max_textsize" placeholder="1024" class="inbox" type="number" min="1" max="16777216" name="max_textsize" value="<?php if( !empty(file_get_contents($mojisizefile)) ){ echo htmlspecialchars(file_get_contents($mojisizefile), ENT_QUOTES, 'UTF-8'); } ?>">
					</div>

					<input type="submit" class = "irobutton" name="btn_submit" value="保存&更新">
				</form>
			</div>
		</div>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>

</body>

</html>