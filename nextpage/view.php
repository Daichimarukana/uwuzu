
<?php 
//関数呼び出し
//- 文字装飾・URL変換など
require('../function/function.php');

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
            
            echo '        <a href="/@' . htmlentities($this->value['account'], ENT_QUOTES, 'UTF-8', false) . '"><img src="'. htmlentities('../'.$this->value['iconname'], ENT_QUOTES, 'UTF-8', false) . '"></a>';
            echo '        <a href="/@' . htmlentities($this->value['account'], ENT_QUOTES, 'UTF-8', false) . '"><div class="u_name">' . replaceProfileEmojiImages(htmlentities($this->value['username'], ENT_QUOTES, 'UTF-8', false)) . '</div></a>';
            echo '        <div class="idbox">';
            echo '            <a href="/@' . htmlentities($this->value['account'], ENT_QUOTES, 'UTF-8', false) . '">@' . htmlentities($this->value['account'], ENT_QUOTES, 'UTF-8', false) . '</a>';
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
            $datetime = strtotime(htmlentities($this->value['datetime'], ENT_QUOTES, 'UTF-8', false));
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
                echo '    <div class="nsfw" data-uniqid="' . htmlentities($this->value['uniqid'], ENT_QUOTES, 'UTF-8', false) . '">';
                echo '    <p>NSFW指定がされている投稿です！<br>職場や公共の場での表示には適さない場合があります。<br>表示ボタンを押すと表示されます。</p>';
                echo '    <div class="btnzone">';
                echo '    <input type="button" id="nsfw_view" class="mini_irobtn" value="表示">';
                echo '    </div>';
                echo '    </div>';
                echo '    <div class="nsfw_main" data-uniqid="' . htmlentities($this->value['uniqid'], ENT_QUOTES, 'UTF-8', false) . '">';
                echo '    <div class="block">';
            }
            echo '    <p>' . processMarkdownAndWrapEmptyLines(replaceEmojisWithImages(replaceURLsWithLinks(nl2br(htmlentities($this->value['ueuse'], ENT_QUOTES, 'UTF-8', false))))) . '</h1></h2></h3></font></center></p>';
            
            if (!empty($this->value['photo4']) && $this->value['photo4'] !== 'none') {
                echo '    <div class="photo4">';
                echo '        <a><img src="'.htmlentities($this->value['photo1'], ENT_QUOTES, 'UTF-8', false).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        <a><img src="'.htmlentities($this->value['photo2'], ENT_QUOTES, 'UTF-8', false).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        <a><img src="'.htmlentities($this->value['photo3'], ENT_QUOTES, 'UTF-8', false).'" alt="画像3" title="画像3" data-id="3" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        <a><img src="'.htmlentities($this->value['photo4'], ENT_QUOTES, 'UTF-8', false).'" alt="画像4" title="画像4" data-id="4" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '    </div>';
            } elseif (!empty($this->value['photo3']) && $this->value['photo3'] !== 'none') {
                echo '    <div class="photo3">';
                echo '        <a><img src="'.htmlentities($this->value['photo1'], ENT_QUOTES, 'UTF-8', false).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        <a><img src="'.htmlentities($this->value['photo2'], ENT_QUOTES, 'UTF-8', false).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        <div class="photo3_btm">';
                echo '            <a><img src="'.htmlentities($this->value['photo3'], ENT_QUOTES, 'UTF-8', false).'" alt="画像3" title="画像3" data-id="3" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        </div>';
                echo '    </div>';
            } elseif (!empty($this->value['photo2']) && $this->value['photo2'] !== 'none') {
                echo '    <div class="photo2">';
                echo '        <a><img src="'.htmlentities($this->value['photo1'], ENT_QUOTES, 'UTF-8', false).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '        <a><img src="'.htmlentities($this->value['photo2'], ENT_QUOTES, 'UTF-8', false).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '    </div>';
            } elseif (!empty($this->value['photo1']) && $this->value['photo1'] !== 'none') {
                echo '    <div class="photo1">';
                echo '        <a><img src="'.htmlentities($this->value['photo1'], ENT_QUOTES, 'UTF-8', false).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                echo '    </div>';
            }
            if (!empty($this->value['video1']) && $this->value['video1'] !== 'none') {
                echo '    <div class="video1">';
                echo '        <video controls src="' . htmlentities($this->value['video1'], ENT_QUOTES, 'UTF-8', false) . '"></video>';
                echo '    </div>';
            }elseif (!empty(YouTube_and_nicovideo_Links($this->value['ueuse']))) {
                echo '    <div class="youtube_and_nicovideo_player">';
                echo '    '.YouTube_and_nicovideo_Links($this->value['ueuse']).'';
                echo '    </iframe></div>';
            }

            if(!($this->value['abi'] == "none")){
                echo '<div class="abi">';
                echo '  <div class="back">';
                echo '<h1>' . replaceProfileEmojiImages(htmlentities($this->value['username'], ENT_QUOTES, 'UTF-8', false)) . 'さんが追記しました</h1>';
                echo '  </div>';
                echo '<p>'.processMarkdownAndWrapEmptyLines(replaceEmojisWithImages(replaceURLsWithLinks(nl2br(htmlentities($this->value['abi'], ENT_QUOTES, 'UTF-8', false))))) . '</p>';
                echo '<div class="h3s">追記日時 : '. date("Y年m月d日 H:i", strtotime(htmlentities($this->value['abidate'], ENT_QUOTES, 'UTF-8', false))) . '</div>';
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
                echo '<button class="favbtn favbtn_after" id="favbtn"  data-uniqid="' . htmlentities($this->value['uniqid'], ENT_QUOTES, 'UTF-8', false) . '" data-userid2="' . htmlentities($this->value['account'], ENT_QUOTES, 'UTF-8', false) . '"><svg><use xlink:href="../img/sysimage/favorite_2.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . htmlentities($this->value['favcnt']) . '</span></button>';
            }else{
                echo '<button class="favbtn" id="favbtn"  data-uniqid="' . htmlentities($this->value['uniqid'], ENT_QUOTES, 'UTF-8', false) . '" data-userid2="' . htmlentities($this->value['account'], ENT_QUOTES, 'UTF-8', false) . '"><svg><use xlink:href="../img/sysimage/favorite_1.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . htmlentities($this->value['favcnt']) . '</span></button>';
            }
            echo '<a href="/!'.htmlentities($this->value['uniqid'], ENT_QUOTES, 'UTF-8', false). '" class="tuduki"><svg><use xlink:href="../img/sysimage/reply_1.svg#reply_1"></use></svg>'.htmlentities($this->value['reply_count'], ENT_QUOTES, 'UTF-8', false).'</a>';
            echo '<button name="share" id="share" class="share" data-uniqid="' . htmlentities($this->value['uniqid'], ENT_QUOTES, 'UTF-8', false) . '" data-userid="' . htmlentities($this->value['account'], ENT_QUOTES, 'UTF-8', false) . '"><svg><use xlink:href="../img/sysimage/share_1.svg#share_1"></use></svg></button>';
            
            $bookmarkList = explode(',', $this->value['bookmark']);
            if (in_array($this->value['uniqid'], $bookmarkList)) {
                echo '<button name="bookmark" id="bookmark" class="bookmark bookmark_after" data-uniqid="' . htmlentities($this->value['uniqid'], ENT_QUOTES, 'UTF-8', false) . '" data-userid="' . htmlentities($this->value['account'], ENT_QUOTES, 'UTF-8', false) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
            }else{
                echo '<button name="bookmark" id="bookmark" class="bookmark" data-uniqid="' . htmlentities($this->value['uniqid'], ENT_QUOTES, 'UTF-8', false) . '" data-userid="' . htmlentities($this->value['account'], ENT_QUOTES, 'UTF-8', false) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
            }
                
            if($this->value['account'] === $this->userid){
                if(!($this->value['role'] === "ice")){
                    if($this->value['abi'] === "none"){
                        echo '<button name="addabi" id="addabi" data-uniqid2="' . htmlentities($this->value['uniqid'], ENT_QUOTES, 'UTF-8', false) . '" class="addabi"><svg><use xlink:href="../img/sysimage/addabi_1.svg#addabi_1"></use></svg></button>';
                    }
                }
                echo '<input type="submit" name="delueuse" id="uniqid2" data-uniqid2="' . htmlentities($this->value['uniqid'], ENT_QUOTES, 'UTF-8', false) . '" class="delbtn" value="削除">';
            }
            echo '</div>';
            echo '</div>';

        }
    }
}
?>