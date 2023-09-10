<?php

$servernamefile = "../server/servername.txt";

$serverinfofile = '../server/info.txt';
$serverinfo = file_get_contents($serverinfofile);

$servertermsfile = '../server/terms.txt';
$serverterms = file_get_contents($servertermsfile);

$serverprvfile = '../server/privacypolicy.txt';
$serverprv = file_get_contents($serverprvfile);

$contactfile = "../server/contact.txt";

$adminfile = "../server/admininfo.txt";

$serverstopfile = "../server/serverstop.txt";

$onlyuserfile = "../server/onlyuser.txt";

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
	$servername = $_POST['servername'];

	$serverinfo = $_POST['serverinfo'];

	$serveradminname = $_POST['serveradminname'];

	$servermailadds = $_POST['servermailadds'];

	$onlyuser = $_POST['onlyuser'];

	if($onlyuser === "true"){
		$saveonlyuser = "true";
	}else{
		$saveonlyuser = "false";
	}

	$postrobots = $_POST['robots'];

	if($postrobots === "true"){
		//GPTBotによるクロールを拒否
		$file = fopen($robots, 'w');
		$data = "User-agent: GPTBot\nDisallow: /";
		fputs($file, $data);
		fclose($file);
	}else{
		//GPTBotによるクロールを許可
		$file = fopen($robots, 'w');
		$data = "";
		fputs($file, $data);
		fclose($file);
	}


	$serverterms = $_POST['serverterms'];

	$serverprv = $_POST['serverprv'];

	$serverstop = $_POST['serverstop'];

	//鯖名
	$file = fopen($servernamefile, 'w');
	$data = $servername;
	fputs($file, $data);
	fclose($file);

	//鯖紹介
	$file = fopen($serverinfofile, 'w');
	$data = $serverinfo;
	fputs($file, $data);
	fclose($file);

	//鯖管理者名
	$file = fopen($adminfile, 'w');
	$data = $serveradminname;
	fputs($file, $data);
	fclose($file);

	//鯖管理者メアド
	$file = fopen($contactfile, 'w');
	$data = $servermailadds;
	fputs($file, $data);
	fclose($file);

	//招待制にするか
	$file = fopen($onlyuserfile, 'w');
	$data = $saveonlyuser;
	fputs($file, $data);
	fclose($file);

	//利用規約
	$file = fopen($servertermsfile, 'w');
	$data = $serverterms;
	fputs($file, $data);
	fclose($file);

	//プライバシーポリシー
	$file = fopen($serverprvfile, 'w');
	$data = $serverprv;
	fputs($file, $data);
	fclose($file);

	//鯖停止
	$file = fopen($serverstopfile, 'w');
	$data = $serverstop;
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
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>サーバー設定 - <?php echo file_get_contents($servernamefile);?></title>

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
					<h1>サーバー設定</h1>
					<div>
						<p>サーバー名</p>
						<input id="servername" placeholder="uwuzuさ～ば～" class="inbox" type="text" name="servername" value="<?php if( !empty(file_get_contents($servernamefile)) ){ echo htmlspecialchars(file_get_contents($servernamefile), ENT_QUOTES, 'UTF-8'); } ?>">
					</div>

					<div>
						<p>サーバー紹介メッセージ</p>
						<textarea id="serverinfo" placeholder="たのしいさーばーです" class="inbox" type="text" name="serverinfo"><?php $sinfo = explode("\n", $serverinfo); foreach ($sinfo as $info) { echo $info; }?></textarea>
					</div>

					<div>
						<p>サーバー管理者の名前</p>
						<input id="serveradminname" placeholder="わたし" class="inbox" type="text" name="serveradminname" value="<?php if( !empty(file_get_contents($adminfile)) ){ echo htmlspecialchars(file_get_contents($adminfile), ENT_QUOTES, 'UTF-8'); } ?>">
					</div>

					<div>
						<p>サーバーへのお問い合わせ用メールアドレス</p>
						<input id="servermailadds" placeholder="" class="inbox" type="text" name="servermailadds" value="<?php if( !empty(file_get_contents($contactfile)) ){ echo htmlspecialchars(file_get_contents($contactfile), ENT_QUOTES, 'UTF-8'); } ?>">
					</div>

					<div>
						<p>招待制にするかどうか</p>
						<div class="switch_button">
							<?php if(file_get_contents($onlyuserfile) === "true"){?>
								<input id="onlyuser" class="switch_input" type='checkbox' name="onlyuser" value="true" checked/>
								<label for="onlyuser" class="switch_label"></label>
							<?php }else{?>
								<input id="onlyuser" class="switch_input" type='checkbox' name="onlyuser" value="true" />
								<label for="onlyuser" class="switch_label"></label>
							<?php }?>
						</div>
					</div>

					<div>
						<p>OpenAIによるクロールを拒否するかどうか<br>※robots.txtによりOpenAIからのクロールを拒否するものであり、他のAI企業によるクロールを完全拒否するものではございません。</p>
						<div class="switch_button">
							<?php if(file_get_contents($robots) === "User-agent: GPTBot\nDisallow: /"){?>
								<input id="robots" class="switch_input" type='checkbox' name="robots" value="true" checked/>
								<label for="robots" class="switch_label"></label>
							<?php }else{?>
								<input id="robots" class="switch_input" type='checkbox' name="robots" value="true" />
								<label for="robots" class="switch_label"></label>
							<?php }?>
						</div>
					</div>

					<div>
						<p>利用規約</p>
						<textarea id="serverterms" placeholder="しっかり書きましょう" class="inbox" type="text" name="serverterms"><?php $sinfo = explode("\n", $serverterms); foreach ($sinfo as $info) { echo $info; }?></textarea>
					</div>
					<div>
						<p>プライバシーポリシー</p>
						<textarea id="serverprv" placeholder="しっかり書きましょう" class="inbox" type="text" name="serverprv"><?php $sinfo = explode("\n", $serverprv); foreach ($sinfo as $info) { echo $info; }?></textarea>
					</div>

					<div>
						<p>サーバー停止時表示メッセージ</p>
						<input id="serverstop" placeholder="現在サーバーは止まっておりません。" class="inbox" type="text" name="serverstop" value="<?php if( !empty(file_get_contents($serverstopfile)) ){ echo htmlspecialchars(file_get_contents($serverstopfile), ENT_QUOTES, 'UTF-8'); } ?>">
					</div>

					<input type="submit" class = "irobutton" name="btn_submit" value="保存&更新">
				</form>
			</div>
		</div>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	
<script>
$(document).ready(function() {

	$(document).on('click', '.delbtn', function (event) {

        var code = $(this).attr('del-code');
		var userid = '<?php echo $userid; ?>';
		var account_id = '<?php echo $loginid; ?>';
		var codeElement = $(this).closest('.server_code');

		$.ajax({
			url: 'code_delete.php',
			method: 'POST',
			data: { code: code, userid: userid, account_id: account_id },
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					codeElement.remove();
				} else {
					// 削除失敗時の処理
				}
			},
			error: function () {
				// エラー時の処理
			}
		});
    });
});
</script>
</body>

</html>