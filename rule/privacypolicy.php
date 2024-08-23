<!DOCTYPE html>

<?php
require("../function/function.php");

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

//-------------------------------------

$domain = $_SERVER['HTTP_HOST'];

//------------------------

$privacypolicyfile = "../server/privacypolicy.txt";
$privacypolicydata = file_get_contents($privacypolicyfile);

$sprivacypolicy = explode("\n", $privacypolicydata);
$htmltext = '';  // 初期化

function processMarkdownRules($markdownText) {

    // コード（#code）をHTMLのdiv class="code"タグに変換
    $markdownText = preg_replace('/^#code (.+)/m', '<div class="code"><p>$1</p></div>', $markdownText);

    // 画像（#img）をHTMLのimgタグに変換
    $markdownText = preg_replace('/^#img (.+)/m', '<img src="$1">', $markdownText);
    
    // タイトル（#、##、###）をHTMLのhタグに変換
    $markdownText = preg_replace('/^# (.+)/m', '<h2>$1</h2>', $markdownText);
    $markdownText = preg_replace('/^## (.+)/m', '<h3>$1</h3>', $markdownText);
    $markdownText = preg_replace('/^### (.+)/m', '<h4>$1</h4>', $markdownText);

    // 箇条書き（-）をHTMLのul/liタグに変換
    $markdownText = preg_replace('/^- (.+)/m', '<ul><li>$1</li></ul>', $markdownText);

    // 空行の前に何もない行をHTMLのpタグに変換
    $markdownText = preg_replace('/(^\s*)(?!\s)(.*)/m', '$1<p>$2</p>', $markdownText);

    return $markdownText;
}


foreach ($sprivacypolicy as $privacypolicy) {
    $markdowntext = $privacypolicy;
    $convertedText = processMarkdownRules($markdowntext);
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
<title>プライバシーポリシー - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
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

    <h1><?php echo safetext($serversettings["serverinfo"]["server_name"]);?>プライバシーポリシー</h1>
    <div class="p3"><?php echo safetext($serversettings["serverinfo"]["server_name"]);?></div>
    <div class="p2c"><?php echo $domain;?></div>

    <p><?php echo $htmltext;?></p>

    <a href = "javascript:history.back();" class="irobutton">戻る</a>

</div>


</body>

</html>