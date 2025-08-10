<?php
$serversettings_file = "server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);


require('db.php');
require("function/function.php");
//hCaptcha--------------------------------------------
require('settings_admin/hCaptcha_settings/hCaptcha_settings.php');
//Cloudflare_Turnstile--------------------------------------------
require('settings_admin/CloudflareTurnstile_settings/CloudflareTurnstile_settings.php');
//----------------------------------------------------


// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;

$row["userid"] = "";
$row["password"] = "";

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
if(!($is_login === false)){
	header("Location: /home/");
	exit;
}
//-------------------------------------------------------------

$serversettings_file = "server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);


//------------------------

$serverinfofile = 'server/info.txt';
$serverinfo = file_get_contents($serverinfofile);

//-------------------------------------

//-------------------------
$softwarefile = "server/uwuzuinfo.txt";
$softwaredata = file_get_contents($softwarefile);

$softwaredata = explode( "\n", $softwaredata );
$cnt = count( $softwaredata );
for( $i=0;$i<$cnt;$i++ ){
    $uwuzuinfo[$i] = ($softwaredata[$i]);
}
//-------------------------

$domain = $_SERVER['HTTP_HOST'];

//------------------------
//パスワード試行回数制限-------------------------------------------
if (!isset($_SESSION['login_passtry'])) {
    $_SESSION['login_passtry'] = 0;
}
//-------------------------------------------------------------

