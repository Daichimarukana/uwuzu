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

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

session_name('uwuzu_s_id');
session_set_cookie_params(0, '', '', true, true);
session_start();
session_regenerate_id(true);

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$authcode = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;


$userid = htmlentities($_SESSION['userid']);



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
                $error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
            }
        
            // プリペアドステートメントを削除
            $stmt = null;
        }
    } else {
        $error_message[] = "二段階認証が出来ませんでした。再度お試しください。(AUTHCODE_CHECK_DAME)";
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
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="../js/jquery-min.js"></script>
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>設定 - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>

</head>

<body>
<?php require('../require/leftbox.php');?>
	<main>

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

        $title = htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');

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
<?php require('../require/noscript_modal.php');?>
</body>

</html>