<?php
require("../function/function.php");

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
    header("Location: " . $_SERVER['PHP_SELF']);
}
session_start();
$_SESSION = array();
session_destroy();
?>

<!DOCTYPE html>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css">
<script src="../js/jquery-min.js"></script>
<script src="../js/console_notice.js"></script>
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ログアウト完了 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
</head>

<script src="../js/back.js"></script>
<body>



<div class="leftbox2">
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
<script>
document.cookie.split(";").forEach(function(c) { document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); });
</script>