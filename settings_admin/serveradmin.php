<?php
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$serverinfofile = '../server/info.txt';
$serverinfo = file_get_contents($serverinfofile);

$servertermsfile = '../server/terms.txt';
$serverterms = file_get_contents($servertermsfile);

$serverprvfile = '../server/privacypolicy.txt';
$serverprv = file_get_contents($serverprvfile);

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

	$servericon = $_POST['servericon'];

	$serverhead = $_POST['serverhead'];

	$serverlogo_onoff = $_POST['serverlogo_onoff'];

	$serverlogo_light = $_POST['serverlogo_light'];
	$serverlogo_dark = $_POST['serverlogo_dark'];

	if(!($serverlogo_onoff === "true")){
		$serverlogo_light = "";
		$serverlogo_dark = "";
	}

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
	$activitypub = $_POST['activitypub'];
	if($activitypub === "true"){
		$saveactivitypub = "true";
	}else{
		$saveactivitypub = "false";
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

	$server_new_settings = '
	;サーバーの基本情報
	[serverinfo]
	;サーバー名
	server_name = "'.$servername.'"
	;サーバーアイコンのアドレス
	server_icon = "'.$servericon.'"
	;サーバーヘッダーのアドレス
	server_head = "'.$serverhead.'"
	;サーバーロゴのアドレス
	server_logo_home  = "'.$serverlogo_light.'"
	server_logo_login = "'.$serverlogo_dark.'"
	;管理者関係
	server_admin = "'.$serveradminname.'"
	server_admin_mailadds = "'.$servermailadds.'"
	;招待のオンオフ
	server_invitation = "'.$saveonlyuser.'"
	server_activitypub = "'.$saveactivitypub.'"
	';

	//サーバー設定上書き
	$file = fopen($serversettings_file, 'w');
	$data = $server_new_settings;
	fputs($file, $data);
	fclose($file);

	
	//鯖紹介
	$file = fopen($serverinfofile, 'w');
	$data = $serverinfo;
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
<title>サーバー設定 - <?php echo htmlentities($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>

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
					<!--(サーバーアイコン)-->
					<?php if( !empty($serversettings["serverinfo"]["server_head"]) ){ ?>
					<div class="serverhead_set">
						<img src="<?php echo htmlentities($serversettings["serverinfo"]["server_head"], ENT_QUOTES, 'UTF-8'); ?>">
					</div>
					<?php }?>
					<?php if( !empty($serversettings["serverinfo"]["server_icon"]) ){ ?>
					<div class="servericon">
						<?php if( !empty($serversettings["serverinfo"]["server_head"]) ){ ?>
							<div class="up">
								<img src="<?php echo htmlentities($serversettings["serverinfo"]["server_icon"], ENT_QUOTES, 'UTF-8'); ?>">
							</div>
						<?php }else{?>
							<img src="<?php echo htmlentities($serversettings["serverinfo"]["server_icon"], ENT_QUOTES, 'UTF-8'); ?>">
						<?php }?>
					</div>
					<?php }?>
					<!--(サーバーアイコンここまで)-->
					<div>
						<p>サーバーアイコン</p>
						<div class="p2">サーバー登録画面などに表示されます。<br>自動的に角が丸くなります。<br>URLより設定してください。(設定しなくても大丈夫です。)</div>
						<input id="servericon" placeholder="https://~" class="inbox" type="text" name="servericon" value="<?php if( !empty($serversettings["serverinfo"]["server_icon"]) ){ echo htmlentities($serversettings["serverinfo"]["server_icon"], ENT_QUOTES, 'UTF-8'); } ?>">
					</div>
					<div>
						<p>サーバーヘッダー</p>
						<div class="p2">サーバー登録画面などに表示されます。<br>自動的に角が丸くなります。<br>URLより設定してください。(設定しなくても大丈夫です。)</div>
						<input id="serverhead" placeholder="https://~" class="inbox" type="text" name="serverhead" value="<?php if( !empty($serversettings["serverinfo"]["server_head"]) ){ echo htmlentities($serversettings["serverinfo"]["server_head"], ENT_QUOTES, 'UTF-8'); } ?>">
					</div>

					<div>
						<p>サーバーロゴ機能のオンオフ</p>
						<div class="switch_button">
							<?php if(!empty($serversettings["serverinfo"]["server_logo_home"]&&$serversettings["serverinfo"]["server_logo_login"])){?>
								<input id="serverlogo_onoff" class="switch_input" type='checkbox' name="serverlogo_onoff" value="true" checked/>
								<label for="serverlogo_onoff" class="switch_label"></label>
							<?php }else{?>
								<input id="serverlogo_onoff" class="switch_input" type='checkbox' name="serverlogo_onoff" value="true" />
								<label for="serverlogo_onoff" class="switch_label"></label>
							<?php }?>
						</div>
					</div>
					<div id="serverlogo">
						<p>サーバーロゴ</p>
						<div class="p2">サーバーの左上に表示されているuwuzuのロゴを独自のロゴに置き換えるときに使用します。<br>自動的に角が丸くなります。<br>URLより設定してください。<br>背景透過画像を推奨します。</div>
						<div class="p2">ログイン後のロゴ</div>
						<input id="serverlogo" placeholder="https://~" class="inbox" type="text" name="serverlogo_light" value="<?php if( !empty($serversettings["serverinfo"]["server_logo_home"]) ){ echo htmlentities($serversettings["serverinfo"]["server_logo_home"], ENT_QUOTES, 'UTF-8'); } ?>">
						<div class="p2">ログイン画面と利用規約などドキュメントページのロゴ</div>
						<input id="serverlogo" placeholder="https://~" class="inbox" type="text" name="serverlogo_dark" value="<?php if( !empty($serversettings["serverinfo"]["server_logo_login"]) ){ echo htmlentities($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8'); } ?>">
					</div>
					<script>
					if ($("#serverlogo_onoff").prop("checked")) {
						$('#serverlogo').show();
					}else{
						$('#serverlogo').hide();
					}
					$('#serverlogo_onoff').change(function(){
						$('#serverlogo').toggle();
					});
					</script>
					
					<div>
						<p>サーバー名</p>
						<div class="p2">サーバー名です。</div>
						<input id="servername" placeholder="uwuzuさ～ば～" class="inbox" type="text" name="servername" value="<?php if( !empty($serversettings["serverinfo"]["server_name"]) ){ echo htmlentities($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8'); } ?>">
					</div>

					<div>
						<p>サーバー紹介メッセージ</p>
						<div class="p2">サーバーの紹介メッセージです。</div>
						<textarea id="serverinfo" placeholder="たのしいさーばーです" class="inbox" type="text" name="serverinfo"><?php $sinfo = explode("\n", $serverinfo); foreach ($sinfo as $info) { echo $info; }?></textarea>
					</div>

					<div>
						<p>サーバー管理者の名前</p>
						<div class="p2">サーバー管理者名です。</div>
						<input id="serveradminname" placeholder="わたし" class="inbox" type="text" name="serveradminname" value="<?php if( !empty($serversettings["serverinfo"]["server_admin"]) ){ echo htmlentities($serversettings["serverinfo"]["server_admin"], ENT_QUOTES, 'UTF-8'); } ?>">
					</div>

					<div>
						<p>サーバーへのお問い合わせ用メールアドレス</p>
						<div class="p2">ユーザーからのお問い合わせメアドです。</div>
						<input id="servermailadds" placeholder="" class="inbox" type="text" name="servermailadds" value="<?php if( !empty($serversettings["serverinfo"]["server_admin_mailadds"]) ){ echo htmlentities($serversettings["serverinfo"]["server_admin_mailadds"], ENT_QUOTES, 'UTF-8'); } ?>">
					</div>

					<div>
						<p>招待制にするかどうか</p>
						<div class="switch_button">
							<?php if($serversettings["serverinfo"]["server_invitation"] === "true"){?>
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
						<p>ActivityPubサーバーとして認識されるようにするか</p>
						<div class="p2">ActivityPubの仮実装をオンにするかです。inboxに入ってきた内容には今現在これといったレスポンスを返しません。<br>また、publicKeyも返却しません。<br>現状ActivityPubサーバーと連合を組むことは出来ません。(リモートユーザーの確認程度なら出来ます。)<br>オフの状態だと410 Goneを返します。</div>
						<div class="switch_button">
							<?php if($serversettings["serverinfo"]["server_activitypub"] === "true"){?>
								<input id="activitypub" class="switch_input" type='checkbox' name="activitypub" value="true" checked/>
								<label for="activitypub" class="switch_label"></label>
							<?php }else{?>
								<input id="activitypub" class="switch_input" type='checkbox' name="activitypub" value="true" />
								<label for="activitypub" class="switch_label"></label>
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