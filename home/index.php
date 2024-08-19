<?php
require('../db.php');
//関数呼び出し
//- EXIF
require('../function/function.php');

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

$mojisizefile = "../server/textsize.txt";
$mojisize = (int)safetext(file_get_contents($mojisizefile));

//投稿及び返信レート制限↓(分):デフォで60件/分まで
$max_ueuse_rate_limit = 60;

$banurldomainfile = "../server/banurldomain.txt";
$banurl_info = file_get_contents($banurldomainfile);
$banurl = preg_split("/\r\n|\n|\r/", $banurl_info);

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
if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] == true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,follow,admin,role,sacinfo,blocklist FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', safetext($_SESSION['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_SESSION['loginid'] === $res["loginid"] && $_SESSION['userid'] == $res["userid"]){
	// セッションに値をセット
	$userid = safetext($res['userid']); // セッションに格納されている値をそのままセット
	$username = safetext($res['username']); // セッションに格納されている値をそのままセット
	$loginid = safetext($res["loginid"]);
	$role = safetext($res["role"]);
	$sacinfo = safetext($res["sacinfo"]);
	$myblocklist = safetext($res["blocklist"]);
	$myfollowlist = safetext($res["follow"]);
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
	$passQuery->bindValue(':userid', safetext($_COOKIE['userid']));
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_COOKIE['loginid'] === $res["loginid"] && $_COOKIE['userid'] == $res["userid"]){
	// セッションに値をセット
	$userid = safetext($res['userid']); // クッキーから取得した値をセット
	$username = safetext($res['username']); // クッキーから取得した値をセット
	$loginid = safetext($res["loginid"]);
	$role = safetext($res["role"]);
	$sacinfo = safetext($res["sacinfo"]);
	$myblocklist = safetext($res["blocklist"]);
	$myfollowlist = safetext($res["follow"]);
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
$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

//-----------------URLから取得----------------
if(isset($_GET['text'])) { 
    $ueuse = safetext($_GET['text']);
}elseif(isset($_COOKIE['ueuse'])) { 
    $ueuse = safetext($_COOKIE['ueuse']);
}

//-------------------------------------------

if( !empty($_POST['btn_submit']) ) {
	$ueuse = safetext($_POST['ueuse']);

	if(isset($_POST['nsfw_chk'])){
		$nsfw_chk = safetext($_POST['nsfw_chk']);
	}else{
		$nsfw_chk = "false";
	}

	$photo1 = $_FILES['upload_images'];
	$photo2 = $_FILES['upload_images2'];
	$photo3 = $_FILES['upload_images3'];
	$photo4 = $_FILES['upload_images4'];
	$video1 = $_FILES['upload_videos1'];

	$rpUniqid = "";
	$ruUniqid = "";
	$ueuse_result = send_ueuse($userid,$rpUniqid,$ruUniqid,$ueuse,$photo1,$photo2,$photo3,$photo4,$video1,$nsfw_chk);

	if($ueuse_result == null){
		//一時保存していたユーズ内容の削除
		setcookie("ueuse", "", time() - 3600);

		$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
		header("Location:".$url."");
		exit;  
	}else{
		$error_message = $ueuse_result;
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
<script src="../js/jquery-min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<script src="../js/nsfw_event.js"></script>
<link rel="manifest" href="../manifest/manifest.json" />
<script>
if ("serviceWorker" in navigator) {
	navigator.serviceWorker.register("../sw.js").then(reg => {
		console.log("ServiceWorker OK", reg);
	}).catch(err => {
		console.log("ServiceWorker BAD", err);
	});
}
</script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<link rel="stylesheet" href="../css/home.css">
<title>ローカルタイムライン - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

</head>

<body>

	<div>
		<div id="new_ueuse" class="new_ueuse" style="display:none;">
			<a onclick="window.location.reload(true);"><p>🍊新しいユーズがあります！</p></a>
		</div>
		<div id="notify" class="new_ueuse" style="display:none;">
			<p>お知らせです</p>
		</div>
		<div id="clipboard" class="online" style="display:none;">
			<p>🗒️📎 ユーズのURLをコピーしました！</p>
		</div>
		<div id="offline" class="offline" style="display:none;">
			<p>🦖💨 インターネットへの接続が切断されました...</p>
		</div>
		<div id="online" class="online" style="display:none;">
			<p>🌐💫 インターネットへの接続が復帰しました！！！</p>
		</div>
	</div>

	<?php require('../require/leftbox.php');?>
	
	<main class="outer">
		<?php if(empty($_COOKIE['event'])){
			  if (date("md") == "0101") {?>
			<div class="hny" id="osho_gats">
				<div class="top">Happy New Year <?php echo date("Y")?> !!!</div>
				<div class="textmain">
					<h1>あけましておめでとうございます！</h1>
					<p>あけましておめでとうございます<br>今日から<?php echo date("Y年")?>ですね～！<br>今年もどうぞuwuzuをよろしくお願いいたします！</p>
					<p><script type="text/javascript">
					rand = Math.floor(Math.random()*8);
										
					if (rand == 0) msg = "早速ですが年越しジャンプしました？";
					if (rand == 1) msg = "早速ですがお餅は食べましたか？";
					if (rand == 2) msg = "お餅を喉に詰まらせないよう気をつけてくださいね～";
					if (rand == 3) msg = "福袋とか買いましたか～？";
					if (rand == 4) msg = "やっぱりこたつでゆっくりしたいね...";
					if (rand == 5) msg = "みかんでも食べます？";
					if (rand == 6) msg = "お鍋でもどうですか～？";
					if (rand == 7) msg = "一生こたつにいたい...";
											
					document.write(msg);
					</script></p>
					<div class="rp"><?php echo date("Y年n月j日")?></div>
					<button id="os_exit_btn" class="ueusebtn">とじる</button>
				</div>
			</div>
			<?php }?>
		<?php }?>

		<div class="tlchange">
			<button class="btn on" id="timeline_local">ローカル</button>
			<button class="btn" id="timeline_follow">フォロー</button>
		</div>
		<?php if( !empty($error_message) ): ?>
			<ul class="errmsg">
				<?php foreach( $error_message as $value ): ?>
					<p>・ <?php echo $value; ?></p>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if(!($role ==="ice")){?>
			<form method="post" enctype="multipart/form-data">
				<div class="sendbox">
					<textarea id="ueuse" placeholder="いまどうしてる？" name="ueuse"><?php if( !empty($ueuse) ){ echo safetext($ueuse); } ?></textarea>

					<div class="fxbox">
						<label for="upload_images" id="images" title="画像1">
						<svg><use xlink:href="../img/sysimage/image_1.svg#image"></use></svg>
						<input type="file" name="upload_images" id ="upload_images" accept="image/*">
						</label>
						<label for="upload_images2" id="images2" style="display: none" title="画像2">
						<svg><use xlink:href="../img/sysimage/image_1.svg#image"></use></svg>
						<input type="file" name="upload_images2" id ="upload_images2" accept="image/*">
						</label>
						<label for="upload_images3" id="images3" style="display: none" title="画像3">
						<svg><use xlink:href="../img/sysimage/image_1.svg#image"></use></svg>
						<input type="file" name="upload_images3" id ="upload_images3" accept="image/*">
						</label>
						<label for="upload_images4" id="images4" style="display: none" title="画像4">
						<svg><use xlink:href="../img/sysimage/image_1.svg#image"></use></svg>
						<input type="file" name="upload_images4" id ="upload_images4" accept="image/*">
						</label>
						<label for="upload_videos1" id="videos1" title="動画1">
						<svg><use xlink:href="../img/sysimage/video_1.svg#video"></use></svg>
						<input type="file" name="upload_videos1" id ="upload_videos1" accept="video/*">
						</label>

						<div class="nsfw_button">
							<input id="nsfw_chk" class="nsfw_input" type='checkbox' name="nsfw_chk" value="true"/>
							<label for="nsfw_chk" class="nsfw_label" title="投稿をNSFW指定にする"><svg><use xlink:href="../img/sysimage/eye_1.svg#eye"></use></svg></label>
						</div>

						
						<label for="emoji_picker_btn" title="カスタム絵文字">
						<svg><use xlink:href="../img/sysimage/menuicon/emoji.svg#emoji"></use></svg>
						<input id="emoji_picker_btn" type='checkbox' value="false" style="display:none;"/>
						</label>

						<div class="moji_cnt" id="moji_cnt"><?php echo safetext($mojisize); ?></div>

						<input type="submit" class="ueusebtn" id='ueusebtn' name="btn_submit" value="ユーズする">
					</div>

					<div class="emoji_picker" id="emoji_picker" style="display:none;">
						<p>カスタム絵文字</p>
						<div class="emoji_picker_flex">
							
						</div>
					</div>
				</div>
			</form>
		<?php }?>

		<section class="inner">
			<div id="postContainer">
				

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

	<div id="myDelModal" class="modal">
		<div class="modal-content">
			<p>ユーズを削除しますか？</p>
			<form class="btn_area" method="post" id="deleteForm">
				<input type="button" id="deleteButton" class="fbtn_no" name="delete" value="削除">
				<input type="button" id="cancelButton" class="fbtn" value="キャンセル">
			</form>
		</div>
	</div>

	
	<div id="myAbiModal" class="modal">
		<div class="modal-content">
			<h1>ユーズに追記しますか？</h1>
			<p>※追記は削除出来ません。</p>
			<form method="post" id="AbiForm">
			<textarea id="abitexts" placeholder="なに追記する～？" name="abi"></textarea>
			<div class="btn_area">
				<input type="submit" id="AbiAddButton" class="fbtn_no" name="abi" value="追記">
				<input type="button" id="AbiCancelButton" class="fbtn" value="キャンセル">
			</div>
			</form>
		</div>
	</div>
	
	<div id="myQuoteReuseModal" class="modal">
		<div class="modal-content">
			<h1>引用リユーズ</h1>
			<p></p>
			<textarea id="reusetexts" placeholder="引用を追加" name="reuse"></textarea>
			<div class="btn_area">
				<input type="button" id="ReuseButton" class="fbtn_no" name="abi" value="リユーズ">
				<input type="button" id="ReuseCancelButton" class="fbtn" value="キャンセル">
			</div>
		</div>
	</div>

	<div id="Big_ImageModal" class="Image_modal">
		<div class="modal-content">
			<img id="Big_ImageMain" href="">
		</div>
	</div>

	<div id="ueuse_popup_back" class="ueuse_popup_back" style="display: none;">
		<div id="ueuse_popup" class="ueuse_popup_menu" style="display: none;">
			<button name="share" id="share" class="popbtn"><svg><use xlink:href="../img/sysimage/share_1.svg#share_1"></use></svg><span>シェア</span></button>
			<button name="delete" id="delete" class="popbtn delbtn"><svg><use xlink:href="../img/sysimage/delete_1.svg#delete"></use></svg><span>削除</span></button>
		</div>

		<div id="reuse_popup" class="ueuse_popup_menu" style="display: none;">
			<button name="normal_reuse_btn" id="normal_reuse_btn" class="popbtn"><svg><use xlink:href="../img/sysimage/reuse_1.svg#reuse_1"></use></svg><span>リユーズ</span></button>
			<button name="quote_reuse_btn" id="quote_reuse_btn" class="popbtn"><svg><use xlink:href="../img/sysimage/quote_1.svg#quote_1"></use></svg><span>引用</span></button>
			<button name="delete_reuse_btn" id="delete_reuse_btn" class="popbtn delbtn"><svg><use xlink:href="../img/sysimage/delete_1.svg#delete"></use></svg><span>取り消し</span></button>
		</div>
	</div>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>

</body>

<script>
$(document).ready(function() {
	var userid = '<?php echo $userid; ?>';
	var account_id = '<?php echo $loginid; ?>';

	var pageNumber = 1;
    var isLoading = false;
	var mode = "local";

	loadPosts();

    function loadPosts() {
        if (isLoading) return;
        isLoading = true;
		$("#loading").show();
		if(mode == "local"){
			$.ajax({
				url: '../nextpage/nextpage.php', // PHPファイルへのパス
				method: 'GET',
				data: { page: pageNumber, userid: userid , account_id: account_id },
				dataType: 'html',
				timeout: 300000,
				success: function(response) {
					$('#postContainer').append(response);
					pageNumber++;
					isLoading = false;
					$("#loading").hide();
				},
				error: function (xhr, textStatus, errorThrown) {  // エラーと判定された場合
					isLoading = false;
					$("#loading").hide();
					$("#error").show();
				},
			});
		}else if(mode == "follow"){
			$.ajax({
				url: '../nextpage/ftlpage.php', // PHPファイルへのパス
				method: 'GET',
				data: { page: pageNumber, userid: userid , account_id: account_id },
				dataType: 'html',
				timeout: 300000,
				success: function(response) {
					$('#postContainer').append(response);
					pageNumber++;
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
    }
	$("#timeline_local").on('click', function(event) {
		$('#timeline_local').addClass('on');
		$('#timeline_follow').removeClass('on');

		event.preventDefault();
		$("#postContainer").empty();
		pageNumber = 1;
		mode = "local";
		loadPosts();
	});
	$("#timeline_follow").on('click', function(event) {
		$('#timeline_local').removeClass('on');
		$('#timeline_follow').addClass('on');

		event.preventDefault();
		$("#postContainer").empty();
		pageNumber = 1;
		mode = "follow";
		loadPosts();
	});

	$('.outer').on('scroll', function() {
		var innerHeight = $('.inner').innerHeight(), //内側の要素の高さ
			outerHeight = $('.outer').innerHeight(), //外側の要素の高さ
			outerBottom = innerHeight - outerHeight; //内側の要素の高さ - 外側の要素の高さ
		if (outerBottom <= $('.outer').scrollTop()) {
			var elem = document.getElementById("noueuse");

			if (elem === null){
				// 存在しない場合の処理
				loadPosts();
			} else {
				// 存在する場合の処理
				return;
			}
		}
	});

	$(document).on('click', '.favbtn, .favbtn_after', function(event) {
		event.preventDefault();

		var postUniqid = $(this).data('uniqid');
		var likeCountElement = $(this).find('.like-count'); // いいね数を表示する要素

		var isLiked = $(this).hasClass('favbtn_after'); // 現在のいいねの状態を判定

		var $this = $(this); // ボタン要素を変数に格納

		$.ajax({
			url: '../favorite/favorite.php',
			method: 'POST',
			data: { uniqid: postUniqid, userid: userid, account_id: account_id  }, // ここに自分のユーザーIDを指定
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					// いいね成功時の処理
					if (isLiked) {
						$this.removeClass('favbtn_after'); // クラスを削除していいねを取り消す
						$this.find('use').attr('xlink:href', '../img/sysimage/favorite_1.svg#favorite'); // 画像を元の画像に戻す
					} else {
						$this.addClass('favbtn_after'); // クラスを追加していいねを追加する
						$this.find('use').attr('xlink:href', '../img/sysimage/favorite_2.svg#favorite'); // 画像を新しい画像に置き換える
					}

					var newFavoriteList = response.newFavorite.split(',');
					var likeCount = newFavoriteList.length - 1;
					likeCountElement.text(likeCount); // いいね数を更新
				} else {
					// いいね失敗時の処理
				}
			}.bind(this), // コールバック内でthisが適切な要素を指すようにbindする
			error: function() {
				// エラー時の処理
			}
		});
	});


	$(document).on('click', '.bookmark, .bookmark_after', function(event) {

		event.preventDefault();

		var postUniqid = $(this).data('uniqid');
		var likeCountElement = $(this).find('.like-count'); // いいね数を表示する要素

		var isLiked = $(this).hasClass('bookmark_after'); // 現在のいいねの状態を判定

		var $this = $(this); // ボタン要素を変数に格納

		$.ajax({
			url: '../bookmark/bookmark.php',
			method: 'POST',
			data: { uniqid: postUniqid, userid: userid, account_id: account_id  }, // ここに自分のユーザーIDを指定
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					// いいね成功時の処理
					if (isLiked) {
						$this.removeClass('bookmark_after'); // クラスを削除していいねを取り消す
					} else {
						$this.addClass('bookmark_after'); // クラスを追加していいねを追加する
					}
				} else {
					// いいね失敗時の処理
				}
			}.bind(this), // コールバック内でthisが適切な要素を指すようにbindする
			error: function() {
				// エラー時の処理
			}
		});
	});



	
    
	var modal = document.getElementById('myDelModal');
    var deleteButton = document.getElementById('deleteButton');
    var cancelButton = document.getElementById('cancelButton'); // 追加
	var modalMain = $('.modal-content');

    $(document).on('click', '#delete', function (event) {
        modal.style.display = 'block';
		modalMain.addClass("slideUp");
    	modalMain.removeClass("slideDown");

        var uniqid = $(this).parents().attr('data-uniqid');
		var postElement = $("#ueuse-"+uniqid);

        deleteButton.addEventListener('click', () => {
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal.style.display = 'none';
			}, 150);

            $.ajax({
                url: '../delete/delete.php',
                method: 'POST',
                data: { uniqid: uniqid, userid: userid, account_id: account_id },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        postElement.remove();
                    } else {
                        // 削除失敗時の処理
                    }
                },
                error: function () {
                    // エラー時の処理
                }
            });
        });

        cancelButton.addEventListener('click', () => { // 追加
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal.style.display = 'none';
			}, 150);
        });
    });

	var abimodal = document.getElementById('myAbiModal');
	var AbiAddButton = document.getElementById('AbiAddButton');
	var AbiCancelButton = document.getElementById('AbiCancelButton');
	var modalMain = $('.modal-content');

	$(document).on('click', '.addabi', function (event) {

		abimodal.style.display = 'block';
		modalMain.addClass("slideUp");
		modalMain.removeClass("slideDown");

		var uniqid2 = $(this).attr('data-uniqid2');
		var postAbiElement = $(this).closest('.addabi');

		AbiCancelButton.addEventListener('click', () => {
			modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				abimodal.style.display = 'none';
			}, 150);
		});

		$('#AbiForm').off('submit').on('submit', function (event) {

			event.preventDefault();

			var abitext = document.getElementById("abitexts").value;
			var usernames = '<?php echo $username; ?>';

			if(abitext == ""){
				modalMain.removeClass("slideUp");
				modalMain.addClass("slideDown");
				window.setTimeout(function(){
					abimodal.style.display = 'none';
				}, 150);
			}else{
				$.ajax({
					url: '../abi/addabi.php',
					method: 'POST',
					data: { uniqid: uniqid2, abitext: abitext, userid: userid, account_id: account_id },
					dataType: 'json',
					success: function (response) {
						if (response.success) {
							abimodal.style.display = 'none';
							postAbiElement.remove();
						} else {
							abimodal.style.display = 'none';
							postAbiElement.remove();
						}
					},
					error: function (xhr, status, error) {
						abimodal.style.display = 'none';
						postAbiElement.remove();
					}
				});
			}
		});
	});


	$(document).on('click', '#quote_reuse_btn', function (event) {
		var modalMain = $('.modal-content');
		var reuseModal = $('#myQuoteReuseModal');

		reuseModal.show();
		modalMain.addClass("slideUp");
		modalMain.removeClass("slideDown");

		var uniqid = $(this).parents().attr('data-uniqid');

		$('#ReuseCancelButton').on('click', function (event) {
			modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				reuseModal.hide();
			}, 150);
		});

		$('#ReuseButton').on('click', function (event) {
			event.preventDefault();

			var reusetext = $("#reusetexts").val();

			if(reusetext == ""){
				modalMain.removeClass("slideUp");
				modalMain.addClass("slideDown");
				window.setTimeout(function(){
					reuseModal.hide();
				}, 150);
			}else{
				$.ajax({
					url: '../function/reuse.php',
					method: 'POST',
					data: { uniqid: uniqid, reusetext: reusetext, userid: userid, account_id: account_id},
					dataType: 'json',
					success: function (response) {
						if (response.success) {
							reuseModal.hide();
							view_notify("引用リユーズしました");
						} else {
							reuseModal.hide();
							view_notify("引用リユーズに失敗しました");
						}
					},
					error: function (xhr, status, error) {
						reuseModal.hide();
						view_notify("引用リユーズに失敗しました");
					}
				});
			}
		});
	});

	$(document).on('click', '#normal_reuse_btn', function (event) {
		event.preventDefault();
		var uniqid = $(this).parents().attr('data-uniqid');
		var reusetext = "";
		$.ajax({
			url: '../function/reuse.php',
			method: 'POST',
			data: { uniqid: uniqid, reusetext: reusetext, userid: userid, account_id: account_id},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					view_notify("リユーズしました");
				} else {
					view_notify("リユーズに失敗しました");
				}
			},
			error: function (xhr, status, error) {
				view_notify("リユーズに失敗しました");
			}
		});
	});

	$(document).on('click', '#delete_reuse_btn', function (event) {
		event.preventDefault();
		var uniqid = $(this).parents().attr('data-uniqid');
		var reusetext = "";
		var postElement = $("#ueuse-"+uniqid);
		$.ajax({
			url: '../delete/delete.php',
			method: 'POST',
			data: { uniqid: uniqid, userid: userid, account_id: account_id },
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					postElement.remove();
				} else {
					view_notify("リユーズの取り消しに失敗しました");
				}
			},
			error: function () {
				view_notify("リユーズの取り消しに失敗しました");
			}
		});
	});

	window.addEventListener('online', function(){
		checkOnline();
	});
	window.addEventListener('offline', function(){
		checkOnline();
	});
	function checkOnline() {
		if( navigator.onLine ) {
			$("#online").show();
			$("#offline").hide();
		} else {
			$("#online").hide();
			$("#offline").show();
		}
	}


	$(document).on('click', '#share', function (event) {

		var domain = "<?php echo $domain;?>";
		var share_uniqid = $(this).parents().attr('data-uniqid');
		var share_userid = $(this).parents().attr('data-userid');

		if (typeof navigator.share === 'undefined') {
			navigator.clipboard.writeText("https://"+domain+"/!"+share_uniqid+"")
			$("#clipboard").show();
			window.setTimeout(function(){
				$("#clipboard").hide();
			}, 5000);
            return;
        }

        var shareData = {
            title: ''+share_userid+'さんのID '+share_uniqid+' のユーズ - uwuzu',
        	text: '',
            url: "https://"+domain+"/!"+share_uniqid+"",
        };

		navigator.share(shareData)
		.then(function () {
			// シェア完了後の処理
		})
		.catch(function (error) {
			// シェア失敗時の処理
		});

	});

	var osho_gats = document.getElementById('osho_gats');
	$('#os_exit_btn').on('click', function() {
		document.cookie = "event=done; max-age=86400";
		osho_gats.style.display = 'none';
	});

	var now_time = new Date().toUTCString();
	setInterval(() => {
		$.ajax({
			url: '../nextpage/newueuse_chk.php',
			method: 'POST',
			data: { loading_dt: now_time, userid: userid, account_id: account_id  }, // ここに自分のユーザーIDを指定
			dataType: 'json',
			timeout: 300000,
			success: function(response) {
				if (response.success) {
					$("#new_ueuse").show();
				} else {
					$("#new_ueuse").hide();
				}
			}.bind(this), // コールバック内でthisが適切な要素を指すようにbindする
			error: function(e) {
				$("#new_ueuse").hide();
			}
		});
	}, 60000);

	$(document).on('click', '#reusebtn', function(event) {
		$('#reuse_popup').css({
			left: event.pageX - 80,
			top: event.pageY
		});

		var reusebtncss = $(this).attr('class');
		if(reusebtncss.indexOf('reuse_after') >= 0){
			$("#delete_reuse_btn").show();
		}else{
			$("#delete_reuse_btn").hide();
		}

		$("#reuse_popup").attr('data-uniqid',$(this).attr('data-uniqid'));
		$("#reuse_popup").attr('data-userid',$(this).attr('data-userid'));

		$("#ueuse_popup_back").show();
		$("#reuse_popup").show();
	});

	$(document).on('click', '#popup', function(event) {
		$('#ueuse_popup').css({
			left: event.pageX - 80,
			top: event.pageY
		});

		$("#ueuse_popup").attr('data-uniqid',$(this).attr('data-uniqid'));
		$("#ueuse_popup").attr('data-userid',$(this).attr('data-userid'));

		if(!(userid == $(this).attr('data-userid'))){
			$("#ueuse_popup").children("#delete").hide();
		}else{
			$("#ueuse_popup").children("#delete").show();
		}

		$("#ueuse_popup_back").show();
		$("#ueuse_popup").show();
	});
	$(document).on('click', '#ueuse_popup_back, .popbtn', function(event) {
		$('#ueuse_popup').addClass("bye");
		$('#reuse_popup').addClass("bye");

		setTimeout(function(){
			$("#ueuse_popup_back").hide();
			$('#ueuse_popup').hide();
			$('#reuse_popup').hide();

			$('#ueuse_popup').removeClass("bye");
			$('#reuse_popup').removeClass("bye");
		}, 250);
	});

	//----------------------------------------------------------------------------------------------------------------------
	//-------------------------------------------------------send_box-------------------------------------------------------
	//----------------------------------------------------------------------------------------------------------------------
	document.getElementById("upload_videos1").addEventListener('change', function(e){
		var file_reader = new FileReader();
		// ファイルの読み込みを行ったら実行
		file_reader.addEventListener('load', function(e) {
			$('#videos1').addClass('label_set');
		});
		file_reader.readAsText(e.target.files[0]);
	});
	document.getElementById("upload_images4").addEventListener('change', function(e){
		var file_reader = new FileReader();
		// ファイルの読み込みを行ったら実行
		file_reader.addEventListener('load', function(e) {
			$('#images4').addClass('label_set');
		});
		file_reader.readAsText(e.target.files[0]);
	});

	document.getElementById("upload_images3").addEventListener('change', function(e){
		var file_reader = new FileReader();
		// ファイルの読み込みを行ったら実行
		file_reader.addEventListener('load', function(e) {
			$('#images3').addClass('label_set');
			$("#images4").show();
		});
		file_reader.readAsText(e.target.files[0]);
	});

	document.getElementById("upload_images2").addEventListener('change', function(e){
		var file_reader = new FileReader();
		// ファイルの読み込みを行ったら実行
		file_reader.addEventListener('load', function(e) {
			$('#images2').addClass('label_set');
			$("#images3").show();
		});
		file_reader.readAsText(e.target.files[0]);
	});
	document.getElementById("upload_images").addEventListener('change', function(e){
		var file_reader = new FileReader();
		// ファイルの読み込みを行ったら実行
		file_reader.addEventListener('load', function(e) {
			$('#images').addClass('label_set');
			$("#images2").show();
		});
		file_reader.readAsText(e.target.files[0]);
	});

	var cnt = 0;

	$(document).on('paste', function(event) {
		if(cnt < 4){
			var clipboardData = event.originalEvent.clipboardData || window.clipboardData;
			if (clipboardData && clipboardData.items) {
				for (var i = 0; i < clipboardData.items.length; i++) {
					var item = clipboardData.items[i];
					if (item.type.indexOf("image") !== -1 && cnt <= 4) {
						var file = item.getAsFile();
						var fileInput;

						if (cnt === 0) {
							fileInput = $('#upload_images');
						} else {
							fileInput = $('#upload_images' + (cnt + 1));
						}

						// FileListオブジェクトを再生成して、Fileオブジェクトを追加
						var dataTransfer = new DataTransfer();
						dataTransfer.items.add(file);
						fileInput[0].files = dataTransfer.files;

						if(fileInput[0].files){
							if (cnt === 0) {
								$('#images').addClass('label_set');
								$("#images2").show();
							} else {
								$('#images' + (cnt + 1)).addClass('label_set');
								$("#images" + (cnt + 2)).show();
							}
						}

						cnt++;
					}
				}
			}
		}
    });

	$('#ueuse').on('input', function () {
		var mojisize = '<?php echo $mojisize; ?>';
		var mojicount = Number(mojisize) - $(this).val().length;
		if(mojicount >= 0){
			$('#moji_cnt').removeClass('red');
			$('#moji_cnt').html(mojicount);
			$('#ueusebtn').prop('disabled', false);
		}else{
			$('#moji_cnt').addClass('red');
			$('#moji_cnt').html(mojicount);
			$('#ueusebtn').prop('disabled', true);
		}
		document.cookie = "ueuse=" + encodeURIComponent($(this).val()) + "; Secure; SameSite=Lax; path=/home;";
	});
	loadEmojis();

	$("#emoji_picker_btn").click(function () {
		if ($("#emoji_picker_btn").prop("checked") == true) {
			$("#emoji_picker").show();
		} else {
			$("#emoji_picker").hide();
		}
	});
	$('.emoji_picker').on('scroll', function() {
		var innerHeight = $('.emoji_picker_flex').innerHeight(),
			outerHeight = $('.emoji_picker').innerHeight(),
			outerBottom = innerHeight - outerHeight;
		if (outerBottom <= $('.emoji_picker').scrollTop()) {
			if ($('#noemoji').length){
				return;
			} else {
				loadEmojis();
			}
		}
	});
	var Emoji_pageNumber = 1;
	var isEmojiLoading = false;
	function loadEmojis() {

		if (isEmojiLoading) return;
		isEmojiLoading = true;

		var search_query = '';
		var viewmode = 'picker'
		$.ajax({
			url: '../nextpage/emojiview.php', // PHPファイルへのパス
			method: 'GET',
			data: { page: Emoji_pageNumber, userid: userid , account_id: account_id , search_query: search_query, view_mode: viewmode},
			dataType: 'html',
			timeout: 300000,
			success: function(response) {
				$('.emoji_picker_flex').append(response);
				Emoji_pageNumber++;
				isEmojiLoading = false;
				if($("#error").length){
					$("#error").hide();
				}
				
				EmojiClickEvent();
			},
			error: function (xhr, textStatus, errorThrown) {  // エラーと判定された場合
				isEmojiLoading = false;
				$("#error").show();
				EmojiClickEvent();
			},
		});
	}
	function EmojiClickEvent() {
		$(".one_emoji").click(function (event) {
			event.preventDefault();
			var children = $(this).children("img");
			var custom_emojiname = children.attr("title");
			$("#ueuse").val($("#ueuse").val() + custom_emojiname);
		});
	}
});
</script>
</html>