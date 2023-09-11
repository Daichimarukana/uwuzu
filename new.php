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

$servernamefile = "server/servername.txt";

$onlyuserfile = "server/onlyuser.txt";
$onlyuser = file_get_contents($onlyuserfile);

session_name('uwuzu_s_id');
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


if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,admin FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', $_SESSION['userid']);
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: login.php");
		exit;
	}elseif($_SESSION['loginid'] === $res["loginid"]){
	// セッションに値をセット
	$userid = $_SESSION['userid']; // セッションに格納されている値をそのままセット
	$username = $_SESSION['username']; // セッションに格納されている値をそのままセット
	$_SESSION['admin_login'] = true;
	$_SESSION['userid'] = $userid;
	$_SESSION['username'] = $username;
	$_SESSION['loginid'] = $res["loginid"];
	setcookie('userid', $userid, time() + 60 * 60 * 24 * 14);
	setcookie('username', $username, time() + 60 * 60 * 24 * 14);
	setcookie('loginid', $res["loginid"], time() + 60 * 60 * 24 * 14);
	setcookie('admin_login', true, time() + 60 * 60 * 24 * 14);
    header("Location: home/index.php");
	exit;
	}

		
} elseif (isset($_COOKIE['admin_login']) && $_COOKIE['admin_login'] == true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,admin FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', $_COOKIE['userid']);
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_COOKIE['loginid'] === $res["loginid"]){
	// セッションに値をセット
	$userid = $_COOKIE['userid']; // クッキーから取得した値をセット
	$username = $_COOKIE['username']; // クッキーから取得した値をセット
	$_SESSION['admin_login'] = true;
	$_SESSION['userid'] = $userid;
	$_SESSION['username'] = $username;
	$_SESSION['loginid'] = $res["loginid"];
	setcookie('userid', $userid, time() + 60 * 60 * 24 * 14);
	setcookie('username', $username, time() + 60 * 60 * 24 * 14);
	setcookie('loginid', $res["loginid"], time() + 60 * 60 * 24 * 14);
	setcookie('admin_login', true, time() + 60 * 60 * 24 * 14);
    header("Location: home/index.php");
    exit;
	}


}

if( !empty($_POST['btn_submit']) ) {


    //$row['userid'] = "daichimarukn";

    // 空白除去
	$username = $_POST['username'];
    $userid = $_POST['userid'];

    $password = $_POST['password'];
    $chkpass = $_POST['chkpass'];
    $mailadds = $_POST['mailadds'];

    $profile = $_POST['profile'];

    if($onlyuser === "true"){
        $invitationcode = $_POST['invitationcode'];
    }

    //cookieに保存
    setcookie("username",$username,time()+60*60*24*14);
    setcookie("userid",$userid,time()+60*60*24*14);

    setcookie("password",$password,time()+60*60*24*14);
    setcookie("mailadds",$mailadds,time()+60*60*24*14);

    setcookie("profile",$profile,time()+60*60*24*14);

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

		// アップロードされたファイルの拡張子を取得
		$extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
		
		// 新しいファイル名を生成（uniqid + 拡張子）
		$newFilename = uniqid() . '-'.$userid.'.' . $extension;
		
		// 保存先のパスを生成
		$uploadedPath = 'usericons/' . $newFilename;
		
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

    

    if($onlyuser === "true"){
        $query = $dbh->prepare('SELECT * FROM invitation WHERE code = :code limit 1');

        $query->execute(array(':code' => $invitationcode));
    
        $result = $query->fetch();

        // 招待コードの入力チェック
        if( empty($invitationcode) ) {
            $error_message[] = '招待コードを入力してください。';
        } else {
            if($result > 0){
                if($result["used"] === "true"){
                    $error_message[] = 'この招待コード('.$invitationcode.')は既に使用されています。';
                }
            }else{
                $error_message[] = 'この招待コード('.$invitationcode.')は使えません。';
            }

        }
    }


    $query = $dbh->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

    $query->execute(array(':userid' => $userid));

    $result = $query->fetch();


	// ユーザーネームの入力チェック
	if( empty($username) ) {
		$error_message[] = '表示名を入力してください。';
	} else {
        // 文字数を確認
        if( 25 < mb_strlen($username, 'UTF-8') ) {
			$error_message[] = 'ユーザーネームは25文字以内で入力してください。';
		}
    }

    // IDの入力チェック
	if( empty($userid) ) {
		$error_message[] = 'ユーザーIDを入力してください。';
	} else {

        // 文字数を確認
        if( 20 < mb_strlen($userid, 'UTF-8') ) {
			$error_message[] = 'IDは20文字以内で入力してください。';
		}

        if(in_array($userid, $banuserid) === true ){
            $error_message[] = 'そのIDは登録禁止になっています。';
        }

        if($result > 0){
            $error_message[] = 'このID('.$userid.')は既に使用されています。他のIDを作成してください。'; //このE-mailは既に使用されています。
        }

    }

    // パスワードの入力チェック
	if( empty($password) ) {
		$error_message[] = 'パスワードを入力してください。';
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
            $error_message[] = "パスワードが弱いです。セキュリティ上変更してください。";
        } else {
            
        }

        if ($chkpass == $password ){
            
        }else{
            $error_message[] = '確認用パスワードが違います。';
        }
        
        if( 4 > mb_strlen($password, 'UTF-8') ) {
			$error_message[] = 'パスワードは4文字以上である必要があります。';
		}

        // 文字数を確認
        if( 100 < mb_strlen($password, 'UTF-8') ) {
			$error_message[] = 'パスワードは100文字以内で入力してください。';
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

        if($onlyuser === "true"){
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
        $error_message[] = '登録に失敗しました。';
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
<link rel="stylesheet" href="css/style.css">
<link rel="apple-touch-icon" type="image/png" href="favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>アカウント登録 - <?php echo file_get_contents($servernamefile);?></title>
</head>


<script src="js/back.js"></script>
<body>


<div class="leftbox">
    <div class="logo">
        <img src="img/uwuzulogo.svg">
    </div>

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
                <input onInput="checkForm(this)" placeholder="" class="inbox" id="password" type="text" name="password" value="<?php if( !empty($_SESSION['password']) ){ echo htmlspecialchars( $_SESSION['password'], ENT_QUOTES, 'UTF-8'); } ?>">
            </div>

            <div>
                <p>パスワード再確認 *</p>
                <input onInput="checkForm(this)" placeholder="" class="inbox" oncopy="return false" onpaste="return false" oncontextmenu="return false" id="chkpass" type="text" style="-webkit-text-security:disc;" name="chkpass" value="<?php if( !empty($_SESSION['chkpass']) ){ echo htmlspecialchars( $_SESSION['chkpass'], ENT_QUOTES, 'UTF-8'); } ?>">
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
                <a href="rule/terms.php" class="fbtn">利用規約</a>
                <a href="rule/terms.php" class="fbtn">プライバシーポリシー</a>
            </div>

            <p>登録を押すと利用規約とプライバシーポリシーに同意したこととなります。<br>未確認の場合は上のボタンよりお読みください。</p>
            <?php if($onlyuser === "true"){?>
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