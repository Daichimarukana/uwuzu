<?php

require('db.php');

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;

$row["userid"] = array();
$row["password"] = array();

$ruserid = array();
$rpassword = array();

$userid = null;
$_SESSION["userid"]="";

$password = null;
$_SESSION["password"]="";


session_start();


$option = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
);
$pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);


//$row['userid'] = "daichimarukn";

$userid = $_SESSION['userid'];


$options = array(
    // SQL実行失敗時に例外をスルー
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    // デフォルトフェッチモードを連想配列形式に設定
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // バッファードクエリを使う（一度に結果セットを全て取得し、サーバー負荷を軽減）
    // SELECTで得た結果に対してもrowCountメソッドを使えるようにする
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
);

if( empty($userid) ) {
    $filePath = 'img/deficon/icon.png';
    $data = file_get_contents($filePath);
    header('Content-type: image/png');
    //データを出力
    echo $data;
    exit();
}else{
    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);


    $result = $dbh->prepare("SELECT iconname, iconcontent, icontype, iconsize profile FROM account WHERE userid = :userid");

    $result->bindValue(':userid', $userid);
    // SQL実行
    $result->execute();


    $row = $result->fetch(); // ここでデータベースから取得した値を $row に代入する



    header('Content-type: ' . $row['icontype']);
    echo $row['iconcontent'];
    exit();
}