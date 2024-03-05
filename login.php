<?php
$serversettings_file = "server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);


require('db.php');
//hCaptcha--------------------------------------------
require('settings_admin/hCaptcha_settings/hCaptcha_settings.php');
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
session_set_cookie_params(0, '', '', true, true);
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
if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true && isset($_COOKIE['loginid']) && isset($_SESSION['userid'])) {
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
    $acck = $dbh->prepare("SELECT userid, loginid FROM account WHERE userid = :userid");
    $acck->bindValue(':userid', $_SESSION['userid']);
    $acck->execute();
    $acck_data = $acck->fetch();
    if(!empty($acck_data)){
        if($_COOKIE['loginid'] === $acck_data["loginid"] && $_SESSION['userid'] === $acck_data["userid"] ){
            header("Location: home/index.php");
            exit;
        }
    }
} elseif (isset($_COOKIE['admin_login']) && $_COOKIE['admin_login'] == true && isset($_COOKIE['loginid']) && isset($_COOKIE['userid'])) {
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
    $acck = $dbh->prepare("SELECT userid, loginid FROM account WHERE userid = :userid");
    $acck->bindValue(':userid', $_COOKIE['userid']);
    $acck->execute();
    $acck_data = $acck->fetch();
    if(!empty($acck_data)){
        if($_COOKIE['loginid'] === $acck_data["loginid"] && $_COOKIE['userid'] === $acck_data["userid"] ){
            header("Location: home/index.php");
            exit;
        }
    }
}

if( !empty($_POST['btn_submit']) ) {

    $userid = htmlentities($_POST['userid']);
    $password = htmlentities($_POST['password']);

    if(!empty(CAPTCHA && CAPTCHA == "true")){
        if(isset($_POST['h-captcha-response'])){
            $hcaptcha_token = htmlentities($_POST['h-captcha-response']);
            if($hcaptcha_token){
                $captcha_data = [
                    'secret' => htmlentities(SEAC_KEY),
                    'response' => $hcaptcha_token,
                    'sitekey' => htmlentities(SITE_KEY)
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
                    $error_message[] = "あなたが人間である確認ができませんでした。(ERROR)";
                }
            }else{
                $error_message[] = "あなたが人間である確認ができませんでした。(ERROR)";
            }
        }else{
            $error_message[] = "あなたが人間である確認ができませんでした。(ERROR)";
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
    // SQL実行
    $result->execute();



    // ... (前略)
        // IDの入力チェック
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
            if($result->rowCount() > 0) {
                $row = $result->fetch(); // ここでデータベースから取得した値を $row に代入する

                if($row["userid"] == $userid){
                    if(password_verify($password,$row["password"])){
                        if(empty($row["authcode"])){
                            $_SESSION['admin_login'] = true;

                            $_SESSION['userid'] = $userid;
                            $_SESSION['loginid'] = $row["loginid"];
                            // リダイレクト先のURLへ転送する
                            $url = 'check.php';
                            header('Location: ' . $url, true, 303);

                            // すべての出力を終了
                            exit;
                        }else{
                            $_SESSION['userid'] = $userid;
                            $url = 'authlogin.php';
                            header('Location: ' . $url, true, 303);

                            // すべての出力を終了
                            exit;
                        }
                    }
                    else{
                        $error_message[] = 'IDまたはパスワードが違います(PASS_AND_ID_CHIGAUYANKE)'; 
                    }
                }else{
                    $error_message[] = 'IDまたはパスワードが違います(PASS_AND_ID_CHIGAUYANKE)'; 
                }
            }
            else {
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
<meta property="og:title" content="ログイン - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?>">
<meta property="og:description" content="<?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?>にログイン">
<meta property="og:url" content="https://<?php echo htmlentities($domain, ENT_QUOTES, 'UTF-8'); ?>/login">
<meta property="og:image" content="<?php echo htmlspecialchars($serversettings["serverinfo"]["server_icon"], ENT_QUOTES, 'UTF-8');?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="ログイン - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?>">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="ログイン - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?>"/>
<meta name="twitter:description" content="<?php echo htmlentities($serverinfo);?>"/>
<!--OGPここまで-->
<link rel="stylesheet" href="css/style.css">
<script src="js/unsupported.js"></script>
<?php if(!empty(CAPTCHA && CAPTCHA == "true")){?>
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
<?php }?>
<link rel="apple-touch-icon" type="image/png" href="favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ログイン - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>
</head>

<script src="js/back.js"></script>
<body>

<div class="leftbox">
    <?php if(!empty(htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8'))){ ?>
        <div class="logo">
            <a href="../index.php"><img src=<?php echo htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8');?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="../index.php"><img src="img/uwuzulogo.svg"></a>
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
                    <label for="userid">ユーザーID</label>
                    <input onInput="checkForm(this)" id="userid" class="inbox" type="text" name="userid" value="<?php if( !empty($_SESSION['userid']) ){ echo htmlentities( $_SESSION['userid'], ENT_QUOTES, 'UTF-8'); } ?>">
                </div>
                <div>
                    <label for="password">パスワード</label>
                    <input id="password" class="inbox" type="password" name="password" maxlength="32" value="<?php if( !empty($_SESSION['password']) ){ echo htmlentities( $_SESSION['password'], ENT_QUOTES, 'UTF-8'); } ?>">
                </div>
                <?php if(!empty(CAPTCHA && CAPTCHA == "true")){?>
                    <div class="captcha_zone">
                    <div class="p2">人間だと思いますが一応お伺いします...<br>人間ですか？<br>人間の場合はチェックボックスにチェックしてください！</div>
                        <div class="h-captcha" data-sitekey="<?php echo htmlentities(SITE_KEY);?>"></div>
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


window.onload = function(){
var ele = document.getElementsByTagName("body")[0];
var n = Math.floor(Math.random() * 3); // 3枚の画像がある場合
ele.style.backgroundImage = "url(img/titleimg/"+n+".png)";
}

</script>


</body>
</html>