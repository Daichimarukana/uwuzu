<?php 
class MessageDisplay {
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }
    
    public function display() {
        echo '<div class="notification">';
        echo '    <div class="flebox">';
            
        echo '        <div class="time">';
        $day = date("Ymd", strtotime(htmlspecialchars($this->value['datetime'])));
        if ($day == date("Ymd")) {
            echo date("今日 H:i", strtotime(htmlspecialchars($this->value['datetime'])));
        } else {
            echo date("Y年m月d日 H:i", strtotime(htmlspecialchars($this->value['datetime'])));
        }
        echo '        </div>';
            
        echo '    </div>';
            
        // 投稿内のHTMLコードを表示する部分
        echo '    <h3>' . htmlspecialchars($this->value['title']) . '</h3>';
        echo '    <p>' . htmlspecialchars($this->value['msg']) . '</p>';
        echo '    <a href="' . htmlspecialchars($this->value['url']) . '">続きをみる</a>';
            
        echo '</div>';
    }
}
?>
