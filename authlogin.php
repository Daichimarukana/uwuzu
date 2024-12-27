<?php

$serversettings_file = "server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

require('db.php');
//関数呼び出し
//- ユーザーエージェントからdevice名とるやつ
require('function/function.php');

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
session_regenerate_id(true);

$userid = $_SESSION['userid'];

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

if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true && isset($_COOKIE['loginid']) && isset($_SESSION['userid'])) {
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
    $acck = $dbh->prepare("SELECT userid, loginid FROM account WHERE userid = :userid");
    $acck->bindValue(':userid', $_SESSION['userid']);
    $acck->execute();
    $acck_data = $acck->fetch();
    if(!empty($acck_data)){
        if($_COOKIE['loginid'] === $acck_data["loginid"] && $_SESSION['userid'] === $acck_data["userid"] ){
            header("Location: home/index.php");
            exit;
        }
    }
} elseif (isset($_COOKIE['admin_login']) && $_COOKIE['admin_login'] == true && isset($_COOKIE['loginid']) && isset($_COOKIE['userid'])) {
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
    $acck = $dbh->prepare("SELECT userid, loginid FROM account WHERE userid = :userid");
    $acck->bindValue(':userid', $_COOKIE['userid']);
    $acck->execute();
    $acck_data = $acck->fetch();
    if(!empty($acck_data)){
        if($_COOKIE['loginid'] === $acck_data["loginid"] && $_COOKIE['userid'] === $acck_data["userid"] ){
            header("Location: home/index.php");
            exit;
        }
    }
}

