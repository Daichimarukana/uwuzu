<?php
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

        $newFilename = uniqid() . '-' . $userid . '.' . $extension;
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
//文字装飾・URL変換など
function processMarkdownAndWrapEmptyLines($markdownText){

    //\___________________[注意]__________________\
    // \____ここの順番を変えるとうまく動かなくなります___\
    //  \______Markdownうまく動くところを探すべし______\

    $markdownText = preg_replace('/\[\[buruburu (.+)\]\]/m', '<span class="buruburu">$1</span>', $markdownText);//ぶるぶる

    $markdownText = preg_replace('/(^|[^`])`([^`\n]+)`($|[^`])/m', '$1<span class="inline">$2</span>$3', $markdownText);//Inline Code

    $markdownText = preg_replace_callback('/^\[\[time (\d+)\]\]/m', function($matches) {
        $timestamp = $matches[1];
        return '<span class="unixtime" title="'.date("Y/m/d H:i.s", htmlentities($timestamp, ENT_QUOTES, 'UTF-8', false)).'">' . date("Y/m/d H:i", htmlentities($timestamp, ENT_QUOTES, 'UTF-8', false)) . '</span>';
    }, $markdownText);

    //太字&斜体------------------------------------------------------------------------
    $markdownText = preg_replace('/\*\*\*(.+)\*\*\*(?=\s)/', '<b><i>$1</i></b>', $markdownText);//太字&斜体の全部のせセット
    $markdownText = preg_replace('/\*\*\*(.+)\*\*\*/', '<b><i>$1</i></b>', $markdownText);//太字&斜体の全部のせセット

    $markdownText = preg_replace('/\_\_\_(.+)\_\_\_(?=\s)/', '<b><i>$1</i></b>', $markdownText);//太字&斜体の全部のせセット
    $markdownText = preg_replace('/\b\_\_\_(.+)\_\_\_\b/', '<b><i>$1</i></b>', $markdownText);//太字&斜体の全部のせセット

    //太字-----------------------------------------------------------------------------
    $markdownText = preg_replace('/\*\*(.+)\*\*(?=\s)/', '<b>$1</b>', $markdownText);//太字
    $markdownText = preg_replace('/\b\*\*(.+)\*\*\b/', '<b>$1</b>', $markdownText);//太字

    $markdownText = preg_replace('/\_\_(.+)\_\_(?=\s)/', '<b>$1</b>', $markdownText);//太字
    $markdownText = preg_replace('/\b\_\_(.+)\_\_\b/', '<b>$1</b>', $markdownText);//太字

    //斜体-----------------------------------------------------------------------------
    $markdownText = preg_replace('/\*(.+)\*(?=\s)/', '<i>$1</i>', $markdownText);//斜体
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
    // ユーズ内で絵文字名（:emoji:）を検出して画像に置き換える
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
            return ":".$emojiName.":";
        }else{
            $emoji_path = $emoji_row["emojifile"];
            return "<img src='../".$emoji_path."' alt=':$emojiName:' title=':$emojiName:'>";
        }
    }, $postText);
    
    // @username を検出してリンクに置き換える
    $usernamePattern = '/@(\w+)/';
    $postTextWithImagesAndUsernames = preg_replace_callback($usernamePattern, function($matches) {
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
            return "@$username";
        }else{
            return "<a class = 'mta' href='/@".htmlentities($mentionsuserData["userid"], ENT_QUOTES, 'UTF-8', false)."'>@".replaceProfileEmojiImages(htmlentities($mentionsuserData["username"], ENT_QUOTES, 'UTF-8', false))."</a>";
        }
    }, $postTextWithImages);

    $hashtagsPattern = '/#([\p{Han}\p{Hiragana}\p{Katakana}A-Za-z0-9ー_!]+)/u';
    $postTextWithHashtags = preg_replace_callback($hashtagsPattern, function($matches) {
        $hashtags = $matches[1];
        return "<a class='hashtags' href='/search?q=" . urlencode('#' . $hashtags) . "'>" . '#' . $hashtags . "</a>";
    }, $postTextWithImagesAndUsernames);

    return $postTextWithHashtags;
}

