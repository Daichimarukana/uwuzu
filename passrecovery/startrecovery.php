<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$badpassfile = "../server/badpass.txt";
$badpass_info = file_get_contents($badpassfile);
$badpass = preg_split("/\r\n|\n|\r/", $badpass_info);

require('../db.php');
require('../function/function.php');

//phpmailer--------------------------------------------
require('../settings_admin/plugin_settings/phpmailer_settings.php');
require('../settings_admin/plugin_settings/phpmailer_sender.php');
//------------------------------------------------------

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;

$row["loginid"] = "";
$row["authcode"] = "";

$ruserid = "";
$rpassword = "";

$userid = "";
$_SESSION["userid"]="";

$password = null;
$_SESSION["password"]="";

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

if(!(empty($_SESSION['userid']))){
    $userid = $_SESSION['userid'];
}else{
    $userid = null;
}
try {
    $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $options);
} catch(PDOException $e) {
    // 接続エラーのときエラー内容を取得する
    $error_message[] = 'データベース接続エラー: ' . $e->getMessage();
}

//ログイン認証---------------------------------------------------
blockedIP($_SERVER['REMOTE_ADDR']);
$is_login = uwuzuUserLogin($_SESSION, $_COOKIE, $_SERVER['REMOTE_ADDR'], "user");
if(!($is_login === false)){
	header("Location: ../home/");
	exit;
}
//-------------------------------------------------------------

