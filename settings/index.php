<?php
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}

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
session_set_cookie_params(0, '', '', true, true);
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

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist,mail_settings FROM account WHERE userid = :userid");
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

//ページ内のみ使用変数-------------------------
$mail_settings = htmlentities($res["mail_settings"]);
//------------------------------------------
//phpmailer--------------------------------------------
require('../settings_admin/plugin_settings/phpmailer_settings.php');
//------------------------------------------------------
if( !empty($pdo) ) {
	
	// データベース接続の設定
	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	));

	$userQuery = $dbh->prepare("SELECT username, userid, profile, role, token FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $userid);
	$userQuery->execute();
	$userData = $userQuery->fetch();

	$role = $userData["role"];

	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

	$rerole = $dbh->prepare("SELECT username, userid, password, mailadds, profile, iconname, headname, role, datetime, authcode FROM account WHERE userid = :userid");

    $rerole->bindValue(':userid', $userid);
    // SQL実行
    $rerole->execute();

    $userdata = $rerole->fetch(); // ここでデータベースから取得した値を $role に代入する
	
}



if( !empty($_POST['btn_submit']) ) {

	if( !empty($_POST['im_bot']) ) {
		$im_bot = $_POST['im_bot'];
	}else{
		$im_bot = "false";
	}
	if($im_bot === "true"){
		$saveim_bot = "bot";
	}else{
		$saveim_bot = "none";
	}

    // 空白除去
	$username = $_POST['username'];

    $mailadds = $_POST['mailadds'];

	if( !empty($_POST['mail_important']) ) {
		$mail_important = $_POST['mail_important'];
	}else{
		$mail_important = "false";
	}
	if($mail_important === "true"){
		if(filter_var($mailadds, FILTER_VALIDATE_EMAIL)){
			$savemail_important = "important";
		}else{
			$savemail_important = "none";
			$error_message[] = 'メールアドレスが無効なため重要なお知らせをメールで受信する機能は使用できません。(MAILADDS_CHECK_DAME)';
		}
	}else{
		$savemail_important = "none";
	}

    $profile = $_POST['profile'];

    $options = array(
        // SQL実行失敗時に例外をスルー
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // デフォルトフェッチモードを連想配列形式に設定
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // バッファードクエリを使う（一度に結果セットを全て取得し、サーバー負荷を軽減）
        // SELECTで得た結果に対してもrowCountメソッドを使えるようにする
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    );

    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);


    $query = $dbh->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

    $query->execute(array(':userid' => $userid));

    $result = $query->fetch();


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
	$hashpassword = password_hash($password, PASSWORD_DEFAULT);

	try {
		// SQL作成
		$stmt = $pdo->prepare("UPDATE account SET username = :username, mailadds = :mailadds, profile = :profile, sacinfo = :saveimbot, mail_settings = :mail_settings WHERE userid = :userid;");

		// 他の値をセット
		$stmt->bindParam(':username', $username, PDO::PARAM_STR);
		$stmt->bindParam(':mailadds', $mailadds, PDO::PARAM_STR);
		$stmt->bindParam(':profile', $profile, PDO::PARAM_STR);
		$stmt->bindParam(':saveimbot', $saveim_bot, PDO::PARAM_STR);
		$stmt->bindParam(':mail_settings', $savemail_important, PDO::PARAM_STR);

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

	$pass_chk = htmlentities($_POST['passchk_userid']);
	$password = $_POST['password'];

	$hashpassword = password_hash($password, PASSWORD_DEFAULT);

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

	// ユーザーネームの入力チェック
	if( empty($password) ) {
		$error_message[] = '新しいパスワードを入力してください。(PASSWORD_NEW_INPUT_PLEASE)';
	} else {

		$weakPasswords = array(
            "password",
            "123456",
            "123456789",
            "12345",
            "12345678",
            "123123",
            "1234567890",
            "1234567",
            "1q2w3e",
            "qwerty123",
            "aa12345678",
            "password1",
            "1234",
            "qwertyuiop",
            "123321",
            "12321",
            "qwertyui",
            "abcd1234",
            "zaq12wsx",
            "1q2w3e4r",
            "qwer1234",
            "sakura",
            "asdf1234",
            "asdfghjkl",
            "asdfghjk",
            "member",
            "1qaz2wsx",
            "doraemon",
            "makoto",
            "takeshi",
            "machi1",
            "machida",
            "machida1",
            "tokyo",
            "arashi",
            "dropbox",
            "twitter",
            "elonmusk",
            "xcorp",
            "1234qwer",
            "japan",
            "nippon",
            "tukareta",
            "tweet",
            "discord",
            "misskey",
            "qwerty",
            "123456789",
            "abc123",
            "password123",
            "admin",
            "letmein",
            "iloveyou",
            "111111",
            "12345678910",
            "user",
            "root",
            "system",
            // 他にも弱いパスワードを追加できます
        );
        
        function isWeakPassword($passwords) {
            global $weakPasswords;
            return in_array($passwords, $weakPasswords);
        }

        // テスト用のパスワード（実際にはユーザー入力などから取得することになります。

        if (isWeakPassword($password)) {
            $error_message[] = "パスワードが弱いです。セキュリティ上変更してください。(PASSWORD_ZEIJAKU)";
        } else {
            
        }
        // 文字数を確認
        if( 256 < mb_strlen($password, 'UTF-8') ) {
			$error_message[] = 'パスワードは256文字以内で入力してください。(PASSWORD_OVER_MAX_COUNT)';
		}

		if( 4 > mb_strlen($password, 'UTF-8') ) {
			$error_message[] = 'パスワードは4文字以上である必要があります。(PASSWORD_TODOITENAI_MIN_COUNT)';
		}
    }


    if( empty($error_message) ) {
		// トランザクション開始
	$pdo->beginTransaction();
	$hashpassword = password_hash($password, PASSWORD_DEFAULT);

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
			
			// 新しいファイル名を生成（uniqid + 拡張子）
			$newFilename = uniqid() . '-'.$userid.'.' . $extension;
			
			// 保存先のパスを生成
			$uploadedPath = 'userheads/' . $newFilename;

			// EXIF削除
			delete_exif($extension, $uploadedFile['tmp_name']);

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

    $options = array(
        // SQL実行失敗時に例外をスルー
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // デフォルトフェッチモードを連想配列形式に設定
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // バッファードクエリを使う（一度に結果セットを全て取得し、サーバー負荷を軽減）
        // SELECTで得た結果に対してもrowCountメソッドを使えるようにする
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    );

    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);


    $query = $dbh->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

    $query->execute(array(':userid' => $userid));

    $result = $query->fetch();


	

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
			
			// 新しいファイル名を生成（uniqid + 拡張子）
			$newFilename = uniqid() . '-'.$userid.'.' . $extension;
			
			// 保存先のパスを生成
			$uploadedPath = 'usericons/' . $newFilename;

			// EXIF削除
			delete_exif($extension, $uploadedFile['tmp_name']);

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


    $options = array(
        // SQL実行失敗時に例外をスルー
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // デフォルトフェッチモードを連想配列形式に設定
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // バッファードクエリを使う（一度に結果セットを全て取得し、サーバー負荷を軽減）
        // SELECTで得た結果に対してもrowCountメソッドを使えるようにする
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    );

    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);


    $query = $dbh->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

    $query->execute(array(':userid' => $userid));

    $result = $query->fetch();


	

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
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="../js/jquery-min.js"></script>
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>設定 - <?php echo htmlentities($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>

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
					<img src="<?php echo htmlentities('../'.$userdata['headname']); ?>">
				</div>

				<div class="iconimg">
					<img src="<?php echo htmlentities('../'.$userdata['iconname']); ?>">
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
						<input id="username" placeholder="" class="inbox" type="text" name="username" value="<?php if( !empty($userdata['username']) ){ echo htmlentities( $userdata['username'], ENT_QUOTES, 'UTF-8'); } ?>">
					</div>
					<div>
						<p>メールアドレス</p>
						<input id="mailadds" type="text" placeholder="" class="inbox" name="mailadds" value="<?php if( !empty($userdata['mailadds']) ){ echo htmlentities( $userdata['mailadds'], ENT_QUOTES, 'UTF-8'); } ?>">
					</div>
					<!--プロフィール関連-->
					<div>
						<p>プロフィール</p>
						<textarea id="profile" type="text" placeholder="" class="inbox" name="profile" value=""><?php if( !empty($userdata['profile']) ){ echo htmlentities( $userdata['profile'], ENT_QUOTES, 'UTF-8'); } ?></textarea>
					</div>

					<?php if(!empty($userData['token'])){?>

						<p>このアカウントがBotであることを公開する</p>
						<div class="switch_button">
							<?php if($sacinfo === "bot"){?>
								<input id="im_bot" class="switch_input" type='checkbox' name="im_bot" value="true" checked/>
								<label for="im_bot" class="switch_label"></label>
							<?php }else{?>
								<input id="im_bot" class="switch_input" type='checkbox' name="im_bot" value="true" />
								<label for="im_bot" class="switch_label"></label>
							<?php }?>
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
			if(empty($userdata['authcode'])){
			?>
				<p>一時的に有効なキーを生成する二段階認証を設定することにより本人以外がログインしにくくなります。</p>
				<input type="submit" class = "irobutton" name="auth_on_submit" value="二段階認証の設定">
			<?php }else{ ?>
				<p>下のボタンを押すとすぐに解除されます。確認などはありません。気をつけてください。</p>
				<input type="submit" class = "irobutton" name="auth_off_submit" value="二段階認証の解除">
			<?php } ?>

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