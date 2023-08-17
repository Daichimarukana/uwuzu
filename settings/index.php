<?php

$servernamefile = "../server/servername.txt";
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

session_start();

$userid = $_SESSION['userid'];
$username = $_SESSION['username'];

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
		header("Location: ../login.php");
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
	}else{
		header("Location: ../login.php");
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

if( !empty($pdo) ) {
	
	// データベース接続の設定
	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	));

	$userQuery = $dbh->prepare("SELECT username, userid, profile, role FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $userid);
	$userQuery->execute();
	$userData = $userQuery->fetch();

	$role = $userData["role"];

	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

	$rerole = $dbh->prepare("SELECT username, userid, password, mailadds, profile, iconname, iconcontent, icontype, iconsize, headname, headcontent, headtype, headsize, role, datetime FROM account WHERE userid = :userid");

    $rerole->bindValue(':userid', $userid);
    // SQL実行
    $rerole->execute();

    $userdata = $rerole->fetch(); // ここでデータベースから取得した値を $role に代入する

	
}



if( !empty($_POST['btn_submit']) ) {

    // 空白除去
	$username = $_POST['username'];

    $mailadds = $_POST['mailadds'];

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
		$error_message[] = '表示名を入力してください。';
	} else {
        // 文字数を確認
        if( 25 < mb_strlen($username, 'UTF-8') ) {
			$error_message[] = 'ユーザーネームは25文字以内で入力してください。';
		}
    }

    if( empty($error_message) ) {
		// トランザクション開始
	$pdo->beginTransaction();
	$hashpassword = password_hash($password, PASSWORD_DEFAULT);

	try {
		// SQL作成
		$stmt = $pdo->prepare("UPDATE account SET username = :username, mailadds = :mailadds, profile = :profile WHERE userid = :userid;");

		// 他の値をセット
		$stmt->bindParam(':username', $username, PDO::PARAM_STR);
		$stmt->bindParam(':mailadds', $mailadds, PDO::PARAM_STR);
		$stmt->bindParam(':profile', $profile, PDO::PARAM_STR);

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
		$error_message[] = '更新に失敗しました。';
	}

    // プリペアドステートメントを削除
    $stmt = null;
    }
}



if( !empty($_POST['pass_submit']) ) {

	$password = $_POST['password'];

	$hashpassword = password_hash($password, PASSWORD_DEFAULT);

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
        // 文字数を確認
        if( 100 < mb_strlen($password, 'UTF-8') ) {
			$error_message[] = 'パスワードは100文字以内で入力してください。';
		}

		if( 4 > mb_strlen($password, 'UTF-8') ) {
			$error_message[] = 'パスワードは4文字以上である必要があります。';
		}
    }

    if( empty($error_message) ) {
		// トランザクション開始
	$pdo->beginTransaction();
	$hashpassword = password_hash($password, PASSWORD_DEFAULT);

	try {
		// SQL作成
		$stmt = $pdo->prepare("UPDATE account SET password = :password WHERE userid = :userid;");

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
		$error_message[] = '更新に失敗しました。';
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
        $headimg = $_FILES['image2s'];
    }else{
		$error_message[] = 'ヘッダー画像を選択してください';
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
		$stmt = $pdo->prepare("UPDATE account SET headname = :headname, headtype = :headtype, headcontent = :headcontent, headsize = :headsize WHERE userid = :userid");

		// ヘッダー画像関連の処理
		$headName = $headimg['name'];
		$headType = $headimg['type'];
		$headContent = file_get_contents($headimg['tmp_name']);
		$headSize = $headimg['size'];

		// ヘッダー画像のバインド
		$stmt->bindValue(':headname', $headName, PDO::PARAM_STR);
		$stmt->bindValue(':headtype', $headType, PDO::PARAM_STR);
		$stmt->bindValue(':headcontent', $headContent, PDO::PARAM_STR);
		$stmt->bindValue(':headsize', $headSize, PDO::PARAM_INT);

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
        $error_message[] = '更新に失敗しました。';
    }

    // プリペアドステートメントを削除
    $stmt = null;
    }
}


if( !empty($_POST['img2btn_submit']) ) {

    if (!empty($_FILES['image']['name'])) {
        $img = $_FILES['image'];
    }else{
		$error_message[] = 'アイコン画像を選択してください';
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
		$stmt = $pdo->prepare("UPDATE account SET iconname = :iconname, icontype = :icontype, iconcontent = :iconcontent, iconsize = :iconsize WHERE userid = :userid");

		$iconName = $img['name'];
		$iconType = $img['type'];
		$iconContent = file_get_contents($img['tmp_name']);
		$iconSize = $img['size'];

		// アイコン画像のバインド
		$stmt->bindValue(':iconname', $iconName, PDO::PARAM_STR);
		$stmt->bindValue(':icontype', $iconType, PDO::PARAM_STR);
		$stmt->bindValue(':iconcontent', $iconContent, PDO::PARAM_STR);
		$stmt->bindValue(':iconsize', $iconSize, PDO::PARAM_INT);

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
        $error_message[] = '更新に失敗しました。';
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


// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/home.css">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>設定 - <?php echo file_get_contents($servernamefile);?></title>

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
			<div class="hed">
				<img src="../user/headimage.php?account=<?php echo urlencode($userdata['userid']); ?>">
			</div>

			<div class="iconimg">
				<img src="../image.php">
			</div>
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
                <input id="username" placeholder="" class="inbox" type="text" name="username" value="<?php if( !empty($userdata['username']) ){ echo htmlspecialchars( $userdata['username'], ENT_QUOTES, 'UTF-8'); } ?>">
            </div>
            <div>
                <p>メールアドレス</p>
                <input id="mailadds" type="text" placeholder="" class="inbox" name="mailadds" value="<?php if( !empty($userdata['mailadds']) ){ echo htmlspecialchars( $userdata['mailadds'], ENT_QUOTES, 'UTF-8'); } ?>">
            </div>
            <!--プロフィール関連-->
            <div>
                <p>プロフィール</p>
                <textarea id="profile" type="text" placeholder="" class="inbox" name="profile" value=""><?php if( !empty($userdata['profile']) ){ echo htmlspecialchars( $userdata['profile'], ENT_QUOTES, 'UTF-8'); } ?></textarea>
            </div>
			            
            <input type="submit" class = "irobutton" name="btn_submit" value="情報更新">

			<div>
                <p>パスワード</p>
                <input id="password" type="text" class="inbox" name="password" oncopy="return false" onpaste="return false" oncontextmenu="return false" style="-webkit-text-security:disc;" value="">
            </div>

			<input type="submit" class = "irobutton" name="pass_submit" value="パスワード更新">


        </form>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
</body>
</html>