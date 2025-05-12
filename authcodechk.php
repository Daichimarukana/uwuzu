<?php

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
$pdo = null;
$stmt = null;
$res = null;
$option = null;


if( !empty($_SESSION['userid']) ) {
    $userid = $_SESSION['userid'];
}else{
    header("Location: login.php");
	exit;
}


// データベースに接続

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

if( !empty($_POST['btn_submit']) ) {
    $_SESSION['userid'] = $userid;
    // リダイレクト先のURLへ転送する
    $url = 'addauthcode.php';
    header('Location: ' . $url, true, 303);

    // すべての出力を終了
    exit;
}

if( !empty($_POST['skip_submit']) ) {
    if (isset($_SERVER['HTTP_COOKIE'])) {
		$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
		foreach($cookies as $cookie) {
			$parts = explode('=', $cookie);
			$name = trim($parts[0]);
			setcookie($name, '', time()-1000);
			setcookie($name, '', time()-1000, '/');
		}
	}
    $userid = "";
    // リダイレクト先のURLへ転送する
    $url = 'success.php';
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


<div class="leftbox2">
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

        <p>二段階認証を設定しますか？</p>
        <p>二段階認証を設定することによりログイン時の一時キーが必要となりセキュリティを強化することが出来ます。<br>設定にはGoogleAuthenticatorなどの二段階認証アプリが必要です。</p>

            <?php if( !empty($error_message) ): ?>
                <ul class="errmsg">
                    <?php foreach( $error_message as $value ): ?>
                        <p>・ <?php echo $value; ?></p>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
                
        <form class="formarea" enctype="multipart/form-data" method="post">
                <input type="submit" class = "irobutton" name="btn_submit" value="登録">
                <input type="submit" class = "sirobutton" name="skip_submit" value="スキップ">
        </form>
        
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