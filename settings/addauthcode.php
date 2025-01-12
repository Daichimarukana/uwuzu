<?php

function random($length = 12)
{
    return substr(str_shuffle('23456789ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz'), 0, $length);
}


require('../db.php');
require("../function/function.php");


$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

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

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$authcode = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;


$userid = safetext($_SESSION['userid']);



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

//ログイン認証---------------------------------------------------
blockedIP($_SERVER['REMOTE_ADDR']);
$is_login = uwuzuUserLogin($_SESSION, $_COOKIE, $_SERVER['REMOTE_ADDR'], "user");
if($is_login === false){
	header("Location: ../index.php");
	exit;
}else{
	$userid = safetext($is_login['userid']);
	$username = safetext($is_login['username']);
	$loginid = safetext($is_login["loginid"]);
	$role = safetext($is_login["role"]);
	$sacinfo = safetext($is_login["sacinfo"]);
	$myblocklist = safetext($is_login["blocklist"]);
	$myfollowlist = safetext($is_login["follow"]);
	$is_Admin = safetext($is_login["admin"]);
}
$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

require_once '../authcode/GoogleAuthenticator.php';

if(!(empty($pdo))){
	// ユーザーデータ取得
	$userQuery = $pdo->prepare("SELECT * FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $userid);
	$userQuery->execute();
	$userData = $userQuery->fetch();
}

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
			
            $hashbackupcode = uwuzu_password_hash($backupcode);

            $secret = $_SESSION['secretcode'];

			if(!(empty($userData["encryption_ivkey"]))){
				$userEnckey = GenUserEnckey($userData["datetime"]);
				$enc_seacret = EncryptionUseEncrKey($secret, $userEnckey, $userData["encryption_ivkey"]);
			}else{
				$enc_seacret = $secret;
			}

            // トランザクション開始
            $pdo->beginTransaction();
        
            try {
        
                        // SQL作成
                $stmt = $pdo->prepare("UPDATE account SET authcode = :authcode,backupcode = :backupcode WHERE userid = :userid");
        
                $stmt->bindValue(':authcode', $enc_seacret, PDO::PARAM_STR);
                $stmt->bindValue(':backupcode', $hashbackupcode, PDO::PARAM_STR);
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
<title>設定 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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

        $title = safetext($serversettings["serverinfo"]["server_name"]);

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