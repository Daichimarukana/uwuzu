<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}

require('../db.php');

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
session_set_cookie_params(0, '', '', true, true);
session_start();
session_regenerate_id(true);

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

if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] == true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', htmlentities($_SESSION['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_SESSION['loginid'] === $res["loginid"] && $_SESSION['userid'] == $res["userid"]){
	// セッションに値をセット
	$userid = htmlentities($res['userid']); // セッションに格納されている値をそのままセット
	$username = htmlentities($res['username']); // セッションに格納されている値をそのままセット
	$loginid = htmlentities($res["loginid"]);
	$role = htmlentities($res["role"]);
	$sacinfo = htmlentities($res["sacinfo"]);
	$myblocklist = htmlentities($res["blocklist"]);
	$myfollowlist = htmlentities($res["follow"]);
	$_SESSION['admin_login'] = true;
	$_SESSION['userid'] = $userid;
	$_SESSION['username'] = $username;
	$_SESSION['loginid'] = $res["loginid"];
	setcookie('userid', $userid, [
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
	setcookie('loginid', $res["loginid"],[
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
	}else{
		header("Location: ../login.php");
		exit;
	}

		
} elseif (isset($_COOKIE['admin_login']) && $_COOKIE['admin_login'] == true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', htmlentities($_COOKIE['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_COOKIE['loginid'] === $res["loginid"] && $_COOKIE['userid'] == $res["userid"]){
	// セッションに値をセット
	$userid = htmlentities($res['userid']); // クッキーから取得した値をセット
	$username = htmlentities($res['username']); // クッキーから取得した値をセット
	$loginid = htmlentities($res["loginid"]);
	$role = htmlentities($res["role"]);
	$sacinfo = htmlentities($res["sacinfo"]);
	$myblocklist = htmlentities($res["blocklist"]);
	$myfollowlist = htmlentities($res["follow"]);
	$_SESSION['admin_login'] = true;
	$_SESSION['userid'] = $userid;
	$_SESSION['username'] = $username;
	$_SESSION['loginid'] = $res["loginid"];
	setcookie('userid', $userid,[
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
	setcookie('loginid', $res["loginid"],[
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
	}else{
		header("Location: ../login.php");
		exit;
	}


} else {
	// ログインが許可されていない場合、ログインページにリダイレクト
	header("Location: ../login.php");
	exit;
}
if(empty($userid)){
	header("Location: ../login.php");
	exit;
} 
if(empty($username)){
	header("Location: ../login.php");
	exit;
}

if(!($res["admin"] === "yes")){
	header("Location: ../login.php");
	exit;
}
$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];


if( !empty($_POST['btn_submit']) ) {
	//$level = $_POST['notice_level'];
	$title = htmlentities($_POST['title'], ENT_QUOTES, 'UTF-8', false);
    $note = htmlentities($_POST['note'], ENT_QUOTES, 'UTF-8', false);

    // IDの入力チェック
	if( empty($title) ) {
		$error_message[] = 'タイトルを入力してください！(INPUT_PLEASE)';
	} else {

        // 文字数を確認
        if( 1024 < mb_strlen($title, 'UTF-8') ) {
			$error_message[] = 'タイトルは1024文字以内で入力してください。(INPUT_OVER_MAX_COUNT)';
		}

    }

	/*if( empty($level) ) {
		$error_message[] = '緊急度レベルを指定してください！(INPUT_PLEASE)';
	} else {
		if($level == 'normal'){
			$notice_level = 'normal';
		}elseif($level == 'warning'){
			$notice_level = 'warning';
		}elseif($level == 'danger'){
			$notice_level = 'danger';
		}else{
			$error_message[] = '緊急度レベルが正しく指定されていません！(ERROR)';
		}
    }*/

	if( empty($error_message) ) {

		$uniqid = createUniqId();
		
		// 書き込み日時を取得
        $datetime = date("Y-m-d H:i:s");

        // トランザクション開始
        $pdo->beginTransaction();

        try {

            // SQL作成
            $stmt = $pdo->prepare("INSERT INTO notice (uniqid,title,note,account,datetime) VALUES (:uniqid,:title,:note,:account,:datetime)");


            // 値をセット
			//$stmt->bindParam( ':level', $notice_level, PDO::PARAM_STR);
			$stmt->bindParam( ':uniqid', $uniqid, PDO::PARAM_STR);
            $stmt->bindParam( ':title', $title, PDO::PARAM_STR);
            $stmt->bindParam( ':note', $note, PDO::PARAM_STR);

            $stmt->bindParam( ':account', $userid, PDO::PARAM_STR);
            
            $stmt->bindParam( ':datetime', $datetime, PDO::PARAM_STR);

            // SQLクエリの実行
            $res = $stmt->execute();

            // コミット
            $res = $pdo->commit();

        } catch(Exception $e) {

            // エラーが発生した時はロールバック
            $pdo->rollBack();
        }

        if( $res ) {
            $url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location:".$url."");
            exit;  
        } else {
            $error_message[] = '配信に失敗しました。(REGISTERED_DAME)';
        }

        // プリペアドステートメントを削除
        $stmt = null;


	}
   
}

if( !empty($_POST['note_del']) ) {
	$note_id = htmlentities($_POST['note_id']);

	if (!empty($pdo)) {

		$query = $pdo->prepare('SELECT * FROM notice WHERE uniqid = :uniqid limit 1');
		$query->execute(array(':uniqid' => $note_id));
		$result = $query->fetch();
		if($result == 0){
			$error_message[] = "削除できないお知らせです。";
		}

		if(empty($error_message)){

			try{
				// 通知削除クエリを実行
				$deleteQuery = $pdo->prepare("DELETE FROM notice WHERE uniqid = :uniqid");
				$deleteQuery->bindValue(':uniqid', $note_id, PDO::PARAM_STR);
				$res = $deleteQuery->execute();

			} catch (Exception $e) {
					
				// エラーが発生した時はロールバック
				$pdo->rollBack();
			}

			if( $res ) {
				$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header("Location:".$url."");
				exit;  
			} else {
				$error_message[] = "お知らせの削除ができませんでした。(NOTICE_DELETE_DAME)";
			}

			$stmt = null;
		}
	}
}

if (!empty($pdo)) {
    $sql = "SELECT * FROM notice ORDER BY datetime DESC";
    $allnotice = $pdo->query($sql);    

    while ($row = $allnotice->fetch(PDO::FETCH_ASSOC)) {

        $Notices[] = $row;
    }
}


require('../logout/logout.php');

// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/home.css">
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<script src="../js/jquery-min.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>お知らせ配信 - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>

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

			<h1>お知らせ配信</h1>

			<p>タイトルと内容を入力して配信してください。<br>削除と編集はここからは出来ません。<br>DB管理画面から行ってください。</p>

			<!--<div>
				<p>緊急度レベル</p>
				<div class="p2">通常:右側に表示される通常の表示<br>警告:画面上部に常時表示<br>緊急:開いたときに画面中央にお知らせを表示(ウィンドウ)</div>
				<div class="radio_btn_zone">
					<input type="radio" name="notice_level" value="normal" id="normal" class="radiobtn_input" checked>
					<label for="normal" class="radiobtn_label">通常</label>

					<input type="radio" name="notice_level" value="warning" id="warning" class="radiobtn_input">
					<label for="warning" class="radiobtn_label">警告</label>

					<input type="radio" name="notice_level" value="danger" id="danger" class="radiobtn_input">
					<label for="danger" class="radiobtn_label">緊急</label>
				</div>
			</div>-->

            <div>
                <p>タイトル</p>
                <input placeholder="ここにタイトル" class="inbox" type="text" name="title" value="<?php if( !empty($_SESSION['title']) ){ echo htmlspecialchars( $_SESSION['title'], ENT_QUOTES, 'UTF-8'); } ?>">
            </div>

            <div>
                <p>本文</p>
                <textarea placeholder="ここに内容" class="inbox" name="note"><?php if( !empty($_SESSION['note']) ){ echo htmlspecialchars( $_SESSION['note'], ENT_QUOTES, 'UTF-8'); } ?></textarea>
            </div>

            <div>
                
            <input type="submit" class = "irobutton" name="btn_submit" value="配信">
            </div>

        </form>

		<div class="formarea">
			<?php if(!(empty($Notices))){?>
				<?php foreach ($Notices as $value) {?>
					<div class="server_code">
						<details>
							<summary><?php echo htmlentities($value["title"]);?></summary>
							<hr>
							<div class="p2">本文</div>
							<p><?php echo htmlentities($value["note"]);?></p>
							<hr>
							<div class="p2">配信日時</div>
							<p><?php echo date("Y年m月d日 H:i", strtotime(htmlentities($value["datetime"])));?></p>
							<div class="p2">ID</div>
							<?php if(!(empty($value["uniqid"]))){?>
								<p><?php echo htmlentities($value["uniqid"]);?></p>
							<?php }else{?>
								<p>IDはありません。</p>
							<?php }?>
							<hr>

							<form enctype="multipart/form-data" method="post">
								<?php if(!(empty($value["uniqid"]))){?>
									<div class="delbox">
										<p>削除ボタンを押すとこのお知らせは削除されます。</p>
										<input type="text" name="note_id" id="note_id" value="<?php echo htmlentities($value["uniqid"]);?>" style="display:none;" >
										<input type="submit" name="note_del" class="delbtn" value="削除">
									</div>
								<?php }else{?>
									<div class="delbox">
										<p>このお知らせは削除できません。</p>
									</div>
								<?php }?>
							</form>
						</details>
					</div>
				<?php }?>
			<?php }?>
		
		</div>

        
	</main>

	<?php require('../require/rightbox.php');?>
    <?php require('../require/botbox.php');?>
</body>

</html>