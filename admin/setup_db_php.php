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
    }else{
        header("Location: setup_uwuzu_db.php");
        exit;
    }
    
    $db_php = true;
}else{
    $db_php = false;
}

if(!(empty($_POST['btn_submit']))){
	$DataBase_Name = safetext($_POST['db_name']);
	$DataBase_User = safetext($_POST['db_user']);
	$DataBase_Pass = safetext($_POST['db_pass']);
	$DataBase_Host = safetext($_POST['db_host']);

    $Encryption_KEY = safetext(hash("sha3-512", bin2hex(random_bytes(64))));

    try {

        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname='.$DataBase_Name.';host='.$DataBase_Host , $DataBase_User, $DataBase_Pass, $option);
    
    } catch(PDOException $e) {
        $error_message[] = "データベースに接続できませんでした。\n設定は間違っていませんか？";
    }

    if(empty($error_message)){
        $DB_Settings = "
        <?php // データベースの接続情報
        define( 'DB_HOST', '".$DataBase_Host."');
        define( 'DB_USER', '".$DataBase_User."');
        define( 'DB_PASS', '".$DataBase_Pass."');
        define( 'DB_NAME', '".$DataBase_Name."');

        // ENC_KEYは操作しないでください。ユーザーデータを使用できなくなるおそれがあります。
        define( 'ENC_KEY', '".$Encryption_KEY."');

        // タイムゾーン設定
        date_default_timezone_set('Asia/Tokyo');
        ?>
        ";

        //設定上書き
        $file = fopen('../db.php', 'w');
        $data = $DB_Settings;
        fputs($file, $data);
        fclose($file);

        header("Location: setup_uwuzu_db.php");
        exit;  
    }
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
<title>db.phpのセットアップ - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
</head>


<script src="../js/back.js"></script>
<body>


<div class="leftbox">
    <div class="logo">
        <img src="../img/uwuzulogo.svg">
    </div>

    <div class="textbox">
        <h1>db.phpのセットアップ</h1>
            <?php if( !empty($error_message) ): ?>
                <ul class="errmsg">
                    <?php foreach( $error_message as $value ): ?>
                        <p>・ <?php echo $value; ?></p>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <script src="back.js"></script>

        <p>db.phpのセットアップを行います。<br>
            db.phpはuwuzuがデータベースへ接続するための設定ファイルです。<br>
            これが設定されていないとuwuzuはデータベースに接続できません。</p>

            <form class="formarea" enctype="multipart/form-data" method="post">
                <div>
                    <p>データベース名</p>
                    <div class="p2">空のデータベースを用意してください</div>
                    <input id="db_name" type="text" placeholder="uwuzu_db" class="inbox" name="db_name" value="<?php echo safetext(DB_NAME)?>">
                </div>
                <div>
                    <p>ユーザー名</p>
                    <div class="p2">データベースを操作できるユーザー名を入力してください。</div>
                    <input id="db_user" type="text" placeholder="root" class="inbox" name="db_user" value="<?php echo safetext(DB_USER)?>">
                </div>
                <div>
                    <p>パスワード</p>
                    <div class="p2">上のユーザーのパスワードを入力してください。</div>
                    <input id="db_pass" type="password" placeholder="********" class="inbox" name="db_pass" value="<?php echo safetext(DB_PASS)?>">
                </div>    
                <div class="switch_flexbox">
                    <div class="switch_button">
                        <input id="passview" class="switch_input" type='checkbox' name="passview" value=""/>
                        <label for="passview" class="switch_label"></label>
                    </div>
                    <p>パスワードを表示する</p>
                </div>

                <div>
                    <p>データベースのホスト名</p>
                    <div class="p2">データベースのホスト名を入力してください。<br>localhostであることが多いです。</div>
                    <input id="db_host" type="text" placeholder="localhost" class="inbox" name="db_host" value="<?php echo safetext(DB_HOST)?>">
                </div>

                <input type="submit" class = "irobutton" name="btn_submit" value="次へ">
            </form>

            <div class="btnbox">
                <a href="index.php" class="sirobutton">戻る</a>
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
$("#passview").click(function () {
    if ($("#passview").prop("checked") == true) {
        $('#db_pass').get(0).type = 'text';
    } else {
        $('#db_pass').get(0).type = 'password';
    }
});
</script>


</body>
</html>