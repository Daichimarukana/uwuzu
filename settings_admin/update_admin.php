<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

require('../db.php');
require("../function/function.php");


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
	$myfollowlist = safetext($is_login["follow"]);
	$is_Admin = safetext($is_login["admin"]);
}

$notiQuery = $pdo->prepare("SELECT COUNT(*) as notification_count FROM notification WHERE touserid = :userid AND userchk = 'none'");
$notiQuery->bindValue(':userid', $userid);
$notiQuery->execute();
$notiData = $notiQuery->fetch(PDO::FETCH_ASSOC);

$notificationcount = $notiData['notification_count'];

if (!empty($_POST['update_submit'])) { 
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['extract_path'])) { 
		$extractPath = safetext($_POST['extract_path']); 
		 
		// JSONファイルの再読み込み 
		$jsonFile = $extractPath . '/update.json'; 
		if (file_exists($jsonFile)) { 
			$jsonData = json_decode(file_get_contents($jsonFile), true); 
			if (json_last_error() === JSON_ERROR_NONE) { 
				// 上書きファイルの処理 
				if (!(empty($jsonData['files']['overwrite']))) { 
					foreach ($jsonData['files']['overwrite'] as $file) { 
						$sourceFile = $extractPath . '/' . $file; 
						$destinationFile = $_SERVER['DOCUMENT_ROOT'] . '/' . $file; 

						if (file_exists($sourceFile)) {
							// ディレクトリが存在しない場合は作成
							$destinationDir = dirname($destinationFile);
							if (!file_exists($destinationDir)) {
								mkdir($destinationDir, 0775, true);
							}

							copy($sourceFile, $destinationFile);
						} else { 
							$error_message[] = "アップデート元のzipファイルに本来予定されていたファイルがありませんでしたが、アップデート作業は完了しました。(UPDATE_FILE_NOT_FOUND)"; 
						} 
					} 
				} 
				 
				// 削除ファイルの処理 
				if (!(empty($jsonData['files']['delete']))) { 
					foreach ($jsonData['files']['delete'] as $file) { 
						$deleteFile = $_SERVER['DOCUMENT_ROOT'] . '/' . $file; 
						if (file_exists($deleteFile)) { 
							unlink($deleteFile); 
						} else { 
							$error_message[] = "削除予定のファイルがありませんでしたが、アップデート作業は完了しました。(DELETE_FILE_NOT_FOUND)"; 
						} 
					} 
				}				 
			} else { 
				$error_message[] = "update.jsonがうまく読み込めませんでした。(LOADING_ERROR)"; 
			} 
		} else { 
			$error_message[] = "update.jsonが見つかりませんでした。(LOADING_ERROR)"; 
		} 
 
		if (file_exists($extractPath)) { 
			if (is_dir($extractPath)) { 
				deleteDirectory($extractPath); 
			} 
		} 
	} else { 
		$error_message[] = "不正なリクエストです。(BAD_REQUEST)"; 
	} 
}

require('../logout/logout.php');
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
<title>アップデート - <?php echo safetext($serversettings["serverinfo"]["server_name"]);?></title>

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
				<div class="formarea">
					<h1>アップデート</h1>
					<p>ここからuwuzuのアップデートが行えます。<br>
						データベースの構造に変更を加える必要のあるアップデートの場合、データベースの構造の変更を手動で行っていただいた後にここからuwuzu自体のアップデートが行えます。<br>
						uwuzuは一度アップデートするともとの状態に戻すことはできません。<br>
						また、アップデート中に発生したエラーや不具合に関してuwuzu開発者が責任を取ることはできません。<br>
						<br>
						飛び級アップデートはuwuzuが破損するため絶対にしないでください。
					</p>
					
					
					<label class="irobutton" for="file_upload">ファイル選択
						<input type="file" id="file_upload" name="zip" accept="application/zip">
					</label>
					<p id="file_select" style="display:none;">ファイルを選択しました</p>
				</div>

				<div id="confirm_update" class="formarea" style="display: none;">
					<h1>アップデート内容の確認</h1>
					<p>アップデート内容を確認してください。</p>

					<div class="update_box">
						<h1 id="software">ソフトウェア名</h1>
						<h2 id="version">version 1.2.3</h2>

						<p>リリースノート</p>
						<div class="update_text">
							<p id="release_notes"></p>
						</div>

						<p>注意事項</p>
						<div class="update_text">
							<p id="notices"></p>
						</div>
					</div>

					<form enctype="multipart/form-data" method="post">
						<input type="hidden" name="extract_path" id="extract_path" value="">
						<input type="submit" class="irobutton" name="update_submit" value="アップデート">
					</form>
				</div>
			</div>
		</div>
	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>
	<?php require('../require/noscript_modal.php');?>
</body>
<script>
$(document).ready(function(){
    $('#file_upload').change(function(e) {
        $('#file_select').show();

        var file = $('#file_upload').prop('files')[0];

        if (file) {
            const formData = new FormData();
            formData.append('update_zip', file);
            formData.append('userid', "<?php echo $userid ?>");
            formData.append('account_id', "<?php echo $loginid ?>");

            $.ajax({
                url: 'api/update_query.php', // PHPファイルへのパス
                method: 'POST',
                data: formData,
                processData: false, // jQueryが自動的に処理しないようにする
                contentType: false, // jQueryが自動的に設定するContent-Typeを無効にする
				cache: false,
				dataType: 'json',
                timeout: 300000,
                success: function(response) {
                    if (response["success"] == true) {
						$("#extract_path").val(response["file_path"]);

						$("#software").text(response["software_name"]);
						$("#version").text(response["version"]);

                        $("#release_notes").html(response["release_notes"].replace(/\n/g, '<br>'));
						$("#notices").html(response["notices"].replace(/\n/g, '<br>'));

						$("#confirm_update").show();
                    } else {
                        console.log("error1");
                    }
                }.bind(this),
                error: function (xhr, textStatus, errorThrown) {
                    console.log(xhr);
                }
            });
        }
    });
});
</script>
</html>