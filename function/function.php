<?php
function isIpInCIDR($ip, $cidr){
    if (!strpos($cidr, '/')) {
        return $ip === $cidr;
    }

    [$network, $prefixLength] = explode('/', $cidr);
    if((filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) && (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))){
        $prefixLength = (int)$prefixLength;

        $ipBinary = inet_pton($ip);
        $networkBinary = inet_pton($network);

        if ($ipBinary === false || $networkBinary === false) {
            actionLog(null, "error", "isIpInCIDR", null, "invalid_ip_or_network_".$ipBinary."/".$networkBinary, 4); 
            return false;
        }

        $totalBits = strlen($networkBinary) * 8;

        if ($prefixLength < 0 || $prefixLength > $totalBits) {
            actionLog(null, "error", "isIpInCIDR", null, "bad_prefix_length_".$prefixLength, 4); 
            return false;
        }

        $mask = str_repeat("\xFF", (int)($prefixLength / 8));
        $remainingBits = $prefixLength % 8;

        if ($remainingBits > 0) {
            $mask .= chr((0xFF << (8 - $remainingBits)) & 0xFF);
        }
        $mask = str_pad($mask, strlen($networkBinary), "\x00");

        return ($ipBinary & $mask) === ($networkBinary & $mask);
    }else{
        actionLog(null, "error", "isIpInCIDR", null, "bad_ip", 4); 
        return false;
    }
}
function blockedIP($ip_addr) {
    // データベースに接続
    try {
        $pdo = new PDO(
            'mysql:charset=utf8mb4;dbname=' . DB_NAME . ';host=' . DB_HOST,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
            ]
        );
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }

    // IPブロックリストの取得
    $search_query = $pdo->prepare('SELECT ipaddr FROM ipblock');
    $search_query->execute();
    $blocked_ips = $search_query->fetchAll(PDO::FETCH_COLUMN);

    foreach ($blocked_ips as $blocked_ip) {
        if (isIpInCIDR($ip_addr, $blocked_ip)) {
            $fron_uwuzu_errcode = "IP_BANNED";
            $url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . "/unsupported.php?errcode=" . $fron_uwuzu_errcode;
            header("Location: " . $url);

            require(__DIR__ . '/../unsupported.php');
            exit;
        }
    }
}
function stopLoadAvg(){
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		$loadAve = 0;
	} else {
		if (function_exists("sys_getloadavg")) {
            $load = sys_getloadavg();
            $loadAve = is_array($load) && isset($load[0]) ? $load[0] : 0;
        } else {
            $loadAve = 0;
        }
	}

    if (defined('STOP_LA') && (int)STOP_LA !== -1) {
        if ($loadAve > (int)STOP_LA) {
            include_once __DIR__ . '/../errorpage/overcapacity.php';
            exit;
        }
    }
}
//通常のログイン処理
function uwuzuUserLogin($session, $cookie, $ip_addr, $operation_permission = "user") {
    //セッション,クッキー,IPアドレス,閲覧権限(userかadminかの二種類)を受け取る
    $serversettings_file = $_SERVER['DOCUMENT_ROOT']."/server/serversettings.ini";
    $serversettings = parse_ini_file($serversettings_file, true);
    // データベースに接続
    try {
        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
    } catch(PDOException $e) {
        actionLog(null, "error", "uwuzuUserLogin", null, $e, 4);
        return false;
    }

    if(isset($session['loginid'])){
        $loginid = safetext($session['loginid']);
    }else if(isset($cookie['loginid'])){
        $loginid = safetext($cookie['loginid']);
    } else {
        return false;
    }

    if(isset($session['loginkey'])) {
        $loginkey = safetext($session['loginkey']);
    }else if(isset($cookie['loginkey'])){
        $loginkey = safetext($cookie['loginkey']);
    } else {
        $loginkey = null;
    }

    $loginQuery = $pdo->prepare("SELECT * FROM account WHERE loginid = :loginid");
    $loginQuery->bindValue(':loginid', $loginid);
    $loginQuery->execute();
    $loginResponse = $loginQuery->fetch();
    if(empty($loginResponse["userid"])){
        return false;
    }elseif($loginid === $loginResponse["loginid"]){
        $userEncKey = GenUserEnckey($loginResponse["datetime"]);
        $userLoginKey = hash_hmac('sha256', $loginResponse["loginid"], $userEncKey);

        if(!(empty($loginkey))){
            if(hash_equals($loginkey, $userLoginKey)){
                if($operation_permission == "admin"){
                    if($loginResponse["admin"] == "yes"){
                        $is_login = true;
                    }else{
                        $is_login = false;
                        stopLoadAvg();
                    }
                }else{
                    $is_login = true;
                    stopLoadAvg();
                }
            }else{
                $is_login = false;
                stopLoadAvg();
            }
        }else{
            if(isset($session['userid']) && isset($session['username'])){
                if($session['userid'] === $loginResponse["userid"] && $session['username'] === $loginResponse["username"]){
                    if($operation_permission === "admin"){
                        if($loginResponse["admin"] == "yes"){
                            $is_login = true;
                        }else{
                            $is_login = false;
                            stopLoadAvg();
                        }
                    }else{
                        $is_login = true;
                        stopLoadAvg();
                    }
                }else{
                    $is_login = false;
                    stopLoadAvg();
                }
            }else if(isset($cookie['userid']) && isset($cookie['username'])){
                if($cookie['userid'] === $loginResponse["userid"] && $cookie['username'] === $loginResponse["username"]){
                    if($operation_permission === "admin"){
                        if($loginResponse["admin"] == "yes"){
                            $is_login = true;
                        }else{
                            $is_login = false;
                            stopLoadAvg();
                        }
                    }else{
                        $is_login = true;
                        stopLoadAvg();
                    }
                }else{
                    $is_login = false;
                    stopLoadAvg();
                }
            }else{
                $is_login = false;
                stopLoadAvg();
            }
        }

        if($is_login === true){
            $userid = safetext($loginResponse['userid']); // セッションに格納されている値をそのままセット
            $username = safetext($loginResponse['username']); // セッションに格納されている値をそのままセット
            $loginid = safetext($loginResponse["loginid"]);

            $_SESSION['userid'] = $userid;
            $_SESSION['username'] = $username;
            $_SESSION['loginid'] = $loginid;

            setcookie('loginid', $loginid,[
                'expires' => time() + 60 * 60 * 24 * 28,
                'path' => '/',
                'samesite' => 'lax',
                'secure' => true,
                'httponly' => true,
            ]);

            setcookie('loginkey', $userLoginKey,[
                'expires' => time() + 60 * 60 * 24 * 28,
                'path' => '/',
                'samesite' => 'lax',
                'secure' => true,
                'httponly' => true,
            ]);

            //IP保存が有効であれば保存する---------------------------------------------------
            if(safetext($serversettings["serverinfo"]["server_get_ip"]) === "true"){
                if(filter_var($ip_addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || filter_var($ip_addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
                    $enc_ip_addr = EncryptionUseEncrKey($ip_addr, $userEncKey, $loginResponse["encryption_ivkey"]);
                    $pdo->beginTransaction();
                    try {
                        $updateQuery = $pdo->prepare("UPDATE account SET last_ip = :last_ip WHERE userid = :userid");
                        $updateQuery->bindValue(':last_ip', $enc_ip_addr, PDO::PARAM_STR);
                        $updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
                        $res = $updateQuery->execute();

                        if($res){
                            $pdo->commit();
                        }else{
                            // ロールバック
                            $pdo->rollBack();
                            actionLog($userid, "error", "uwuzuUserLogin", null, "IPアドレスを記録できませんでした！", 3);
                        }
                    } catch (Exception $e) {
                        // ロールバック
                        $pdo->rollBack();
                        actionLog($userid, "error", "uwuzuUserLogin", null, $e, 4);
                    }
                }else{
                    actionLog($userid, "notice", "uwuzuUserLogin", null, "ユーザーのIPアドレスが不正な値でした！", 2);
                }
            }

            //JobがあればJobを実行する---------------------------------------------------
            $job = getJob($pdo, $userid);
            if(!(empty($job))){
                if($job["job"] == "deleteUser"){
                    deleteUser($pdo, $job["userid"], $job["step"], $job["uniqid"]);
                }
            }

            return $loginResponse;
        }else{
            return false;
        }
    }else{
        return false;
    }
}
//APIなどのログイン処理(loginidとloginkeyが有効かを確かめる)
function uwuzuUserLoginCheck($loginid, $loginkey, $operation_permission = "user") {
    //セッション,クッキー,IPアドレス,閲覧権限(userかadminかの二種類)を受け取る
    // データベースに接続
    try {
        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
    } catch(PDOException $e) {
        actionLog(null, "error", "uwuzuUserLogin", null, $e, 4);
        return false;
    }

    if(!(isset($loginid))){
        return false;
        exit;
    }

    if(!(isset($loginkey))){
        return false;
        exit;
    }

    $loginQuery = $pdo->prepare("SELECT * FROM account WHERE loginid = :loginid");
    $loginQuery->bindValue(':loginid', $loginid);
    $loginQuery->execute();
    $loginResponse = $loginQuery->fetch();
    if(empty($loginResponse["userid"])){
        return false;
    }elseif($loginid === $loginResponse["loginid"]){
        $userEncKey = GenUserEnckey($loginResponse["datetime"]);
        $userLoginKey = hash_hmac('sha256', $loginResponse["loginid"], $userEncKey);

        if(!(empty($loginkey))){
            if(hash_equals($loginkey, $userLoginKey)){
                if($operation_permission == "admin"){
                    if($loginResponse["admin"] == "yes"){
                        $is_login = true;
                    }else{
                        $is_login = false;
                    }
                }else{
                    $is_login = true;
                }
            }else{
                $is_login = false;
            }
        }else{
            $is_login = false;
        }

        return $is_login;
    }else{
        return false;
    }
}
//---------UNIQID-MAKER---------
function Legacy_createUniqId(){ 
    list($msec, $sec) = explode(" ", microtime()); 
    $hashCreateTime = $sec.floor($msec*1000000); 
     
    $hashCreateTime = strrev($hashCreateTime); 
 
    return base_convert($hashCreateTime,10,36); 
}
function createUniqId($randDigits = 6) {
    $msec_time = (int)(microtime(true) * 1000);
    $randMax = pow(10, $randDigits) - 1;
    $rand_num = str_pad(random_int(0, $randMax), $randDigits, '0', STR_PAD_LEFT);
    $combined = $msec_time . $rand_num;
    return base_convert(strrev($combined), 10, 36);
}
function parseUniqId($id, $randDigits = 6) {
    $reversed_num_str = base_convert($id, 36, 10);
    $combined_num_str = strrev($reversed_num_str);
    $msec_time_str = substr($combined_num_str, 0, -$randDigits);
    return date("Y-m-d H:i:s.v", (int)($msec_time_str / 1000));
}
//----------EXIF_Delete----------
//EXIFを削除するやつです。
function rotate($image, $exif){
    $orientation = $exif['Orientation'] ?? 1;

    switch ($orientation) {
        case 1: //no rotate
            break;
        case 2: //FLIP_HORIZONTAL
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 3: //ROTATE 180
            $image = imagerotate($image, 180, 0);
            break;
        case 4: //FLIP_VERTICAL
            imageflip($image, IMG_FLIP_VERTICAL);
            break;
        case 5: //ROTATE 270 FLIP_HORIZONTAL
            $image = imagerotate($image, 270, 0);
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 6: //ROTATE 90
            $image = imagerotate($image, 270, 0);
            break;
        case 7: //ROTATE 90 FLIP_HORIZONTAL
            $image = imagerotate($image, 90, 0);
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 8: //ROTATE 270
            $image = imagerotate($image, 90, 0);
            break;
    }
    return $image;
}
function delete_exif($extension, $path){
    $exifimgext = array(
		"jpg",
		"jpeg",
		"jfif",
		"pjpeg",
		"pjp",
		"hdp",
		"wdp",
		"jxr",
		"tiff",
		"tif"
	);
	if(in_array($extension,$exifimgext)){
        if(check_mime($path) == "image/jpeg"){
            $gd = imagecreatefromjpeg($path);
            $w = imagesx($gd);
            $h = imagesy($gd);
            $gd_out = imagecreatetruecolor($w,$h);
            imagecopyresampled($gd_out, $gd, 0,0,0,0, $w,$h,$w,$h);
            $exif = exif_read_data($path); 
            $gd_out = rotate($gd_out, $exif);
            imagejpeg($gd_out, $path);
            imagedestroy($gd_out);
        }
	}
}
//----------EXIF_Delete----------
//----------Check_Extension------
//ファイル形式チェック(画像かどうか)
function check_mime($tmp_name){
    $finfo = new finfo();
    $tmp_ext = $finfo->file($tmp_name, FILEINFO_MIME_TYPE);
    $safe_img_mime = array(
		"image/gif",
		"image/jpeg",
		"image/png",
		"image/svg+xml",
		"image/webp",
        "image/bmp",
        "image/x-icon",
        "image/tiff"
	);
	if(in_array($tmp_ext,$safe_img_mime)){
        return $tmp_ext;
    }else{
        return false;
    }
}
//ファイル形式チェック(画像かどうか)
function check_mime_video($tmp_name){
    $finfo = new finfo();
    $tmp_ext = $finfo->file($tmp_name, FILEINFO_MIME_TYPE);
    $safe_vid_mime = array(
		"video/mpeg",
		"video/mp4",
		"video/webm",
		"video/x-msvideo",
	);
	if(in_array($tmp_ext,$safe_vid_mime)){
        return $tmp_ext;
    }else{
        return false;
    }
}
function convert_mime($mime_type){
    $safe_img_mime = array(
        "image/gif" => 'gif',
        "image/jpeg" => 'jpg',
        "image/png" => 'png',
        "image/svg+xml" => 'svg',
        "image/webp" => 'webp',
        "image/bmp" => 'bmp',
        "image/x-icon" => 'ico',
        "image/tiff" => 'tiff',
        "video/mpeg" => 'mpeg',
        "video/mp4" => 'mp4',
        "video/webm" => 'webm',
        "video/x-msvideo" => 'avi',
    );
    if(isset($safe_img_mime[$mime_type])){
        return $safe_img_mime[$mime_type];
    }else{
        return false;
    }
}
//ファイル形式チェック(Base64の場合)
function base64_mime($Base64,$userid){
    $Base64 = base64_decode($Base64);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_buffer($finfo, $Base64);

    $safe_img_mime = [
		"image/gif" => 'gif',
		"image/jpeg" => 'jpg',
		"image/png" => 'png',
		"image/svg+xml" => 'svg',
		"image/webp" => 'webp',
        "image/bmp" => 'bmp',
        "image/x-icon" => 'ico',
        "image/tiff" => 'tiff'
	];

    if(isset($safe_img_mime[$mime_type])){
        $extension = $safe_img_mime[$mime_type];
        $temp_file = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($temp_file, $Base64);

        delete_exif($extension, $temp_file);

        $newFilename = createUniqId() . '-' . $userid . '.' . $extension;
        $uploadedPath = '../ueuseimages/' . $newFilename;

        $result = copy($temp_file, "../".$uploadedPath);
        if($result){
            return $uploadedPath;
        } else {
            return false;
        }
    } else {
        return false;
    }
}
//APIユーズと通常ユーズ統合時に使うのでけさない
function base64_to_files($Base64, $userid) { 
    // Base64デコード
    $decodedData = base64_decode($Base64); 
    if ($decodedData === false) {
        return false;
    }

    // MIMEタイプの検出
    $finfo = finfo_open(FILEINFO_MIME_TYPE); 
    $mime_type = finfo_buffer($finfo, $decodedData); 
    finfo_close($finfo);

    // 許可されているMIMEタイプと拡張子の対応
    $safe_img_mime = [ 
        "image/gif" => 'gif', 
        "image/jpeg" => 'jpg', 
        "image/png" => 'png', 
        "image/svg+xml" => 'svg', 
        "image/webp" => 'webp', 
        "image/bmp" => 'bmp', 
        "image/x-icon" => 'ico', 
        "image/tiff" => 'tiff' 
    ]; 
 
    if (!(isset($safe_img_mime[$mime_type]))) { 
        return false;
    }

    $extension = $safe_img_mime[$mime_type];

    // 一時ファイルを作成
    $temp_file = tempnam(sys_get_temp_dir(), 'img'); 
    file_put_contents($temp_file, $decodedData);

    // 必要に応じてEXIFデータを削除
    delete_exif($extension, $temp_file);

    // ファイル名とアップロードパスを生成
    $newFilename = createUniqId() . '-' . $userid . '.' . $extension; 

    // $_FILES形式の配列を作成して返す
    return [
        'name' => $newFilename,
        'type' => $mime_type,
        'tmp_name' => $temp_file,
        'error' => 0,
        'size' => filesize($temp_file),
    ];
}

function resizeImage($filePath, $maxWidth, $maxHeight) {
    if (file_exists($filePath)) {  
        // 元の画像タイプを取得
        $imageType = check_mime($filePath);
        // 画像タイプに応じてリソースを作成
        if($imageType == "image/jpeg"){
            $originalImage = imagecreatefromjpeg($filePath);
        } elseif($imageType == "image/png") {
            $originalImage = imagecreatefrompng($filePath);
        } elseif($imageType == "image/webp") {
            $originalImage = imagecreatefromwebp($filePath);
        } elseif($imageType == "image/bmp") {
            $originalImage = imagecreatefrombmp($filePath);
        } else {
            return;
        }

        // 元の画像のサイズを取得
        list($originalWidth, $originalHeight) = getimagesize($filePath);

        // 縦横比を計算
        $aspectRatio = $originalWidth / $originalHeight;

        // 新しいサイズを計算
        if ($maxWidth / $maxHeight > $aspectRatio) {
            $newWidth = $maxHeight * $aspectRatio;
            $newHeight = $maxHeight;
        } else {
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $aspectRatio;
        }

        // 新しい画像リソースを作成
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        // 画像をリサイズ
        imagecopyresampled($resizedImage, $originalImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        // リサイズされた画像を表示
        imagewebp($resizedImage, $filePath);
        // メモリの解放
        imagedestroy($originalImage);
        imagedestroy($resizedImage);
    }
}

function uploadAmazonS3($tmp_name){
    if(check_mime_video($tmp_name) == false){
        $is_video = false;
    }else{
        $is_video = true;
    }
    $credentials = [
        'key'    => AMS3_ACCESSKEY,
        'secret' => AMS3_SECRETKEY,
    ];

    $bucket        = AMS3_BUCKET_NM;
    $srcFilePath   = $tmp_name;
    if($is_video == true){
        $mime = check_mime_video($srcFilePath);
        $extension = convert_mime($mime);
    }else{
        $mime = check_mime($srcFilePath);
        $extension = convert_mime($mime);
    }
    $key           = AMS3_PREFIX_NM.'/'.createUniqId().'' . '.' . $extension;

    if(AMS3_IS_S3FPS_ == 'true'){
        $S3FPS = true;
    }else{
        $S3FPS = false;
    }

    try {
        $s3Client = new Aws\S3\S3Client([
            'endpoint'    => AMS3_ENDPOINTS,
            'region'      => AMS3_REGION_NM,
            'version'     => 'latest',
            'credentials' => $credentials,
            'use_path_style_endpoint' => $S3FPS,
        ]);
        $result = $s3Client->putObject([
            'Bucket'     => $bucket,
            'Key'        => $key,
            'SourceFile' => $srcFilePath,
            'ContentType'	=> $mime,
        ]);
        if($result){
            $url = AMS3_BASE_URLS . '/' . $key;
            return $url;
        }else{
            actionLog(null, "error", "uploadAmazonS3", null, "アップロードに失敗しました", 4); 
            return false;
        }
    } catch (Aws\S3\Exception\S3Exception $e) {
        actionLog(null, "error", "uploadAmazonS3", null, $e->getMessage(), 4); 
        return false;
    }
}

function deleteAmazonS3($url){
    $key = explode("/", mb_substr(parse_url($url, PHP_URL_PATH), 1));
    if ($key[0] == AMS3_BUCKET_NM) {
        array_shift($key);
    }
    $key = implode("/", $key);

    $credentials = [
        'key'    => AMS3_ACCESSKEY,
        'secret' => AMS3_SECRETKEY,
    ];

    $bucket        = AMS3_BUCKET_NM;
    if(AMS3_IS_S3FPS_ == 'true'){
        $S3FPS = true;
    }else{
        $S3FPS = false;
    }

    try {
        $s3Client = new Aws\S3\S3Client([
            'endpoint'    => AMS3_ENDPOINTS,
            'region'      => AMS3_REGION_NM,
            'version'     => 'latest',
            'credentials' => $credentials,
            'use_path_style_endpoint' => $S3FPS,
        ]);
        $is_hasfile = $s3Client->doesObjectExistV2($bucket, $key, false, []);
        if($is_hasfile == true){
            $result = $s3Client->deleteObject([
                'Bucket'     => $bucket,
                'Key'        => $key
            ]);
            if($result){
                return true;
            }else{
                actionLog(null, "error", "deleteAmazonS3", null, "削除に失敗しました", 4); 
                return false;
            }
        }else{
            actionLog(null, "error", "deleteAmazonS3", null, $key."が既に削除されていました", 1); 
            return true;
        }
    } catch (Aws\S3\Exception\S3Exception $e) {
        actionLog(null, "error", "deleteAmazonS3", null, $e->getMessage(), 4); 
        return false;
    }
}

//文字装飾・URL変換など
function processMarkdownAndWrapEmptyLines($markdownText) {
    $placeholders = [];

    // インラインコードをプレースホルダーに置き換える
    $markdownText = preg_replace_callback('/`([^`\n]+)`/', function($matches) use (&$placeholders) {
        $placeholder = 'PLACEHOLDER_' . count($placeholders);
        $placeholders[$placeholder] = '<span class="inline">' . $matches[1] . '</span>';
        return $placeholder;
    }, $markdownText);

    // ここから先の処理はインラインコードとコードブロックに影響しない

    $markdownText = preg_replace('/\[\[buruburu (.+)\]\]/m', '<span class="buruburu">$1</span>', $markdownText);//ぶるぶる

    $markdownText = preg_replace_callback('/\[\[time (\d+)\]\]/m', function($matches) {
        $timestamp = $matches[1];
        return '<span class="unixtime" title="'.date("Y/m/d H:i.s", htmlentities($timestamp, ENT_QUOTES, 'UTF-8', false)).'">' . date("Y/m/d H:i", htmlentities($timestamp, ENT_QUOTES, 'UTF-8', false)) . '</span>';
    }, $markdownText);

    //太字&斜体------------------------------------------------------------------------
    $markdownText = preg_replace('/\*\*\*(.+)\*\*\*(?=\s)/', '<b><i>$1</i></b>', $markdownText);//太字&斜体の全部のせセット
    $markdownText = preg_replace('/\*\*\*(.+)\*\*\*/', '<b><i>$1</i></b>', $markdownText);//太字&斜体の全部のせセット

    $markdownText = preg_replace('/\_\_\_(.+)\_\_\_(?=\s)/', '<b><i>$1</i></b>', $markdownText);//太字&斜体の全部のせセット
    $markdownText = preg_replace('/\b\_\_\_(.+)\_\_\_\b/', '<b><i>$1</i></b>', $markdownText);//太字&斜体の全部のせセット

    //太字-----------------------------------------------------------------------------
    $markdownText = preg_replace('/\*\*(.+)\*\*/', '<b>$1</b>', $markdownText);//太字
    $markdownText = preg_replace('/\b\*\*(.+)\*\*\b/', '<b>$1</b>', $markdownText);//太字

    $markdownText = preg_replace('/\_\_(.+)\_\_(?=\s)/', '<b>$1</b>', $markdownText);//太字
    $markdownText = preg_replace('/\b\_\_(.+)\_\_\b/', '<b>$1</b>', $markdownText);//太字

    //斜体-----------------------------------------------------------------------------
    $markdownText = preg_replace('/\*(.+)\*/', '<i>$1</i>', $markdownText);//斜体
    $markdownText = preg_replace('/\b\*(.+)\*\b/', '<i>$1</i>', $markdownText);//斜体

    $markdownText = preg_replace('/\_(.+)\_(?=\s)/', '<i>$1</i>', $markdownText);//斜体
    $markdownText = preg_replace('/\b\_(.+)\_\b/', '<i>$1</i>', $markdownText);//斜体

    $markdownText = preg_replace('/\~\~(.+)\~\~/m', '<s>$1</s>', $markdownText);//打ち消し線

    $markdownText = preg_replace('/&gt;&gt;&gt; (.+)/m', '<span class="quote">$1</span>', $markdownText);//>>> 引用

    $markdownText = preg_replace('/\|\|(.+)\|\|/m', '<span class="blur">$1</span>', $markdownText);//黒塗り

    // タイトル（#、##、###）をHTMLのhタグに変換
    $markdownText = preg_replace('/^# (.+)/m', '<h1>$1</h1>', $markdownText);
    $markdownText = preg_replace('/^## (.+)/m', '<h2>$1</h2>', $markdownText);
    $markdownText = preg_replace('/^### (.+)/m', '<h3>$1</h3>', $markdownText);

    // 箇条書き（-）をHTMLのul/liタグに変換
    $markdownText = preg_replace('/^- (.+)/m', '<p>・ $1</p>', $markdownText);

    // 空行の前に何もない行をHTMLのpタグに変換
    $markdownText = preg_replace('/(^\s*)(?!\s)(.*)/m', '$1<p>$2</p>', $markdownText);

    // プレースホルダーを元のコードに戻す
    foreach ($placeholders as $placeholder => $original) {
        $markdownText = str_replace($placeholder, $original, $markdownText);
    }

    return $markdownText;
}

//Profile
function replaceProfileEmojiImages($postText) {
    $postText = str_replace('&#039;', '\'', $postText);
    // プロフィール名で絵文字名（:emoji:）を検出して画像に置き換える
    $emojiPattern = '/:(\w+):/';
    $postTextWithImages = preg_replace_callback($emojiPattern, function($matches) {
        $emojiName = $matches[1];
        //絵文字path取得
        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));
        $emoji_Query = $dbh->prepare("SELECT emojifile, emojiname FROM emoji WHERE emojiname = :emojiname");
        $emoji_Query->bindValue(':emojiname', $emojiName);
        $emoji_Query->execute();
        $emoji_row = $emoji_Query->fetch();
        if(empty($emoji_row["emojifile"])){
            $emoji_path = "img/sysimage/errorimage/emoji_404.png";
        }else{
            $emoji_path = $emoji_row["emojifile"];
        }
        return "<img src='../".$emoji_path."' alt=':$emojiName:' title=':$emojiName:'>";
    }, $postText);
    return $postTextWithImages;
}
// ユーズ内の絵文字やhashtagを画像に置き換える
function replaceEmojisWithImages($postText) { 
    $postText = str_replace('&#039;', '\'', $postText); 

    $emojiPattern = '/:(\w+):/'; 
    $postTextWithImages = preg_replace_callback($emojiPattern, function($matches) { 
        $emojiName = $matches[1]; 
        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array( 
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, 
        )); 
        $emoji_Query = $dbh->prepare("SELECT emojifile, emojiname FROM emoji WHERE emojiname = :emojiname"); 
        $emoji_Query->bindValue(':emojiname', $emojiName); 
        $emoji_Query->execute(); 
        $emoji_row = $emoji_Query->fetch(); 
        if(empty($emoji_row["emojifile"])){ 
            $emoji_path = "img/sysimage/errorimage/emoji_404.png"; 
            return ":".$emojiName.":"; 
        }else{ 
            $emoji_path = $emoji_row["emojifile"]; 
            return "<img src='../".$emoji_path."' alt=':$emojiName:' title=':$emojiName:'>"; 
        } 
    }, $postText); 

    $urlPattern = '/https?:\/\/[^\s]+/';
    $urlPlaceholders = [];
    $postTextWithPlaceholders = preg_replace_callback($urlPattern, function($matches) use (&$urlPlaceholders) {
        $placeholder = 'URL_PLACEHOLDER_' . count($urlPlaceholders);
        $urlPlaceholders[$placeholder] = $matches[0];
        return $placeholder;
    }, $postTextWithImages);

    $usernamePattern = '/@(\w+)/';
    $postTextWithUsernames = preg_replace_callback($usernamePattern, function($matches) { 
        $username = $matches[1]; 

        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array( 
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, 
        )); 

        $mentionsuserQuery = $dbh->prepare("SELECT username, userid FROM account WHERE userid = :userid"); 
        $mentionsuserQuery->bindValue(':userid', $username); 
        $mentionsuserQuery->execute(); 
        $mentionsuserData = $mentionsuserQuery->fetch();    

        if(empty($mentionsuserData)){ 
            return "@".$username.""; 
        }else{ 
            return "<a class='mta' href='/@".htmlentities($mentionsuserData["userid"], ENT_QUOTES, 'UTF-8', false)."'>@".replaceProfileEmojiImages(htmlentities($mentionsuserData["username"], ENT_QUOTES, 'UTF-8', false))."</a>"; 
        } 
    }, $postTextWithPlaceholders);

    $postTextWithUrlsRestored = str_replace(array_keys($urlPlaceholders), array_values($urlPlaceholders), $postTextWithUsernames);

    $hashtagsPattern = '/#([\p{Han}\p{Hiragana}\p{Katakana}A-Za-z0-9ー_!]+)/u'; 
    $postTextWithHashtags = preg_replace_callback($hashtagsPattern, function($matches) { 
        $hashtags = $matches[1]; 
        return "<a class='hashtags' href='/search?q=" . urlencode('#' . $hashtags) . "'>" . '#' . $hashtags . "</a>"; 
    }, $postTextWithUrlsRestored);

    return $postTextWithHashtags;
}
function replaceURLsWithLinks($postText, $maxLength = 48) {
    $pattern = '/(https:\/\/[\w!?\/+\-_~;.,*&@#$%()+|https:\/\/[ぁ-んァ-ヶ一-龠々\w\-\/?=&%.]+)/';
    $convertedText = preg_replace_callback($pattern, function($matches) use ($maxLength) {
        $link = $matches[0];
        if(!(preg_match('/:(\w+):/',$link))){
            $no_https_link = preg_replace('/https:\/\//', '', $link, 1);
            if (mb_strlen($link) > $maxLength) {
                $truncatedLink = mb_substr($no_https_link, 0, $maxLength).'...';
                return '<a href="'.$link.'" target="_blank">'.$truncatedLink.'</a>';
            } else {
                return '<a href="'.$link.'" target="_blank">'.$no_https_link.'</a>';
            }   
        }else{
            return $link;
        }
    }, $postText);

    return $convertedText;
}
function YouTube_and_nicovideo_Links($postText) {
    // URLを正規表現を使って検出
    $pattern = '/(https:\/\/[^\s<>\[\]\'"]+)/';  // 改良された正規表現
    preg_match_all($pattern, $postText, $matches);

    if(empty($url)){
        $postText = "";
    }

    // 検出したURLごとに処理を行う
    foreach ($matches[0] as $url) {
        // ドメイン部分を抽出
        $parsedUrl = parse_url($url);
        if(!(empty($parsedUrl['host']))){
            $video_time = "0";
            $video_id = "";

            if($parsedUrl['host'] == "youtube.com" || $parsedUrl['host'] == "youtu.be" || $parsedUrl['host'] == "www.youtube.com" || $parsedUrl['host'] == "m.youtube.com"){
                if (isset($parsedUrl['query'])) {
                    // クエリ部分を連想配列に変換する
                    parse_str($parsedUrl['query'], $queryParams);

                    // video_idの取得
                    if (isset($queryParams['v'])) {
                        $video_id = safetext($queryParams['v']);
                        $iframe = true;
                    } else {
                        $video_id = str_replace('/', '', safetext($parsedUrl['path']));
                        $iframe = true;
                    }
                    // video_timeの取得
                    if (isset($queryParams['amp;t'])) {
                        $video_time = safetext($queryParams['amp;t']);
                        if(!(is_numeric($video_time))){
                            $video_time = "0";
                        }
                    } else {
                        $video_time = "0";
                    }
                    $video_id = str_replace('&amp;', '?', $video_id);
                } elseif (isset($parsedUrl['path'])) {
                    if (preg_match('/^\/watch\/|^\/embed\/|^\/shorts\/|^\/v\/|\//', $parsedUrl['path'])) {
                        $video_id = str_replace('/', '', htmlentities($parsedUrl['path'], ENT_QUOTES, 'UTF-8', false));
                        $video_time = 0;
                        $iframe = true;
                    } else {
                        // チャンネルや他のパスの場合は動画IDを取得しない
                        $video_id = "";
                        $video_time = 0;
                        $iframe = false;
                    }
                } else {
                    $video_id = "";
                    $video_time = "0";
                    $iframe = false;
                }

                // 不要な文字を削除してaタグを生成
                if ($iframe) {
                    $link = '<iframe src="https://www.youtube-nocookie.com/embed/'.$video_id.'?start='.$video_time.'" rel="0" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
                } else {
                    $link = "";
                }

                // URLをドメインのみを表示するaタグで置き換え
                $postText = $link;
            }elseif($parsedUrl['host'] == "nicovideo.jp" || $parsedUrl['host'] == "www.nicovideo.jp" || $parsedUrl['host'] == "nico.ms"){
                if(isset($parsedUrl['path'])){
                    $video_id = str_replace('/','',str_replace('/watch/', '', safetext($parsedUrl['path'])));
                    $iframe = true;
                }else{
                    $video_id = "";
                    $iframe = false;
                }
                if (isset($parsedUrl['query'])) {
                    // クエリ部分を連想配列に変換する
                    parse_str($parsedUrl['query'], $queryParams);

                    // video_timeの取得
                    if (isset($queryParams['from'])) {
                        $video_time = safetext($queryParams['from']);
                        if(!(is_numeric($video_time))){
                            $video_time = "0";
                        }
                    } else {
                        $video_time = "0";
                    }
                }
                // 不要な文字を削除してaタグを生成
                if($iframe == true){
                    $link = '<iframe src="https://embed.nicovideo.jp/watch/'.$video_id.'?from='.$video_time.'"</iframe>';
                }else{
                    $link = "";
                }
                // URLをドメインのみを表示するaタグで置き換え
                $postText = $link;
            }else{
                $postText = "";
            }
        }
    }

    return $postText;
}

