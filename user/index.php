<?php

$servernamefile = "../server/servername.txt";

$domain = $_SERVER['HTTP_HOST'];

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
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

$userid = htmlentities($_SESSION['userid']);
$username = htmlentities($_SESSION['username']);

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

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', htmlentities($_SESSION['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_SESSION['loginid'] === $res["loginid"] && $_SESSION['userid'] === $res["userid"]){
	// セッションに値をセット
	$userid = htmlentities($_SESSION['userid']); // セッションに格納されている値をそのままセット
	$username = htmlentities($_SESSION['username']); // セッションに格納されている値をそのままセット
	$loginid = htmlentities($res["loginid"]);
	$role = htmlentities($res["role"]);
	$sacinfo = htmlentities($res["sacinfo"]);
	$myblocklist = htmlentities($res["blocklist"]);
	$myfollowlist = htmlentities($res["follow"]);
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

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', htmlentities($_COOKIE['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_COOKIE['loginid'] === $res["loginid"] && $_COOKIE['userid'] === $res["userid"]){
	// セッションに値をセット
	$userid = htmlentities($_COOKIE['userid']); // クッキーから取得した値をセット
	$username = htmlentities($_COOKIE['username']); // クッキーから取得した値をセット
	$loginid = htmlentities($res["loginid"]);
	$role = htmlentities($res["role"]);
	$sacinfo = htmlentities($res["sacinfo"]);
	$myblocklist = htmlentities($res["blocklist"]);
	$myfollowlist = htmlentities($res["follow"]);
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

	$uwuzuid2 = htmlentities(str_replace('@', '', $_GET['uwuzuid']));

	$uwuzuid = htmlentities(str_replace('@'.$domain, '', $uwuzuid2));

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
		$pattern = '/(https:\/\/[^\s<>\[\]\'"]+)/';  // 改良された正規表現
		preg_match_all($pattern, $postText, $matches);
	
		// 検出したURLごとに処理を行う
		foreach ($matches[0] as $url) {
			// ドメイン部分を抽出
			$parsedUrl = parse_url($url);
			$domain = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
	
			// 不要な文字を削除してaタグを生成
			$urlWithoutSpaces = preg_replace('/\s+/', '', $url);
			$link = "<a href='$urlWithoutSpaces' target='_blank'>$domain</a>";
	
			// URLをドメインのみを表示するaタグで置き換え
			$postText = preg_replace('/' . preg_quote($url, '/') . '/', $link, $postText);
		}
	
		return $postText;
	}

	$userQuery = $dbh->prepare("SELECT username, userid, profile, role, follower, blocklist FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $uwuzuid);
	$userQuery->execute();
	$userData = $userQuery->fetch();


	if(!empty($userData["userid"])){

	
		$roles = explode(',', $userData["role"]); // カンマで区切られたロールを配列に分割

		$rerole = $dbh->prepare("SELECT  follow, follower,blocklist, username, userid, password, mailadds, profile, iconname, headname, role, datetime FROM account WHERE userid = :userid");

		$rerole->bindValue(':userid', $uwuzuid);
		// SQL実行
		$rerole->execute();

		$userdata = $rerole->fetch(); // ここでデータベースから取得した値を $role に代入する
		
		$roleDataArray = array();
		
		foreach ($roles as $roleId) {
			$rerole = $dbh->prepare("SELECT rolename, roleauth, rolecolor FROM role WHERE roleidname = :role");
			$rerole->bindValue(':role', $roleId);
			$rerole->execute();
			$roleDataArray[$roleId] = $rerole->fetch();
		}
		

		//-------フォロー数---------
		$follow = $userdata['follow']; // コンマで区切られたユーザーIDを含む変数

		// コンマで区切って配列に分割し、要素数を数える
		$followIds = explode(',', $follow);
		$followCount = count($followIds)-1;

        $follow_on_me = array_search($userid, $followIds);
	
		if ($follow_on_me !== false) {
			$follow_yes = "フォローされています"; // worldを含む:6
		}else{
			$follow_yes = ""; // worldを含む:6
		}

		//-------フォロワー数---------
		$follower = $userdata['follower']; // コンマで区切られたユーザーIDを含む変数

		// コンマで区切って配列に分割し、要素数を数える
		$followerIds = explode(',', $follower);
		$followerCount = count($followerIds)-1;

		$profileText = htmlentities($userData['profile'], ENT_QUOTES, 'UTF-8');


		$allueuse = $dbh->prepare("SELECT account FROM ueuse WHERE account = :userid");
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


	}else{
		$userData["userid"] = "none";
		$userData['username'] = "ゆーざーなし";

		$ueuse_cnt = "zero";
		$followCount = "zero";
		$followerCount = "zero";
	}

	
}

