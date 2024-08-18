<!DOCTYPE html>

<?php
require("../function/function.php");

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

//-------------------------------------

$domain = $_SERVER['HTTP_HOST'];

//------------------------

$termsfile = "../server/terms.txt";
$termsdata = file_get_contents($termsfile);

$sterms = explode("\n", $termsdata);
$htmltext = '';  // 初期化

foreach ($sterms as $terms) {
    $markdowntext = $terms;
    $convertedText = processMarkdownAndWrapEmptyLines($markdowntext);
    $htmltext .= $convertedText . "\n";  // 変換されたテキストを追加
}


?>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/home.css">
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>利用規約 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
</head>

<body>

<div class="topbox">
    <?php if(!empty(safetext($serversettings["serverinfo"]["server_logo_login"]))){ ?>
        <div class="logo">
            <a href="../index.php"><img src=<?php echo safetext($serversettings["serverinfo"]["server_logo_login"]);?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="../index.php"><img src="../img/uwuzulogo.svg"></a>
        </div>
    <?php }?>
</div>

<div class="terms">

    <h1><?php echo safetext($serversettings["serverinfo"]["server_name"]);?>利用規約</h1>
    <div class="p3"><?php echo safetext($serversettings["serverinfo"]["server_name"]);?></div>
    <div class="p2c"><?php echo $domain;?></div>

    <p><?php echo $htmltext;?></p>

    <a href = "javascript:history.back();" class="irobutton">戻る</a>

</div>


</body>

</html>