if( !empty($_POST['btn_submit']) ) {
    $_SESSION['form_data'] = $_POST;

    $userid = safetext($_POST['userid']);
    $password = safetext($_POST['password']);

    if(!empty(H_CAPTCHA_ONOFF && H_CAPTCHA_ONOFF == "true")){
        if(isset($_POST['h-captcha-response'])){
            $hcaptcha_token = safetext($_POST['h-captcha-response']);
            if($hcaptcha_token){
                $captcha_data = [
                    'secret' => safetext(H_CAPTCHA_SEAC_KEY),
                    'response' => $hcaptcha_token,
                    'sitekey' => safetext(H_CAPTCHA_SITE_KEY)
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
            $CF_Turnstile_token = safetext($_POST['cf-turnstile-response']);
            if($CF_Turnstile_token){
                $CF_Turnstile_data = [
                    'secret' => safetext(CF_TURNSTILE_SEAC_KEY),
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


    $result = $dbh->prepare("SELECT userid, password, loginid, authcode FROM account WHERE userid = :userid");
    $result->bindValue(':userid', $userid);
    $result->execute();

	if( empty($userid) ) {
		$error_message[] = 'ユーザーIDを入力してください。(USERID_INPUT_PLEASE)';
	} else {
        if(!(preg_match("/^[a-zA-Z0-9_]+$/", $userid))){
            $error_message[] = "IDは半角英数字で入力してください。(「_」は使用可能です。)(USERID_DONT_USE_WORD)";
        }
        if( empty($password) ) {
            $error_message[] = 'パスワードを入力してください。(PASSWORD_INPUT_PLEASE)';
        }

        if(empty($error_message)){
            if ($_SESSION["login_passtry"] <= 5) {
                $delay = $_SESSION["login_passtry"] * 2;
            } else {
                $delay = min(pow(2, $_SESSION["login_passtry"] - 2), 60);
            }
            sleep($delay);

            if($result->rowCount() > 0) {
                $row = $result->fetch(); // ここでデータベースから取得した値を $row に代入する

                if(strtolower($row["userid"]) == strtolower($userid)){
                    if(uwuzu_password_verify($password,$row["password"])){
                        if(empty($row["authcode"])){
                            $_SESSION['userid'] = $userid;
                            $_SESSION["login_passtry"] = 0;

                            $_SESSION['form_data'] = array();//フォーム初期化
                            // リダイレクト先のURLへ転送する
                            $url = 'check.php';
                            header('Location: ' . $url, true, 303);

                            // すべての出力を終了
                            exit;
                        }else{
                            $_SESSION['userid'] = $userid;
                            $_SESSION["login_passtry"] = 0;
                            
                            $_SESSION['form_data'] = array();//フォーム初期化
                            $url = 'authlogin.php';
                            header('Location: ' . $url, true, 303);

                            // すべての出力を終了
                            exit;
                        }
                    }else{
                        $_SESSION["login_passtry"]++;
                        $error_message[] = 'IDまたはパスワードが違います(PASS_AND_ID_CHIGAUYANKE)'; 
                    }
                }else{
                    $_SESSION["login_passtry"]++;
                    $error_message[] = 'IDまたはパスワードが違います(PASS_AND_ID_CHIGAUYANKE)'; 
                }
            }else {
                $_SESSION["login_passtry"]++;
                $error_message[] = 'IDまたはパスワードが違います(PASS_AND_ID_CHIGAUYANKE)';
            }
        }
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
<meta property="og:title" content="ログイン - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?>">
<meta property="og:description" content="<?php echo safetext($serversettings["serverinfo"]["server_name"]);?>にログイン">
<meta property="og:url" content="https://<?php echo safetext($domain); ?>/login">
<meta property="og:image" content="<?php echo safetext($serversettings["serverinfo"]["server_icon"]);?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="ログイン - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?>">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="ログイン - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?>"/>
<meta name="twitter:description" content="<?php echo safetext(preg_replace('/\r\n|\r|\n/', '', $serverinfo));?>"/>
<!--OGPここまで-->
<link rel="stylesheet" href="css/style.css">
<script src="js/jquery-min.js"></script>
<script src="js/unsupported.js"></script>
<script src="js/back.js"></script>
<?php if(!empty(H_CAPTCHA_ONOFF && H_CAPTCHA_ONOFF == "true")){?>
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
<?php }?>
<?php if(!empty(CF_TURNSTILE_ONOFF && CF_TURNSTILE_ONOFF == "true")){?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php }?>
<link rel="apple-touch-icon" type="image/png" href="favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ログイン - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
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
        <h1>ログイン</h1>

        <p>IDとパスワードを入力してください！</p>

            <?php if( !empty($error_message) ): ?>
                <ul class="errmsg">
                    <?php foreach( $error_message as $value ): ?>
                        <p>・ <?php echo $value; ?></p>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form class="formarea" method="post">
                <div>
                    <p>ユーザーID</p>
                    <input onInput="checkForm(this)" id="userid" class="inbox" type="text" name="userid" value="<?php if( !empty($_SESSION['form_data']['userid']) ){ echo safetext($_SESSION['form_data']['userid']); } ?>">
                </div>
                <div>
                    <p>パスワード</p>
                    <input id="password" class="inbox" type="password" name="password" maxlength="256" value="<?php if( !empty($_SESSION['form_data']['password']) ){ echo safetext($_SESSION['form_data']['password']); } ?>">
                </div>
                <div class="switch_flexbox">
                    <div class="switch_button">
                        <input id="passview" class="switch_input" type='checkbox' name="passview" value=""/>
                        <label for="passview" class="switch_label"></label>
                    </div>
                    <p>パスワードを表示する</p>
                </div>
                <div class="p2">パスワードに絵文字や日本語を使用している場合はパスワードの表示をオンにして入力してください。</div>

                <?php if(!empty(H_CAPTCHA_ONOFF && H_CAPTCHA_ONOFF == "true")){?>
                    <div class="captcha_zone">
                    <div class="p2">人間だと思いますが一応お伺いします...<br>人間ですか？<br>人間の場合はチェックボックスにチェックしてください！</div>
                        <div class="h-captcha" data-sitekey="<?php echo safetext(H_CAPTCHA_SITE_KEY);?>"></div>
                    </div>
                <?php }?>

                <?php if(!empty(CF_TURNSTILE_ONOFF && CF_TURNSTILE_ONOFF == "true")){?>
                    <div class="captcha_zone">
                        <div class="cf-turnstile" data-sitekey="<?php echo safetext(CF_TURNSTILE_SITE_KEY);?>" data-callback="javascriptCallback" data-language="ja"></div>
                    </div>
                <?php }?>
                
                <input type="submit" name="btn_submit" class="irobutton" value="ログイン">
            </form>

            <div class="btnbox">
                <a href="index.php" class="sirobutton">戻る</a>
                <a href="passrecovery" class="sirobutton">パスワード復元</a>
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
$("#passview").click(function () {
    if ($("#passview").prop("checked") == true) {
        $('#password').get(0).type = 'text';
    } else {
        $('#password').get(0).type = 'password';
    }
});

</script>


</body>
</html>