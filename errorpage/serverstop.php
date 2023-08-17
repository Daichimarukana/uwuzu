<!DOCTYPE html>

<?php
$servernamefile = "../server/servername.txt";

//-------------------------------------

$domain = $_SERVER['HTTP_HOST'];

//------------------------

$contactfile = "../server/contact.txt";

$termsfile = "../server/serverstop.txt";
$termsdata = file_get_contents($termsfile);

function processMarkdownAndWrapEmptyLines($markdownText) {

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
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>サーバー停止 - <?php echo file_get_contents($servernamefile);?></title>
</head>


<body>

<div class="topbox">
    <div class="logo">
        <img src="../img/uwuzulogo.svg">
    </div>
</div>

<div class="terms">

    <div class="p3"><?php echo file_get_contents($servernamefile);?></div>
    <div class="p2c"><?php echo $domain;?></div>
    <div class="err404">
    <h1>サーバー停止中</h1>
    <p>(/´･ヮ･`\)<br>現在サーバーが管理者によって停止されています...<br>停止の理由は以下の通りです。</p>

    <hr>
    <p><?php echo $htmltext;?></p>


    </div>

</div>


</body>

</html>