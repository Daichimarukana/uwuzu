<?php 
$servernamefile = "server/servername.txt";

$serverlogofile = "server/serverlogo.txt";
$serverlogodata = file_get_contents($serverlogofile);
$serverlogodata = explode( "\n", $serverlogodata );
$cnt = count( $serverlogodata );
for( $i=0;$i<$cnt;$i++ ){
    $serverlogo_link[$i] = ($serverlogodata[$i]);
}

if(!(empty($_SESSION['backupcode']))){
    $backupcode = $_SESSION['backupcode'];
}else{
    $backupcode = null;
}
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
    <?php if(!empty($serverlogo_link[1])){ ?>
        <div class="logo">
            <a href="../index.php"><img src=<?php echo htmlspecialchars($serverlogo_link[1], ENT_QUOTES, 'UTF-8');?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="../index.php"><img src="../img/uwuzulogo.svg"></a>
        </div>
    <?php }?>

    <div class="textbox">
        <h1>アカウント作成完了！</h1>
        <p><br>いぇ～い！</p>
        <p>88888888888</p>
        <p>アカウント登録が完了しました！</p>
        <?php 
        if(!(empty($backupcode))){?>
        <p>バックアップコードは以下のものです！<br>以下のコードでスマートフォンをなくしてしまったなどのもしものときにログインいただけます。<br>絶対に大切に保管してください！<br>また、そのバックアップコードは絶対に公開しないでください。</p>
		<p><?php echo $backupcode;?>
        <?php }?>

        <div class="btnbox">
            <a href="login.php" class="sirobutton">ログイン</a>
        </div>
    </div>
</div>

</body>

</html>