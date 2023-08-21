<?php 
$servernamefile = "../server/servername.txt";
?>

<!DOCTYPE html>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>残念なお知らせ - <?php echo file_get_contents($servernamefile);?></title>
</head>

<script src="back.js"></script>
<body>



<div class="leftbox2">
    <div class="logo">
        <img src="../img/uwuzulogo.svg">
    </div>

    <div class="textbox">
        <h1>パスワード変更不可</h1>

        <p>申し訳ございませんがお使いのアカウントのパスワードは変更できません。</p>

        <div class="btnbox">
            <a href="../index.php" class="sirobutton">もどる</a>
        </div>
    </div>
</div>

</body>

</html>