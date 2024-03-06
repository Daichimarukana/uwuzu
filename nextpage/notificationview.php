<?php 
function processMarkdownAndWrapEmptyLines($markdownText){

    //\___________________[注意]__________________\
    // \____ここの順番を変えるとうまく動かなくなります___\
    //  \______Markdownうまく動くところを探すべし______\

    $markdownText = preg_replace('/\[\[buruburu (.+)\]\]/m', '<span class="buruburu">$1</span>', $markdownText);//ぶるぶる

    $markdownText = preg_replace('/(^|[^`])`([^`\n]+)`($|[^`])/m', '$1<span class="inline">$2</span>$3', $markdownText);//Inline Code

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
            return "<a class = 'mta' href='/@".htmlspecialchars($mentionsuserData["userid"], ENT_QUOTES, 'UTF-8', false)."'>@".replaceProfileEmojiImages(htmlspecialchars($mentionsuserData["username"], ENT_QUOTES, 'UTF-8', false))."</a>";
        }
    }, $postTextWithImages);

    $hashtagsPattern = '/#([\p{Han}\p{Hiragana}\p{Katakana}A-Za-z0-9ー_]+)/u';
    $postTextWithHashtags = preg_replace_callback($hashtagsPattern, function($matches) {
        $hashtags = $matches[1];
        return "<a class='hashtags' href='/search?q=" . urlencode('#') . htmlspecialchars($hashtags, ENT_QUOTES, 'UTF-8', false) . "'>" . '#' . htmlspecialchars($hashtags, ENT_QUOTES, 'UTF-8', false) . "</a>";
    }, $postTextWithImagesAndUsernames);

    return $postTextWithHashtags;
}

class MessageDisplay {
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }
    
    public function display() {
        if($this->value['userchk'] === "none"){
            echo '<div class="notification this">';
        }else{
            echo '<div class="notification">';
        }
        echo '    <div class="flebox">';
            
        echo '        <div class="time">';
        $day = date("Ymd", strtotime(htmlspecialchars($this->value['datetime'], ENT_QUOTES, 'UTF-8', false)));
        if ($day == date("Ymd")) {
            echo date("今日 H:i", strtotime(htmlspecialchars($this->value['datetime'], ENT_QUOTES, 'UTF-8', false)));
        } else {
            echo date("Y年m月d日 H:i", strtotime(htmlspecialchars($this->value['datetime'], ENT_QUOTES, 'UTF-8', false)));
        }
        echo '        </div>';
            
        echo '    </div>';
            
        // 投稿内のHTMLコードを表示する部分
        if(!(empty($this->value['fromuserid']))){
            echo '    <div class="flebox">';
                echo '    <div class="icon">';
                    if(($this->value['fromuserid'] == "uwuzu-fromsys")){
                        if(!(empty($this->value["servericon"]))){
                            echo '    <a href="/rule/serverabout"><img src="'.htmlspecialchars($this->value["servericon"], ENT_QUOTES, 'UTF-8', false).'"></a>';
                        }else{
                            echo '    <a href="/rule/serverabout"><img src="../img/uwuzuicon.png"></a>';
                        }
                    }else{
                        echo '    <a href="/@'.htmlspecialchars($this->value['fromuserid'], ENT_QUOTES, 'UTF-8', false).'"><img src="' . htmlspecialchars($this->value['fromusericon'], ENT_QUOTES, 'UTF-8', false) . '"></a>';
                    }
                echo '    </div>';
                if(($this->value['fromuserid'] == "uwuzu-fromsys")){
                    echo '    <div class="username"><a href="/rule/serverabout">uwuzu</a></div>';
                }else{
                    echo '    <div class="username"><a href="/@'.htmlspecialchars($this->value['fromuserid'], ENT_QUOTES, 'UTF-8', false).'">'.htmlspecialchars($this->value['fromusername'], ENT_QUOTES, 'UTF-8', false).'</a></div>';
                }
            echo '    </div>';
        }
        echo '    <h3>' . replaceEmojisWithImages(htmlspecialchars($this->value['title'], ENT_QUOTES, 'UTF-8', false)) . '</h3>';
        echo '    <p>' . processMarkdownAndWrapEmptyLines(replaceEmojisWithImages(nl2br(htmlspecialchars($this->value['msg'], ENT_QUOTES, 'UTF-8', false)))) . '</p>';
        echo '    <a href="' . htmlspecialchars($this->value['url'], ENT_QUOTES, 'UTF-8', false) . '">詳細をみる</a>';
            
        echo '</div>';
    }
}
?>
