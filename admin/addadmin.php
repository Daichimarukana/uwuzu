<?php
require('../db.php');
//関数呼び出し
//- EXIF
require('../function/function.php');

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$badpassfile = "../server/badpass.txt";
$badpass_info = file_get_contents($badpassfile);
$badpass = preg_split("/\r\n|\n|\r/", $badpass_info);

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

$aduser = "yes";

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

$query = $dbh->prepare('SELECT * FROM account WHERE admin = :adminuser limit 1');

$query->execute(array(':adminuser' => $aduser));

$result2 = $query->fetch();

if($result2 > 0){
    header("Location: ../login.php");
	exit;
}



if( !empty($_POST['btn_submit']) ) {

    // 空白除去
	$username = safetext($_POST['username']);
    $userid = safetext($_POST['userid']);

    $password = safetext($_POST['password']);
    $chkpass = safetext($_POST['chkpass']);
    $mailadds = safetext($_POST['mailadds']);

    if(!(filter_var($mailadds, FILTER_VALIDATE_EMAIL))){
        $error_message[] = 'メールアドレスが正しい形式ではありません。(MAILADDS_CHECK_DAME)';
    }

    $profile = safetext($_POST['profile']);

    if(safetext($serversettings["serverinfo"]["server_invitation"]) === "true"){
        $invitationcode = safetext($_POST['invitationcode']);
    }

    //----------------[icon image]-------------------------------
    if (empty($_FILES['image']['name'])) {
        $localFilePathhead = '../img/deficon/icon.png';
    
        // 新しいファイル名を生成（uniqid + 拡張子）
        $newFilename = uniqid() . '-'.$userid.'.png';
        
        // 保存先のパスを生成
        $uploadedPath = 'usericons/' . $newFilename;
        
        // ファイルを移動
        $result = copy($localFilePathhead, '../'.$uploadedPath);
		
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
                $error_message[] = 'アップロード失敗！(1)エラーコード：' .$uploadedFile['error'].'';
            }
        }else{
            $error_message[] = "使用できない画像形式です。(SORRY_FILE_HITAIOU)";
        }
	}

    //----------------[header image]-------------------------------
    $localFilePathhead = '../img/defhead/head.png';
    
    // 新しいファイル名を生成（uniqid + 拡張子）
    $newFilename = uniqid() . '-'.$userid.'.png';
    
    // 保存先のパスを生成
    $uploadedPath = 'userheads/' . $newFilename;
    
    // ファイルを移動
    $result = copy($localFilePathhead, '../'.$uploadedPath);
    
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

    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

    if(safetext($serversettings["serverinfo"]["server_invitation"]) === "true"){
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

        if($result > 0){
            $error_message[] = 'このID('.$userid.')は既に使用されています。他のIDを作成してください。(USERID_SHIYOUZUMI)';
        }
        if(!(preg_match("/^[a-zA-Z0-9_]+$/", $userid))){
            $error_message[] = "IDは半角英数字で入力してください。(「_」は使用可能です。)(USERID_DONT_USE_WORD)";
        }

    }

    // パスワードの入力チェック
	if( empty($password) ) {
		$error_message[] = 'パスワードを入力してください。(PASSWORD_INPUT_PLEASE)';
	} else {

        if(in_array($password, $badpass) === true ){
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

    $userEnckey = GenUserEnckey($datetime);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $randomBytes = random_bytes($ivLength);
    $randomhash = hash('sha3-512', $randomBytes);
    $iv = substr($randomhash, 0, $ivLength);

    // メアドを暗号化する
    if(!(empty($mailadds))){
        $enc_mailadds = EncryptionUseEncrKey($mailadds, $userEnckey, $iv);
    }else{
        $enc_mailadds = "";
    }

    try {

        $role = "official";
        $admin = "yes";
        $hashpassword = password_hash($password, PASSWORD_DEFAULT);
        $loginid = sha1(uniqid(mt_rand(), true));

        // SQL作成
        $stmt = $pdo->prepare("INSERT INTO account (username, userid, password, loginid, mailadds, profile, iconname, headname, role, datetime, admin, encryption_ivkey) VALUES (:username, :userid, :password, :loginid, :mailadds, :profile, :iconname, :headname, :role, :datetime, :admin ,:encryption_ivkey)");

        // アイコン画像
        $stmt->bindValue(':iconname', $iconName, PDO::PARAM_STR);

        // ヘッダー画像
        $stmt->bindValue(':headname', $headName, PDO::PARAM_STR);

        // 他の値をセット
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':userid', $userid, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashpassword, PDO::PARAM_STR);
        $stmt->bindParam(':loginid', $loginid, PDO::PARAM_STR);
        $stmt->bindParam(':mailadds', $enc_mailadds, PDO::PARAM_STR);
        $stmt->bindParam(':profile', $profile, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);
        
        $stmt->bindParam(':encryption_ivkey', $iv, PDO::PARAM_STR);

        $stmt->bindParam(':admin', $admin, PDO::PARAM_STR);

        // SQLクエリの実行
        $res = $stmt->execute();

        // コミット
        $res = $pdo->commit();

        if(safetext($serversettings["serverinfo"]["server_invitation"]) === "true"){
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
        $url = 'success';
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
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css">
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/zxcvbn.js"></script>
<link rel="apple-touch-icon" type="../image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>管理者アカウント登録 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
</head>


<script src="back.js"></script>
<body>


<div class="leftbox">
    <div class="logo">
        <img src="../img/uwuzulogo.svg">
    </div>

    <div class="textbox">
        <h1>アカウント登録</h1>

        <p>管理者アカウント登録です。</p>
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
                <img id="iconimg" src="../img/deficon/icon.png">
            </div>
            <label class="irobutton" for="file_upload">ファイル選択
            <input type="file" id="file_upload" name="image" accept="image/*">
            </label>
            <p id="img_select" style="display:none;">画像を選択しました</p>
        </div>


        <script src="js/back.js"></script>
            <!--ユーザーネーム関係-->
            <div>
                <p>ユーザーネーム *</p>
                <div class="p2">プロフィールページに掲載され公開されます。<br>※サービス管理者が確認できます。</div>
                <input id="username" placeholder="" class="inbox" type="text" name="username" value="<?php if( !empty($_SESSION['username']) ){ echo safetext( $_SESSION['username']); } ?>">
            </div>
            <div>
                <p>ユーザーID *</p>
                <div class="p2">後から変更はできません。<br>プロフィールページに掲載され公開されます。<br>※サービス管理者が確認できます。</div>
                <input onInput="checkForm(this)" placeholder="" class="inbox" id="userid" type="text" name="userid" value="<?php if( !empty($_SESSION['userid']) ){ echo safetext( $_SESSION['userid']); } ?>">
            </div>
            <!--アカウント関連-->
            <div>
                <p>パスワード *</p>
                <div class="p2">ログイン時に必要となります。<br>※サービス管理者が確認できません。</div>
                <input placeholder="" class="inbox" id="password" type="text" name="password" value="<?php if( !empty($_SESSION['password']) ){ echo safetext( $_SESSION['password']); } ?>">
                <div class="p2" id="password_zxcvbn" style="display: none;"></div>
            </div>

            <div>
                <p>パスワード再確認 *</p>
                <input placeholder="" class="inbox" oncopy="return false" onpaste="return false" oncontextmenu="return false" id="chkpass" type="text" style="-webkit-text-security:disc;" name="chkpass" value="<?php if( !empty($_SESSION['chkpass']) ){ echo safetext( $_SESSION['chkpass']); } ?>">
            </div>

            <div>
                <p>メールアドレス</p>
                <div class="p2">設定しておくとアカウント復旧に利用できます。<br>※サービス管理者が確認できます。</div>
                <input id="mailadds" type="text" placeholder="" class="inbox" name="mailadds" value="<?php if( !empty($_SESSION['mailadds']) ){ echo safetext( $_SESSION['mailadds']); } ?>">
            </div>
            <!--プロフィール関連-->
            <div>
                <p>プロフィール</p>
                <div class="p2">プロフィールページに掲載され公開されます。<br>※サービス管理者が確認できます。</div>
                <input id="profile" type="text" placeholder="" class="inbox" name="profile" value="<?php if( !empty($_SESSION['profile']) ){ echo safetext( $_SESSION['profile']); } ?>">
            </div>
            <?php if(safetext($serversettings["serverinfo"]["server_invitation"]) === "true"){?>
                <div>
                    <p>招待コード</p>
                    <div class="p2">招待コードがないとこのサーバーには登録できません。</div>
                    <input id="profile" type="text" placeholder="" class="inbox" name="invitationcode" value="<?php if( !empty($_SESSION['invitationcode']) ){ echo safetext( $_SESSION['invitationcode']); } ?>">
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
            $('#img_select').show();
            $('#iconimg').attr('src', file_reader.result);
        });
        file_reader.readAsDataURL(e.target.files[0]);
    });
});
</script>


</body>
</html>