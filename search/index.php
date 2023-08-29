<?php
$servernamefile = "../server/servername.txt";

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

session_start();

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

if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,admin FROM account WHERE userid = :userid");
	$passQuery->bindValue(':userid', $_SESSION['userid']);
	$passQuery->execute();
	$res = $passQuery->fetch();
	if(empty($res["userid"])){
		header("Location: ../login.php");
		exit;
	}elseif($_SESSION['loginid'] === $res["loginid"]){
	// セッションに値をセット
	$userid = $_SESSION['userid']; // セッションに格納されている値をそのままセット
	$username = $_SESSION['username']; // セッションに格納されている値をそのままセット
	$_SESSION['admin_login'] = true;
	$_SESSION['userid'] = $userid;
	$_SESSION['username'] = $username;
	$_SESSION['loginid'] = $res["loginid"];
	setcookie('userid', $userid, time() + 60 * 60 * 24 * 14);
	setcookie('username', $username, time() + 60 * 60 * 24 * 14);
	setcookie('loginid', $res["loginid"], time() + 60 * 60 * 24 * 14);
	setcookie('admin_login', true, time() + 60 * 60 * 24 * 14);
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
	}elseif($_COOKIE['loginid'] === $res["loginid"]){
	// セッションに値をセット
	$userid = $_COOKIE['userid']; // クッキーから取得した値をセット
	$username = $_COOKIE['username']; // クッキーから取得した値をセット
	$_SESSION['admin_login'] = true;
	$_SESSION['userid'] = $userid;
	$_SESSION['username'] = $username;
	$_SESSION['loginid'] = $res["loginid"];
	setcookie('userid', $userid, time() + 60 * 60 * 24 * 14);
	setcookie('username', $username, time() + 60 * 60 * 24 * 14);
	setcookie('loginid', $res["loginid"], time() + 60 * 60 * 24 * 14);
	setcookie('admin_login', true, time() + 60 * 60 * 24 * 14);
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

if(isset($_GET['q'])){ 
	$keyword = htmlentities($_GET['q']);
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
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<link rel="stylesheet" href="../css/home.css">
<title>検索 - <?php echo file_get_contents($servernamefile);?></title>

</head>

<body>

	<?php require('../require/leftbox.php');?>
	
	<main class="outer">

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
			<input class="inbox" placeholder="ユーズ検索" id="ueusetext" type="text" value="<?php if( !empty($keyword) ){ echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); } ?>">
			<button class="search_btn" id="search_btn">検索</button>
		</div>

		<section class="inner">
			<div id="postContainer">
				

			</div>
		</section>

		<div id="loading" class="loading" style="display: none;">
			🤔
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
			<textarea id="abitexts" placeholder="なに追記する～？" name="abi"><?php if( !empty($_SESSION['abi']) ){ echo htmlentities( $_SESSION['abi'], ENT_QUOTES, 'UTF-8'); } ?></textarea>
			<div class="btn_area">
				<input type="submit" id="AbiAddButton" class="fbtn_no" name="abi" value="追記">
				<input type="button" id="AbiCancelButton" class="fbtn" value="キャンセル">
			</div>
			</form>
		</div>
	</div>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>

</body>

<script>
$(document).ready(function() {
	
	if(ueusetext.value){
		loadPosts();
	}

	$(document).on('click', '.search_btn', function(event) {
		loadPosts();
	});

	var isLoading = false;

	function loadPosts() {
		if (isLoading) return;
		isLoading = true;
		$("#loading").show();
		
		var ueusetext = document.getElementById('ueusetext');
		var userid = '<?php echo $userid; ?>';
		var keyword = ueusetext.value;
		
		// 前回の検索結果をクリア
		$('#postContainer').empty();

		console.log(keyword)
		
		// 新しいキーワードで検索を実行
		$.ajax({
			url: '../nextpage/searchpage.php', // PHPファイルへのパス
			method: 'GET',
			data: { keyword: keyword, userid: userid },
			dataType: 'html',
			success: function(response) {
				$('#postContainer').append(response);
				$("#loading").hide();
				isLoading = false;
			}
		});
	}



	$(document).on('click', '.favbtn, .favbtn_after', function(event) {

		event.preventDefault();

		var postUniqid = $(this).data('uniqid');
		var userid = '<?php echo $userid; ?>';
		var likeCountElement = $(this).find('.like-count'); // いいね数を表示する要素

		var isLiked = $(this).hasClass('favbtn_after'); // 現在のいいねの状態を判定

		var $this = $(this); // ボタン要素を変数に格納

		$.ajax({
			url: '../favorite/favorite.php',
			method: 'POST',
			data: { uniqid: postUniqid, userid: userid }, // ここに自分のユーザーIDを指定
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					// いいね成功時の処理
					if (isLiked) {
						$this.removeClass('favbtn_after'); // クラスを削除していいねを取り消す
						$this.find('img').attr('src', '../img/sysimage/favorite_1.svg'); // 画像を元の画像に戻す
					} else {
						$this.addClass('favbtn_after'); // クラスを追加していいねを追加する
						$this.find('img').attr('src', '../img/sysimage/favorite_2.svg'); // 画像を新しい画像に置き換える
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



	
    
	var modal = document.getElementById('myDelModal');
    var deleteButton = document.getElementById('deleteButton');
    var cancelButton = document.getElementById('cancelButton'); // 追加

    $(document).on('click', '.delbtn', function (event) {
        modal.style.display = 'block';

        var uniqid2 = $(this).attr('data-uniqid2');
		var postElement = $(this).closest('.ueuse');

        deleteButton.addEventListener('click', () => {
            modal.style.display = 'none';

            $.ajax({
                url: '../delete/delete.php',
                method: 'POST',
                data: { uniqid: uniqid2 },
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
            modal.style.display = 'none';
        });
    });



	var abimodal = document.getElementById('myAbiModal');
	var AbiAddButton = document.getElementById('AbiAddButton');
	var AbiCancelButton = document.getElementById('AbiCancelButton');

	$(document).on('click', '.addabi', function (event) {

		abimodal.style.display = 'block';

		var uniqid2 = $(this).attr('data-uniqid2');
		var postAbiElement = $(this).closest('.addabi');

		AbiCancelButton.addEventListener('click', () => {
			abimodal.style.display = 'none';
		});

		$('#AbiForm').off('submit').on('submit', function (event) {

			event.preventDefault();

			var abitext = document.getElementById("abitexts").value;

			if(abitext == ""){
				abimodal.style.display = 'none';
			}else{
				$.ajax({
					url: '../abi/addabi.php',
					method: 'POST',
					data: { uniqid: uniqid2, abitext: abitext},
					dataType: 'json',
					success: function (response) {
						console.log(response); // レスポンス内容をコンソールに表示
						if (response.success) {
							abimodal.style.display = 'none';
							postAbiElement.remove();

						} else {

						}
					},
					error: function (xhr, status, error) {

					}
				});
			}
		});
	});


});

</script>
</html>