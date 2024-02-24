
<?php 
function processMarkdownAndWrapEmptyLines($markdownText){

    //\___________________[注意]__________________\
    // \____ここの順番を変えるとうまく動かなくなります___\
    //  \______Markdownうまく動くところを探すべし______\

    $markdownText = preg_replace('/\[\[buruburu (.+)\]\]/m', '<span class="buruburu">$1</span>', $markdownText);//ぶるぶる

    $markdownText = preg_replace('/(^|[^`])`([^`\n]+)`($|[^`])/m', '$1<span class="inline">$2</span>$3', $markdownText);//Inline Code

    /*$markdownText = preg_replace_callback('/^\[\[time (\d+)\]\]/m', function($matches) {
        $timestamp = $matches[1];
        return '<span class="unixtime">' . date("Y/m/d H:i:s", $timestamp) . '</span>';
    }, $markdownText);*/

    $markdownText = preg_replace('/\*\*\*(.*?)\*\*\*/', '<b><i>$1</i></b>', $markdownText);//太字&斜体の全部のせセット
    $markdownText = preg_replace('/\_\_\_(.*?)\_\_\_/', '<b><i>$1</i></b>', $markdownText);//太字&斜体の全部のせセット

    $markdownText = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $markdownText);//太字
    $markdownText = preg_replace('/\_\_(.*?)\_\_/', '<b>$1</b>', $markdownText);//太字

    $markdownText = preg_replace('/\*(.*?)\*/', '<i>$1</i>', $markdownText);//斜体
    $markdownText = preg_replace('/\_(.*?)\_/', '<i>$1</i>', $markdownText);//斜体

    $markdownText = preg_replace('/\~\~(.*?)\~\~/m', '<s>$1</s>', $markdownText);//打ち消し線

    $markdownText = preg_replace('/&gt;&gt;&gt; (.*)/m', '<span class="quote">$1</span>', $markdownText);//>>> 引用

    $markdownText = preg_replace('/\|\|(.*)\|\|/m', '<span class="blur">$1</span>', $markdownText);//黒塗り

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
        }else{
            $emoji_path = $emoji_row["emojifile"];
        }
        return "<img src='../".$emoji_path."' alt=':$emojiName:' title=':$emojiName:'>";
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
            return "<a class = 'mta' href='/@".htmlentities($mentionsuserData["userid"])."'>@".replaceProfileEmojiImages(htmlentities($mentionsuserData["username"]))."</a>";
        }
    }, $postTextWithImages);

    $hashtagsPattern = '/#([\p{Han}\p{Hiragana}\p{Katakana}A-Za-z0-9ー_]+)/u';
    $postTextWithHashtags = preg_replace_callback($hashtagsPattern, function($matches) {
        $hashtags = $matches[1];
        return "<a class='hashtags' href='/search?q=" . urlencode('#') . $hashtags . "'>" . '#' . $hashtags . "</a>";
    }, $postTextWithImagesAndUsernames);

    return $postTextWithHashtags;
}
function replaceURLsWithLinks($postText) {

    $postText = str_replace('&#039;', '\'', $postText);

    // URLを正規表現を使って検出
    $pattern = '/(https:\/\/[^\s<>\[\]\'"]+)/';  // 改良された正規表現
    preg_match_all($pattern, $postText, $matches);

    // 検出したURLごとに処理を行う
    foreach ($matches[0] as $url) {
        // ドメイン部分を抽出
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['path'])) {
            $parsedUrl['path'] = '';
        }
        if (!isset($parsedUrl['query'])) {
            $parsedUrl['query'] = '';
        }

        $nochk_domain = $parsedUrl['host'].$parsedUrl['path'].$parsedUrl['query'];

        if(strlen($nochk_domain) > 47){
            $domain = mb_substr($nochk_domain, 0, 48, "UTF-8")."...";
        }else{
            $domain = $nochk_domain;
        }

        // 不要な文字を削除してaタグを生成
        $urlWithoutSpaces = preg_replace('/\s+/', '', $url);
        $link = "<a href='$urlWithoutSpaces' target='_blank' title='$urlWithoutSpaces'>$domain</a>";

        // URLをドメインのみを表示するaタグで置き換え
        $postText = preg_replace('/' . preg_quote($url, '/') . '/', $link, $postText);
    }

    return $postText;
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
        if($parsedUrl['host'] == "youtube.com" || $parsedUrl['host'] == "youtu.be" || $parsedUrl['host'] == "www.youtube.com" || $parsedUrl['host'] == "m.youtube.com"){

            if (isset($parsedUrl['query'])) {
                if(false !== strpos($parsedUrl['query'], 'v=')) {
                    $video_id = str_replace('v=', '', htmlentities($parsedUrl['query']));
                    $iframe = true;
                }else{
                    $video_id = str_replace('/', '', htmlentities($parsedUrl['path']));
                    $iframe = true;
                }
            }elseif(isset($parsedUrl['path'])){
                $video_id = str_replace('/', '', htmlentities($parsedUrl['path']));
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
                $video_id = str_replace('/watch/', '', htmlentities($parsedUrl['path']));
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

    return $postText;
}

class MessageDisplay {
    private $value;
    private $userid;

    public function __construct($value, $userid) {
        $this->value = $value;
        $this->userid = $userid;
    }

    public function display() {

        if (empty($this->value)) {
            echo '<div class="tokonone" id="noueuse"><p>ユーズがありません</p></div>';
        } else {
            echo '<div class="ueuse">';
            if(!empty($this->value['rpuniqid'])){
            echo '<div class="rp"><p>┗━ 一番上のユーズに返信</p></div>';
            }
            echo '    <div class="flebox">';
            
            echo '        <a href="/@' . htmlentities($this->value['account']) . '"><img src="'. htmlentities('../'.$this->value['iconname']) . '"></a>';
            echo '        <a href="/@' . htmlentities($this->value['account']) . '"><div class="u_name">' . replaceProfileEmojiImages(htmlentities($this->value['username'])) . '</div></a>';
            echo '        <div class="idbox">';
            echo '            <a href="/@' . htmlentities($this->value['account']) . '">@' . htmlentities($this->value['account']) . '</a>';
            echo '        </div>';
            if(!empty($this->value['sacinfo'])){
                if($this->value['sacinfo'] === "bot"){
                    echo '<div class="bot">Bot</div>';
                }
            }
            if (false !== strpos($this->value['role'], 'official')) {
                echo '      <div class="checkicon">';
                echo '          <div class="check" />';
                echo '      </div>';
                echo '</div>';
            }
            
            echo '        <div class="time">';
            $datetime = strtotime(htmlentities($this->value['datetime']));
            $today = strtotime(date("Y-m-d"));
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            if (date("md", $datetime) == "0101") {
                if (date("Y", $datetime) == date("Y")) {
                    echo "元日 " . date("H:i", $datetime);
                } else {
                    echo date("Y年m月d日 H:i", $datetime);
                }
            } elseif ($datetime >= $tomorrow) {
                echo date("Y年m月d日 H:i", $datetime) . " (未来)";
            } elseif ($datetime >= $today) {
                echo "今日 " . date("H:i", $datetime);
            } elseif (date("Y", $datetime) == date("Y")) {
                echo date("m月d日 H:i", $datetime);
            } else {
                echo date("Y年m月d日 H:i", $datetime);
            }            
            echo '        </div>';
            
            echo '    </div>';

            if($this->value['nsfw'] === "true"){
                echo '    <div class="nsfw" data-uniqid="' . htmlentities($this->value['uniqid']) . '">';
                echo '    <p>NSFW指定がされている投稿です！<br>職場や公共の場での表示には適さない場合があります。<br>表示ボタンを押すと表示されます。</p>';
                echo '    <div class="btnzone">';
                echo '    <input type="button" id="nsfw_view" class="mini_irobtn" value="表示">';
                echo '    </div>';
                echo '    </div>';
                echo '    <div class="nsfw_main" data-uniqid="' . htmlentities($this->value['uniqid']) . '">';
                echo '    <div class="block">';
            }
            echo '    <p>' . processMarkdownAndWrapEmptyLines(replaceEmojisWithImages(replaceURLsWithLinks(nl2br($this->value['ueuse'])))) . '</h1></h2></h3></font></center></p>';
            
            if (!empty($this->value['photo4']) && $this->value['photo4'] !== 'none') {
                echo '    <div class="photo4">';
                echo '        <a href="'.htmlentities($this->value['photo1']).'" target=”_blank”><img src="'.htmlentities($this->value['photo1']).'" alt="画像1" title="画像1" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        <a href="'.htmlentities($this->value['photo2']).'" target=”_blank”><img src="'.htmlentities($this->value['photo2']).'" alt="画像2" title="画像2" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        <a href="'.htmlentities($this->value['photo3']).'" target=”_blank”><img src="'.htmlentities($this->value['photo3']).'" alt="画像3" title="画像3" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        <a href="'.htmlentities($this->value['photo4']).'" target=”_blank”><img src="'.htmlentities($this->value['photo4']).'" alt="画像4" title="画像4" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '    </div>';
            } elseif (!empty($this->value['photo3']) && $this->value['photo3'] !== 'none') {
                echo '    <div class="photo3">';
                echo '        <a href="'.htmlentities($this->value['photo1']).'" target=”_blank”><img src="'.htmlentities($this->value['photo1']).'" alt="画像1" title="画像1" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        <a href="'.htmlentities($this->value['photo2']).'" target=”_blank”><img src="'.htmlentities($this->value['photo2']).'" alt="画像2" title="画像2" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        <div class="photo3_btm">';
                echo '            <a href="'.htmlentities($this->value['photo3']).'" target=”_blank”><img src="'.htmlentities($this->value['photo3']).'" alt="画像3" title="画像3" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        </div>';
                echo '    </div>';
            } elseif (!empty($this->value['photo2']) && $this->value['photo2'] !== 'none') {
                echo '    <div class="photo2">';
                echo '        <a href="'.htmlentities($this->value['photo1']).'" target=”_blank”><img src="'.htmlentities($this->value['photo1']).'" alt="画像1" title="画像1" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        <a href="'.htmlentities($this->value['photo2']).'" target=”_blank”><img src="'.htmlentities($this->value['photo2']).'" alt="画像2" title="画像2" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '    </div>';
            } elseif (!empty($this->value['photo1']) && $this->value['photo1'] !== 'none') {
                echo '    <div class="photo1">';
                echo '        <a href="'.htmlentities($this->value['photo1']).'" target=”_blank”><img src="'.htmlentities($this->value['photo1']).'" alt="画像1" title="画像1" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '    </div>';
            }
            if (!empty($this->value['video1']) && $this->value['video1'] !== 'none') {
                echo '    <div class="video1">';
                echo '        <video controls src="' . htmlentities($this->value['video1']) . '"></video>';
                echo '    </div>';
            }elseif (!empty(YouTube_and_nicovideo_Links($this->value['ueuse']))) {
                echo '    <div class="youtube_and_nicovideo_player">';
                echo '    '.YouTube_and_nicovideo_Links($this->value['ueuse']).'';
                echo '    </iframe></div>';
            }

            if(!($this->value['abi'] == "none")){
                echo '<div class="abi">';
                echo '  <div class="back">';
                echo '<h1>' . replaceProfileEmojiImages(htmlentities($this->value['username'])) . 'さんが追記しました</h1>';
                echo '  </div>';
                echo '<p>'.processMarkdownAndWrapEmptyLines(replaceEmojisWithImages(replaceURLsWithLinks(nl2br($this->value['abi'])))) . '</p>';
                echo '<div class="h3s">追記日時 : '. date("Y年m月d日 H:i", strtotime(htmlentities($this->value['abidate']))) . '</div>';
                echo '</div>';
            }
            if($this->value['nsfw'] === "true"){
                echo '    </div>';
                echo '    </div>';
            }
            
            echo '<hr>';
            echo '<div class="favbox">';
            $favoriteList = explode(',', $this->value['favorite']);
            if (in_array($this->userid, $favoriteList)) {
                echo '<button class="favbtn favbtn_after" id="favbtn"  data-uniqid="' . htmlentities($this->value['uniqid']) . '" data-userid2="' . htmlentities($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_2.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . htmlentities($this->value['favcnt']) . '</span></button>';
            }else{
                echo '<button class="favbtn" id="favbtn"  data-uniqid="' . htmlentities($this->value['uniqid']) . '" data-userid2="' . htmlentities($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_1.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . htmlentities($this->value['favcnt']) . '</span></button>';
            }
            echo '<a href="/!'.htmlentities($this->value['uniqid']). '~' . htmlentities($this->value['account']) . '" class="tuduki"><svg><use xlink:href="../img/sysimage/reply_1.svg#reply_1"></use></svg>'.htmlentities($this->value['reply_count']).'</a>';
            echo '<button name="share" id="share" class="share" data-uniqid="' . htmlentities($this->value['uniqid']) . '" data-userid="' . htmlentities($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/share_1.svg#share_1"></use></svg></button>';
            
            $bookmarkList = explode(',', $this->value['bookmark']);
            if (in_array($this->value['uniqid'], $bookmarkList)) {
                echo '<button name="bookmark" id="bookmark" class="bookmark bookmark_after" data-uniqid="' . htmlentities($this->value['uniqid']) . '" data-userid="' . htmlentities($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
            }else{
                echo '<button name="bookmark" id="bookmark" class="bookmark" data-uniqid="' . htmlentities($this->value['uniqid']) . '" data-userid="' . htmlentities($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
            }
                
            if($this->value['account'] === $this->userid){
                if(!($this->value['role'] === "ice")){
                    if($this->value['abi'] === "none"){
                        echo '<button name="addabi" id="addabi" data-uniqid2="' . htmlentities($this->value['uniqid']) . '" class="addabi"><svg><use xlink:href="../img/sysimage/addabi_1.svg#addabi_1"></use></svg></button>';
                    }
                }
                echo '<input type="submit" name="delueuse" id="uniqid2" data-uniqid2="' . htmlentities($this->value['uniqid']) . '" class="delbtn" value="削除">';
            }
            echo '</div>';
            echo '</div>';

        }
    }
}
?>