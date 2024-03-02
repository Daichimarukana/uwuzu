<?php

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}

require('db.php');

$serversettings_file = "server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);


session_name('uwuzu_s_id');
session_set_cookie_params(0, '', '', true, true);
session_start();

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;


$userid = $_SESSION['userid'];


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
<script src="js/unsupported.js"></script>
<link rel="apple-touch-icon" type="image/png" href="favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>アカウント登録 - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>
</head>


<script src="js/back.js"></script>
<body>


<div class="leftbox2">
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