function to_null($value) {
    $null_conditions = [
        "ueuse"  => "",
        "photo1" => "none",
        "photo2" => "none",
        "photo3" => "none",
        "photo4" => "none",
        "video1" => "none",
        "rpuniqid" => "",
        "ruuniqid" => "",
        "abi" => "none",
    ];
    
    foreach ($null_conditions as $key => $invalid_value) {
        if (isset($value[$key]) && $value[$key] === $invalid_value) {
            $value[$key] = null;
        }
    }

    return $value;
}

function UserAgent_to_Device($useragent) {
    if(preg_match('/Windows\sNT\s10.0/', $useragent)) {
        $device = "Windows 10/11";
    }elseif(preg_match('/Windows\sNT\s6.3/', $useragent)) {
        $device = "Windows 8.1";
    }elseif(preg_match('/Windows\sNT\s6.2/', $useragent)) {
        $device = "Windows 8";
    }elseif(preg_match('/Windows\sNT\s6.1/', $useragent)) {
        $device = "Windows 7";
    }elseif(preg_match('/Windows\sNT\s6.0/', $useragent)) {
        $device = "Windows Vista";
    }elseif(preg_match('/Windows\sNT\s5.2/', $useragent)) {
        $device = "Windows XP";
    }elseif(preg_match('/Windows\sNT\s5.1/', $useragent)) {
        $device = "Windows XP";
    }elseif(preg_match('/Windows\sPhone/', $useragent)) {
        $device = "Windows Phone";
    }elseif(preg_match('/iPhone/', $useragent)) {
        $device = "iPhone";
    }elseif(preg_match('/iPad/', $useragent)) {
        $device = "iPad";
    }elseif(preg_match('/iPod\stouch/', $useragent)) {
        $device = "iPod touch";
    }elseif(preg_match('/Mac\sOS\sX/', $useragent)) {
        $device = "macOS";
    }elseif(preg_match('/Android/', $useragent)) {
        $device = "Android";
    }elseif(preg_match('/BlackBerry/', $useragent)) {
        $device = "BlackBerry";
    }elseif(preg_match('/Linux/', $useragent)) {
        $device = "Linux";
    }elseif(preg_match('/Nintendo\sWiiU/', $useragent)) {
        $device = "Nintendo WiiU";
    }elseif(preg_match('/PlayStation\s4/', $useragent)) {
        $device = "PlayStation 4";
    }elseif(preg_match('/PlayStation\s5/', $useragent)) {
        $device = "PlayStation 5";
    }elseif(preg_match('/Nintendo\sSwitch/', $useragent)) {
        $device = "Nintendo Switch";
    }elseif(preg_match('/Nintendo\s3DS/', $useragent)) {
        $device = "Nintendo 3DS";
    }else{
        $device = "Others";
    }
    return $device;
}
function File_MaxUploadSize(){
    $memory_max = ini_get('memory_limit');
    $post_max = ini_get('post_max_size');
    $upload_max = ini_get('upload_max_filesize');
    if(!($memory_max == "-1")){
        $memory_max_s = return_bytes($memory_max);
    }else{
        $memory_max_s = PHP_INT_MAX;
    }
    if(!($post_max == "-1")){
        $post_max_s = return_bytes($post_max);
    }else{
        $post_max_s = PHP_INT_MAX;
    }
    if(!($upload_max == "-1")){
        $upload_max_s = return_bytes($upload_max);
    }else{
        $upload_max_s = PHP_INT_MAX;
    }

    if($memory_max_s >= $post_max_s){
        $maxsize = $post_max_s;
    }else{
        $maxsize = $memory_max_s;
    }

    if($maxsize >= $upload_max_s){
        $file_maxsize = $upload_max_s;
    }else{
        $file_maxsize = $maxsize;
    }
    return $file_maxsize;
}
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $num = (int)str_replace($last, '', strtolower($val));
    if($last == "g"){
        $num *= 1024 * 1024 * 1024;
    }else if($last == "m"){
        $num *= 1024 * 1024;
    }else if($last == "k"){
        $num *= 1024;
    }else if($last == ""){
        $num = $num;
    }
    return $num;
}
function x1024($byte){
	$n_mb = $byte / 1024;
    return round($n_mb, 1);
}
function uwuzu_ver($select,$path){
    $softwaredata = file_get_contents($path);

    $softwaredata = explode( "\n", $softwaredata );
    $cnt = count( $softwaredata );
    for( $i=0;$i<$cnt;$i++ ){
        $software_info[$i] = ($softwaredata[$i]);
    }
    if($select == "name"){
        $ret = $software_info[0];
    }elseif($select == "ver_"){
        $ret = $software_info[1];
    }elseif($select == "date"){
        $ret = $software_info[2];
    }elseif($select == "dev_"){
        $ret = $software_info[3];
    }else{
        $ret = "no_data";
    }
    return htmlentities($ret, ENT_QUOTES, 'UTF-8', false);
}
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
            $mentionedUsers[] = strtolower($mention_username);
        }
    }, $postText);

    return $mentionedUsers;
}
function GenNotificationId($to, $from, $title, $message, $url, $category) {
    $data = "" . $to . ":" . $from . ":" . $title . ":" . $message . ":" . $url . ":" . $category;
    return hash('sha3-512', $data);
}

