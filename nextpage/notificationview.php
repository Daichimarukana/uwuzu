<?php 
//関数呼び出し
//- 文字装飾・URL変換など
require('../function/function.php');

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
        $day = date("Ymd", strtotime(htmlentities($this->value['datetime'], ENT_QUOTES, 'UTF-8', false)));
        if ($day == date("Ymd")) {
            echo date("今日 H:i", strtotime(htmlentities($this->value['datetime'], ENT_QUOTES, 'UTF-8', false)));
        } else {
            echo date("Y年m月d日 H:i", strtotime(htmlentities($this->value['datetime'], ENT_QUOTES, 'UTF-8', false)));
        }
        echo '        </div>';
            
        echo '    </div>';
            
        // 投稿内のHTMLコードを表示する部分
        if(!(empty($this->value['fromuserid']))){
            echo '    <div class="flebox">';
                echo '    <div class="icon">';
                    if(($this->value['fromuserid'] == "uwuzu-fromsys")){
                        if(!(empty($this->value["servericon"]))){
                            echo '    <a href="/rule/serverabout"><img src="'.htmlentities($this->value["servericon"], ENT_QUOTES, 'UTF-8', false).'"></a>';
                        }else{
                            echo '    <a href="/rule/serverabout"><img src="../img/uwuzuicon.png"></a>';
                        }
                    }else{
                        echo '    <a href="/@'.htmlentities($this->value['fromuserid'], ENT_QUOTES, 'UTF-8', false).'"><img src="' . htmlentities($this->value['fromusericon'], ENT_QUOTES, 'UTF-8', false) . '"></a>';
                    }
                echo '    </div>';
                if(($this->value['fromuserid'] == "uwuzu-fromsys")){
                    echo '    <div class="username"><a href="/rule/serverabout">uwuzu</a></div>';
                }else{
                    echo '    <div class="username"><a href="/@'.htmlentities($this->value['fromuserid'], ENT_QUOTES, 'UTF-8', false).'">'.replaceEmojisWithImages(htmlentities($this->value['fromusername'], ENT_QUOTES, 'UTF-8', false)).'</a></div>';
                }
            echo '    </div>';
        }
        echo '    <h3>' . replaceEmojisWithImages(htmlentities($this->value['title'], ENT_QUOTES, 'UTF-8', false)) . '</h3>';
        echo '    <p>' . processMarkdownAndWrapEmptyLines(replaceEmojisWithImages(nl2br(htmlentities($this->value['msg'], ENT_QUOTES, 'UTF-8', false)))) . '</p>';
        echo '    <a href="' . htmlentities($this->value['url'], ENT_QUOTES, 'UTF-8', false) . '">詳細をみる</a>';
            
        echo '</div>';
    }
}
?>
