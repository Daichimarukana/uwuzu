<!DOCTYPE html>

<?php

require('../db.php');
//関数呼び出し
//- php.iniのファイル最大サイズ
require('../function/function.php');

session_name('uwuzu_s_id');
session_start();

$mojisizefile = "../server/textsize.txt";
$mojisize = safetext(file_get_contents($mojisizefile));

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);


//-------------------------------------

$domain = $_SERVER['HTTP_HOST'];

//------------------------

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

//User
$result = $mysqli->query("SELECT userid FROM account");
$count1 = $result->num_rows;
//ueuse
$result2 = $mysqli->query("SELECT uniqid FROM ueuse");
$count2 = $result2->num_rows;
//emoji
$result3 = $mysqli->query("SELECT sysid FROM emoji");
$count3 = $result3->num_rows;
//bot
$result4 = $mysqli->query("SELECT userid FROM account WHERE sacinfo = 'bot'");
$count4 = $result4->num_rows;


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
<title>サーバー情報 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
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

    <h1>サーバー情報</h1>
    <!--(サーバーアイコン)-->
    <?php if( !empty($serversettings["serverinfo"]["server_head"]) ){ ?>
        <div class="serverhead">
            <img src="<?php echo safetext($serversettings["serverinfo"]["server_head"]); ?>">
        </div>
        <?php }?>
        <?php if( !empty($serversettings["serverinfo"]["server_icon"]) ){ ?>
        <div class="servericon">
            <?php if( !empty($serversettings["serverinfo"]["server_head"]) ){ ?>
                <div class="up">
                    <img src="<?php echo safetext($serversettings["serverinfo"]["server_icon"]); ?>">
                </div>
            <?php }else{?>
                <img src="<?php echo safetext($serversettings["serverinfo"]["server_icon"]); ?>">
            <?php }?>
        </div>
    <?php }?>
    <!--(サーバーアイコンここまで)-->
    <div class="sp3"><?php echo safetext($serversettings["serverinfo"]["server_name"]);?></div>
    <div class="sp2c"><?php echo $domain;?></div>
    <hr>
    <h4>説明</h4>
    <p><?php
        $sinfo = explode("\n", $serverinfo);
        foreach ($sinfo as $info) {
            echo $info.'<br>';
        }?></p>
    <hr>
    <h4>制限</h4>
    <p>ファイルサイズの上限 : <?php echo x1024(x1024(File_MaxUploadSize()));?>MB</p>
    <p>ユーズの文字数の上限 : <?php echo $mojisize;?>文字</p>
    <hr>

    <h4>管理者情報</h4>
    <p>管理者名 : <?php echo safetext($serversettings["serverinfo"]["server_admin"]);?></p>
    <p>連絡用メールアドレス : <?php echo safetext($serversettings["serverinfo"]["server_admin_mailadds"]);?></p>

    <hr>

    <h4>統計情報</h4>
    <div class="overview">
        <div class="overview_cnt_l">
            <div class="p2">ユーザー数</div>
            <p><b><?php echo safetext($count1);?></b></p>
        </div>
        <div class="overview_cnt_r">
            <div class="p2">投稿数</div>
            <p><b><?php echo safetext($count2);?></b></p>
        </div>
    </div>
    <div class="overview">
        <div class="overview_cnt_l">
            <div class="p2">カスタム絵文字数</div>
            <p><b><?php echo safetext($count3);?></b></p>
        </div>
        <div class="overview_cnt_r">
            <div class="p2">Botアカウント数</div>
            <p><b><?php echo safetext($count4);?></b></p>
        </div>
    </div>

    <?php if(safetext($serversettings["serverinfo"]["server_invitation"]) == "true"){?>
    <hr>
    <h4>注意</h4>
    <p>このサーバーにアカウント登録するには招待コードが必要です。</p>
    <?php }?>
    <hr>
    <h4>サーバーソフトウェア</h4>
    <div class="p3" translate="no"><?php echo $uwuzuinfo[0];?></div>
    <div class="p2c">Version : <?php echo $uwuzuinfo[1];?><br>Developer : <?php echo $uwuzuinfo[3];?><br>Last Update : <?php echo $uwuzuinfo[2];?></div>

    <a href = "javascript:history.back();" class="irobutton">戻る</a>

</div>


</body>

</html>