<?php

function random_token($length = 64)
{
    return substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}
$domain = $_SERVER['HTTP_HOST'];
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

function random($length){
    return substr(str_shuffle('1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 0, $length);
}


require('../db.php');
require("../function/function.php");


// 変数の初期化
$datetime = array();
$user_name = null;
$message = array();
$message_data = null;
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;

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

//ログイン認証---------------------------------------------------
blockedIP($_SERVER['REMOTE_ADDR']);
$is_login = uwuzuUserLogin($_SESSION, $_COOKIE, $_SERVER['REMOTE_ADDR'], "user");
if($is_login === false){
	header("Location: ../index.php");
	exit;
}else{
	$userid = safetext($is_login['userid']);
	$username = safetext($is_login['username']);
	$loginid = safetext($is_login["loginid"]);
	$role = safetext($is_login["role"]);
	$sacinfo = safetext($is_login["sacinfo"]);
	$myblocklist = safetext($is_login["blocklist"]);
	$is_Admin = safetext($is_login["admin"]);
}
$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

if( !empty($pdo) ) {
	
	// データベース接続の設定
	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	));

	$userQuery = $dbh->prepare("SELECT userid,role,datetime FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $userid);
	$userQuery->execute();
	$userData = $userQuery->fetch();
	
}
$is_trueclient = false;
if(!(empty($_GET["session"])) && !(empty($_GET["client"])) && !(empty($_GET["scope"]))){
    $is_trueclient = true;
    $session_code = safetext($_GET["session"]);

    if($is_Admin == "yes"){
        $admin_permission = true;
    }else{
        $admin_permission = false;
    }
    
    if(strlen($session_code) > 512){
        $is_trueclient = false;
    }
    $client_name = safetext($_GET["client"]);
    if(isset($_GET["icon"])){
        $client_icon = safetext(urldecode($_GET["icon"]));
    }else{
        $client_icon = "../img/sysimage/errorimage/emoji_404.png";
    }
    if(isset($_GET["scope"])){
        $client_scope_base = safetext(urldecode($_GET["scope"]));
        $client_scope_base = array_unique(array_map('trim', explode(",", $client_scope_base)));

        $client_scope = [];
        $securityScopes = ["write:me", "write:ueuse", "write:follow", "write:favorite", "write:notifications", "write:bookmark", "read:bookmark"];
        $securityScopesView = false;

        foreach ($client_scope_base as $scope) {
            if (GetAPIScopes($scope, $admin_permission)) {
                $client_scope[] = GetAPIScopes($scope, $admin_permission);
                if($securityScopesView === false && in_array($scope, $securityScopes)){
                    $securityScopesView = true;
                }
            } else {
                $client_scope[] = "未知のスコープ ($scope)";
            }
        }
    }else{
        $client_scope[] = "権限なし";
    }
    if(isset($_GET["about"])){
        $client_about = safetext(urldecode($_GET["about"]));
    }else{
        $client_about = "クライアントによる説明はありません。";
    }
    if(isset($_GET["callback"])){
        $client_callback = urldecode($_GET["callback"]);
    }else{
        $client_callback = null;
    }
}else{
    $is_trueclient = false;
}

if(!(isset($is_done))){
    $is_done = false;
}

if($is_trueclient === true){
    if( !empty($_POST['allow_submit']) ) {
        $tokenQuery = $pdo->prepare("SELECT userid, token FROM api WHERE sessionid = :sessionid");
        $tokenQuery->bindValue(':sessionid', $session_code);
        $tokenQuery->execute();
        $tokenData = $tokenQuery->fetch();

        if(!(empty($tokenData["userid"]))){
            $error_message[] = "不正なリクエストです。";
        }

        foreach ($client_scope_base as $scope) {
            if (GetAPIScopes($scope, $admin_permission)) {
                $client_scope_done[] = $scope;
            }else{
                $client_scope_done = array();
            }
        }
        $client_scope_done = implode(",", $client_scope_done);
        if(empty($client_scope_done)){
            $error_message[] = "不正な権限要求です。";
        }

        if($role == "ice"){
            $error_message[] = "アカウントが凍結されているためAPIトークンの発行は行えません。";
        }

        if(empty($error_message)){
            $pdo->beginTransaction();
            try {
                $uniqid = createUniqId();
                $token = GenAPIToken();
                $datetime = date("Y-m-d H:i:s");

                $stmt = $pdo->prepare("INSERT INTO api (uniqid, userid, token, scope, datetime, clientname, sessionid) VALUES (:uniqid, :userid, :token, :scope, :datetime, :clientname, :sessionid)");
                
                $stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
                $stmt->bindParam(':userid', $userid, PDO::PARAM_STR);
                $stmt->bindParam(':token', $token, PDO::PARAM_STR);
                $stmt->bindParam(':scope', $client_scope_done, PDO::PARAM_STR);
                $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);
                $stmt->bindParam(':clientname', $client_name, PDO::PARAM_STR);
                $stmt->bindParam(':sessionid', $session_code, PDO::PARAM_STR);

                $res = $stmt->execute();

                $res = $pdo->commit();

            } catch(Exception $e) {
                $pdo->rollBack();
            }
            if($res) {
                if($admin_permission === true){
                    actionLog($userid, "info", "api/auth", $client_name, "管理者のアカウントでAPIトークンが発行されました。\n".$client_scope_done, 4);
                }
                
                if(!(empty($client_callback))){
                    header("Location: ".$client_callback."");
                    exit; 
                }else{
                    $is_done = true;
                }
            }else{
                $is_done = false;
                actionLog($userid, "error", "api/auth", $client_name, $e->getMessage(), 3);
                $error_message[] = "APIトークンの生成に失敗しました...(REGISTED_DAME)";
            }
        }
    }
}


