<?php 
require("../function/function.php");

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

?>

<!DOCTYPE html>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css">
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>残念なお知らせ - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
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
        <h1>パスワード変更不可</h1>

        <p>申し訳ございませんがお使いのアカウントのパスワードは変更できません。</p>

        <div class="btnbox">
            <a href="../index.php" class="sirobutton">もどる</a>
        </div>
    </div>
</div>

</body>

</html>