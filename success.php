<?php 
require("function/function.php");
$serversettings_file = "server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);


if(!(empty($_SESSION['backupcode']))){
    $backupcode = $_SESSION['backupcode'];
}else{
    $backupcode = null;
}
if(!(empty($_SESSION['done']))){
    if($_SESSION['done'] == false){
        $error_message[] = "アカウント移行の終了処理が完了できていません。\n前使用していたサーバーでアカウントの移行を取り消してください。";
    }
}
?>

<!DOCTYPE html>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="css/style.css">
<script src="js/jquery-min.js"></script>
<script src="js/unsupported.js"></script>
<link rel="apple-touch-icon" type="image/png" href="favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>アカウント登録完了!!! - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
</head>

<script src="js/back.js"></script>
<body>



<div class="leftbox2">
    <?php if(!empty(safetext($serversettings["serverinfo"]["server_logo_login"]))){ ?>
        <div class="logo">
            <a href="index.php"><img src=<?php echo safetext($serversettings["serverinfo"]["server_logo_login"]);?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="index.php"><img src="img/uwuzulogo.svg"></a>
        </div>
    <?php }?>

    <div class="textbox">
        <?php if( !empty($error_message) ): ?>
            <ul class="errmsg">
                <?php foreach( $error_message as $value ): ?>
                    <p>・ <?php echo $value; ?></p>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <h1>アカウント作成完了！</h1>
        <p>いぇ～い！</p>
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