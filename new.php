<?php

$banuseridfile = "server/banuserid.txt";
$banuserid_info = file_get_contents($banuseridfile);

$banuserid = preg_split("/\r\n|\n|\r/", $banuserid_info);

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}

require('db.php');
//関数呼び出し
//- EXIF
require('function/function.php');
//hCaptcha--------------------------------------------
require('settings_admin/hCaptcha_settings/hCaptcha_settings.php');
//Cloudflare_Turnstile--------------------------------------------
require('settings_admin/CloudflareTurnstile_settings/CloudflareTurnstile_settings.php');
//----------------------------------------------------

$serversettings_file = "server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);


session_name('uwuzu_s_id');
session_set_cookie_params(0, '', '', true, true);
session_start();

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;

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

if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true && isset($_COOKIE['loginid']) && isset($_SESSION['userid'])) {
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
    $acck = $dbh->prepare("SELECT userid, loginid FROM account WHERE userid = :userid");
    $acck->bindValue(':userid', $_SESSION['userid']);
    $acck->execute();
    $acck_data = $acck->fetch();
    if(!empty($acck_data)){
        if($_COOKIE['loginid'] === $acck_data["loginid"] && $_SESSION['userid'] === $acck_data["userid"] ){
            header("Location: home/index.php");
            exit;
        }
    }
} elseif (isset($_COOKIE['admin_login']) && $_COOKIE['admin_login'] == true && isset($_COOKIE['loginid']) && isset($_COOKIE['userid'])) {
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
    $acck = $dbh->prepare("SELECT userid, loginid FROM account WHERE userid = :userid");
    $acck->bindValue(':userid', $_COOKIE['userid']);
    $acck->execute();
    $acck_data = $acck->fetch();
    if(!empty($acck_data)){
        if($_COOKIE['loginid'] === $acck_data["loginid"] && $_COOKIE['userid'] === $acck_data["userid"] ){
            header("Location: home/index.php");
            exit;
        }
    }
}


