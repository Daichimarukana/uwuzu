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
    $pdo = new PDO('mysql:charset=UTF8;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

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
$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

require('../logout/logout.php');

if( !empty($_POST['delete_all_bookmark']) ) {
	$updateQuery = $pdo->prepare("UPDATE account SET bookmark = :bookmark WHERE userid = :userid");
	$updateQuery->bindValue(':bookmark', '', PDO::PARAM_STR);
	$updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
	$res = $updateQuery->execute();
	if ($res) {
		$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];;
		header("Location:".$url."");
		exit;  
	} else {
		$error_message[] = "BOOKMARK_NOT_DELETED";
	}
}

// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<link rel="stylesheet" href="../css/home.css">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>ブックマーク - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>

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

		<div class="emojibox_flex">
			<h1>ブックマーク</h1>
			<div class="right_box">
				<button name="del_bookmark" id="del_bookmark" class="emojibox_button" title="ブックマークの一括削除"><svg><use xlink:href="../img/sysimage/delete_1.svg#delete"></use></svg></a>
			</div>
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

		<!--ブックマーク全削除-->
		<div id="Del_all_bookmark" class="modal">
			<div class="modal-content">
				<h1>ブックマークを全て削除しますか？</h1>
				<p>削除後の復元はできません。</p>
				<form class="btn_area" method="post" id="Del_bookmark_Form">
					<input type="submit" id="Del_bookmark_Button" class="fbtn_no" name="delete_all_bookmark" value="削除">
					<input type="button" id="Del_bookmark_Cancel" class="fbtn" value="キャンセル">
				</form>
			</div>
		</div>
		<!--ブックマーク全削除-->

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

	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>


</body>

<script>

$(document).ready(function () {
    loadPosts();

    var pageNumber = 1;
    var isLoading = false;

    function loadPosts() {
        if (isLoading) return;
        isLoading = true;
        $("#loading").show();
        var userid = '<?php echo $userid; ?>';
        var account_id = '<?php echo $loginid; ?>';
        $.ajax({
            url: '../nextpage/bookmark.php', // PHPファイルへのパス
            method: 'GET',
            data: { page: pageNumber, userid: userid, account_id: account_id },
            dataType: 'html',
            success: function (response) {
                $('#postContainer').append(response);
                pageNumber++;
                isLoading = false;
                $("#loading").hide();
            }
        });
    }

    $('.outer').on('scroll', function () {
        var innerHeight = $('.inner').innerHeight(), //内側の要素の高さ
            outerHeight = $('.outer').innerHeight(), //外側の要素の高さ
            outerBottom = innerHeight - outerHeight; //内側の要素の高さ - 外側の要素の高さ
        if (outerBottom <= $('.outer').scrollTop()) {
            var elem = document.getElementById("noueuse");

            if (elem === null) {
                // 存在しない場合の処理
                loadPosts();
            } else {
                // 存在する場合の処理
                return;
            }
        }
    });


    $(document).on('click', '.favbtn, .favbtn_after', function (event) {

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
            data: { uniqid: postUniqid, userid: userid, account_id: account_id }, // ここに自分のユーザーIDを指定
            dataType: 'json',
            success: function (response) {
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
            error: function () {
                // エラー時の処理
            }
        });
    });


    $(document).on('click', '.bookmark, .bookmark_after', function (event) {

        event.preventDefault();

        var postUniqid = $(this).data('uniqid');
        var userid = '<?php echo $userid; ?>';
        var account_id = '<?php echo $loginid; ?>';
        var likeCountElement = $(this).find('.like-count'); // いいね数を表示する要素

        var isLiked = $(this).hasClass('bookmark_after'); // 現在のいいねの状態を判定

        var $this = $(this); // ボタン要素を変数に格納

        $.ajax({
            url: '../bookmark/bookmark.php',
            method: 'POST',
            data: { uniqid: postUniqid, userid: userid, account_id: account_id }, // ここに自分のユーザーIDを指定
            dataType: 'json',
            success: function (response) {
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
            error: function () {
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
            window.setTimeout(function () {
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
            window.setTimeout(function () {
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
            window.setTimeout(function () {
                abimodal.style.display = 'none';
            }, 150);
        });

        $('#AbiForm').off('submit').on('submit', function (event) {

            event.preventDefault();

            var abitext = document.getElementById("abitexts").value;
            var usernames = '<?php echo $username; ?>';
            var userid = '<?php echo $userid; ?>';
            var account_id = '<?php echo $loginid; ?>';

            if (abitext == "") {
                modalMain.removeClass("slideUp");
                modalMain.addClass("slideDown");
                window.setTimeout(function () {
                    abimodal.style.display = 'none';
                }, 150);
            } else {
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

	var bookmark_modal = document.getElementById('Del_all_bookmark');
    var bookmark_deleteButton = document.getElementById('Del_bookmark_Button');
    var bookmark_cancelButton = document.getElementById('Del_bookmark_Cancel'); // 追加
	var modalMain = $('.modal-content');

    $(document).on('click', '.emojibox_button', function (event) {
        bookmark_modal.style.display = 'block';
		modalMain.addClass("slideUp");
    	modalMain.removeClass("slideDown");

        bookmark_deleteButton.addEventListener('click', () => {
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				bookmark_modal.style.display = 'none';
			}, 150);
        });

        bookmark_cancelButton.addEventListener('click', () => { // 追加
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				bookmark_modal.style.display = 'none';
			}, 150);
        });
    });

    window.addEventListener('online', function () {
        checkOnline();
    });
    window.addEventListener('offline', function () {
        checkOnline();
    });
    function checkOnline() {
        if (navigator.onLine) {
            $("#online").show();
            $("#offline").hide();
        } else {
            $("#online").hide();
            $("#offline").show();
        }
    }

    $(document).on('click', '.share', function (event) {

        var domain = "<?php echo $domain;?>";
        var share_uniqid = $(this).attr('data-uniqid');
        var share_userid = $(this).attr('data-userid');

        if (typeof navigator.share === 'undefined') {
            navigator.clipboard.writeText("https://" + domain + "/!" + share_uniqid + "")
            $("#clipboard").show();
            window.setTimeout(function () {
                $("#clipboard").hide();
            }, 5000);
            return;
        }

        var shareData = {
            title: '' + share_userid + 'さんのID ' + share_uniqid + ' のユーズ - uwuzu',
            text: '',
            url: "https://" + domain + "/!" + share_uniqid + "",
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
    $('#os_exit_btn').on('click', function () {
        document.cookie = "event=done; max-age=86400";
        osho_gats.style.display = 'none';
    });
});
</script>

</html>