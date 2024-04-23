<?php

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}

require('../db.php');

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
session_set_cookie_params(0, '', '', true, true);
session_start();

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;

if(!(empty(DB_NAME) && empty(DB_HOST) && empty(DB_USER) && empty(DB_PASS))){
    try {

        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
    
    } catch(PDOException $e) {
    
        // 接続エラーのときエラー内容を取得する
        $error_message[] = $e->getMessage();
    }
    
    $aduser = "yes";
    
    $options = array(
        // SQL実行失敗時に例外をスルー
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // デフォルトフェッチモードを連想配列形式に設定
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // バッファードクエリを使う（一度に結果セットを全て取得し、サーバー負荷を軽減）
        // SELECTで得た結果に対してもrowCountメソッドを使えるようにする
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    );
    
    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
    
    $query = $dbh->prepare('SELECT * FROM account WHERE admin = :adminuser limit 1');
    
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

if (in_array("gd", get_loaded_extensions())) {
    $check_gd = true;
} else {
    $check_gd = false;
}
if (in_array("fileinfo", get_loaded_extensions())) {
    $check_fileinfo = true;
} else {
    $check_fileinfo = false;
}
if (in_array("mbstring", get_loaded_extensions())) {
    $check_mbstring = true;
} else {
    $check_mbstring = false;
}
if (in_array("pdo_mysql", get_loaded_extensions())) {
    $check_pdo_mysql = true;
} else {
    $check_pdo_mysql = false;
}
// データベースの接続を閉じる
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
<title>uwuzuへようこそ！！！ - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>
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
        <script src="back.js"></script>

        <p>おめでとうございます！！！</p>
        <p>uwuzuの導入が完了しました！</p>
        <p>これよりuwuzuのセットアップを開始します！<br>
            セットアップを始める前に、PHPの必須モジュールがインストールされているか、以下の欄をみてご確認ください。<br>
            Not setが一つでもある場合は再度モジュールの設定を行ってください！<br>
            <br>
            <?php if($db_php == true){?>
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
            セットアップ中にエラーに遭遇した場合はuwuzu.comを確認し、解消に向けて取り組みましょう！</p>
        
        <div class="module_chk">
            <div class="p2">Already setが設定済みでNot setが未設定です。</div>
            <p>GD : <?php if($check_gd == true){echo "Already set✅";}else{echo "Not set🟥";}?></p>
            <p>Fileinfo : <?php if($check_fileinfo == true){echo "Already set✅";}else{echo "Not set🟥";}?></p>
            <p>mbstring : <?php if($check_mbstring == true){echo "Already set✅";}else{echo "Not set🟥";}?></p>
            <p>pdo_mysql : <?php if($check_pdo_mysql == true){echo "Already set✅";}else{echo "Not set🟥";}?></p>
        </div>


        <p>uwuzu<br>Version : <?php echo $uwuzuinfo[1]?></p>
            <div class="btnbox">
                <a href="setup_db_php.php" class="irobutton">セットアップ開始！</a>
            </div>
        </div>
        
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