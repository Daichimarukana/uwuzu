<?php
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$badpassfile = "../server/badpass.txt";
$badpass_info = file_get_contents($badpassfile);
$badpass = preg_split("/\r\n|\n|\r/", $badpass_info);

function random_key($moji_cnt = 16){
    return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', $moji_cnt)), 0, $moji_cnt);
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

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist,mail_settings FROM account WHERE userid = :userid");
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

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist,mail_settings FROM account WHERE userid = :userid");
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

//ページ内のみ使用変数-------------------------
$mail_settings = safetext($res["mail_settings"]);
//------------------------------------------
//phpmailer--------------------------------------------
require('../settings_admin/plugin_settings/phpmailer_settings.php');
//------------------------------------------------------
require('../settings_admin/plugin_settings/aiblockwatermark_settings.php');
if( !empty($pdo) ) {
	
	// データベース接続の設定
	$userQuery = $pdo->prepare("SELECT * FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $userid);
	$userQuery->execute();
	$userData = $userQuery->fetch();

	$role = $userData["role"];

	$isAIBlock = val_OtherSettings("isAIBlock", $userData["other_settings"]);
	$isAIBWM = val_OtherSettings("isAIBlockWaterMark", $userData["other_settings"]);

	if(!(empty($userData["encryption_ivkey"]))){
		$view_mailadds = DecryptionUseEncrKey($userData["mailadds"], GenUserEnckey($userData["datetime"]), $userData["encryption_ivkey"]);
	}else{
		$view_mailadds = $userData["mailadds"];
	}

	if(empty($userData["notification_settings"])){
		$notification_settings_list = ["system","favorite","reply","reuse","ueuse","follow","mention","other"];
	}else{
		$notification_settings_list = explode(',', $userData["notification_settings"]);
	}
}



if( !empty($_POST['btn_submit']) ) {

	if(!(empty($_POST['im_bot']))){
		if($_POST['im_bot'] == "on"){
			$saveim_bot = "bot";
		}else{
			$saveim_bot = "none";
		}
	}else{
		$saveim_bot = "none";
	}

	$username = safetext($_POST['username']);

    $mailadds = safetext($_POST['mailadds']);

	if( !empty($_POST['isAIBlock']) ) {
		$new_isAIBlock = safetext($_POST['isAIBlock']);
	}else{
		$new_isAIBlock = "false";
	}

	if($new_isAIBlock === "true"){
		$save_isAIBlock = true;
	}else{
		$save_isAIBlock = false;
	}
	$other_settings_json = val_AddOtherSettings("isAIBlock", $save_isAIBlock, $userData["other_settings"]);

	if( !empty($_POST['isAIBMW']) ) {
		$new_isAIBMW = safetext($_POST['isAIBMW']);
	}else{
		$new_isAIBMW = "false";
	}
	if($new_isAIBMW === "true"){
		$save_isAIBMW = true;
	}else{
		$save_isAIBMW = false;
	}
	$other_settings_json = val_AddOtherSettings("isAIBlockWaterMark", $save_isAIBMW, $other_settings_json);

	if( !empty($_POST['mail_important']) ) {
		$mail_important = safetext($_POST['mail_important']);
	}else{
		$mail_important = "false";
	}
	if(!(empty($mailadds))){
		if(filter_var($mailadds, FILTER_VALIDATE_EMAIL)){
			if($mail_important === "true"){
				$savemail_important = "important";
			}else{
				$savemail_important = "none";
			}

			if(!(empty($userData["encryption_ivkey"]))){
				$userEnckey = GenUserEnckey($userData["datetime"]);
				$enc_mailadds = EncryptionUseEncrKey($mailadds, $userEnckey, $userData["encryption_ivkey"]);
			}else{
				$ivLength = openssl_cipher_iv_length('aes-256-cbc');
				$randomBytes = random_bytes($ivLength);
				$randomhash = hash('sha3-512', $randomBytes);
				$iv = substr($randomhash, 0, $ivLength);

				// トランザクション開始
				$pdo->beginTransaction();

				try {
					// SQL作成
					$stmt = $pdo->prepare("UPDATE account SET encryption_ivkey = :encryption_ivkey WHERE userid = :userid;");
					$stmt->bindParam(':encryption_ivkey', $iv, PDO::PARAM_STR);
					$stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
					$res = $stmt->execute();
					$res = $pdo->commit();

				} catch (Exception $e) {
					$pdo->rollBack();
				}

				if (!($res)) {
					$error_message[] = "アカウント操作に失敗しました(ERROR)";
				}
				$stmt = null;

				$userEnckey = GenUserEnckey($userData["datetime"]);
				$enc_mailadds = EncryptionUseEncrKey($mailadds, $userEnckey, $iv);
			}
		}else{
			$savemail_important = "none";
			$error_message[] = 'メールアドレスが正しい形式ではありません。(MAILADDS_CHECK_DAME)';
		}
	}else{
		$enc_mailadds = "";
	}
	

    $profile = safetext($_POST['profile']);
	if( 1024 < mb_strlen($profile, 'UTF-8') ) {
        $error_message[] = 'プロフィールは1024文字以内で入力してください。(INPUT_OVER_MAX_COUNT)';
    }

	// ユーザーネームの入力チェック
	if( empty($username) ) {
		$error_message[] = '表示名を入力してください。(USERNAME_INPUT_PLEASE)';
	} else {
        // 文字数を確認
        if( 50 < mb_strlen($username, 'UTF-8') ) {
			$error_message[] = 'ユーザーネームは50文字以内で入力してください。(USERNAME_OVER_MAX_COUNT)';
		}
    }

    if( empty($error_message) ) {
		// トランザクション開始
		$pdo->beginTransaction();

		try {
			// SQL作成
			$stmt = $pdo->prepare("UPDATE account SET username = :username, mailadds = :mailadds, profile = :profile, sacinfo = :saveimbot, mail_settings = :mail_settings, other_settings = :other_settings WHERE userid = :userid;");

			// 他の値をセット
			$stmt->bindParam(':username', $username, PDO::PARAM_STR);
			$stmt->bindParam(':mailadds', $enc_mailadds, PDO::PARAM_STR);
			$stmt->bindParam(':profile', $profile, PDO::PARAM_STR);
			$stmt->bindParam(':saveimbot', $saveim_bot, PDO::PARAM_STR);
			$stmt->bindParam(':mail_settings', $savemail_important, PDO::PARAM_STR);
			$stmt->bindParam(':other_settings', $other_settings_json, PDO::PARAM_STR);

			// 条件を指定
			// 以下の部分を適切な条件に置き換えてください
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
			$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
		}

		// プリペアドステートメントを削除
		$stmt = null;
    }
}



if( !empty($_POST['pass_submit']) ) {

	$pass_chk = safetext($_POST['passchk_userid']);
	$password = $_POST['password'];

	if(empty($pass_chk)){
		$error_message[] = 'ユーザーidを入力してください。(USERID_INPUT_PLEASE)';
	}else{
		if(!(preg_match("/^[a-zA-Z0-9_]+$/", $pass_chk))){
            $error_message[] = "IDは半角英数字で作成してください。(「_」は使用可能です。)(USERID_DONT_USE_WORD)";
        }else{
			if(!($pass_chk === $userData["userid"])){
				$error_message[] = 'ユーザーidが不正です。(USERID_CHIGAUYANKE)';
			}
		}
	}

	// パスワードの入力チェック
	if( empty($password) ) {
		$error_message[] = 'パスワードを入力してください。(PASSWORD_INPUT_PLEASE)';
	} else {

        if(in_array($password, $badpass) === true ){
            $error_message[] = "パスワードが弱いです。セキュリティ上変更してください。(PASSWORD_ZEIJAKU)";
        }
        
        if( 4 > mb_strlen($password, 'UTF-8') ) {
			$error_message[] = 'パスワードは4文字以上である必要があります。(PASSWORD_TODOITENAI_MIN_COUNT)';
		}

        // 文字数を確認
        if( 256 < mb_strlen($password, 'UTF-8') ) {
			$error_message[] = 'パスワードは256文字以内で入力してください。(PASSWORD_OVER_MAX_COUNT)';
		}
    }


    if( empty($error_message) ) {
		// トランザクション開始
	$pdo->beginTransaction();
	
	$hashpassword = uwuzu_password_hash($password);

	try {
		// SQL作成
		$stmt = $pdo->prepare("UPDATE account SET password = :password WHERE userid = :userid");

		// 他の値をセット
		$stmt->bindParam(':password', $hashpassword, PDO::PARAM_STR);

		// 条件を指定
		// 以下の部分を適切な条件に置き換えてください
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
		$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
	}

    // プリペアドステートメントを削除
    $stmt = null;
    }
}


if( !empty($_POST['logout']) ) {
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
}

if( !empty($_POST['img1btn_submit']) ) {

    if (!empty($_FILES['image2s']['name'])) {
        // アップロードされたファイル情報
		$uploadedFile = $_FILES['image2s'];

		if(check_mime($uploadedFile['tmp_name'])){
			// アップロードされたファイルの拡張子を取得
			$extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);

			// EXIF削除
			delete_exif($extension, $uploadedFile['tmp_name']);
			// リサイズ
			resizeImage($uploadedFile['tmp_name'], 2048, 1024);

			if(check_mime($uploadedFile['tmp_name']) == "image/webp"){
				// 新しいファイル名を生成（uniqid + 拡張子）
				$newFilename = uniqid() . '-'.$userid.'.webp';
			}else{
				// 新しいファイル名を生成（uniqid + 拡張子）
				$newFilename = uniqid() . '-'.$userid.'.' . $extension;
			}
			// 保存先のパスを生成
			$uploadedPath = 'userheads/' . $newFilename;

			// ファイルを移動
			$result = move_uploaded_file($uploadedFile['tmp_name'], '../'.$uploadedPath);
			
			if ($result) {
				$headName = $uploadedPath; // 保存されたファイルのパスを使用
			} else {
				$errnum = $uploadedFile['error'];
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
			$error_message[] = "使用できない画像形式です。(FILE_UPLOAD_DEKINAKATTA)";
		}
    }else{
		$error_message[] = 'ヘッダー画像を選択してください(PHOTO_SELECT_PLEASE)';
    }
	

    if( empty($error_message) ) {
    // トランザクション開始
    $pdo->beginTransaction();

    try {

				// SQL作成
		$stmt = $pdo->prepare("UPDATE account SET headname = :headname WHERE userid = :userid");

		// ヘッダー画像のバインド
		$stmt->bindValue(':headname', $headName, PDO::PARAM_STR);

		// ユーザーIDのバインド（WHERE句に必要）
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
        $error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
    }

    // プリペアドステートメントを削除
    $stmt = null;
    }
}


if( !empty($_POST['img2btn_submit']) ) {

    if (!empty($_FILES['image']['name'])) {
        // アップロードされたファイル情報
		$uploadedFile = $_FILES['image'];

		if(check_mime($uploadedFile['tmp_name'])){
			// アップロードされたファイルの拡張子を取得
			$extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);

			// EXIF削除
			delete_exif($extension, $uploadedFile['tmp_name']);
			// リサイズ
			resizeImage($uploadedFile['tmp_name'], 512, 512);

			if(check_mime($uploadedFile['tmp_name']) == "image/webp"){
				// 新しいファイル名を生成（uniqid + 拡張子）
				$newFilename = uniqid() . '-'.$userid.'.webp';
			}else{
				// 新しいファイル名を生成（uniqid + 拡張子）
				$newFilename = uniqid() . '-'.$userid.'.' . $extension;
			}
			// 保存先のパスを生成
			$uploadedPath = 'usericons/' . $newFilename;

			// ファイルを移動
			$result = move_uploaded_file($uploadedFile['tmp_name'], '../'.$uploadedPath);
			
			if ($result) {
				$iconName = $uploadedPath; // 保存されたファイルのパスを使用
			} else {
				$errnum = $uploadedFile['error'];
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
			$error_message[] = "使用できない画像形式です。(FILE_UPLOAD_DEKINAKATTA)";
		}
    }else{
		$error_message[] = 'アイコン画像を選択してください(PHOTO_SELECT_PLEASE)';
    }

    if( empty($error_message) ) {
    // トランザクション開始
    $pdo->beginTransaction();

    try {

				// SQL作成
		$stmt = $pdo->prepare("UPDATE account SET iconname = :iconname WHERE userid = :userid");

		// アイコン画像のバインド
		$stmt->bindValue(':iconname', $iconName, PDO::PARAM_STR);

		// ユーザーIDのバインド（WHERE句に必要）
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
        $error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
    }

    // プリペアドステートメントを削除
    $stmt = null;
    }
}

require('../logout/logout.php');

if( !empty($_POST['auth_on_submit']) ) {
	$_SESSION['userid'] = $userid;
    // リダイレクト先のURLへ転送する
    $url = 'addauthcode.php';
    header('Location: ' . $url, true, 303);

    // すべての出力を終了
    exit;
}

if( !empty($_POST['auth_off_submit']) ) {
	if( empty($error_message) ) {
		$secret = "";
		$backupcode = "";
		// トランザクション開始
		$pdo->beginTransaction();
	
		try {
	
					// SQL作成
			$stmt = $pdo->prepare("UPDATE account SET authcode = :authcode,backupcode = :backupcode WHERE userid = :userid");
	
			$stmt->bindValue(':authcode', $secret, PDO::PARAM_STR);
			$stmt->bindValue(':backupcode', $backupcode, PDO::PARAM_STR);
	
			// ユーザーIDのバインド（WHERE句に必要）
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
			$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
		}
	
		// プリペアドステートメントを削除
		$stmt = null;
	}
}


if( !empty($_POST['notification_submit']) ) {
	$New_notification_list = ["system", "other"];

	if(!(empty($_POST['notification_favorite']))){
		if($_POST['notification_favorite'] == "on"){
			$New_notification_list[] = "favorite";
		}
	}

	if(!(empty($_POST['notification_reuse']))){
		if($_POST['notification_reuse'] == "on"){
			$New_notification_list[] = "reuse";
		}
	}

	if(!(empty($_POST['notification_reply']))){
		if($_POST['notification_reply'] == "on"){
			$New_notification_list[] = "reply";
		}
	}

	if(!(empty($_POST['notification_mention']))){
		if($_POST['notification_mention'] == "on"){
			$New_notification_list[] = "mention";
		}
	}

	if(!(empty($_POST['notification_follow']))){
		if($_POST['notification_follow'] == "on"){
			$New_notification_list[] = "follow";
		}
	}

	if( empty($error_message) ) {
		$Save_notification_list = implode(',', array_unique($New_notification_list));

		// トランザクション開始
		$pdo->beginTransaction();
	
		try {
			$stmt = $pdo->prepare("UPDATE account SET notification_settings = :notification_settings WHERE userid = :userid");
			$stmt->bindValue(':notification_settings', $Save_notification_list, PDO::PARAM_STR);
			$stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
	
			$res = $stmt->execute();
	
			// コミット
			$res = $pdo->commit();
		} catch (Exception $e) {
			$pdo->rollBack();
		}
	
		if ($res) {
			$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			header("Location:".$url."");
			exit; 
		} else {
			$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
		}
	
		// プリペアドステートメントを削除
		$stmt = null;
	}
}

// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/home.css">
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<script src="../js/jquery-min.js"></script>
<script src="../js/zxcvbn.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>設定 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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
			<div>
			<h1>プロフィール</h1>
				<div class="hed">
					<img src="<?php echo safetext('../'.$userData['headname']); ?>">
				</div>

				<div class="iconimg">
					<img src="<?php echo safetext('../'.$userData['iconname']); ?>">
				</div>
				<?php if($role === "ice"){?>
					<p>お使いのアカウントは凍結されているため設定を変更できません</p>
				<?php }else{?>

					<label class="imgbtn" for="file_upload">アイコン選択
					<input type="file" id="file_upload" name="image" accept="image/*">
					</label>

					<label class="imgbtn2" for="file_upload2">ヘッダー選択
					<input type="file" id="file_upload2" name="image2s" accept="image/*">
					</label>
					
					<div class="sub">
						<input type="submit" class = "imgbtn" name="img1btn_submit" value="ヘッダー画像更新">
						<input type="submit" class = "imgbtn" name="img2btn_submit" value="アイコン画像更新">
					</div>

					<!--ユーザーネーム関係-->
					<div>
						<p>ユーザーネーム</p>
						<input id="username" placeholder="" class="inbox" type="text" name="username" value="<?php if( !empty($userData['username']) ){ echo safetext( $userData['username']); } ?>">
					</div>
					<div>
						<p>メールアドレス</p>
						<input id="mailadds" type="text" placeholder="" class="inbox" name="mailadds" value="<?php if( !empty($view_mailadds) ){ echo safetext( $view_mailadds); } ?>">
					</div>
					<!--プロフィール関連-->
					<div>
						<p>プロフィール</p>
						<textarea id="profile" type="text" placeholder="" class="inbox" name="profile" value=""><?php if( !empty($userData['profile']) ){ echo safetext( $userData['profile']); } ?></textarea>
					</div>

					<?php if(!empty($userData['token'])){?>

						<p>このアカウントがBotであることを公開する</p>
						<div class="switch_button">
							<input id="im_bot" class="switch_input" type='checkbox' name="im_bot" <?php if($sacinfo === "bot"){?>checked<?php }?>/>
							<label for="im_bot" class="switch_label"></label>
						</div>

					<?php }elseif($userData['token']==='ice'){ ?>
						<p>アカウントが凍結されているためBotであることの設定変更はできません。</p>
					<?php }?>
					
					<?php if(!empty(MAIL_CHKS && MAIL_CHKS == "true")){?>
						<p>重要なお知らせをメールで受信する</p>
						<div class="p2">あなたのアカウントが凍結された際やBANされた際にメールにてお知らせする機能です。<br>利用可能なメールアドレスを事前に設定しておく必要があります。</div>
						<div class="switch_button">
							<?php if(false !== strpos($mail_settings, 'important')) {?>
								<input id="mail_important" class="switch_input" type='checkbox' name="mail_important" value="true" checked/>
								<label for="mail_important" class="switch_label"></label>
							<?php }else{?>
								<input id="mail_important" class="switch_input" type='checkbox' name="mail_important" value="true" />
								<label for="mail_important" class="switch_label"></label>
							<?php }?>
						</div>
					<?php }?>

					<p>AIによる学習を拒否する</p>
					<div class="p2">あなたのプロフィールにAIが訪れた際に、ユーズや画像などのコンテンツを学習しないように要求します。<br>
						これはHTML内にnoaiフラグを含むことで実装されているため、必ずしもすべてのAIがこれに従うとは限りません。<br>
						なお、この機能はまだ確実な動作が保証されないためベータ版です。</div>
					<div class="switch_button">
						<?php if($isAIBlock == true){?>
							<input id="isAIBlock" class="switch_input" type='checkbox' name="isAIBlock" value="true" checked/>
							<label for="isAIBlock" class="switch_label"></label>
						<?php }else{?>
							<input id="isAIBlock" class="switch_input" type='checkbox' name="isAIBlock" value="true" />
							<label for="isAIBlock" class="switch_label"></label>
						<?php }?>
					</div>

					<?php if(!empty(AIBWM_CHK && AIBWM_CHK == "true")){?>
						<p>AI学習防止透かしを自動挿入する</p>
						<div class="p2">画像を添付してユーズした際に自動的に画像の右下に「AI学習禁止」と書かれた透かしを挿入する機能です。<br>
							この機能はまだ確実な動作が確認されていないためベータ版です。<br>
							また、gif、tiffやsvgなどの一部画像形式では挿入されません。</div>
						<div class="switch_button">
							<?php if($isAIBWM == true){?>
								<input id="isAIBMW" class="switch_input" type='checkbox' name="isAIBMW" value="true" checked/>
								<label for="isAIBMW" class="switch_label"></label>
							<?php }else{?>
								<input id="isAIBMW" class="switch_input" type='checkbox' name="isAIBMW" value="true" />
								<label for="isAIBMW" class="switch_label"></label>
							<?php }?>
						</div>
					<?php }?>
								
					<input type="submit" class = "irobutton" name="btn_submit" value="情報更新">

				<?php }?>
			</div>
			<hr>
			<h1>パスワード</h1>
			<div>
                <p>ユーザーid</p>
                <input id="passchk_userid" type="text" class="inbox" name="passchk_userid" oncopy="return false" onpaste="return false" oncontextmenu="return false" value="">
            </div>
			<div>
                <p>新しいパスワード</p>
                <input id="password" type="password" class="inbox" name="password" oncopy="return false" onpaste="return false" oncontextmenu="return false" value="">
				<div id="password_zxcvbn" class="p2" style="display: none;"></div>
				<p>パスワードを表示する</p>
				<div class="switch_button">
					<input id="passview" class="switch_input" type='checkbox' name="passview" value=""/>
					<label for="passview" class="switch_label"></label>
				</div>
			</div>
			

			<input type="submit" class = "irobutton" name="pass_submit" value="パスワード更新">

			<hr>
			<h1>二段階認証</h1>
			<?php 
			if(empty($userData['authcode'])){
			?>
				<p>一時的に有効なキーを生成する二段階認証を設定することにより本人以外がログインしにくくなります。</p>
				<input type="submit" class = "irobutton" name="auth_on_submit" value="二段階認証の設定">
			<?php }else{ ?>
				<p>下のボタンを押すとすぐに解除されます。確認などはありません。気をつけてください。</p>
				<input type="submit" class = "irobutton" name="auth_off_submit" value="二段階認証の解除">
			<?php } ?>

			<hr>
			<h1>通知</h1>

			<p>いいね</p>
			<div class="p2">ユーズがいいねされた時に通知されます。</div>
			<div class="switch_button">
				<input id="notification_favorite" class="switch_input" type='checkbox' name="notification_favorite" <?php if(in_array("favorite",$notification_settings_list)){?>checked<?php }?>/>
				<label for="notification_favorite" class="switch_label"></label>
			</div>

			<p>リユーズ</p>
			<div class="p2">ユーズがリユーズまたは引用リユーズされた時に通知されます。</div>
			<div class="switch_button">
				<input id="notification_reuse" class="switch_input" type='checkbox' name="notification_reuse" <?php if(in_array("reuse",$notification_settings_list)){?>checked<?php }?>/>
				<label for="notification_reuse" class="switch_label"></label>
			</div>

			<p>返信</p>
			<div class="p2">ユーズに返信が来た時に通知されます。</div>
			<div class="switch_button">
				<input id="notification_reply" class="switch_input" type='checkbox' name="notification_reply" <?php if(in_array("reply",$notification_settings_list)){?>checked<?php }?>/>
				<label for="notification_reply" class="switch_label"></label>
			</div>

			<p>メンション</p>
			<div class="p2">メンションされた時に通知されます。</div>
			<div class="switch_button">
				<input id="notification_mention" class="switch_input" type='checkbox' name="notification_mention" <?php if(in_array("mention",$notification_settings_list)){?>checked<?php }?>/>
				<label for="notification_mention" class="switch_label"></label>
			</div>

			<p>フォロー</p>
			<div class="p2">フォローされた時に通知されます。</div>
			<div class="switch_button">
				<input id="notification_follow" class="switch_input" type='checkbox' name="notification_follow" <?php if(in_array("follow",$notification_settings_list)){?>checked<?php }?>/>
				<label for="notification_follow" class="switch_label"></label>
			</div>

			<input type="submit" class = "irobutton" name="notification_submit" value="通知の設定&更新">

        </form>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>
</body>
</html>
<script>
window.addEventListener('DOMContentLoaded', function(){
	$("#passview").click(function () {
		if ($("#passview").prop("checked") == true) {
			$('#password').get(0).type = 'text';
		} else {
			$('#password').get(0).type = 'password';
		}
	});
	$('#password').on('input', function () {
		var safetypass = $('#password').val();
		if(String(safetypass).length > 0){
			$("#password_zxcvbn").show();
			var point = zxcvbn(safetypass);
			if(point.score == 0){
				$("#password_zxcvbn").text("パスワードがめっちゃ弱いです！");
                $("#password_zxcvbn").css('color', 'var(--error)');
			}else if(point.score == 1){
				$("#password_zxcvbn").text("弱いパスワードです！");
                $("#password_zxcvbn").css('color', 'var(--danger)');
			}else if(point.score == 2){
				$("#password_zxcvbn").text("危ないパスワードです！");
                $("#password_zxcvbn").css('color', 'var(--warn)');
			}else if(point.score == 3){
				$("#password_zxcvbn").text("普通のパスワードです");
                $("#password_zxcvbn").css('color', 'var(--good)');
			}else if(point.score == 4){
				$("#password_zxcvbn").text("おめでとうございます！強いパスワードです！");
                $("#password_zxcvbn").css('color', 'var(--success)');
			}
		}else{
			$("#password_zxcvbn").hide();
		}
	});

    $('#file_upload').change(function(e) {
        var file_reader = new FileReader();
        file_reader.addEventListener('load', function(e) {
            $('.iconimg').children('img').attr('src', file_reader.result);
        });
        file_reader.readAsDataURL(e.target.files[0]);
    });

	$('#file_upload2').change(function(e) {
        var file_reader = new FileReader();
        file_reader.addEventListener('load', function(e) {
            $('.hed').children('img').attr('src', file_reader.result);
        });
        file_reader.readAsDataURL(e.target.files[0]);
    });

});
</script>