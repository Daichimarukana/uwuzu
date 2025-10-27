<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

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
    
    $sql = "SELECT ipaddr,note,adduserid,datetime FROM ipblock ORDER BY datetime DESC";
    $ipaddr_query = $pdo->query($sql);    

    while ($row = $ipaddr_query->fetch(PDO::FETCH_ASSOC)) {
        $ipaddr_list[] = $row;
    }
}

if( !empty($_POST['ip_btn_submit']) ) {
	$ipaddr = safetext($_POST['ipaddr']);
	if (strpos($ipaddr, '/')) {
		[$network, $prefixLength] = explode('/', $ipaddr);
	}else{
		$network = $ipaddr;
		$prefixLength = null;
	}
	
	$note = safetext($_POST['note']);

	if(filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){

		$search_query = $pdo->prepare('SELECT * FROM ipblock WHERE ipaddr = :ipaddr limit 1');
		if(!(empty($prefixLength))){
			$pre_ip = $network."/".$prefixLength;
			$search_query->execute(array(':ipaddr' => $pre_ip));
		}else{
			$search_query->execute(array(':ipaddr' => $network));
		}
		
		$result = $search_query->fetch();

		if($result > 0){
			$error_message[] = 'IPアドレスはすでに登録されています。(ERROR)';
		} else {
			$pdo->beginTransaction();
			$datetime = date("Y-m-d H:i:s");
			try {
				$stmt = $pdo->prepare("INSERT INTO ipblock (ipaddr, note, adduserid, datetime) VALUES (:ipaddr, :note, :adduserid, :datetime)");

				$stmt->bindParam(':ipaddr', $ipaddr, PDO::PARAM_STR);
				$stmt->bindParam(':note', $note, PDO::PARAM_STR);
				$stmt->bindParam(':adduserid', $userid, PDO::PARAM_STR);
				$stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

				// SQLクエリの実行
				$res = $stmt->execute();

				$res = $pdo->commit();
			} catch (Exception $e) {
				$pdo->rollBack();
				actionLog($userid, "error", "ipblock_admin_add", null, $e, 4);
			}

			if ($res) {
				actionLog($userid, "info", "ipblock_admin_add", null, "ブロックするIPアドレスを追加しました", 0);
				$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header("Location:".$url."");
				exit;  
			} else {
				actionLog($userid, "error", "ipblock_admin_add", null, "ブロックするIPアドレスを追加できませんでした", 3);
				$error_message[] = '登録に失敗しました。(REGISTERED_DAME)';
			}

			$stmt = null;
		}
	} else {
		$error_message[] = '不正なIPアドレスです。(ERROR)';
	}
}

if( !empty($_POST['ip_del_submit']) ) {
	$ipaddr = safetext($_POST['del_ipaddr']);

	if (strpos($ipaddr, '/')) {
		[$network, $prefixLength] = explode('/', $ipaddr);
	}else{
		$network = $ipaddr;
		$prefixLength = null;
	}

	if(filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
		$search_query = $pdo->prepare('SELECT * FROM ipblock WHERE ipaddr = :ipaddr limit 1');
		if(!(empty($prefixLength))){
			$pre_ip = $network."/".$prefixLength;
			$search_query->execute(array(':ipaddr' => $pre_ip));
		}else{
			$search_query->execute(array(':ipaddr' => $network));
		}
		$result = $search_query->fetch();

		if($result > 0){
			$pdo->beginTransaction();
			try{
				$deleteQuery = $pdo->prepare("DELETE FROM ipblock WHERE ipaddr = :ipaddr");
				$deleteQuery->bindValue(':ipaddr', $ipaddr, PDO::PARAM_STR);
				if(!(empty($prefixLength))){
					$pre_ip = $network."/".$prefixLength;
					$deleteQuery->bindValue(':ipaddr', $pre_ip, PDO::PARAM_STR);
				}else{
					$deleteQuery->bindValue(':ipaddr', $network, PDO::PARAM_STR);
				}
				$res = $deleteQuery->execute();
				$res = $pdo->commit();
			} catch (Exception $e) {
				$pdo->rollBack();
				$res = null;
				actionLog($userid, "error", "ipblock_admin_del", null, $e, 4);
			}

			if ($res) {
				actionLog($userid, "info", "ipblock_admin_del", null, "ブロックするIPアドレスを削除しました", 0);
				$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header("Location:".$url."");
				exit;  
			} else {
				actionLog($userid, "error", "ipblock_admin_del", null, "ブロックするIPアドレスを削除できませんでした", 3);
				$error_message[] = '削除に失敗しました。(REGISTERED_DAME)';
			}
		} else {
			$error_message[] = 'IPアドレスが見つかりませんでした。(ERROR)';
		}
	} else {
		$error_message[] = '不正なIPアドレスです。(ERROR)';
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
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>IPブロック - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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
					<h1>IPブロック</h1>
					<p>IPアドレスのブロック機能です。</p>
					<div>
						<p>IPアドレス</p>
						<div class="p2">IPv4とIPv6に対応しています。<br>
							CIDR表記にも対応しています。</div>
						<input id="ipaddr" placeholder="000.000.000.000" class="inbox" type="text" name="ipaddr">
					</div>
					<div>
						<p>ノート</p>
						<textarea placeholder="ここに内容" class="inbox" name="note"></textarea>
					</div>
					<input type="submit" class = "irobutton" name="ip_btn_submit" value="登録">
				</form>
				<div class="formarea">
					<h1>ブロック中のIPアドレス</h1>
					<?php 
					if(!(empty($ipaddr_list))){
						foreach ($ipaddr_list as $value) {?>
						<div class="server_code">
							<details>
								<summary><?php if( !empty($value["ipaddr"]) ){ echo safetext($value["ipaddr"]); }?></summary>
								<p>登録ユーザー:<?php if( !empty($value["adduserid"]) ){ echo safetext($value["adduserid"]); }?></p>
								<p>登録日時:<?php if( !empty($value["datetime"]) ){ echo safetext($value["datetime"]); }?></p>
								<hr>
								<p><?php if( !empty($value["note"]) ){ echo nl2br(safetext($value["note"])); }?></p>
								<hr>
								<form class="delbox" enctype="multipart/form-data" method="post">
									<p>削除ボタンを押すとこのIPアドレスは削除されます。</p>
									<input id="del_ipaddr" style="display: none;" type="text" name="del_ipaddr" value="<?php if( !empty($value["ipaddr"]) ){ echo safetext($value["ipaddr"]); }?>">
									<input type="submit" class="delbtn" name="ip_del_submit" value="削除">
								</form>
							</details>
						</div>	
						<?php }?>
					<?php }else{?>
						<p>IPアドレスは登録されていません。</p>
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