if( !empty($_POST['btn_submit']) ) {
    $useragent = safetext($_SERVER['HTTP_USER_AGENT']);
    $device = UserAgent_to_Device($useragent);

    $userbackupcode = $_POST['userbackupcode'];

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

    $result = $dbh->prepare("SELECT * FROM account WHERE userid = :userid");

    $result->bindValue(':userid', $userid);
    // SQL実行
    $result->execute();

    if(!(empty($userbackupcode))){
        $row = $result->fetch();
        $backuplogin = false;
        if(mb_strlen($row["backupcode"]) === 32 && mb_strlen($userbackupcode) === 32){
            if($row["backupcode"] === $userbackupcode){
                $backuplogin = true;
            }else{
                $backuplogin = false;
            }
        }else{
            $backuplogin = false;
        }
        
        if($backuplogin === true || uwuzu_password_verify($userbackupcode,$row["backupcode"])){
            $pdo->beginTransaction();
            
            try {
                $touserid = $userid;
                $datetime = date("Y-m-d H:i:s");
                $msg = "バックアップコードを使用しログインされました！\nバックアップコード変更のために二段階認証を再設定することを強くおすすめします。\nまた、もしバックアップコードを利用してログインした覚えがない場合は「その他」よりセッショントークンを再生成し、設定画面よりパスワードを変更し、二段階認証を再設定してください！\n\nログインした端末 : ".$device;
                $title = '🔴バックアップコード使用のお知らせ🔴';
                $url = '/settings';
                $userchk = 'none';
                // 通知用SQL作成
                $stmt = $pdo->prepare("INSERT INTO notification (fromuserid, touserid, msg, url, datetime, userchk, title) VALUES (:fromuserid, :touserid, :msg, :url, :datetime, :userchk, :title)");
        
                $stmt->bindParam(':fromuserid', safetext("uwuzu-fromsys"), PDO::PARAM_STR);
                $stmt->bindParam(':touserid', safetext($touserid), PDO::PARAM_STR);
                $stmt->bindParam(':msg', safetext($msg), PDO::PARAM_STR);
                $stmt->bindParam(':url', safetext($url), PDO::PARAM_STR);
                $stmt->bindParam(':userchk', safetext($userchk), PDO::PARAM_STR);
                $stmt->bindParam(':title', safetext($title), PDO::PARAM_STR);

                $stmt->bindParam(':datetime', safetext($datetime), PDO::PARAM_STR);

                // SQLクエリの実行
                $res = $stmt->execute();

                // コミット
                $res = $pdo->commit();

            } catch(Exception $e) {

                // エラーが発生した時はロールバック
                $pdo->rollBack();
        	}

            clearstatcache();
                                        
            if (isset($_SERVER['HTTP_COOKIE'])) {
                $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
                foreach($cookies as $cookie) {
                    $parts = explode('=', $cookie);
                    $name = trim($parts[0]);
                    setcookie($name, '', time()-1000);
                }
            }

            setcookie('userid', $userid,[
                'expires' => time() + 60 * 60 * 24 * 28,
                'path' => '/',
                'samesite' => 'lax',
                'secure' => true,
                'httponly' => true,
            ]);
            setcookie('loginid', $row["loginid"],[
                'expires' => time() + 60 * 60 * 24 * 28,
                'path' => '/',
                'samesite' => 'lax',
                'secure' => true,
                'httponly' => true,
            ]);
            setcookie('username', $row["username"],[
                'expires' => time() + 60 * 60 * 24 * 28,
                'path' => '/',
                'samesite' => 'lax',
                'secure' => true,
                'httponly' => true,
            ]);
            setcookie('admin_login', true,[
                'expires' => time() + 60 * 60 * 24 * 28,
                'path' => '/',
                'samesite' => 'lax',
                'secure' => true,
                'httponly' => true,
            ]);

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
        }else{
            $error_message[] = "そのバックアップコードは使用できません。(BACKUPCODE_DAME)";
        }
    }else{

        if($result->rowCount() > 0) {
            $row = $result->fetch();

            if(!(empty($row["encryption_ivkey"])) && (!(mb_strlen($row["authcode"]) === 16))){
                $tousercode = DecryptionUseEncrKey($row["authcode"], GenUserEnckey($row["datetime"]), $row["encryption_ivkey"]);
            }else{
                $tousercode = $row["authcode"];
            }

            $chkauthcode = new PHPGangsta_GoogleAuthenticator();

            $userauthcode = $_POST['usercode'];

            if(empty($userauthcode)){
                $error_message[] = "コードを入力してください。(AUTHCODE_INPUT_PLEASE)";
            }else{

                $discrepancy = 2;

                $checkResult = $chkauthcode->verifyCode($tousercode, $userauthcode, $discrepancy);
                if ($checkResult) {

                    $pdo->beginTransaction();
                    try {
                        $touserid = $userid;
                        $datetime = date("Y-m-d H:i:s");
                        $msg = "アカウントにログインがありました。\nもしログインした覚えがない場合は「その他」よりセッショントークンを再生成し、パスワードを変更し、二段階認証を再設定してください。\n\nログインした端末 : ".$device;
                        $title = '🚪ログイン通知🚪';
                        $url = '/settings';
                        $userchk = 'none';
                        // 通知用SQL作成
                        $stmt = $pdo->prepare("INSERT INTO notification (fromuserid, touserid, msg, url, datetime, userchk, title) VALUES (:fromuserid, :touserid, :msg, :url, :datetime, :userchk, :title)");
                
                        $stmt->bindParam(':fromuserid', safetext("uwuzu-fromsys"), PDO::PARAM_STR);
                        $stmt->bindParam(':touserid', safetext($touserid), PDO::PARAM_STR);
                        $stmt->bindParam(':msg', safetext($msg), PDO::PARAM_STR);
                        $stmt->bindParam(':url', safetext($url), PDO::PARAM_STR);
                        $stmt->bindParam(':userchk', safetext($userchk), PDO::PARAM_STR);
                        $stmt->bindParam(':title', safetext($title), PDO::PARAM_STR);

                        $stmt->bindParam(':datetime', safetext($datetime), PDO::PARAM_STR);
                
                        // SQLクエリの実行
                        $res = $stmt->execute();
                
                        // コミット
                        $res = $pdo->commit();
                
                    } catch(Exception $e) {
                
                        // エラーが発生した時はロールバック
                        $pdo->rollBack();
                    }

                    clearstatcache();
                                        
                    if (isset($_SERVER['HTTP_COOKIE'])) {
                        $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
                        foreach($cookies as $cookie) {
                            $parts = explode('=', $cookie);
                            $name = trim($parts[0]);
                            setcookie($name, '', time()-1000);
                        }
                    }

                    setcookie('userid', $userid,[
                        'expires' => time() + 60 * 60 * 24 * 28,
                        'path' => '/',
                        'samesite' => 'lax',
                        'secure' => true,
                        'httponly' => true,
                    ]);
                    setcookie('loginid', $row["loginid"],[
                        'expires' => time() + 60 * 60 * 24 * 28,
                        'path' => '/',
                        'samesite' => 'lax',
                        'secure' => true,
                        'httponly' => true,
                    ]);
                    setcookie('username', $row["username"],[
                        'expires' => time() + 60 * 60 * 24 * 28,
                        'path' => '/',
                        'samesite' => 'lax',
                        'secure' => true,
                        'httponly' => true,
                    ]);
                    setcookie('admin_login', true,[
                        'expires' => time() + 60 * 60 * 24 * 28,
                        'path' => '/',
                        'samesite' => 'lax',
                        'secure' => true,
                        'httponly' => true,
                    ]);

                    $_SESSION['admin_login'] = true;

                    $_SESSION['userid'] = $userid;
                    $_SESSION['loginid'] = $row["loginid"];
                
                    $_SESSION['username'] = $row["username"];
                    $_SESSION['password'] = null;
                
                    // リダイレクト先のURLへ転送する
                    $url = '/home';
                    header('Location: ' . $url, true, 303);
                
                    // すべての出力を終了
                    exit;
                        
                }else {
                    $error_message[] = '二段階認証が出来ませんでした。再度お試しください。(AUTHCODE_CHECK_DAME)';
                }
            }
        }else{
            $error_message[] = 'データの取得が出来ませんでした。再度お試しください。(AUTHCODE_GET_ACCOUNT_NOT_FOUND)';
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
<link rel="stylesheet" href="css/style.css">
<script src="js/jquery-min.js"></script>
<script src="js/unsupported.js"></script>
<link rel="apple-touch-icon" type="image/png" href="favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ログイン - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>
</head>

<script src="js/back.js"></script>
<body>

<div class="leftbox">
    <?php if(!empty(safetext($serversettings["serverinfo"]["server_logo_login"]))){ ?>
        <div class="logo">
            <a href="index.php"><img src=<?php echo safetext($serversettings["serverinfo"]["server_logo_login"]);?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="index.php"><img src="img/uwuzulogo.svg"></a>
        </div>
    <?php }?>

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
                    <input id="profile" type="number" placeholder="123456" class="inbox" name="usercode" value="">
                </div>
                <div>
                    <p>バックアップコード</p>
                    <div class="p2">もし二段階認証が出来ない場合は32桁英数字のバックアップコードを入力してください。</div>
                    <input id="profile" type="text" placeholder="通常は入力しなくて大丈夫です。" class="inbox" name="userbackupcode" value="">
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
</script>


</body>
</html>