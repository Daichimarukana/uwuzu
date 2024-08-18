<?php 

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
        $day = date("Ymd", strtotime(safetext($this->value['datetime'])));
        if ($day == date("Ymd")) {
            echo date("今日 H:i", strtotime(safetext($this->value['datetime'])));
        } else {
            echo date("Y年m月d日 H:i", strtotime(safetext($this->value['datetime'])));
        }
        echo '        </div>';
            
        echo '    </div>';
            
        // 投稿内のHTMLコードを表示する部分
        if(!(empty($this->value['fromuserid']))){
            echo '    <div class="flebox">';
                echo '    <div class="icon">';
                    if(($this->value['fromuserid'] == "uwuzu-fromsys")){
                        if(!(empty($this->value["servericon"]))){
                            echo '    <a href="/rule/serverabout"><img src="'.safetext($this->value["servericon"]).'"></a>';
                        }else{
                            echo '    <a href="/rule/serverabout"><img src="../img/uwuzuicon.png"></a>';
                        }
                    }else{
                        echo '    <a href="/@'.safetext($this->value['fromuserid']).'"><img src="' . safetext($this->value['fromusericon']) . '"></a>';
                    }
                echo '    </div>';
                if(($this->value['fromuserid'] == "uwuzu-fromsys")){
                    echo '    <div class="username"><a href="/rule/serverabout">uwuzu</a></div>';
                }else{
                    echo '    <div class="username"><a href="/@'.safetext($this->value['fromuserid']).'">'.replaceEmojisWithImages(safetext($this->value['fromusername'])).'</a></div>';
                }
            echo '    </div>';
        }
        echo '    <h3>' . replaceEmojisWithImages(safetext($this->value['title'])) . '</h3>';
        echo '    <p>' . processMarkdownAndWrapEmptyLines(replaceEmojisWithImages(nl2br(safetext($this->value['msg'])))) . '</p>';
        echo '    <a href="' . safetext($this->value['url']) . '">詳細をみる</a>';
            
        echo '</div>';
    }
}
?>