require('../logout/logout.php');


?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../../css/home.css">
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="../js/jquery-min.js"></script>
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>アクセス許可確認 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

</head>

<body>
	<?php require('../require/leftbox.php');?>
	<main>

	<?php if( !empty($error_message) ): ?>
		<ul class="errmsg">
			<?php foreach( $error_message as $value ): ?>
				<p>・ <?php echo $value; ?></p>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
        <form class="formarea" enctype="multipart/form-data" method="post">
            <h1>アクセス許可確認</h1>
            <?php if($is_trueclient === true && $is_done === false && !($role == "ice")){?>
                <p>以下のサービスにあなたのアカウントを使わせてもいいですか...？</p>

                <div class="auth_clientbox">
                    <div class="flexbox">
                        <img src="<?php echo $client_icon;?>">
                        <p><?php echo $client_name;?></p>
                    </div>
                    <div class="about">
                        <div class="p2">説明</div>
                        <p><?php echo nl2br($client_about);?></p>
                        <div class="p2">要求している権限</div>
                        <div class="scopebox">
                            <?php 
                            foreach ($client_scope as $value) {
                                echo "<p>- " . safetext($value) . "</p>";
                            }
                            ?>
                        </div>
                    </div>
                    <div class="accountbox">
                        <div class="p2">ログイン中のアカウント</div>
                        <div class="flexbox">
                            <img src="<?php echo localcloudURL($is_login["iconname"]);?>">
                            <p><?php echo $username."(@".$userid.")";?></p>
                        </div>
                    </div>
                    <?php if(!(empty($client_callback))){?>
                        <div class="callbackbox">
                            <div class="p2">許可すると以下のURLにリダイレクトされます</div>
                            <p><?php echo safetext($client_callback);?></p>
                        </div>
                    <?php }?>
                </div>

                <?php if($securityScopesView === true){?>
                    <div class="errmsg justfit">
                        <p>このサービスは<b>あなたに代わってアカウントの操作を行うことや、一部の情報を閲覧することができます</b>。アクセスを許可するかよく考えてください。</p>
                    </div>
                <?php }?>

                <div class="btnbox flexbox">
                    <a href="javascript:history.back();" class="sirobutton">戻る</a>
                    <input type="submit" class = "irobutton" name="allow_submit" value="許可">
                </div>
			<?php }elseif($is_trueclient === false){?>
                <p>不正なクライアントによるアクセスです。</p>
                <div class="btnbox">
                    <a href="javascript:history.back();" class="sirobutton">戻る</a>
                </div>
            <?php }elseif($is_done === true){?>
                <p>許可が完了しました！<br>
                    このページを閉じてもとのサービスに戻って大丈夫です。</p>
            <?php }elseif($role == "ice"){?>
                <p>アカウントが凍結されているため、アクセスの許可は行えません。</p>
            <?php }else{?>
                <p>不明なエラーです。はじめからやり直してください。</p>
            <?php }?>
        </form>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>
</body>
</html>

<script>
$(document).ready(function() {
    $(function(){
        $("input"). keydown(function(e) {
            if ((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
                return false;
            } else {
                return true;
            }
        });
    });
});
</script>