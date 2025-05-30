<?php

function random_token($length = 64)
{
    return substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}
$domain = $_SERVER['HTTP_HOST'];
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

	$userQuery = $dbh->prepare("SELECT userid,token,role FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $userid);
	$userQuery->execute();
	$userData = $userQuery->fetch();
	
}

if( !empty($_POST['btn_submit']) ) {

	$chkuserid = safetext($_POST['chkuserid']);

	if( empty($chkuserid) ) {
		$error_message[] = '確認用ユーザーIDを入力してください。(USERID_CHECK_INPUT_PLEASE)';
	} else {
		if($chkuserid === $userData["userid"]){
			$res = addJob($pdo, $userData["userid"], "deleteUser", "stop_account");
		
			if ($res) {
				if (isset($_SERVER['HTTP_COOKIE'])) {
					$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
					foreach($cookies as $cookie) {
						$parts = explode('=', $cookie);
						$name = trim($parts[0]);
						setcookie($name, '', time()-1000);
						setcookie($name, '', time()-1000, '/');
					}
				}
				header("Location:../index.php");
				exit;
			} else {
				$error_message[] = 'アカウント削除に失敗しました。(ACCOUNT_DELETE_DAME)';
			}

		
			// プリペアドステートメントを削除
			$stmt = null;
		}else{
			$error_message[] = '確認用ユーザーIDが違います。(USERID_CHIGAUYANKE)';
		}
    }


}


if( !empty($_POST['session_submit']) ) {
	$LoginIdBytes = random_bytes(64);
	$loginid = hash('sha3-512', $LoginIdBytes);
	$pdo->beginTransaction();
		try {
			
            $stmt = $pdo->prepare("UPDATE account SET loginid = :loginid WHERE userid = :userid;");

            $stmt->bindParam(':loginid', $loginid, PDO::PARAM_STR);

            $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);

                // SQLクエリの実行
            $res = $stmt->execute();

            // コミット
            $res = $pdo->commit();
		} catch (Exception $e) {

			// エラーが発生した時はロールバック
			$pdo->rollBack();
		}
	
		if ($res) {
			if (isset($_SERVER['HTTP_COOKIE'])) {
				$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
				foreach($cookies as $cookie) {
					$parts = explode('=', $cookie);
					$name = trim($parts[0]);
					setcookie($name, '', time()-1000);
					setcookie($name, '', time()-1000, '/');
				}
			}
			// リダイレクト先のURLへ転送する
			$url = '../index.php';
			header('Location: ' . $url, true, 303);
		
			// すべての出力を終了
			exit;
		} else {
			$error_message[] = 'セッショントークンの再生成に失敗しました。(END_OF_SESSION_DAME)';
		}
}

if( !empty($_POST['logout_submit']) ) {
	$url = '../logout/index.php';
	header('Location: ' . $url);
	exit;
}

if( !empty($_POST['token_submit']) ) {
	$token = random_token();
	$nones = "none";
	$pdo->beginTransaction();
		try {
			
            $stmt = $pdo->prepare("UPDATE account SET token = :token, sacinfo = :new_sacinfo WHERE userid = :userid;");

            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
			$stmt->bindParam(':new_sacinfo', $nones, PDO::PARAM_STR);

            $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);

                // SQLクエリの実行
            $res = $stmt->execute();

            // コミット
            $res = $pdo->commit();
		} catch (Exception $e) {

			// エラーが発生した時はロールバック
			$pdo->rollBack();
		}
	
		if ($res) {
			$_SESSION['token'] = $token;
			// リダイレクト先のURLへ転送する
			$url = 'token.php';
			header('Location: ' . $url, true, 303);
			exit; 
		} else {
			$error_message[] = 'アクセストークンの発行に失敗しました。(CREATE_TOKEN_DAME)';
		}

}

if( !empty($_POST['token_off_submit']) ) {
	$token = '';
	$new_sacinfo = 'none';
	$pdo->beginTransaction();
		try {
			
            $stmt = $pdo->prepare("UPDATE account SET token = :token,sacinfo = :sacinfo WHERE userid = :userid;");

            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
			$stmt->bindParam(':sacinfo', $new_sacinfo, PDO::PARAM_STR);

            $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);

                // SQLクエリの実行
            $res = $stmt->execute();

            // コミット
            $res = $pdo->commit();
		} catch (Exception $e) {

			// エラーが発生した時はロールバック
			$pdo->rollBack();
		}
	
		if ($res) {
			$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			header("Location:".$url."");
			exit; 
		} else {
			$error_message[] = 'アクセストークンの削除に失敗しました。(TOKEN_DELETE_DAME)';
		}

}

