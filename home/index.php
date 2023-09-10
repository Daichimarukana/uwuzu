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

session_name('uwuzu_s_id');
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

if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true) {

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,admin,role FROM account WHERE userid = :userid");
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
	$role = $res["role"];
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

	$passQuery = $pdo->prepare("SELECT username,userid,loginid,admin,role FROM account WHERE userid = :userid");
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
	$role = $res["role"];
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

$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

//-------------------------------------------
function get_mentions_userid($postText) {
    // @useridを検出する
    $usernamePattern = '/@(\w+)/';
    $mentionedUsers = [];

    preg_replace_callback($usernamePattern, function($matches) use (&$mentionedUsers) {
        $mention_username = $matches[1];

        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));
    
        $mention_userQuery = $dbh->prepare("SELECT username, userid FROM account WHERE userid = :userid");
        $mention_userQuery->bindValue(':userid', $mention_username);
        $mention_userQuery->execute();
        $mention_userData = $mention_userQuery->fetch();   
        
        if (!empty($mention_userData)) {
            $mentionedUsers[] = $mention_username;
        }
    }, $postText);

    return $mentionedUsers;
}

if( !empty($_POST['btn_submit']) ) {

	$ueuse = htmlentities($_POST['ueuse']);

	// メッセージの入力チェック
	if( empty($ueuse) ) {
		$error_message[] = '内容を入力してください。';
	} else {
        // 文字数を確認
        if( 1024 < mb_strlen($ueuse, 'UTF-8') ) {
			$error_message[] = '内容は1024文字以内で入力してください。';
		}
    }


	if (empty($_FILES['upload_images']['name'])) {
		$photo1 = "none";
	} else {
		// アップロードされたファイル情報
		$uploadedFile = $_FILES['upload_images'];

		// アップロードされたファイルの拡張子を取得
		$extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
		
		// 新しいファイル名を生成（uniqid + 拡張子）
		$newFilename = uniqid() . '-'.$userid.'.' . $extension;
		
		// 保存先のパスを生成
		$uploadedPath = '../ueuseimages/' . $newFilename;
		
		// ファイルを移動
		$result = move_uploaded_file($uploadedFile['tmp_name'], $uploadedPath);
		
		if ($result) {
			$photo1 = $uploadedPath; // 保存されたファイルのパスを使用
		} else {
			$errnum = $uploadedFile['error'];
			if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
			if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
			if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
			if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
			if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
			if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
			if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
			$error_message[] = 'アップロード失敗！(2)エラーコード：' .$errcode.'';
		}
	}

	if (empty($_FILES['upload_images2']['name'])) {
		$photo2 = "none";
	} else {

		if (empty($_FILES['upload_images']['name'])){
			$error_message[] = '画像1から画像を選択してください！！！';
		}
		// アップロードされたファイル情報
		$uploadedFile2 = $_FILES['upload_images2'];

		if( 10000000 < $uploadedFile2["size"] ) {
			$error_message[] = 'ファイルサイズが大きすぎます！';
		}
		// アップロードされたファイルの拡張子を取得
		$extension2 = pathinfo($uploadedFile2['name'], PATHINFO_EXTENSION);
		
		// 新しいファイル名を生成（uniqid + 拡張子）
		$newFilename2 = uniqid() . '-'.$userid.'.' . $extension2;
		
		// 保存先のパスを生成
		$uploadedPath2 = '../ueuseimages/' . $newFilename2;
		
		// ファイルを移動
		$result2 = move_uploaded_file($uploadedFile2['tmp_name'], $uploadedPath2);
		
		if ($result2) {
			$photo2 = $uploadedPath2; // 保存されたファイルのパスを使用
		} else {
			$errnum = $uploadedFile2['error'];
			if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
			if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
			if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
			if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
			if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
			if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
			if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
			$error_message[] = 'アップロード失敗！(2)エラーコード：' .$errcode.'';
		}
	}

	if (empty($_FILES['upload_videos1']['name'])) {
		$video1 = "none";
	} else {
		// アップロードされたファイル情報
		$uploadedFile3 = $_FILES['upload_videos1'];
		
		// アップロードされたファイルの拡張子を取得
		$extension3 = strtolower(pathinfo($uploadedFile3['name'], PATHINFO_EXTENSION)); // 小文字に変換

		// サポートされている動画フォーマットの拡張子を配列で定義
		$supportedExtensions = array("mp4", "avi", "mov", "webm");

		if (in_array($extension3, $supportedExtensions)) {
			// 正しい拡張子の場合、新しいファイル名を生成
			$newFilename3 = uniqid() . '-'.$userid.'.' . $extension3;
			// 保存先のパスを生成
			$uploadedPath3 = '../ueusevideos/' . $newFilename3;
		
			// ファイルを移動
			$result3 = move_uploaded_file($uploadedFile3['tmp_name'], $uploadedPath3);
		
			if ($result3) {
				$video1 = $uploadedPath3; // 保存されたファイルのパスを使用
			} else {
				$errnum = $uploadedFile3['error'];
				if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
				if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
				if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
				if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
				if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
				if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
				if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
				$error_message[] = 'アップロード失敗！(2)エラーコード：' .$errcode.'';
			}
		} else {
			$error_message[] = '対応していないファイル形式です！';
		}
		
		
	}

	if( empty($error_message) ) {
		
		    // 書き込み日時を取得
            $datetime = date("Y-m-d H:i:s");
			$uniqid = createUniqId();
			$abi = "none";

            // トランザクション開始
            $pdo->beginTransaction();

            try {

                // SQL作成
                $stmt = $pdo->prepare("INSERT INTO ueuse (username, account, uniqid, ueuse, photo1, photo2, video1, datetime, abi) VALUES (:username, :account, :uniqid, :ueuse, :photo1, :photo2, :video1, :datetime, :abi)");
        
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':account', $userid, PDO::PARAM_STR);
				$stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
                $stmt->bindParam(':ueuse', $ueuse, PDO::PARAM_STR);

				$stmt->bindParam(':photo1', $photo1, PDO::PARAM_STR);
				$stmt->bindParam(':photo2', $photo2, PDO::PARAM_STR);
				$stmt->bindParam(':video1', $video1, PDO::PARAM_STR);
                $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

				$stmt->bindParam(':abi', $abi, PDO::PARAM_STR);

                // SQLクエリの実行
                $res = $stmt->execute();

                // コミット
                $res = $pdo->commit();

				$mentionedUsers = get_mentions_userid($ueuse);

				foreach ($mentionedUsers as $mentionedUser) {
				
					$pdo->beginTransaction();

					try {
						$touserid = $mentionedUser;
						$datetime = date("Y-m-d H:i:s");
						$msg = "" . $ueuse . "";
						$title = "" . $username . "さんにメンションされました！";
						$url = "/!" . $uniqid . "~" . $userid . "";
						$userchk = 'none';

						// 通知用SQL作成
						$stmt = $pdo->prepare("INSERT INTO notification (touserid, msg, url, datetime, userchk, title) VALUES (:touserid, :msg, :url, :datetime, :userchk, :title)");


						$stmt->bindParam(':touserid', $touserid, PDO::PARAM_STR);
						$stmt->bindParam(':msg', $msg, PDO::PARAM_STR);
						$stmt->bindParam(':url', $url, PDO::PARAM_STR);
						$stmt->bindParam(':userchk', $userchk, PDO::PARAM_STR);
						$stmt->bindParam(':title', $title, PDO::PARAM_STR);

						$stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

						// SQLクエリの実行
						$res = $stmt->execute();

						// コミット
						$res = $pdo->commit();

					} catch(Exception $e) {

						// エラーが発生した時はロールバック
						$pdo->rollBack();
					}
			
				}

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



require('../logout/logout.php');



// データベースの接続を閉じる
$pdo = null;

if(isset($_GET['text'])){ 
	$ueuse = $_GET['text'];
}else{
	$ueuse = "";
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
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
<title>ローカルタイムライン - <?php echo file_get_contents($servernamefile);?></title>

</head>

<body>

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
					<button class="os_exit_btn">とじる</button>
				</div>
			</div>
			<?php }?>
		<?php }?>
		<div class="tlchange">
				<a href="index" class="on">LTL</a>
				<a href="ftl" class="off">FTL</a>
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
					<textarea id="ueuse" placeholder="いまどうしてる？" name="ueuse"><?php if( !empty($ueuse) ){ echo htmlspecialchars($ueuse, ENT_QUOTES, 'UTF-8'); } ?></textarea>
					<p>画像のEXIF情報(位置情報など)は削除されません。<br>情報漏洩に気をつけてくださいね…</p>
					<div class="fxbox">
						<label for="upload_images" id="images">
						<img src="../img/sysimage/image_1.svg">
						<input type="file" name="upload_images" id ="upload_images" accept="image/*">
						</label>
						<label for="upload_images2" id="images2">
						<img src="../img/sysimage/image_1.svg">
						<input type="file" name="upload_images2" id ="upload_images2" accept="image/*">
						</label>
						<label for="upload_videos1" id="videos1">
						<img src="../img/sysimage/video_1.svg">
						<input type="file" name="upload_videos1" id ="upload_videos1" accept="video/*">
						</label>

						<input type="submit" class="ueusebtn" name="btn_submit" value="ユーズする">
					</div>
				</div>
			</form>
		<?php }?>
		<script>
			document.getElementById("upload_videos1").addEventListener('change', function(e){
				var file_reader = new FileReader();
				// ファイルの読み込みを行ったら実行
				file_reader.addEventListener('load', function(e) {
				console.log(e.target.result);
					const element = document.querySelector('#videos1');
					const createElement = '<p>動画を選択しました。</p>';
					element.insertAdjacentHTML('afterend', createElement);
				});
				file_reader.readAsText(e.target.files[0]);
			});
			document.getElementById("upload_images2").addEventListener('change', function(e){
			var file_reader = new FileReader();
			// ファイルの読み込みを行ったら実行
			file_reader.addEventListener('load', function(e) {
			console.log(e.target.result);
				const element = document.querySelector('#images2');
				const createElement = '<p>画像を選択しました。</p>';
				element.insertAdjacentHTML('afterend', createElement);
			});
			file_reader.readAsText(e.target.files[0]);
			});
			document.getElementById("upload_images").addEventListener('change', function(e){
			var file_reader = new FileReader();
			// ファイルの読み込みを行ったら実行
			file_reader.addEventListener('load', function(e) {
			console.log(e.target.result);
				const element = document.querySelector('#images');
				const createElement = '<p>画像を選択しました。</p>';
				element.insertAdjacentHTML('afterend', createElement);
			});
			file_reader.readAsText(e.target.files[0]);
			});
		</script>

		<section class="inner">
			<div id="postContainer">
				

			</div>
		</section>

		<div id="loading" class="loading" style="display: none;">
			🤔
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
			<textarea id="abitexts" placeholder="なに追記する～？" name="abi"><?php if( !empty($_SESSION['abi']) ){ echo htmlspecialchars( $_SESSION['abi'], ENT_QUOTES, 'UTF-8'); } ?></textarea>
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

	loadPosts();

    var pageNumber = 1;
    var isLoading = false;

    function loadPosts() {
        if (isLoading) return;
        isLoading = true;
		$("#loading").show();
		var userid = '<?php echo $userid; ?>';
        $.ajax({
            url: '../nextpage/nextpage.php', // PHPファイルへのパス
            method: 'GET',
            data: { page: pageNumber, userid: userid },
            dataType: 'html',
            success: function(response) {
                $('#postContainer').append(response);
                pageNumber++;
                isLoading = false;
				$("#loading").hide();
            }
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
		var userid = '<?php echo $userid; ?>';
		var account_id = '<?php echo $loginid; ?>';
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
	var modalMain = $('.modal-content');

    $(document).on('click', '.delbtn', function (event) {
        modal.style.display = 'block';
		modalMain.addClass("slideUp");
    	modalMain.removeClass("slideDown");

        var uniqid2 = $(this).attr('data-uniqid2');
		var userid = '<?php echo $userid; ?>';
		var account_id = '<?php echo $loginid; ?>';
		var postElement = $(this).closest('.ueuse');

        deleteButton.addEventListener('click', () => {
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				modal.style.display = 'none';
			}, 150);

            $.ajax({
                url: '../delete/delete.php',
                method: 'POST',
                data: { uniqid: uniqid2, userid: userid, account_id: account_id },
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
			var userid = '<?php echo $userid; ?>';
			var account_id = '<?php echo $loginid; ?>';

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
						console.log(response); // レスポンス内容をコンソールに表示
						if (response.success) {
							abimodal.style.display = 'none';
							postAbiElement.remove();
							console.log(response);
						} else {
							abimodal.style.display = 'none';
							postAbiElement.remove();
						}
					},
					error: function (xhr, status, error) {
						console.log(error);
						abimodal.style.display = 'none';
						postAbiElement.remove();
					}
				});
			}
		});
	});

	var osho_gats = document.getElementById('osho_gats');
	$(document).on('click', '.os_exit_btn', function (event) {
		document.cookie = "event=done; max-age=86400";
		osho_gats.style.display = 'none';
	});

});

</script>
</html>