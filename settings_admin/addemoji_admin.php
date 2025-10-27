<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

require('../db.php');
//関数呼び出し
//- EXIF
require('../function/function.php');

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
	$is_Admin = safetext($is_login["admin"]);
}

$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

if( !empty($pdo) ) {

	$sql = "SELECT * FROM emoji ORDER BY emojidate DESC";
	$allemoji = $pdo->query($sql);    

	while ($row = $allemoji->fetch(PDO::FETCH_ASSOC)) {

		$Emojis[] = $row;
	}

}

if( !empty($_POST['btn_submit']) ) {
	$emojiname = safetext($_POST['emojiname']);
    $emojiinfo = safetext($_POST['emojiinfo']);

    if (!empty($_FILES['image']['name'])) {
        // アップロードされたファイル情報
		$uploadedFile = $_FILES['image'];

		if(filesize($uploadedFile['tmp_name']) > 256000){
			$error_message[] = "絵文字のファイルサイズは256KB以下に押さえてください。(EMOJI_OVER_256KB)";
		}

		if(check_mime($uploadedFile['tmp_name'])){
			if(empty($error_message)){
				// アップロードされたファイルの拡張子を取得
				$extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
				
				// 新しいファイル名を生成（uniqid + 拡張子）
				$newFilename = createUniqId() . '.' . $extension;
				
				// 保存先のパスを生成
				$uploadedPath = 'emojiimage/' . $newFilename;

				// EXIF削除
				delete_exif($extension, $uploadedFile['tmp_name']);

				// ファイルを移動
				$result = move_uploaded_file($uploadedFile['tmp_name'], '../'.$uploadedPath);
				
				if ($result) {
					$emoji_path = $uploadedPath; // 保存されたファイルのパスを使用
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
		}else{
			$error_message[] = "使用できない画像形式です。(FILE_UPLOAD_DEKINAKATTA)";
		}
    }else{
		$error_message[] = '画像を選択してください';
    }



    $options = array(
        // SQL実行失敗時に例外をスルー
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // デフォルトフェッチモードを連想配列形式に設定
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // バッファードクエリを使う（一度に結果セットを全て取得し、サーバー負荷を軽減）
        // SELECTで得た結果に対してもrowCountメソッドを使えるようにする
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    );

    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);


    $query = $dbh->prepare('SELECT * FROM emoji WHERE emojiname = :emojiname limit 1');

    $query->execute(array(':emojiname' => $emojiname));

    $result = $query->fetch();

    // IDの入力チェック
	if( empty($emojiname) ) {
		$error_message[] = '絵文字IDを入力してください！(EMOJI_ID_INPUT_PLEASE)';
	} else {

        // 文字数を確認
        if( 20 < mb_strlen($emojiname, 'UTF-8') ) {
			$error_message[] = 'IDは20文字以内で入力してください。(EMOJI_ID_OVER_MAX_COUNT)';
		}

        if($result > 0){
            $error_message[] = 'このID('.$emojiname.')は既に使用されています。他のIDを作成してください。(EMOJI_ID_SHIYOUZUMI)'; //このE-mailは既に使用されています。
        }

    }

	if( empty($error_message) ) {
		
		// 書き込み日時を取得
        $datetime = date("Y-m-d H:i:s");

        // トランザクション開始
        $pdo->beginTransaction();

        try {

            // SQL作成
            $stmt = $pdo->prepare("INSERT INTO emoji (emojifile, emojiname, emojiinfo, emojidate) VALUES ( :emojifile, :emojiname, :emojiinfo, :emojidate)");
    
            $stmt->bindValue(':emojifile', $emoji_path, PDO::PARAM_STR);

            // 値をセット
            $stmt->bindParam( ':emojiname', $emojiname, PDO::PARAM_STR);
            $stmt->bindParam( ':emojiinfo', $emojiinfo, PDO::PARAM_STR);
            
            $stmt->bindParam( ':emojidate', $datetime, PDO::PARAM_STR);

            // SQLクエリの実行
            $res = $stmt->execute();

            // コミット
            $res = $pdo->commit();

        } catch(Exception $e) {

            // エラーが発生した時はロールバック
            $pdo->rollBack();
			actionLog($userid, "error", "addemoji_admin", null, $e, 4);
        }

        if( $res ) {
			actionLog($userid, "info", "addemoji_admin", null, "カスタム絵文字が追加されました", 0);
            $url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location:".$url."");
            exit;  
        } else {
            $error_message[] = '登録に失敗しました。(REGISTERED_DAME)';
        }

        // プリペアドステートメントを削除
        $stmt = null;


	}
}

if( !empty($_POST['emoji_del']) ) {
	$emoji_name = safetext($_POST['emoji_id']);

	$query = $pdo->prepare('SELECT * FROM emoji WHERE emojiname = :emojiname limit 1');
	$query->bindValue(':emojiname', $emoji_name);
	$query->execute();
	$emoji_img = $query->fetch();

	if(!(empty($emoji_img))){
		if (is_file("../".$emoji_img["emojifile"])) {
			unlink("../".$emoji_img["emojifile"]);
		}else{
			$error_message[] = "絵文字の画像が見つかりませんでした。(EMOJI_NOT_FOUND)";
		}

		if(empty($error_message)){
			try{
				// 通知削除クエリを実行
				$deleteQuery = $pdo->prepare("DELETE FROM emoji WHERE emojiname = :emojiname");
				$deleteQuery->bindValue(':emojiname', $emoji_name, PDO::PARAM_STR);
				$res = $deleteQuery->execute();
		
			} catch (Exception $e) {
				$pdo->rollBack();
				actionLog($userid, "error", "addemoji_admin_del", null, $e, 4);
			}
		
			if( $res ) {
				actionLog($userid, "info", "addemoji_admin_del", null, "カスタム絵文字が削除されました", 0);
				$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header("Location:".$url."");
				exit;  
			} else {
				$error_message[] = $e->getMessage();
			}
		}
	}else{
		$error_message[] = "絵文字が見つかりませんでした。(EMOJI_NOT_FOUND)";
	}

	// プリペアドステートメントを削除
	$stmt = null;
}


require('../logout/logout.php');



// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/home.css">
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="../js/jquery-min.js"></script>
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<title>絵文字登録 - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

</head>

<body>
<?php require('../require/leftbox.php');?>
<main>
	<div class="admin_settings">
		<?php require('settings_left_menu.php');?>

		<div class="admin_right">

			<?php if( !empty($error_message) ): ?>
				<ul class="errmsg">
					<?php foreach( $error_message as $value ): ?>
						<p>・ <?php echo $value; ?></p>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
					
			<form class="formarea" enctype="multipart/form-data" method="post">

				<h1>絵文字登録</h1>

				<p>絵文字登録です。</p>
				<div class="p2">
				注意 : uwuzuで表示されるカスタム絵文字の最大の大きさは縦64pxです。<br>
				縦64px以上のカスタム絵文字を登録しても縮小されて表示されます。<br>
				最大ファイルサイズは256KBです。<br>
				これはカスタム絵文字によってuwuzuが重たくならないようにするための仕様です。</div>

				<div id="wrap">
					<div class="emojipreview">
						<div class="emojiimg light">
							<img id="emojiimg_light" src="../img/sysimage/errorimage/emoji_404.png">
						</div>
						<div class="emojiimg dark">
							<img id="emojiimg_dark" src="../img/sysimage/errorimage/emoji_404.png">
						</div>
					</div>
					
					<label class="irobutton" for="file_upload">ファイル選択
					<input type="file" id="file_upload" name="image" >
					</label>
					<p id="img_select" style="display:none;">画像を選択しました</p>
				</div>
				<!--ユーザーネーム関係-->
				<div>
					<p>EmojiID</p>
					<input id="username" onInput="checkForm(this)" placeholder="kusa" class="inbox" type="text" name="emojiname" value="<?php if( !empty($_SESSION['emojiname']) ){ echo safetext( $_SESSION['emojiname']); } ?>">
				</div>

				<div>
					<p>この絵文字について</p>
					<input id="username" placeholder="くさデス" class="inbox" type="text" name="emojiinfo" value="<?php if( !empty($_SESSION['emojiinfo']) ){ echo safetext( $_SESSION['emojiinfo']); } ?>">
				</div>

				<div>
					<input type="submit" class = "irobutton" name="btn_submit" value="登録">
				</div>
			</form>

			<div class="formarea">
				<?php if(!(empty($Emojis))){?>
					<?php foreach ($Emojis as $value) {?>
						<div class="emoji_admin">
							<details>
								<summary><img src="../<?php echo safetext($value["emojifile"]);?>"><?php echo safetext($value["emojiname"]);?></summary>
								<hr>
								<div class="p2">説明</div>
								<p><?php echo nl2br(safetext($value["emojiinfo"]));?></p>
								<hr>
								<div class="p2">登録日時</div>
								<p><?php echo date("Y年m月d日 H:i", strtotime(safetext($value["emojidate"])));?></p>

								<hr>

								<form enctype="multipart/form-data" method="post">
									<div class="delbox">
										<p>削除ボタンを押すとこの絵文字は削除されます。<br>
											この絵文字を使用した投稿からは絵文字が表示されなくなります。</p>
										<input type="text" name="emoji_id" id="emoji_id" value="<?php echo safetext($value["emojiname"]);?>" style="display:none;" >
										<input type="submit" name="emoji_del" class="delbtn" value="削除">
									</div>
								</form>
							</details>
						</div>
					<?php }?>
				<?php }?>
			
			</div>


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
$(document).ready(function(){
	$('#file_upload').change(function(e) {
		var file_reader = new FileReader();
		file_reader.addEventListener('load', function(e) {
			$('#img_select').show();
			$('#emojiimg_light').attr('src', file_reader.result);
			$('#emojiimg_dark').attr('src', file_reader.result);
		});
		file_reader.readAsDataURL(e.target.files[0]);
	});
});
</script>
</html>