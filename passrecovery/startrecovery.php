<?php

$servernamefile = "../server/servername.txt";

require('../db.php');


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

session_name('uwuzu_s_id');
session_start();
session_regenerate_id(true);

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

    require_once '../authcode/GoogleAuthenticator.php';

    $result = $dbh->prepare("SELECT authcode,loginid,username FROM account WHERE userid = :userid");

    $result->bindValue(':userid', $userid);
    // SQL実行
    $result->execute();
    if($result->rowCount() > 0) {
        $row = $result->fetch();

        $tousercode = $row["authcode"];

        $chkauthcode = new PHPGangsta_GoogleAuthenticator();

        $userauthcode = $_POST['usercode'];
        $password = $_POST['password'];

        if(empty($userauthcode)){
            $error_message[] = "コードを入力してください。";
        }else{

            $discrepancy = 2;

            $checkResult = $chkauthcode->verifyCode($tousercode, $userauthcode, $discrepancy);
            if ($checkResult) {
                
                // パスワードの入力チェック
                if( empty($password) ) {
                    $error_message[] = 'パスワードを入力してください。';
                } else {

                    $weakPasswords = array(
                        "password",
                        "123456",
                        "123456789",
                        "12345",
                        "12345678",
                        "123123",
                        "1234567890",
                        "1234567",
                        "1q2w3e",
                        "qwerty123",
                        "aa12345678",
                        "password1",
                        "1234",
                        "qwertyuiop",
                        "123321",
                        "12321",
                        "qwertyui",
                        "abcd1234",
                        "zaq12wsx",
                        "1q2w3e4r",
                        "qwer1234",
                        "sakura",
                        "asdf1234",
                        "asdfghjkl",
                        "asdfghjk",
                        "member",
                        "1qaz2wsx",
                        "doraemon",
                        "makoto",
                        "takeshi",
                        "machi1",
                        "machida",
                        "machida1",
                        "tokyo",
                        "arashi",
                        "dropbox",
                        "twitter",
                        "elonmusk",
                        "xcorp",
                        "1234qwer",
                        "japan",
                        "nippon",
                        "tukareta",
                        "tweet",
                        "discord",
                        "misskey",
                        "qwerty",
                        "123456789",
                        "abc123",
                        "password123",
                        "admin",
                        "letmein",
                        "iloveyou",
                        "111111",
                        "12345678910",
                        "user",
                        "root",
                        "system",
                        // 他にも弱いパスワードを追加できます
                    );
                    
                    function isWeakPassword($passwords) {
                        global $weakPasswords;
                        return in_array($passwords, $weakPasswords);
                    }

                    // テスト用のパスワード（実際にはユーザー入力などから取得することになります。

                    if (isWeakPassword($password)) {
                        $error_message[] = "パスワードが弱いです。セキュリティ上変更してください。";
                    } else {
                        
                    }
                    
                    if( 4 > mb_strlen($password, 'UTF-8') ) {
                        $error_message[] = 'パスワードは4文字以上である必要があります。';
                    }

                    // 文字数を確認
                    if( 100 < mb_strlen($password, 'UTF-8') ) {
                        $error_message[] = 'パスワードは100文字以内で入力してください。';
                    }
                }

                if( empty($error_message) ) {
                    // トランザクション開始
                $pdo->beginTransaction();
                $hashpassword = password_hash($password, PASSWORD_DEFAULT);
                
                    try {
                        // SQL作成
                        $stmt = $pdo->prepare("UPDATE account SET password = :password WHERE userid = :userid;");
                
                        // 他の値をセット
                        $stmt->bindParam(':password', $hashpassword, PDO::PARAM_STR);
                
                        // 条件を指定
                        // 以下の部分を適切な条件に置き換えてください
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
                        $_SESSION['userid'] = "";
                        $url = 'donerecovery.php';
                        header('Location: ' . $url, true, 303);

                        // すべての出力を終了
                        exit;
                    } else {
                        $error_message[] = 'パスワードの更新に失敗しました。';
                    }
                }
                        
            }else {
                $error_message[] = '二段階認証が出来ませんでした。再度お試しください。';
            }
            
            // プリペアドステートメントを削除
            $stmt = null;
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
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ログイン - <?php echo file_get_contents($servernamefile);?></title>
</head>

<script src="back.js"></script>
<body>

<div class="leftbox">
    <div class="logo">
        <img src="../img/uwuzulogo.svg">
    </div>

    <div class="textbox">
        <h1>二段階認証</h1>

        <p>二段階認証コードと新しいパスワードを入力してください。</p>

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
                    <input id="profile" type="number" placeholder="123456" class="inbox" name="usercode" value="">
                </div>
                <div>
                    <p>新しいパスワード</p>
                    <div class="p2">新しいパスワードを入力してください。</div>
                    <input id="profile" type="text" placeholder="" class="inbox" name="password" value="">
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

</script>


</body>
</html>