function send_notification($to,$from,$title,$message,$url,$category){
    // データベースに接続
    try {
        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
    } catch(PDOException $e) {
        actionLog($from, "error", "send_notification", $to, $e, 4);
        return false;
    }

    if(!(strtolower($to) == strtolower($from)) || $category === "system" || $category === "other"){
        $to_result = getUserData($pdo, $to);

        $category_list = ["system","favorite","reply","reuse","ueuse","follow","mention","other", "login"];
        if(in_array($category, $category_list)){
            if(in_array($category, explode(',', $to_result["notification_settings"])) || empty($to_result["notification_settings"]) || $category === "system" || $category === "other"){
                //ブロックされてたら送らない
                if(!(in_array($from, explode(',', $to_result["blocklist"])))){
                    if(!(empty($pdo))){		
                        $pdo->beginTransaction();
                        try {
                            $fromuserid = safetext($from);
                            $touserid = safetext($to);
                            $datetime = date("Y-m-d H:i:s");
                            $msg = safetext($message);
                            $title = safetext($title);
                            $url = safetext($url);
                            $userchk = 'none';
                            $notification_category = safetext($category);
                            $notification_id = GenNotificationId($touserid, $fromuserid, $title, $msg, $url, $notification_category);
                    
                            // 通知用SQL作成
                            $stmt = $pdo->prepare("INSERT INTO notification (fromuserid, touserid, msg, url, datetime, userchk, title, category, notificationid) VALUES (:fromuserid, :touserid, :msg, :url, :datetime, :userchk, :title, :category, :notificationid)");
                    
                            $stmt->bindParam(':fromuserid', $fromuserid, PDO::PARAM_STR);
                            $stmt->bindParam(':touserid', $touserid, PDO::PARAM_STR);
                            $stmt->bindParam(':msg', $msg, PDO::PARAM_STR);
                            $stmt->bindParam(':url', $url, PDO::PARAM_STR);
                            $stmt->bindParam(':userchk', $userchk, PDO::PARAM_STR);
                            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                            $stmt->bindParam(':category', $notification_category, PDO::PARAM_STR);
                            $stmt->bindParam(':notificationid', $notification_id, PDO::PARAM_STR);
                    
                            $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);
                    
                            $res = $stmt->execute();
                    
                            $res = $pdo->commit();
                    
                            if($res){
                                return true;
                            }else{
                                $pdo->rollBack();
                                actionLog($from, "error", "send_notification", $to, "通知の送信に失敗しました(rollBack)", 3);
                                return false;
                            }
                    
                        } catch(Exception $e) {
                            $pdo->rollBack();
                            actionLog($from, "error", "send_notification", $to, $e, 4);
                            return false;
                        }
                    }else{
                        return false;
                    }
                }else{
                    return true;
                }
            }else{
                // 受信しない設定なのでtrue
                return true;
            }
        }else{
            return false;
        }
    }else{
        // 送信元と送信先が同じなら送信しない
        return true;
    }
}

