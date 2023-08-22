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

$userid = htmlentities($_SESSION['userid']);
$username = htmlentities($_SESSION['username']);


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
$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

function customStripTags($html, $allowedTags) {
    $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
    return strip_tags($html, $allowedTagsString);
}

$allowedTags = array('h1', 'h2', 'h3', 'center', 'font');

if( !empty($pdo) ) {
	
	// データベース接続の設定
	$dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	));

	$uwuzuid = htmlentities(str_replace('@', '', $_GET['uwuzuid']));

	// ユーズ内の絵文字を画像に置き換える
	function replaceEmojisWithImages($postText) {
		// ユーズ内で絵文字名（:emoji:）を検出して画像に置き換える
		$pattern = '/:(\w+):/';
		$postTextWithImages = preg_replace_callback($pattern, function($matches) {
			$emojiName = $matches[1];
			return "<img src='../emoji/emojiimage.php?emoji=" . urlencode($emojiName) . "' alt='$emojiName'>";
		}, $postText);
		return $postTextWithImages;
	}

	function replaceURLsWithLinks($postText) {
		// URLを正規表現を使って検出
		$pattern = '/(https?:\/\/[^\s]+)/';
		preg_match_all($pattern, $postText, $matches);
	
		// 検出したURLごとに処理を行う
		foreach ($matches[0] as $url) {
			// ドメイン部分を抽出
			$parsedUrl = parse_url($url);
			$domain = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
	
			// ドメインのみを表示するaタグを生成
			$link = "<a href='$url'>$domain</a>";
	
			// URLをドメインのみを表示するaタグで置き換え
			$postText = str_replace($url, $link, $postText);
		}
	
		return $postText;
	}

	$userQuery = $dbh->prepare("SELECT username, userid, profile, role, follower FROM account WHERE userid = :userid");
	$userQuery->bindValue(':userid', $uwuzuid);
	$userQuery->execute();
	$userData = $userQuery->fetch();


	if(!empty($userData["userid"])){

	
		$roles = explode(',', $userData["role"]); // カンマで区切られたロールを配列に分割

		$rerole = $dbh->prepare("SELECT  follow, follower, username, userid, password, mailadds, profile, iconname, iconcontent, icontype, iconsize, headname, headcontent, headtype, headsize, role, datetime FROM account WHERE userid = :userid");

		$rerole->bindValue(':userid', $uwuzuid);
		// SQL実行
		$rerole->execute();

		$userdata = $rerole->fetch(); // ここでデータベースから取得した値を $role に代入する
		
		$roleDataArray = array();
		
		foreach ($roles as $roleId) {
			$rerole = $dbh->prepare("SELECT rolename, roleauth, rolecolor FROM role WHERE roleidname = :role");
			$rerole->bindValue(':role', $roleId);
			$rerole->execute();
			$roleDataArray[$roleId] = $rerole->fetch();
		}
		

		//-------フォロー数---------
		$follow = $userdata['follow']; // コンマで区切られたユーザーIDを含む変数

		// コンマで区切って配列に分割し、要素数を数える
		$followIds = explode(',', $follow);
		$followCount = count($followIds)-1;
		
		$follow_on_me = strpos($follow, $userid);
		if ($follow_on_me !== false) {
			$follow_yes = "フォローされています"; // worldを含む:6
		}else{
			$follow_yes = ""; // worldを含む:6
		}

		//-------フォロワー数---------
		$follower = $userdata['follower']; // コンマで区切られたユーザーIDを含む変数

		// コンマで区切って配列に分割し、要素数を数える
		$followerIds = explode(',', $follower);
		$followerCount = count($followerIds)-1;

		$profileText = htmlentities($userData['profile'], ENT_QUOTES, 'UTF-8');

	}else{
		$userData["userid"] = "none";
		$userData['username'] = "ゆーざーなし";
	}
}

if (!empty($_POST['follow'])) {
    // フォローボタンが押された場合の処理
    $followerList = explode(',', $userdata['follower']);
    if (!in_array($userid, $followerList)) {
        // 自分が相手をフォローしていない場合、相手のfollowerカラムと自分のfollowカラムを更新
        $followerList[] = $userid;
        $newFollowerList = implode(',', $followerList);

        // UPDATE文を実行してフォロー情報を更新
        $updateQuery = $pdo->prepare("UPDATE account SET follower = :follower WHERE userid = :userid");
        $updateQuery->bindValue(':follower', $newFollowerList, PDO::PARAM_STR);
        $updateQuery->bindValue(':userid', $userData['userid'], PDO::PARAM_STR);
        $res = $updateQuery->execute();

        // 自分のfollowカラムを更新
        $updateQuery = $pdo->prepare("UPDATE account SET follow = CONCAT_WS(',', follow, :follow) WHERE userid = :userid");
        $updateQuery->bindValue(':follow', $userData["userid"], PDO::PARAM_STR);
        $updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
        $res_follow = $updateQuery->execute();

        if ($res && $res_follow) {
            $url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location:" . $url);
            exit;
        } else {
            $error_message[] = '更新に失敗しました。';
        }

        $stmt = null;
    }
} elseif (!empty($_POST['unfollow'])) {
	// フォロー解除ボタンが押された場合の処理
    $followerList = explode(',', $userdata['follower']);
    if (in_array($userid, $followerList)) {
        // 自分が相手をフォローしている場合、相手のfollowerカラムと自分のfollowカラムを更新
        $followerList = array_diff($followerList, array($userid));
        $newFollowerList = implode(',', $followerList);

        // UPDATE文を実行してフォロー情報を更新
        $updateQuery = $pdo->prepare("UPDATE account SET follower = :follower WHERE userid = :userid");
        $updateQuery->bindValue(':follower', $newFollowerList, PDO::PARAM_STR);
        $updateQuery->bindValue(':userid', $userData['userid'], PDO::PARAM_STR);
        $res = $updateQuery->execute();

		$deluserid = ",".$userdata["userid"];
        // 自分のfollowカラムから相手のユーザーIDを削除
        $updateQuery = $pdo->prepare("UPDATE account SET follow = REPLACE(follow, :follow, '') WHERE userid = :userid");
        $updateQuery->bindValue(':follow', $deluserid, PDO::PARAM_STR);
        $updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
        $res_follow = $updateQuery->execute();

        if ($res && $res_follow) {
            $url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location:" . $url);
            exit;
        } else {
            $error_message[] = '更新に失敗しました。';
        }

        $stmt = null;
    }
}



