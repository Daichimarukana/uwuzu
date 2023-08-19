<?php

$servernamefile = "server/servername.txt";

require('db.php');


// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;

$row["loginid"] = "";
$row["authcode"] = "";

$ruserid = "";
$rpassword = "";

$userid = "";
$_SESSION["userid"]="";

$password = null;
$_SESSION["password"]="";


session_start();

$userid = $_SESSION['userid'];
try {
    $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $options);
} catch(PDOException $e) {
    // 接続エラーのときエラー内容を取得する
    $error_message[] = 'データベース接続エラー: ' . $e->getMessage();
}


if( !empty($_POST['btn_submit']) ) {

    $options = array(
        // SQL実行失敗時に例外をスルー
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // デフォルトフェッチモードを連想配列形式に設定
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // バッファードクエリを使う（一度に結果セットを全て取得し、サーバー負荷を軽減）
        // SELECTで得た結果に対してもrowCountメソッドを使えるようにする
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    );

    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $options);

    require_once 'authcode/GoogleAuthenticator.php';

    $result = $dbh->prepare("SELECT authcode,loginid,username FROM account WHERE userid = :userid");

    $result->bindValue(':userid', $userid);
    // SQL実行
    $result->execute();
    if($result->rowCount() > 0) {
        $row = $result->fetch();

        $tousercode = $row["authcode"];

        $chkauthcode = new PHPGangsta_GoogleAuthenticator();

        $userauthcode = $_POST['usercode'];

        if(empty($userauthcode)){
            $error_message[] = "コードを入力してください。";
        }else{

            $discrepancy = 2;

            $checkResult = $chkauthcode->verifyCode($tousercode, $userauthcode, $discrepancy);
            if ($checkResult) {
                $_SESSION['admin_login'] = true;
                $_SESSION['userid'] = $userid;
                $_SESSION['loginid'] = $row["loginid"];
            
                $_SESSION['username'] = $row["username"];
                $_SESSION['password'] = "";
            
                // リダイレクト先のURLへ転送する
                $url = '/home';
                header('Location: ' . $url, true, 303);
            
                // すべての出力を終了
                exit;
                    
            }else {
                $error_message[] = '二段階認証が出来ませんでした。再度お試しください。';
            }
        }
    }else{
        $error_message[] = 'データの取得が出来ませんでした。再度お試しください。';
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
<title>ログイン - <?php echo file_get_contents($servernamefile);?></title>
</head>

<script src="js/back.js"></script>
<body>

<div class="leftbox">
    <div class="logo">
        <img src="img/uwuzulogo.svg">
    </div>

    <div class="textbox">
        <h1>二段階認証</h1>

        <p>二段階認証コードを入力してください。</p>

            <?php if( !empty($error_message) ): ?>
                <ul class="errmsg">
                    <?php foreach( $error_message as $value ): ?>
                        <p>・ <?php echo $value; ?></p>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form class="formarea" enctype="multipart/form-data" method="post">
                <div>
                    <p>二段階認証コード</p>
                    <div class="p2">6桁のコードを入力してください。</div>
                    <input id="profile" type="text" placeholder="123456" class="inbox" name="usercode" value="">
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

function checkForm($this)
{
    var str=$this.value;
    while(str.match(/[^A-Z^a-z\d\-]/))
    {
        str=str.replace(/[^A-Z^a-z\d\-]/,"");
    }
    $this.value=str;
}


window.onload = function(){
var ele = document.getElementsByTagName("body")[0];
var n = Math.floor(Math.random() * 3); // 3枚の画像がある場合
ele.style.backgroundImage = "url(img/titleimg/"+n+".png)";
}

</script>


</body>
</html>