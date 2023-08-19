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

$userid = htmlentities($_SESSION['userid']);
$username = htmlentities($_SESSION['username']);

// 管理者としてログインしているか確認
if( empty($_SESSION['admin_login']) || $_SESSION['admin_login'] !== true ) {
	// ログインページへリダイレクト
	header("Location: ../login.php");
	exit;
}


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
$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

if( !empty($pdo) ) {
	
	// データベース接続の設定
	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	));

	$userQuery = $dbh->prepare("SELECT userid FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $userid);
	$userQuery->execute();
	$userData = $userQuery->fetch();
	
}

if( !empty($_POST['btn_submit']) ) {

	$chkuserid = htmlentities($_POST['chkuserid']);

	if( empty($chkuserid) ) {
		$error_message[] = '確認用ユーザーIDを入力してください。';
	} else {
		if($chkuserid === $userData["userid"]){
			$userId = $userData["userid"]; // 削除対象のユーザーID
			$folderPath = "../ueuseimages/"; // フォルダのパス
			
			// 指定したフォルダ内でユーザーIDを含むファイルを検索
			$filesToDelete = glob($folderPath . "*-$userId.*"); // 「-ユーザーID.拡張子」というパターンを検索
			
			// ファイルを順に削除
			foreach ($filesToDelete as $file) {
				if (is_file($file)) {
					unlink($file); // ファイルを削除
				}
			}
			
			$folderPath2 = "../ueusevideos/"; // フォルダのパス
			
			// 指定したフォルダ内でユーザーIDを含むファイルを検索
			$filesToDelete2 = glob($folderPath2 . "*-$userId.*"); // 「-ユーザーID.拡張子」というパターンを検索
			
			// ファイルを順に削除
			foreach ($filesToDelete2 as $file2) {
				if (is_file($file2)) {
					unlink($file2); // ファイルを削除
				}
			}
			

			try {
				$pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS);
		
				// 投稿削除クエリを実行
				$deleteQuery = $pdo->prepare("DELETE FROM ueuse WHERE account = :userid");
				$deleteQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
				$res = $deleteQuery->execute();
				
				// アカウント削除クエリを実行
				$deleteQuery = $pdo->prepare("DELETE FROM account WHERE userid = :userid");
				$deleteQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
				$res = $deleteQuery->execute();

				// フォローの更新
				$updateFollowQuery = $pdo->prepare("UPDATE account SET follow = REPLACE(follow, :userid, '') WHERE follow LIKE :pattern");
				$updateFollowQuery->bindValue(':userid', ",$userid", PDO::PARAM_STR);
				$updateFollowQuery->bindValue(':pattern', "%,$userid%", PDO::PARAM_STR);
				$updateFollowQuery->execute();

				// フォロワーの更新
				$updateFollowerQuery = $pdo->prepare("UPDATE account SET follower = REPLACE(follower, :userid, '') WHERE follower LIKE :pattern");
				$updateFollowerQuery->bindValue(':userid', ",$userid", PDO::PARAM_STR);
				$updateFollowerQuery->bindValue(':pattern', "%,$userid%", PDO::PARAM_STR);
				$updateFollowerQuery->execute();

				// いいねの更新
				$updateFavoriteQuery = $pdo->prepare("UPDATE ueuse SET favorite = REPLACE(favorite, :favorite, '') WHERE favorite LIKE :pattern");
				$updateFavoriteQuery->bindValue(':favorite', ",$userid", PDO::PARAM_STR);
				$updateFavoriteQuery->bindValue(':pattern', "%,$userid%", PDO::PARAM_STR);
				$updateFavoriteQuery->execute();
		
			} catch (Exception $e) {
		
				// エラーが発生した時はロールバック
				$pdo->rollBack();
			}
		
			if ($res) {
				if (isset($_SERVER['HTTP_COOKIE'])) {
					$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
					foreach($cookies as $cookie) {
						$parts = explode('=', $cookie);
						$name = trim($parts[0]);
						setcookie($name, '', time()-1000);
						setcookie($name, '', time()-1000, '/');
					}
				}
				header("Location:../index.php");
				exit; 
			} else {
				$error_message[] = 'アカウント削除に失敗しました。';
			}

		
			// プリペアドステートメントを削除
			$stmt = null;
		}else{
			$error_message[] = '確認用ユーザーIDが違います';
		}
    }


}


if( !empty($_POST['session_submit']) ) {
	$loginid = sha1(uniqid(mt_rand(), true));
	$pdo->beginTransaction();
		try {
			
            $stmt = $pdo->prepare("UPDATE account SET loginid = :loginid WHERE userid = :userid;");

            $stmt->bindParam(':loginid', $loginid, PDO::PARAM_STR);

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
		} else {
			$error_message[] = '登録に失敗しました。';
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


?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/home.css">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>その他の項目 - <?php echo file_get_contents($servernamefile);?></title>

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
        <form class="formarea" method="post">
		<h1>セッション終了</h1>
		<p>下のセッションを終了ボタンを押すと全てのログイン中のデバイスからログアウトされます。<br>再度uwuzu使用するにはログインが必須になります。</p>
		<input type="submit" class = "irobutton" name="session_submit" value="セッションを終了">
			<hr>
		<h1>アカウント削除</h1>
			<p>アカウント誤削除を防ぐため下の入力ボックスにご自身のユーザーIDを入力する必要があります。</p>

			<?php if($res["admin"] === "yes"){?>
				<p class="errmsg">あなたはこのサーバーの管理者のようです。<br>管理者アカウントの移行は済んでいますか？<br>アカウントを削除しても大丈夫なのですか...？</p>
			<?php }?>

			<div>
                <p>確認用ユーザーID</p>
                <input id="chkuserid" placeholder="" class="inbox" type="text" name="chkuserid" value="">
            </div>

            
            <input type="submit" class = "irobutton" name="btn_submit" value="アカウント削除">


        </form>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
</body>
</html>