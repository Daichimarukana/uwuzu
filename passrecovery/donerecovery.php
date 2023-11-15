<?php 
$servernamefile = "../server/servername.txt";

$serverlogofile = "../server/serverlogo.txt";
$serverlogodata = file_get_contents($serverlogofile);
$serverlogodata = explode( "\n", $serverlogodata );
$cnt = count( $serverlogodata );
for( $i=0;$i<$cnt;$i++ ){
    $serverlogo_link[$i] = ($serverlogodata[$i]);
}
?>

<!DOCTYPE html>

<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>パスワード変更完了 - <?php echo file_get_contents($servernamefile);?></title>
</head>

<script src="back.js"></script>
<body>



<div class="leftbox2">
    <?php if(!empty(htmlspecialchars($serverlogo_link[1], ENT_QUOTES, 'UTF-8'))){ ?>
        <div class="logo">
            <a href="../index.php"><img src=<?php echo htmlspecialchars($serverlogo_link[1], ENT_QUOTES, 'UTF-8');?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="../index.php"><img src="../img/uwuzulogo.svg"></a>
        </div>
    <?php }?>

    <div class="textbox">
        <h1>パスワードの変更が完了しました！</h1>

        <p>パスワードの変更が完了しました。下のボタンよりログインしてください！</p>

        <div class="btnbox">
            <a href="../login.php" class="sirobutton">ログイン</a>
        </div>
    </div>
</div>

</body>

</html>