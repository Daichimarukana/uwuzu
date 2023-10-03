
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
            echo '  <a href="/@' . htmlentities($this->value['userid']) . '"><img src="'. htmlentities('../'.$this->value['headname']) . '"></a>';
            echo '</div>';

            echo '<div class="flebox">';
            echo '    <div class="user">';
            
            echo '        <a href="/@' . htmlentities($this->value['userid']) . '"><img src="'. htmlentities('../'.$this->value['iconname']) . '"></a>';
            echo '        <a href="/@' . htmlentities($this->value['userid']) . '">' . htmlentities($this->value['username']) . '</a>';
            echo '        <div class="idbox">';
            echo '            <a href="/@' . htmlentities($this->value['userid']) . '">@' . htmlentities($this->value['userid']) . '</a>';
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
            echo '    <p>' .replaceEmojisWithImages(replaceURLsWithLinks(nl2br($this->value['profile']))) . '</h1></h2></h3></font></center></p>';
            echo '</div>';

            echo '</div>';

        }
    }
}
?>