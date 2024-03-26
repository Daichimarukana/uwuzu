
<?php 
class UserdataDisplay {
    private $uservalue;
    private $userid;

    public function __construct($uservalue, $userid) {
        $this->value = $uservalue;
        $this->userid = $userid;
    }

    public function display() {

        if (empty($this->value)) {
            echo '<div class="tokonone" id="noueuse"><p>ユーザーがいません</p></div>';
        } else {
            echo '<div class="ueuse">';
        
            echo '<div class="headbox">';
            echo '  <a href="/@' . htmlentities($this->value['userid'], ENT_QUOTES, 'UTF-8', false) . '"><img src="'. htmlentities('../'.$this->value['headname'], ENT_QUOTES, 'UTF-8', false) . '"></a>';
            echo '</div>';

            echo '<div class="flebox">';
            echo '    <div class="user">';
            
            echo '        <a href="/@' . htmlentities($this->value['userid'], ENT_QUOTES, 'UTF-8', false) . '"><img src="'. htmlentities('../'.$this->value['iconname'], ENT_QUOTES, 'UTF-8', false) . '"></a>';
            echo '        <div class="u_name"><a href="/@' . htmlentities($this->value['userid'], ENT_QUOTES, 'UTF-8', false) . '">' . replaceEmojisWithImages(htmlentities($this->value['username'], ENT_QUOTES, 'UTF-8', false)) . '</a></div>';
            echo '        <div class="idbox">';
            echo '            <a href="/@' . htmlentities($this->value['userid'], ENT_QUOTES, 'UTF-8', false) . '">@' . htmlentities($this->value['userid'], ENT_QUOTES, 'UTF-8', false) . '</a>';
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
            echo '</div>';
            echo '</div>';
            
            echo '<div class="profilebox">';
            echo '    <p>' .replaceEmojisWithImages(replaceURLsWithLinks(nl2br(htmlentities($this->value['profile'], ENT_QUOTES, 'UTF-8', false)))) . '</h1></h2></h3></font></center></p>';
            echo '</div>';

            echo '</div>';

        }
    }
}
?>