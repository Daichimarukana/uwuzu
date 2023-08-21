<?php 
class MessageDisplay {
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }
    
    public function display() {
        if($this->value['userchk'] === "none"){
            echo '<div class="notification2">';
        }else{
            echo '<div class="notification">';
        }
        echo '    <div class="flebox">';
            
        echo '        <div class="time">';
        $day = date("Ymd", strtotime(htmlentities($this->value['datetime'])));
        if ($day == date("Ymd")) {
            echo date("今日 H:i", strtotime(htmlentities($this->value['datetime'])));
        } else {
            echo date("Y年m月d日 H:i", strtotime(htmlentities($this->value['datetime'])));
        }
        echo '        </div>';
            
        echo '    </div>';
            
        // 投稿内のHTMLコードを表示する部分
        echo '    <h3>' . htmlentities($this->value['title']) . '</h3>';
        echo '    <p>' . htmlentities($this->value['msg']) . '</p>';
        echo '    <a href="' . htmlentities($this->value['url']) . '">詳細をみる</a>';
            
        echo '</div>';
    }
}
?>
