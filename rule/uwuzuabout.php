<!DOCTYPE html>

<?php
$serverlogofile = "../server/serverlogo.txt";
$serverlogodata = file_get_contents($serverlogofile);
$serverlogodata = explode( "\n", $serverlogodata );
$cnt = count( $serverlogodata );
for( $i=0;$i<$cnt;$i++ ){
    $serverlogo_link[$i] = ($serverlogodata[$i]);
}

$servernamefile = "../server/servername.txt";

//-------------------------------------

$domain = $_SERVER['HTTP_HOST'];

//------------------------

$contactfile = "../server/contact.txt";

$termsfile = "../server/uwuzuabout.txt";
$termsdata = file_get_contents($termsfile);

$softwarefile = "../server/uwuzuinfo.txt";
$softwaredata = file_get_contents($softwarefile);

$softwaredata = explode( "\n", $softwaredata );
$cnt = count( $softwaredata );
for( $i=0;$i<$cnt;$i++ ){
    $uwuzuinfo[$i] = ($softwaredata[$i]);
}

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
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title><?php echo $uwuzuinfo[0]?>について - <?php echo file_get_contents($servernamefile);?></title>
</head>


<body>

<div class="topbox">
    <?php if(!empty($serverlogo_link[1])){ ?>
        <div class="logo">
            <a href="../index.php"><img src=<?php echo htmlspecialchars($serverlogo_link[1], ENT_QUOTES, 'UTF-8');?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="../index.php"><img src="../img/uwuzulogo.svg"></a>
        </div>
    <?php }?>
</div>

<div class="terms">

    <h1><?php echo $uwuzuinfo[0]?>について</h1>
    <div class="p3"><?php echo file_get_contents($servernamefile);?></div>
    <div class="p2c"><?php echo $domain;?></div>
    <div class="p3"><?php echo $uwuzuinfo[0];?></div>
    <div class="p2c">Version : <?php echo $uwuzuinfo[1];?><br>Developer : <?php echo $uwuzuinfo[3];?><br>Last Update : <?php echo $uwuzuinfo[2];?></div>
    

    <p><?php echo $htmltext;?></p>

    <a href = "javascript:history.back();" class="irobutton">戻る</a>

</div>


</body>

</html>