<?php

function createUniqId() {
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec . floor($msec * 1000000);

    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime, 10, 36);
}

require('../db.php');

require('view.php');

// データベースに接続
try {
    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=utf8mb4;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);
} catch (PDOException $e) {
    // 接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}

$userid = htmlentities($_GET['userid']);

$itemsPerPage = 30; // 1ページあたりのユーズ数
$pageNumber = htmlentities(isset($_GET['page'])) ? htmlentities(intval($_GET['page'])) : 1;
$offset = ($pageNumber - 1) * $itemsPerPage;

$messages = array();

if (!empty($pdo)) {

    
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
			$link = "<a href='$url' target='_blank'>$domain</a>";
	
			// URLをドメインのみを表示するaタグで置き換え
			$postText = str_replace($url, $link, $postText);
		}
	
		return $postText;
	}

    
    $sql = "SELECT account, username, uniqid, rpuniqid, ueuse, datetime, photo1, photo2, video1, favorite, abi, abidate FROM ueuse WHERE rpuniqid = '' ORDER BY datetime DESC LIMIT $offset, $itemsPerPage";
    $message_array = $pdo->query($sql);    

    while ($row = $message_array->fetch(PDO::FETCH_ASSOC)) {

        $messages[] = $row;
    }

    // ユーザー情報を取得して、$messages内のusernameをuserDataのusernameに置き換える
    foreach ($messages as &$message) {
        $userQuery = $pdo->prepare("SELECT username, userid, profile, role FROM account WHERE userid = :userid");
        $userQuery->bindValue(':userid', $message["account"]);
        $userQuery->execute();
        $userData = $userQuery->fetch();

        if ($userData) {
            $message['username'] = $userData['username'];
        }
    }

    if(!empty($messages)){
        foreach ($messages as $value) {
                
            $fav = $value['favorite']; // コンマで区切られたユーザーIDを含む変数
    
            // コンマで区切って配列に分割し、要素数を数える
            $favIds = explode(',', $fav);
            $value["favcnt"] = count($favIds)-1;
    
            $messageDisplay = new MessageDisplay($value, $userid); // $userid をコンストラクタに渡す
            $messageDisplay->display();
        }
    }else{
        echo '<div class="tokonone" id="noueuse"><p>ユーズがありません</p></div>';
    }
    
    $pdo = null;

}

?>