if (!empty($_POST['follow'])) {
    // フォローボタンが押された場合の処理
    $followerList = explode(',', $userdata['follower']);
    if (!in_array($userid, $followerList)) {
        // 自分が相手をフォローしていない場合、相手のfollowerカラムと自分のfollowカラムを更新
        $followerList[] = $userid;
        $newFollowerList = implode(',', $followerList);

        // UPDATE文を実行してフォロー情報を更新
        $updateQuery = $pdo->prepare("UPDATE account SET follower = :follower WHERE userid = :userid");
        $updateQuery->bindValue(':follower', $newFollowerList, PDO::PARAM_STR);
        $updateQuery->bindValue(':userid', $userData['userid'], PDO::PARAM_STR);
        $res = $updateQuery->execute();

        // 自分のfollowカラムを更新
        $updateQuery = $pdo->prepare("UPDATE account SET follow = CONCAT_WS(',', follow, :follow) WHERE userid = :userid");
        $updateQuery->bindValue(':follow', $userData["userid"], PDO::PARAM_STR);
        $updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
        $res_follow = $updateQuery->execute();
        
		$datetime = date("Y-m-d H:i:s");
		$pdo->beginTransaction();

		try {
			$touserid = $userData["userid"];
			$datetime = date("Y-m-d H:i:s");
			$msg = "".$userid."さんにフォローされました。";
			$title = "🎉".$userid."さんにフォローされました！🎉";
			$url = "/@" . $userid . "";
			$userchk = 'none';

			// 通知用SQL作成
			$stmt = $pdo->prepare("INSERT INTO notification (touserid, msg, url, datetime, userchk, title) VALUES (:touserid, :msg, :url, :datetime, :userchk, :title)");


			$stmt->bindParam(':touserid', $touserid, PDO::PARAM_STR);
			$stmt->bindParam(':msg', $msg, PDO::PARAM_STR);
			$stmt->bindParam(':url', $url, PDO::PARAM_STR);
			$stmt->bindParam(':userchk', $userchk, PDO::PARAM_STR);
			$stmt->bindParam(':title', $title, PDO::PARAM_STR);

			$stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

			// SQLクエリの実行
			$res = $stmt->execute();

			// コミット
			$res = $pdo->commit();

		} catch(Exception $e) {

			// エラーが発生した時はロールバック
			$pdo->rollBack();
		}

		if ($res && $res_follow) {
            $url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location:" . $url);
            exit;
        } else {
            $error_message[] = '更新に失敗しました。';
        }
	}

} elseif (!empty($_POST['unfollow'])) {
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
            $url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location:" . $url);
            exit;
        } else {
            $error_message[] = '更新に失敗しました。';
        }

        $stmt = null;
    }
}