function delete_notification($to,$from,$title,$message,$url,$category){
    // データベースに接続
    try {
        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
    } catch(PDOException $e) {
        actionLog($from, "error", "send_notification", $to, $e, 4);
        return false;
    }

    if(!(strtolower($to) == strtolower($from)) || $category === "system" || $category === "other"){
        $to_result = getUserData($pdo, $to);

        $category_list = ["system","favorite","reply","reuse","ueuse","follow","mention","other", "login"];
        if(in_array($category, $category_list)){
            if(in_array($category, explode(',', $to_result["notification_settings"])) || empty($to_result["notification_settings"]) || $category === "system" || $category === "other"){
                if(!(empty($pdo))){		
                    $fromuserid = safetext($from);
                    $touserid = safetext($to);
                    $msg = safetext($message);
                    $title = safetext($title);
                    $url = safetext($url);
                    $notification_category = safetext($category);
                    $notification_id = GenNotificationId($touserid, $fromuserid, $title, $msg, $url, $notification_category);
                    $pdo->beginTransaction();
                    try {
                        // 削除クエリを実行
                        $deleteQuery = $pdo->prepare("DELETE FROM notification WHERE notificationid = :notificationid");
                        $deleteQuery->bindValue(':notificationid', $notification_id, PDO::PARAM_STR);
                        $res = $deleteQuery->execute();
                        
                        if ($res) {
                            $res = $pdo->commit();
                            return true;
                        } else {
                            $pdo->rollBack();
                            actionLog($from, "error", "delete_notification", $to, "通知の削除に失敗しました(rollBack)", 3);
                            return false;
                        }
                    } catch(PDOException $e) {
                        $pdo->rollBack();
                        actionLog($from, "error", "delete_notification", $to, $e, 4);
                        return false;
                    }
                }else{
                    return false;
                }
            }else{
                // 受信しない設定なのでtrue
                return true;
            }
        }else{
            return false;
        }
    }else{
        // 送信元と送信先が同じなら送信しない
        return true;
    }
}
// ユーズするとき全部この関数
function send_ueuse($userid,$rpUniqid,$ruUniqid,$ueuse,$photo1,$photo2,$photo3,$photo4,$video1,$nsfw,$aibwm){
    // AIBlockWaterMark--------------------------------------------
    require_once(__DIR__ . '/../settings_admin/plugin_settings/aiblockwatermark_settings.php');
    //------------------------------------------------------
    if ($aibwm === true && !empty(AIBWM_CHK) && AIBWM_CHK == "true") {
        if (file_exists(__DIR__ . '/../plugin/AIBlockWaterMark/aiblockwatermark.php')) {
            require(__DIR__ . '/../plugin/AIBlockWaterMark/aiblockwatermark.php');
        }
    }
    //------------------------------------------------------
    if (file_exists(__DIR__ . '/../settings_admin/plugin_settings/amazons3_settings.php')) {
        require_once(__DIR__ . '/../settings_admin/plugin_settings/amazons3_settings.php');
        if (defined('AMS3_CHKS') && AMS3_CHKS == "true") {
            if (file_exists(__DIR__ . '/../plugin/aws/aws-autoloader.php')) {
                require_once(__DIR__ . '/../plugin/aws/aws-autoloader.php');
            } else {
                actionLog(null, "error", "uploadAmazonS3", null, "AWS SDK for PHPが見つかりませんでした！", 4);
            }
        }
    } else {
        actionLog(null, "error", "uploadAmazonS3", null, "amazons3_settings.phpが見つかりませんでした！", 3);
    }

    $rpUniqid = safetext($rpUniqid);
    $ruUniqid = safetext($ruUniqid);
    $userid = safetext($userid);
    $ueuse = safetext($ueuse);
    $nsfw = safetext($nsfw);

    $error_message = array();
    $mojisizefile = __DIR__ . "/../server/textsize.txt";

    //投稿及び返信レート制限↓(分):デフォで60件/分まで
    if(!((int)RATE_LM === -1)){
        $max_ueuse_rate_limit = (int)RATE_LM;
    }else{
        $max_ueuse_rate_limit = PHP_INT_MAX;
    }

    $banurldomainfile = __DIR__ . "/../server/banurldomain.txt";
    $banurl_info = file_get_contents($banurldomainfile);
    $banurl = array_filter(preg_split("/\r\n|\n|\r/", $banurl_info));


    // データベースに接続
    try {
        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
    } catch(PDOException $e) {
        actionLog($userid, "error", "send_ueuse", null, $e, 4);
        return [false, "DB_ERROR"];
    }

    if(!(empty($pdo))){		
        $uniqid = createUniqId();//最初に決めちゃう
        if(empty(getUeuseData($pdo, $uniqid))){

            $userData = getUserData($pdo, $userid);
            $username = safetext($userData["username"]);
            $userRoleList = explode(',', safetext($userData["role"]));
            if(in_array("ice", $userRoleList)){
                $error_message[] = 'アカウントが凍結されています。(ACCOUNT_HAS_BEEN_FROZEN)';
            }
            $ueuse = safetext($ueuse);
            if(safetext($nsfw) === "true"){
                $save_nsfw = "true";
            }else{
                $save_nsfw = "false";
            }
            if(empty($ueuse) && empty($ruUniqid)) {
                $error_message[] = '内容を入力してください。(INPUT_PLEASE)';
            } else {
                // 文字数を確認
                if((int)safetext(file_get_contents($mojisizefile)) < mb_strlen($ueuse, 'UTF-8')) {
                    $error_message[] = '内容は'.safetext(file_get_contents($mojisizefile)).'文字以内で入力してください。(INPUT_OVER_MAX_COUNT)';
                }

                // 禁止url確認
                if(!(empty($banurl))){
                    for($i = 0; $i < count($banurl); $i++) {
                        if(!($banurl[$i] == "")){
                            if (false !== strpos($ueuse, 'https://'.$banurl[$i])) {
                                $error_message[] = '投稿が禁止されているURLが含まれています。(INPUT_CONTAINS_PROHIBITED_URL)';
                            }
                        }
                    }
                }
                
                // 改行ユーズ確認
                if(preg_match('/^[\n\r]+$/', $ueuse) === 1){
                    $error_message[] = '内容を入力してください。(INPUT_PLEASE)';
                }
            }

            $old_datetime = date("Y-m-d H:i:00");
            $now_datetime = date("Y-m-d H:i:00",strtotime("+1 minute"));
            $rate_Query = $pdo->prepare("SELECT * FROM ueuse WHERE account = :userid AND TIME(datetime) BETWEEN :old_datetime AND :now_datetime");
            $rate_Query->bindValue(':userid', $userid);
            $rate_Query->bindValue(':old_datetime', $old_datetime);
            $rate_Query->bindValue(':now_datetime', $now_datetime);
            $rate_Query->execute();
            $rate_count = $rate_Query->rowCount();
            if(!($rate_count > $max_ueuse_rate_limit-1)){
                if(empty($error_message)) {   
                    if (empty($photo1['name'])) {
                        $save_photo1 = "none";
                    } else {
                        // アップロードされたファイル情報
                        $uploadedFile = $photo1;

                        if(!(empty($uploadedFile['tmp_name']))){
                            if(check_mime($uploadedFile['tmp_name'])){
                                // アップロードされたファイルの拡張子を取得
                                $extension = convert_mime(check_mime($uploadedFile['tmp_name']));
                                delete_exif($extension, $uploadedFile['tmp_name']);
                                if($aibwm === true){
                                    AIBlockWaterMark($uploadedFile['tmp_name'], $userid);
                                }
                                if(AMS3_CHKS == "true"){
                                    $s3result = uploadAmazonS3($uploadedFile['tmp_name']);
                                }else{
                                    // 新しいファイル名を生成（uniqid + 拡張子）
                                    $newFilename = createUniqId() . '-'.$userid.'.' . $extension;
                                    // 保存先のパスを生成
                                    $uploadedPath = '../ueuseimages/' . $newFilename;
                                    // ファイルを移動
                                    $result = move_uploaded_file($uploadedFile['tmp_name'], __DIR__."/".$uploadedPath);
                                    
                                    if ($result) {
                                        $save_photo1 = $uploadedPath; // 保存されたファイルのパスを使用
                                    } else {
                                        $errnum = $uploadedFile['error'];
                                        if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
                                        if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
                                        if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
                                        if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
                                        if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
                                        if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
                                        if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
                                        $error_message[] = 'アップロード失敗！(1)エラーコード：' .$errcode.'';
                                    }
                                }
                                if(isset($s3result)){
                                    if($s3result == false){
                                        $error_message[] = 'アップロード失敗！(1)エラーコード： S3ERROR';
                                    }else{
                                        $save_photo1 = $s3result; // S3に保存されたファイルのパスを使用
                                    }
                                }
                            }else{
                                $error_message[] = "使用できない画像形式です。(SORRY_FILE_HITAIOU)";
                            }
                        }else{
                            $error_message[] = "ファイルがアップロードできませんでした。(FILE_UPLOAD_DEKINAKATTA)";
                        }
                    }

                    if (empty($photo2['name'])) {
                        $save_photo2 = "none";
                    } else {
                        if (empty($photo1['name'])){
                            $error_message[] = '画像1から画像を選択してください！！！(PHOTO_SELECT_PLEASE)';
                        }
                        // アップロードされたファイル情報
                        $uploadedFile2 = $photo2;

                        if(!(empty($uploadedFile2['tmp_name']))){
                            if(check_mime($uploadedFile2['tmp_name'])){
                                // アップロードされたファイルの拡張子を取得
                                $extension2 = convert_mime(check_mime($uploadedFile2['tmp_name']));
                                delete_exif($extension2, $uploadedFile2['tmp_name']);
                                if($aibwm === true){
                                    AIBlockWaterMark($uploadedFile2['tmp_name'], $userid);
                                }
                                if(AMS3_CHKS == "true"){
                                    $s3result = uploadAmazonS3($uploadedFile2['tmp_name']);
                                }else{
                                    // 新しいファイル名を生成（uniqid + 拡張子）
                                    $newFilename2 = createUniqId() . '-'.$userid.'.' . $extension2;
                                    // 保存先のパスを生成
                                    $uploadedPath2 = '../ueuseimages/' . $newFilename2;
                                    // ファイルを移動
                                    $result2 = move_uploaded_file($uploadedFile2['tmp_name'], __DIR__."/".$uploadedPath2);
                                    if ($result2) {
                                        $save_photo2 = $uploadedPath2; // 保存されたファイルのパスを使用
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
                                if(isset($s3result)){
                                    if($s3result == false){
                                        $error_message[] = 'アップロード失敗！(2)エラーコード： S3ERROR';
                                    }else{
                                        $save_photo2 = $s3result; // S3に保存されたファイルのパスを使用
                                    }
                                }
                            }else{
                                $error_message[] = "使用できない画像形式です。(SORRY_FILE_HITAIOU)";
                            }
                        }else{
                            $error_message[] = "ファイルがアップロードできませんでした。(FILE_UPLOAD_DEKINAKATTA)";
                        }
                    }

                    if (empty($photo3['name'])) {
                        $save_photo3 = "none";
                    } else {
                        if (empty($photo2['name'])){
                            $error_message[] = '画像2から画像を選択してください！！！(PHOTO_SELECT_PLEASE)';
                        }
                        // アップロードされたファイル情報
                        $uploadedFile3 = $photo3;

                        if(!(empty($uploadedFile3['tmp_name']))){
                            if(check_mime($uploadedFile3['tmp_name'])){
                                // アップロードされたファイルの拡張子を取得
                                $extension3 = convert_mime(check_mime($uploadedFile3['tmp_name']));
                                delete_exif($extension3, $uploadedFile3['tmp_name']);
                                if($aibwm === true){
                                    AIBlockWaterMark($uploadedFile3['tmp_name'], $userid);
                                }
                                if(AMS3_CHKS == "true"){
                                    $s3result = uploadAmazonS3($uploadedFile3['tmp_name']);
                                }else{
                                    // 新しいファイル名を生成（uniqid + 拡張子）
                                    $newFilename3 = createUniqId() . '-'.$userid.'.' . $extension3;
                                    // 保存先のパスを生成
                                    $uploadedPath3 = '../ueuseimages/' . $newFilename3;
                                    // ファイルを移動
                                    $result3 = move_uploaded_file($uploadedFile3['tmp_name'], __DIR__."/".$uploadedPath3);
                                    if ($result3) {
                                        $save_photo3 = $uploadedPath3; // 保存されたファイルのパスを使用
                                    } else {
                                        $errnum = $uploadedFile3['error'];
                                        if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
                                        if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
                                        if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
                                        if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
                                        if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
                                        if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
                                        if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
                                        $error_message[] = 'アップロード失敗！(3)エラーコード：' .$errcode.'';
                                    }
                                }
                                if(isset($s3result)){
                                    if($s3result == false){
                                        $error_message[] = 'アップロード失敗！(3)エラーコード： S3ERROR';
                                    }else{
                                        $save_photo3 = $s3result; // S3に保存されたファイルのパスを使用
                                    }
                                }
                            }else{
                                $error_message[] = "使用できない画像形式です。(SORRY_FILE_HITAIOU)";
                            }
                        }else{
                            $error_message[] = "ファイルがアップロードできませんでした。(FILE_UPLOAD_DEKINAKATTA)";
                        }
                    }

                    if (empty($photo4['name'])) {
                        $save_photo4 = "none";
                    } else {
                        if (empty($photo3['name'])){
                            $error_message[] = '画像3から画像を選択してください！！！(PHOTO_SELECT_PLEASE)';
                        }
                        // アップロードされたファイル情報
                        $uploadedFile4 = $photo4;
                        if(!(empty($uploadedFile4['tmp_name']))){
                            if(check_mime($uploadedFile4['tmp_name'])){
                                // アップロードされたファイルの拡張子を取得
                                $extension4 = convert_mime(check_mime($uploadedFile4['tmp_name']));
                                delete_exif($extension4, $uploadedFile4['tmp_name']);
                                if($aibwm === true){
                                    AIBlockWaterMark($uploadedFile4['tmp_name'], $userid);
                                }
                                if(AMS3_CHKS == "true"){
                                    $s3result = uploadAmazonS3($uploadedFile4['tmp_name']);
                                }else{
                                    // 新しいファイル名を生成（uniqid + 拡張子）
                                    $newFilename4 = createUniqId() . '-'.$userid.'.' . $extension4;
                                    // 保存先のパスを生成
                                    $uploadedPath4 = '../ueuseimages/' . $newFilename4;
                                    // ファイルを移動
                                    $result4 = move_uploaded_file($uploadedFile4['tmp_name'], __DIR__."/".$uploadedPath4);  
                                    if ($result4) {
                                        $save_photo4 = $uploadedPath4; // 保存されたファイルのパスを使用
                                    } else {
                                        $errnum = $uploadedFile4['error'];
                                        if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
                                        if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
                                        if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
                                        if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
                                        if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
                                        if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
                                        if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
                                        $error_message[] = 'アップロード失敗！(4)エラーコード：' .$errcode.'';
                                    }
                                }
                                if(isset($s3result)){
                                    if($s3result == false){
                                        $error_message[] = 'アップロード失敗！(1)エラーコード： S3ERROR';
                                    }else{
                                        $save_photo4 = $s3result; // S3に保存されたファイルのパスを使用
                                    }
                                }
                            }else{
                                $error_message[] = "使用できない画像形式です。(SORRY_FILE_HITAIOU)";
                            }
                        }else{
                            $error_message[] = "ファイルがアップロードできませんでした。(FILE_UPLOAD_DEKINAKATTA)";
                        }
                    }

                    if (empty($video1['name'])) {
                        $save_video1 = "none";
                    } else {
                        // アップロードされたファイル情報
                        $uploadedVideo = $video1;

                        if(!(empty($uploadedVideo['tmp_name']))){
                            if(check_mime_video($uploadedVideo['tmp_name'])){
                                if(AMS3_CHKS == "true"){
                                    $s3result = uploadAmazonS3($uploadedVideo['tmp_name']);
                                }else{
                                    // アップロードされたファイルの拡張子を取得
                                    $extensionVideo = convert_mime(check_mime_video($uploadedVideo['tmp_name']));
                                    // 正しい拡張子の場合、新しいファイル名を生成
                                    $newFilenameVideo = createUniqId() . '-'.$userid.'.' . $extensionVideo;
                                    // 保存先のパスを生成
                                    $uploadedPathVideo = '../ueusevideos/' . $newFilenameVideo;
                                    // ファイルを移動
                                    $resultVideo = move_uploaded_file($uploadedVideo['tmp_name'], __DIR__."/".$uploadedPathVideo);
                                    if ($resultVideo) {
                                        $save_video1 = $uploadedPathVideo; // 保存されたファイルのパスを使用
                                    } else {
                                        $errnum = $uploadedVideo['error'];
                                        if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
                                        if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
                                        if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
                                        if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
                                        if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
                                        if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
                                        if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
                                        $error_message[] = 'アップロード失敗！(5)エラーコード：' .$errcode.'';
                                    }
                                }
                                if(isset($s3result)){
                                    if($s3result == false){
                                        $error_message[] = 'アップロード失敗！(5)エラーコード： S3ERROR';
                                    }else{
                                        $save_video1 = $s3result; // S3に保存されたファイルのパスを使用
                                    }
                                }
                            } else {
                                $error_message[] = '対応していないファイル形式です！(SORRY_FILE_HITAIOU)';
                            }
                        }else{
                            $error_message[] = "ファイルがアップロードできませんでした。(FILE_UPLOAD_DEKINAKATTA)";
                        }
                    }

                    if(empty($error_message)) {                
                        // 書き込み日時を取得
                        $datetime = date("Y-m-d H:i:s");
                        $abi = "none";
                        $popularity = 0;
                        $mentionedUsers = array_unique(get_mentions_userid($ueuse));
                        $mentions = implode(",", $mentionedUsers);

                        if(empty($rpUniqid) && empty($ruUniqid)){
                            //-----------通常ユーズ-----------
                            // トランザクション開始
                            $pdo->beginTransaction();

                            try {

                                // SQL作成
                                $stmt = $pdo->prepare("INSERT INTO ueuse (username, account, uniqid, ueuse, photo1, photo2, photo3, photo4, video1, datetime, abi, nsfw, popularity, mentions) VALUES (:username, :account, :uniqid, :ueuse, :photo1, :photo2, :photo3, :photo4, :video1, :datetime, :abi, :nsfw, :popularity, :mentions)");
                        
                                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                                $stmt->bindParam(':account', $userid, PDO::PARAM_STR);
                                $stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
                                $stmt->bindParam(':ueuse', $ueuse, PDO::PARAM_STR);

                                $stmt->bindParam(':photo1', $save_photo1, PDO::PARAM_STR);
                                $stmt->bindParam(':photo2', $save_photo2, PDO::PARAM_STR);
                                $stmt->bindParam(':photo3', $save_photo3, PDO::PARAM_STR);
                                $stmt->bindParam(':photo4', $save_photo4, PDO::PARAM_STR);
                                $stmt->bindParam(':video1', $save_video1, PDO::PARAM_STR);
                                $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

                                $stmt->bindParam(':nsfw', $save_nsfw, PDO::PARAM_STR);
                                $stmt->bindParam(':popularity', $popularity, PDO::PARAM_INT);

                                $stmt->bindParam(':mentions', $mentions, PDO::PARAM_STR);

                                $stmt->bindParam(':abi', $abi, PDO::PARAM_STR);

                                // SQLクエリの実行
                                $res = $stmt->execute();

                                // コミット
                                $res = $pdo->commit();

                                foreach ($mentionedUsers as $mentionedUser) {
                                    send_notification($mentionedUser,$userid,"".$userid."さんにメンションされました！",$ueuse,"/!".$uniqid."", "mention");
                                }

                            } catch(Exception $e) {
                                // エラーが発生した時はロールバック
                                $pdo->rollBack();
                                actionLog($userid, "error", "send_ueuse", null, $e, 4);
                            }
                        }elseif((!empty($rpUniqid)) && empty($ruUniqid)){
                            //-----------リプライ-----------
                            $toUserIdQuery = $pdo->prepare("SELECT account FROM ueuse WHERE uniqid = :ueuseid ORDER BY datetime ASC LIMIT 1");
                            $toUserIdQuery->bindValue(':ueuseid', $rpUniqid, PDO::PARAM_STR);
                            $toUserIdQuery->execute();
                            $toUserId_res = $toUserIdQuery->fetch();    

                            if(!(empty($toUserId_res))){
                                $touserid = $toUserId_res["account"];
                            }else{
                                $touserid = null;
                            }

                            changePopularity($pdo, $rpUniqid, $userid, 3);
                            // トランザクション開始
                            $pdo->beginTransaction();
                            
                            try {
                                // SQL作成
                                $stmt = $pdo->prepare("INSERT INTO ueuse (username, account, uniqid, rpuniqid, ueuse, photo1, photo2, photo3, photo4, video1, datetime, abi, nsfw, popularity, mentions) VALUES (:username, :account, :uniqid, :rpuniqid, :ueuse, :photo1, :photo2, :photo3, :photo4, :video1, :datetime, :abi, :nsfw, :popularity, :mentions)");

                                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                                $stmt->bindParam(':account', $userid, PDO::PARAM_STR);
                                $stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
                                $stmt->bindParam(':rpuniqid', $rpUniqid, PDO::PARAM_STR);
                                $stmt->bindParam(':ueuse', $ueuse, PDO::PARAM_STR);

                                $stmt->bindParam(':photo1', $save_photo1, PDO::PARAM_STR);
                                $stmt->bindParam(':photo2', $save_photo2, PDO::PARAM_STR);
                                $stmt->bindParam(':photo3', $save_photo3, PDO::PARAM_STR);
                                $stmt->bindParam(':photo4', $save_photo4, PDO::PARAM_STR);
                                $stmt->bindParam(':video1', $save_video1, PDO::PARAM_STR);
                                $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

                                $stmt->bindParam(':nsfw', $save_nsfw, PDO::PARAM_STR);

                                $stmt->bindParam(':abi', $abi, PDO::PARAM_STR);
                                $stmt->bindParam(':popularity', $popularity, PDO::PARAM_INT);
                                $stmt->bindParam(':mentions', $mentions, PDO::PARAM_STR);

                                // SQLクエリの実行
                                $res = $stmt->execute();

                                // コミット
                                $res = $pdo->commit();

                                foreach ($mentionedUsers as $mentionedUser) {
                                    send_notification($mentionedUser,$userid,"".$userid."さんにメンションされました！",$ueuse,"/!".$uniqid."", "mention");
                                }

                                send_notification($touserid,$userid,"".$userid."さんが返信しました！",$ueuse,"/!".$uniqid."", "reply");
                            } catch(Exception $e) {
                                // エラーが発生した時はロールバック
                                $pdo->rollBack();
                                actionLog($userid, "error", "send_ueuse", null, $e, 4);
                            }
                        }elseif(empty($rpUniqid) && (!empty($ruUniqid))){
                            //-----------リユーズ-----------
                            $toUserIdQuery = $pdo->prepare("SELECT account FROM ueuse WHERE uniqid = :ueuseid ORDER BY datetime ASC LIMIT 1");
                            $toUserIdQuery->bindValue(':ueuseid', $ruUniqid, PDO::PARAM_STR);
                            $toUserIdQuery->execute();
                            $toUserId_res = $toUserIdQuery->fetch();

                            if(!(empty($toUserId_res))){
                                $touserid = $toUserId_res["account"];
                            }else{
                                $touserid = null;
                            }

                            changePopularity($pdo, $ruUniqid, $userid, 2);

                            // トランザクション開始
                            $pdo->beginTransaction();

                            try {
                                // SQL作成
                                $stmt = $pdo->prepare("INSERT INTO ueuse (username, account, uniqid, ruuniqid, ueuse, photo1, photo2, photo3, photo4, video1, datetime, abi, nsfw, popularity, mentions) VALUES (:username, :account, :uniqid, :ruuniqid, :ueuse, :photo1, :photo2, :photo3, :photo4, :video1, :datetime, :abi, :nsfw, :popularity, :mentions)");

                                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                                $stmt->bindParam(':account', $userid, PDO::PARAM_STR);
                                $stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
                                $stmt->bindParam(':ruuniqid', $ruUniqid, PDO::PARAM_STR);
                                $stmt->bindParam(':ueuse', $ueuse, PDO::PARAM_STR);

                                $stmt->bindParam(':photo1', $save_photo1, PDO::PARAM_STR);
                                $stmt->bindParam(':photo2', $save_photo2, PDO::PARAM_STR);
                                $stmt->bindParam(':photo3', $save_photo3, PDO::PARAM_STR);
                                $stmt->bindParam(':photo4', $save_photo4, PDO::PARAM_STR);
                                $stmt->bindParam(':video1', $save_video1, PDO::PARAM_STR);
                                $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

                                $stmt->bindParam(':nsfw', $save_nsfw, PDO::PARAM_STR);

                                $stmt->bindParam(':abi', $abi, PDO::PARAM_STR);
                                $stmt->bindParam(':popularity', $popularity, PDO::PARAM_INT);
                                $stmt->bindParam(':mentions', $mentions, PDO::PARAM_STR);


                                // SQLクエリの実行
                                $res = $stmt->execute();

                                // コミット
                                $res = $pdo->commit();

                                foreach ($mentionedUsers as $mentionedUser) {
                                    send_notification($mentionedUser,$userid,"".$userid."さんにメンションされました！",$ueuse,"/!".$uniqid."", "mention");
                                }

                                send_notification($touserid,$userid,"".$userid."さんがリユーズしました！",$ueuse,"/!".$uniqid."", "reuse");

                            } catch(Exception $e) {
                                // エラーが発生した時はロールバック
                                $pdo->rollBack();
                                actionLog($userid, "error", "send_ueuse", null, $e, 4);
                            }
                        }else{
                            $error_message[] = '返信とリユーズを同時に行うことはできません。(ERROR)';
                            return [false, $error_message];
                        }

                        if( $res ) {
                            return [true, $uniqid];
                        } else {
                            $error_message[] = "ユーズに失敗しました。(REGISTERED_DAME)";
                            return [false, $error_message];
                        }

                        // プリペアドステートメントを削除
                        $stmt = null;
                    }else{
                        actionLog($userid, "error", "send_ueuse", null, $error_message, 0);
                        return [false, $error_message];
                    }
                }else{
                    actionLog($userid, "error", "send_ueuse", null, $error_message, 0);
                    return [false, $error_message];
                }
            }else{
                $error_message[] = "投稿回数のレート制限を超過しています。(OVER_RATE_LIMIT)";
                actionLog($userid, "error", "send_ueuse", null, $error_message, 0);
                return [false, $error_message];
            }
        }else{
            $error_message[] = "ユーズのIDに問題が発生しました。(ERROR)";
            actionLog($userid, "error", "send_ueuse", null, $error_message, 4);
            return [false, $error_message];
        }
    }
}

function delete_ueuse($uniqid, $userid, $account_id){
    if(file_exists(__DIR__ . "/../settings_admin/plugin_settings/amazons3_settings.php")){
        require_once __DIR__ . '/../settings_admin/plugin_settings/amazons3_settings.php';
        if(AMS3_CHKS == "true"){
            if(file_exists(__DIR__ . "/../plugin/aws/aws-autoloader.php")){
                require_once __DIR__ . '/../plugin/aws/aws-autoloader.php';
            }else{
                actionLog(null, "error", "delete_ueuse", null, "AWS SDK for PHPが見つかりませんでした！", 4);
            }
        }
    }else{
        actionLog(null, "error", "delete_ueuse", null, "amazons3_settings.phpが見つかりませんでした！", 3);
    }

    if (safetext(isset($uniqid)) && safetext(isset($userid)) && safetext(isset($account_id))){
        $postUserid = safetext($userid);
        $postUniqid = safetext($uniqid);
        $loginid = safetext($account_id);
    
        try {
            $option = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
            );
            $pdo = new PDO('mysql:charset=utf8mb4;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);
        } catch (PDOException $e) {
            // 接続エラーのときエラー内容を取得する
            $error_message[] = $e->getMessage();
            actionLog($userid, "error", "delete_ueuse", null, $e, 4);
        }
    
        $query = $pdo->prepare('SELECT * FROM ueuse WHERE uniqid = :uniqid limit 1');
        $query->execute(array(':uniqid' => $postUniqid));
        $result = $query->fetch();
    
        if($result > 0){
            if($result["account"] === $postUserid){
                $query = $pdo->prepare('SELECT * FROM account WHERE userid = :userid limit 1');
                $query->execute(array(':userid' => $postUserid));
                $result2 = $query->fetch();
    
                if($result2["loginid"] === $loginid){
                    $photo_query = $pdo->prepare("SELECT * FROM ueuse WHERE account = :userid AND uniqid = :uniqid");
                    $photo_query->bindValue(':userid', $postUserid);
                    $photo_query->bindValue(':uniqid', $postUniqid);
                    $photo_query->execute();
                    $photo_and_video = $photo_query->fetch();
                    
                    if(!($photo_and_video["photo1"] == "none")){
                        if(filter_var($photo_and_video["photo1"], FILTER_VALIDATE_URL)){
                            if(AMS3_CHKS == "true"){
                                deleteAmazonS3($photo_and_video["photo1"]);
                            }
                        }else{
                            $photoDelete1 = glob($photo_and_video["photo1"]); // 「-ユーザーID.拡張子」というパターンを検索
                            foreach ($photoDelete1 as $photo1) {
                                if (is_file($photo1)) {
                                    unlink($photo1);
                                }
                            }
                        }
                    }
                    if(!($photo_and_video["photo2"] == "none")){
                        if(filter_var($photo_and_video["photo2"], FILTER_VALIDATE_URL)){
                            if(AMS3_CHKS == "true"){
                                deleteAmazonS3($photo_and_video["photo2"]);
                            }
                        }else{
                            $photoDelete2 = glob($photo_and_video["photo2"]); // 「-ユーザーID.拡張子」というパターンを検索
                            foreach ($photoDelete2 as $photo2) {
                                if (is_file($photo2)) {
                                    unlink($photo2);
                                }
                            }
                        }
                    }
                    if(!($photo_and_video["photo3"] == "none")){
                        if(filter_var($photo_and_video["photo3"], FILTER_VALIDATE_URL)){
                            if(AMS3_CHKS == "true"){
                                deleteAmazonS3($photo_and_video["photo3"]);
                            }
                        }else{
                            $photoDelete3 = glob($photo_and_video["photo3"]); // 「-ユーザーID.拡張子」というパターンを検索
                            foreach ($photoDelete3 as $photo3) {
                                if (is_file($photo3)) {
                                    unlink($photo3);
                                }
                            }
                        }
                    }
                    if(!($photo_and_video["photo4"] == "none")){
                        if(filter_var($photo_and_video["photo4"], FILTER_VALIDATE_URL)){
                            if(AMS3_CHKS == "true"){
                                deleteAmazonS3($photo_and_video["photo4"]);
                            }
                        }else{
                            $photoDelete4 = glob($photo_and_video["photo4"]); // 「-ユーザーID.拡張子」というパターンを検索
                            foreach ($photoDelete4 as $photo4) {
                                if (is_file($photo4)) {
                                    unlink($photo4);
                                }
                            }
                        }
                    }
                    if(!($photo_and_video["video1"] == "none")){
                        if(filter_var($photo_and_video["video1"], FILTER_VALIDATE_URL)){
                            if(AMS3_CHKS == "true"){
                                deleteAmazonS3($photo_and_video["video1"]);
                            }
                        }else{
                            $videoDelete1 = glob($photo_and_video["video1"]); // 「-ユーザーID.拡張子」というパターンを検索
                            foreach ($videoDelete1 as $video1) {
                                if (is_file($video1)) {
                                    unlink($video1);
                                }
                            }
                        }
                    }
    
                    $ruChkquery = $pdo->prepare('SELECT * FROM ueuse WHERE ruuniqid = :uniqid AND ueuse = "" limit 1');
                    $ruChkquery->execute(array(':uniqid' => $postUniqid));
                    $result3 = $ruChkquery->fetch();
                    
                    if($result3 > 0){
                        // トランザクション開始
                        $pdo->beginTransaction();
                        try {
                            // 削除クエリを実行
                            $rudeleteQuery = $pdo->prepare("DELETE FROM ueuse WHERE ruuniqid = :uniqid AND ueuse = ''");
                            $rudeleteQuery->bindValue(':uniqid', $postUniqid, PDO::PARAM_STR);
                            $res = $rudeleteQuery->execute();
            
                            if (!($res)){
                                $pdo->rollBack();
                                $error_message[] = "リユーズの削除ができませんでした。";
                                actionLog($userid, "error", "delete_ueuse", null, $error_message, 3);
                            }else{
                                $pdo->commit();
                            }
                        } catch(PDOException $e) {
                            $pdo->rollBack();
                            $error_message[] = 'データベースエラー：' . $e->getMessage();
                            actionLog($userid, "error", "delete_ueuse", null, $e, 4);
                        }
                    }
    
                    $ru_tree_Chkquery = $pdo->prepare('SELECT * FROM ueuse WHERE uniqid = :ruuniqid limit 1');
                    $ru_tree_Chkquery->execute(array(':ruuniqid' => $result["ruuniqid"]));
                    $result4 = $ru_tree_Chkquery->fetch();
                    if($result4 > 0){
                        changePopularity($pdo, $result["ruuniqid"], $userid, -2);
                    }

                    $rp_tree_Chkquery = $pdo->prepare('SELECT * FROM ueuse WHERE uniqid = :rpuniqid limit 1');
                    $rp_tree_Chkquery->execute(array(':rpuniqid' => $result["rpuniqid"]));
                    $result5 = $rp_tree_Chkquery->fetch();
                    if($result5 > 0){
                        changePopularity($pdo, $result["rpuniqid"], $userid, -3);
                    }

                    // トランザクション開始
                    $pdo->beginTransaction();
                    try {
                        // 削除クエリを実行
                        $deleteQuery = $pdo->prepare("DELETE FROM ueuse WHERE uniqid = :uniqid AND account = :userid");
                        $deleteQuery->bindValue(':uniqid', $postUniqid, PDO::PARAM_STR);
                        $deleteQuery->bindValue(':userid', $postUserid, PDO::PARAM_STR);
                        $res = $deleteQuery->execute();
    
                        if ($res) {
                            $pdo->commit(); 
                            return [true, "削除に成功しました！"];
                        } else {
                            $pdo->rollBack();
                            return [false, "削除に失敗しました"];
                        }
                    } catch(PDOException $e) {
                        $pdo->rollBack();
                        actionLog($userid, "error", "delete_ueuse", null, $e, 4);
                        return [false, "削除に失敗しました！"];
                    }
                }
            }else{
                return [false, "削除に失敗しました！"];
            }
        }else{
            return [true, "すでに削除しています"];
        }
    }else{
        return [true, "削除に成功しました！"];
    }
}
// SQL操作関数pdo引っ張ってくるように変更(あとでほかもする)
function follow_user($pdo, $to_userid, $userid){
    if (!(empty($pdo)) && !(empty($to_userid)) && !(empty($userid))){
        $myData = getUserData($pdo, $userid);
        $userData = getUserData($pdo, $to_userid);

        if (empty($myData) || empty($userData)) {
            return false;
        }

        if ($myData["userid"] == $userData["userid"]) {
            return false;
        }

        if($myData["role"] == "ice" || $userData["role"] == "ice"){
            actionLog($userid, "error", "follow_user", $to_userid, "凍結されているユーザーはフォローできません。", 3);
            return false;
        }

        $other_settings_me = is_OtherSettings($pdo, $userid);
        $other_settings_user = is_OtherSettings($pdo, $to_userid);
        if($other_settings_me === true && $other_settings_user === true){
            // トランザクションを開始
            $pdo->beginTransaction();
            try {
                // フォローボタンが押された場合の処理
                $followerList = explode(',', $userData['follower'] ?? '');
                if (!(in_array($userid, $followerList))) {
                    // 自分が相手をフォローしていない場合、相手のfollowerカラムと自分のfollowカラムを更新
                    $followerList[] = $userid;
                    $followerList = array_values(array_unique(array_filter($followerList)));
                    $newFollowerList = implode(',', $followerList);

                    // UPDATE文を実行してフォロー情報を更新
                    $updateQuery = $pdo->prepare("UPDATE account SET follower = :follower WHERE userid = :userid");
                    $updateQuery->bindValue(':follower', $newFollowerList, PDO::PARAM_STR);
                    $updateQuery->bindValue(':userid', $userData['userid'], PDO::PARAM_STR);
                    $res = $updateQuery->execute();

                    // 自分のfollowカラムを更新
                    $myflwlist = explode(',', $myData["follow"]);
                    $myflwlist[] = $userData['userid'];
                    $myflwlist = array_values(array_unique(array_filter($myflwlist)));
                    $newFollowList = implode(',', $myflwlist);

                    $updateQuery = $pdo->prepare("UPDATE account SET follow = :follow WHERE userid = :userid");
                    $updateQuery->bindValue(':follow', $newFollowList, PDO::PARAM_STR);
                    $updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
                    $res_follow = $updateQuery->execute();

                    send_notification($userData["userid"], $userid, "🎉" . $userid . "さんにフォローされました！🎉", "" . $userid . "さんにフォローされました。", "/@" . $userid . "", "follow");

                    if ($res && $res_follow) {
                        $pdo->commit();
                        return true;
                    } else {
                        $pdo->rollBack();
                        actionLog($userid, "error", "follow_user", $to_userid, "フォローに失敗", 3);
                        return false;
                    }
                }else{
                    $pdo->commit();
                    return true;
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                actionLog($userid, "error", "follow_user", $to_userid, $e, 4);
                return false;
            }
        }else{
            return false;
        }
    }else{
        return false;
    }
}
function unfollow_user($pdo, $to_userid, $userid){
    if (!(empty($pdo)) && !(empty($to_userid)) && !(empty($userid))){
        $myData = getUserData($pdo, $userid);
        $userData = getUserData($pdo, $to_userid);

        $other_settings_me = is_OtherSettings($pdo, $userid);
        $other_settings_user = is_OtherSettings($pdo, $to_userid);
        if($other_settings_me === true && $other_settings_user === true){
            // トランザクションを開始
            $pdo->beginTransaction();
            try {
                // フォロー解除ボタンが押された場合の処理
                $followerList = explode(',', $userData['follower']);
                if (in_array($userid, $followerList)) {
                    // 自分が相手をフォローしている場合、相手のfollowerカラムと自分のfollowカラムを更新
                    $followerList = array_diff($followerList, array($userid));
                    $followerList = array_values(array_unique(array_filter($followerList)));
                    $newFollowerList = implode(',', $followerList);

                    // UPDATE文を実行してフォロー情報を更新
                    $updateQuery = $pdo->prepare("UPDATE account SET follower = :follower WHERE userid = :userid");
                    $updateQuery->bindValue(':follower', $newFollowerList, PDO::PARAM_STR);
                    $updateQuery->bindValue(':userid', $userData['userid'], PDO::PARAM_STR);
                    $res = $updateQuery->execute();

                    $myflwlist = explode(',', $myData["follow"]);
                    $delfollowList = array_diff($myflwlist, array($userData['userid']));
                    $delfollowList = array_values(array_unique(array_filter($delfollowList)));
                    $deluserid = implode(',', $delfollowList);

                    // 自分のfollowカラムから相手のユーザーIDを削除
                    $updateQuery = $pdo->prepare("UPDATE account SET follow = :follow WHERE userid = :userid");
                    $updateQuery->bindValue(':follow', $deluserid, PDO::PARAM_STR);
                    $updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
                    $res_follow = $updateQuery->execute();

                    if ($res && $res_follow) {
                        // コミット
                        $pdo->commit();
                        return true;
                    } else {
                        // ロールバック
                        $pdo->rollBack();
                        actionLog($userid, "error", "unfollow_user", $to_userid, "フォロー解除に失敗", 3);
                        return false;
                    }
                }else{
                    $pdo->commit();
                    return true;
                }
            } catch (Exception $e) {
                // ロールバック
                $pdo->rollBack();
                actionLog($userid, "error", "unfollow_user", $to_userid, $e, 4);
                return false;
            }
        }else{
            return false;
        }
    }else{
        return false;
    }
}
function block_user($pdo, $to_userid, $userid){
    if (!(empty($pdo)) && !(empty($to_userid)) && !(empty($userid))){
        $myData = getUserData($pdo, $userid);
        $userData = getUserData($pdo, $to_userid);

        if (empty($myData) || empty($userData)) {
            return false;
        }

        if ($myData["userid"] == $userData["userid"]) {
            return false;
        }

        $other_settings_me = is_OtherSettings($pdo, $userid);
        $other_settings_user = is_OtherSettings($pdo, $to_userid);
        if($other_settings_me === true && $other_settings_user === true){
            // トランザクションを開始
            $pdo->beginTransaction();
            try {
                // フォロー解除ボタンが押された場合の処理
                $blockList = explode(',', $myData['blocklist']);
                if (!(in_array($userData['userid'], $blockList))) {
                    $blockList[] = $userData['userid'];
                    $newBlockList = implode(',', array_unique($blockList));

                    // UPDATE文を実行してフォロー情報を更新
                    $updateQuery = $pdo->prepare("UPDATE account SET blocklist = :blocklist WHERE userid = :userid");
                    $updateQuery->bindValue(':blocklist', $newBlockList, PDO::PARAM_STR);
                    $updateQuery->bindValue(':userid', $myData['userid'], PDO::PARAM_STR);
                    $res = $updateQuery->execute();

                    if ($res) {
                        // コミット
                        $pdo->commit();

                        $unfollow = unfollow_user($pdo, $to_userid, $userid);
                        if($unfollow === true){
                            return true;
                        }else{
                            return false;
                        }
                    } else {
                        // ロールバック
                        $pdo->rollBack();
                        actionLog($userid, "error", "block_user", $to_userid, "ブロックに失敗", 3);
                        return false;
                    }
                }else{
                    $pdo->rollBack();
                    return true;
                }
            } catch (Exception $e) {
                // ロールバック
                $pdo->rollBack();
                actionLog($userid, "error", "block_user", $to_userid, $e, 4);
                return false;
            }
        }else{
            return false;
        }
    }else{
        return false;
    }
}
function unblock_user($pdo, $to_userid, $userid){
    if (!(empty($pdo)) && !(empty($to_userid)) && !(empty($userid))){
        $myData = getUserData($pdo, $userid);
        $userData = getUserData($pdo, $to_userid);

        if (empty($myData) || empty($userData)) {
            return false;
        }

        $other_settings_me = is_OtherSettings($pdo, $userid);
        $other_settings_user = is_OtherSettings($pdo, $to_userid);
        if($other_settings_me === true && $other_settings_user === true){
            // トランザクションを開始
            $pdo->beginTransaction();
            try {
                // フォロー解除ボタンが押された場合の処理
                $blockList = explode(',', $myData['blocklist']);
                if (in_array($userData['userid'], $blockList)) {
                    $blockList = array_diff($blockList, array($userData['userid']));
                    $newBlockList = implode(',', $blockList);

                    // UPDATE文を実行してフォロー情報を更新
                    $updateQuery = $pdo->prepare("UPDATE account SET blocklist = :blocklist WHERE userid = :userid");
                    $updateQuery->bindValue(':blocklist', $newBlockList, PDO::PARAM_STR);
                    $updateQuery->bindValue(':userid', $myData['userid'], PDO::PARAM_STR);
                    $res = $updateQuery->execute();

                    if ($res) {
                        // コミット
                        $pdo->commit();
                        return true;
                    } else {
                        // ロールバック
                        $pdo->rollBack();
                        actionLog($userid, "error", "unblock_user", $to_userid, "ブロック解除に失敗", 3);
                        return false;
                    }
                }else{
                    $pdo->rollBack();
                    return true;
                }
            } catch (Exception $e) {
                // ロールバック
                $pdo->rollBack();
                actionLog($userid, "error", "unblock_user", $to_userid, $e, 4);
                return false;
            }
        }else{
            return false;
        }
    }else{
        return false;
    }
}
//--------------------アカウント削除--------------------
function deleteUser($pdo, $userid, $step, $job_uniqid){
    $userdata = getUserData($pdo, $userid);
    if(empty($userdata)){
        changeJob($pdo, $userid, $job_uniqid, "delete_account", "finished");
        return false;
    }else{
        $userid = $userdata["userid"];
        if($step == "stop_account"){
            if(changeJob($pdo, $userid, $job_uniqid, "stop_account", "running")){
                $newrole = "ice";
                $newtoken = "ice";
                $newadmin = "none";
                // トランザクション開始
                $pdo->beginTransaction();

                try {
                    $stmt = $pdo->prepare("UPDATE account SET role = :role,token = :newtoken,admin = :newadmin WHERE userid = :userid");

                    $stmt->bindValue(':role', $newrole, PDO::PARAM_STR);
                    $stmt->bindValue(':newtoken', $newtoken, PDO::PARAM_STR);
                    $stmt->bindValue(':newadmin', $newadmin, PDO::PARAM_STR);

                    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);

                    $res = $stmt->execute();

                    if ($res) {
                        $pdo->commit(); 

                        send_notification($userid, "uwuzu-fromsys", "🗑️アカウントの削除が開始されました🗑️", "アカウントの削除が開始されました！\n今後、アカウントのデータは順次削除されます。\n削除には時間がかかります。\n\nログアウトしてお待ち下さい。\n\nアカウントの復旧はできません。", "/others", "system");
                        if(changeJob($pdo, $userid, $job_uniqid, "delete_ueuse", "waiting")){
                            return true;
                        }else{
                            actionLog($userid, "error", "deleteAccount", null, "Job("+$job_uniqid+")のステータスをdelete_image-waitingに変更失敗", 3);
                            return false;
                        }
                    } else {
                        $pdo->rollBack();
                        actionLog($userid, "error", "deleteUser", $userid, "アカウントの削除前凍結に失敗しました", 4);
                        if(changeJob($pdo, $userid, $job_uniqid, "stop_account", "waiting")){
                            return true;
                        }else{
                            actionLog($userid, "error", "deleteAccount", null, "Job("+$job_uniqid+")のステータスをstop_account-waitingに変更失敗", 3);
                            return false;
                        }
                    }
                } catch (Exception $e) {
                    $pdo->rollBack();
                    actionLog($userid, "error", "deleteUser", $userid, "iceError: ".$e, 4);
                    if(changeJob($pdo, $userid, $job_uniqid, "stop_account", "error")){
                        return true;
                    }else{
                        actionLog($userid, "error", "deleteAccount", null, "Job("+$job_uniqid+")のステータスをstop_account-errorに変更失敗", 3);
                        return false;
                    }
                }
            }

        }

        if($step == "delete_ueuse"){
            if(changeJob($pdo, $userid, $job_uniqid, "delete_ueuse", "running")){
                // ユーズを直近100件取得
                $getUeuse_query = $pdo->prepare("SELECT * FROM ueuse WHERE account = :userid ORDER BY datetime DESC LIMIT 50"); 				
                $getUeuse_query->bindValue(':userid', $userid, PDO::PARAM_STR);
                $getUeuse_query->execute();
                $getUeuse = $getUeuse_query->fetchAll();

                foreach ($getUeuse as $ueuse) {
                    delete_ueuse($ueuse["uniqid"], $userid, $userdata["loginid"]);
                }

                if(count($getUeuse) >= 50){
                    if(changeJob($pdo, $userid, $job_uniqid, "delete_ueuse", "waiting")){
                        return true;
                    }else{
                        actionLog($userid, "error", "deleteAccount", null, "Job("+$job_uniqid+")のステータスをdelete_ueuse-waitingに変更失敗", 3);
                        return false;
                    }
                }else{
                    if(changeJob($pdo, $userid, $job_uniqid, "delete_image", "waiting")){
                        return true;
                    }else{
                        actionLog($userid, "error", "deleteAccount", null, "Job("+$job_uniqid+")のステータスをdelete_image-waitingに変更失敗", 3);
                        return false;
                    }
                }
            }
        }

        if($step == "delete_image"){
            if(changeJob($pdo, $userid, $job_uniqid, "delete_image", "running")){
                // ユーザーの画像を削除
                $folderPath = "../ueuseimages/";
                $filesToDelete = glob($folderPath . "*-$userid.*");
                foreach ($filesToDelete as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                // ユーザーの動画を削除
                $folderPath2 = "../ueusevideos/";
                $filesToDelete2 = glob($folderPath2 . "*-$userid.*");
                foreach ($filesToDelete2 as $file2) {
                    if (is_file($file2)) {
                        unlink($file2);
                    }
                }

                if(changeJob($pdo, $userid, $job_uniqid, "delete_follow", "waiting")){
                    return true;
                }else{
                    actionLog($userid, "error", "deleteAccount", null, "Job("+$job_uniqid+")のステータスをdelete_follow-waitingに変更失敗", 3);
                    return false;
                }
            }
        }

        if($step == "delete_follow"){
            if(changeJob($pdo, $userid, $job_uniqid, "delete_follow", "running")){
                // フォロー・フォロワー情報を削除したい全てのアカウントを取得
                    $flw_query = $pdo->prepare("SELECT * 
                    FROM account 
                    WHERE FIND_IN_SET(:userid, follow) > 0
                    OR FIND_IN_SET(:userid, follower) > 0;
                "); 				
                $flw_query->bindValue(':userid', $userid, PDO::PARAM_STR);
                $flw_query->execute();
                $flw_accounts = $flw_query->fetchAll();

                foreach ($flw_accounts as $account) {
                    unfollow_user($pdo, $account['userid'], $userid);
                    unfollow_user($pdo, $userid, $account['userid']);
                }

                // ユーザーIDを削除したい全てのアカウントを取得
                $blk_query = $pdo->prepare("SELECT * 
                    FROM account 
                    WHERE FIND_IN_SET(:userid, blocklist) > 0;
                "); 				
                $blk_query->bindValue(':userid', $userid, PDO::PARAM_STR);
                $blk_query->execute();
                $blk_accounts = $blk_query->fetchAll();

                foreach ($blk_accounts as $account) {
                    unblock_user($pdo, $userid, $account['userid']);
                }

                //いいねを外したいすべてのユーズを取得
                $fav_ueuse_query = $pdo->prepare("SELECT * 
                    FROM ueuse 
                    WHERE FIND_IN_SET(:userid, favorite) > 0;
                "); 				
                $fav_ueuse_query->bindValue(':userid', $userid, PDO::PARAM_STR);
                $fav_ueuse_query->execute();
                $fav_ueuse_ueuses = $fav_ueuse_query->fetchAll();
                foreach ($fav_ueuse_ueuses as $ueuse) {
                    addFavorite($pdo, $ueuse['uniqid'], $userid);
                }

                if(changeJob($pdo, $userid, $job_uniqid, "delete_account", "waiting")){
                    return true;
                }else{
                    actionLog($userid, "error", "deleteAccount", null, "Job("+$job_uniqid+")のステータスをdelete_account-waitingに変更失敗", 3);
                    return false;
                }
            }
        }

        if($step == "delete_account"){
            if(file_exists(__DIR__ . "/../settings_admin/plugin_settings/amazons3_settings.php")){
                require_once __DIR__ . '/../settings_admin/plugin_settings/amazons3_settings.php';
                if(AMS3_CHKS == "true"){
                    if(file_exists(__DIR__ . "/../plugin/aws/aws-autoloader.php")){
                        require_once __DIR__ . '/../plugin/aws/aws-autoloader.php';
                    }else{
                        actionLog(null, "error", "uploadAmazonS3", null, "AWS SDK for PHPが見つかりませんでした！", 4);
                    }
                }
            }else{
                actionLog(null, "error", "uploadAmazonS3", null, "amazons3_settings.phpが見つかりませんでした！", 3);
            }

            if(changeJob($pdo, $userid, $job_uniqid, "delete_account", "running")){
                $usericonurl = $userdata["iconname"];
                if(filter_var($usericonurl, FILTER_VALIDATE_URL)){
                    if(AMS3_CHKS == "true"){
                        deleteAmazonS3($usericonurl);
                    }
                }else{
                    $folderPath3 = "../usericons/";
                    $filesToDelete3 = glob($folderPath3 . "*-$userid.*"); // 「-ユーザーID.拡張子」というパターンを検索
                    // ファイルを順に削除
                    foreach ($filesToDelete3 as $file3) {
                        if (is_file($file3)) {
                            unlink($file3); // ファイルを削除
                        }
                    }
                }

                $userheadurl = $userdata["headname"];
                if(filter_var($userheadurl, FILTER_VALIDATE_URL)){
                    if(AMS3_CHKS == "true"){
                        deleteAmazonS3($userheadurl);
                    }
                }else{
                    $folderPath4 = "../userheads/";
                    $filesToDelete4 = glob($folderPath4 . "*-$userid.*"); // 「-ユーザーID.拡張子」というパターンを検索
                    // ファイルを順に削除
                    foreach ($filesToDelete4 as $file4) {
                        if (is_file($file4)) {
                            unlink($file4);
                        }
                    }
                }

                $pdo->beginTransaction(); 
                try {
                    // 投稿削除クエリを実行
                    $deleteQuery = $pdo->prepare("DELETE FROM ueuse WHERE account = :userid");
                    $deleteQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
                    $res = $deleteQuery->execute();
        
                    // 通知削除クエリを実行(自分宛ての通知)
                    $deleteQuery = $pdo->prepare("DELETE FROM notification WHERE touserid = :touserid");
                    $deleteQuery->bindValue(':touserid', $userid, PDO::PARAM_STR);
                    $res = $deleteQuery->execute();
                    
                    // 通知削除クエリを実行(自分からの通知)
                    $deleteQuery = $pdo->prepare("DELETE FROM notification WHERE fromuserid = :fromuserid");
                    $deleteQuery->bindValue(':fromuserid', $userid, PDO::PARAM_STR);
                    $res = $deleteQuery->execute();

                    // APIキー削除クエリを実行
                    $deleteQuery = $pdo->prepare("DELETE FROM api WHERE userid = :userid");
                    $deleteQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
                    $res = $deleteQuery->execute();

                    // アカウント削除クエリを実行
                    $deleteQuery = $pdo->prepare("DELETE FROM account WHERE userid = :userid");
                    $deleteQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
                    $res = $deleteQuery->execute();
        
                    if($res) {
                        // コミット
                        $pdo->commit();
                        changeJob($pdo, $userid, $job_uniqid, "delete_account", "finished");
                        actionLog($userid, "success", "deleteAccount", null, "アカウント削除に成功", 1);

                        return true;
                    } else {
                        // ロールバック
                        $pdo->rollBack();
                        actionLog($userid, "error", "deleteAccount", null, "アカウント削除に失敗", 3);
                        changeJob($pdo, $userid, $job_uniqid, "delete_account", "error");

                        return false;
                    }
                } catch (Exception $e) {
                    // エラーが発生した時はロールバック
                    $pdo->rollBack();
                    actionLog($userid, "error", "deleteAccount", null, $e, 4);
                    changeJob($pdo, $userid, $job_uniqid, "delete_account", "error");

                    return false;
                }
            }
        }
    }
}
function changePopularity($pdo, $uniqid, $userid, $change_range){
    if (!(empty($pdo)) && !(empty($uniqid))){
        if(is_numeric($change_range)){
            $pdo->beginTransaction();
            try {
                // 投稿のいいね情報を取得
                $stmt = $pdo->prepare("SELECT popularity FROM ueuse WHERE uniqid = :uniqid");
                $stmt->bindValue(':uniqid', $uniqid, PDO::PARAM_STR);
                $stmt->execute();
                $post = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!(empty($post))) {
                    $new_popularity = (int)$post['popularity'] + (int)$change_range;
                    if($new_popularity >= 2147483647){
                        $new_popularity = 2147483647;
                    }

                    $updateQuery = $pdo->prepare("UPDATE ueuse SET popularity = :popularity WHERE uniqid = :uniqid");
                    $updateQuery->bindValue(':popularity', $new_popularity, PDO::PARAM_INT);
                    $updateQuery->bindValue(':uniqid', $uniqid, PDO::PARAM_STR);
                    $res = $updateQuery->execute();

                    if ($res) {
                        $pdo->commit();
                        return true;
                    } else {
                        $pdo->rollBack();
                        actionLog($userid, "error", "changePopularity", $uniqid, "いいねに失敗しました", 3);
                        return false;
                    }
                } else {
                    $pdo->rollBack();
                    return false;
                }
            } catch(PDOException $e) {
                actionLog($userid, "error", "changePopularity", $uniqid, $e, 4);
                return false;
            }
        }else{
            actionLog($userid, "error", "changePopularity", $uniqid, "不正な変更値です", 4);
            return false;
        }        
    }
}
function addFavorite($pdo, $uniqid, $userid){
    if (!(empty($pdo)) && !(empty($uniqid)) && !(empty($userid))){
        // 投稿のいいね情報を取得
        $stmt = $pdo->prepare("SELECT account,ueuse,favorite FROM ueuse WHERE uniqid = :uniqid");
        $stmt->bindValue(':uniqid', $uniqid, PDO::PARAM_STR);
        $stmt->execute();
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!(empty($post))) {
            $favoriteList = explode(',', $post['favorite']);
            $index = array_search($userid, $favoriteList);

            if ($index === false) {
                // ユーザーIDを追加
                $favoriteList[] = $userid;

                send_notification(safetext($post['account']),$userid,"".$userid."さんがいいねしました！",safetext($post['ueuse']),"/!".$uniqid."","favorite");
                
                //1いいねでスコアが1増加
                changePopularity($pdo, $uniqid, $userid, 1);
            } else {
                // ユーザーIDを削除
                array_splice($favoriteList, $index, 1);
                
                //1いいね解除でスコアが1減る
                changePopularity($pdo, $uniqid, $userid, -1);

                delete_notification(safetext($post['account']),$userid,"".$userid."さんがいいねしました！",safetext($post['ueuse']),"/!".$uniqid."","favorite");
            }

            $pdo->beginTransaction();
            try {
                // 新しいいいね情報を更新
                $newFavorite = implode(',', $favoriteList);
                $updateQuery = $pdo->prepare("UPDATE ueuse SET favorite = :favorite WHERE uniqid = :uniqid");
                $updateQuery->bindValue(':favorite', $newFavorite, PDO::PARAM_STR);
                $updateQuery->bindValue(':uniqid', $uniqid, PDO::PARAM_STR);
                $res = $updateQuery->execute();

                if ($res) {
                    $pdo->commit();
                    return [true, "いいねに成功しました", $newFavorite];
                } else {
                    $pdo->rollBack();
                    actionLog($userid, "error", "addFavorite", $uniqid, "いいねに失敗しました", 3);
                    return [false, "いいねに失敗しました", $post['favorite']];
                }
            } catch(PDOException $e) {
                actionLog($userid, "error", "addFavorite", $uniqid, $e, 4);
                return [false, "データベースエラー", null];
            }
        } else {
            return [false, "投稿が見つかりませんでした", null];
        }
    }
}
function getFavorite($pdo, $uniqid){
    if (!(empty($pdo)) && !(empty($uniqid))){

        try {
            // 投稿のいいね情報を取得
            $stmt = $pdo->prepare("SELECT account,ueuse,favorite FROM ueuse WHERE uniqid = :uniqid");
            $stmt->bindValue(':uniqid', $uniqid, PDO::PARAM_STR);
            $stmt->execute();
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!(empty($post))) {
                return [true, "いいねを取得しました", $post['favorite']];
            } else {
                return [false, "投稿が見つかりませんでした", null];
            }
        } catch(PDOException $e) {
            actionLog(null, "error", "getFavorite", $uniqid, $e, 4);
            return [false, "データベースエラー", null];
        }
    }
}
function getUserData($pdo, $userid) {
    $query = $pdo->prepare("SELECT * FROM account WHERE userid = :userid");
    $query->bindValue(':userid', $userid, PDO::PARAM_STR);
    $query->execute();
    return $query->fetch();
}
function getUserDataForUpdate($pdo, $userid) {
    $query = $pdo->prepare("SELECT * FROM account WHERE userid = :userid FOR UPDATE");
    $query->bindValue(':userid', $userid, PDO::PARAM_STR);
    $query->execute();
    return $query->fetch();
}

function getUeuseData($pdo, $uniqid) {
    $query = $pdo->prepare("SELECT * FROM ueuse WHERE uniqid = :uniqid");
    $query->bindValue(':uniqid', $uniqid, PDO::PARAM_STR);
    $query->execute();
    $ueuseDatas = $query->fetch();

    if (empty($ueuseDatas)) {
        return false;
    }

    //リプライ数取得
    $rpQuery = $pdo->prepare("SELECT COUNT(*) as reply_count FROM ueuse WHERE rpuniqid = :rpuniqid");
    $rpQuery->bindValue(':rpuniqid', $ueuseDatas['uniqid']);
    $rpQuery->execute();
    $rpData = $rpQuery->fetch(PDO::FETCH_ASSOC);
    
    if ($rpData){
        $ueuseDatas['reply_count'] = $rpData['reply_count'];
    }

    //リユーズ数取得
    $ruQuery = $pdo->prepare("SELECT COUNT(*) as reuse_count FROM ueuse WHERE ruuniqid = :ruuniqid");
    $ruQuery->bindValue(':ruuniqid', $ueuseDatas['uniqid']);
    $ruQuery->execute();
    $ruData = $ruQuery->fetch(PDO::FETCH_ASSOC);
    
    if ($ruData){
        $ueuseDatas['reuse_count'] = $ruData['reuse_count'];
    }

    $fav = $ueuseDatas['favorite'];
    $favIds = explode(',', $fav);
    $ueuseDatas["favorite_conut"] = count($favIds)-1;

    return $ueuseDatas;
}
function actionLog($userid, $type, $place, $target, $content, $importance){

    if(empty($userid)){
        $userid = "uwuzu-fromsys";
    }

    switch ($importance) {
        case 0:
            $importance_level = 0;
            break;
        case 1:
            $importance_level = 1;
            break;
        case 2:
            $importance_level = 2;
            break;
        case 3:
            $importance_level = 3;
            break;
        case 4:
            $importance_level = 4;
            break;
        case "none":
            $importance_level = 0;
            break;
        case "low":
            $importance_level = 1;
            break;
        case "middle":
            $importance_level = 2;
            break;
        case "high":
            $importance_level = 3;
            break;
        case "critical":
            $importance_level = 4;
            break;
        default:
            $importance_level = 0;
            break;
    }

    if(empty($type)){
        $type = "none";
    }

    if(empty($target)){
        $target = "none";
    }

    if(empty($content)){
        $content = "none";
    }
    if(is_array($content)){
        $content = implode(', ', $content);
    }

    if(empty($place)){
        $place = "none";
    }

    // データベースに接続
    try {
        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
    } catch(PDOException $e) {
        return false;
    }

    if(!(empty($pdo))){
        $uniqid = createUniqId();
        $datetime = date('Y-m-d H:i:s');

        // トランザクション開始
        $pdo->beginTransaction();

        try {
            // SQL作成
            $stmt = $pdo->prepare("INSERT INTO actionlog (uniqid, userid, type, place, target, content, importance, datetime) VALUES (:uniqid, :userid, :type, :place, :target, :content, :importance, :datetime)");

            $stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
            $stmt->bindParam(':userid', $userid, PDO::PARAM_STR);
            $stmt->bindParam(':type', $type, PDO::PARAM_STR);
            $stmt->bindParam(':place', $place, PDO::PARAM_STR);

            $stmt->bindParam(':target', $target, PDO::PARAM_STR);
            $stmt->bindParam(':content', $content, PDO::PARAM_STR);
            $stmt->bindParam(':importance', $importance_level, PDO::PARAM_INT);
            $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);
            $res = $stmt->execute();
            if($res){
                $pdo->commit();
                return true;
            }else{
                $pdo->rollBack();
                return false;
            }
        } catch(Exception $e) {
            // エラーが発生した時はロールバック
            // ここでログを残そうとすると無限ループ入るのでなし
            $pdo->rollBack();
            return false;
        }
    }
}

function addJob($pdo, $userid, $job, $step){
    $userid = getUserData($pdo, $userid)["userid"];
    if(empty($userid)){
        return false;
    }
    if(empty($job)){
        return false;
    }
    if(empty($step)){
        $step = "start";
    }

    if(!(empty($pdo))){
        $uniqid = createUniqId();
        $datetime = date('Y-m-d H:i:s');
        $status = "waiting";

        // トランザクション開始
        $pdo->beginTransaction();

        try {
            // SQL作成
            $stmt = $pdo->prepare("INSERT INTO jobs (uniqid, userid, job, step, status, datetime) VALUES (:uniqid, :userid, :job, :step, :status, :datetime)");

            $stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
            $stmt->bindParam(':userid', $userid, PDO::PARAM_STR);
            $stmt->bindParam(':job', $job, PDO::PARAM_STR);
            $stmt->bindParam(':step', $step, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);
            $res = $stmt->execute();
            if($res){
                $pdo->commit();
                return true;
            }else{
                actionLog($userid, "error", "addJob", null, "Jobを追加できませんでした！", 3);
                $pdo->rollBack();
                return false;
            }
        } catch(Exception $e) {
            actionLog($userid, "error", "addJob", null, $e, 4);
            $pdo->rollBack();
            return false;
        }
    }
}

function changeJob($pdo, $userid, $uniqid, $step, $status){
    if(empty($uniqid)){
        return false;
    }
    if(empty($step)){
        $step = "start";
    }
    if(empty($status)){
        $status = "waiting";
    }
    $status_list = ["waiting","running","finished","error"];
    if(in_array($status, $status_list)){
        if(!(empty($pdo))){
            $pdo->beginTransaction();

            try {
                $updateQuery = $pdo->prepare("UPDATE jobs SET step = :step, status = :status WHERE uniqid = :uniqid");
                $updateQuery->bindValue(':step', $step, PDO::PARAM_STR);
                $updateQuery->bindValue(':status', $status, PDO::PARAM_STR);
                $updateQuery->bindValue(':uniqid', $uniqid, PDO::PARAM_STR);
                $res = $updateQuery->execute();

                if($res){
                    $pdo->commit();
                    return true;
                }else{
                    $pdo->rollBack();
                    actionLog($userid, "error", "is_OtherSettings", null, "ジョブを編集できませんでした", 3);
                    return false;
                }
            } catch(Exception $e) {
                actionLog($userid, "error", "changeJob", null, $e, 4);
                $pdo->rollBack();
                return false;
            }
        }
    }else{
        actionLog($userid, "error", "changeJob", null, "不正なステータスです！", 3);
        return false;
    }
}

function getJob($pdo, $userid){
    if(empty($userid)){
        return false;
    }

    if(!(empty($pdo))){
        $query = $pdo->prepare("SELECT * FROM jobs WHERE status = 'waiting' ORDER BY datetime ASC LIMIT 1");
        $query->execute();
        $job = $query->fetch(PDO::FETCH_ASSOC);

        if($job){
            return $job;
        }else{
            return false;
        }
    }
}

function localcloudURL($url){
    if(!($url == null || $url == "" || $url == "none")){
        if(filter_var($url, FILTER_VALIDATE_URL)){
            return $url;
        }else{
            return "../" . $url;
        }
    }else{
        return null;
    }
}

function localcloudURLtoAPI($text){
    if(!($text == null || $text == "" || $text == "none")){
        $domain = $_SERVER['HTTP_HOST'];
        $address = localcloudURL($text);
        if(strpos($address, '../') !== false){
            $address = "https://".$domain."/".str_replace('../', '', $address);
        }
        return $address;
    }else{
        return null;
    }
}

function safetext($text){
    // テキストの安全化
    return htmlspecialchars(preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $text), ENT_QUOTES, 'UTF-8', false);
}

function to_array_safetext($value) {
    foreach ($value as $key => $val) {
        if (is_array($val)) {
            $value[$key] = to_array_safetext($val);
        } else {
            $value[$key] = safetext($val);
        }
    }
    return $value;
}

function decode_yajirushi($postText){
    $postText = str_replace('&larr;', '←', $postText);
    $postText = str_replace('&darr;', '↓', $postText);
    $postText = str_replace('&uarr;', '↑', $postText);
    $postText = str_replace('&rarr;', '→', $postText);
    return $postText;
}
function deleteDirectory($dir) {
    //ディレクトリを一括で消すやつ
    if (!is_dir($dir)) {
        return false;
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            // サブディレクトリの場合、再帰的に削除
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }

    return rmdir($dir);
}
// ユーザーデータの暗号化関連
function GenUserEnckey($datetime){
    $dateBaseKey = hash('sha3-512', $datetime);
    $complexEncKey = hash('sha3-512', ENC_KEY . $dateBaseKey);
    return $complexEncKey;
}
function EncryptionUseEncrKey($data,$key,$iv){
    return openssl_encrypt($data, "aes-256-cbc", $key, 0, $iv);
}
function DecryptionUseEncrKey($data,$key,$iv){
    return openssl_decrypt($data, "aes-256-cbc", $key, 0, $iv);
}
// パスワードのArgon2&bcrypt対応認証
function uwuzu_password_hash($password){
    if (in_array("argon2id", password_algos())) {
        $hashpassword = password_hash($password, PASSWORD_ARGON2ID);
    }else{
        if(strlen($password) > 72){
            $onehash = hash('sha3-256', $password);
            $hashpassword = password_hash($onehash, PASSWORD_BCRYPT);
        }else{
            $hashpassword = password_hash($password, PASSWORD_BCRYPT);
        }
    }

    return $hashpassword;
}
function uwuzu_password_verify($password, $hash){
    if (in_array("argon2id", password_algos())) {
        if(password_verify($password, $hash)){
            return true;
        }else{
            return false;
        }
    }else{
        if(strlen($password) > 72){
            $shapass = hash('sha3-256', $password);
            if(password_verify($shapass, $hash)){
                return true;
            }else{
                $pass72 = substr($password, 0, 72);
                if(password_verify($pass72, $hash)){
                    return true;
                }else{
                    return false;
                }
            }
        }else{
            if(password_verify($password, $hash)){
                return true;
            }else{
                return false;
            }
        }
    }
}

//ユーザーのOther_Settings読み取り関数
function val_OtherSettings($dataname, $jsontext){
    $other_settings = json_decode($jsontext, true);
    if(!(empty($other_settings[$dataname]))) {
        if(is_bool($other_settings[$dataname]) === true){
            if($other_settings[$dataname] == true){
                $ret = true;
            }else{
                $ret = false;
            }
        }elseif(is_int($other_settings[$dataname]) === true){
            $ret = (int)$other_settings[$dataname];
        }elseif(is_string($other_settings[$dataname])){
            $ret = $other_settings[$dataname];
        }
    }else{
        $ret = false;
    }
    return $ret;
}
//ユーザーのOther_Settings追加関数
function val_AddOtherSettings($dataname, $data, $jsontext){
    $other_settings = json_decode($jsontext, true);
    if(empty($other_settings)){
        $new_data = [$dataname=>$data];
        $ret = json_encode($new_data);
    }else{
        if(isset($dataname) && isset($data) && isset($jsontext)) {
            if(is_bool($data) === true){
                $new_data = [$dataname=>$data];
                $ret = json_encode(array_merge($other_settings,$new_data));
            }elseif(is_int($data) === true){
                $new_data = [$dataname=>(int)$data];
                $ret = json_encode(array_merge($other_settings,$new_data));
            }elseif(is_string($data)){
                $new_data = [$dataname=>$data];
                $ret = json_encode(array_merge($other_settings,$new_data));
            }
        }else{
            $ret = false;
        }
    }
    
    return $ret;
}
//ユーザーのOther_Settingsが既にあるかないか(なければ空のJSONを追加)
function is_OtherSettings($pdo, $userid, $add = true){
    $other_settings = getUserData($pdo, $userid)["other_settings"];
    if(empty($other_settings)){
        if($add === true){
            $new_data = [];
            $new_json = json_encode($new_data);

            $pdo->beginTransaction();
            try {
                // UPDATE文を実行してフォロー情報を更新
                $updateQuery = $pdo->prepare("UPDATE account SET other_settings = :other_settings WHERE userid = :userid");
                $updateQuery->bindValue(':other_settings', $new_json, PDO::PARAM_STR);
                $updateQuery->bindValue(':userid', $userid, PDO::PARAM_STR);
                $res = $updateQuery->execute();

                if($res){
                    $pdo->commit();
                    return true;
                }else{
                    // ロールバック
                    $pdo->rollBack();
                    actionLog($userid, "error", "is_OtherSettings", null, "空のOtherSettingsを追加できませんでした", 3);
                    return false;
                }
            } catch (Exception $e) {
                // ロールバック
                $pdo->rollBack();
                actionLog($userid, "error", "is_OtherSettings", null, $e, 4);
                return false;
            }
        }else{
            return false;
        }
    }else{
        return true;
    }
}

function GetActivityPubJson($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,         // リダイレクトを追跡
        CURLOPT_MAXREDIRS => 10,                // 最大リダイレクト回数
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'uwuzu-ActivityPubClient/1.0',
        CURLOPT_HTTPHEADER => [
            'Accept: application/activity+json, application/ld+json, application/json'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode >= 400) {
        return null;
    }

    $json = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    return $json;
}

function GetActivityPubUser($userid, $domain) {
    $webfingerUrl = "https://$domain/.well-known/webfinger?resource=acct:$userid@$domain";

    $webfingerJson = GetActivityPubJson($webfingerUrl);

    if (!$webfingerJson || empty($webfingerJson['links'])) {
        return ['error' => 'Failed to fetch WebFinger'];
    }

    $actorUrl = null;
    foreach ($webfingerJson['links'] as $link) {
        if ($link['rel'] === 'self' && $link['type'] === 'application/activity+json') {
            $actorUrl = $link['href'];
            break;
        }
    }

    if (!$actorUrl) {
        return ['error' => 'Actor URL not found'];
    }

    $actorJson = GetActivityPubJson($actorUrl);
    if (!$actorJson) {
        return ['error' => 'Failed to fetch actor'];
    }

    $summaryHtml = $actorJson['summary'] ?? '';
    $withNewlines = preg_replace('/<br\s*\/?>/i', "\n", $summaryHtml);
    $plainText = strip_tags($withNewlines);


    return [
        'userid' => $actorJson['preferredUsername'] ?? null,
        'username' => $actorJson['name'] ?? null,
        'profile' => $plainText ?? null,
        'id' => $actorJson['id'] ?? null,
        'inbox' => $actorJson['inbox'] ?? null,
        'outbox' => $actorJson['outbox'] ?? null,
        'followers' => $actorJson['followers'] ?? null,
        'iconname' => $actorJson['icon']['url'] ?? "../img/deficon/icon.png",
        'headname' => $actorJson['image']['url'] ?? "../img/defhead/head.png",
        'datetime' => $actorJson['published'] ?? null,
        'role' => 'user',
        'other_settings' => '{}',
        'follow' => '',
        'follower' => '',
        'raw' => $actorJson
    ];
}

function FormatUeuseItem(array $value, string $myblocklist, string $mybookmark, $pdo, string $userId): ?array {
    if (in_array(safetext($value['account']), explode(",", $myblocklist))) return null;
    if ($value["role"] === "ice") return null;

    $value['iconname'] = filter_var($value['iconname'], FILTER_VALIDATE_URL)
        ? $value['iconname']
        : "../" . $value['iconname'];

    $value = to_null($value);
    $value = to_array_safetext($value);
    $value["role"] = explode(',', $value["role"]);

    if(isset($value["activitypub"]) && $value["activitypub"] == true) {
        $value["activitypub"] = true;
    } else {
        $value["activitypub"] = false;
    }

    if(isset($value["sacinfo"]) && $value["sacinfo"] == "bot") {
        $value["is_bot"] = true;
    } else {
        $value["is_bot"] = false;
    }

    if (!empty($value['rpuniqid'])) {
        $value["type"] = "Reply";
    } elseif (!empty($value['ruuniqid'])) {
        $value["type"] = "Reuse";

        $reused = getUeuseData($pdo, $value['ruuniqid']);
        if ($reused) {
            $reusedUserData = getUserData($pdo, $reused['account']);
            $reusedUserData["role"] = explode(',', $reusedUserData["role"]);

            $reused = to_null($reused);
            $reused = to_array_safetext($reused);

            if(isset($reusedUserData["sacinfo"]) && $reusedUserData["sacinfo"] == "bot") {
                $reusedUserData["is_bot"] = true;
            } else {
                $reusedUserData["is_bot"] = false;
            }

            $value["reuse"] = [
                "type" => "Reuse",
                "uniqid" => $reused["uniqid"],
                "datetime" => $reused["datetime"],
                "userid" => $reused["account"],
                "userdata" => [
                    "userid" => $reusedUserData["userid"],
                    "username" => $reusedUserData["username"],
                    "iconurl" => filter_var($reusedUserData['iconname'], FILTER_VALIDATE_URL)
                        ? $reusedUserData['iconname']
                        : "../" . $reusedUserData['iconname'],
                    "role" => $reusedUserData["role"],
                    "is_bot" => $reusedUserData["is_bot"],
                ],
                "ueuse" => $reused["ueuse"],
                "photo1" => $reused["photo1"],
                "photo2" => $reused["photo2"],
                "photo3" => $reused["photo3"],
                "photo4" => $reused["photo4"],
                "video1" => $reused["video1"],
                "rpuniqid" => $reused["rpuniqid"],
                "ruuniqid" => $reused["ruuniqid"],
                "nsfw" => filter_var($reused["nsfw"], FILTER_VALIDATE_BOOLEAN),
                "favoritecount" => $reused["favorite_conut"],
                "replycount" => $reused["reply_count"],
                "reusecount" => $reused["reuse_count"],
                "is_favorite" => in_array($userId, explode(',', $reused['favorite'])),
                "is_bookmark" => in_array($reused["uniqid"], explode(',', $mybookmark)),
                "abi" => [
                    "abi_text" => $reused["abi"],
                    "abi_date" => $reused["abidate"],
                ],
                "is_activitypub" => $value["activitypub"],
            ];
        } else {
            $value["reuse"] = null;
        }
    } else {
        $value["type"] = "Ueuse";
    }

    $ueuse = [
        "type" => $value["type"],
        "uniqid" => $value["uniqid"],
        "datetime" => $value["datetime"],
        "userid" => $value["account"],
        "userdata" => [
            "userid" => $value["account"],
            "username" => $value["username"],
            "iconurl" => $value['iconname'],
            "role" => $value["role"],
            "is_bot" => $value["is_bot"],
        ],
        "ueuse" => $value["ueuse"],
        "photo1" => $value["photo1"],
        "photo2" => $value["photo2"],
        "photo3" => $value["photo3"],
        "photo4" => $value["photo4"],
        "video1" => $value["video1"],
        "rpuniqid" => $value["rpuniqid"],
        "ruuniqid" => $value["ruuniqid"],
        "nsfw" => filter_var($value["nsfw"], FILTER_VALIDATE_BOOLEAN),
        "favoritecount" => $value["favorite_conut"],
        "replycount" => $value["reply_count"],
        "reusecount" => $value["reuse_count"],
        "is_favorite" => in_array($userId, explode(',', $value['favorite'])),
        "is_bookmark" => in_array($value["uniqid"], explode(',', $mybookmark)),
        "abi" => [
            "abi_text" => $value["abi"],
            "abi_date" => $value["abidate"],
        ],
        "is_activitypub" => $value["activitypub"],
    ];

    if ($value["type"] === "Reuse") {
        $ueuse["reuse"] = $value["reuse"];
    }

    return $ueuse;
}

function GetAPIScopes($scope){
    $scopelist = [
        "read:me" => "重要な情報以外の自分のアカウントの情報を見る",
        "write:me" => "重要な情報以外の自分のアカウントの情報を変更する",
        "read:users" => "他のユーザーのアカウント情報を見る",
        "read:ueuse" => "ユーズを見る",
        "write:ueuse" => "ユーズの作成・削除をする",
        "write:follow" => "フォロー・フォロー解除をする",
        "write:favorite" => "いいねをする・解除をする",
        "read:notifications" => "通知を見る",
        "write:notifications" => "通知を既読にする",
        "write:bookmark" => "ブックマークにユーズを追加・削除する",
        "read:bookmark" => "ブックマークを見る"
    ];
    if(empty($scope)){
        return $scopelist;
    }else{
        if(array_key_exists($scope, $scopelist)){
            return $scopelist[$scope];
        }else{
            return false;
        }
    }
}

function MinimumHash($text) {
    $hash = hash('sha3-512', $text);

    for ($i = 0; $i < 5; $i++) {
        $parts = str_split($hash, 2);
        $new = [];
        foreach ($parts as $index => $part) {
            $new[] = ($index % 2 === 0) ? substr($part, 0, -1) : substr($part, 1);
        }
        $hash = implode('', $new);
    }

    $baseChars = preg_replace('/[^a-zA-Z0-9]/', '', $text);
    if ($baseChars === '') {
        $baseChars = 'fallback';
    }
    $baseChars = str_split($baseChars);

    $alphabet = array_merge(range('a', 'z'), range('0', '9'));
    $map = [];
    foreach ($alphabet as $i => $char) {
        $map[$char] = $baseChars[$i % count($baseChars)];
    }

    $encoded = '';
    foreach (str_split($hash) as $char) {
        if ($char === '.') continue; // ドット除外
        if (isset($map[$char])) {
            $encoded .= $map[$char];
            if (strlen($encoded) === 4) break;
        }
    }

    return (strlen($encoded) === 4) ? $encoded : null;
}

function GenAPIToken(int $totalLength = 64){
    $prefix = strtoupper(MinimumHash($_SERVER['HTTP_HOST']));
    $length = $totalLength - strlen($prefix);

    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $charLen = strlen($chars);

    $token = '';
    while (strlen($token) < $length) {
        $byte = random_bytes(1);
        $val = ord($byte);
        if ($val < 62 * floor(256 / 62)) {
            $token .= $chars[$val % $charLen];
        }
    }

    return $prefix . $token;
}
function DelAPIToken($pdo, $uniqid){
    if(!(empty($uniqid))){
        $tokenQuery = $pdo->prepare("SELECT token FROM api WHERE uniqid = :uniqid");
        $tokenQuery->bindValue(':uniqid', $uniqid);
        $tokenQuery->execute();
        $tokenData = $tokenQuery->fetch();
        if(!(empty($tokenData["token"]))){
            $pdo->beginTransaction();
            try {
                // 削除クエリを実行
                $deleteQuery = $pdo->prepare("DELETE FROM api WHERE uniqid = :uniqid");
                $deleteQuery->bindValue(':uniqid', $uniqid, PDO::PARAM_STR);
                $res = $deleteQuery->execute();
                
                if ($res) {
                    $res = $pdo->commit();
                    return true;
                } else {
                    $pdo->rollBack();
                    actionLog($uniqid, "error", "delete_api_token", null, "APIトークンの削除に失敗しました(rollBack)", 3);
                    return false;
                }
            } catch(PDOException $e) {
                $pdo->rollBack();
                actionLog($uniqid, "error", "delete_api_token", null, $e, 4);
                return false;
            }
        }else{
            actionLog($uniqid, "error", "delete_api_token", null, "カラムは存在しますがAPIトークンが存在しません", 3);
            return false;
        }
    }else{
        return false;
    }
}
function DelSessionidAPIToken($pdo, $session){
    $tokenQuery = $pdo->prepare("SELECT uniqid, userid, token FROM api WHERE sessionid = :sessionid");
    $tokenQuery->bindValue(':sessionid', $session);
    $tokenQuery->execute();
    $tokenData = $tokenQuery->fetch();

    $none = "";
    if(!(empty($tokenData["userid"]))){
        $pdo->beginTransaction();
        try {
            $updateQuery = $pdo->prepare("UPDATE api SET sessionid = :sessionid WHERE uniqid = :uniqid");
            $updateQuery->bindValue(':sessionid', $none, PDO::PARAM_STR);
            $updateQuery->bindValue(':uniqid', $tokenData["uniqid"], PDO::PARAM_STR);
            $res = $updateQuery->execute();

            if($res){
                $pdo->commit();
                return true;
            }else{
                // ロールバック
                $pdo->rollBack();
                actionLog($tokenData["userid"], "error", "DelSessionidAPIToken", $tokenData["uniqid"], "セッションIDの無効化に失敗しました！", 3);
                return false;
            }
        } catch (Exception $e) {
            // ロールバック
            $pdo->rollBack();
            actionLog($tokenData["userid"], "error", "DelSessionidAPIToken", $tokenData["uniqid"], $e, 4);
            return false;
        }   
    }else{
        actionLog($tokenData["userid"], "error", "DelSessionidAPIToken", $tokenData["uniqid"], "セッションIDが存在しません。", 3);
        return false;
    }
}
function APIAuth($pdo, $token, $scope){
    $tokenQuery = $pdo->prepare("SELECT userid, scope FROM api WHERE token = :token");
    $tokenQuery->bindValue(':token', $token);
    $tokenQuery->execute();
    $tokenData = $tokenQuery->fetch();

    if(!(empty($tokenData["userid"]))){
        $allow_scope = array_unique(array_map('trim', explode(",", $tokenData["scope"])));
        if(in_array($scope, $allow_scope)){
            $userdata = getUserData($pdo, $tokenData["userid"]);
            if(!(empty($userdata))){
                if($userdata["role"] === "ice"){
                    return [false, "this_account_has_been_frozen", null];
                }else{
                    return [true, "success", $userdata];
                }
            }else{
                return [false, "token_invalid", null];
            }
        }else{
            return [false, "not_allow_scope", null];
        }
    }else{
        $userQuery = $pdo->prepare("SELECT * FROM account WHERE token = :token");
        $userQuery->bindValue(':token', $token);
        $userQuery->execute();
        $userData = $userQuery->fetch();

        if(empty($userData["userid"])){
            return [false, "token_invalid", null];
        }elseif($userData["role"] === "ice"){
            return [false, "this_account_has_been_frozen", null];
        }else{
            return [true, "success", $userData];
        }
    }
}

?>