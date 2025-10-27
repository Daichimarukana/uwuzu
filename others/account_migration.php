<?php

function random_token($length = 64)
{
    return substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}
$domain = $_SERVER['HTTP_HOST'];
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

function random($length){
    return substr(str_shuffle('1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 0, $length);
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

// データベースに接続
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
$is_login = uwuzuUserLogin($_SESSION, $_COOKIE, $_SERVER['REMOTE_ADDR'], "user");
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

	$userQuery = $dbh->prepare("SELECT userid,role,datetime FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $userid);
	$userQuery->execute();
	$userData = $userQuery->fetch();
	
}

if( !empty($_POST['migration_submit']) ) {

	$server_domain = safetext($_POST['server_domain']);

	if( empty($server_domain) ) {
		$error_message[] = '移行先のサーバードメインを入力してください。(INPUT_PLEASE)';
	}else{
		$domain_response = @file_get_contents("https://".$server_domain."/");
		if (empty($domain_response)) {
			$error_message[] = '入力されたドメインに接続できませんでした。(INPUT_PLEASE)';
		}
	}
	if($server_domain == $domain){
		$error_message[] = 'このサーバーに移行することはできません。(MIGRATION_ONAJI_SERVER_DAME)';
	}

    if(empty($error_message)){
        $data = array();
        $options = [
            'http' => [
                'method'=> 'POST',
                'content' => http_build_query($data, '', '&')
            ]
        ];
        $Get_Info = @file_get_contents("http://".$server_domain."/api/serverinfo-api", false, stream_context_create($options));
		if($Get_Info === false){
			$error_message[] = '入力されたサーバーがuwuzu以外のサーバーソフトウェア(MisskeyさんやMastodonさんなど)を使用しているかuwuzu v1.2.26未満のバージョンを使用している可能性があります。(MIGRATION_TO_SERVER_NOT_UWUZU)';
		}else{
			$Check_result = json_decode($Get_Info, true);
			
			if($Check_result["software"]["name"] == "uwuzu"){
				$version = str_pad(str_replace('.', '', $Check_result["software"]["version"]), 4, 0, STR_PAD_RIGHT);
				
				if($version >= 1360){
					if($Check_result["server_info"]["account_migration"] == "true"){
						$pdo->beginTransaction();
						try {
							$account = $userid;
							$migration_code = createUniqId();
							$encryption_key = random(32);
							$encryption_ivkey = random(16);
							$datetime = date("Y-m-d H:i:s");
							$domain = $server_domain;

							$stmt = $pdo->prepare("INSERT INTO migration (account, domain, migration_code, encryption_key, encryption_ivkey, datetime) VALUES (:account, :domain, :migration_code, :encryption_key, :encryption_ivkey, :datetime)");

							$stmt->bindParam(':account', safetext($account), PDO::PARAM_STR);
							$stmt->bindParam(':domain', safetext($domain), PDO::PARAM_STR);
							$stmt->bindParam(':migration_code', safetext($migration_code), PDO::PARAM_STR);
							$stmt->bindParam(':encryption_key', safetext($encryption_key), PDO::PARAM_STR);
							$stmt->bindParam(':encryption_ivkey', safetext($encryption_ivkey), PDO::PARAM_STR);
							$stmt->bindParam(':datetime', safetext($datetime), PDO::PARAM_STR);

							$res = $stmt->execute();

							$res = $pdo->commit();

						} catch(Exception $e) {
							$pdo->rollBack();
						}
						if($res) {
							$_SESSION["migration_code"] = safetext($migration_code);
							$_SESSION["encryption_key"] = safetext($encryption_key);
							$_SESSION["encryption_ivkey"] = safetext($encryption_ivkey);
							header("Location: account_migration_done.php");
							exit;  
						}else{
							$error_message[] = $e->getMessage();
						}
					}else{
						$error_message[] = "移行先のサーバーがアカウントの移行登録を拒否しているためアカウントの移行はできません。(MIGRATION_TO_SERVER_IYADA)";
					}
				}else{
					$error_message[] = "移行先のサーバーのuwuzuバージョンが1.3.6未満のためアカウントの移行はできません。(MIGRATION_TO_SERVER_BAD_UWUZU_VERSION)";
				}
			}else{
				$error_message[] = "移行先のサーバーのソフトウェアがuwuzuではありません。(MIGRATION_TO_SERVER_NOT_UWUZU)";
			}
		}
    }
   
}

if( !empty($_POST['migration_cancel_submit']) ) {
	$account = $userid;
	$pdo->beginTransaction();
	try {
		$deleteQuery = $pdo->prepare("DELETE FROM migration WHERE account = :account");
		$deleteQuery->bindValue(':account', safetext($account), PDO::PARAM_STR);
		$res = $deleteQuery->execute();
		$res = $pdo->commit();
	} catch(Exception $e) {
		$pdo->rollBack();
	}
	if($res) {
		$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		header("Location: ".$url."");
		exit;  
	}else{
		$error_message[] = $e->getMessage();
	}
}

if(!(empty($pdo))){
	$CheckQuery = $pdo->prepare("SELECT COUNT(*) as count FROM migration WHERE account = :account");
	$CheckQuery->bindValue(':account', $userid);
	$CheckQuery->execute();
	$CheckData = $CheckQuery->fetch(PDO::FETCH_ASSOC);
	$CheckCount = $CheckData['count'];
	if(!(empty($CheckCount))){
		if((int)$CheckCount > 0){
			$migration_start = true;
		}else{
			$migration_start = false;
		}
	}else{
		$migration_start = false;
	}
}

$today = strtotime(date("Y-m-d"));
$accountDate = new DateTime($userData["datetime"]);
$day = strtotime($accountDate->format('Y-m-d'));
$account_date = ($today - $day) / (60 * 60 * 24);
if($account_date > 30){
	$migration = true;
}else{
	$migration = false;
}

require('../logout/logout.php');


?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/home.css">
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="../js/jquery-min.js"></script>
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>アカウントの移行 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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
        <form class="formarea" enctype="multipart/form-data" method="post">
            <h1>アカウントの移行</h1>
            <p>アカウントの移行をすると以下のデータが引き継がれます。<br>フォロー・フォロワー、いいねなどの情報は引き継がれません。<br>ユーザーID、パスワードや二段階認証の設定は移行先サーバーで再度行う必要があります。<br>移行後30日は別のサーバーに移行することはできません。<p>
            <p>- アカウント名<br>
            - プロフィール<br>
            - アイコン<br>
            - ヘッダー<br>
            - メールアドレス</p>
			<p>アカウントの移行後このサーバーのアカウントは自動凍結されます。</p>
			<?php
			if($migration == true){?>
				<?php if($userData['role']==='ice'){ ?>
					<p>このアカウントは凍結されているため移行できません。</p>
				<?php }else{ ?>
					<?php if($migration_start == true){?>
						<div class="errmsg">
							<p>既に移行作業は開始されているようです。<br>もし以降に必要な情報をなくしてしまった場合はもう一度最初から作業をやり直す必要があります！</p>
						</div>
						<input type="submit" class = "irobutton" name="migration_cancel_submit" value="アカウントの移行を取り消す">
					<?php }else{?>
						<div>
							<p>移行先サーバーのドメイン</p>
							<div class="p2">uwuzu v1.3.6以上が使用されているサーバーに対応します。</div>
							<input id="server_domain" type="text" placeholder="uwuzu.example.com" class="inbox" name="server_domain" value="">
						</div>
						<input type="submit" class = "irobutton" name="migration_submit" value="アカウントの移行を開始">
					<?php }?>
				<?php }?>
			<?php }else{?>
				<p>アカウントを作成してから30日以上経過していないとアカウントの移行はできません。</p>
			<?php }?>
        </form>

        <div class="btnbox">
                <a href="javascript:history.back();" class="sirobutton">戻る</a>
            </div>
        </div>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>
</body>
</html>

<script>
$(document).ready(function() {
    $(function(){
        $("input"). keydown(function(e) {
            if ((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
                return false;
            } else {
                return true;
            }
        });
    });
});
</script>