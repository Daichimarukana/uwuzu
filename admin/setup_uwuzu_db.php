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
    
    try{
        $table_query = $dbh->prepare('SELECT 1 FROM role LIMIT 1;');
        $table_query->execute();
        $table_result = $table_query->fetch();
        if($table_result > 0){
            $query = $dbh->prepare('SELECT * FROM account WHERE admin = :adminuser limit 1');
    
            $query->execute(array(':adminuser' => $aduser));
            
            $result2 = $query->fetch();
            
            if($result2 > 0){
                header("Location: ../login.php");
                exit;
            }
            header("Location: addadmin.php");
            exit;
        }
    } catch(PDOException $e) {
        
    }
    
    $db_php = true;
}else{
    $db_php = false;
}

if(!(empty($_POST['btn_submit']))){
    $sqlfile = "../uwuzu_database.sql";
    $sqldata = file_get_contents($sqlfile);
	if ($sqldata === false) {
        $error_message[] = "SQLファイルの読み込みに失敗しました。";
        exit();
    }
    if(empty($error_message)){
        try {
            $option = array(
                PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION
            );
            $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
            
            $pdo->exec($sqldata);
            $db_setup = true;

            $role_sql = "insert into role (rolename, roleauth, rolecolor, roleidname) VALUES ('凍結済み', 'ice', 'CCCCCC', 'ice');
                        insert into role (rolename, roleauth, rolecolor, roleidname) VALUES ('公式', 'official', 'CCCCCC', 'official');
                        insert into role (rolename, roleauth, rolecolor, roleidname) VALUES ('ゆーざー', 'user', 'CCCCCC', 'user');";
            $pdo->exec($role_sql);
            $role_setup = true;

        } catch (PDOException $e) {
            $error_message[] = 'SQL実行エラー: ' . $e->getMessage();
        }
        if(empty($error_message)){
            header("Location: addadmin.php");
            exit;  
        }
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
<title>データベースのセットアップ - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>
</head>


<script src="../js/back.js"></script>
<body>


<div class="leftbox">
    <div class="logo">
        <img src="../img/uwuzulogo.svg">
    </div>

    <div class="textbox">
        <h1>データベースのセットアップ</h1>
            <?php if( !empty($error_message) ): ?>
                <ul class="errmsg">
                    <?php foreach( $error_message as $value ): ?>
                        <p>・ <?php echo $value; ?></p>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <script src="back.js"></script>

        <p>データベースのセットアップを行います。<br>
            データベース内にテーブルというデータを保存する場所と必須ロールを作成します！<br>
            作成にあたり、uwuzuにデフォルトで同梱されているuwuzu_database.sqlというファイルを使用します。<br>
            このファイルに悪質な命令などが含まれているとあなたのサーバーが死んでしまうおそれがあります！<br>
            <br>
            uwuzuをuwuzuの公式ダウンロードページ(Github)からダウンロードしているならおそらく安全かと思われますが、<br>
            uwuzu_database.sqlが安全なことを確認したうえで以下のデータベースのセットアップを実行してください。<br>
            <br>
            また、このセットアップには時間がかかる事があります！<br>
            処理が終わるまで再起動などはせずに、そのままお待ち下さい！<br>
            <br>
            データベースのセットアップが完了すると、管理者アカウントの登録へ進みます。</p>

            <form class="formarea" enctype="multipart/form-data" method="post">
                <input type="submit" class = "irobutton" name="btn_submit" value="セットアップ実行">
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