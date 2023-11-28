<!DOCTYPE html>

<?php

require('../db.php');

session_name('uwuzu_s_id');
session_start();

$servericonfile = "../server/servericon.txt";

//-------------------------
$serverlogofile = "../server/serverlogo.txt";
$serverlogodata = file_get_contents($serverlogofile);
$serverlogodata = explode( "\n", $serverlogodata );
$cnt = count( $serverlogodata );
for( $i=0;$i<$cnt;$i++ ){
    $serverlogo_link[$i] = ($serverlogodata[$i]);
}

$contactfile = "../server/contact.txt";

$adminfile = "../server/admininfo.txt";

$onlyuserfile = "../server/onlyuser.txt";
$onlyuser = file_get_contents($onlyuserfile);

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

$serverinfofile = '../server/info.txt';
$serverinfo = file_get_contents($serverinfofile);


// データベースに接続
try {
    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=utf8mb4;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);
} catch (PDOException $e) {
    // 接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$result = $mysqli->query("SELECT userid FROM account ORDER BY datetime");

/* 結果セットの行数を取得します */
$count1 = $result->num_rows;

$result2 = $mysqli->query("SELECT uniqid FROM ueuse ORDER BY datetime");

/* 結果セットの行数を取得します */
$count2 = $result2->num_rows;


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
<title>サーバー情報 - <?php echo file_get_contents($servernamefile);?></title>
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

    <h1>サーバー情報</h1>
    <?php if( !empty(file_get_contents($servericonfile)) ){ ?>
        <div class="servericon">
            <img src="<?php echo htmlspecialchars(file_get_contents($servericonfile), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
    <?php }?>
    <div class="sp3"><?php echo file_get_contents($servernamefile);?></div>
    <div class="sp2c"><?php echo $domain;?></div>
    <hr>
    <h4>説明</h4>
    <p><?php
        $sinfo = explode("\n", $serverinfo);
        foreach ($sinfo as $info) {
            echo $info.'<br>';
        }?></p>
    <hr>

    <h4>管理者情報</h4>
    <p>管理者名 : <?php echo htmlspecialchars(file_get_contents($adminfile), ENT_QUOTES, 'UTF-8');?></p>
    <p>連絡用メールアドレス : <?php echo htmlspecialchars(file_get_contents($contactfile), ENT_QUOTES, 'UTF-8');?></p>

    <hr>

    <h4>統計情報</h4>
    <p>ユーザー数 : <?php echo $count1."<br>"?></p>
    <p>投稿数 : <?php echo $count2."<br>"?></p>

    <?php if($onlyuser =="true"){?>
    <hr>
    <h4>注意</h4>
    <p>このサーバーにアカウント登録するには招待コードが必要です。</p>
    <?php }?>
    <hr>
    <h4>サーバーソフトウェア</h4>
    <div class="p3"><?php echo $uwuzuinfo[0];?></div>
    <div class="p2c">Version : <?php echo $uwuzuinfo[1];?><br>Developer : <?php echo $uwuzuinfo[3];?><br>Last Update : <?php echo $uwuzuinfo[2];?></div>

    <a href = "javascript:history.back();" class="irobutton">戻る</a>

</div>


</body>

</html>