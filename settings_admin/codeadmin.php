<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

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

if( !empty($_POST['code_btn_submit']) ) {
	$make_code = safetext($_POST['make_code']);
	$code_num = 0;
	while ($code_num < (int)$make_code) {
		$code_num++;
		$pdo->beginTransaction();
		$datetime = date("Y-m-d H:i:s");

		try {

			$new_invcode = random_code();
			$used = "none";
	
			// SQL作成
			$stmt = $pdo->prepare("INSERT INTO invitation (code, used, datetime) VALUES (:code, :used, :datetime)");
	
			$stmt->bindParam(':code', $new_invcode, PDO::PARAM_STR);
			$stmt->bindParam(':used', $used, PDO::PARAM_STR);
			$stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);
	
			// SQLクエリの実行
			$res = $stmt->execute();
	
			// コミット
			$res = $pdo->commit();
	
		} catch (Exception $e) {
	
			// エラーが発生した時はロールバック
			$pdo->rollBack();
		}
	}
	if ($res) {
		actionLog($userid, "info", "codeadmin", null, "招待コードを".$make_code."件発行しました", 0);
		$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		header("Location:".$url."");
		exit;  
	} else {
		actionLog($userid, "error", "codeadmin", null, "招待コードを".$make_code."件発行できませんでした", 3);
		$error_message[] = '発行に失敗しました。(REGISTERED_DAME)';
	}

    // プリペアドステートメントを削除
    $stmt = null;
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
<title>招待コード発行所 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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
					<h1>招待コード発行所</h1>
					<?php if(safetext($serversettings["serverinfo"]["server_invitation"]) === "true"){?>
						<p>下の発行ボタンで新しくコードを発行できます！<br>なお、コードは一回限り有効です。</p>
						<div>
							<p>発行数</p>
							<input id="make_code" placeholder="1" class="inbox" type="number" name="make_code" value="1" min="1" max="10000">
						</div>
						<input type="submit" class = "irobutton" name="code_btn_submit" value="発行！">
						<?php 
						if(!(empty($codes))){
							foreach ($codes as $value) {?>
							<div class="server_code">
								<details>
									<summary>コード:<?php if( !empty($value["code"]) ){ echo safetext($value["code"]); }?><?php if( !empty($value["used"]) ){if($value["used"] === "true"){echo " ✅";}}?> </summary>
									<p>使用状況:<?php if( !empty($value["used"]) ){
										if($value["used"] === "none"){
											echo "未使用<br>発行日時:".$value["datetime"]."";
										}elseif($value["used"] === "true"){
											echo "使用済み<br>使用日時:".$value["datetime"]."";
										}}?></p>
									<div class="delbox">
										<p>削除ボタンを押すとこのコードは使用できなくなります。</p>
										<button type="button" id="code_delete" class="delbtn" del-code="<?php echo safetext($value["code"]);?>">削除</button>
									</div>
								</details>
							</div>	
							<?php }?>
						<?php }else{?>
							<p>招待コードは発行されていません。</p>
						<?php }?>
					<?php }else{?>
						<p>サーバーは招待制にされていないため招待コードは利用できません。</p>
					<?php }?>

				</form>
			</div>
		</div>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>
	
<script>
$(document).ready(function() {

	$(document).on('click', '.delbtn', function (event) {

        var code = $(this).attr('del-code');
		var userid = '<?php echo $userid; ?>';
		var account_id = '<?php echo $loginid; ?>';
		var codeElement = $(this).closest('.server_code');

		$.ajax({
			url: 'api/code_delete.php',
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