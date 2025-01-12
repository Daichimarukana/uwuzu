<?php 
require('../db.php');
require("../function/function.php"); 
blockedIP($_SERVER['REMOTE_ADDR']);

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
$_SESSION = array(); 
session_regenerate_id(true);
session_destroy(); 

if (isset($_SERVER['HTTP_COOKIE'])) { 
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']); 
    foreach ($cookies as $cookie) { 
        $parts = explode('=', $cookie); 
        $name = trim($parts[0]); 
        
        // Cookie削除（パスとドメインを指定）
        setcookie($name, '', time() - 3600, '/'); 
        setcookie($name, '', time() - 3600, '/', $_SERVER['HTTP_HOST']); 
    } 
} 
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
            <a href="../index.php" class="sirobutton">ログイン</a>
        </div>
    </div>
</div>

</body>

</html>