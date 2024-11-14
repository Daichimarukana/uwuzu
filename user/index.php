<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);


$domain = $_SERVER['HTTP_HOST'];

require('../db.php');
//関数呼び出し
//- 文字装飾・URL変換など
require('../function/function.php');

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
	$pdo = new PDO('mysql:charset=utf8mb4;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);
} catch (PDOException $e) {

	// 接続エラーのときエラー内容を取得する
	$error_message[] = $e->getMessage();
}

if (isset($_SESSION['admin_login']) && $_SESSION['admin_login'] == true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', safetext($_SESSION['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if (empty($res["userid"])) {
		header("Location: ../login.php");
		exit;
	} elseif ($_SESSION['loginid'] === $res["loginid"] && $_SESSION['userid'] == $res["userid"]) {
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
		setcookie('username', $username, [
			'expires' => time() + 60 * 60 * 24 * 14,
			'path' => '/',
			'samesite' => 'lax',
			'secure' => true,
			'httponly' => true,
		]);
		setcookie('loginid', $res["loginid"], [
			'expires' => time() + 60 * 60 * 24 * 14,
			'path' => '/',
			'samesite' => 'lax',
			'secure' => true,
			'httponly' => true,
		]);
		setcookie('admin_login', true, [
			'expires' => time() + 60 * 60 * 24 * 14,
			'path' => '/',
			'samesite' => 'lax',
			'secure' => true,
			'httponly' => true,
		]);
	} else {
		header("Location: ../login.php");
		exit;
	}
} elseif (isset($_COOKIE['admin_login']) && $_COOKIE['admin_login'] == true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', safetext($_COOKIE['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if (empty($res["userid"])) {
		header("Location: ../login.php");
		exit;
	} elseif ($_COOKIE['loginid'] === $res["loginid"] && $_COOKIE['userid'] == $res["userid"]) {
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
		setcookie('userid', $userid, [
			'expires' => time() + 60 * 60 * 24 * 14,
			'path' => '/',
			'samesite' => 'lax',
			'secure' => true,
			'httponly' => true,
		]);
		setcookie('username', $username, [
			'expires' => time() + 60 * 60 * 24 * 14,
			'path' => '/',
			'samesite' => 'lax',
			'secure' => true,
			'httponly' => true,
		]);
		setcookie('loginid', $res["loginid"], [
			'expires' => time() + 60 * 60 * 24 * 14,
			'path' => '/',
			'samesite' => 'lax',
			'secure' => true,
			'httponly' => true,
		]);
		setcookie('admin_login', true, [
			'expires' => time() + 60 * 60 * 24 * 14,
			'path' => '/',
			'samesite' => 'lax',
			'secure' => true,
			'httponly' => true,
		]);
	} else {
		header("Location: ../login.php");
		exit;
	}
} else {
	// ログインが許可されていない場合、ログインページにリダイレクト
	header("Location: ../login.php");
	exit;
}
if (empty($userid)) {
	header("Location: ../login.php");
	exit;
}
if (empty($username)) {
	header("Location: ../login.php");
	exit;
}
$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

function customStripTags($html, $allowedTags)
{
	$allowedTagsString = '<' . implode('><', $allowedTags) . '>';
	return strip_tags($html, $allowedTagsString);
}

$allowedTags = array('h1', 'h2', 'h3', 'center', 'font');