function replaceURLsWithLinks($postText, $maxLength = 48) {
    $pattern = '/(https:\/\/[\w!?\/+\-_~;.,*&@#$%()+|https:\/\/[ぁ-んァ-ヶ一-龠々\w\-\/?=&%.]+)/';
    $convertedText = preg_replace_callback($pattern, function($matches) use ($maxLength) {
        $link = $matches[0];
        if(!(preg_match('/:(\w+):/',$link))){
            $no_https_link = str_replace("https://", "", $link);
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
            if($parsedUrl['host'] == "youtube.com" || $parsedUrl['host'] == "youtu.be" || $parsedUrl['host'] == "www.youtube.com" || $parsedUrl['host'] == "m.youtube.com"){

                if (isset($parsedUrl['query'])) {
                    if(false !== strpos($parsedUrl['query'], 'v=')) {
                        $video_id = str_replace('v=', '', htmlentities($parsedUrl['query'], ENT_QUOTES, 'UTF-8', false));
                        $iframe = true;
                    }else{
                        $video_id = str_replace('/', '', htmlentities($parsedUrl['path'], ENT_QUOTES, 'UTF-8', false));
                        $iframe = true;
                    }
                    $video_id = str_replace('&amp;', '?', $video_id);
                }elseif(isset($parsedUrl['path'])){
                    $video_id = str_replace('/', '', htmlentities($parsedUrl['path'], ENT_QUOTES, 'UTF-8', false));
                    $iframe = true;
                }else{
                    $video_id = "";
                    $iframe = false;
                }
                // 不要な文字を削除してaタグを生成
                if($iframe == true){
                    $link = '<iframe src="https://www.youtube-nocookie.com/embed/'.$video_id.'" rel="0" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
                }else{
                    $link = "";
                }
                // URLをドメインのみを表示するaタグで置き換え
                $postText = $link;
            }elseif($parsedUrl['host'] == "nicovideo.jp" || $parsedUrl['host'] == "www.nicovideo.jp"){

                if(isset($parsedUrl['path'])){
                    $video_id = str_replace('/watch/', '', htmlentities($parsedUrl['path'], ENT_QUOTES, 'UTF-8', false));
                    $iframe = true;
                }else{
                    $video_id = "";
                    $iframe = false;
                }
                // 不要な文字を削除してaタグを生成
                if($iframe == true){
                    $link = '<iframe src="https://embed.nicovideo.jp/watch/'.$video_id.'"</iframe>';
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
        $memory_max_s = ini_parse_quantity($memory_max);
    }else{
        $memory_max_s = PHP_INT_MAX;
    }
    if(!($post_max == "-1")){
        $post_max_s = ini_parse_quantity($post_max);
    }else{
        $post_max_s = PHP_INT_MAX;
    }
    if(!($upload_max == "-1")){
        $upload_max_s = ini_parse_quantity($upload_max);
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
function send_notification($to,$from,$title,$message,$url){
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
        				
        $pdo->beginTransaction();

        try {
            $fromuserid = htmlentities($from, ENT_QUOTES, 'UTF-8', false);
            $touserid = htmlentities($to, ENT_QUOTES, 'UTF-8', false);
            $datetime = date("Y-m-d H:i:s");
            $msg = htmlentities($message, ENT_QUOTES, 'UTF-8', false);
            $title = htmlentities($title, ENT_QUOTES, 'UTF-8', false);
            $url = htmlentities($url, ENT_QUOTES, 'UTF-8', false);
            $userchk = 'none';
    
            // 通知用SQL作成
            $stmt = $pdo->prepare("INSERT INTO notification (fromuserid, touserid, msg, url, datetime, userchk, title) VALUES (:fromuserid, :touserid, :msg, :url, :datetime, :userchk, :title)");
    
            $stmt->bindParam(':fromuserid', $fromuserid, PDO::PARAM_STR);
            $stmt->bindParam(':touserid', $touserid, PDO::PARAM_STR);
            $stmt->bindParam(':msg', $msg, PDO::PARAM_STR);
            $stmt->bindParam(':url', $url, PDO::PARAM_STR);
            $stmt->bindParam(':userchk', $userchk, PDO::PARAM_STR);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
    
            $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);
    
            $res = $stmt->execute();
    
            $res = $pdo->commit();
    
            if($res){
                return true;
            }else{
                return false;
            }
    
        } catch(Exception $e) {
            return false;
        }
    }
}

?>