
<?php 
function processMarkdownAndWrapEmptyLines($markdownText){

    $markdownText = preg_replace('/^\[\[buruburu (.+)\]\]/m', '<p class="buruburu">$1</p>', $markdownText);//←ここ！！！！！！！！！！！！！！
    // コード（#code）をHTMLのdiv class="code"タグに変換
    $markdownText = preg_replace('/^#code (.+)/m', '<div class="code"><p>$1</p></div>', $markdownText);

    // 画像（#img）をHTMLのimgタグに変換
    $markdownText = preg_replace('/^#img (.+)/m', '<img src="$1">', $markdownText);

    // タイトル（#、##、###）をHTMLのhタグに変換
    $markdownText = preg_replace('/^# (.+)/m', '<h1>$1</h1>', $markdownText);
    $markdownText = preg_replace('/^## (.+)/m', '<h2>$1</h2>', $markdownText);
    $markdownText = preg_replace('/^### (.+)/m', '<h3>$1</h3>', $markdownText);

    // 箇条書き（-）をHTMLのul/liタグに変換
    $markdownText = preg_replace('/^- (.+)/m', '<ul><li>$1</li></ul>', $markdownText);

    // 空行の前に何もない行をHTMLのpタグに変換
    $markdownText = preg_replace('/(^\s*)(?!\s)(.*)/m', '$1<p>$2</p>', $markdownText);

    return $markdownText;
}

// ユーズ内の絵文字を画像に置き換える
function replaceEmojisWithImages($postText) {
    // ユーズ内で絵文字名（:emoji:）を検出して画像に置き換える
    $emojiPattern = '/:(\w+):/';
    $postTextWithImages = preg_replace_callback($emojiPattern, function($matches) {
        $emojiName = $matches[1];
        return "<img src='../emoji/emojiimage.php?emoji=" . urlencode($emojiName) . "' alt=':$emojiName:' title=':$emojiName:'>";
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
            return "<a class = 'mta' href='/@".$mentionsuserData["userid"]."'>@".$mentionsuserData["username"]."</a>";
        }
    }, $postTextWithImages);

    $hashtagsPattern = '/#([\p{Han}\p{Hiragana}\p{Katakana}A-Za-z0-9_]+)/u';
    $postTextWithHashtags = preg_replace_callback($hashtagsPattern, function($matches) {
        $hashtags = $matches[1];
            return "<a class = 'hashtags' href='/search?q=".urlencode('#').$hashtags."'>".'#'.$hashtags."</a>";
    }, $postTextWithImagesAndUsernames);

    return $postTextWithHashtags;
}

function replaceURLsWithLinks($postText) {
    // URLを正規表現を使って検出
    $pattern = '/(https:\/\/[^\s]+)/';
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
            echo '        <a href="/@' . htmlentities($this->value['account']) . '">' . htmlentities($this->value['username']) . '</a>';
            echo '        <div class="idbox">';
            echo '            <a href="/@' . htmlentities($this->value['account']) . '">@' . htmlentities($this->value['account']) . '</a>';
            echo '        </div>';
            if (false !== strpos($this->value['role'], 'official')) {
                echo '      <div class="checkicon">';
                echo '          <div class="check" />';
                echo '      </div>';
                echo '</div>';
            }
            
            echo '        <div class="time">';
            $datetime = strtotime(htmlentities($this->value['datetime']));
            $today = strtotime(date("Y-m-d"));
            if (date("md", $datetime) == "0101") {
                if (date("Y", $datetime) == date("Y")) {
                    echo "元日 " . date("H:i", $datetime);
                } else {
                    echo date("Y年m月d日 H:i", $datetime);
                }
            } elseif ($datetime >= $today) {
                echo "今日 " . date("H:i", $datetime);
            } elseif (date("Y", $datetime) == date("Y")) {
                echo date("m月d日 H:i", $datetime);
            } else {
                echo date("Y年m月d日 H:i", $datetime);
            }            
            echo '        </div>';
            
            echo '    </div>';
            
            echo '    <p>' . processMarkdownAndWrapEmptyLines(replaceEmojisWithImages(replaceURLsWithLinks(nl2br($this->value['ueuse'])))) . '</h1></h2></h3></font></center></p>';
            
            if (!empty($this->value['photo2']) && $this->value['photo2'] !== 'none') {
                echo '    <div class="photo2">';
                echo '        <a href="'.htmlentities($this->value['photo1']).'" target=”_blank”><img src="'.htmlentities($this->value['photo1']).'" alt="画像1" title="画像1"></a>';
                echo '        <a href="'.htmlentities($this->value['photo2']).'" target=”_blank”><img src="'.htmlentities($this->value['photo2']).'" alt="画像2" title="画像2"></a>';
                echo '    </div>';
            } elseif (!empty($this->value['photo1']) && $this->value['photo1'] !== 'none') {
                echo '    <div class="photo1">';
                echo '        <a href="'.htmlentities($this->value['photo1']).'" target=”_blank”><img src="'.htmlentities($this->value['photo1']).'" alt="画像1" title="画像1"></a>';
                echo '    </div>';
            }
            if (!empty($this->value['video1']) && $this->value['video1'] !== 'none') {
                echo '    <div class="video1">';
                echo '        <video controls src="' . htmlentities($this->value['video1']) . '"></video>';
                echo '    </div>';
            }

            if(!($this->value['abi'] == "none")){
                echo '<div class="abi">';
                echo '  <div class="back">';
                echo '<h1>' . htmlentities($this->value['username']) . 'さんが追記しました</h1>';
                echo '  </div>';
                echo '<p>'.replaceEmojisWithImages(replaceURLsWithLinks(nl2br($this->value['abi']))) . '</p>';
                echo '<h3>追記日時 : '. date("Y年m月d日 H:i", strtotime(htmlentities($this->value['abidate']))) . '</h3>';
                echo '</div>';
            }
            
            echo '<hr>';
            echo '<div class="favbox">';
            if (false !== strstr($this->value['favorite'], $this->userid)) {
                echo '<button class="favbtn favbtn_after" id="favbtn"  data-uniqid="' . htmlentities($this->value['uniqid']) . '" data-userid2="' . htmlentities($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_2.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . htmlentities($this->value['favcnt']) . '</span></button>';
            }else{
                echo '<button class="favbtn" id="favbtn"  data-uniqid="' . htmlentities($this->value['uniqid']) . '" data-userid2="' . htmlentities($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_1.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . htmlentities($this->value['favcnt']) . '</span></button>';
            }
            echo '<a href="/!'.htmlentities($this->value['uniqid']). '~' . htmlentities($this->value['account']) . '" class="tuduki"><svg><use xlink:href="../img/sysimage/reply_1.svg#reply_1"></use></svg>'.htmlentities($this->value['reply_count']).'</a>';
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