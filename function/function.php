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
    $tmp_ext = mime_content_type($tmp_name);
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
    $tmp_ext = mime_content_type($tmp_name);
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
    $markdownText = preg_replace('/\b\*\*\*(.+)\*\*\*\b/', '<b><i>$1</i></b>', $markdownText);//太字&斜体の全部のせセット

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
?>