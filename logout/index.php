<?php
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time()-1000);
        setcookie($name, '', time()-1000, '');
        setcookie($name, '', time()-1000, '/');
        setcookie($name, '', time()-1000, '/emoji');
        setcookie($name, '', time()-1000, '/home');
        setcookie($name, '', time()-1000, '/notice');
        setcookie($name, '', time()-1000, '/notification');
        setcookie($name, '', time()-1000, '/others');
        setcookie($name, '', time()-1000, '/search');
        setcookie($name, '', time()-1000, '/settings');
        setcookie($name, '', time()-1000, '/emoji');
        setcookie($name, '', time()-1000, '/user');
        setcookie('admin_login', '', time()-1000, '');
        setcookie('loginid', '', time()-1000, '');
        setcookie('userid', '', time()-1000, '');
        setcookie('username', '', time()-1000, '');
    }
}
$_SESSION = null;
?>

<!DOCTYPE html>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css?<?php echo date('Ymd-Hi'); ?>">
<script src="../js/console_notice.js?<?php echo date('Ymd-Hi'); ?>"></script>
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ログアウト完了 - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>
</head>

<script src="../js/back.js"></script>
<body>



<div class="leftbox2">
    <div class="logo">
        <img src="../img/uwuzulogo.svg">
    </div>

    <div class="textbox">
        <h1>ログアウト完了</h1>
        <p><br>ログアウトが完了しました！</p>
        <p>ボタンを押すとログインページにリダイレクトします。</p>

        <div class="btnbox">
            <a href="../login.php" class="sirobutton">ログイン</a>
        </div>
    </div>
</div>

</body>

</html>