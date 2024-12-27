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
		'expires' => time() + 60 * 60 * 24 * 28,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('username', $username,[
		'expires' => time() + 60 * 60 * 24 * 28,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('loginid', $res["loginid"],[
		'expires' => time() + 60 * 60 * 24 * 28,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('admin_login', true,[
		'expires' => time() + 60 * 60 * 24 * 28,
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
		'expires' => time() + 60 * 60 * 24 * 28,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('username', $username,[
		'expires' => time() + 60 * 60 * 24 * 28,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('loginid', $res["loginid"],[
		'expires' => time() + 60 * 60 * 24 * 28,
		'path' => '/',
		'samesite' => 'lax',
		'secure' => true,
		'httponly' => true,
	]);
	setcookie('admin_login', true,[
		'expires' => time() + 60 * 60 * 24 * 28,
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

if(empty($_SESSION["query_userid"])){
	header("Location: useradmin");
	exit;
}

$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

//phpmailer--------------------------------------------
require('plugin_settings/phpmailer_settings.php');
require('plugin_settings/phpmailer_sender.php');
//------------------------------------------------------

if (!empty($pdo)) {

	$userQuery = $pdo->prepare("SELECT * FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $_SESSION["query_userid"]);
	$userQuery->execute();
	$userdata = $userQuery->fetch();

	if(!(empty($userdata["encryption_ivkey"]))){
		$view_mailadds = DecryptionUseEncrKey($userdata["mailadds"], GenUserEnckey($userdata["datetime"]), $userdata["encryption_ivkey"]);
	}else{
		$view_mailadds = $userdata["mailadds"];
	}

	$roles = explode(',', $userdata["role"]);

	$roleDataArray = array();
			
	foreach ($roles as $roleId) {
		$rerole = $pdo->prepare("SELECT rolename, roleauth, rolecolor, roleeffect FROM role WHERE roleidname = :role");
		$rerole->bindValue(':role', $roleId);
		$rerole->execute();
		$roleDataArray[$roleId] = $rerole->fetch();
	}

	$followIds = explode(',', $userdata['follow']);
	$followCount = count($followIds)-1;

	$followerIds = explode(',', $userdata['follower']);
	$followerCount = count($followerIds)-1;

	$result = $pdo->prepare("SELECT ueuse FROM ueuse WHERE account = :userid ORDER BY datetime");
	$result->bindValue(':userid', $userdata["userid"]);
	$result->execute();
	$upload_cnt1 = $result->rowCount();

}
if( !empty($_POST['send_ice_submit']) ) {

	$notice_msg = $_POST['notice_msg'];

	$newrole = "ice";
	$newtoken = "ice";
	$newadmin = "none";
	// トランザクション開始
	$pdo->beginTransaction();

	try {
		$touserid = safetext($userdata['userid']);
		// SQL作成
		$stmt = $pdo->prepare("UPDATE account SET role = :role,token = :newtoken,admin = :newadmin WHERE userid = :userid");

		$stmt->bindValue(':role', $newrole, PDO::PARAM_STR);
		$stmt->bindValue(':newtoken', $newtoken, PDO::PARAM_STR);
		$stmt->bindValue(':newadmin', $newadmin, PDO::PARAM_STR);

		$stmt->bindValue(':userid', $touserid, PDO::PARAM_STR);

		// SQLクエリの実行
		$res = $stmt->execute();

		// コミット
		$res = $pdo->commit();


	} catch (Exception $e) {

		// エラーが発生した時はロールバック
		$pdo->rollBack();
		actionLog($userid, "error", "send_ice_submit", $touserid, $e, 4);
	}

	//凍結通知メール
	if(false !== strpos($userdata["mail_settings"], 'important')) {
		if(!empty(MAIL_CHKS)){
			if(MAIL_CHKS == "true"){
				if( !empty($view_mailadds) ){
					if(filter_var($view_mailadds, FILTER_VALIDATE_EMAIL)){
						$mail_title = "お使いの".safetext($serversettings["serverinfo"]["server_name"])."アカウントは凍結されました";
						$mail_text = "".$userdata["username"]."(".$userdata["userid"].")さん    いつもuwuzuをご利用いただきありがとうございます。  ご利用のアカウント(".$userdata["userid"].")が".safetext($serversettings["serverinfo"]["server_name"])."管理者により凍結されたためお知らせいたします。  サービス管理者からのメッセージは以下のものです。    ". $notice_msg ."    異議申し立てする場合は[".safetext($serversettings["serverinfo"]["server_admin_mailadds"])."]まで異議申し立てをする旨を記載し送信をしてください。";

						$error_message[] = send_html_mail($view_mailadds,$mail_title,$mail_text,"../");
					}
				}
			}
		}
	}
	//------------

	$pdo->beginTransaction();

		try {
			$touserid = safetext($userdata['userid']);
			$datetime = safetext(date("Y-m-d H:i:s"));
			$msg = safetext("サービス管理者からのメッセージは以下のものです。\n" . $notice_msg . "\n異議申し立てする場合は連絡用メールに異議申し立てをする旨を記載し送信をしてください。");
			$title = safetext("🧊お使いのアカウントは凍結されました。🧊");
			$url = safetext("/rule/serverabout");
			$userchk = 'none';
			$fromuserid ="uwuzu-fromsys";

			// 通知用SQL作成
			$stmt = $pdo->prepare("INSERT INTO notification (fromuserid, touserid, msg, url, datetime, userchk, title) VALUES (:fromuserid, :touserid, :msg, :url, :datetime, :userchk, :title)");

			$stmt->bindParam(':fromuserid', $fromuserid, PDO::PARAM_STR);
			$stmt->bindParam(':touserid', $touserid, PDO::PARAM_STR);
			$stmt->bindParam(':msg', $msg, PDO::PARAM_STR);
			$stmt->bindParam(':url', $url, PDO::PARAM_STR);
			$stmt->bindParam(':userchk', $userchk, PDO::PARAM_STR);
			$stmt->bindParam(':title', $title, PDO::PARAM_STR);

			$stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

			// SQLクエリの実行
			$res2 = $stmt->execute();

			// コミット
			$res2 = $pdo->commit();

		} catch(Exception $e) {

			// エラーが発生した時はロールバック
			$pdo->rollBack();
			actionLog($userid, "error", "send_ice_submit", $touserid, $e, 4);
		}

		if ($res) {
			actionLog($userid, "info", "send_ice_submit", $touserid, $touserid."さんを".$userid."さんが凍結しました", 0);
			header("Location:useradmin");
			exit; 
		} else {
			$error_message[] = '凍結に失敗しました。(USER_ICE_DAME)';
			actionLog($userid, "error", "send_ice_submit", $touserid, $error_message, 4);
		}
}
if( !empty($_POST['send_water_submit']) ) {

	$newrole = "user";
	$newtoken = "";
	$newadmin = "none";
	// トランザクション開始
	$pdo->beginTransaction();

	try {
		$touserid = safetext($userdata['userid']);
		// SQL作成
		$stmt = $pdo->prepare("UPDATE account SET role = :role,token = :newtoken,admin = :newadmin WHERE userid = :userid");

		$stmt->bindValue(':role', $newrole, PDO::PARAM_STR);
		$stmt->bindValue(':newtoken', $newtoken, PDO::PARAM_STR);
		$stmt->bindValue(':newadmin', $newadmin, PDO::PARAM_STR);

		$stmt->bindValue(':userid', $touserid, PDO::PARAM_STR);

		// SQLクエリの実行
		$res = $stmt->execute();

		// コミット
		$res = $pdo->commit();


	} catch (Exception $e) {
		$error_message[] = "えらー(ERROR)";
		// エラーが発生した時はロールバック
		$pdo->rollBack();
		actionLog($userid, "error", "send_water_submit", $touserid, $e, 4);
	}

	//凍結通知メール
	if(false !== strpos($userdata["mail_settings"], 'important')) {
		if(!empty(MAIL_CHKS)){
			if(MAIL_CHKS == "true"){
				if( !empty($view_mailadds) ){
					if(filter_var($view_mailadds, FILTER_VALIDATE_EMAIL)){
						$mail_title = "お使いの".safetext($serversettings["serverinfo"]["server_name"])."アカウントは解凍されました！";
						$mail_text = "".$userdata["username"]."(".$userdata["userid"].")さん    いつもuwuzuをご利用いただきありがとうございます。  ご利用のアカウント(".$userdata["userid"].")が解凍されたためお知らせいたします。  今後、ご利用のuwuzuアカウントは今まで通りご利用いただけます。  また、APIを使用している方はAPIのトークンがリセットされているため再度トークンを発行してご利用ください。";

						$error_message[] = send_html_mail($view_mailadds,$mail_title,$mail_text,"../");
					}
				}
			}
		}
	}
	//------------

	$pdo->beginTransaction();

		try {
			$touserid = safetext($userdata['userid']);
			$datetime = safetext(date("Y-m-d H:i:s"));
			$msg = safetext("サービス管理者によりお使いのアカウントは解凍されました！\n今まで通りご利用いただけます。\nなお、APIを使用している方はAPIのトークンがリセットされているため再度トークンを発行してご利用ください。");
			$title = safetext("🫗お使いのアカウントが解凍されました！🫗");
			$url = safetext("/home");
			$userchk = 'none';
			$fromuserid ="uwuzu-fromsys";

			// 通知用SQL作成
			$stmt = $pdo->prepare("INSERT INTO notification (fromuserid, touserid, msg, url, datetime, userchk, title) VALUES (:fromuserid, :touserid, :msg, :url, :datetime, :userchk, :title)");

			$stmt->bindParam(':fromuserid', $fromuserid, PDO::PARAM_STR);
			$stmt->bindParam(':touserid', $touserid, PDO::PARAM_STR);
			$stmt->bindParam(':msg', $msg, PDO::PARAM_STR);
			$stmt->bindParam(':url', $url, PDO::PARAM_STR);
			$stmt->bindParam(':userchk', $userchk, PDO::PARAM_STR);
			$stmt->bindParam(':title', $title, PDO::PARAM_STR);

			$stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

			// SQLクエリの実行
			$res2 = $stmt->execute();

			// コミット
			$res2 = $pdo->commit();

		} catch(Exception $e) {
			$error_message[] = "えらー(ERROR)";
			// エラーが発生した時はロールバック
			$pdo->rollBack();
			actionLog($userid, "error", "send_water_submit", $touserid, $e, 4);
		}

		if ($res) {
			actionLog($userid, "info", "send_water_submit", $touserid, $touserid."さんを".$userid."さんが解凍しました", 0);
			header("Location:useradmin");
			exit; 
		} else {
			$error_message[] = '解凍に失敗しました。(USER_WATER_DAME)';
			actionLog($userid, "error", "send_water_submit", $touserid, $error_message, 4);
		}

}


if( !empty($_POST['send_ban_submit']) ) {
	$userId2 = $userdata['userid']; // 削除対象のユーザーID
	$folderPath = "../ueuseimages/"; // フォルダのパス
	
	// 指定したフォルダ内でユーザーIDを含むファイルを検索
	$filesToDelete = glob($folderPath . "*-$userId2.*"); // 「-ユーザーID.拡張子」というパターンを検索
	
	// ファイルを順に削除
	foreach ($filesToDelete as $file) {
		if (is_file($file)) {
			unlink($file); // ファイルを削除
		}
	}
	
	$folderPath2 = "../ueusevideos/"; // フォルダのパス
	
	// 指定したフォルダ内でユーザーIDを含むファイルを検索
	$filesToDelete2 = glob($folderPath2 . "*-$userId2.*"); // 「-ユーザーID.拡張子」というパターンを検索
	
	// ファイルを順に削除
	foreach ($filesToDelete2 as $file2) {
		if (is_file($file2)) {
			unlink($file2); // ファイルを削除
		}
	}

	$folderPath3 = "../usericons/"; // フォルダのパス
	
	// 指定したフォルダ内でユーザーIDを含むファイルを検索
	$filesToDelete3 = glob($folderPath3 . "*-$userId2.*"); // 「-ユーザーID.拡張子」というパターンを検索
	
	// ファイルを順に削除
	foreach ($filesToDelete3 as $file3) {
		if (is_file($file3)) {
			unlink($file3); // ファイルを削除
		}
	}

	$folderPath4 = "../userheads/"; // フォルダのパス
	
	// 指定したフォルダ内でユーザーIDを含むファイルを検索
	$filesToDelete4 = glob($folderPath4 . "*-$userId2.*"); // 「-ユーザーID.拡張子」というパターンを検索
	
	// ファイルを順に削除
	foreach ($filesToDelete4 as $file4) {
		if (is_file($file4)) {
			unlink($file4); // ファイルを削除
		}
	}
	

	try {
		$pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS);

		// 投稿削除クエリを実行
		$deleteQuery = $pdo->prepare("DELETE FROM ueuse WHERE account = :userid");
		$deleteQuery->bindValue(':userid', $userId2, PDO::PARAM_STR);
		$res = $deleteQuery->execute();
		
		// アカウント削除クエリを実行
		$deleteQuery = $pdo->prepare("DELETE FROM account WHERE userid = :userid");
		$deleteQuery->bindValue(':userid', $userId2, PDO::PARAM_STR);
		$res = $deleteQuery->execute();

		// 通知削除クエリを実行
		$deleteQuery = $pdo->prepare("DELETE FROM notification WHERE touserid = :touserid");
		$deleteQuery->bindValue(':touserid', $userId2, PDO::PARAM_STR);
		$res = $deleteQuery->execute();

		// 通知削除クエリを実行(自分からの通知)
		$deleteQuery = $pdo->prepare("DELETE FROM notification WHERE fromuserid = :fromuserid");
		$deleteQuery->bindValue(':fromuserid', $userId2, PDO::PARAM_STR);
		$res = $deleteQuery->execute();

		// ユーザーIDを削除したい全てのアカウントを取得
		$query = $pdo->prepare("SELECT * FROM account WHERE follow LIKE :pattern1 OR follow LIKE :pattern2 OR follow LIKE :pattern3 OR follower LIKE :pattern1 OR follower LIKE :pattern2 OR follower LIKE :pattern3");
		$query->bindValue(':pattern1', "%,$userid,%", PDO::PARAM_STR);
		$query->bindValue(':pattern2', "%,$userid", PDO::PARAM_STR);
		$query->bindValue(':pattern3', "$userid,%", PDO::PARAM_STR);
		$query->execute();
		$accounts = $query->fetchAll();

		foreach ($accounts as $account) {
			// フォローの更新
			if (strpos($account['follow'], ",$userid,") !== false || strpos($account['follow'], ",$userid") !== false || strpos($account['follow'], "$userid,") !== false) {
				$followList = explode(',', $account['follow']);
				$followList = array_diff($followList, array($userid));
				$newFollowList = implode(',', $followList);

				$updateFollowQuery = $pdo->prepare("UPDATE account SET follow = :follow WHERE userid = :userid");
				$updateFollowQuery->bindValue(':follow', $newFollowList, PDO::PARAM_STR);
				$updateFollowQuery->bindValue(':userid', $account['userid'], PDO::PARAM_STR);
				$updateFollowQuery->execute();
			}

			// フォロワーの更新
			if (strpos($account['follower'], ",$userid,") !== false || strpos($account['follower'], ",$userid") !== false || strpos($account['follower'], "$userid,") !== false) {
				$followerList = explode(',', $account['follower']);
				$followerList = array_diff($followerList, array($userid));
				$newFollowerList = implode(',', $followerList);

				$updateFollowerQuery = $pdo->prepare("UPDATE account SET follower = :follower WHERE userid = :userid");
				$updateFollowerQuery->bindValue(':follower', $newFollowerList, PDO::PARAM_STR);
				$updateFollowerQuery->bindValue(':userid', $account['userid'], PDO::PARAM_STR);
				$updateFollowerQuery->execute();
			}
		}

		$query = $pdo->prepare("SELECT * FROM ueuse WHERE favorite LIKE :pattern1 OR favorite LIKE :pattern2 OR favorite LIKE :pattern3");
		$query->bindValue(':pattern1', "%,$userid,%", PDO::PARAM_STR);
		$query->bindValue(':pattern2', "%,$userid", PDO::PARAM_STR);
		$query->bindValue(':pattern3', "$userid,%", PDO::PARAM_STR);
		$query->execute();
		$accounts = $query->fetchAll();

		foreach ($accounts as $account) {
			// いいねの更新
			if (strpos($account['favorite'], ",$userid,") !== false || strpos($account['favorite'], ",$userid") !== false || strpos($account['favorite'], "$userid,") !== false) {
				$favoriteList = explode(',', $account['favorite']);
				$favoriteList = array_diff($favoriteList, array($userid));
				$newFavoriteList = implode(',', $favoriteList);

				$updateFavoriteQuery = $pdo->prepare("UPDATE ueuse SET favorite = :favorite WHERE uniqid = :uniqid");
				$updateFavoriteQuery->bindValue(':favorite', $newFavoriteList, PDO::PARAM_STR);
				$updateFavoriteQuery->bindValue(':uniqid', $account['uniqid'], PDO::PARAM_STR);
				$updateFavoriteQuery->execute();
			}
		}

		//BAN通知メール
		if(false !== strpos($userdata["mail_settings"], 'important')) {
			if(!empty(MAIL_CHKS)){
				if(MAIL_CHKS == "true"){
					if( !empty($view_mailadds) ){
						if(filter_var($view_mailadds, FILTER_VALIDATE_EMAIL)){
							$mail_title = "お使いの".safetext($serversettings["serverinfo"]["server_name"])."アカウントはBANされました";
							$mail_text = "".$userdata["username"]."(".$userdata["userid"].")さん    いつもuwuzuをご利用いただきありがとうございます。  この度、ご利用のアカウント(".$userdata["userid"].")が".safetext($serversettings["serverinfo"]["server_name"])."管理者によりBAN(削除)されたためお知らせいたします。  今後は今までご利用いただいた".safetext($serversettings["serverinfo"]["server_name"])."アカウントは利用できません。  ".safetext($serversettings["serverinfo"]["server_name"])."サーバー上から今までご利用いただいていたアカウントの情報は削除されたためログインなどもできません。    ご理解とご協力のほどよろしくお願いします。";

							$error_message[] = send_html_mail($view_mailadds,$mail_title,$mail_text,"../");
						}
					}
				}
			}
		}
		//------------

	} catch (Exception $e) {

		// エラーが発生した時はロールバック
		$pdo->rollBack();
		actionLog($userid, "error", "send_ban_submit", $userId2, $e, 4);
	}

	if ($res) {
		actionLog($userid, "info", "send_ban_submit", $userId2, $userid."さんが".$userId2."さんをBANしました", 4);
		header("Location:useradmin");
		exit; 
	} else {
		$error_message[] = 'アカウント削除に失敗しました。(ACCOUNT_DELETE_DAME)';
		actionLog($userid, "error", "send_ban_submit", $userId2, $error_message, 4);
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
			<div class="admin_userinfo">
				<div class="icon">
					<img src="<?php echo safetext('../'.$userdata['iconname']); ?>">
					<div class="tatext">
						<h2><?php echo safetext($userdata['username']); ?></h2>
						<p>@<?php echo safetext($userdata['userid']); ?></p>
					</div>
				</div>

				<div class="roleboxes">
					<?php foreach ($roles as $roleId): ?>
						<?php $roleData = $roleDataArray[$roleId]; ?>
						<?php 
							if(safetext($roleData["roleeffect"]) == '' || safetext($roleData["roleeffect"]) == 'none'){
								$role_view_effect = "";
							}elseif(safetext($roleData["roleeffect"]) == 'shine'){
								$role_view_effect = "shine";
							}elseif(safetext($roleData["roleeffect"]) == 'rainbow'){
								$role_view_effect = "rainbow";
							}else{
								$role_view_effect = "";
							}
						?>
						<div class="rolebox <?php echo safetext($role_view_effect); ?>" style="border: 1px solid <?php echo '#' . safetext($roleData["rolecolor"]); ?>;">
							<p style="color: <?php echo '#' . $roleData["rolecolor"]; ?>;">
								<?php if (!empty($roleData["rolename"])) { echo safetext($roleData["rolename"]); }else{ echo("ロールが正常に設定されていません。");} ?>
							</p>
						</div>
					<?php endforeach; ?>
				</div>

			
				<div class="profile">
					<div class="p2">プロフィール</div>
					<p><?php echo nl2br(safetext($userdata["profile"])); ?></p>
				</div>
				<hr>
				<div class="about">
					<div class="p2">メールアドレス</div>
					<p><?php if( !empty($view_mailadds) ){ echo safetext($view_mailadds); }else{echo "未設定";} ?></p>   
					<hr>
					<div class="p2">二段階認証</div>
					<p><?php if( !empty($userdata["authcode"]) ){ echo "設定済み";}else{echo "未設定";}  ?></p>
					<hr>
					<div class="p2">管理者権限</div>
					<p><?php if( !empty($userdata["admin"] === "yes") ){ echo "あり";}else{echo "なし";}  ?></p>
					<hr>
					<div class="p2">フォロー数</div>
					<p><?php if( $followCount > 0 ){ echo safetext($followCount);}else{echo "なし";}  ?></p>
					<div class="p2">フォロワー数</div>
					<p><?php if( $followerCount > 0 ){ echo safetext($followerCount);}else{echo "なし";}  ?></p>
					<hr>
					<div class="p2">投稿数</div>
					<p><?php if( $upload_cnt1 > 0 ){ echo $upload_cnt1;}else{echo "なし";}  ?></p>
					<hr>
					<div class="p2">アカウント登録日時</div>
					<p><?php echo safetext($userdata["datetime"]); ?></p>
					<hr>
					<div class="p2">アカウント操作</div>
					<div class="banzone">
						<?php if($roleId === "ice"){?>
							<button id="water" class="waterbtn">解凍</button>
						<?php }else{?>
							<button id="ice" class="icebtn">凍結</button>
						<?php }?>
						<button id="ban" class="banbtn">BAN</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div id="account_IceModal" class="modal">
		<div class="modal-content">
			<h1>このアカウントを凍結しますか？</h1>
			<p><?php echo safetext($userdata['username']); ?>さんのアカウントを凍結しますか？<br>凍結すると<?php echo safetext($userdata['username']); ?>さんは以下のことができなくなります。<br>・投稿<br>・追記<br>・フォロー<br>・返信<br>・管理者権限の利用(管理者権限を持っていた場合)<br>・APIの使用(トークンがリセットされます)<br>また、以下の欄に入力した内容が個別メッセージとして通知欄に表示されます。<br><?php echo safetext($userdata['username']); ?>さんは異議申し立てが可能です。</p>
			<form method="post" id="deleteForm">
			<textarea id="notice_msg" placeholder="<?php echo safetext($userdata['username']); ?>さんへのメッセージ" name="notice_msg"></textarea>
				<div class="btn_area">
					<input type="submit" id="deleteButton" class="fbtn_no" name="send_ice_submit" value="凍結">
					<input type="button" id="cancelButton" class="fbtn" value="キャンセル">
				</div>
			</form>
		</div>
	</div>

	<div id="account_BanModal" class="modal">
		<div class="modal-content">
			<h1>このアカウントをBANしますか？</h1>
			<p><?php echo safetext($userdata['username']); ?>さんのアカウントをBANしますか？<br>BANすると<?php echo safetext($userdata['username']); ?>さんのアカウントとは削除されます。<br>また、以下のデータも削除されます。<br>・アカウントに紐づいている画像や写真データ<br>・投稿<br>・フォロー情報やいいね情報<br>・APIトークン<br>・アカウントのメールアドレス<br>・その他アカウントに関連している情報<br>また、すぐに削除されるため本人に削除通知を送ることは出来ません。<br><?php echo safetext($userdata['username']); ?>さんのアカウントを削除する場合は「BAN」を押してください。<br>アカウントの復旧は出来ません。</p>
			<form class="btn_area" method="post" id="deleteForm">
				<input type="submit" id="deleteButton2" class="fbtn_no" name="send_ban_submit" value="BAN">
				<input type="button" id="cancelButton2" class="fbtn" value="キャンセル">
			</form>
		</div>
	</div>	
	
	<div id="account_WaterModal" class="modal">
		<div class="modal-content">
			<h1>このアカウントを解凍しますか？</h1>
			<p><?php echo safetext($userdata['username']); ?>さんのアカウントを解凍しますか？<br>凍結すると<?php echo safetext($userdata['username']); ?>さんは今まで通りアカウントを使用できます。</p>
			<form method="post" id="deleteForm">
				<div class="btn_area">
					<input type="submit" id="deleteButton3" class="fbtn_no" name="send_water_submit" value="解凍">
					<input type="button" id="cancelButton3" class="fbtn" value="キャンセル">
				</div>
			</form>
		</div>
	</div>

	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>

</body>
<script>
$(document).ready(function() {

	var modal3 = document.getElementById('account_WaterModal');
    var deleteButton3 = document.getElementById('deleteButton3');
    var cancelButton3 = document.getElementById('cancelButton3'); // 追加
	var modalMain = $('.modal-content');

    $(document).on('click', '.waterbtn', function (event) {
        modal3.style.display = 'block';
		modalMain.addClass("slideUp");
    	modalMain.removeClass("slideDown");

        deleteButton3.addEventListener('click', () => {
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal3.style.display = 'none';
			}, 150);
        });

        cancelButton3.addEventListener('click', () => { // 追加
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal3.style.display = 'none';
			}, 150);
        });
    });


	var modal = document.getElementById('account_IceModal');
    var deleteButton = document.getElementById('deleteButton');
    var cancelButton = document.getElementById('cancelButton'); // 追加
	var modalMain = $('.modal-content');

    $(document).on('click', '.icebtn', function (event) {
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

	var modal2 = document.getElementById('account_BanModal');
    var deleteButton2 = document.getElementById('deleteButton2');
    var cancelButton2 = document.getElementById('cancelButton2'); // 追加
	var modalMain = $('.modal-content');

    $(document).on('click', '.banbtn', function (event) {
        modal2.style.display = 'block';
		modalMain.addClass("slideUp");
    	modalMain.removeClass("slideDown");

        deleteButton2.addEventListener('click', () => {
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal2.style.display = 'none';
			}, 150);
        });

        cancelButton2.addEventListener('click', () => { // 追加
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal2.style.display = 'none';
			}, 150);
        });
    });
});
</script>
</html>