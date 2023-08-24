<?php
$servernamefile = "../server/servername.txt";
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
    }
}
?>

<!DOCTYPE html>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ログアウト完了 - <?php echo file_get_contents($servernamefile);?></title>
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