if( !empty($_POST['cache_submit']) ) {
	header("Location: cache_clear.php");
	exit; 
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
<title>その他の項目 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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
        <form class="formarea" method="post">

		<h1>セッショントークンの再生成</h1>
		<p>下のセッショントークン再生成ボタンを押すと全てのログイン中のデバイスからログアウトされます。<br>再度uwuzu使用するにはログインが必須になります。</p>
		<input type="submit" class = "irobutton" name="session_submit" value="セッショントークン再生成">

		<hr>

		<h1>ログアウト</h1>
		<p>ログアウトです。他のログイン済みの端末からはログアウトされません。</p>
		<input type="submit" class = "irobutton" name="logout_submit" value="ログアウト">

		<hr>

		<h1>キャッシュクリア</h1>
		<p>下のボタンを押すことでキャッシュクリアが可能です。</p>
		<div class="p2">この機能は試験的なものであり、正常に動作しない可能性があります。</div>
		<input type="submit" class = "irobutton" name="cache_submit" value="キャッシュクリア">

		<hr>

		<h1>チュートリアル</h1>
		<p>uwuzuの基礎的なチュートリアルを行うことができます！</p>
		<input type="button" class = "irobutton" id="tutorial" value="チュートリアル">

		<hr>
		<h1>他のサーバーへアカウント移行</h1>
		<p>uwuzuサーバー同士でのアカウント移行が可能になりました！</p>
		<?php if($userData['token']==='ice'){ ?>
			<p>このアカウントは凍結されているため移行できません。</p>
		<?php }else{ ?>
			<a href="account_migration" class="irobutton">アカウント移行</a>
		<?php }?>

		<hr>
		<h1>アカウント削除</h1>
		<p>アカウント誤削除を防ぐため下の入力ボックスにご自身のユーザーIDを入力する必要があります。</p>
		<?php if($is_Admin === "yes"){?>
			<p class="errmsg">あなたはこのサーバーの管理者のようです。<br>管理者アカウントの移行は済んでいますか？<br>アカウントを削除しても大丈夫なのですか...？</p>
		<?php }?>
		<div>
			<p>確認用ユーザーID</p>
			<input id="chkuserid" placeholder="" class="inbox" type="text" name="chkuserid" value="">
		</div>
		<input type="submit" class = "irobutton" name="btn_submit" value="アカウント削除">
		
		<hr>
		<h1>API</h1>
		<p>APIの使用方法はdocs.uwuzu.xyzよりAPIドキュメントをご確認ください。</p>

		<?php 
			if(empty($userData['token'])){
		?>
		<p>以下のボタンよりアクセストークンを取得すると使用できます。<br>アクセストークンは一度発行すると作り直すまで再度確認はできません。また、絶対に他人に知られないように保護してください。<p>
		<input type="submit" class = "irobutton" name="token_submit" value="アクセストークン発行">
		<?php }elseif($userData['role']==='ice'){ ?>
			<p>アクセストークンはアカウントが凍結されているため発行できません。</p>
		<?php }else{ ?>
			<p>以下のボタンよりアクセストークンを削除できます。ボタンを押すとすぐに削除されますのでご注意ください。</p>
			<input type="submit" class = "irobutton" name="token_off_submit" value="アクセストークン削除">
		<?php }?>
		<hr>
		<div class="p2" id="help_me">もしものときは</div>

        </form>

		<div id="help_me_Modal" class="modal">
			<div class="modal-content">
				<h1>もしものときは</h1>
				<p>こんにちは、uwuzu開発者のだいちまるです。<br>ここを見ているということはなにかあったのでしょうか...<br>心配です。</p>
				<p>もし炎上をしてしまったり、インターネットによる心身のつらさなどに襲われ生きづらかったり周りと触れづらい状態にあるならば信頼できる人への相談や失踪をして、インターネットの海から離れるのも良いかもしれません。</p>
				<p>インターネットが全てではないですし、このサーバーからいなくなるだけでも気が楽になるかもしれません。</p>
				<p>一度ゆっくり休んでから人生を再開してみてはいかがでしょうか、自分を第一に、自分を大事に。<br>そして、インターネットは情報の海であることを忘れないように。</p>

				<form method="post" id="deleteForm">
					<div class="btn_area">
						<input type="button" id="cancelButton" class="fbtn" value="とじる">
					</div>
				</form>
			</div>
		</div>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>
	<?php require('../require/tutorial.php');?>
</body>
</html>

<script>
$(document).ready(function() {
	$('#tutorial').on('click', function() {
		$(".tutorial_background").show();
	});

	var modal = document.getElementById('help_me_Modal');
    var cancelButton = document.getElementById('cancelButton');
	var modalMain = $('.modal-content');

	$('#help_me').on('click', function() {
        modal.style.display = 'block';
		modalMain.addClass("slideUp");
    	modalMain.removeClass("slideDown");

        cancelButton.addEventListener('click', () => { // 追加
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal.style.display = 'none';
			}, 150);
        });
    });
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