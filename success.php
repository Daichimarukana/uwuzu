<?php 
$servernamefile = "server/servername.txt";
?>

<!DOCTYPE html>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="css/style.css">
<link rel="apple-touch-icon" type="image/png" href="favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>アカウント登録完了!!! - <?php echo file_get_contents($servernamefile);?></title>
</head>

<script src="js/back.js"></script>
<body>



<div class="leftbox2">
    <div class="logo">
        <img src="img/uwuzulogo.svg">
    </div>

    <div class="textbox">
        <h1>アカウント作成完了！</h1>
        <p><br>いぇ～い！</p>
        <p>88888888888</p>
        <p>アカウント登録が完了しました！</p>

        <div class="btnbox">
            <a href="login.php" class="sirobutton">ログイン</a>
        </div>
    </div>
</div>

</body>

</html>