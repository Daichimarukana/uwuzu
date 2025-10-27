<?php

function random($length = 12)
{
    return substr(str_shuffle('23456789ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz'), 0, $length);
}

require('db.php');
require("function/function.php");

$serversettings_file = "server/serversettings.ini";
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

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$authcode = array();
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

if( !empty($pdo) ) {
    $userData = getUserData($pdo, $_SESSION['userid']);
    if(!(empty($userData))){
        if($_SESSION['is_register_account'] === true){
            $userid = $userData["userid"];
        }else{
            header("Location: login.php");
            exit;
        }
    }else{
        $_SESSION = array();
        header("Location: index.php");
        exit;
    }

    //ログイン認証---------------------------------------------------
    blockedIP($_SERVER['REMOTE_ADDR']);
    $is_login = uwuzuUserLogin($_SESSION, $_COOKIE, $_SERVER['REMOTE_ADDR'], "user");
    if(!($is_login === false)){
        header("Location: /home/");
        exit;
    }
    //-------------------------------------------------------------

    require_once 'authcode/GoogleAuthenticator.php';

    if(empty($_SESSION['secretcode'])){
        $authcode = new PHPGangsta_GoogleAuthenticator();
        $secret = $authcode->createSecret();
        $_SESSION['secretcode'] = $secret;
    }else{
        $authcode = new PHPGangsta_GoogleAuthenticator();
        $secret = $_SESSION['secretcode'];
    }

    if(!(empty($pdo))){
        // ユーザーデータ取得
        $userQuery = $pdo->prepare("SELECT * FROM account WHERE userid = :userid");
        $userQuery->bindValue(':userid', $userid);
        $userQuery->execute();
        $userData = $userQuery->fetch();
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
                    $enc_seacret = EncryptionUseEncrKey($secret, $userEnckey, $iv);
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
                    $_SESSION["userid"] = $userid;
                    $_SESSION['backupcode'] = $backupcode;
                    $_SESSION['is_register_account'] = true;
                    // リダイレクト先のURLへ転送する
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
}

// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="css/style.css">
<script src="js/jquery-min.js"></script>
<script src="js/unsupported.js"></script>
<script src="js/back.js"></script>
<link rel="apple-touch-icon" type="image/png" href="favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>アカウント登録 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
</head>

<body>
<div class="leftbox">
    <?php if(!empty(safetext($serversettings["serverinfo"]["server_logo_login"]))){ ?>
        <div class="logo">
            <a href="index.php"><img src=<?php echo safetext($serversettings["serverinfo"]["server_logo_login"]);?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="index.php"><img src="img/uwuzulogo.svg"></a>
        </div>
    <?php }?>

    <div class="textbox">
        <h1>二段階認証</h1>

        <p id="setup_text">以下の二次元コードを読み込むか、二次元コードの下の秘密鍵を認証アプリに入力して二段階認証をセットアップしてください。</p>
        <p>セットアップが完了したら入力ボックスにコードを入力して「次へ」ボタンを押してください！<br>注意:まだ二段階認証の設定は終わっていません。次へを押すと設定が完了します。</p>

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
        <div class="authzone">
            <a href="<?php echo safetext(urldecode($qrCodeUrl));?>"><img src="../qr/php/qr_img.php?d=<?php echo $qrCodeUrl?>"></a>
            <div class="p2"><?php echo safetext($secret);?></div>
        </div>
                
        <form class="formarea" enctype="multipart/form-data" method="post">
            <div>
                <p>二段階認証コード</p>
                <div class="p2">先程セットアップして出力された6桁のコードを入力してください。</div>
                <input id="profile" type="text" placeholder="123456" class="inbox" name="usercode" value="">
            </div>
                <input type="submit" class = "irobutton" name="btn_submit" value="次へ">
        </form>
        
    </div>
</div>
<script>
//unsupported.jsでuaは取得済み↓
if (user_agent_os == "Android" || user_agent_os == "iOS_6_Over" || user_agent_os == "iPad") {
    $("#setup_text").text("以下の二次元コードをタップするか、二次元コードを読み込んで二段階認証をセットアップしてください。");
}
</script>


</body>
</html>