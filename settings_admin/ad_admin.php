<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

function random_code($length = 8){
    return substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
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
$is_login = uwuzuUserLogin($_SESSION, $_COOKIE, $_SERVER['REMOTE_ADDR'], "admin");
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
	$myfollowlist = safetext($is_login["follow"]);
	$is_Admin = safetext($is_login["admin"]);
}

$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

if( !empty($_POST['ads_btn_submit']) ) {

	$ads_url = safetext($_POST['ads_url']);
	$ads_img_url = safetext($_POST['ads_img_url']);
	$ads_start_date = date('Y/m/d H:i:s', strtotime(safetext(date($_POST['ads_start_date']))));
	$ads_limit_date = date('Y/m/d H:i:s', strtotime(safetext(date($_POST['ads_limit_date']))));

	$ads_memo = safetext($_POST['ads_memo']);

	if(empty($ads_url)){
		$error_message[] = "URLが入力されていません。(INPUT_PLEASE)";
	}
	if(empty($ads_img_url)){
		$error_message[] = "画像のURLが入力されていません。(INPUT_PLEASE)";
	}
	if(empty($ads_start_date)){
		$error_message[] = "設置開始日時が入力されていません。(INPUT_PLEASE)";
	}
	if(empty($ads_limit_date)){
		$error_message[] = "設置終了日時が入力されていません。(INPUT_PLEASE)";
	}
	if(empty($ads_memo)){
		$error_message[] = "メモが入力されていません。(INPUT_PLEASE)";
	}

	if(empty($error_message)){
		if (!empty($pdo)) {
			// 書き込み日時を取得
			$datetime = date("Y-m-d H:i:s");
			$uniqid = createUniqId();

			// トランザクション開始
			$pdo->beginTransaction();

			try {

				// SQL作成
				$stmt = $pdo->prepare("INSERT INTO ads (uniqid, url, image_url, memo, start_date, limit_date, datetime) VALUES (:uniqid, :url, :image_url, :memo, :start_date, :limit_date, :datetime)");
		
				$stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
				$stmt->bindParam(':url', $ads_url, PDO::PARAM_STR);
				$stmt->bindParam(':image_url', $ads_img_url, PDO::PARAM_STR);
				$stmt->bindParam(':memo', $ads_memo, PDO::PARAM_STR);
				$stmt->bindParam(':start_date', $ads_start_date, PDO::PARAM_STR);
				$stmt->bindParam(':limit_date', $ads_limit_date, PDO::PARAM_STR);
				$stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

				// SQLクエリの実行
				$res = $stmt->execute();

				// コミット
				$res = $pdo->commit();

			} catch(Exception $e) {

				// エラーが発生した時はロールバック
				$pdo->rollBack();
				actionLog($userid, "error", "ad_admin", null, $e, 4);
			}

			if( $res ) {
				actionLog($userid, "info", "ad_admin", null, "広告が新規作成されました", 0);
				$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header("Location:".$url."");
				exit;  
			} else {
				$error_message[] = $e->getMessage();
			}

			// プリペアドステートメントを削除
			$stmt = null;

		}
	}
}
if( !empty($_POST['ads_del']) ) {
	$ads_uniqid = safetext($_POST['ads_id']);
	try{
		// 通知削除クエリを実行
		$deleteQuery = $pdo->prepare("DELETE FROM ads WHERE uniqid = :uniqid");
		$deleteQuery->bindValue(':uniqid', $ads_uniqid, PDO::PARAM_STR);
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
		$error_message[] = $e->getMessage();
	}


	// プリペアドステートメントを削除
	$stmt = null;
}

require('../logout/logout.php');

if (!empty($pdo)) {
    $sql = "SELECT * FROM ads ORDER BY datetime DESC";
    $allads = $pdo->query($sql);    

    while ($row = $allads->fetch(PDO::FETCH_ASSOC)) {

        $adss[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/home.css">
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>広告 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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
	<div class="admin_settings">
		<?php require('settings_left_menu.php');?>
		
		<div class="admin_right">       
			<form class="formarea" enctype="multipart/form-data" method="post">
				<h1>広告追加</h1>
				<p>広告はLTL・FTL・返信画面に表示されます。<br>また、投稿15件につき一件の広告がランダムに選ばれ表示されます。<br>表示の優先順位は設定できません。</p>
				<div>
					<p>クリックした時のリダイレクト先URL</p>
					<div class="p2">広告をクリックするとこのURLに飛びます。</div>
					<input id="ads_url" placeholder="https://uwuzu.net/" class="inbox" type="text" name="ads_url" value="">
				</div>
				<div>
					<p>画像URL</p>
					<div class="p2">以下のURL先の画像が表示されます。</div>
					<input id="ads_img_url" placeholder="https://uwuzu.net/img/uwuzulogo.png" class="inbox" type="text" name="ads_img_url" value="">
				</div>
				<div>
					<p>掲載開始日時</p>
					<div class="p2">広告の掲載開始日時です。</div>
					<input type="datetime-local" name="ads_start_date" class="inbox" value="">
				</div>
				<div>
					<p>掲載終了日時</p>
					<div class="p2">広告の掲載終了日時です。</div>
					<input type="datetime-local" name="ads_limit_date" class="inbox" value="">
				</div>
				<div>
					<p>広告のメモ</p>
					<div class="p2">ユーザーが広告について確認するときに表示されるメモです。</div>
					<textarea type="text" name="ads_memo" placeholder="このメモはユーザーに公開されます" class="inbox"></textarea>
				</div>

				<input type="submit" class = "irobutton" name="ads_btn_submit" value="追加">
			</form>
			<div class="formarea">
					<h1>広告一覧</h1>
					<?php if(!(empty($adss))){?>
						<?php foreach ($adss as $value) {?>
							<div class="server_code">
								<details>
									<summary><?php echo safetext($value["url"]);?></summary>
									<hr>
									<p>設置状況:<?php if( !empty($value["url"]) ){
										if($value["start_date"] < date("Y-m-d H:i:s") && $value["limit_date"] > date("Y-m-d H:i:s")){
											echo "設置中　　✅";
										}else{
											echo "設置解除済⛔";
										}}?></p>
									<p>設置期間:<?php echo date("Y/m/d H:i",strtotime($value["start_date"])).' - '.date("Y/m/d H:i",strtotime($value["limit_date"])).'';?>
									<hr>
									<p>URL:<?php echo safetext($value["url"]);?></p>
									<p>画像URL:<?php echo safetext($value["image_url"]);?></p>
									<hr>
									<p>メモ:<?php echo safetext($value["memo"]);?></p>
									<hr>
									<p>追加日時:<?php echo safetext($value["datetime"]);?></p>
									<hr>
									<form enctype="multipart/form-data" method="post">
										<div class="delbox">
											<p>削除ボタンを押すとこの広告は削除されます。</p>
											<input type="text" name="ads_id" id="ads_id" value="<?php echo safetext($value["uniqid"]);?>" style="display:none;" >
											<input type="submit" name="ads_del" class="delbtn" value="削除">
										</div>
									</form>
							</details>
						</div>
						<?php }?>
					<?php }else{?>
						<div class="tokonone" id="noueuse"><p>広告がありません</p></div>
					<?php }?>
			</div>
		</div>
	</div>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>

</body>
</html>