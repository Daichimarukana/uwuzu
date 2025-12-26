<?php
require('../db.php');
require("../function/function.php");

$softwarefile = "../server/uwuzuinfo.txt";
$softwaredata = file_get_contents($softwarefile);

$softwaredata = explode( "\n", $softwaredata );
$cnt = count( $softwaredata );
for( $i=0;$i<$cnt;$i++ ){
    $uwuzuinfo[$i] = ($softwaredata[$i]);
}

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

session_name('uwuzu_s_id');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;
$db_error = false;

if(!(defined("DB_NAME")) || !(defined("DB_HOST")) || !(defined("DB_USER")) || !(defined("DB_PASS"))){
    $db_new_settings = "
        <?php // データベースの接続情報
        define( 'DB_HOST', '');
        define( 'DB_USER', '');
        define( 'DB_PASS', '');
        define( 'DB_NAME', '');

        // ENC_KEYは操作しないでください。ユーザーデータを使用できなくなるおそれがあります。
        define( 'ENC_KEY', '');

        define( 'RATE_LM', ''); // レートリミット(ユーズ/分)
        define( 'STOP_LA', ''); // 自動停止ロードアベレージ上限
        // タイムゾーン設定
        date_default_timezone_set('Asia/Tokyo');
        ?>
	";

	//サーバー設定上書き
	$file = fopen("../db.php", 'w');
	$data = $db_new_settings;
	fputs($file, $data);
	fclose($file);

    $error_message[] = "db.phpを初期化しました。ページを再読込してください。";
    $db_error = true;
    $db_php = false;
}else if(!(empty(DB_NAME) && empty(DB_HOST) && empty(DB_USER) && empty(DB_PASS))){
    try {

        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
    
    } catch(PDOException $e) {
        $error_message[] = $e->getMessage();
        $db_error = true;
    }

    if(empty($error_message)){
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = :schema AND table_name = :table
            LIMIT 1
        ");
        $stmt->execute([
            ':schema' => DB_NAME,
            ':table' => "ipblock",
        ]);

        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            blockedIP($_SERVER['REMOTE_ADDR']);
        }
    
        $aduser = "yes";
        
        $query = $pdo->prepare('SELECT * FROM account WHERE admin = :adminuser limit 1');
        
        $query->execute(array(':adminuser' => $aduser));
        
        $result2 = $query->fetch();
        
        if($result2 > 0){
            header("Location: ../login.php");
            exit;
        }
        
        $db_php = true;
    }else{
        $db_php = false;
    }
}else{
    $db_php = false;
}

$extensions_to_check = [
    "gd" => "GD",
    "fileinfo" => "Fileinfo",
    "mbstring" => "mbstring",
    "pdo_mysql" => "pdo_mysql",
    "mysqli" => "mysqli",
    "zip" => "ZipArchive",
    "curl" => "cURL"
];

$loaded_extensions = get_loaded_extensions();
$extension_status = [];

foreach ($extensions_to_check as $extension_id => $display_name){
    $is_loaded = in_array($extension_id, $loaded_extensions);
    $extension_status[$display_name] = $is_loaded;
}

$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css">
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<link rel="apple-touch-icon" type="../image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>uwuzuへようこそ！！！ - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
</head>


<script src="../js/back.js"></script>
<body>


<div class="leftbox">
    <div class="logo">
        <img src="../img/uwuzulogo.svg">
    </div>

    <div class="textbox">
        <h1>uwuzuへようこそ！！！</h1>
            <?php if( !empty($error_message) ): ?>
                <ul class="errmsg">
                    <?php foreach( $error_message as $value ): ?>
                        <p>・ <?php echo $value; ?></p>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <script src="../js/back.js"></script>

        <p>おめでとうございます！！！</p>
        <p>uwuzuの導入が完了しました！</p>

        <?php if($db_error === true){?>
            <p>uwuzuのセットアップをしたいところですが...<br>
                データベースの接続にエラーが発生しているようです。<br>
                上の赤枠のエラーコードへの対応をお願いします。<br>
                <br>
                なお、データベースの接続設定をやり直す場合は、db.phpを空欄のファイルにしてください。
            </p>
            <div class="p2">正常動作中のuwuzuのdb.phpを空欄のファイルにすると、これまで正常にデータベースに接続できていてデータを既に保存していた場合に、データの復号に問題が発生してしまい、uwuzuを使用できなくなるおそれがございます。<br>
                操作は慎重に...！</div>
        <?php }else{?>
        <p>これよりuwuzuのセットアップを開始します！<br>
            セットアップを始める前に、PHPの必須モジュールがインストールされているか、以下の欄をみてご確認ください。<br>
            Not setが一つでもある場合は再度モジュールの設定を行ってください！<br>
            <br>
            <?php if($db_php === true){?>
                db.phpの設定は済んでいるようですね、それでは早速セットアップを開始しましょう！
            <?php }else{?>
                また、uwuzuのセットアップを始める前に、以下の情報をあなたが知っている必要があります！<br>
                - データベース名(空のデータベースを用意してください。)<br>
                - データベースを管理できるユーザー名<br>
                - データベースへアクセスできるユーザーのパスワード<br>
                - データベースのホストアドレス<br>
                これらの情報はuwuzuがデータベースを使用するために必要で、uwuzu導入フォルダ内のdb.phpに保存されます。<br>
                もしこのあとうまくセットアップが継続できなければ手動でdb.phpに上の情報を保存してください！<br>
                これらのデータをあなたが知っているのであれば早速セットアップを開始しましょう！<br>
            <?php }?>
            <br>
            セットアップ中にエラーに遭遇した場合はdocs.uwuzu.comを確認し、解消に向けて取り組みましょう！</p>
        
            <div class="module_chk">
                <div class="p2">Already setが設定済みでNot setが未設定です。</div>
                <div class="p2">PHPの必須モジュールの確認は全ての必須モジュールを対象に行われるものではありません。php側にてデフォルトでインストール・有効になっているものはチェック・表示しない場合がございます。</div>
                <?php foreach ($extension_status as $name => $status): ?>
                    <p>
                        <?php echo htmlspecialchars($name); ?> :
                        <?php
                            echo $status ? "Already set✅" : "Not set🟥";
                        ?>
                    </p>
                <?php endforeach; ?>
            </div>


        <p>uwuzu<br>Version : <?php echo $uwuzuinfo[1]?></p>
            <div class="btnbox">
                <a href="setup_db_php.php" class="irobutton">セットアップ開始！</a>
            </div>
        </div>

        <?php }?>
        
    </div>
</div>


<script type="text/javascript">

function checkForm(inputElement) {
    var str = inputElement.value;
    while (str.match(/[^A-Za-z\d_]/)) {
        str = str.replace(/[^A-Za-z\d_]/, "");
    }
    inputElement.value = str;
}
</script>


</body>
</html>