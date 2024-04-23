<?php
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$mojisizefile = "../server/textsize.txt";
$mojisize = (int)htmlentities(file_get_contents($mojisizefile), ENT_QUOTES, 'UTF-8');

//投稿及び返信レート制限↓(分):デフォで60件/分まで
$max_ueuse_rate_limit = 60;

$banurldomainfile = "../server/banurldomain.txt";
$banurl_info = file_get_contents($banurldomainfile);
$banurl = preg_split("/\r\n|\n|\r/", $banurl_info);

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}
require('../db.php');
//関数呼び出し
//- EXIF
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
session_set_cookie_params(0, '', '', true, true);
session_start();
session_regenerate_id(true);

//------------------------------------------
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
if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] == true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', htmlentities($_SESSION['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_SESSION['loginid'] === $res["loginid"] && $_SESSION['userid'] == $res["userid"]){
	// セッションに値をセット
	$userid = htmlentities($res['userid']); // セッションに格納されている値をそのままセット
	$username = htmlentities($res['username']); // セッションに格納されている値をそのままセット
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
	$passQuery->bindValue(':userid', htmlentities($_COOKIE['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_COOKIE['loginid'] === $res["loginid"] && $_COOKIE['userid'] == $res["userid"]){
	// セッションに値をセット
	$userid = htmlentities($res['userid']); // クッキーから取得した値をセット
	$username = htmlentities($res['username']); // クッキーから取得した値をセット
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

//-----------------URLから取得----------------
if(isset($_GET['text'])) { 
    $ueuse = htmlentities($_GET['text'], ENT_QUOTES, 'UTF-8', false);
}elseif(isset($_COOKIE['ueuse'])) { 
    $ueuse = htmlentities($_COOKIE['ueuse'], ENT_QUOTES, 'UTF-8', false);
}

//-------------------------------------------
function get_mentions_userid($postText) {
    // @useridを検出する
    $usernamePattern = '/@(\w+)/';
    $mentionedUsers = [];

    preg_replace_callback($usernamePattern, function($matches) use (&$mentionedUsers) {
        $mention_username = $matches[1];

        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));
    
        $mention_userQuery = $dbh->prepare("SELECT username, userid FROM account WHERE userid = :userid");
        $mention_userQuery->bindValue(':userid', $mention_username);
        $mention_userQuery->execute();
        $mention_userData = $mention_userQuery->fetch();   
        
        if (!empty($mention_userData)) {
            $mentionedUsers[] = $mention_username;
        }
    }, $postText);

    return $mentionedUsers;
}

if( !empty($_POST['btn_submit']) ) {
	$ueuse = htmlentities($_POST['ueuse'], ENT_QUOTES, 'UTF-8', false);

	if(isset($_POST['nsfw_chk'])){
		$nsfw_chk = htmlentities($_POST['nsfw_chk'], ENT_QUOTES, 'UTF-8', false);
	}else{
		$nsfw_chk = "false";
	}

	if($nsfw_chk === "true"){
		$save_nsfw = "true";
	}else{
		$save_nsfw = "false";
	}

	// メッセージの入力チェック
	if( empty($ueuse) ) {
		$error_message[] = '内容を入力してください。(INPUT_PLEASE)';
	} else {
        // 文字数を確認
        if( (int)htmlentities(file_get_contents($mojisizefile), ENT_QUOTES, 'UTF-8', false) < mb_strlen($ueuse, 'UTF-8') ) {
			$error_message[] = '内容は'.htmlentities(file_get_contents($mojisizefile), ENT_QUOTES, 'UTF-8', false).'文字以内で入力してください。(INPUT_OVER_MAX_COUNT)';
		}

		// 禁止url確認
		for($i = 0; $i < count($banurl); $i++) {
			if(!($banurl[$i] == "")){
				if (false !== strpos($ueuse, 'https://'.$banurl[$i])) {
					$error_message[] = '投稿が禁止されているURLが含まれています。(INPUT_CONTAINS_PROHIBITED_URL)';
				}
			}
		}

    }

	$old_datetime = date("Y-m-d H:i:00");
	$now_datetime = date("Y-m-d H:i:00",strtotime("+1 minute"));
	$rate_Query = $pdo->prepare("SELECT * FROM ueuse WHERE account = :userid AND TIME(datetime) BETWEEN :old_datetime AND :now_datetime");
	$rate_Query->bindValue(':userid', $userid);
	$rate_Query->bindValue(':old_datetime', $old_datetime);
	$rate_Query->bindValue(':now_datetime', $now_datetime);
	$rate_Query->execute();
	$rate_count = $rate_Query->rowCount();
	if(!($rate_count > $max_ueuse_rate_limit-1)){
		if (empty($_FILES['upload_images']['name'])) {
			$photo1 = "none";
		} else {
			// アップロードされたファイル情報
			$uploadedFile = $_FILES['upload_images'];

			if(check_mime($uploadedFile['tmp_name'])){
				// アップロードされたファイルの拡張子を取得
				$extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
				
				// 新しいファイル名を生成（uniqid + 拡張子）
				$newFilename = uniqid() . '-'.$userid.'.' . $extension;
				
				// 保存先のパスを生成
				$uploadedPath = '../ueuseimages/' . $newFilename;

				// EXIF削除
				delete_exif($extension, $uploadedFile['tmp_name']);
				
				// ファイルを移動
				$result = move_uploaded_file($uploadedFile['tmp_name'], $uploadedPath);
				
				if ($result) {
					$photo1 = $uploadedPath; // 保存されたファイルのパスを使用
				} else {
					$errnum = $uploadedFile['error'];
					if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
					if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
					if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
					if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
					if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
					if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
					if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
					$error_message[] = 'アップロード失敗！(1)エラーコード：' .$errcode.'';
				}
			}else{
				$error_message[] = "使用できない画像形式です。(SORRY_FILE_HITAIOU)";
			}
		}

		if (empty($_FILES['upload_images2']['name'])) {
			$photo2 = "none";
		} else {

			if (empty($_FILES['upload_images']['name'])){
				$error_message[] = '画像1から画像を選択してください！！！(PHOTO_SELECT_PLEASE)';
			}
			// アップロードされたファイル情報
			$uploadedFile2 = $_FILES['upload_images2'];

			if(check_mime($uploadedFile2['tmp_name'])){
				// アップロードされたファイルの拡張子を取得
				$extension2 = pathinfo($uploadedFile2['name'], PATHINFO_EXTENSION);
				
				// 新しいファイル名を生成（uniqid + 拡張子）
				$newFilename2 = uniqid() . '-'.$userid.'.' . $extension2;
				
				// 保存先のパスを生成
				$uploadedPath2 = '../ueuseimages/' . $newFilename2;
				
				// EXIF削除
				delete_exif($extension, $uploadedFile2['tmp_name']);

				// ファイルを移動
				$result2 = move_uploaded_file($uploadedFile2['tmp_name'], $uploadedPath2);
				
				if ($result2) {
					$photo2 = $uploadedPath2; // 保存されたファイルのパスを使用
				} else {
					$errnum = $uploadedFile2['error'];
					if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
					if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
					if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
					if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
					if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
					if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
					if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
					$error_message[] = 'アップロード失敗！(2)エラーコード：' .$errcode.'';
				}
			}else{
				$error_message[] = "使用できない画像形式です。(SORRY_FILE_HITAIOU)";
			}
		}

		if (empty($_FILES['upload_images3']['name'])) {
			$photo3 = "none";
		} else {

			if (empty($_FILES['upload_images2']['name'])){
				$error_message[] = '画像2から画像を選択してください！！！(PHOTO_SELECT_PLEASE)';
			}
			// アップロードされたファイル情報
			$uploadedFile3 = $_FILES['upload_images3'];

			if(check_mime($uploadedFile3['tmp_name'])){
				// アップロードされたファイルの拡張子を取得
				$extension3 = pathinfo($uploadedFile3['name'], PATHINFO_EXTENSION);
				
				// 新しいファイル名を生成（uniqid + 拡張子）
				$newFilename3 = uniqid() . '-'.$userid.'.' . $extension3;
				
				// 保存先のパスを生成
				$uploadedPath3 = '../ueuseimages/' . $newFilename3;

				// EXIF削除
				delete_exif($extension3, $uploadedFile3['tmp_name']);

				// ファイルを移動
				$result3 = move_uploaded_file($uploadedFile3['tmp_name'], $uploadedPath3);
				
				if ($result3) {
					$photo3 = $uploadedPath3; // 保存されたファイルのパスを使用
				} else {
					$errnum = $uploadedFile3['error'];
					if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
					if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
					if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
					if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
					if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
					if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
					if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
					$error_message[] = 'アップロード失敗！(3)エラーコード：' .$errcode.'';
				}
			}else{
				$error_message[] = "使用できない画像形式です。(SORRY_FILE_HITAIOU)";
			}
		}

		if (empty($_FILES['upload_images4']['name'])) {
			$photo4 = "none";
		} else {

			if (empty($_FILES['upload_images3']['name'])){
				$error_message[] = '画像3から画像を選択してください！！！(PHOTO_SELECT_PLEASE)';
			}
			// アップロードされたファイル情報
			$uploadedFile4 = $_FILES['upload_images4'];

			if(check_mime($uploadedFile4['tmp_name'])){
				// アップロードされたファイルの拡張子を取得
				$extension4 = pathinfo($uploadedFile4['name'], PATHINFO_EXTENSION);
				
				// 新しいファイル名を生成（uniqid + 拡張子）
				$newFilename4 = uniqid() . '-'.$userid.'.' . $extension4;
				
				// 保存先のパスを生成
				$uploadedPath4 = '../ueuseimages/' . $newFilename4;

				// EXIF削除
				delete_exif($extension4, $uploadedFile4['tmp_name']);

				// ファイルを移動
				$result4 = move_uploaded_file($uploadedFile4['tmp_name'], $uploadedPath4);
				
				if ($result4) {
					$photo4 = $uploadedPath4; // 保存されたファイルのパスを使用
				} else {
					$errnum = $uploadedFile4['error'];
					if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
					if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
					if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
					if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
					if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
					if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
					if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
					$error_message[] = 'アップロード失敗！(4)エラーコード：' .$errcode.'';
				}
			}else{
				$error_message[] = "使用できない画像形式です。(SORRY_FILE_HITAIOU)";
			}
		}

		if (empty($_FILES['upload_videos1']['name'])) {
			$video1 = "none";
		} else {
			// アップロードされたファイル情報
			$uploadedVideo = $_FILES['upload_videos1'];
			
			// アップロードされたファイルの拡張子を取得
			$extensionVideo = strtolower(pathinfo($uploadedVideo['name'], PATHINFO_EXTENSION)); // 小文字に変換

			if(check_mime_video($uploadedVideo['tmp_name'])){
				// 正しい拡張子の場合、新しいファイル名を生成
				$newFilenameVideo = uniqid() . '-'.$userid.'.' . $extensionVideo;
				// 保存先のパスを生成
				$uploadedPathVideo = '../ueusevideos/' . $newFilenameVideo;
			
				// ファイルを移動
				$resultVideo = move_uploaded_file($uploadedVideo['tmp_name'], $uploadedPathVideo);
			
				if ($resultVideo) {
					$video1 = $uploadedPathVideo; // 保存されたファイルのパスを使用
				} else {
					$errnum = $uploadedVideo['error'];
					if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
					if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
					if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
					if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
					if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
					if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
					if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
					$error_message[] = 'アップロード失敗！(5)エラーコード：' .$errcode.'';
				}
			} else {
				$error_message[] = '対応していないファイル形式です！(SORRY_FILE_HITAIOU)';
			}
			
			
		}

		if( empty($error_message) ) {
			//一時保存していたユーズ内容の削除
			setcookie("ueuse", "", time() - 30);
			
			// 書き込み日時を取得
			$datetime = date("Y-m-d H:i:s");
			$uniqid = createUniqId();
			$abi = "none";

			// トランザクション開始
			$pdo->beginTransaction();

			try {

				// SQL作成
				$stmt = $pdo->prepare("INSERT INTO ueuse (username, account, uniqid, ueuse, photo1, photo2, photo3, photo4, video1, datetime, abi, nsfw) VALUES (:username, :account, :uniqid, :ueuse, :photo1, :photo2, :photo3, :photo4, :video1, :datetime, :abi, :nsfw)");
		
				$stmt->bindParam(':username', htmlentities($username, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
				$stmt->bindParam(':account', htmlentities($userid, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
				$stmt->bindParam(':uniqid', htmlentities($uniqid, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
				$stmt->bindParam(':ueuse', htmlentities($ueuse, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);

				$stmt->bindParam(':photo1', htmlentities($photo1, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
				$stmt->bindParam(':photo2', htmlentities($photo2, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
				$stmt->bindParam(':photo3', htmlentities($photo3, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
				$stmt->bindParam(':photo4', htmlentities($photo4, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
				$stmt->bindParam(':video1', htmlentities($video1, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);
				$stmt->bindParam(':datetime', htmlentities($datetime, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);

				$stmt->bindParam(':nsfw', htmlentities($save_nsfw, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);

				$stmt->bindParam(':abi', htmlentities($abi, ENT_QUOTES, 'UTF-8', false), PDO::PARAM_STR);

				// SQLクエリの実行
				$res = $stmt->execute();

				// コミット
				$res = $pdo->commit();

				$mentionedUsers = array_unique(get_mentions_userid($ueuse));

				foreach ($mentionedUsers as $mentionedUser) {
					send_notification($mentionedUser,$userid,"".$userid."さんにメンションされました！",$ueuse,"/!".$uniqid."");
				}

			} catch(Exception $e) {

				// エラーが発生した時はロールバック
				$pdo->rollBack();
			}

			if( $res ) {
				$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];;
				header("Location:".$url."");
				exit;  
			} else {
				$error_message[] = $e->getMessage();
			}

			// プリペアドステートメントを削除
			$stmt = null;
		}
	}else{
		$error_message[] = "投稿回数のレート制限を超過しています。(OVER_RATE_LIMIT)";
	}
}



require('../logout/logout.php');



// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<script src="../js/nsfw_event.js"></script>
<link rel="manifest" href="../manifest/manifest.json" />
<script>
if ("serviceWorker" in navigator) {
	navigator.serviceWorker.register("../sw.js").then(reg => {
		console.log("ServiceWorker OK", reg);
	}).catch(err => {
		console.log("ServiceWorker BAD", err);
	});
}
</script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<link rel="stylesheet" href="../css/home.css">
<title>ローカルタイムライン - <?php echo htmlentities($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8', false);?></title>

</head>

<body>

	<div>
		<div id="new_ueuse" class="new_ueuse" style="display:none;">
			<a onclick="window.location.reload(true);"><p>🍊新しいユーズがあります！</p></a>
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

	<?php require('../require/leftbox.php');?>
	
	<main class="outer">
		<?php if(empty($_COOKIE['event'])){
			  if (date("md") == "0101") {?>
			<div class="hny" id="osho_gats">
				<div class="top">Happy New Year <?php echo date("Y")?> !!!</div>
				<div class="textmain">
					<h1>あけましておめでとうございます！</h1>
					<p>あけましておめでとうございます<br>今日から<?php echo date("Y年")?>ですね～！<br>今年もどうぞuwuzuをよろしくお願いいたします！</p>
					<p><script type="text/javascript">
					rand = Math.floor(Math.random()*8);
										
					if (rand == 0) msg = "早速ですが年越しジャンプしました？";
					if (rand == 1) msg = "早速ですがお餅は食べましたか？";
					if (rand == 2) msg = "お餅を喉に詰まらせないよう気をつけてくださいね～";
					if (rand == 3) msg = "福袋とか買いましたか～？";
					if (rand == 4) msg = "やっぱりこたつでゆっくりしたいね...";
					if (rand == 5) msg = "みかんでも食べます？";
					if (rand == 6) msg = "お鍋でもどうですか～？";
					if (rand == 7) msg = "一生こたつにいたい...";
											
					document.write(msg);
					</script></p>
					<div class="rp"><?php echo date("Y年n月j日")?></div>
					<button id="os_exit_btn" class="ueusebtn">とじる</button>
				</div>
			</div>
			<?php }?>
		<?php }?>

		<div class="tlchange">
				<a href="index" class="on">ローカル</a>
				<a href="ftl" class="off">フォロー</a>
		</div>
		<?php if( !empty($error_message) ): ?>
			<ul class="errmsg">
				<?php foreach( $error_message as $value ): ?>
					<p>・ <?php echo $value; ?></p>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if(!($role ==="ice")){?>
			<form method="post" enctype="multipart/form-data">
				<div class="sendbox">
					<textarea id="ueuse" placeholder="いまどうしてる？" name="ueuse"><?php if( !empty($ueuse) ){ echo htmlentities($ueuse, ENT_QUOTES, 'UTF-8', false); } ?></textarea>

					<div class="fxbox">
						<label for="upload_images" id="images" title="画像1">
						<svg><use xlink:href="../img/sysimage/image_1.svg#image"></use></svg>
						<input type="file" name="upload_images" id ="upload_images" accept="image/*">
						</label>
						<label for="upload_images2" id="images2" style="display: none" title="画像2">
						<svg><use xlink:href="../img/sysimage/image_1.svg#image"></use></svg>
						<input type="file" name="upload_images2" id ="upload_images2" accept="image/*">
						</label>
						<label for="upload_images3" id="images3" style="display: none" title="画像3">
						<svg><use xlink:href="../img/sysimage/image_1.svg#image"></use></svg>
						<input type="file" name="upload_images3" id ="upload_images3" accept="image/*">
						</label>
						<label for="upload_images4" id="images4" style="display: none" title="画像4">
						<svg><use xlink:href="../img/sysimage/image_1.svg#image"></use></svg>
						<input type="file" name="upload_images4" id ="upload_images4" accept="image/*">
						</label>
						<label for="upload_videos1" id="videos1" title="動画1">
						<svg><use xlink:href="../img/sysimage/video_1.svg#video"></use></svg>
						<input type="file" name="upload_videos1" id ="upload_videos1" accept="video/*">
						</label>

						<div class="nsfw_button">
							<input id="nsfw_chk" class="nsfw_input" type='checkbox' name="nsfw_chk" value="true"/>
							<label for="nsfw_chk" class="nsfw_label" title="投稿をNSFW指定にする"><svg><use xlink:href="../img/sysimage/eye_1.svg#eye"></use></svg></label>
						</div>

						
						<label for="emoji_picker_btn" title="カスタム絵文字">
						<svg><use xlink:href="../img/sysimage/menuicon/emoji.svg#emoji"></use></svg>
						<input id="emoji_picker_btn" type='checkbox' value="false" style="display:none;"/>
						</label>

						<div class="moji_cnt" id="moji_cnt"><?php echo htmlentities($mojisize, ENT_QUOTES, 'UTF-8', false); ?></div>

						<input type="submit" class="ueusebtn" id='ueusebtn' name="btn_submit" value="ユーズする">
					</div>

					<div class="emoji_picker" id="emoji_picker" style="display:none;">
						<p>カスタム絵文字</p>
						<div class="emoji_picker_flex">
							
						</div>
					</div>
				</div>
			</form>
		<?php }?>

		<section class="inner">
			<div id="postContainer">
				

			</div>
		</section>

		<div id="loading" class="loading" style="display: none;">
			🤔
		</div>
		<div id="error" class="error" style="display: none;">
			<h1>エラー</h1>
			<p>サーバーの応答がなかったか不完全だったようです。<br>ネットワークの接続が正常かを確認の上再読み込みしてください。<br>(NETWORK_HUKANZEN_STOP)</p>
		</div>

	</main>

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
			<h1>ユーズに追記しますか？</h1>
			<p>※追記は削除出来ません。</p>
			<form method="post" id="AbiForm">
			<textarea id="abitexts" placeholder="なに追記する～？" name="abi"><?php if( !empty($_SESSION['abi']) ){ echo htmlentities( $_SESSION['abi'], ENT_QUOTES, 'UTF-8', false); } ?></textarea>
			<div class="btn_area">
				<input type="submit" id="AbiAddButton" class="fbtn_no" name="abi" value="追記">
				<input type="button" id="AbiCancelButton" class="fbtn" value="キャンセル">
			</div>
			</form>
		</div>
	</div>

	<div id="Big_ImageModal" class="Image_modal">
		<div class="modal-content">
			<img id="Big_ImageMain" href="">
		</div>
	</div>


	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>

</body>

<script>
$(document).ready(function() {

	loadPosts();

    var pageNumber = 1;
    var isLoading = false;

    function loadPosts() {
        if (isLoading) return;
        isLoading = true;
		$("#loading").show();
		var userid = '<?php echo $userid; ?>';
		var account_id = '<?php echo $loginid; ?>';
        $.ajax({
            url: '../nextpage/nextpage.php', // PHPファイルへのパス
            method: 'GET',
            data: { page: pageNumber, userid: userid , account_id: account_id },
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

	$('.outer').on('scroll', function() {
		var innerHeight = $('.inner').innerHeight(), //内側の要素の高さ
			outerHeight = $('.outer').innerHeight(), //外側の要素の高さ
			outerBottom = innerHeight - outerHeight; //内側の要素の高さ - 外側の要素の高さ
		if (outerBottom <= $('.outer').scrollTop()) {
			var elem = document.getElementById("noueuse");

			if (elem === null){
				// 存在しない場合の処理
				loadPosts();
			} else {
				// 存在する場合の処理
				return;
			}
		}
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


	$(document).on('click', '.bookmark, .bookmark_after', function(event) {

		event.preventDefault();

		var postUniqid = $(this).data('uniqid');
		var userid = '<?php echo $userid; ?>';
		var account_id = '<?php echo $loginid; ?>';
		var likeCountElement = $(this).find('.like-count'); // いいね数を表示する要素

		var isLiked = $(this).hasClass('bookmark_after'); // 現在のいいねの状態を判定

		var $this = $(this); // ボタン要素を変数に格納

		$.ajax({
			url: '../bookmark/bookmark.php',
			method: 'POST',
			data: { uniqid: postUniqid, userid: userid, account_id: account_id  }, // ここに自分のユーザーIDを指定
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

	$(document).on('click', '.share', function (event) {

		var domain = "<?php echo $domain;?>";
		var share_uniqid = $(this).attr('data-uniqid');
		var share_userid = $(this).attr('data-userid');

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

	var osho_gats = document.getElementById('osho_gats');
	$('#os_exit_btn').on('click', function() {
		document.cookie = "event=done; max-age=86400";
		osho_gats.style.display = 'none';
	});

	var now_time = new Date().toUTCString();
	setInterval(() => {
		
		var userid = '<?php echo $userid; ?>';
		var account_id = '<?php echo $loginid; ?>';
		$.ajax({
			url: '../nextpage/newueuse_chk.php',
			method: 'POST',
			data: { loading_dt: now_time, userid: userid, account_id: account_id  }, // ここに自分のユーザーIDを指定
			dataType: 'json',
			timeout: 300000,
			success: function(response) {
				if (response.success) {
					$("#new_ueuse").show();
				} else {
					$("#new_ueuse").hide();
				}
			}.bind(this), // コールバック内でthisが適切な要素を指すようにbindする
			error: function(e) {
				$("#new_ueuse").hide();
			}
		});
	}, 60000);

	//----------------------------------------------------------------------------------------------------------------------
	//-------------------------------------------------------send_box-------------------------------------------------------
	//----------------------------------------------------------------------------------------------------------------------
	document.getElementById("upload_videos1").addEventListener('change', function(e){
		var file_reader = new FileReader();
		// ファイルの読み込みを行ったら実行
		file_reader.addEventListener('load', function(e) {
			$('#videos1').addClass('label_set');
		});
		file_reader.readAsText(e.target.files[0]);
	});
	document.getElementById("upload_images4").addEventListener('change', function(e){
	var file_reader = new FileReader();
	// ファイルの読み込みを行ったら実行
	file_reader.addEventListener('load', function(e) {
		$('#images4').addClass('label_set');
	});
	file_reader.readAsText(e.target.files[0]);
	});

	document.getElementById("upload_images3").addEventListener('change', function(e){
	var file_reader = new FileReader();
	// ファイルの読み込みを行ったら実行
	file_reader.addEventListener('load', function(e) {
		$('#images3').addClass('label_set');
		$("#images4").show();
	});
	file_reader.readAsText(e.target.files[0]);
	});

	document.getElementById("upload_images2").addEventListener('change', function(e){
	var file_reader = new FileReader();
	// ファイルの読み込みを行ったら実行
	file_reader.addEventListener('load', function(e) {
		$('#images2').addClass('label_set');
		$("#images3").show();
	});
	file_reader.readAsText(e.target.files[0]);
	});
	document.getElementById("upload_images").addEventListener('change', function(e){
	var file_reader = new FileReader();
	// ファイルの読み込みを行ったら実行
	file_reader.addEventListener('load', function(e) {
		$('#images').addClass('label_set');
		$("#images2").show();
	});
	file_reader.readAsText(e.target.files[0]);
	});

	$('#ueuse').on('input', function () {
		var mojisize = '<?php echo $mojisize; ?>';
		var mojicount = Number(mojisize) - $(this).val().length;
		if(mojicount >= 0){
			$('#moji_cnt').removeClass('red');
			$('#moji_cnt').html(mojicount);
			$('#ueusebtn').prop('disabled', false);
		}else{
			$('#moji_cnt').addClass('red');
			$('#moji_cnt').html(mojicount);
			$('#ueusebtn').prop('disabled', true);
		}
		document.cookie = "ueuse=" + encodeURIComponent($(this).val()) + "; Secure; SameSite=Lax; path=/home;";
	});
	loadEmojis();
	$("#emoji_picker_btn").click(function () {
		if ($("#emoji_picker_btn").prop("checked") == true) {
			$("#emoji_picker").show();
		} else {
			$("#emoji_picker").hide();
		}
	});
	$('.emoji_picker').on('scroll', function() {
		var innerHeight = $('.emoji_picker_flex').innerHeight(),
			outerHeight = $('.emoji_picker').innerHeight(),
			outerBottom = innerHeight - outerHeight;
		if (outerBottom <= $('.emoji_picker').scrollTop()) {
			if ($('#noemoji').length){
				return;
			} else {
				loadEmojis();
			}
		}
	});
	var Emoji_pageNumber = 1;
	var isLoading = false;
	function loadEmojis() {
		if (isLoading) return;
		isLoading = true;

		var userid = '<?php echo $userid; ?>';
		var account_id = '<?php echo $loginid; ?>';
		var search_query = '';
		var viewmode = 'picker'
		$.ajax({
			url: '../nextpage/emojiview.php', // PHPファイルへのパス
			method: 'GET',
			data: { page: Emoji_pageNumber, userid: userid , account_id: account_id , search_query: search_query, view_mode: viewmode},
			dataType: 'html',
			timeout: 300000,
			success: function(response) {
				$('.emoji_picker_flex').append(response);
				Emoji_pageNumber++;
				isLoading = false;
				if($("#error").length){
					$("#error").hide();
				}
				
				EmojiClickEvent();
			},
			error: function (xhr, textStatus, errorThrown) {  // エラーと判定された場合
				isLoading = false;
				$("#error").show();
				EmojiClickEvent();
			},
		});
	}
	function EmojiClickEvent() {
		$(".one_emoji").click(function (event) {
			event.preventDefault();
			var children = $(this).children("img");
			var custom_emojiname = children.attr("title");
			$("#ueuse").val($("#ueuse").val() + custom_emojiname);
		});
	}
});
</script>
</html>