if(!($userid == null)){
    if($_SESSION['auth_status'] === "go_recovery"){
        $userData = getUserData($pdo, $_SESSION['userid']);
        if(!(empty($userData))){
            $userid = $userData["userid"];
        }else{
            $_SESSION = array();
            header("Location: badrecovery.php");
            exit;
        }
    }elseif($_SESSION['auth_status'] === "bad_recovery"){
        $_SESSION = array();
        header("Location: badrecovery.php");
        exit;
    }else{
        $_SESSION = array();
        header("Location: badrecovery.php");
        exit;
    }

    if( !empty($_SESSION['mailadds']) ) {
        $result = $pdo->prepare("SELECT userid, username, mailadds, loginid, authcode, encryption_ivkey, datetime FROM account WHERE userid = :userid");
        $result->bindValue(':userid', $userid);
        $result->execute();
        $row = $result->fetch();

        if(!(empty($row["encryption_ivkey"]))){
            $userEnckey = GenUserEnckey($row["datetime"]);
            $dec_mailadds = DecryptionUseEncrKey($row["mailadds"], $userEnckey, $row["encryption_ivkey"]);
        }else{
            $dec_mailadds = $row["mailadds"];
        }

        if($dec_mailadds == $_SESSION['mailadds']){

            if( !empty($_POST['btn_submit']) ) {
                if(!(empty($_SESSION["uwuzu_authcode"]))){
                    $result = $pdo->prepare("SELECT authcode,loginid,username FROM account WHERE userid = :userid");
                    $result->bindValue(':userid', $userid);
                    // SQL実行
                    $result->execute();
                    if($result->rowCount() > 0) {
                        $userauthcode = $_POST['usercode'];
                        $password = $_POST['password'];
                
                        if(empty($userauthcode)){
                            $error_message[] = "コードを入力してください。(AUTHCODE_INPUT_PLEASE)";
                        }else{
                            if ($userauthcode === safetext($_SESSION["uwuzu_authcode"])) {
                                // パスワードの入力チェック
                                if( empty($password) ) {
                                    $error_message[] = 'パスワードを入力してください。(PASSWORD_INPUT_PLEASE)';
                                } else {
                                    
                                    if(in_array($password, $badpass) === true ){
                                        $error_message[] = "パスワードが弱いです。セキュリティ上変更してください。(PASSWORD_ZEIJAKU)";
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
                                    $other_settings_me = is_OtherSettings($pdo, $userid);
                                    if($other_settings_me === true){
                                        // トランザクション開始
                                        $pdo->beginTransaction();
                                        
                                        $hashpassword = uwuzu_password_hash($password);
                                    
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
                                            $msg = "お使いのアカウントのパスワードがパスワードの復元により変更されました。\n変更した覚えがない場合はパスワードを変更し、セッショントークンを再生成してください。";
                                            send_notification($userid,"uwuzu-fromsys","🔴アカウントのパスワードが復元により変更されました。🔴",$msg,"/others", "system");
                    
                                            $_SESSION['userid'] = "";
                                            $_SESSION['auth_status'] = 'done_recovery';
                                            $url = 'donerecovery.php';
                                            header('Location: ' . $url, true, 303);
                    
                                            // すべての出力を終了
                                            exit;
                                        } else {
                                            $error_message[] = 'パスワードの更新に失敗しました。(REGISTERED_DAME)';
                                        }
                                    }else{
                                        $error_message[] = 'パスワードの更新に失敗しました。(REGISTERED_DAME)';
                                    }
                                }
                            }
                        }
                    }
                }else{

                    require_once '../authcode/GoogleAuthenticator.php';
                
                    $result = $pdo->prepare("SELECT * FROM account WHERE userid = :userid");
                
                    $result->bindValue(':userid', $userid);
                    // SQL実行
                    $result->execute();
                    if($result->rowCount() > 0) {
                        $row = $result->fetch();
                
                        if(!(empty($row["encryption_ivkey"])) && (!(mb_strlen($row["authcode"]) === 16))){
                            $tousercode = DecryptionUseEncrKey($row["authcode"], GenUserEnckey($row["datetime"]), $row["encryption_ivkey"]);
                        }else{
                            $tousercode = $row["authcode"];
                        }
            
                        $chkauthcode = new PHPGangsta_GoogleAuthenticator();
                
                        $userauthcode = $_POST['usercode'];
                        $password = $_POST['password'];
                
                        if(empty($userauthcode)){
                            $error_message[] = "コードを入力してください。(AUTHCODE_INPUT_PLEASE)";
                        }else{
                
                            $discrepancy = 2;
                
                            $checkResult = $chkauthcode->verifyCode($tousercode, $userauthcode, $discrepancy);
                            if ($checkResult) {
                                
                                // パスワードの入力チェック
                                if( empty($password) ) {
                                    $error_message[] = 'パスワードを入力してください。(PASSWORD_INPUT_PLEASE)';
                                } else {
                                    
                                    if(in_array($password, $badpass) === true ){
                                        $error_message[] = "パスワードが弱いです。セキュリティ上変更してください。(PASSWORD_ZEIJAKU)";
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
                                    
                                    $hashpassword = uwuzu_password_hash($password);

                                
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
                                        $msg = "お使いのアカウントのパスワードがパスワードの復元により変更されました。\n変更した覚えがない場合はパスワードを変更し、セッショントークンを再生成してください。";
                                        send_notification($userid,"uwuzu-fromsys","🔴アカウントのパスワードが復元により変更されました。🔴",$msg,"/others", "system");
                
                                        $_SESSION['userid'] = "";
                                        $_SESSION['auth_status'] = 'done_recovery';
                                        $url = 'donerecovery.php';
                                        header('Location: ' . $url, true, 303);
                
                                        // すべての出力を終了
                                        exit;
                                    } else {
                                        $error_message[] = 'パスワードの更新に失敗しました。(REGISTERED_DAME)';
                                    }
                                }
                                        
                            }else {
                                $error_message[] = '二段階認証が出来ませんでした。再度お試しください。(AUTHCODE_CHECK_DAME)';
                            }
                            
                            // プリペアドステートメントを削除
                            $stmt = null;
                        }
                    }
            
                }
            }

            if(!empty($_POST['mail_submit'])){
                if(!empty(MAIL_CHKS)){
                    if(MAIL_CHKS == "true"){
                        if( !empty($dec_mailadds) ){
                            if(filter_var($dec_mailadds, FILTER_VALIDATE_EMAIL)){
                                $authcode = random_int(100000, 999999);
                                $mail_title = "パスワード復元の認証";
                                $mail_text = "".$row["username"]."(".$userid.")さん    いつもuwuzuをご利用いただきありがとうございます。  ご利用のアカウント(".$userid.")のパスワード復元コードは以下のものです。    ".safetext($authcode)."    もしパスワードの復元操作をしていないのであればこのメールは無視してください。";
        
                                send_html_mail($dec_mailadds,$mail_title,$mail_text,"../");

                                $_SESSION["uwuzu_authcode"] = $authcode;
                            }else{
                                $error_message[] = "メールアドレスが正しい形式ではありません。";
                            }
                        }
                    }else{
                        $error_message[] = "サーバーでメール配信機能がオフになっています。";
                    }
                }
            }
        }
    }
}else{
    $_SESSION['mailadds'] = "";
    $_SESSION['userid'] = "";
    $_SESSION['auth_status'] = 'bad_recovery';
    $url = 'badrecovery.php';
    header('Location: ' . $url, true, 303);
    exit;
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
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>パスワードの復元 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
</head>

<script src="../js/back.js"></script>
<body>

<div class="leftbox">
    <?php if(!empty(safetext($serversettings["serverinfo"]["server_logo_login"]))){ ?>
        <div class="logo">
            <a href="../index.php"><img src=<?php echo safetext($serversettings["serverinfo"]["server_logo_login"]);?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="../index.php"><img src="../img/uwuzulogo.svg"></a>
        </div>
    <?php }?>

    <div class="textbox">
        <h1>二段階認証</h1>

        <p>二段階認証コードと新しいパスワードを入力してください。<br>メールで認証することも可能です。</p>
        <div class="p2">二段階認証コードを設定していない場合、メールで認証をしてください。</div>

            <?php if( !empty($error_message) ): ?>
                <ul class="errmsg">
                    <?php foreach( $error_message as $value ): ?>
                        <p>・ <?php echo $value; ?></p>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form class="formarea" enctype="multipart/form-data" method="post">
                <div>
                    <p>二段階認証コード</p>
                    <div class="p2">6桁のコードを入力してください。</div>
                    <input id="verify_code" type="number" placeholder="123456" class="inbox" name="usercode" value="">
                </div>
                <div>
                    <p>新しいパスワード</p>
                    <div class="p2">新しいパスワードを入力してください。</div>
                    <input id="password" type="text" placeholder="" class="inbox" name="password" value="">
                    <div class="p2" id="password_zxcvbn" style="display: none;"></div>
                </div>
                <input type="submit" class = "irobutton" name="btn_submit" value="次へ">
                <?php 
                if(!empty(MAIL_CHKS)){
                    if(MAIL_CHKS == "true"){
                ?>
                    <input type="submit" class = "irobutton" name="mail_submit" value="メールで認証する">
                <?php }}?>
            </form>

            <div class="btnbox">
                <a href="index.php" class="sirobutton">戻る</a>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

function checkForm($this)
{
    var str=$this.value;
    while(str.match(/[^A-Z^a-z\d\-]/))
    {
        str=str.replace(/[^A-Z^a-z\d\-]/,"");
    }
    $this.value=str;
}

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

</script>


</body>
</html>