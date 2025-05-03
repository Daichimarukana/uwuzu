<?php // データベースの接続情報
define( 'DB_HOST', '');
define( 'DB_USER', '');
define( 'DB_PASS', '');
define( 'DB_NAME', '');

// ENC_KEYは操作しないでください。ユーザーデータを使用できなくなるおそれがあります。
define( 'ENC_KEY', '');

define( 'RATE_LM', '60'); // レートリミット(ユーズ/分)
define( 'STOP_LA', '4'); // 自動停止ロードアベレージ上限
// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');
?>
        