require('../logout/logout.php');



// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/home.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title><?php echo htmlentities($userData['username'], ENT_QUOTES, 'UTF-8'); ?> さんのプロフィール - <?php echo file_get_contents($servernamefile);?></title>

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

		<div class="userheader">
			<?php if($userData["userid"] == "none"){?>
				<div class="tokonone" id="noueuse"><p>このユーザーは存在しません</p></div>
			<?php }else{?>
			<div class="hed">
				<img src="../user/headimage.php?account=<?php echo urlencode($userData['userid']); ?>">
			</div>
			<div class="icon">
				<img src="../home/tlimage.php?account=<?php echo urlencode($userData['userid']); ?>">
				<h2><?php echo htmlentities($userData['username'], ENT_QUOTES, 'UTF-8'); ?></h2>
				<p>@<?php echo htmlentities($userData['userid'], ENT_QUOTES, 'UTF-8'); ?></p>
			</div>

			<div class="roleboxes">
				<?php foreach ($roles as $roleId): ?>
					<?php $roleData = $roleDataArray[$roleId]; ?>
					<div class="rolebox" style="border: 1px solid <?php echo '#' . $roleData["rolecolor"]; ?>;">
						<p style="color: <?php echo '#' . $roleData["rolecolor"]; ?>;">
							<?php if (!empty($roleData["rolename"])) { echo htmlentities($roleData["rolename"], ENT_QUOTES, 'UTF-8'); } ?>
						</p>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="profile">
				<p><?php echo replaceEmojisWithImages(replaceURLsWithLinks(nl2br($profileText))); ?></p>
			</div>
			
		</div>
		<div class="fzone">
			<div class="time">
				<p><?php echo date('Y年m月d日 H:i:s', strtotime($userdata['datetime'])); ?>からuwuzuを利用しています。</p>
				<p>フォロー数:<?php echo $followCount;?> フォロワー数:<?php echo $followerCount;?></p>
			</div>
			<?php if(!empty($follow_yes)){?>
				<div class="follow_yes">
					<p><?php echo $follow_yes;?></p>
				</div>
			<?php }?>
			<?php if ($userData['userid'] == $userid) { ?>
				<div class="follow">
					<a href="../settings/" class="fbtn_no">設定</a>
				</div>
			<?php } else { ?>
				<form method="post">
					<div class="follow">
						<?php
						$followerList = explode(',', $userdata['follower']);
						if (in_array($userid, $followerList)) {
							// フォロー済みの場合はフォロー解除ボタンを表示
							echo '<input type="button" id="openModalButton" class="fbtn_un" name="unfollow" value="フォロー解除">';
						} else {
							// 未フォローの場合はフォローボタンを表示
							echo '<input type="submit" class="fbtn" name="follow" value="フォロー">';
						}
						?>
					</div>
				</form>
			<?php } ?>
			<?php } ?>
		</div>

		<div id="myModal" class="modal">
			<div class="modal-content">
				<p><?php echo htmlentities($userData['username'], ENT_QUOTES, 'UTF-8'); ?>さんをフォロー解除しますか？</p>
				<form class="btn_area" method="post">
					<input type="submit" id="openModalButton" class="fbtn_no" name="unfollow" value="フォロー解除">
					<input type="button" id="closeModal" class="fbtn" value="キャンセル">
				</form>
			</div>
		</div>



		<hr>
			<section class="inner">
				<div id="postContainer">

				</div>
			</section>

			<div id="loading" class="loading" style="display: none;">
				🤔
			</div>

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
					<p>ユーズに追記しますか？</p>
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
		
	</main>



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
		var uwuzuid = '<?php echo $uwuzuid; ?>';
		var userid = '<?php echo $userid; ?>';
        $.ajax({
            url: '../nextpage/userpage.php', // PHPファイルへのパス
            method: 'GET',
            data: { page: pageNumber, id: uwuzuid ,userid: userid},
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

});

    // JavaScriptでウィンドウを制御
    const modal1 = document.getElementById('myModal');
    const openModalButton = document.getElementById('openModalButton');
    const closeButton = document.getElementById('closeModal');

    openModalButton.addEventListener('click', () => {
        modal1.style.display = 'block';
    });

    closeButton.addEventListener('click', () => {
        modal1.style.display = 'none';
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
</script>

</html>