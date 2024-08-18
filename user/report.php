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

$userid = safetext($_SESSION['userid']);
$username = safetext($_SESSION['username']);

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

if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] == true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', safetext($_SESSION['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_SESSION['loginid'] === $res["loginid"] && $_SESSION['userid'] == $res["userid"]){
	// セッションに値をセット
	$userid = safetext($res['userid']); // セッションに格納されている値をそのままセット
	$username = safetext($res['username']); // セッションに格納されている値をそのままセット
	$loginid = safetext($res["loginid"]);
	$role = safetext($res["role"]);
	$sacinfo = safetext($res["sacinfo"]);
	$myblocklist = safetext($res["blocklist"]);
	$myfollowlist = safetext($res["follow"]);
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
	$passQuery->bindValue(':userid', safetext($_COOKIE['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_COOKIE['loginid'] === $res["loginid"] && $_COOKIE['userid'] == $res["userid"]){
	// セッションに値をセット
	$userid = safetext($res['userid']); // クッキーから取得した値をセット
	$username = safetext($res['username']); // クッキーから取得した値をセット
	$loginid = safetext($res["loginid"]);
	$role = safetext($res["role"]);
	$sacinfo = safetext($res["sacinfo"]);
	$myblocklist = safetext($res["blocklist"]);
	$myfollowlist = safetext($res["follow"]);
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
$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

function customStripTags($html, $allowedTags) {
    $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
    return strip_tags($html, $allowedTagsString);
}

$allowedTags = array('h1', 'h2', 'h3', 'center', 'font');

if( !empty($pdo) ) {
	
	// データベース接続の設定
	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	));

	if(isset($_GET['q'])){ 
		$uwuzuid = safetext($_GET['q']);
	}else{
		$uwuzuid = "";
	}
	
	// ユーズ内の絵文字を画像に置き換える
	function replaceEmojisWithImages($postText) {
		// ユーズ内で絵文字名（:emoji:）を検出して画像に置き換える
		$emojiPattern = '/:(\w+):/';
		$postTextWithImages = preg_replace_callback($emojiPattern, function($matches) {
			$emojiName = $matches[1];
			return "<img src='../emoji/emojiimage.php?emoji=" . urlencode($emojiName) . "' alt=':$emojiName:' title=':$emojiName:'>";
		}, $postText);
		
		// @username を検出してリンクに置き換える
		$usernamePattern = '/@(\w+)/';
		$postTextWithImagesAndUsernames = preg_replace_callback($usernamePattern, function($matches) {
			$username = $matches[1];

			$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			));
		
			$mentionsuserQuery = $dbh->prepare("SELECT username, userid FROM account WHERE userid = :userid");
			$mentionsuserQuery->bindValue(':userid', $username);
			$mentionsuserQuery->execute();
			$mentionsuserData = $mentionsuserQuery->fetch();   
			
			if(empty($mentionsuserData)){
				return "@$username";
			}else{
				return "<a class = 'mta' href='/@".$mentionsuserData["userid"]."'>@".$mentionsuserData["username"]."</a>";
			}
		}, $postTextWithImages);

		$hashtagsPattern = '/#([\p{Han}\p{Hiragana}\p{Katakana}A-Za-z0-9_]+)/u';
		$postTextWithHashtags = preg_replace_callback($hashtagsPattern, function($matches) {
			$hashtags = $matches[1];
				return "<a class = 'hashtags' href='/search?q=".urlencode('#').$hashtags."'>".'#'.$hashtags."</a>";
		}, $postTextWithImagesAndUsernames);

		return $postTextWithHashtags;
	}

	function replaceURLsWithLinks($postText) {
		// URLを正規表現を使って検出
		$pattern = '/(https?:\/\/[^\s]+)/';
		preg_match_all($pattern, $postText, $matches);
	
		// 検出したURLごとに処理を行う
		foreach ($matches[0] as $url) {
			// ドメイン部分を抽出
			$parsedUrl = parse_url($url);
			$domain = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
	
			// ドメインのみを表示するaタグを生成
			$link = "<a href='$url'>$domain</a>";
	
			// URLをドメインのみを表示するaタグで置き換え
			$postText = str_replace($url, $link, $postText);
		}
	
		return $postText;
	}

	$userQuery = $dbh->prepare("SELECT username, userid, profile, role, follower FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $uwuzuid);
	$userQuery->execute();
	$userData = $userQuery->fetch();


	if(empty($userData["userid"])){
		$userData["userid"] = "none";
		$userData['username'] = "でふぉると";
		$userData['profile'] = "プロフィールはありません。";
		$server_on_userchk = false;
	}else{
		$server_on_userchk = true;
	}
}

if (!empty($_POST['report'])) {
	$msg = safetext($_POST['send_text']);
	
	// 書き込み日時を取得
	$datetime = date("Y-m-d H:i:s");
	$uniqid = createUniqId();
	$admin_chk = "none";
	$touserid = $userData['userid'];

	//管理者取得
	$adminQuery = $pdo->prepare("SELECT userid FROM account WHERE admin = :adminid");
	$adminQuery->bindValue(':adminid', "yes");
	$adminQuery->execute();
	$admin_res = $adminQuery->fetchAll();

	// トランザクション開始
	$pdo->beginTransaction();

	try {

		// SQL作成
		$stmt = $pdo->prepare("INSERT INTO report (uniqid, userid, report_userid, msg, datetime, admin_chk) VALUES (:uniqid, :userid, :report_userid, :msg, :datetime, :admin_chk)");

		$stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
		$stmt->bindParam(':userid', $touserid, PDO::PARAM_STR);
		$stmt->bindParam(':report_userid', $userid, PDO::PARAM_STR);
		$stmt->bindParam(':msg', $msg, PDO::PARAM_STR);
		$stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

		$stmt->bindParam(':admin_chk', $admin_chk, PDO::PARAM_STR);

		// SQLクエリの実行
		$res = $stmt->execute();

		// コミット
		$res = $pdo->commit();

		foreach ($admin_res as $to_admin) {
		
			$pdo->beginTransaction();

			try {
				$fromuserid = $userid;
				$touserid2 = $to_admin["userid"];//管理者宛通知
				$datetime = date("Y-m-d H:i:s");
				$msg = "通報情報をご確認ください！";
				$title = "🚨" . $touserid . "さんが通報されました！🚨";
				$url = "/settings_admin/useradmin";
				$userchk = 'none';

				// 通知用SQL作成
				$stmt = $pdo->prepare("INSERT INTO notification (fromuserid, touserid, msg, url, datetime, userchk, title) VALUES (:fromuserid, :touserid, :msg, :url, :datetime, :userchk, :title)");

				$stmt->bindParam(':fromuserid', safetext('uwuzu-fromsys'), PDO::PARAM_STR);
				$stmt->bindParam(':touserid', safetext($touserid2), PDO::PARAM_STR);
				$stmt->bindParam(':msg', $msg, PDO::PARAM_STR);
				$stmt->bindParam(':url', safetext($url), PDO::PARAM_STR);
				$stmt->bindParam(':userchk', safetext($userchk), PDO::PARAM_STR);
				$stmt->bindParam(':title', safetext($title), PDO::PARAM_STR);

				$stmt->bindParam(':datetime', safetext($datetime), PDO::PARAM_STR);

				// SQLクエリの実行
				$res = $stmt->execute();

				// コミット
				$res = $pdo->commit();

			} catch(Exception $e) {

				// エラーが発生した時はロールバック
				$pdo->rollBack();
			}
	
		}

	} catch(Exception $e) {

		// エラーが発生した時はロールバック
		$pdo->rollBack();
	}

	if( $res ) {
		header("Location:success?q=".var_dump($admin_res["userid"]));
        exit;
	} else {
		$error_message[] = $e->getMessage();
	}

	// プリペアドステートメントを削除
	$stmt = null;
}

require('../logout/logout.php');



// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<script src="../js/jquery-min.js"></script>
<script src="../js/console_notice.js"></script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/home.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title><?php echo safetext($userData['username']); ?> さんを通報 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

</head>

<body>
	<?php require('../require/leftbox.php');?>
	<main class="outer">

		<?php if( !empty($error_message) ): ?>
			<ul class="errmsg">
				<?php foreach( $error_message as $value ): ?>
					<p>・ <?php echo $value; ?></p>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if($server_on_userchk === true){?>
			<form class="formarea" enctype="multipart/form-data" method="post">
				<h1>通報</h1>
				<div class="p2">通報先アカウント名</div>
				<p>@<?php echo safetext($userData['username']); ?></p>
				<div class="p2">通報先id</div>
				<p>@<?php echo safetext($userData['userid']); ?></p>
				<div class="p2">プロフィール</div>
				<p><?php echo nl2br(safetext($userData['profile'])); ?></p>
				<hr>
				<p><?php echo safetext($userData['username']); ?>さんを通報しますか？<br>通報すると管理者宛に通知が届き、管理者の判断により<?php echo safetext($userData['username']); ?>さんのアカウントに対処が行われます。<br>なお、虚偽の通報や理にかなわない通報などによっては管理者の判断によりあなたのアカウントが凍結やBAN(削除)される可能性があります。</p>
				<p>管理者は通報者のid(@<?php echo safetext($userid); ?>)を確認できます。</p>
				<p>もし通報理由などありましたら下の入力欄に記載してください。</p>
			<?php if($userid === $userData['userid']){; ?>
				<ul class="errmsg">
				<p>通報しようとしているアカウントはご自身のアカウントのようですが本当に通報してもよろしいのですか？<br>もしアカウントの削除をしたいのであれば左側メニューよりその他からアカウント削除が可能です。</p>
				</ul>
			<?php }?>
			<textarea id="send_text" placeholder="通報理由" name="send_text" class="inbox"></textarea>
			<div class="p2">※誤通報防止の為通報ボタンを小さくしております。</div>
			<div class="delbox">
				<button type="button" id="delbtn" class="delbtn">通報</button>
			</div>
				<div id="myDelModal" class="modal">
					<div class="modal-content">
						<h1>最終確認</h1>
						<p>本当に通報しますか？<br>通報は取り消しできません。</p>
						<div class="btn_area">
							<input type="submit" id="deleteButton" class="fbtn_no" name="report" value="通報">
							<input type="button" id="cancelButton" class="fbtn" value="キャンセル">
						</div>
					</div>
				</div>
			</form>
		<?php }else{?>
			<div class="formarea">
				<h1>通報</h1>
				<p>申し訳ないのですが、お探しのユーザーはこのサーバーに存在しません。</p>
			</div>
		<?php }?>
	</main>



	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>

</body>

<script>
$(document).ready(function() {

	var modal = document.getElementById('myDelModal');
	var deleteButton = document.getElementById('deleteButton');
	var cancelButton = document.getElementById('cancelButton'); // 追加
	var modalMain = $('.modal-content');

	$(document).on('click', '.delbtn', function (event) {
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
});
</script>

</html>