if( !empty($_POST['btn_submit']) ) {


    //$row['userid'] = "daichimarukn";

    // 空白除去
	$username = htmlentities($_POST['username']);
    $userid = htmlentities($_POST['userid']);

    $password = htmlentities($_POST['password']);
    $chkpass = htmlentities($_POST['chkpass']);
    $mailadds = htmlentities($_POST['mailadds']);

    $profile = htmlentities($_POST['profile']);

    if(htmlspecialchars($serversettings["serverinfo"]["server_invitation"], ENT_QUOTES, 'UTF-8') === "true"){
        $invitationcode = $_POST['invitationcode'];
    }

    if(!empty(H_CAPTCHA_ONOFF && H_CAPTCHA_ONOFF == "true")){
        if(isset($_POST['h-captcha-response'])){
            $hcaptcha_token = htmlentities($_POST['h-captcha-response']);
            if($hcaptcha_token){
                $captcha_data = [
                    'secret' => htmlentities(H_CAPTCHA_SEAC_KEY),
                    'response' => $hcaptcha_token,
                    'sitekey' => htmlentities(H_CAPTCHA_SITE_KEY)
                ];
                $options = [
                    'http' => [
                        'method'=> 'POST',
                        'header'=> 'Content-Type: application/x-www-form-urlencoded',
                        'content' => http_build_query($captcha_data, '', '&')
                    ]
                ];
                $hCaptcha_result = json_decode(file_get_contents('https://hcaptcha.com/siteverify', false, stream_context_create($options)),true);
                if(!($hCaptcha_result["success"] == true)){
                    $error_message[] = "hCaptchaであなたが人間である確認ができませんでした。(ERROR)";
                }
            }else{
                $error_message[] = "hCaptchaであなたが人間である確認ができませんでした。(ERROR)";
            }
        }else{
            $error_message[] = "hCaptchaであなたが人間である確認ができませんでした。(ERROR)";
        }
    }
    if(!empty(CF_TURNSTILE_ONOFF && CF_TURNSTILE_ONOFF == "true")){
        if(isset($_POST['cf-turnstile-response'])){
            $CF_Turnstile_token = htmlentities($_POST['cf-turnstile-response']);
            if($CF_Turnstile_token){
                $CF_Turnstile_data = [
                    'secret' => htmlentities(CF_TURNSTILE_SEAC_KEY),
                    'response' => $CF_Turnstile_token
                ];
                $CF_Turnstile_options = [
                    'http' => [
                        'method'=> 'POST',
                        'header'=> 'Content-Type: application/x-www-form-urlencoded',
                        'content' => http_build_query($CF_Turnstile_data, '', '&')
                    ]
                ];
                $CF_Turnstile_result = json_decode(file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, stream_context_create($CF_Turnstile_options)),true);
                if(!($CF_Turnstile_result["success"] == true)){
                    $error_message[] = "CloudflareTurnstileであなたが人間である確認ができませんでした。(ERROR)";
                }
            }else{
                $error_message[] = "CloudflareTurnstileであなたが人間である確認ができませんでした。(ERROR)";
            }
        }else{
            $error_message[] = "CloudflareTurnstileであなたが人間である確認ができませんでした。(ERROR)";
        }
    }

    //----------------[icon image]-------------------------------
    if (empty($_FILES['image']['name'])) {
        $localFilePathhead = 'img/deficon/icon.png';
    
        // 新しいファイル名を生成（uniqid + 拡張子）
        $newFilename = uniqid() . '-'.$userid.'.png';
        
        // 保存先のパスを生成
        $uploadedPath = 'usericons/' . $newFilename;
        
        // ファイルを移動
        $result = copy($localFilePathhead, $uploadedPath);
		
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
			$error_message[] = 'アップロード失敗！(1)エラーコード：' .$uploadedFile['error'].'';
		}

	} else {
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
            $result = move_uploaded_file($uploadedFile['tmp_name'], $uploadedPath);

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
                $error_message[] = 'アップロード失敗！(1)エラーコード：' .$uploadedFile['error'].'';
            }
        }else{
            $error_message[] = "使用できない画像形式です。(FILE_UPLOAD_DEKINAKATTA)";
        }
	}

    //----------------[header image]-------------------------------
    $localFilePathhead = 'img/defhead/head.png';
    
    // 新しいファイル名を生成（uniqid + 拡張子）
    $newFilename = uniqid() . '-'.$userid.'.png';
    
    // 保存先のパスを生成
    $uploadedPath = 'userheads/' . $newFilename;
    
    // ファイルを移動
    $result = copy($localFilePathhead, $uploadedPath);
    
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
        $error_message[] = 'アップロード失敗！(2)エラーコード：' .$uploadedFile['error'].'';
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

    $dbh = new PDO('mysql:charset=UTF8;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

    if(htmlspecialchars($serversettings["serverinfo"]["server_invitation"], ENT_QUOTES, 'UTF-8') === "true"){
        $query = $dbh->prepare('SELECT * FROM invitation WHERE code = :code limit 1');

        $query->execute(array(':code' => $invitationcode));
    
        $result = $query->fetch();

        // 招待コードの入力チェック
        if( empty($invitationcode) ) {
            $error_message[] = '招待コードを入力してください。(INVITATION_CODE_INPUT_PLEASE)';
        } else {
            if($result > 0){
                if($result["used"] === "true"){
                    $error_message[] = 'この招待コード('.$invitationcode.')は既に使用されています。(INVITATION_CODE_SHIYOUZUMI)';
                }
            }else{
                $error_message[] = 'この招待コード('.$invitationcode.')は使えません。(INVITATION_CODE_DEAD)';
            }

        }
    }


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

    // IDの入力チェック
	if( empty($userid) ) {
		$error_message[] = 'ユーザーIDを入力してください。(USERID_INPUT_PLEASE)';
	} else {

        // 文字数を確認
        if( 20 < mb_strlen($userid, 'UTF-8') ) {
			$error_message[] = 'IDは20文字以内で入力してください。(USERID_OVER_MAX_COUNT)';
		}

        if(in_array($userid, $banuserid) === true ){
            $error_message[] = 'そのIDは登録禁止になっています。(USERID_CONTAINS_PROHIBITED)';
        }

        if($result > 0){
            $error_message[] = 'このID('.$userid.')は既に使用されています。他のIDを作成してください。(USERID_SHIYOUZUMI)'; //このE-mailは既に使用されています。
        }

        if(!(preg_match("/^[a-zA-Z0-9_]+$/", $userid))){
            $error_message[] = "IDは半角英数字で入力してください。(「_」は使用可能です。)(USERID_DONT_USE_WORD)";
        }

    }

    // パスワードの入力チェック
	if( empty($password) ) {
		$error_message[] = 'パスワードを入力してください。(PASSWORD_INPUT_PLEASE)';
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

        if (isWeakPassword($password)) {
            $error_message[] = "パスワードが弱いです。セキュリティ上変更してください。(PASSWORD_ZEIJAKU)";
        }

        if (!($chkpass == $password)){
            $error_message[] = '確認用パスワードが違います。(PASSWORD_CHIGAUYANKE)';
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
    $datetime = date("Y-m-d H:i:s");

    try {

        $role = "user";
        $admin = "none";
        $hashpassword = password_hash($password, PASSWORD_DEFAULT);
        $loginid = sha1(uniqid(mt_rand(), true));

        // SQL作成
        $stmt = $pdo->prepare("INSERT INTO account (username, userid, password, loginid, mailadds, profile, iconname, headname, role, datetime, admin) VALUES (:username, :userid, :password, :loginid, :mailadds, :profile, :iconname, :headname, :role, :datetime, :admin )");

        // アイコン画像
        $stmt->bindValue(':iconname', $iconName, PDO::PARAM_STR);

        // ヘッダー画像
        $stmt->bindValue(':headname', $headName, PDO::PARAM_STR);

        // 他の値をセット
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':userid', $userid, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashpassword, PDO::PARAM_STR);
        $stmt->bindParam(':loginid', $loginid, PDO::PARAM_STR);
        $stmt->bindParam(':mailadds', $mailadds, PDO::PARAM_STR);
        $stmt->bindParam(':profile', $profile, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);
        
        $stmt->bindParam(':admin', $admin, PDO::PARAM_STR);

        // SQLクエリの実行
        $res = $stmt->execute();

        // コミット
        $res = $pdo->commit();

        if(htmlspecialchars($serversettings["serverinfo"]["server_invitation"], ENT_QUOTES, 'UTF-8') === "true"){
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE invitation SET used = :used, datetime = :datetime WHERE code = :code;");

            $true = "true";
            $stmt->bindParam(':used', $true, PDO::PARAM_STR);
            $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

            $stmt->bindValue(':code', $invitationcode, PDO::PARAM_STR);

                // SQLクエリの実行
            $res = $stmt->execute();

            // コミット
            $res = $pdo->commit();
        }

    } catch (Exception $e) {

        // エラーが発生した時はロールバック
        $pdo->rollBack();
    }

    if ($res) {
        // リダイレクト先のURLへ転送する
        $_SESSION['userid'] = $userid;
        $url = 'authcodechk';
        header('Location: ' . $url, true, 303);

        // すべての出力を終了
        exit;
    } else {
        $error_message[] = '登録に失敗しました。(REGISTERED_DAME)';
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
<head prefix="og:http://ogp.me/ns#">
<meta charset="utf-8">
<!--OGPはじまり-->
<meta property="og:title" content="アカウント登録 - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?>">
<meta property="og:description" content="<?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?>にアカウント登録">
<meta property="og:url" content="https://<?php echo htmlentities($domain, ENT_QUOTES, 'UTF-8'); ?>/new">
<meta property="og:image" content="<?php echo htmlspecialchars($serversettings["serverinfo"]["server_icon"], ENT_QUOTES, 'UTF-8');?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="アカウント登録 - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?>">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="アカウント登録 - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?>"/>
<meta name="twitter:description" content="<?php echo htmlentities($serverinfo);?>"/>
<!--OGPここまで-->
<link rel="stylesheet" href="css/style.css">
<script src="js/jquery-min.js"></script>
<script src="js/unsupported.js"></script>
<?php if(!empty(H_CAPTCHA_ONOFF && H_CAPTCHA_ONOFF == "true")){?>
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
<?php }?>
<?php if(!empty(CF_TURNSTILE_ONOFF && CF_TURNSTILE_ONOFF == "true")){?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php }?>
<link rel="apple-touch-icon" type="image/png" href="favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>アカウント登録 - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>
</head>


<script src="js/back.js"></script>
<body>


<div class="leftbox">
    <?php if(!empty(htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8'))){ ?>
        <div class="logo">
            <a href="../index.php"><img src=<?php echo htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8');?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="../index.php"><img src="img/uwuzulogo.svg"></a>
        </div>
    <?php }?>

    <div class="textbox">
        <h1>アカウント登録</h1>

        <p>アカウント登録です。</p>
        <p>必須項目には「*」があります。

            <?php if( !empty($error_message) ): ?>
                <ul class="errmsg">
                    <?php foreach( $error_message as $value ): ?>
                        <p>・ <?php echo $value; ?></p>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
                
        <form class="formarea" enctype="multipart/form-data" method="post">

        <div id="wrap">
            <div class="iconimg">
                <img src="img/deficon/icon.png">
            </div>
            <label class="irobutton" for="file_upload">ファイル選択
            <input type="file" id="file_upload" name="image" accept="image/*">
            </label>
        </div>


        <script src="js/back.js"></script>
            <!--ユーザーネーム関係-->
            <div>
                <p>ユーザーネーム *</p>
                <div class="p2">プロフィールページに掲載され公開されます。<br>※サービス管理者が確認できます。</div>
                <input id="username" placeholder="" class="inbox" type="text" name="username" value="<?php if( !empty($_SESSION['username']) ){ echo htmlspecialchars( $_SESSION['username'], ENT_QUOTES, 'UTF-8'); } ?>">
            </div>
            <div>
                <p>ユーザーID *</p>
                <div class="p2">後から変更はできません。<br>プロフィールページに掲載され公開されます。<br>※サービス管理者が確認できます。</div>
                <input onInput="checkForm(this)" placeholder="" class="inbox" id="userid" type="text" name="userid" value="<?php if( !empty($_SESSION['userid']) ){ echo htmlspecialchars( $_SESSION['userid'], ENT_QUOTES, 'UTF-8'); } ?>">
            </div>
            <!--アカウント関連-->
            <div>
                <p>パスワード *</p>
                <div class="p2">ログイン時に必要となります。<br>※サービス管理者が確認できません。</div>
                <input placeholder="" class="inbox" id="password" type="text" name="password" value="<?php if( !empty($_SESSION['password']) ){ echo htmlspecialchars( $_SESSION['password'], ENT_QUOTES, 'UTF-8'); } ?>">
            </div>

            <div>
                <p>パスワード再確認 *</p>
                <input placeholder="" class="inbox" oncopy="return false" onpaste="return false" oncontextmenu="return false" id="chkpass" type="text" style="-webkit-text-security:disc;" name="chkpass" value="<?php if( !empty($_SESSION['chkpass']) ){ echo htmlspecialchars( $_SESSION['chkpass'], ENT_QUOTES, 'UTF-8'); } ?>">
            </div>

            <div>
                <p>メールアドレス</p>
                <div class="p2">設定しておくとアカウント復旧に利用できます。<br>※サービス管理者が確認できます。</div>
                <input id="mailadds" type="text" placeholder="" class="inbox" name="mailadds" value="<?php if( !empty($_SESSION['mailadds']) ){ echo htmlspecialchars( $_SESSION['mailadds'], ENT_QUOTES, 'UTF-8'); } ?>">
            </div>
            <!--プロフィール関連-->
            <div>
                <p>プロフィール</p>
                <div class="p2">プロフィールページに掲載され公開されます。<br>※サービス管理者が確認できます。</div>
                <input id="profile" type="text" placeholder="" class="inbox" name="profile" value="<?php if( !empty($_SESSION['profile']) ){ echo htmlspecialchars( $_SESSION['profile'], ENT_QUOTES, 'UTF-8'); } ?>">
            </div>

            <div class="btn_area">
                <a href="rule/terms.php" target="_blank" class="fbtn">利用規約</a>
                <a href="rule/privacypolicy.php" target="_blank" class="fbtn">プライバシーポリシー</a>
            </div>

            <p>登録を押すと利用規約とプライバシーポリシーに同意したこととなります。<br>未確認の場合は上のボタンよりお読みください。</p>
            <?php if(!empty(H_CAPTCHA_ONOFF && H_CAPTCHA_ONOFF == "true")){?>
                <div class="captcha_zone">
                    <div class="p2">あなたは人間ですか？<br>もし人間であれば下のチェックボックスにチェックしてください！</div>
                    <div class="h-captcha" data-sitekey="<?php echo htmlentities(H_CAPTCHA_SITE_KEY);?>"></div>
                </div>
            <?php }?>
            <?php if(!empty(CF_TURNSTILE_ONOFF && CF_TURNSTILE_ONOFF == "true")){?>
                <div class="captcha_zone">
                    <div class="cf-turnstile" data-sitekey="<?php echo htmlentities(CF_TURNSTILE_SITE_KEY);?>" data-callback="javascriptCallback" data-language="ja"></div>
                </div>
            <?php }?>
            <?php if(htmlspecialchars($serversettings["serverinfo"]["server_invitation"], ENT_QUOTES, 'UTF-8') === "true"){?>
                <div>
                    <p>招待コード</p>
                    <div class="p2">招待コードがないとこのサーバーには登録できません。</div>
                    <input id="profile" type="text" placeholder="" class="inbox" name="invitationcode" value="<?php if( !empty($_SESSION['invitationcode']) ){ echo htmlspecialchars( $_SESSION['invitationcode'], ENT_QUOTES, 'UTF-8'); } ?>">
                </div>
                <input type="submit" class = "irobutton" name="btn_submit" value="登録">
            <?php }else{?>
                <input type="submit" class = "irobutton" name="btn_submit" value="登録">
            <?php }?>
        </form>

        <div class="btnbox">
                <a href="index.php" class="sirobutton">戻る</a>
            </div>
        </div>
        
    </div>
</div>


<script type="text/javascript">

function checkForm(inputElement) {
    var str = inputElement.value;
    while (str.match(/[^A-Za-z\d_]/)) {
        str = str.replace(/[^A-Za-z\d_]/, "");
    }
    inputElement.value = str;
}


window.addEventListener('DOMContentLoaded', function(){

// ファイルが選択されたら実行
document.getElementById("file_upload").addEventListener('change', function(e){

  var file_reader = new FileReader();

  // ファイルの読み込みを行ったら実行
  file_reader.addEventListener('load', function(e) {
    console.log(e.target.result);
        const element = document.querySelector('#wrap');
        const createElement = '<p>画像を選択しました。</p>';
        element.insertAdjacentHTML('afterend', createElement);
  });

  file_reader.readAsText(e.target.files[0]);
});
});
</script>


</body>
</html>