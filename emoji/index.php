<?php
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

require('../db.php');
require("../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

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

//------------------------------------------

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
	$myfollowlist = safetext($is_login["follow"]);
	$is_Admin = safetext($is_login["admin"]);
}
$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

if (!empty($pdo)) {
    $sql = "SELECT emojifile,emojiname,emojiinfo,emojidate FROM emoji ORDER BY emojidate DESC";
    $message_array = $pdo->query($sql);

    while ($row = $message_array->fetch(PDO::FETCH_ASSOC)) {

        $messages[] = $row;
    }
}

require('../logout/logout.php');

if(isset($_GET['q'])){ 
	$keyword = safetext($_GET['q']);
}else{
	$keyword = "";
}

// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<link rel="stylesheet" href="../css/home.css">
<title>絵文字一覧 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

</head>

<body>
	<?php require('../require/leftbox.php');?>
	<div>
		<div id="clipboard" class="online" style="display:none;">
			<p>🗒️📎 コピーしました！</p>
		</div>
	</div>
	
	<main class="outer">
		<?php if( !empty($error_message) ): ?>
			<ul class="errmsg">
				<?php foreach( $error_message as $value ): ?>
					<p>・ <?php echo $value; ?></p>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<section class="inner">
			<div class="emojibox">
            	<h1>絵文字一覧</h1>
			</div>
			<div class="sendbox">
				<input class="inbox" placeholder="絵文字検索" id="emoji_searchword" type="text" value="<?php if( !empty($keyword) ){ echo safetext($keyword); } ?>">
				<button class="search_btn" id="search_btn">検索</button>
			</div>
			<div class="emojizone" id="emojizone">
						
			</div>
		</section>

		<div id="loading" class="loading" style="display: none;">
			🤔
		</div>
		<div id="error" class="error" style="display: none;">
			<h1>エラー</h1>
			<p>サーバーの応答がなかったか不完全だったようです。<br>ネットワークの接続が正常かを確認の上再読み込みしてください。<br>(NETWORK_HUKANZEN_STOP)</p>
		</div>

	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>

</body>
<script>
$(document).ready(function() {

	$(document).on('click', '.search_btn', function(event) {
		if ($("#emoji_searchword").val() != ''){
			$('#emojizone').empty();
			loadEmojis();
		} else {
			return;
		}
	});

    window.document.onkeydown = function(event){
        if (event.key === 'Enter') {
			if ($("#emoji_searchword").val() != ''){
				$('#emojizone').empty();
				loadEmojis();
			} else {
				return;
			}
        }
    }

	$(document).on('click','.emjtex',function(){
		var children = $(this).children("div").children("div").children("h3");

		navigator.clipboard.writeText(children.text());
		$("#clipboard").show();
		window.setTimeout(function(){
			$("#clipboard").hide();
		}, 5000);
	});

	loadEmojis();

	var Emoji_pageNumber = 1;
	var isLoading = false;

	function loadEmojis() {
		if (isLoading) return;
		isLoading = true;
		$("#loading").show();

		var userid = '<?php echo $userid; ?>';
		var account_id = '<?php echo $loginid; ?>';
		var search_query = $("#emoji_searchword").val();
		var viewmode = 'page'
		$.ajax({
			url: '../nextpage/emojiview.php', // PHPファイルへのパス
			method: 'GET',
			data: { page: Emoji_pageNumber, userid: userid , account_id: account_id , search_query: search_query, view_mode: viewmode},
			dataType: 'html',
			timeout: 300000,
			success: function(response) {
				$('#emojizone').append(response);
				Emoji_pageNumber++;
				isLoading = false;
				$("#loading").hide();
			},
			error: function (xhr, textStatus, errorThrown) {  // エラーと判定された場合
				isLoading = false;
				$("#loading").hide();
				$("#error").show();
			},
		});
	}

	$('.outer').on('scroll', function() {
		var innerHeight = $('.inner').innerHeight(), //内側の要素の高さ
			outerHeight = $('.outer').innerHeight(), //外側の要素の高さ
			outerBottom = innerHeight - outerHeight; //内側の要素の高さ - 外側の要素の高さ
		if (outerBottom <= $('.outer').scrollTop()) {
			var elem = document.getElementById("noemoji");

			if (elem === null){
				// 存在しない場合の処理
				loadEmojis();
			} else {
				// 存在する場合の処理
				return;
			}
		}
	});

});
</script>

</html>