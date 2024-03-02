<?php

$serversettings_file = "server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

require('db.php');


// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;
$error_message = array();

$row["userid"] = array();
$row["password"] = array();

$ruserid = array();
$rpassword = array();

session_name('uwuzu_s_id');
session_set_cookie_params(0, '', '', true, true);
session_start();
session_regenerate_id(true);

// データベースに接続
try {

    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

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

    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);


    $result = $dbh->prepare("SELECT userid, username, profile, role FROM account WHERE userid = :userid");

    $result->bindValue(':userid', $userid);
    // SQL実行
    $result->execute();


    $row = $result->fetch(); // ここでデータベースから取得した値を $row に代入する

    $username = $row["username"];

    $role = $row["role"];


    //--------------------------------------

	$userQuery = $dbh->prepare("SELECT username, userid, loginid, profile, role, iconname FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $userid);
	$userQuery->execute();
	$userData = $userQuery->fetch();
	
	$roles = explode(',', $userData["role"]); // カンマで区切られたロールを配列に分割
	
	$roleDataArray = array();
	
	foreach ($roles as $roleId) {
		$rerole = $dbh->prepare("SELECT rolename, roleauth, rolecolor FROM role WHERE roleidname = :role");
		$rerole->bindValue(':role', $roleId);
		$rerole->execute();
		$roleDataArray[$roleId] = $rerole->fetch();
	}



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

    $pdo->beginTransaction();
    try {
        $touserid = $userid;
        $datetime = date("Y-m-d H:i:s");
        $msg = "アカウントにログインがありました。\nもしログインした覚えがない場合は「その他」よりセッションを終了し、パスワードを変更してください。";
        $title = '🚪ログイン通知🚪';
        $url = '/settings';
        $userchk = 'none';
        // 通知用SQL作成
        $stmt = $pdo->prepare("INSERT INTO notification (fromuserid, touserid, msg, url, datetime, userchk, title) VALUES (:fromuserid, :touserid, :msg, :url, :datetime, :userchk, :title)");

        $stmt->bindParam(':fromuserid', htmlentities("uwuzu-fromsys"), PDO::PARAM_STR);
        $stmt->bindParam(':touserid', htmlentities($touserid), PDO::PARAM_STR);
        $stmt->bindParam(':msg', htmlentities($msg), PDO::PARAM_STR);
        $stmt->bindParam(':url', htmlentities($url), PDO::PARAM_STR);
        $stmt->bindParam(':userchk', htmlentities($userchk), PDO::PARAM_STR);
        $stmt->bindParam(':title', htmlentities($title), PDO::PARAM_STR);

        $stmt->bindParam(':datetime', htmlentities($datetime), PDO::PARAM_STR);

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
        'expires' => time() + 60 * 60 * 24 * 14,
        'path' => '/',
        'samesite' => 'lax',
        'secure' => true,
        'httponly' => true,
    ]);
    setcookie('loginid', $userData["loginid"],[
        'expires' => time() + 60 * 60 * 24 * 14,
        'path' => '/',
        'samesite' => 'lax',
        'secure' => true,
        'httponly' => true,
    ]);
    setcookie('username', $username,[
        'expires' => time() + 60 * 60 * 24 * 14,
        'path' => '/',
        'samesite' => 'lax',
        'secure' => true,
        'httponly' => true,
    ]);
    setcookie('admin_login', true,[
        'expires' => time() + 60 * 60 * 24 * 14,
        'path' => '/',
        'samesite' => 'lax',
        'secure' => true,
        'httponly' => true,
    ]);

    $_SESSION['admin_login'] = true;
    $_SESSION['userid'] = $userid;
    $_SESSION['loginid'] = $userData["loginid"];

    $_SESSION['username'] = $username;
    $_SESSION['password'] = null;

    // リダイレクト先のURLへ転送する
    $url = '/home';
    header('Location: ' . $url, true, 303);

    // すべての出力を終了
    exit;
}

if( !empty($_POST['btn_submit2']) ) {

    $_SESSION['admin_login'] = false;
    $_SESSION['userid'] = "";

    $_SESSION['username'] = "";

    // リダイレクト先のURLへ転送する
    $url = 'index.php';
    header('Location: ' . $url, true, 303);

    // すべての出力を終了
    exit;
}
                    


// プロフィールの絵文字対応
function replaceProfileEmojiImages($postText) {
    // プロフィール名で絵文字名（:emoji:）を検出して画像に置き換える
    $emojiPattern = '/:(\w+):/';
    $postTextWithImages = preg_replace_callback($emojiPattern, function($matches) {
        $emojiName = $matches[1];
        //絵文字path取得
        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));
        $emoji_Query = $dbh->prepare("SELECT emojifile, emojiname FROM emoji WHERE emojiname = :emojiname");
        $emoji_Query->bindValue(':emojiname', $emojiName);
        $emoji_Query->execute();
        $emoji_row = $emoji_Query->fetch();
        if(empty($emoji_row["emojifile"])){
            $emoji_path = "img/sysimage/errorimage/emoji_404.png";
        }else{
            $emoji_path = $emoji_row["emojifile"];
        }
        return "<img src='../".$emoji_path."' alt=':$emojiName:' title=':$emojiName:'>";
    }, $postText);
    return $postTextWithImages;
}

// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="css/style.css">
<script src="js/unsupported.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="favicon/icon-192x192.png">
<title>確認 - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>
</head>

<script src="js/back.js"></script>
<body>

<div class="leftbox">
    <?php if(!empty(htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8'))){ ?>
        <div class="logo">
            <a href="../index.php"><img src=<?php echo htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8');?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="../index.php"><img src="img/uwuzulogo.svg"></a>
        </div>
    <?php }?>

    <div class="textbox">
        <h1>確認</h1>

        <p>あなたは <?php if( !empty($row["username"]) ){ echo replaceProfileEmojiImages(htmlentities( $row["username"], ENT_QUOTES, 'UTF-8')); } ?> ですか？</p>

            <?php if( !empty($error_message) ): ?>
                <ul class="errmsg">
                    <?php foreach( $error_message as $value ): ?>
                        <p>・ <?php echo $value; ?></p>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="myarea">
                <img src="<?php echo htmlentities($userData['iconname']); ?>">
                <p>ユーザー名</p>
                <h2><?php if( !empty($row["username"]) ){ echo replaceProfileEmojiImages(htmlentities( $row["username"], ENT_QUOTES, 'UTF-8')); } ?></h2>
                <div class="roleboxes">
                    <?php foreach ($roles as $roleId): ?>
                        <?php $roleData = $roleDataArray[$roleId]; ?>
                        <div class="rolebox" style="border: 1px solid <?php echo '#' . $roleData["rolecolor"]; ?>;">
                            <p style="color: <?php echo '#' . $roleData["rolecolor"]; ?>;">
                                <?php if (!empty($roleData["rolename"])) { echo htmlentities($roleData["rolename"], ENT_QUOTES, 'UTF-8'); } ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr>
                <p>プロフィール</p>
                <h3><?php if( !empty($row["profile"]) ){ echo htmlentities( $row["profile"], ENT_QUOTES, 'UTF-8'); } ?></h3>
                
            </div>

            <form id ="form1" method="post" class="btnbox">
            <input type="submit" name="btn_submit" class="irobutton" value="はい">
            <input type="submit" name="btn_submit2" class="sirobutton" value="いいえ">
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