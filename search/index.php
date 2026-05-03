<?php
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

require('../db.php');
require("../function/function.php");

$domain = $_SERVER['HTTP_HOST'];

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

if(isset($_GET['q'])){ 
	$keyword = safetext($_GET['q']);
}else{
	$keyword = "";
}


$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

//-------------------------------------------

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
<script src="../js/view_function.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<link rel="stylesheet" href="../css/home.css">
<link rel="search" href="<?php echo safetext((empty($_SERVER['HTTPS']) ? 'http://' : 'https://').$_SERVER['HTTP_HOST']."/search/opensearch.php")?>" title="<?php echo safetext($serversettings["serverinfo"]["server_name"]);?>" type="application/opensearchdescription+xml">
<title>検索 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

</head>

<body>
	<?php require('../require/leftbox.php');?>
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
	<main class="outer">
		<div>
			<div id="clipboard" class="online" style="display:none;">
				<p>🗒️📎 ユーズのURLをコピーしました！</p>
			</div>
		</div>

		<?php if( !empty($error_message) ): ?>
			<ul class="errmsg">
				<?php foreach( $error_message as $value ): ?>
					<p>・ <?php echo $value; ?></p>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		
		
		<div class="emojibox">
		<h1>検索</h1>
		</div>
		<div class="sendbox">
			<input class="inbox" placeholder="ユーズ検索" id="ueusetext" type="text" value="<?php if( !empty($keyword) ){ echo safetext($keyword); } ?>">
			<button class="search_btn" id="search_btn">検索</button>
		</div>

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
			<textarea id="abitexts" placeholder="なに追記する～？" name="abi"><?php if( !empty($_SESSION['abi']) ){ echo safetext( $_SESSION['abi']); } ?></textarea>
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
			<div id="NoAI_Footer" class="warning-footer"><span>No AI</span>機械学習への利用を一切拒否します。</div>
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
	view_ueuse_init(userid, account_id);
	var pageNumber = 1;
	var isLoading = false;

	if(ueusetext.value){
		$('#postContainer').empty();
		pageNumber = 1;
		loadPosts();
	}

	$(document).on('click', '.search_btn', function(event) {
		$('#postContainer').empty();
		pageNumber = 1;
		loadPosts();
	});

    window.document.onkeydown = function(event){
        if (event.key === 'Enter') {
			$('#postContainer').empty();
			pageNumber = 1;
            loadPosts();
        }
    }

	function loadPosts() {
		if (isLoading) return;
		isLoading = true;
		$("#loading").show();

		var ueusetext = document.getElementById('ueusetext');
		var keyword = ueusetext.value;
		
		// 新しいキーワードで検索を実行
		$.ajax({
			url: '../nextpage/searchtimeline.php',
			method: 'POST',
			data: { page: pageNumber, userid: userid, account_id: account_id, keyword: keyword },
			dataType: 'json',
			timeout: 300000,
			success: function(response) {
				if(renderUeuses(response)){
					pageNumber++;
					isLoading = false;
					$("#loading").hide();
				}else{
					isLoading = false;
					$("#loading").hide();
					$("#error").show();
				}
			},
			error: function(xhr, textStatus, errorThrown) {
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
						view_notify("ブックマークを解除しました");
					} else {
						$this.addClass('bookmark_after'); // クラスを追加していいねを追加する
						view_notify("ユーズをブックマークしました！");
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
					data: { uniqid: uniqid2, abitext: abitext, username: usernames, userid: userid, account_id: account_id },
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

		$('#ReuseCancelButton').off('click').on('click', function (event) { 
			modalMain.removeClass("slideUp"); 
			modalMain.addClass("slideDown"); 
			window.setTimeout(function(){ 
				reuseModal.hide(); 
			}, 150); 
		}); 

		$('#ReuseButton').off('click').on('click', function (event) {  // ここを修正
			event.preventDefault(); 

			var reusetext = $("#reusetexts").val(); 

			if (reusetext == "") { 
				modalMain.removeClass("slideUp"); 
				modalMain.addClass("slideDown"); 
				window.setTimeout(function(){ 
					reuseModal.hide(); 
				}, 150); 
			} else { 
				$.ajax({ 
					url: '../function/reuse.php', 
					method: 'POST', 
					data: { uniqid: uniqid, reusetext: reusetext, userid: userid, account_id: account_id }, 
					dataType: 'json', 
					success: function (response) { 
						reuseModal.hide(); 
						if (response.success) { 
							view_notify("引用リユーズしました"); 
						} else { 
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
});

</script>
</html>