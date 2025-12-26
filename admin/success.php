<?php 
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

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
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

if(empty($error_message)){
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.tables 
        WHERE table_schema = :schema AND table_name = :table
        LIMIT 1
    ");
    $stmt->execute([
        ':schema' => DB_NAME,
        ':table' => "ipblock",
    ]);

    $exists = $stmt->fetchColumn() > 0;

    if ($exists) {
        blockedIP($_SERVER['REMOTE_ADDR']);
    }
}else{
    header("Location: index.php");
    exit;
}

$aduser = "yes";

$query = $pdo->prepare('SELECT * FROM account WHERE admin = :adminuser limit 1');

$query->execute(array(':adminuser' => $aduser));

$result2 = $query->fetch();

if($result2 > 0){
    header("Location: ../login.php");
	exit;
}

$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-1000, '/');
}
session_destroy();

?>

<!DOCTYPE html>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css">
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>管理者アカウント登録完了!!! - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
</head>

<script src="../js/back.js"></script>
<body>



<div class="leftbox2">
    <div class="logo">
        <img src="../img/uwuzulogo.svg">
    </div>

    <div class="textbox">
        <h1>管理者アカウント登録完了！</h1>
        <p><br>いぇ～い！</p>
        <p>🎉🎉🎉🎊🎊🎊✨✨✨</p>
        <p>管理者アカウントの登録が完了しました！</p>
        <p>以下のログインボタンよりログインしてください！<br>ログイン後は画面左側メニューの「サーバー設定」よりサーバーの情報を設定することをおすすめします！<br>また、左側メニューの「設定」から二段階認証の設定をすることもおすすめします。</p>

        <div class="btnbox">
            <a href="../login.php" class="sirobutton">ログイン</a>
        </div>
    </div>
</div>

</body>

</html>