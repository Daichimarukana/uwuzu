<?php
if( !empty($_POST['logout']) ) {
	// リダイレクト先のURLへ転送する
    $url = '../logout/index.php';
    header('Location: ' . $url, true, 303);

    // すべての出力を終了
    exit;
}
?>