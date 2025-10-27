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
	$is_Admin = safetext($is_login["admin"]);
}

$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

require('../logout/logout.php');

if (!empty($pdo)) {
    $sql = "SELECT * FROM jobs ORDER BY datetime DESC LIMIT 100";
    $alljobs = $pdo->query($sql);    

    while ($row = $alljobs->fetch(PDO::FETCH_ASSOC)) {
        $jobs[] = $row;
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
<title>ジョブ - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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
			<div class="formarea">
				<h1>ジョブ</h1>
				<p>直近100件のジョブを表示します。</p>
				<div class="p2">この機能はベータ版機能であり、今後変更や削除が行われるおそれがあります。</div>
				<?php if(!(empty($jobs))){?>
					<?php foreach ($jobs as $value) {
						switch ($value["status"]){
							case "waiting":
								$status = "Waiting";
								$color = "WARNING";
								break;
							case "running":
								$status = "Running";
								$color = "NOTICE";
								break;
							case "finished":
								$status = "Finished";
								$color = "INFO";
								break;
							case "error":
								$status = "Error";
								$color = "CRITICAL";
								break;
							default:
								$status = "Waiting";
								$color = "WARNING";
								break;
						}
						?>
						<div class="actionlog">
							<details>
								<summary><span class="<?php echo safetext($color);?>"><?php echo safetext($status);?></span><?php echo safetext($value["job"]);?> | <?php echo safetext($value["step"]);?></summary>
								<p>ジョブ: <?php echo safetext($value["job"]);?></p>
								<p>ステップ: <?php echo safetext($value["step"]);?></p>
								<p>発生日時: <?php echo safetext($value["datetime"]);?></p>
								<p>実行ユーザー: <?php echo safetext($value["userid"]);?></p>
							</details>
						</div>
					<?php }?>
				<?php }else{?>
					<p>ジョブはありません</p>
				<?php }?>
			</div>
		</div>
	</div>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>

</body>
<script type="text/javascript">
	function checkForm(inputElement) {
		var str = inputElement.value;
		while (str.match(/[^A-Za-z\d_]/)) {
			str = str.replace(/[^A-Za-z\d_]/, "");
		}
		inputElement.value = str;
	}

	var modal = document.getElementById('account_addrole_Modal');
    var deleteButton = document.getElementById('deleteButton');
    var cancelButton = document.getElementById('cancelButton'); // 追加
	var modalMain = $('.modal-content');

    document.getElementById("addrole").addEventListener('click', function(){
        modal.style.display = 'block';
		modalMain.addClass("slideUp");
    	modalMain.removeClass("slideDown");

        deleteButton.addEventListener('click', () => {
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal.style.display = 'none';
			}, 150);
        });

        cancelButton.addEventListener('click', () => { // 追加
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal.style.display = 'none';
			}, 150);
        });
    });

	var modal2 = document.getElementById('account_delrole_Modal');
    var delrole_deleteButton = document.getElementById('delrole_deleteButton');
    var delrole_cancelButton = document.getElementById('delrole_cancelButton'); // 追加
	var modalMain = $('.modal-content');

    document.getElementById("delrole").addEventListener('click', function(){
        modal2.style.display = 'block';
		modalMain.addClass("slideUp");
    	modalMain.removeClass("slideDown");

        delrole_deleteButton.addEventListener('click', () => {
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal2.style.display = 'none';
			}, 150);
        });

        delrole_cancelButton.addEventListener('click', () => { // 追加
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal2.style.display = 'none';
			}, 150);
        });
    });
</script>
</html>