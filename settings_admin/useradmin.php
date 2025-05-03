<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

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

if( !empty($_POST['btn_submit']) ) {

    // 空白除去
	$target_userid = safetext(str_replace('@', '', $_POST['target_userid']));

	if (!empty($pdo)) {
		$rerole = $pdo->prepare("SELECT * FROM account WHERE userid = :userid");

		$rerole->bindValue(':userid', $target_userid);
		// SQL実行
		$rerole->execute();

		$userdata = $rerole->fetch(); // ここでデータベースから取得した値を $role に代入する

		if(empty($userdata)){
			$error_message[] = "ユーザーがいません(USER_NOT_FOUND)";
		}else{
			$_SESSION['query_userid'] = $userdata["userid"];

			// リダイレクト先のURLへ転送する
			$url = 'userinfo';
			header('Location: ' . $url, true, 303);
		
			// すべての出力を終了
			exit;
		}

	}
}

if( !empty($_POST['report_done']) ) {

	$report_id = safetext($_POST['report_id']);

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
				$error_message[] = '発行に失敗しました。(REGISTERED_DAME)';
			}
	
		} catch (Exception $e) {
			$error_message[] = "えらー(ERROR)";
			// エラーが発生した時はロールバック
			$pdo->rollBack();
		}

	}
}
require('../logout/logout.php');

if(isset($_GET['q'])){ 
	$keyword = safetext($_GET['q']);
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
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>ユーザー管理 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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
					<input id="target_userid" placeholder="admin" class="inbox" type="text" name="target_userid" value="<?php if( !empty($keyword) ){ echo safetext($keyword); } ?>">
				</div>

				<input type="submit" class = "irobutton" name="btn_submit" value="検索">

				<section class="inner">
					<div id="postContainer">
						

					</div>
				</section>

				<div id="loading" class="loading" style="display: none;">
					🤔
				</div>
			</form>
			<div class="formarea">
				<h1>通報</h1>
				<?php if(!(empty($reports))){?>
						<?php foreach ($reports as $value) {?>
							<div class="server_code">
								<details>
									<summary>@<?php if( !empty($value["userid"]) ){ echo safetext($value["userid"]); }?></summary>
									<hr>
									<p>通報先アカウント名:@<?php echo safetext($value["userid"]);?></p>
									<p>通報元アカウント名:@<?php echo safetext($value["report_userid"]);?></p>
									<hr>
									<p>通報元アカウントよりメッセージ</p>
									<p><?php echo nl2br(safetext($value["msg"]));?></p>
									<hr>
									<p>通報日時:<?php echo safetext($value["datetime"]);?></p>
									<hr>
									<p>アカウント操作を行う場合は上の「ユーザーID」にアカウントをしたいユーザーIDを入れて対応してください。</p>
									<form enctype="multipart/form-data" method="post">
										<div class="delbox">
											<p>解決ボタンを押すとこの件は解決済みとなります。</p>
											<input type="text" name="report_id" value="<?php echo safetext($value["uniqid"]);?>" style="display:none;" >
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
	<?php require('../require/noscript_modal.php');?>

</body>

</html>