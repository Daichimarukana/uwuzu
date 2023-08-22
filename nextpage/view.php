
<?php 
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
            
            echo '        <a href="/@' . htmlentities($this->value['account']) . '"><img src="../home/tlimage.php?account=' . urlencode($this->value['account']) . '"></a>';
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
            $day = date("Ymd", strtotime(htmlentities($this->value['datetime'])));
            if ($day == date("Ymd")) {
                echo date("今日 H:i", strtotime(htmlentities($this->value['datetime'])));
            } else {
                echo date("Y年m月d日 H:i", strtotime(htmlentities($this->value['datetime'])));
            }
            echo '        </div>';
            
            echo '    </div>';
            
            echo '    <p>' . replaceEmojisWithImages(replaceURLsWithLinks(nl2br($this->value['ueuse']))) . '</h1></h2></h3></font></center></p>';
            
            if (!empty($this->value['photo2']) && $this->value['photo2'] !== 'none') {
                echo '    <div class="photo2">';
                echo '        <img src="' . htmlentities($this->value['photo1']) . '" alt="画像">';
                echo '        <img src="' . htmlentities($this->value['photo2']) . '" alt="画像">';
                echo '    </div>';
            } elseif (!empty($this->value['photo1']) && $this->value['photo1'] !== 'none') {
                echo '    <div class="photo1">';
                echo '        <img src="' . htmlentities($this->value['photo1']) . '" alt="画像">';
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
                echo '<button class="favbtn favbtn_after" id="favbtn"  data-uniqid="' . htmlentities($this->value['uniqid']) . '" data-userid2="' . htmlentities($this->value['account']) . '"><img src="../img/sysimage/favorite_2.svg" alt="いいね" /> <span class="like-count">' . htmlentities($this->value['favcnt']) . '</span></button>';
            }else{
                echo '<button class="favbtn" id="favbtn"  data-uniqid="' . htmlentities($this->value['uniqid']) . '" data-userid2="' . htmlentities($this->value['account']) . '"><img src="../img/sysimage/favorite_1.svg" alt="いいね" /> <span class="like-count">' . htmlentities($this->value['favcnt']) . '</span></button>';
            }
            echo '<a href="/!'.htmlentities($this->value['uniqid']). '~' . htmlentities($this->value['account']) . '" class="tuduki">返信をみる&する</a>';
            if($this->value['account'] === $this->userid){
                if($this->value['abi'] === "none"){
                    echo '<input type="submit" name="addabi" id="addabi" data-uniqid2="' . htmlentities($this->value['uniqid']) . '" class="addabi" value="追記する">';
                }
                echo '<input type="submit" name="delueuse" id="uniqid2" data-uniqid2="' . htmlentities($this->value['uniqid']) . '" class="delbtn" value="削除">';
            }
            echo '</div>';
            echo '</div>';
        }
    }
}
?>