if (!empty($pdo)) {
	$uwuzuid2 = safetext(str_replace('@', '', $_GET['uwuzuid']));

	$uwuzuid = safetext(str_replace('@' . $domain, '', $uwuzuid2));

	$userQuery = $pdo->prepare("SELECT username, userid, profile, role, follower, blocklist FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $uwuzuid);
	$userQuery->execute();
	$userData = $userQuery->fetch();


	if (!empty($userData["userid"])) {


		$roles = explode(',', $userData["role"]); // カンマで区切られたロールを配列に分割

		$rerole = $pdo->prepare("SELECT  follow, follower,blocklist, username, userid, password, mailadds, profile, iconname, headname, role, datetime, other_settings FROM account WHERE userid = :userid");

		$rerole->bindValue(':userid', $uwuzuid);
		// SQL実行
		$rerole->execute();

		$userdata = $rerole->fetch(); // ここでデータベースから取得した値を $role に代入する

		$roleDataArray = array();

		foreach ($roles as $roleId) {
			$rerole = $pdo->prepare("SELECT rolename, roleauth, rolecolor, roleeffect FROM role WHERE roleidname = :role");
			$rerole->bindValue(':role', $roleId);
			$rerole->execute();
			$roleDataArray[$roleId] = $rerole->fetch();
		}

		$isAIBlock = val_OtherSettings("isAIBlock", $userdata["other_settings"]);

		//-------フォロー数---------
		$follow = $userdata['follow']; // コンマで区切られたユーザーIDを含む変数

		// コンマで区切って配列に分割し、要素数を数える
		$followIds = explode(',', $follow);
		$followCount = count($followIds) - 1;

		$follow_on_me = array_search($userid, $followIds);

		if ($follow_on_me !== false) {
			$follow_yes = "フォローされています"; // worldを含む:6
		} else {
			$follow_yes = ""; // worldを含む:6
		}

		//-------フォロワー数---------
		$follower = $userdata['follower']; // コンマで区切られたユーザーIDを含む変数

		// コンマで区切って配列に分割し、要素数を数える
		$followerIds = explode(',', $follower);
		$followerCount = count($followerIds) - 1;

		$profileText = safetext($userData['profile']);


		$allueuse = $pdo->prepare("SELECT account FROM ueuse WHERE account = :userid");
		$allueuse->bindValue(':userid', $uwuzuid);
		$allueuse->execute();
		$ueuse_cnt = $allueuse->rowCount();

		//-------フォロワー取得---------

		// フォロワーのユーザーIDを $follower_userids 配列に追加
		foreach ($followerIds as $follower_userid) {
			$follower_userids[] = $follower_userid;
		}

		// フォロワーのユーザー情報を取得
		$follower_userdata = array();

		foreach ($follower_userids as $follower_userid) {
			$follower_userQuery = $pdo->prepare("SELECT username, userid, iconname, headname, sacinfo FROM account WHERE userid = :userid");
			$follower_userQuery->bindValue(':userid', $follower_userid);
			$follower_userQuery->execute();
			$follower_userinfo = $follower_userQuery->fetch();

			if ($follower_userinfo) {
				// フォロワーのユーザー情報を $follower_userdata 配列に追加
				$follower_userdata[] = $follower_userinfo;
			}
		}

		//-------フォロー取得---------

		foreach ($followIds as $follow_userid) {
			$follow_userids[] = $follow_userid;
		}

		$follow_userdata = array();

		foreach ($follow_userids as $follow_userid) {
			$follow_userQuery = $pdo->prepare("SELECT username, userid, iconname, headname, sacinfo FROM account WHERE userid = :userid");
			$follow_userQuery->bindValue(':userid', $follow_userid);
			$follow_userQuery->execute();
			$follow_userinfo = $follow_userQuery->fetch();

			if ($follow_userinfo) {
				// フォロワーのユーザー情報を $follower_userdata 配列に追加
				$follow_userdata[] = $follow_userinfo;
			}
		}
	} else {
		$userData["userid"] = "none";
		$userData['username'] = "でふぉると";

		$ueuse_cnt = "zero";
		$followCount = "zero";
		$followerCount = "zero";
	}
}

if (!empty($_POST['follow'])) {
	// トランザクションを開始
	$pdo->beginTransaction();
	try {
		// フォローボタンが押された場合の処理
		$followerList = explode(',', $userdata['follower']);
		if (!(in_array($userid, $followerList))) {
			// 自分が相手をフォローしていない場合、相手のfollowerカラムと自分のfollowカラムを更新
			$followerList[] = $userid;
			$newFollowerList = implode(',', $followerList);

			// UPDATE文を実行してフォロー情報を更新
			$updateQuery = $pdo->prepare("UPDATE account SET follower = :follower WHERE userid = :userid");
			$updateQuery->bindValue(':follower', $newFollowerList, PDO::PARAM_STR);
			$updateQuery->bindValue(':userid', $userData['userid'], PDO::PARAM_STR);
			$res = $updateQuery->execute();

			// 自分のfollowカラムを更新
			$myflwlist = explode(',', $myfollowlist);
			$myflwlist[] = $userData['userid'];
			$newFollowList = implode(',', array_unique($myflwlist));

			$updateQuery = $pdo->prepare("UPDATE account SET follow = :follow WHERE userid = :userid");
			$updateQuery->bindValue(':follow', $newFollowList, PDO::PARAM_STR);
			$updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
			$res_follow = $updateQuery->execute();

			send_notification($userData["userid"], $userid, "🎉" . $userid . "さんにフォローされました！🎉", "" . $userid . "さんにフォローされました。", "/@" . $userid . "", "follow");

			if ($res && $res_follow) {
				$pdo->commit();
				$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header("Location:" . $url);
				exit;
			} else {
				$pdo->rollBack();
				$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
			}
		}
	} catch (Exception $e) {
		// ロールバック
		$pdo->rollBack();
		$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
	}
} elseif (!empty($_POST['unfollow'])) {
	// トランザクションを開始
	$pdo->beginTransaction();
	try {
		// フォロー解除ボタンが押された場合の処理
		$followerList = explode(',', $userdata['follower']);
		if (in_array($userid, $followerList)) {
			// 自分が相手をフォローしている場合、相手のfollowerカラムと自分のfollowカラムを更新
			$followerList = array_diff($followerList, array($userid));
			$newFollowerList = implode(',', $followerList);

			// UPDATE文を実行してフォロー情報を更新
			$updateQuery = $pdo->prepare("UPDATE account SET follower = :follower WHERE userid = :userid");
			$updateQuery->bindValue(':follower', $newFollowerList, PDO::PARAM_STR);
			$updateQuery->bindValue(':userid', $userData['userid'], PDO::PARAM_STR);
			$res = $updateQuery->execute();

			$myflwlist = explode(',', $myfollowlist);
			$delfollowList = array_diff($myflwlist, array($userData['userid']));
			$deluserid = implode(',', $delfollowList);

			// 自分のfollowカラムから相手のユーザーIDを削除
			$updateQuery = $pdo->prepare("UPDATE account SET follow = :follow WHERE userid = :userid");
			$updateQuery->bindValue(':follow', $deluserid, PDO::PARAM_STR);
			$updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
			$res_follow = $updateQuery->execute();

			if ($res && $res_follow) {
				// コミット
				$pdo->commit();

				// リダイレクト
				$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header("Location:" . $url);
				exit;
			} else {
				// ロールバック
				$pdo->rollBack();
				$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
			}

			$stmt = null;
		}
	} catch (Exception $e) {
		// ロールバック
		$pdo->rollBack();
		$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
	}
}


if (!empty($_POST['send_block_submit'])) {

	$pdo->beginTransaction();
	try {
		$updateQuery = $pdo->prepare("UPDATE account SET blocklist = CONCAT_WS(',', blocklist, :blocklist) WHERE userid = :userid");
		$updateQuery->bindValue(':blocklist', $userData["userid"], PDO::PARAM_STR);
		$updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
		$res_block = $updateQuery->execute();

		// フォロー解除ボタンが押された場合の処理
		$followerList = explode(',', $userdata['follower']);
		if (in_array($userid, $followerList)) {
			// 自分が相手をフォローしている場合、相手のfollowerカラムと自分のfollowカラムを更新
			$followerList = array_diff($followerList, array($userid));
			$newFollowerList = implode(',', $followerList);

			// UPDATE文を実行してフォロー情報を更新
			$updateQuery = $pdo->prepare("UPDATE account SET follower = :follower WHERE userid = :userid");
			$updateQuery->bindValue(':follower', $newFollowerList, PDO::PARAM_STR);
			$updateQuery->bindValue(':userid', $userData['userid'], PDO::PARAM_STR);
			$res = $updateQuery->execute();

			$myflwlist = explode(',', $myfollowlist);
			$delfollowList = array_diff($myflwlist, array($userData['userid']));
			$deluserid = implode(',', $delfollowList);
			// 自分のfollowカラムから相手のユーザーIDを削除
			$updateQuery = $pdo->prepare("UPDATE account SET follow = :follow WHERE userid = :userid");
			$updateQuery->bindValue(':follow', $deluserid, PDO::PARAM_STR);
			$updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
			$res_follow = $updateQuery->execute();

			if ($res && $res_follow) {
				$pdo->commit();
				$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header("Location:" . $url);
				exit;
			} else {
				$pdo->rollBack();
				$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
			}

			$stmt = null;
		}

		if ($res_block) {
			$pdo->commit();
			$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			header("Location:" . $url);
			exit;
		} else {
			$pdo->rollBack();
			$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
		}
	} catch (Exception $e) {
		// ロールバック
		$pdo->rollBack();
		$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
	}
} elseif (!empty($_POST['send_un_block_submit'])) {
	$pdo->beginTransaction();
	try {
		$myblklist = explode(',', $myblocklist);
		$delblkList = array_diff($myblklist, array($userData['userid']));
		$deluserid = implode(',', $delblkList);
		// 自分のfollowカラムから相手のユーザーIDを削除
		$updateQuery = $pdo->prepare("UPDATE account SET blocklist = :blocklist WHERE userid = :userid");
		$updateQuery->bindValue(':blocklist', $deluserid, PDO::PARAM_STR);
		$updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
		$res_block = $updateQuery->execute();

		if ($res_block) {
			$pdo->commit();
			$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			header("Location:" . $url);
			exit;
		} else {
			$pdo->rollBack();
			$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
		}
	} catch (Exception $e) {
		// ロールバック
		$pdo->rollBack();
		$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
	}
}



require('../logout/logout.php');



// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">

<head>
	<script src="//cdnjs.cloudflare.com/ajax/libs/push.js/1.0.12/push.min.js"></script>
	<script src="../js/jquery-min.js"></script>
	<script src="../js/unsupported.js"></script>
	<script src="../js/console_notice.js"></script>
	<script src="../js/nsfw_event.js"></script>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<?php if($isAIBlock === true){?>
		<meta name="robots" content="noimageai">
		<meta name="robots" content="noai">
	<?php }?>
	<link rel="stylesheet" href="../css/home.css">
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
	<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
	<title><?php echo safetext($userData['username']); ?> さんのプロフィール - <?php echo safetext($serversettings["serverinfo"]["server_name"]); ?></title>

</head>

<body>

	<div>
		<div id="new_ueuse" class="new_ueuse" style="display:none;">
			<a onclick="window.location.reload(true);"><p>🍊新しいユーズがあります！</p></a>
		</div>
		<div id="notify" class="new_ueuse" style="display:none;">
			<p>お知らせです</p>
		</div>
		<div id="clipboard" class="online" style="display:none;">
			<p>🗒️📎 ユーズのURLをコピーしました！</p>
		</div>
		<div id="offline" class="offline" style="display:none;">
			<p>🦖💨 インターネットへの接続が切断されました...</p>
		</div>
		<div id="online" class="online" style="display:none;">
			<p>🌐💫 インターネットへの接続が復帰しました！！！</p>
		</div>
	</div>

	<?php require('../require/leftbox.php'); ?>
	<main class="outer">

		<?php if (!empty($error_message)) : ?>
			<ul class="errmsg">
				<?php foreach ($error_message as $value) : ?>
					<p>・ <?php echo $value; ?></p>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<div class="userheader">
			<?php if ($userData["userid"] == "none") {
				header("HTTP/1.1 404 Not Found"); ?>
				<!--いないひと--->
				<div class="hed">
					<img src="../img/defhead/head.png">
				</div>
				<div class="icon">
					<img src="../img/deficon/icon.png">
					<h2>でふぉると</h2>
					<p>@none</p>
				</div>
				<div class="roleboxes">
					<div class="rolebox" style="border: 1px solid #252525;">
						<p style="color: #252525;">
							つよいひと
						</p>
					</div>
				</div>
				<div class="profile">
					<p>残念だがそのユーザーはいない。このサーバーには...</p>
				</div>
		</div>
		<div class="fzone">
			<div class="time">
				<p>紀元前3000年からuwuzuを利用していま<b>せん！！！</b></p>
			</div>
		</div>
		<!--ここまで！--->
	<?php } else { ?>
		<div class="hed">
			<img src="<?php echo safetext('../' . $userdata['headname']); ?>">
		</div>
		<div class="icon">
			<img src="<?php echo safetext('../' . $userdata['iconname']); ?>">
			<h2><?php echo replaceProfileEmojiImages(safetext($userData['username'])); ?></h2>
			<p>@<?php echo safetext($userData['userid']); ?><!--<span>@<?php /*echo safetext($domain); */ ?></span>--></p>
		</div>

		<div class="roleboxes">
			<?php foreach ($roles as $roleId) : ?>
				<?php $roleData = $roleDataArray[$roleId]; ?>
				<?php
					if (safetext($roleData["roleeffect"]) == '' || safetext($roleData["roleeffect"]) == 'none') {
						$role_view_effect = "";
					} elseif (safetext($roleData["roleeffect"]) == 'shine') {
						$role_view_effect = "shine";
					} elseif (safetext($roleData["roleeffect"]) == 'rainbow') {
						$role_view_effect = "rainbow";
					} else {
						$role_view_effect = "";
					}
				?>
				<div class="rolebox <?php echo safetext($role_view_effect); ?>" style="border: 1px solid <?php echo '#' . safetext($roleData["rolecolor"]); ?>;">
					<p style="color: <?php echo '#' . $roleData["rolecolor"]; ?>;">
						<?php if (!empty($roleData["rolename"])) {
							echo safetext($roleData["rolename"]);
						} else {
							echo ("ロールが正常に設定されていません。");
						} ?>
					</p>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if (!(in_array(safetext($userData['userid']), explode(",", $myblocklist)))) { ?>
			<div class="profile">
				<p><?php echo replaceEmojisWithImages(replaceURLsWithLinks(nl2br(safetext($profileText)))); ?></p>
			</div>
		<?php } else { ?>
			<div class="profile">
				<p>ブロックしているためプロフィールは表示されません。</p>
			</div>
		<?php } ?>

		</div>
		<div class="fzone">
			<div class="time">
				<p><?php echo date('Y年m月d日 H:i:s', strtotime($userdata['datetime'])); ?>からuwuzuを利用しています。</p>
				<p><?php if (safetext($userdata['role']) === "ice") {
						echo "このアカウントは凍結されています。";
					}; ?></p>
			</div>

			<?php if (!empty($follow_yes)) { ?>
				<div class="follow_yes">
					<p><?php echo $follow_yes; ?></p>
				</div>
			<?php } ?>

			<?php if ($userid !== safetext($userData['userid'])) { ?>
				<?php if (in_array(safetext($userData['userid']), explode(",", $myblocklist))) { ?>
					<div class="follow">
						<a id="un_block" href="javascript:void(0);" class="report" title="ブロック解除"><svg>
								<use xlink:href="../img/sysimage/unblock_1.svg#block"></use>
							</svg></a>
					</div>
				<?php } else { ?>
					<div class="follow">
						<a id="block" href="javascript:void(0);" class="report" title="ブロック"><svg>
								<use xlink:href="../img/sysimage/block_1.svg#block"></use>
							</svg></a>
					</div>
				<?php } ?>
			<?php } ?>

			<div class="follow">
				<a href="/user/report?q=<?php echo safetext($userData['userid']); ?>" class="report" title="通報"><svg>
						<use xlink:href="../img/sysimage/report_1.svg#report"></use>
					</svg></a>
			</div>
			<?php if ($userData['userid'] == $userid) { ?>
				<div class="follow">
					<a href="../settings/" class="fbtn_no" title="設定">設定</a>
				</div>
			<?php } else { ?>

				<?php if (!(in_array(safetext($userData['userid']), explode(",", $myblocklist)))) { ?>
					<form method="post">
						<div class="follow">
							<?php
							if (!($role === "ice")) {
								$followerList = explode(',', $userdata['follower']);
								if (in_array($userid, $followerList)) {
									// フォロー済みの場合はフォロー解除ボタンを表示
									echo '<input type="button" id="openModalButton" class="fbtn_un" name="unfollow" value="フォロー解除">';
								} else {
									// 未フォローの場合はフォローボタンを表示
									echo '<input type="submit" class="fbtn" name="follow" value="フォロー">';
								}
							}
							?>
						</div>
					</form>
				<?php } ?>
			<?php } ?>
		</div>

		<div class="sp_time_area">
			<div class="time">
				<p><?php echo date('Y年m月d日 H:i:s', strtotime($userdata['datetime'])); ?>からuwuzuを利用しています。</p>
				<p><?php if (safetext($userdata['role']) === "ice") {
						echo "このアカウントは凍結されています。";
					}; ?></p>
			</div>
		</div>
	<?php } ?>

		<?php if (!($role === "ice")) { ?>
			<div id="myModal" class="modal">
				<div class="modal-content">
					<p><?php echo replaceProfileEmojiImages(safetext($userData['username'])); ?>さんをフォロー解除しますか？</p>
					<form class="btn_area" method="post">
						<input type="submit" id="openModalButton" class="fbtn" name="unfollow" value="フォロー解除">
						<input type="button" id="closeModal" class="fbtn_no" value="キャンセル">
					</form>
				</div>
			</div>
		<?php } ?>
		<hr>
		<div class="f_c_area">
			<div class="fcnt">
				<div class="p2">ユーズ数</div>
				<p><?php echo $ueuse_cnt; ?></p>
			</div>
			<div class="fcnt" id="follow_cnt" style="cursor:pointer;">
				<div class="p2">フォロー数</div>
				<p><?php echo $followCount; ?></p>
			</div>
			<div class="fcnt" id="follower_cnt" style="cursor:pointer;">
				<div class="p2">フォロワー数</div>
				<p><?php echo $followerCount; ?></p>
			</div>
		</div>
		<hr>
		<div class="select_utl">
			<button class="btn" id="all_ueuse_btn">ユーズ</button>
			<button class="btn" id="media_ueuse_btn">メディア</button>
			<button class="btn" id="like_ueuse_btn">いいね</button>
		</div>

		<hr>
		<?php if (!(in_array(safetext($userData['userid']), explode(",", $myblocklist)))) { ?>
			<section class="inner">
				<div id="postContainer">

				</div>
			</section>

			<div id="loading" class="loading" style="display: none;">
				🤔
			</div>
		<?php } else { ?>
			<div class="tokonone" id="noueuse">
				<p><?php echo safetext($userData['username']); ?>さんをブロックしているため投稿の閲覧は出来ません。</p>
			</div>
		<?php } ?>

		<div id="error" class="error" style="display: none;">
			<h1>エラー</h1>
			<p>サーバーの応答がなかったか不完全だったようです。<br>ネットワークの接続が正常かを確認の上再読み込みしてください。<br>(NETWORK_HUKANZEN_STOP)</p>
		</div>

		<div id="myDelModal" class="modal">
			<div class="modal-content">
				<p>ユーズを削除しますか？</p>
				<form class="btn_area" method="post" id="deleteForm">
					<input type="button" id="deleteButton" class="fbtn_no" name="delete" value="削除">
					<input type="button" id="cancelButton" class="fbtn" value="キャンセル">
				</form>
			</div>
		</div>

		<div id="myAbiModal" class="modal">
			<div class="modal-content">
				<p>ユーズに追記しますか？</p>
				<p>※追記は削除出来ません。</p>
				<form method="post" id="AbiForm">
					<textarea id="abitexts" placeholder="なに追記する～？" name="abi"><?php if (!empty($_SESSION['abi'])) {
																					echo safetext($_SESSION['abi']);
																				} ?></textarea>
					<div class="btn_area">
						<input type="submit" id="AbiAddButton" class="fbtn_no" name="abi" value="追記">
						<input type="button" id="AbiCancelButton" class="fbtn" value="キャンセル">
					</div>
				</form>
			</div>
		</div>

		<div id="account_BlockModal" class="modal">
			<div class="modal-content">
				<h1><?php echo replaceProfileEmojiImages(safetext($userdata['username'])); ?>さんをブロックしますか？</h1>
				<p><?php echo replaceProfileEmojiImages(safetext($userdata['username'])); ?>さんのアカウントをブロックしますか？<br>ブロックするとフォローが解除され、検索以外のLTL、FTL等で<?php echo safetext($userdata['username']); ?>さんの投稿が表示されなくなります。<br>※ブロックしたことは相手には通知されません。<br><br>ブロックを解除するときはこのアカウントのユーザーページ(このページ)から解除を行ってください。</p>
				<form class="btn_area" method="post">
					<input type="submit" id="deleteButton2" class="fbtn_no" name="send_block_submit" value="ブロック">
					<input type="button" id="cancelButton2" class="fbtn" value="キャンセル">
				</form>
			</div>
		</div>

		<div id="account_un_BlockModal" class="modal">
			<div class="modal-content">
				<h1><?php echo replaceProfileEmojiImages(safetext($userdata['username'])); ?>さんのブロックを解除しますか？</h1>
				<p><?php echo replaceProfileEmojiImages(safetext($userdata['username'])); ?>さんのアカウントをブロック解除しますか？<br>ブロック解除すると<?php echo safetext($userdata['username']); ?>さんの投稿の閲覧が可能になりフォローすることもできるようになります。</p>
				<form class="btn_area" method="post">
					<input type="submit" id="deleteButton3" class="fbtn_no" name="send_un_block_submit" value="ブロック解除">
					<input type="button" id="cancelButton3" class="fbtn" value="キャンセル">
				</form>
			</div>
		</div>


		<div id="FollowerUserModal" class="modal">
			<div class="modal-content">
				<p><?php echo replaceProfileEmojiImages(safetext($userData["username"])); ?>さんをフォローしているユーザー</p>
				<?php
				if (!empty($follower_userdata)) {
					foreach ($follower_userdata as $value) {
						if (!(in_array(safetext($value['userid']), explode(",", $myblocklist)))) {
							echo "<div class='action_userlist'>";
							echo "<a href='/@" . safetext($value['userid']) . "'><img src=" . safetext($value['iconname']) . "></a>";
							echo "<div class='userabout'>";
							echo "<div class='username'><a href='/@" . safetext($value['userid']) . "'>" . replaceEmojisWithImages(safetext($value['username'])) . "</a></div>";
							echo "<div class='userid'><a href='/@" . safetext($value['userid']) . "'>@" . safetext($value['userid']) . "</a></div>";
							echo "</div>";
							echo "</div>";
						}
					}
				} else {
					echo "<p>" . replaceProfileEmojiImages(safetext($userData["username"])) . "さんは誰にもフォローされていません。</p>";
				}
				?>
				<div class="btn_area">
					<input type="button" id="CloseButton4" class="fbtn" value="閉じる">
				</div>
			</div>
		</div>

		<div id="FollowUserModal" class="modal">
			<div class="modal-content">
				<p><?php echo replaceProfileEmojiImages(safetext($userData["username"])); ?>さんがフォローしているユーザー</p>
				<?php
				if (!empty($follow_userdata)) {
					foreach ($follow_userdata as $value) {
						if (!(in_array(safetext($value['userid']), explode(",", $myblocklist)))) {
							echo "<div class='action_userlist'>";
							echo "<a href='/@" . safetext($value['userid']) . "'><img src=" . safetext($value['iconname']) . "></a>";
							echo "<div class='userabout'>";
							echo "<div class='username'><a href='/@" . safetext($value['userid']) . "'>" . replaceEmojisWithImages(safetext($value['username'])) . "</a></div>";
							echo "<div class='userid'><a href='/@" . safetext($value['userid']) . "'>@" . safetext($value['userid']) . "</a></div>";
							echo "</div>";
							echo "</div>";
						}
					}
				} else {
					echo "<p>" . replaceProfileEmojiImages(safetext($userData["username"])) . "さんは誰もフォローしていません。</p>";
				}
				?>
				<div class="btn_area">
					<input type="button" id="CloseButton5" class="fbtn" value="閉じる">
				</div>
			</div>
		</div>

		<div id="myQuoteReuseModal" class="modal">
			<div class="modal-content">
				<h1>引用リユーズ</h1>
				<p></p>
				<textarea id="reusetexts" placeholder="引用を追加" name="reuse"></textarea>
				<div class="btn_area">
					<input type="button" id="ReuseButton" class="fbtn_no" name="abi" value="リユーズ">
					<input type="button" id="ReuseCancelButton" class="fbtn" value="キャンセル">
				</div>
			</div>
		</div>

		<div id="Big_ImageModal" class="Image_modal">
			<div class="modal-content">
				<img id="Big_ImageMain" href="">
			</div>
		</div>

		<div id="ueuse_popup_back" class="ueuse_popup_back" style="display: none;">
			<div id="ueuse_popup" class="ueuse_popup_menu" style="display: none;">
				<button name="share" id="share" class="popbtn"><svg><use xlink:href="../img/sysimage/share_1.svg#share_1"></use></svg><span>シェア</span></button>
				<button name="delete" id="delete" class="popbtn delbtn"><svg><use xlink:href="../img/sysimage/delete_1.svg#delete"></use></svg><span>削除</span></button>
			</div>

			<div id="reuse_popup" class="ueuse_popup_menu" style="display: none;">
				<button name="normal_reuse_btn" id="normal_reuse_btn" class="popbtn"><svg><use xlink:href="../img/sysimage/reuse_1.svg#reuse_1"></use></svg><span>リユーズ</span></button>
				<button name="quote_reuse_btn" id="quote_reuse_btn" class="popbtn"><svg><use xlink:href="../img/sysimage/quote_1.svg#quote_1"></use></svg><span>引用</span></button>
				<button name="delete_reuse_btn" id="delete_reuse_btn" class="popbtn delbtn"><svg><use xlink:href="../img/sysimage/delete_1.svg#delete"></use></svg><span>取り消し</span></button>
			</div>
		</div>

	</main>

	<?php require('../require/rightbox.php'); ?>
	<?php require('../require/botbox.php'); ?>
	<?php require('../require/noscript_modal.php'); ?>

</body>

<script>
	$(document).ready(function() {
		var userid = '<?php echo $userid; ?>';
		var account_id = '<?php echo $loginid; ?>';

		loadPosts();

		var pageNumber = 1;

		var isLoading = false;

		var mode = "";

		function loadPosts() {
			if (isLoading) return;
			isLoading = true;
			$("#loading").show();
			$("#error").hide();
			var uwuzuid = '<?php echo $uwuzuid; ?>';
			if (mode == 'allueuse') {
				$.ajax({
					url: '../nextpage/userpage.php', // PHPファイルへのパス
					method: 'GET',
					data: {
						page: pageNumber,
						id: uwuzuid,
						userid: userid,
						account_id: account_id
					},
					dataType: 'html',
					timeout: 300000,
					success: function(response) {
						$('#postContainer').append(response);
						pageNumber++;
						isLoading = false;
						$("#loading").hide();
					},
					error: function(xhr, textStatus, errorThrown) { // エラーと判定された場合
						isLoading = false;
						$("#loading").hide();
						$("#error").show();
					},
				});
			} else if (mode == 'mediaueuse') {
				$.ajax({
					url: '../nextpage/usermediapage.php', // PHPファイルへのパス
					method: 'GET',
					data: {
						page: pageNumber,
						id: uwuzuid,
						userid: userid,
						account_id: account_id
					},
					dataType: 'html',
					timeout: 300000,
					success: function(response) {
						$('#postContainer').append(response);
						pageNumber++;
						isLoading = false;
						$("#loading").hide();
					},
					error: function(xhr, textStatus, errorThrown) { // エラーと判定された場合
						isLoading = false;
						$("#loading").hide();
						$("#error").show();
					},
				});
			} else if (mode == 'likeueuse') {
				$.ajax({
					url: '../nextpage/userlikepage.php', // PHPファイルへのパス
					method: 'GET',
					data: {
						page: pageNumber,
						id: uwuzuid,
						userid: userid,
						account_id: account_id
					},
					dataType: 'html',
					timeout: 300000,
					success: function(response) {
						$('#postContainer').append(response);
						pageNumber++;
						isLoading = false;
						$("#loading").hide();
					},
					error: function(xhr, textStatus, errorThrown) { // エラーと判定された場合
						isLoading = false;
						$("#loading").hide();
						$("#error").show();
					},
				});
			} else {
				$('#all_ueuse_btn').addClass('btmline');
				$.ajax({
					url: '../nextpage/userpage.php', // PHPファイルへのパス
					method: 'GET',
					data: {
						page: pageNumber,
						id: uwuzuid,
						userid: userid,
						account_id: account_id
					},
					dataType: 'html',
					timeout: 300000,
					success: function(response) {
						$('#postContainer').append(response);
						pageNumber++;
						isLoading = false;
						$("#loading").hide();
					},
					error: function(xhr, textStatus, errorThrown) { // エラーと判定された場合
						isLoading = false;
						$("#loading").hide();
						$("#error").show();
					},
				});
			}
		}

		$("#all_ueuse_btn").on('click', function(event) {
			$('#all_ueuse_btn').addClass('btmline');
			$('#media_ueuse_btn').removeClass('btmline');
			$('#like_ueuse_btn').removeClass('btmline');

			event.preventDefault();
			$("#postContainer").empty();
			pageNumber = 1;
			mode = "allueuse";
			loadPosts();
		});

		$("#media_ueuse_btn").on('click', function(event) {
			$('#media_ueuse_btn').addClass('btmline');
			$('#like_ueuse_btn').removeClass('btmline');
			$('#all_ueuse_btn').removeClass('btmline');

			event.preventDefault();
			$("#postContainer").empty();
			pageNumber = 1;
			mode = "mediaueuse";
			loadPosts();
		});

		$("#like_ueuse_btn").on('click', function(event) {
			$('#like_ueuse_btn').addClass('btmline');
			$('#media_ueuse_btn').removeClass('btmline');
			$('#all_ueuse_btn').removeClass('btmline');

			event.preventDefault();
			$("#postContainer").empty();
			pageNumber = 1;
			mode = "likeueuse";
			loadPosts();
		});



		$('.outer').on('scroll', function() {
			var innerHeight = $('.inner').innerHeight(), //内側の要素の高さ
				outerHeight = $('.outer').innerHeight(), //外側の要素の高さ
				outerBottom = innerHeight - outerHeight; //内側の要素の高さ - 外側の要素の高さ
			if (outerBottom <= $('.outer').scrollTop()) {
				var elem = document.getElementById("noueuse");

				if ($("#error").css('display') == 'block') {
					// えらー処理
					return;
				} else if (elem === null) {
					// 存在しない場合の処理
					loadPosts();
				} else {
					// 存在する場合の処理
					return;
				}
			}
		});

		// JavaScriptでウィンドウを制御
		const modal1 = document.getElementById('myModal');
		const openModalButton = document.getElementById('openModalButton');
		const closeButton = document.getElementById('closeModal');
		var modalMain = $('.modal-content');

		openModalButton.addEventListener('click', () => {
			modal1.style.display = 'block';
			modalMain.addClass("slideUp");
			modalMain.removeClass("slideDown");
		});

		closeButton.addEventListener('click', () => {
			modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function() {
				modal1.style.display = 'none';
			}, 150);
		});



		$(document).on('click', '.favbtn, .favbtn_after', function(event) {

			event.preventDefault();

			var postUniqid = $(this).data('uniqid');
			var likeCountElement = $(this).find('.like-count'); // いいね数を表示する要素

			var isLiked = $(this).hasClass('favbtn_after'); // 現在のいいねの状態を判定

			var $this = $(this); // ボタン要素を変数に格納

			$.ajax({
				url: '../favorite/favorite.php',
				method: 'POST',
				data: {
					uniqid: postUniqid,
					userid: userid,
					account_id: account_id
				}, // ここに自分のユーザーIDを指定
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						// いいね成功時の処理
						if (isLiked) {
							$this.removeClass('favbtn_after'); // クラスを削除していいねを取り消す
							$this.find('use').attr('xlink:href', '../img/sysimage/favorite_1.svg#favorite'); // 画像を元の画像に戻す
						} else {
							$this.addClass('favbtn_after'); // クラスを追加していいねを追加する
							$this.find('use').attr('xlink:href', '../img/sysimage/favorite_2.svg#favorite'); // 画像を新しい画像に置き換える
						}

						var newFavoriteList = response.newFavorite.split(',');
						var likeCount = newFavoriteList.length - 1;
						likeCountElement.text(likeCount); // いいね数を更新
					} else {
						// いいね失敗時の処理
					}
				}.bind(this), // コールバック内でthisが適切な要素を指すようにbindする
				error: function() {
					// エラー時の処理
				}
			});

		});


		$(document).on('click', '.bookmark, .bookmark_after', function(event) {

			event.preventDefault();

			var postUniqid = $(this).data('uniqid');
			var likeCountElement = $(this).find('.like-count'); // いいね数を表示する要素

			var isLiked = $(this).hasClass('bookmark_after'); // 現在のいいねの状態を判定

			var $this = $(this); // ボタン要素を変数に格納

			$.ajax({
				url: '../bookmark/bookmark.php',
				method: 'POST',
				data: {
					uniqid: postUniqid,
					userid: userid,
					account_id: account_id
				}, // ここに自分のユーザーIDを指定
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						// いいね成功時の処理
						if (isLiked) {
							$this.removeClass('bookmark_after'); // クラスを削除していいねを取り消す
						} else {
							$this.addClass('bookmark_after'); // クラスを追加していいねを追加する
						}
					} else {
						// いいね失敗時の処理
					}
				}.bind(this), // コールバック内でthisが適切な要素を指すようにbindする
				error: function() {
					// エラー時の処理
				}
			});
		});


		var modal = document.getElementById('myDelModal');
		var deleteButton = document.getElementById('deleteButton');
		var cancelButton = document.getElementById('cancelButton'); // 追加
		var modalMain = $('.modal-content');

		$(document).on('click', '#delete', function (event) {
			modal.style.display = 'block';
			modalMain.addClass("slideUp");
			modalMain.removeClass("slideDown");

			var uniqid = $(this).parents().attr('data-uniqid');
			var postElement = $("#ueuse-"+uniqid);

			deleteButton.addEventListener('click', () => {
				modalMain.removeClass("slideUp");
				modalMain.addClass("slideDown");
				window.setTimeout(function(){
					modal.style.display = 'none';
				}, 150);

				$.ajax({
					url: '../delete/delete.php',
					method: 'POST',
					data: { uniqid: uniqid, userid: userid, account_id: account_id },
					dataType: 'json',
					success: function (response) {
						if (response.success) {
							postElement.remove();
						} else {
							// 削除失敗時の処理
						}
					},
					error: function () {
						// エラー時の処理
					}
				});
			});

			cancelButton.addEventListener('click', () => { // 追加
				modalMain.removeClass("slideUp");
				modalMain.addClass("slideDown");
				window.setTimeout(function(){
					modal.style.display = 'none';
				}, 150);
			});
		});

		var abimodal = document.getElementById('myAbiModal');
		var AbiAddButton = document.getElementById('AbiAddButton');
		var AbiCancelButton = document.getElementById('AbiCancelButton');
		var modalMain = $('.modal-content');

		$(document).on('click', '.addabi', function(event) {

			abimodal.style.display = 'block';
			modalMain.addClass("slideUp");
			modalMain.removeClass("slideDown");

			var uniqid2 = $(this).attr('data-uniqid2');
			var postAbiElement = $(this).closest('.addabi');

			AbiCancelButton.addEventListener('click', () => {
				modalMain.removeClass("slideUp");
				modalMain.addClass("slideDown");
				window.setTimeout(function() {
					abimodal.style.display = 'none';
				}, 150);
			});

			$('#AbiForm').off('submit').on('submit', function(event) {

				event.preventDefault();

				var abitext = document.getElementById("abitexts").value;
				var usernames = '<?php echo $username; ?>';

				if (abitext == "") {
					modalMain.removeClass("slideUp");
					modalMain.addClass("slideDown");
					window.setTimeout(function() {
						abimodal.style.display = 'none';
					}, 150);
				} else {
					$.ajax({
						url: '../abi/addabi.php',
						method: 'POST',
						data: {
							uniqid: uniqid2,
							abitext: abitext,
							username: usernames,
							userid: userid,
							account_id: account_id
						},
						dataType: 'json',
						success: function(response) {
							if (response.success) {
								abimodal.style.display = 'none';
								postAbiElement.remove();
							} else {
								abimodal.style.display = 'none';
								postAbiElement.remove();
							}
						},
						error: function(xhr, status, error) {
							abimodal.style.display = 'none';
							postAbiElement.remove();
						}
					});
				}
			});
		});

		//---------------リユーズ----------------

		$(document).on('click', '#quote_reuse_btn', function (event) {
			var modalMain = $('.modal-content');
			var reuseModal = $('#myQuoteReuseModal');

			reuseModal.show();
			modalMain.addClass("slideUp");
			modalMain.removeClass("slideDown");

			var uniqid = $(this).parents().attr('data-uniqid');

			$('#ReuseCancelButton').on('click', function (event) {
				modalMain.removeClass("slideUp");
				modalMain.addClass("slideDown");
				window.setTimeout(function(){
					reuseModal.hide();
				}, 150);
			});

			$('#ReuseButton').on('click', function (event) {
				event.preventDefault();

				var reusetext = $("#reusetexts").val();

				if(reusetext == ""){
					modalMain.removeClass("slideUp");
					modalMain.addClass("slideDown");
					window.setTimeout(function(){
						reuseModal.hide();
					}, 150);
				}else{
					$.ajax({
						url: '../function/reuse.php',
						method: 'POST',
						data: { uniqid: uniqid, reusetext: reusetext, userid: userid, account_id: account_id},
						dataType: 'json',
						success: function (response) {
							if (response.success) {
								reuseModal.hide();
								view_notify("引用リユーズしました");
							} else {
								reuseModal.hide();
								view_notify("引用リユーズに失敗しました");
							}
						},
						error: function (xhr, status, error) {
							reuseModal.hide();
							view_notify("引用リユーズに失敗しました");
						}
					});
				}
			});
		});

		$(document).on('click', '#normal_reuse_btn', function (event) {
			event.preventDefault();
			var uniqid = $(this).parents().attr('data-uniqid');
			var reusetext = "";
			$.ajax({
				url: '../function/reuse.php',
				method: 'POST',
				data: { uniqid: uniqid, reusetext: reusetext, userid: userid, account_id: account_id},
				dataType: 'json',
				success: function (response) {
					if (response.success) {
						view_notify("リユーズしました");
					} else {
						view_notify("リユーズに失敗しました");
					}
				},
				error: function (xhr, status, error) {
					view_notify("リユーズに失敗しました");
				}
			});
		});

		$(document).on('click', '#delete_reuse_btn', function (event) {
			event.preventDefault();
			var uniqid = $(this).parents().attr('data-uniqid');
			var reusetext = "";
			var postElement = $("#ueuse-"+uniqid);
			$.ajax({
				url: '../delete/delete.php',
				method: 'POST',
				data: { uniqid: uniqid, userid: userid, account_id: account_id },
				dataType: 'json',
				success: function (response) {
					if (response.success) {
						postElement.remove();
					} else {
						view_notify("リユーズの取り消しに失敗しました");
					}
				},
				error: function () {
					view_notify("リユーズの取り消しに失敗しました");
				}
			});
		});

		var modal2 = document.getElementById('account_BlockModal');
		var deleteButton2 = document.getElementById('deleteButton2');
		var cancelButton2 = document.getElementById('cancelButton2'); // 追加
		var modalMain = $('.modal-content');

		$('#block').click(function() {
			modal2.style.display = 'block';
			modalMain.addClass("slideUp");
			modalMain.removeClass("slideDown");

			deleteButton2.addEventListener('click', () => {
				modalMain.removeClass("slideUp");
				modalMain.addClass("slideDown");
				window.setTimeout(function() {
					modal2.style.display = 'none';
				}, 150);
			});

			cancelButton2.addEventListener('click', () => { // 追加
				modalMain.removeClass("slideUp");
				modalMain.addClass("slideDown");
				window.setTimeout(function() {
					modal2.style.display = 'none';
				}, 150);
			});
		});

		var modal3 = document.getElementById('account_un_BlockModal');
		var deleteButton3 = document.getElementById('deleteButton3');
		var cancelButton3 = document.getElementById('cancelButton3'); // 追加
		var modalMain = $('.modal-content');

		$('#un_block').click(function() {
			modal3.style.display = 'block';
			modalMain.addClass("slideUp");
			modalMain.removeClass("slideDown");

			deleteButton3.addEventListener('click', () => {
				modalMain.removeClass("slideUp");
				modalMain.addClass("slideDown");
				window.setTimeout(function() {
					modal3.style.display = 'none';
				}, 150);
			});

			cancelButton3.addEventListener('click', () => { // 追加
				modalMain.removeClass("slideUp");
				modalMain.addClass("slideDown");
				window.setTimeout(function() {
					modal3.style.display = 'none';
				}, 150);
			});
		});

		var modal4 = document.getElementById('FollowerUserModal');
		var CloseButton4 = document.getElementById('CloseButton4'); // 追加
		var modalMain = $('.modal-content');

		$('#follower_cnt').click(function() {
			modal4.style.display = 'block';
			modalMain.addClass("slideUp");
			modalMain.removeClass("slideDown");

			CloseButton4.addEventListener('click', () => {
				modalMain.removeClass("slideUp");
				modalMain.addClass("slideDown");
				window.setTimeout(function() {
					modal4.style.display = 'none';
				}, 150);
			});
		});

		var modal5 = document.getElementById('FollowUserModal');
		var CloseButton5 = document.getElementById('CloseButton5'); // 追加
		var modalMain = $('.modal-content');

		$('#follow_cnt').click(function() {
			modal5.style.display = 'block';
			modalMain.addClass("slideUp");
			modalMain.removeClass("slideDown");

			CloseButton5.addEventListener('click', () => {
				modalMain.removeClass("slideUp");
				modalMain.addClass("slideDown");
				window.setTimeout(function() {
					modal5.style.display = 'none';
				}, 150);
			});
		});

		window.addEventListener('online', function() {
			checkOnline();
		});
		window.addEventListener('offline', function() {
			checkOnline();
		});

		function checkOnline() {
			if (navigator.onLine) {
				$("#online").show();
				$("#offline").hide();
			} else {
				$("#online").hide();
				$("#offline").show();
			}
		}

		$(document).on('click', '#share', function (event) {

			var domain = "<?php echo $domain;?>";
			var share_uniqid = $(this).parents().attr('data-uniqid');
			var share_userid = $(this).parents().attr('data-userid');

			if (typeof navigator.share === 'undefined') {
				navigator.clipboard.writeText("https://"+domain+"/!"+share_uniqid+"")
				$("#clipboard").show();
				window.setTimeout(function(){
					$("#clipboard").hide();
				}, 5000);
				return;
			}

			var shareData = {
				title: ''+share_userid+'さんのID '+share_uniqid+' のユーズ - uwuzu',
				text: '',
				url: "https://"+domain+"/!"+share_uniqid+"",
			};

			navigator.share(shareData)
			.then(function () {
				// シェア完了後の処理
			})
			.catch(function (error) {
				// シェア失敗時の処理
			});

		});

		$(document).on('click', '#reusebtn', function(event) {
			$('#reuse_popup').css({
				left: event.pageX - 80,
				top: event.pageY
			});

			var reusebtncss = $(this).attr('class');
			if(reusebtncss.indexOf('reuse_after') >= 0){
				$("#delete_reuse_btn").show();
			}else{
				$("#delete_reuse_btn").hide();
			}

			$("#reuse_popup").attr('data-uniqid',$(this).attr('data-uniqid'));
			$("#reuse_popup").attr('data-userid',$(this).attr('data-userid'));

			$("#ueuse_popup_back").show();
			$("#reuse_popup").show();
		});

		$(document).on('click', '#popup', function(event) {
			$('#ueuse_popup').css({
				left: event.pageX - 80,
				top: event.pageY
			});

			$("#ueuse_popup").attr('data-uniqid',$(this).attr('data-uniqid'));
			$("#ueuse_popup").attr('data-userid',$(this).attr('data-userid'));

			if(!(userid == $(this).attr('data-userid'))){
				$("#ueuse_popup").children("#delete").hide();
			}else{
				$("#ueuse_popup").children("#delete").show();
			}

			$("#ueuse_popup_back").show();
			$("#ueuse_popup").show();
		});
		$(document).on('click', '#ueuse_popup_back, .popbtn', function(event) {
			$('#ueuse_popup').addClass("bye");
			$('#reuse_popup').addClass("bye");

			setTimeout(function(){
				$("#ueuse_popup_back").hide();
				$('#ueuse_popup').hide();
				$('#reuse_popup').hide();

				$('#ueuse_popup').removeClass("bye");
				$('#reuse_popup').removeClass("bye");
			}, 250);
		});

	});
</script>

</html>