if (!empty($_POST['send_block_submit'])) {

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
            $url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location:" . $url);
            exit;
        } else {
            $error_message[] = '更新に失敗しました。';
        }

        $stmt = null;
    }

	if ($res_block) {
		$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		header("Location:" . $url);
		exit;
	} else {
		$error_message[] = '更新に失敗しました。';
	}

} elseif (!empty($_POST['send_un_block_submit'])) {

	$myblklist = explode(',', $myBlocklist);
	$delblkList = array_diff($myblklist, array($userData['userid']));
	$deluserid = implode(',', $delblkList);
	// 自分のfollowカラムから相手のユーザーIDを削除
	$updateQuery = $pdo->prepare("UPDATE account SET blocklist = :blocklist WHERE userid = :userid");
	$updateQuery->bindValue(':blocklist', $deluserid, PDO::PARAM_STR);
	$updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
	$res_block = $updateQuery->execute();

	if ($res_block) {
		$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		header("Location:" . $url);
		exit;
	} else {
		$error_message[] = '更新に失敗しました。';
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
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="../js/console_notice.js"></script>
<script src="../js/nsfw_event.js"></script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/home.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title><?php echo htmlentities($userData['username'], ENT_QUOTES, 'UTF-8'); ?> さんのプロフィール - <?php echo file_get_contents($servernamefile);?></title>

</head>

<body>

	<div>
		<div id="offline" class="offline" style="display:none;">
			<p>🦖💨 インターネットへの接続が切断されました...</p>
		</div>
		<div id="online" class="online" style="display:none;">
			<p>🌐💫 インターネットへの接続が復帰しました！！！</p>
		</div>
	</div>

	<?php require('../require/leftbox.php');?>
	<main class="outer">

		<?php if( !empty($error_message) ): ?>
			<ul class="errmsg">
				<?php foreach( $error_message as $value ): ?>
					<p>・ <?php echo $value; ?></p>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<div class="userheader">
			<?php if($userData["userid"] == "none"){
				header("HTTP/1.1 404 Not Found");?>
				<!--いないひと--->
				<div class="hed">
					<img src="../img/defhead/head.png">
				</div>
				<div class="icon">
					<img src="../img/deficon/icon.png">
					<h2>でふぉると</h2>
					<p>@admin</p>
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
					<p>紀元前3000年からuwuzuを利用して<s>います。</s>いるわけねぇだろ()</p>
				</div>
			</div>
				<!--ここまで！--->
			<?php }else{?>
			<div class="hed">
				<img src="<?php echo htmlentities('../'.$userdata['headname']); ?>">
			</div>
			<div class="icon">
				<img src="<?php echo htmlentities('../'.$userdata['iconname']); ?>">
				<h2><?php echo htmlentities($userData['username'], ENT_QUOTES, 'UTF-8'); ?></h2>
				<p>@<?php echo htmlentities($userData['userid'], ENT_QUOTES, 'UTF-8'); ?><!--<span>@<?php /*echo htmlentities($domain, ENT_QUOTES, 'UTF-8'); */?></span>--></p>
			</div>

			<div class="roleboxes">
				<?php foreach ($roles as $roleId): ?>
					<?php $roleData = $roleDataArray[$roleId]; ?>
					<div class="rolebox" style="border: 1px solid <?php echo '#' . $roleData["rolecolor"]; ?>;">
						<p style="color: <?php echo '#' . $roleData["rolecolor"]; ?>;">
							<?php if (!empty($roleData["rolename"])) { echo htmlentities($roleData["rolename"], ENT_QUOTES, 'UTF-8'); }else{ echo("ロールが正常に設定されていません。");} ?>
						</p>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if (false === strpos($myblocklist, ','.htmlentities($userData['userid'], ENT_QUOTES, 'UTF-8'))) {?>
				<div class="profile">
					<p><?php echo replaceEmojisWithImages(replaceURLsWithLinks(nl2br($profileText))); ?></p>
				</div>
			<?php }else{?>
				<div class="profile">
					<p>ブロックしているためプロフィールは表示されません。</p>
				</div>
			<?php }?>
			
		</div>
		<div class="fzone">
			<div class="time">
				<p><?php echo date('Y年m月d日 H:i:s', strtotime($userdata['datetime'])); ?>からuwuzuを利用しています。</p>
				<p><?php if(htmlentities($userdata['role']) === "ice"){echo"このアカウントは凍結されています。";}; ?></p>
			</div>
			
			<?php if(!empty($follow_yes)){?>
				<div class="follow_yes">
					<p><?php echo $follow_yes;?></p>
				</div>
			<?php }?>

			<?php if ($userid !== htmlentities($userData['userid'], ENT_QUOTES, 'UTF-8')) {?>
				<?php if (false !== strpos($myblocklist, ','.htmlentities($userData['userid'], ENT_QUOTES, 'UTF-8'))) {?>
					<div class="follow">
						<a id="un_block" href="javascript:void(0);" class="report" title="ブロック解除"><svg><use xlink:href="../img/sysimage/unblock_1.svg#block"></use></svg></a>
					</div>
				<?php }else{?>
					<div class="follow">
						<a id="block" href="javascript:void(0);" class="report" title="ブロック"><svg><use xlink:href="../img/sysimage/block_1.svg#block"></use></svg></a>
					</div>
				<?php }?>
			<?php }?>
			
			<div class="follow">
				<a href="/user/report?q=<?php echo htmlentities($userData['userid'], ENT_QUOTES, 'UTF-8'); ?>" class="report" title="通報"><svg><use xlink:href="../img/sysimage/report_1.svg#report"></use></svg></a>
			</div>
			<?php if ($userData['userid'] == $userid) { ?>
				<div class="follow">
					<a href="../settings/" class="fbtn_no" title="設定" >設定</a>
				</div>
			<?php } else { ?>
				
				<?php if (false === strpos($myblocklist, ','.htmlentities($userData['userid'], ENT_QUOTES, 'UTF-8'))) {?>
					<form method="post">
						<div class="follow">
							<?php
							if(!($role === "ice")){
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
			<?php } ?>
		</div>

		<div class="sp_time_area">
			<div class="time">
				<p><?php echo date('Y年m月d日 H:i:s', strtotime($userdata['datetime'])); ?>からuwuzuを利用しています。</p>
				<p><?php if(htmlentities($userdata['role']) === "ice"){echo"このアカウントは凍結されています。";}; ?></p>
			</div>
		</div>

		<?php if(!($role === "ice")){?>
			<div id="myModal" class="modal">
				<div class="modal-content">
					<p><?php echo htmlentities($userData['username'], ENT_QUOTES, 'UTF-8'); ?>さんをフォロー解除しますか？</p>
					<form class="btn_area" method="post">
						<input type="submit" id="openModalButton" class="fbtn_no" name="unfollow" value="フォロー解除">
						<input type="button" id="closeModal" class="fbtn" value="キャンセル">
					</form>
				</div>
			</div>
		<?php }?>
		<hr>
		<div class="f_c_area">
			<div class="fcnt">
				<div class="p2">ユーズ数</div>
				<p><?php echo $ueuse_cnt;?></p>
			</div>
			<div class="fcnt" id="follow_cnt" style="cursor:pointer;">
				<div class="p2">フォロー数</div>
				<p><?php echo $followCount;?></p>
			</div>
			<div class="fcnt" id="follower_cnt" style="cursor:pointer;">
				<div class="p2">フォロワー数</div>
				<p><?php echo $followerCount;?></p>
			</div>
		</div>
		<hr>
		<div class="select_utl">
			<button class="btn" id="all_ueuse_btn">ユーズ</button>
			<button class="btn" id="media_ueuse_btn">メディア</button>
			<button class="btn" id="like_ueuse_btn">いいね</button>
		</div>

		<hr>
			<?php if (false === strpos($myblocklist, ','.htmlentities($userData['userid'], ENT_QUOTES, 'UTF-8'))) {?>
				<section class="inner">
					<div id="postContainer">

					</div>
				</section>

				<div id="loading" class="loading" style="display: none;">
					🤔
				</div>
			<?php }else{?>
				<div class="tokonone" id="noueuse"><p><?php echo htmlentities($userData['username'], ENT_QUOTES, 'UTF-8'); ?>さんをブロックしているため投稿の閲覧は出来ません。</p></div>
			<?php }?>

			<div id="error" class="error" style="display: none;">
				<h1>エラー</h1>
				<p>サーバーの応答がなかったか不完全だったようです。<br>ネットワークの接続が正常かを確認の上再読み込みしてください。</p>
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
					<textarea id="abitexts" placeholder="なに追記する～？" name="abi"><?php if( !empty($_SESSION['abi']) ){ echo htmlentities( $_SESSION['abi'], ENT_QUOTES, 'UTF-8'); } ?></textarea>
					<div class="btn_area">
						<input type="submit" id="AbiAddButton" class="fbtn_no" name="abi" value="追記">
						<input type="button" id="AbiCancelButton" class="fbtn" value="キャンセル">
					</div>
					</form>
				</div>
			</div>

			<div id="account_BlockModal" class="modal">
				<div class="modal-content">
					<h1><?php echo htmlentities($userdata['username'], ENT_QUOTES, 'UTF-8'); ?>さんをブロックしますか？</h1>
					<p><?php echo htmlentities($userdata['username'], ENT_QUOTES, 'UTF-8'); ?>さんのアカウントをブロックしますか？<br>ブロックするとフォローが解除され、検索以外のLTL、FTL等で<?php echo htmlentities($userdata['username'], ENT_QUOTES, 'UTF-8'); ?>さんの投稿が表示されなくなります。<br>また、相手からこのアカウントを閲覧することもできなくなります。<br>※ブロックしたことは相手には通知されません。<br><br>ブロックを解除するときはこのアカウントのユーザーページ(このページ)から解除を行ってください。</p>
					<form class="btn_area" method="post">
						<input type="submit" id="deleteButton2" class="fbtn_no" name="send_block_submit" value="ブロック">
						<input type="button" id="cancelButton2" class="fbtn" value="キャンセル">
					</form>
				</div>
			</div>	

			<div id="account_un_BlockModal" class="modal">
				<div class="modal-content">
					<h1><?php echo htmlentities($userdata['username'], ENT_QUOTES, 'UTF-8'); ?>さんのブロックを解除しますか？</h1>
					<p><?php echo htmlentities($userdata['username'], ENT_QUOTES, 'UTF-8'); ?>さんのアカウントをブロック解除しますか？<br>ブロック解除すると<?php echo htmlentities($userdata['username'], ENT_QUOTES, 'UTF-8'); ?>さんの投稿の閲覧が可能になりフォローすることもできるようになります。</p>
					<form class="btn_area" method="post">
						<input type="submit" id="deleteButton3" class="fbtn_no" name="send_un_block_submit" value="ブロック解除">
						<input type="button" id="cancelButton3" class="fbtn" value="キャンセル">
					</form>
				</div>
			</div>	


			<div id="FollowerUserModal" class="modal">
				<div class="modal-content">
					<p><?php echo htmlentities($userData["username"], ENT_QUOTES, 'UTF-8');?>さんをフォローしているユーザー</p>
					<?php 
					if(!empty($follower_userdata)){
						foreach ($follower_userdata as $value) {
							if (false === strpos($myblocklist, ',' . htmlentities($value['userid'], ENT_QUOTES, 'UTF-8'))) {
								echo "<div class='action_userlist'>";
								echo "<a href='/@".htmlentities($value['userid'], ENT_QUOTES, 'UTF-8')."'><img src=".htmlentities($value['iconname'], ENT_QUOTES, 'UTF-8')."></a>";
								echo "<div class='userabout'>";
								echo "<div class='username'><a href='/@".htmlentities($value['userid'], ENT_QUOTES, 'UTF-8')."'>".htmlentities($value['username'], ENT_QUOTES, 'UTF-8')."</a></div>";
								echo "<div class='userid'><a href='/@".htmlentities($value['userid'], ENT_QUOTES, 'UTF-8')."'>@".htmlentities($value['userid'], ENT_QUOTES, 'UTF-8')."</a></div>";
								echo "</div>";
								echo "</div>";
							}
						}
					}else{
						echo "<p>".htmlentities($userData["username"], ENT_QUOTES, 'UTF-8')."さんは誰にもフォローされていません。</p>";
					}
					?>
					<div class="btn_area">
						<input type="button" id="CloseButton4" class="fbtn" value="閉じる">
					</div>
				</div>
			</div>	

			<div id="FollowUserModal" class="modal">
				<div class="modal-content">
					<p><?php echo htmlentities($userData["username"], ENT_QUOTES, 'UTF-8');?>さんがフォローしているユーザー</p>
					<?php 
					if(!empty($follow_userdata)){
						foreach ($follow_userdata as $value) {
							if (false === strpos($myblocklist, ',' . htmlentities($value['userid'], ENT_QUOTES, 'UTF-8'))) {
								echo "<div class='action_userlist'>";
								echo "<a href='/@".htmlentities($value['userid'], ENT_QUOTES, 'UTF-8')."'><img src=".htmlentities($value['iconname'], ENT_QUOTES, 'UTF-8')."></a>";
								echo "<div class='userabout'>";
								echo "<div class='username'><a href='/@".htmlentities($value['userid'], ENT_QUOTES, 'UTF-8')."'>".htmlentities($value['username'], ENT_QUOTES, 'UTF-8')."</a></div>";
								echo "<div class='userid'><a href='/@".htmlentities($value['userid'], ENT_QUOTES, 'UTF-8')."'>@".htmlentities($value['userid'], ENT_QUOTES, 'UTF-8')."</a></div>";
								echo "</div>";
								echo "</div>";
							}
						}
					}else{
						echo "<p>".htmlentities($userData["username"], ENT_QUOTES, 'UTF-8')."さんは誰もフォローしていません。</p>";
					}
					?>
					<div class="btn_area">
						<input type="button" id="CloseButton5" class="fbtn" value="閉じる">
					</div>
				</div>
			</div>	
		
	</main>



	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>

</body>

<script>
$(document).ready(function() {

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
		var userid = '<?php echo $userid; ?>';
		if(mode == 'allueuse'){
			$.ajax({
				url: '../nextpage/userpage.php', // PHPファイルへのパス
				method: 'GET',
				data: { page: pageNumber, id: uwuzuid ,userid: userid},
				dataType: 'html',
				timeout: 300000,
				success: function(response) {
					$('#postContainer').append(response);
					pageNumber++;
					isLoading = false;
					$("#loading").hide();
				},
				error: function (xhr, textStatus, errorThrown) {  // エラーと判定された場合
					isLoading = false;
					$("#loading").hide();
					$("#error").show();
				},
			});
		}else if(mode == 'mediaueuse'){
			$.ajax({
				url: '../nextpage/usermediapage.php', // PHPファイルへのパス
				method: 'GET',
				data: { page: pageNumber, id: uwuzuid ,userid: userid},
				dataType: 'html',
				timeout: 300000,
				success: function(response) {
					$('#postContainer').append(response);
					pageNumber++;
					isLoading = false;
					$("#loading").hide();
				},
				error: function (xhr, textStatus, errorThrown) {  // エラーと判定された場合
					isLoading = false;
					$("#loading").hide();
					$("#error").show();
				},
			});
		}else if(mode == 'likeueuse'){
			$.ajax({
				url: '../nextpage/userlikepage.php', // PHPファイルへのパス
				method: 'GET',
				data: { page: pageNumber, id: uwuzuid ,userid: userid},
				dataType: 'html',
				timeout: 300000,
				success: function(response) {
					$('#postContainer').append(response);
					pageNumber++;
					isLoading = false;
					$("#loading").hide();
				},
				error: function (xhr, textStatus, errorThrown) {  // エラーと判定された場合
					isLoading = false;
					$("#loading").hide();
					$("#error").show();
				},
			});
		}else{
			$('#all_ueuse_btn').addClass('btmline');
			$.ajax({
				url: '../nextpage/userpage.php', // PHPファイルへのパス
				method: 'GET',
				data: { page: pageNumber, id: uwuzuid ,userid: userid},
				dataType: 'html',
				timeout: 300000,
				success: function(response) {
					$('#postContainer').append(response);
					pageNumber++;
					isLoading = false;
					$("#loading").hide();
				},
				error: function (xhr, textStatus, errorThrown) {  // エラーと判定された場合
					isLoading = false;
					$("#loading").hide();
					$("#error").show();
				},
			});
		}
    }
	
	$("#all_ueuse_btn").on('click',function(event) {
		$('#all_ueuse_btn').addClass('btmline');
		$('#media_ueuse_btn').removeClass('btmline');
		$('#like_ueuse_btn').removeClass('btmline');

		event.preventDefault();
		$("#postContainer").empty();
		pageNumber = 1;
		mode = "allueuse";
		loadPosts();
	});

	$("#media_ueuse_btn").on('click',function(event) {
		$('#media_ueuse_btn').addClass('btmline');
		$('#like_ueuse_btn').removeClass('btmline');
		$('#all_ueuse_btn').removeClass('btmline');

		event.preventDefault();
		$("#postContainer").empty();
		pageNumber = 1;
		mode = "mediaueuse";
		loadPosts();
	});

	$("#like_ueuse_btn").on('click',function(event) {
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

			if($("#error").css('display') == 'block') {
				// えらー処理
				return;
			}else if (elem === null){
				// 存在しない場合の処理
				loadPosts();
			}else{
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
		window.setTimeout(function(){
			modal1.style.display = 'none';
		}, 150);
    });


	
	$(document).on('click', '.favbtn, .favbtn_after', function(event) {

	event.preventDefault();

	var postUniqid = $(this).data('uniqid');
	var userid = '<?php echo $userid; ?>';
	var account_id = '<?php echo $loginid; ?>';
	var likeCountElement = $(this).find('.like-count'); // いいね数を表示する要素

	var isLiked = $(this).hasClass('favbtn_after'); // 現在のいいねの状態を判定

	var $this = $(this); // ボタン要素を変数に格納

	$.ajax({
		url: '../favorite/favorite.php',
		method: 'POST',
		data: { uniqid: postUniqid, userid: userid, account_id: account_id  }, // ここに自分のユーザーIDを指定
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


		var modal = document.getElementById('myDelModal');
		var deleteButton = document.getElementById('deleteButton');
		var cancelButton = document.getElementById('cancelButton'); // 追加
		var modalMain = $('.modal-content');

		$(document).on('click', '.delbtn', function (event) {
		modal.style.display = 'block';
		modalMain.addClass("slideUp");
		modalMain.removeClass("slideDown");

		var uniqid2 = $(this).attr('data-uniqid2');
		var userid = '<?php echo $userid; ?>';
		var account_id = '<?php echo $loginid; ?>';
		var postElement = $(this).closest('.ueuse');

		deleteButton.addEventListener('click', () => {
			modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal.style.display = 'none';
			}, 150);

			$.ajax({
				url: '../delete/delete.php',
				method: 'POST',
				data: { uniqid: uniqid2, userid: userid, account_id: account_id },
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

		$(document).on('click', '.addabi', function (event) {

		abimodal.style.display = 'block';
		modalMain.addClass("slideUp");
		modalMain.removeClass("slideDown");

		var uniqid2 = $(this).attr('data-uniqid2');
		var postAbiElement = $(this).closest('.addabi');

		AbiCancelButton.addEventListener('click', () => {
			modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				abimodal.style.display = 'none';
			}, 150);
		});

		$('#AbiForm').off('submit').on('submit', function (event) {

			event.preventDefault();

			var abitext = document.getElementById("abitexts").value;
			var usernames = '<?php echo $username; ?>';
			var userid = '<?php echo $userid; ?>';
			var account_id = '<?php echo $loginid; ?>';

			if(abitext == ""){
				modalMain.removeClass("slideUp");
				modalMain.addClass("slideDown");
				window.setTimeout(function(){
					abimodal.style.display = 'none';
				}, 150);
			}else{
				$.ajax({
					url: '../abi/addabi.php',
					method: 'POST',
					data: { uniqid: uniqid2, abitext: abitext, username: usernames, userid: userid, account_id: account_id },
					dataType: 'json',
					success: function (response) {
						console.log(response); // レスポンス内容をコンソールに表示
						if (response.success) {
							abimodal.style.display = 'none';
							postAbiElement.remove();
							console.log(response);
						} else {
							abimodal.style.display = 'none';
							postAbiElement.remove();
						}
					},
					error: function (xhr, status, error) {
						console.log(error);
						abimodal.style.display = 'none';
						postAbiElement.remove();
					}
				});
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
			window.setTimeout(function(){
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
			window.setTimeout(function(){
				modal5.style.display = 'none';
			}, 150);
        });
	});

	window.addEventListener('online', function(){
		checkOnline();
	});
	window.addEventListener('offline', function(){
		checkOnline();
	});
	function checkOnline() {
		if( navigator.onLine ) {
			$("#online").show();
			$("#offline").hide();
		} else {
			$("#online").hide();
			$("#offline").show();
		}
	}

});
</script>

</html>