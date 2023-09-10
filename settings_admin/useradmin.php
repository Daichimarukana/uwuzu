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
    $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

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


if( !empty($_POST['btn_submit']) ) {

    // 空白除去
	$target_userid = $_POST['target_userid'];

	if (!empty($pdo)) {
		$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));

		$rerole = $dbh->prepare("SELECT * FROM account WHERE userid = :userid");

		$rerole->bindValue(':userid', $target_userid);
		// SQL実行
		$rerole->execute();

		$userdata = $rerole->fetch(); // ここでデータベースから取得した値を $role に代入する

		if(empty($userdata)){
			$error_message[] = "ユーザーがいません";
		}else{
			$_SESSION['userdata'] = $userdata;

			// リダイレクト先のURLへ転送する
			$url = 'userinfo';
			header('Location: ' . $url, true, 303);
		
			// すべての出力を終了
			exit;
		}

	}
}

if( !empty($_POST['report_done']) ) {

	$report_id = $_POST['report_id'];

	if (!empty($pdo)) {
		$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));

		$newchk = "done";
		// トランザクション開始
		$pdo->beginTransaction();
	
		try {

			$stmt = $pdo->prepare("UPDATE report SET admin_chk = :adchk WHERE uniqid = :uniqid");
	
			$stmt->bindValue(':adchk', $newchk, PDO::PARAM_STR);
	
			$stmt->bindValue(':uniqid', $report_id , PDO::PARAM_STR);
	
			// SQLクエリの実行
			$res = $stmt->execute();
	
			// コミット
			$res = $pdo->commit();
	
			if ($res) {
				$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header("Location:".$url."");
				exit;  
			} else {
				$error_message[] = '発行に失敗しました。';
			}
	
		} catch (Exception $e) {
			$error_message[] = "えらー";
			// エラーが発生した時はロールバック
			$pdo->rollBack();
		}

	}
}
require('../logout/logout.php');

if(isset($_GET['q'])){ 
	$keyword = htmlentities($_GET['q']);
}else{
	$keyword = "";
}

if (!empty($pdo)) {
    
    $sql = "SELECT * FROM report WHERE admin_chk = 'none' ORDER BY datetime DESC";
    $allreport = $pdo->query($sql);    

    while ($row = $allreport->fetch(PDO::FETCH_ASSOC)) {

        $reports[] = $row;
    }
}

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
<title>ユーザー管理 - <?php echo file_get_contents($servernamefile);?></title>

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
				<h1>ユーザー管理</h1>
				<div>
					<p>ユーザーID</p>
					<div class="p2">「@」は外してください。</div>
					<input id="target_userid" placeholder="admin" class="inbox" type="text" name="target_userid" value="<?php if( !empty($keyword) ){ echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); } ?>">
				</div>

				<input type="submit" class = "irobutton" name="btn_submit" value="検索">

				<section class="inner">
					<div id="postContainer">
						

					</div>
				</section>

				<div id="loading" class="loading" style="display: none;">
					🤔
				</div>

				<hr>
			</form>
			<div class="formarea">
				<h1>通報</h1>
				<?php if(!(empty($reports))){?>
						<?php foreach ($reports as $value) {?>
							<div class="server_code">
								<details>
									<summary>@<?php if( !empty($value["userid"]) ){ echo htmlentities($value["userid"]); }?></summary>
									<hr>
									<p>通報先アカウント名:@<?php echo htmlentities($value["userid"]);?></p>
									<p>通報元アカウント名:@<?php echo htmlentities($value["report_userid"]);?></p>
									<hr>
									<p>通報元アカウントよりメッセージ</p>
									<p><?php echo nl2br(htmlentities($value["msg"]));?></p>
									<hr>
									<p>通報日時:<?php echo htmlentities($value["datetime"]);?></p>
									<hr>
									<p>アカウント操作を行う場合は上の「ユーザーID」にアカウントをしたいユーザーIDを入れて対応してください。</p>
									<form enctype="multipart/form-data" method="post">
										<div class="delbox">
											<p>解決ボタンを押すとこの件は解決済みとなります。</p>
											<input type="text" name="report_id" value="<?php echo htmlentities($value["uniqid"]);?>" style="display:none;" >
											<input type="submit" name="report_done" class="delbtn" value="解決">
										</div>
									</form>
								</details>
							</div>
						<?php }?>
				<?php }else{?>
					<p>通報されたアカウントはありません。</p>
				<?php }?>

			</div>
		</div>
	</div>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>

</body>
</html>