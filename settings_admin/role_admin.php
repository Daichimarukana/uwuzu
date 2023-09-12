<?php

$servernamefile = "../server/servername.txt";

$serverinfofile = '../server/info.txt';
$serverinfo = file_get_contents($serverinfofile);

$servertermsfile = '../server/terms.txt';
$serverterms = file_get_contents($servertermsfile);

$serverprvfile = '../server/privacypolicy.txt';
$serverprv = file_get_contents($serverprvfile);

$contactfile = "../server/contact.txt";

$adminfile = "../server/admininfo.txt";

$serverstopfile = "../server/serverstop.txt";

$onlyuserfile = "../server/onlyuser.txt";

$err404imagefile = "../server/404imagepath.txt";

$robots = "../robots.txt";

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
if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,admin FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', $_SESSION['userid']);
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_SESSION['loginid'] === $res["loginid"] && $_SESSION['userid'] === $res["userid"]){
	// セッションに値をセット
	$userid = $_SESSION['userid']; // セッションに格納されている値をそのままセット
	$username = $_SESSION['username']; // セッションに格納されている値をそのままセット
	$loginid = $res["loginid"];
	$_SESSION['admin_login'] = true;
	$_SESSION['userid'] = $userid;
	$_SESSION['username'] = $username;
	$_SESSION['loginid'] = $res["loginid"];
	setcookie('userid', $userid, [
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	setcookie('username', $username,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	setcookie('loginid', $res["loginid"],[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	setcookie('admin_login', true,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	}else{
		header("Location: ../login.php");
		exit;
	}

		
} elseif (isset($_COOKIE['admin_login']) && $_COOKIE['admin_login'] == true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,admin FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', $_COOKIE['userid']);
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_COOKIE['loginid'] === $res["loginid"] && $_COOKIE['userid'] === $res["userid"]){
	// セッションに値をセット
	$userid = $_COOKIE['userid']; // クッキーから取得した値をセット
	$username = $_COOKIE['username']; // クッキーから取得した値をセット
	$loginid = $res["loginid"];
	$_SESSION['admin_login'] = true;
	$_SESSION['userid'] = $userid;
	$_SESSION['username'] = $username;
	$_SESSION['loginid'] = $res["loginid"];
	setcookie('userid', $userid,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	setcookie('username', $username,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	setcookie('loginid', $res["loginid"],[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
	]);
	setcookie('admin_login', true,[
		'expires' => time() + 60 * 60 * 24 * 14,
		'path' => '/',
		'samesite' => 'lax',
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

	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
	$query = $dbh->prepare('SELECT * FROM role WHERE roleidname = :roleid limit 1');
    $query->execute(array(':roleid' => $roleid));
    $result3 = $query->fetch();

	if(empty($rolename)){
		$error_message[] = "ロール名が入力されていません。";
	}
	if(empty($roleid)){
		$error_message[] = "ロールのidが入力されていません。";
	}elseif($result3 > 0){
		$error_message[] = 'このロールのid('.$roleid.')は既に使用されています。他のidを作成してください。';
	}

	if(empty($rolecolor)){
		$error_message[] = "ロールの色が入力されていません。";
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
				$stmt = $pdo->prepare("INSERT INTO role (rolename, roleauth, rolecolor, roleidname) VALUES (:rolename, :roleauth, :rolecolor, :roleidname)");
		
				$stmt->bindParam(':rolename', $rolename, PDO::PARAM_STR);
				$stmt->bindParam(':roleauth', $roleauth, PDO::PARAM_STR);
				$stmt->bindParam(':rolecolor', $rolecolor, PDO::PARAM_STR);
				$stmt->bindParam(':roleidname', $roleid, PDO::PARAM_STR);

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

		try{
			// フォローの更新
			$updateFollowQuery = $pdo->prepare("UPDATE account SET role = REPLACE(role, :roleid, '') WHERE role LIKE :pattern");
			$updateFollowQuery->bindValue(':roleid', ",$role_id", PDO::PARAM_STR);
			$updateFollowQuery->bindValue(':pattern', "%,$role_id%", PDO::PARAM_STR);
			$res = $updateFollowQuery->execute();
	
		} catch (Exception $e) {
				
			// エラーが発生した時はロールバック
			$pdo->rollBack();
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
			$error_message[] = "既に".$add_roleid."は付与済みです。";
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
		$error_message[] = "ロールがないまたはユーザーがいません。";
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
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>ロール - <?php echo file_get_contents($servernamefile);?></title>

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
					<input id="rolecolor" placeholder="256238" class="inbox" type="text" name="rolecolor" maxlength="6" value="">
				</div>

				<input type="submit" class = "irobutton" name="role_btn_submit" value="作成">
			</form>
			<div class="formarea">
					<hr>
					<h1>ロール付与</h1>
					<p>特定のユーザーにロール付与するときに使用してください。</p>
					<button id="addrole" class="irobutton">付与</button>
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
			<p>ロール付与先のユーザーidと付与したいロールのidを入力してください。<br>なお、現時点ではここからロールの剥奪は出来ませんのでご注意ください。</p>
			<form method="post" id="deleteForm">
				<div class="p2">付与先ユーザーid</div>
				<input type="text" id="add_userid" class="inbox" placeholder="admin" name="add_userid" value="">
				<div class="p2">付与するロールid</div>
				<input type="text" id="add_roleid" class="inbox" placeholder="role" name="add_roleid" value="">
				<div class="btn_area">
					<input type="submit" id="deleteButton" class="fbtn_no" name="send_add_role_submit" value="付与">
					<input type="button" id="cancelButton" class="fbtn" value="キャンセル">
				</div>
			</form>
		</div>
	</div>

	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>

</body>
<script>
$(document).ready(function() {
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
});

</script>
</html>