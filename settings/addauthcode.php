<?php

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}
function random($length = 32)
{
    return substr(str_shuffle('1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}


require('../db.php');

$servernamefile = "../server/servername.txt";

$onlyuserfile = "../server/onlyuser.txt";
$onlyuser = file_get_contents($onlyuserfile);

session_start();

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$authcode = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;


$userid = $_SESSION['userid'];



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

require_once '../authcode/GoogleAuthenticator.php';

if(empty($_SESSION['secretcode'])){
    $authcode = new PHPGangsta_GoogleAuthenticator();
    $secret = $authcode->createSecret();
    $_SESSION['secretcode'] = $secret;
}else{
    $authcode = new PHPGangsta_GoogleAuthenticator();
    $secret = $_SESSION['secretcode'];
}

if( !empty($_POST['btn_submit']) ) {
    $chkauthcode = new PHPGangsta_GoogleAuthenticator();
    //二段階認証の確認
    $userauthcode = $_POST['usercode'];

    $discrepancy = 2;

    $checkResult = $chkauthcode->verifyCode($secret, $userauthcode, $discrepancy);
    if ($checkResult) {
        if( empty($error_message) ) {
            $backupcode = random();
            $secret = $_SESSION['secretcode'];
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
                // リダイレクト先のURLへ転送する
                $_SESSION['backupcode'] = $backupcode;
                $url = 'success.php';
                header('Location: ' . $url, true, 303);
                exit; 
            } else {
                $error_message[] = '更新に失敗しました。';
            }
        
            // プリペアドステートメントを削除
            $stmt = null;
        }
    } else {
        $error_message[] = "二段階認証が出来ませんでした。再度お試しください。";
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

    <div class="emojibox">
    <h1>二段階認証の登録</h1>
            <?php if( !empty($error_message) ): ?>
                <ul class="errmsg">
                    <?php foreach( $error_message as $value ): ?>
                        <p>・ <?php echo $value; ?></p>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        <?php 

        $title = file_get_contents($servernamefile);

        $name = $userid;

        $qrCodeUrl = $authcode->getQRCodeUrl($name, $secret, $title);
        ?>
                
        <form class="formarea" enctype="multipart/form-data" method="post">
        <p>以下の二次元コードより二段階認証をセットアップしてください。</p>
        <p>セットアップが完了したら入力ボックスにコードを入力して「次へ」ボタンを押してください！<br>注意:まだ二段階認証の設定は終わっていません。次へを押すと設定が完了します。</p>
        <div class="authzone">
            <img src="../qr/php/qr_img.php?d=<?php echo $qrCodeUrl?>">
        </div>
            <div>
                <p>二段階認証コード</p>
                <div class="p2">先程セットアップして出力された6桁のコードを入力してください。</div>
                <input id="profile" type="text" placeholder="123456" class="inbox" name="usercode" value="">
            </div>
                <input type="submit" class = "irobutton" name="btn_submit" value="次へ">
        </form>
        
    </div>
    </main>

<?php require('../require/rightbox.php');?>
<?php require('../require/botbox.php');?>
</body>

</html>