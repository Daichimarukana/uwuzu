<?php

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}

require('db.php');

$servernamefile = "server/servername.txt";

$onlyuserfile = "server/onlyuser.txt";
$onlyuser = file_get_contents($onlyuserfile);

session_start();

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$authcode = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;


$userid = $_SESSION['userid'];


// データベースに接続
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

require_once 'authcode/GoogleAuthenticator.php';

if(empty($_SESSION['secretcode'])){
    $authcode = new PHPGangsta_GoogleAuthenticator();
    $secret = $authcode->createSecret();
    $_SESSION['secretcode'] = $secret;
}else{
    $authcode = new PHPGangsta_GoogleAuthenticator();
    $secret = $_SESSION['secretcode'];
}

if( !empty($_POST['btn_submit']) ) {
    $chkauthcode = new PHPGangsta_GoogleAuthenticator();
    //二段階認証の確認
    $userauthcode = $_POST['usercode'];

    $discrepancy = 2;

    $checkResult = $chkauthcode->verifyCode($secret, $userauthcode, $discrepancy);
    if ($checkResult) {
        if( empty($error_message) ) {
            $secret = $_SESSION['secretcode'];
            // トランザクション開始
            $pdo->beginTransaction();
        
            try {
        
                        // SQL作成
                $stmt = $pdo->prepare("UPDATE account SET authcode = :authcode WHERE userid = :userid");
        
                $stmt->bindValue(':authcode', $secret, PDO::PARAM_STR);
        
                // ユーザーIDのバインド（WHERE句に必要）
                $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
        
                // SQLクエリの実行
                $res = $stmt->execute();
        
                // コミット
                $res = $pdo->commit();
        
        
            } catch (Exception $e) {
        
                // エラーが発生した時はロールバック
                $pdo->rollBack();
            }
        
            if ($res) {
                if (isset($_SERVER['HTTP_COOKIE'])) {
                    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
                    foreach($cookies as $cookie) {
                        $parts = explode('=', $cookie);
                        $name = trim($parts[0]);
                        setcookie($name, '', time()-1000);
                        setcookie($name, '', time()-1000, '/');
                    }
                }
                $userid = "";
                // リダイレクト先のURLへ転送する
                $url = 'success.php';
                header('Location: ' . $url, true, 303);
                exit; 
            } else {
                $error_message[] = '更新に失敗しました。';
            }
        
            // プリペアドステートメントを削除
            $stmt = null;
        }
    } else {
        $error_message[] = "二段階認証が出来ませんでした。再度お試しください。";
    }
}



// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="css/style.css">
<link rel="apple-touch-icon" type="image/png" href="favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>アカウント登録 - <?php echo file_get_contents($servernamefile);?></title>
</head>


<script src="js/back.js"></script>
<body>


<div class="leftbox">
    <div class="logo">
        <img src="img/uwuzulogo.svg">
    </div>

    <div class="textbox">
        <h1>二段階認証</h1>

        <p>以下の二次元コードより二段階認証をセットアップしてください。</p>
        <p>セットアップが完了したら入力ボックスにコードを入力して「次へ」ボタンを押してください！<br>注意:まだ二段階認証の設定は終わっていません。次へを押すと設定が完了します。</p>

            <?php if( !empty($error_message) ): ?>
                <ul class="errmsg">
                    <?php foreach( $error_message as $value ): ?>
                        <p>・ <?php echo $value; ?></p>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        <?php 

        $title = file_get_contents($servernamefile);

        $name = $userid;

        $qrCodeUrl = $authcode->getQRCodeGoogleUrl($name, $secret, $title);
        ?>
        <div class="authzone">
            <img src="<?php echo $qrCodeUrl;?>">
        </div>
                
        <form class="formarea" enctype="multipart/form-data" method="post">
            <div>
                <p>二段階認証コード</p>
                <div class="p2">先程セットアップして出力された6桁のコードを入力してください。</div>
                <input id="profile" type="text" placeholder="123456" class="inbox" name="usercode" value="">
            </div>
                <input type="submit" class = "irobutton" name="btn_submit" value="次へ">
        </form>
        
    </div>
</div>


<script type="text/javascript">

function checkForm($this) {
    var str = $this.value;
    while (str.match(/[^A-Za-z\d]/)) {
        str = str.replace(/[^A-Za-z\d]/, "");
    }
    $this.value = str;
}


window.addEventListener('DOMContentLoaded', function(){

// ファイルが選択されたら実行
document.getElementById("file_upload").addEventListener('change', function(e){

  var file_reader = new FileReader();

  // ファイルの読み込みを行ったら実行
  file_reader.addEventListener('load', function(e) {
    console.log(e.target.result);
        const element = document.querySelector('#wrap');
        const createElement = '<p>画像を選択しました。</p>';
        element.insertAdjacentHTML('afterend', createElement);
  });

  file_reader.readAsText(e.target.files[0]);
});
});
</script>


</body>
</html>