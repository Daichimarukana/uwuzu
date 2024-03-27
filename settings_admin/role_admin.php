<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}
function random_code($length = 8){
    return substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
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

if( !empty($_POST['role_btn_submit']) ) {

	$rolename = htmlentities($_POST['rolename']);
	$roleid = htmlentities($_POST['roleid']);
	$rolecolor = htmlentities($_POST['rolecolor']);
	$roleeffect = htmlentities($_POST['roleeffect']);

	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
	$query = $dbh->prepare('SELECT * FROM role WHERE roleidname = :roleid limit 1');
    $query->execute(array(':roleid' => $roleid));
    $result3 = $query->fetch();

	if(empty($rolename)){
		$error_message[] = "ロール名が入力されていません。(INPUT_PLEASE)";
	}
	if(empty($roleid)){
		$error_message[] = "ロールのidが入力されていません。(ROLE_ID_INPUT_PLEASE)";
	}elseif($result3 > 0){
		$error_message[] = 'このロールのid('.$roleid.')は既に使用されています。他のidを作成してください。(ROLE_ID_SHIYOUZUMI)';
	}

	if(empty($rolecolor)){
		$error_message[] = "ロールの色が入力されていません。(INPUT_PLEASE)";
	}

	if(empty($roleeffect)){
		$error_message[] = "ロールに適用するエフェクトが選択されていません。(INPUT_PLEASE)";
	}else{
		if($roleeffect == "1"){
			$save_role_effect = "none";
		}
		if($roleeffect == "2"){
			$save_role_effect = "shine";
		}
		if($roleeffect == "3"){
			$save_role_effect = "rainbow";
		}
	}

	if (!empty($pdo)) {
		if (empty($error_message)) {
			// 書き込み日時を取得
			$datetime = date("Y-m-d H:i:s");
			$roleauth = "user";

			// トランザクション開始
			$pdo->beginTransaction();

			try {

				// SQL作成
				$stmt = $pdo->prepare("INSERT INTO role (rolename, roleauth, rolecolor, roleidname, roleeffect) VALUES (:rolename, :roleauth, :rolecolor, :roleidname, :roleeffect)");
		
				$stmt->bindParam(':rolename', $rolename, PDO::PARAM_STR);
				$stmt->bindParam(':roleauth', $roleauth, PDO::PARAM_STR);
				$stmt->bindParam(':rolecolor', $rolecolor, PDO::PARAM_STR);
				$stmt->bindParam(':roleidname', $roleid, PDO::PARAM_STR);
				$stmt->bindParam(':roleeffect', $save_role_effect, PDO::PARAM_STR);

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
				$error_message[] = $e->getMessage();
			}

			// プリペアドステートメントを削除
			$stmt = null;

		}
	}
}
if( !empty($_POST['role_del']) ) {
	$role_id = htmlentities($_POST['role_id']);
	try{
		// 通知削除クエリを実行
		$deleteQuery = $pdo->prepare("DELETE FROM role WHERE roleidname = :roleid");
		$deleteQuery->bindValue(':roleid', $role_id, PDO::PARAM_STR);
		$res = $deleteQuery->execute();

		// ロールを削除したい全てのアカウントを取得
		$query = $pdo->prepare("SELECT * FROM account WHERE role LIKE :pattern1 OR role LIKE :pattern2 OR role LIKE :pattern3");
		$query->bindValue(':pattern1', "%,$role_id,%", PDO::PARAM_STR);
		$query->bindValue(':pattern2', "%,$role_id", PDO::PARAM_STR);
		$query->bindValue(':pattern3', "$role_id,%", PDO::PARAM_STR);
		$query->execute();
		$accounts = $query->fetchAll();

		foreach ($accounts as $account) {
			// フォローの更新
			if (strpos($account['role'], ",$role_id,") !== false || strpos($account['role'], ",$role_id") !== false || strpos($account['role'], "$role_id,") !== false) {
				$delrole_roleList = explode(',', $account['role']);
				$delrole_roleList = array_diff($delrole_roleList, array($role_id));
				$new_delrole_roleList = implode(',', $delrole_roleList);

				$updateroleQuery = $pdo->prepare("UPDATE account SET role = :role WHERE userid = :userid");
				$updateroleQuery->bindValue(':role', $new_delrole_roleList, PDO::PARAM_STR);
				$updateroleQuery->bindValue(':userid', $account['userid'], PDO::PARAM_STR);
				$updateroleQuery->execute();
			}
		}

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


if( !empty($_POST['send_add_role_submit']) ) {
	$add_userid = htmlentities($_POST['add_userid']);
	$add_roleid = htmlentities($_POST['add_roleid']);

	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
	$query = $dbh->prepare('SELECT * FROM account WHERE userid = :userid limit 1');
    $query->execute(array(':userid' => $add_userid));
    $result4 = $query->fetch();

	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
	$query = $dbh->prepare('SELECT * FROM role WHERE roleidname = :roleid limit 1');
    $query->execute(array(':roleid' => $add_roleid));
    $result5 = $query->fetch();

	if($result4 > 0 && $result5 > 0){

		if (false !== strstr($result4["role"], ','.$add_roleid)) {
			$error_message[] = "既に".$add_roleid."は付与済みです。(ROLE_HUYOZUMI)";
		}

		$New_role_id = $result4["role"] . ',' . $add_roleid;
		if(empty($error_message)){
			try{
				// フォローの更新
				$updateRoleQuery = $pdo->prepare("UPDATE account SET role = :newrole WHERE userid = :userid");
				$updateRoleQuery->bindValue(':newrole', "$New_role_id", PDO::PARAM_STR);
				$updateRoleQuery->bindValue(':userid', $add_userid, PDO::PARAM_STR);
				$res = $updateRoleQuery->execute();
		
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
	}else{
		$error_message[] = "ロールがないまたはユーザーがいません。(ROLE_OR_USER_NOT_FOUND)";
	}
}
if( !empty($_POST['send_del_role_submit']) ) {
	$del_userid = htmlentities($_POST['del_userid']);
	$del_roleid = htmlentities($_POST['del_roleid']);

	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
	$query = $dbh->prepare('SELECT * FROM account WHERE userid = :userid limit 1');
    $query->execute(array(':userid' => $del_userid));
    $result4 = $query->fetch();

	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
	$query = $dbh->prepare('SELECT * FROM role WHERE roleidname = :roleid limit 1');
    $query->execute(array(':roleid' => $del_roleid));
    $result5 = $query->fetch();

	if($result4 > 0 && $result5 > 0){
		$userQuery = $dbh->prepare("SELECT role FROM account WHERE userid = :userid");
		$userQuery->bindValue(':userid', $del_userid);
		$userQuery->execute();
		$userData = $userQuery->fetch();

		// ロール剥奪ボタンが押された場合の処理
		$roleList = explode(',', $userData['role']);
		if (in_array($del_roleid, $roleList)) {
			// 自分が相手をフォローしている場合、相手のfollowerカラムと自分のfollowカラムを更新
			$roleList = array_diff($roleList, array($del_roleid));
			$newroleList = implode(',', $roleList);
	
			// UPDATE文を実行してフォロー情報を更新
			$updateQuery = $pdo->prepare("UPDATE account SET role = :role WHERE userid = :userid");
			$updateQuery->bindValue(':role', $newroleList, PDO::PARAM_STR);
			$updateQuery->bindValue(':userid', $del_userid, PDO::PARAM_STR);
			$res = $updateQuery->execute();
	
			if ($res) {
				$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header("Location:" . $url);
				exit;
			} else {
				$error_message[] = '更新に失敗しました。(REGISTERED_DAME)';
			}
	
			$stmt = null;
		}
	}else{
		$error_message[] = "ロールがないまたはユーザーがいません。(ROLE_OR_USER_NOT_FOUND)";
	}
}

require('../logout/logout.php');

if (!empty($pdo)) {
    $sql = "SELECT * FROM role";
    $allrole = $pdo->query($sql);    

    while ($row = $allrole->fetch(PDO::FETCH_ASSOC)) {

        $roles[] = $row;
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
<title>ロール - <?php echo htmlentities($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8', false);?></title>

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
				<h1>ロール作成</h1>
				<p>ここではロールを作成できます。</p>
				<div>
					<p>ロール名</p>
					<div class="p2">ロールの表示名です。</div>
					<input id="rolename" placeholder="RoleName" class="inbox" type="text" name="rolename" value="">
				</div>
				<div>
					<p>ロールid</p>
					<div class="p2">ロールのidです。ロールを付与する際に使用されるidです。</div>
					<input onInput="checkForm(this)" id="roleid" placeholder="role" class="inbox" type="text" name="roleid" value="">
				</div>
				<div>
					<p>ロールの色</p>
					<div class="p2">ロールの色です。<br>HEXコードで入力してください。(#はつけないでください。)</div>
					<input id="rolecolor" onInput="checkForm(this)" placeholder="256238" class="inbox" type="text" name="rolecolor" maxlength="6" value="">
				</div>
				<div>
				<div class="p2">ロールに付与するエフェクト</div>
					<div class="radio_btn_zone">
						<input type="radio" name="roleeffect" value="1" id="1" class="radiobtn_input" checked>
						<label for="1" class="radiobtn_label">なし</label>

						<input type="radio" name="roleeffect" value="2" id="2" class="radiobtn_input">
						<label for="2" class="radiobtn_label">輝かせる</label>

						<input type="radio" name="roleeffect" value="3" id="3" class="radiobtn_input">
						<label for="3" class="radiobtn_label">枠を虹色にする</label>
					</div>
				</div>

				<input type="submit" class = "irobutton" name="role_btn_submit" value="作成">
			</form>
			<div class="formarea">
				<hr>
				<h1>ロール付与</h1>
				<p>特定のユーザーにロール付与するときに使用してください。</p>
				<button id="addrole" class="irobutton">付与</button>
				<hr>
				<h1>ロール剥奪</h1>
				<p>特定のユーザーからロールを剥奪する時に使用してください。</p>
				<button id="delrole" class="irobutton">剥奪</button>
				<hr>
				<h1>ロール一覧</h1>
				<?php if(!(empty($roles))){?>
					<?php foreach ($roles as $value) {?>
						<div class="server_code">
							<details>
								<summary><?php echo htmlentities($value["rolename"]);?></summary>
								<hr>
								<p>ロールのid:<?php echo htmlentities($value["roleidname"]);?></p>
								<p>ロールの色:#<?php echo htmlentities($value["rolecolor"]);?></p>
								<p>ロールのエフェクト:<?php 
								if(htmlentities($value["roleeffect"]) == '' || htmlentities($value["roleeffect"]) == 'none'){
									$role_view_effect = "なし";
								}elseif(htmlentities($value["roleeffect"]) == 'shine'){
									$role_view_effect = "輝かせる";
								}elseif(htmlentities($value["roleeffect"]) == 'rainbow'){
									$role_view_effect = "枠を虹色にする";
								}else{
									$role_view_effect = "不明";
								}
								echo $role_view_effect;
								?></p>
								<hr>
								<div class="roleboxes">
									<?php 
										if(htmlentities($value["roleeffect"], ENT_QUOTES, 'UTF-8', false) == '' || htmlentities($value["roleeffect"], ENT_QUOTES, 'UTF-8', false) == 'none'){
											$role_view_effect = "";
										}elseif(htmlentities($value["roleeffect"], ENT_QUOTES, 'UTF-8', false) == 'shine'){
											$role_view_effect = "shine";
										}elseif(htmlentities($value["roleeffect"], ENT_QUOTES, 'UTF-8', false) == 'rainbow'){
											$role_view_effect = "rainbow";
										}else{
											$role_view_effect = "";
										}
									?>
									<div class="rolebox <?php echo htmlentities($role_view_effect, ENT_QUOTES, 'UTF-8', false); ?>" style="border: 1px solid <?php echo '#' . htmlentities($value["rolecolor"], ENT_QUOTES, 'UTF-8', false); ?>;">
										<p style="color: <?php echo '#' . $value["rolecolor"]; ?>;">
											<?php if (!empty($value["rolename"])) { echo htmlentities($value["rolename"], ENT_QUOTES, 'UTF-8', false); }else{ echo("ロールが正常に設定されていません。");} ?>
										</p>
									</div>
								</div>
								<hr>

								<form enctype="multipart/form-data" method="post">
									<?php if(!($value["roleidname"] === "user" || $value["roleidname"] === "official" || $value["roleidname"] === "ice")){?>
										<div class="delbox">
											<p>削除ボタンを押すとこのロールは削除されます。<br>また、このロールをつけているユーザー全員からこのロールが剥奪されます。</p>
											<input type="text" name="role_id" id="role_id" value="<?php echo htmlentities($value["roleidname"]);?>" style="display:none;" >
											<input type="submit" name="role_del" class="delbtn" value="削除">
										</div>
									<?php }else{?>
										<div class="delbox">
											<p>このロールは削除できません。</p>
										</div>
									<?php }?>
								</form>
							</details>
						</div>
					<?php }?>
				<?php }?>
			</div>
		</div>
	</div>

	<div id="account_addrole_Modal" class="modal">
		<div class="modal-content">
			<h1>ロール付与</h1>
			<p>ロール付与先のユーザーidと付与したいロールのidを入力してください。</p>
			<form method="post" id="deleteForm">
				<div class="p2">付与先ユーザーid</div>
				<input type="text" id="add_userid" onInput="checkForm(this)" class="inbox" placeholder="admin" name="add_userid" value="">
				<div class="p2">付与するロールid</div>
				<input type="text" id="add_roleid" onInput="checkForm(this)" class="inbox" placeholder="role" name="add_roleid" value="">
				<div class="btn_area">
					<input type="submit" id="deleteButton" class="fbtn_no" name="send_add_role_submit" value="付与">
					<input type="button" id="cancelButton" class="fbtn" value="キャンセル">
				</div>
			</form>
		</div>
	</div>

	<div id="account_delrole_Modal" class="modal">
		<div class="modal-content">
			<h1>ロール剥奪</h1>
			<p>ロール剥奪先のユーザーidと剥奪したいロールのidを入力してください。</p>
			<form method="post" id="delrole_Form">
				<div class="p2">剥奪先ユーザーid</div>
				<input type="text" id="del_userid" onInput="checkForm(this)" class="inbox" placeholder="admin" name="del_userid" value="">
				<div class="p2">剥奪するロールid</div>
				<input type="text" id="del_roleid" onInput="checkForm(this)" class="inbox" placeholder="role" name="del_roleid" value="">
				<div class="btn_area">
					<input type="submit" id="delrole_deleteButton" class="fbtn_no" name="send_del_role_submit" value="剥奪">
					<input type="button" id="delrole_cancelButton" class="fbtn" value="キャンセル">
				</div>
			</form>
		</div>
	</div>

	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>

</body>
<script type="text/javascript">
	function checkForm(inputElement) {
		var str = inputElement.value;
		while (str.match(/[^A-Za-z\d_]/)) {
			str = str.replace(/[^A-Za-z\d_]/, "");
		}
		inputElement.value = str;
	}

	var modal = document.getElementById('account_addrole_Modal');
    var deleteButton = document.getElementById('deleteButton');
    var cancelButton = document.getElementById('cancelButton'); // 追加
	var modalMain = $('.modal-content');

    document.getElementById("addrole").addEventListener('click', function(){
        modal.style.display = 'block';
		modalMain.addClass("slideUp");
    	modalMain.removeClass("slideDown");

        deleteButton.addEventListener('click', () => {
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal.style.display = 'none';
			}, 150);
        });

        cancelButton.addEventListener('click', () => { // 追加
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal.style.display = 'none';
			}, 150);
        });
    });

	var modal2 = document.getElementById('account_delrole_Modal');
    var delrole_deleteButton = document.getElementById('delrole_deleteButton');
    var delrole_cancelButton = document.getElementById('delrole_cancelButton'); // 追加
	var modalMain = $('.modal-content');

    document.getElementById("delrole").addEventListener('click', function(){
        modal2.style.display = 'block';
		modalMain.addClass("slideUp");
    	modalMain.removeClass("slideDown");

        delrole_deleteButton.addEventListener('click', () => {
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal2.style.display = 'none';
			}, 150);
        });

        delrole_cancelButton.addEventListener('click', () => { // 追加
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal2.style.display = 'none';
			}, 150);
        });
    });
</script>
</html>