
<?php 
function GetOriginalUeuse($ruUniqid,$userid){
    try {
        $option = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        );
        $pdo = new PDO('mysql:charset=utf8mb4;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);
    } catch (PDOException $e) {
        // 接続エラーのときエラー内容を取得する
        $error_message[] = $e->getMessage();
    }
    if (!empty($pdo)) {
        $aduserinfoQuery = $pdo->prepare("SELECT username,userid,loginid,admin,role,sacinfo,blocklist,bookmark FROM account WHERE userid = :userid");
        $aduserinfoQuery->bindValue(':userid', safetext($userid));
        $aduserinfoQuery->execute();
        $res = $aduserinfoQuery->fetch();
        $myblocklist = safetext($res["blocklist"]);
        $mybookmark = safetext($res["bookmark"]);

        $sql = "SELECT * FROM ueuse WHERE uniqid = :ruUniqid ORDER BY datetime DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':ruUniqid', $ruUniqid, PDO::PARAM_STR);
        $stmt->execute();
        $message_array = $stmt;

        while ($row = $message_array->fetch(PDO::FETCH_ASSOC)) {
            $messages[] = $row;
        }
        // ユーザー情報を取得して、$messages内のusernameをuserDataのusernameに置き換える
        if(!(empty($messages))){
            foreach ($messages as &$message) {
                $userQuery = $pdo->prepare("SELECT username, userid, profile, role, iconname, headname, sacinfo FROM account WHERE userid = :userid");
                $userQuery->bindValue(':userid', $message["account"]);
                $userQuery->execute();
                $userData = $userQuery->fetch();
                if ($userData) {
                    $message['iconname'] = $userData['iconname'];
                    $message['headname'] = $userData['headname'];
                    $message['username'] = $userData['username'];
                    $message['sacinfo'] = $userData['sacinfo'];
                    $message['role'] = $userData['role'];
                }
                //リプライ数取得
                $rpQuery = $pdo->prepare("SELECT COUNT(*) as reply_count FROM ueuse WHERE rpuniqid = :rpuniqid");
                $rpQuery->bindValue(':rpuniqid', $message['uniqid']);
                $rpQuery->execute();
                $rpData = $rpQuery->fetch(PDO::FETCH_ASSOC);
                
                if ($rpData){
                    $message['reply_count'] = $rpData['reply_count'];
                }

                //リユーズ数取得
                $ruQuery = $pdo->prepare("SELECT COUNT(*) as reuse_count FROM ueuse WHERE ruuniqid = :ruuniqid");
                $ruQuery->bindValue(':ruuniqid', $message['uniqid']);
                $ruQuery->execute();
                $ruData = $ruQuery->fetch(PDO::FETCH_ASSOC);
                
                if ($ruData){
                    $message['reuse_count'] = $ruData['reuse_count'];
                }
            }

            if (false === strpos($myblocklist, ','.safetext($message['account']))) {
                $fav = $message['favorite']; // コンマで区切られたユーザーIDを含む変数
                $favIds = explode(',', $fav);
                $message["favcnt"] = count($favIds)-1;
                $message["bookmark"] = $mybookmark;
                return $message;
            }
        }else{
            return false;
        }
    }
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
            echo '<div class="ueuse" id="ueuse-'.safetext($this->value['uniqid']).'">';
            if(!empty($this->value['rpuniqid'])){
                echo '<div class="rp"><p>┗━ 一番上のユーズに返信</p></div>';
            }
            if(!empty($this->value['ruuniqid'])){
                $org_ueuse = GetOriginalUeuse($this->value['ruuniqid'], $this->userid);
                if(!(empty($org_ueuse))){
                    if(!(empty($this->value['ueuse']))){
                        //引用リユーズ------------------------------------------------------------------------------------------------------
                        //----------------------------------------------------------------------------------------------------------------
                        //----------------------------------------------------------------------------------------------------------------
                        echo '    <div class="flebox">';
                        echo '        <a href="/@' . safetext($this->value['account']) . '"><img src="'. safetext('../'.$this->value['iconname']) . '"></a>';
                        echo '        <a href="/@' . safetext($this->value['account']) . '"><div class="u_name">' . replaceProfileEmojiImages(safetext($this->value['username'])) . '</div></a>';
                        echo '        <div class="idbox">';
                        echo '            <a href="/@' . safetext($this->value['account']) . '">@' . safetext($this->value['account']) . '</a>';
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
                        $datetime = strtotime(safetext($this->value['datetime']));
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
                            echo '    <div class="nsfw" data-uniqid="' . safetext($this->value['uniqid']) . '">';
                            echo '    <p>NSFW指定がされている投稿です！<br>職場や公共の場での表示には適さない場合があります。<br>表示ボタンを押すと表示されます。</p>';
                            echo '    <div class="btnzone">';
                            echo '    <input type="button" id="nsfw_view" class="mini_irobtn" value="表示">';
                            echo '    </div>';
                            echo '    </div>';
                            echo '    <div class="nsfw_main" data-uniqid="' . safetext($this->value['uniqid']) . '">';
                            echo '    <div class="block">';
                        }
                        echo '    <p>' . replaceEmojisWithImages(processMarkdownAndWrapEmptyLines(replaceURLsWithLinks(nl2br(safetext($this->value['ueuse']))))) . '</h1></h2></h3></font></center></p>';
                        
                        echo '<div class="reuse_box" data-uniqid="' . safetext($org_ueuse['uniqid']) . '" id="quote_reuse">';
                        if($org_ueuse['nsfw'] === "true"){
                            echo '    <div class="nsfw" data-uniqid="' . safetext($this->value['uniqid']) . '">';
                            echo '    <p>NSFW指定がされている投稿です！<br>職場や公共の場での表示には適さない場合があります。<br>表示ボタンを押すと表示されます。</p>';
                            echo '    <div class="btnzone">';
                            echo '    <input type="button" id="nsfw_view" class="mini_irobtn" value="表示">';
                            echo '    </div>';
                            echo '    </div>';
                            echo '    <div class="nsfw_main" data-uniqid="' . safetext($this->value['uniqid']) . '">';
                            echo '    <div class="block">';
                        }
                        echo '<div class="reuse_flebox">';
                        echo '<a href="/!' . safetext($org_ueuse['uniqid']) . '"><img src="'. safetext('../'.$org_ueuse['iconname']) . '"></a>';
                        echo '<a href="/!' . safetext($org_ueuse['uniqid']) . '"><div class="u_name">' . replaceProfileEmojiImages(safetext($org_ueuse['username'])) . '</div></a>';
                        echo '<div class="idbox">';
                        echo '<a href="/@' . safetext($org_ueuse['account']) . '">@' . safetext($org_ueuse['account']) . '</a>';
                        echo '</div>';
                        echo '</div>';
                        echo '<p>'.processMarkdownAndWrapEmptyLines(replaceEmojisWithImages(replaceURLsWithLinks(nl2br(safetext($org_ueuse['ueuse']))))) . '</p>';
                        if($org_ueuse['nsfw'] === "true"){
                            echo '    </div>';
                            echo '    </div>';
                        }
                        echo '</div>';

                        if (!empty($this->value['photo4']) && $this->value['photo4'] !== 'none') {
                            echo '    <div class="photo4">';
                            echo '        <a><img src="'.safetext($this->value['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($this->value['photo2']).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($this->value['photo3']).'" alt="画像3" title="画像3" data-id="3" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($this->value['photo4']).'" alt="画像4" title="画像4" data-id="4" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '    </div>';
                        } elseif (!empty($this->value['photo3']) && $this->value['photo3'] !== 'none') {
                            echo '    <div class="photo3">';
                            echo '        <a><img src="'.safetext($this->value['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($this->value['photo2']).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <div class="photo3_btm">';
                            echo '            <a><img src="'.safetext($this->value['photo3']).'" alt="画像3" title="画像3" data-id="3" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        </div>';
                            echo '    </div>';
                        } elseif (!empty($this->value['photo2']) && $this->value['photo2'] !== 'none') {
                            echo '    <div class="photo2">';
                            echo '        <a><img src="'.safetext($this->value['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($this->value['photo2']).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '    </div>';
                        } elseif (!empty($this->value['photo1']) && $this->value['photo1'] !== 'none') {
                            echo '    <div class="photo1">';
                            echo '        <a><img src="'.safetext($this->value['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '    </div>';
                        }
                        if (!empty($this->value['video1']) && $this->value['video1'] !== 'none') {
                            echo '    <div class="video1">';
                            echo '        <video controls src="' . safetext($this->value['video1']) . '"></video>';
                            echo '    </div>';
                        }elseif (!empty(YouTube_and_nicovideo_Links($this->value['ueuse']))) {
                            echo '    <div class="youtube_and_nicovideo_player">';
                            echo '    '.YouTube_and_nicovideo_Links($this->value['ueuse']).'';
                            echo '    </iframe></div>';
                        }
        
                        if(!($this->value['abi'] == "none")){
                            echo '<div class="abi">';
                            echo '  <div class="back">';
                            echo '<h1>' . replaceProfileEmojiImages(safetext($this->value['username'])) . 'さんが追記しました</h1>';
                            echo '  </div>';
                            echo '<p>'.processMarkdownAndWrapEmptyLines(replaceEmojisWithImages(replaceURLsWithLinks(nl2br(safetext($this->value['abi']))))) . '</p>';
                            echo '<div class="h3s">追記日時 : '. date("Y年m月d日 H:i", strtotime(safetext($this->value['abidate']))) . '</div>';
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
                            echo '<button class="favbtn favbtn_after" id="favbtn"  data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid2="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_2.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . safetext($this->value['favcnt']) . '</span></button>';
                        }else{
                            echo '<button class="favbtn" id="favbtn"  data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid2="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_1.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . safetext($this->value['favcnt']) . '</span></button>';
                        }
                        
                        echo '<button name="reusebtn" id="reusebtn" class="reuse" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/reuse_1.svg#reuse_1"></use></svg> <span class="like-count">' . safetext($this->value['reuse_count']) . '</span></button>';
        
                        echo '<a href="/!'.safetext($this->value['uniqid']). '" class="tuduki"><svg><use xlink:href="../img/sysimage/reply_1.svg#reply_1"></use></svg>'.safetext($this->value['reply_count']).'</a>';
                                    
                        $bookmarkList = explode(',', $this->value['bookmark']);
                        if (in_array($this->value['uniqid'], $bookmarkList)) {
                            echo '<button name="bookmark" id="bookmark" class="bookmark bookmark_after" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
                        }else{
                            echo '<button name="bookmark" id="bookmark" class="bookmark" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
                        }
                            
                        if($this->value['account'] === $this->userid){
                            if(!($this->value['role'] === "ice")){
                                if($this->value['abi'] === "none"){
                                    echo '<button name="addabi" id="addabi" data-uniqid2="' . safetext($this->value['uniqid']) . '" class="addabi"><svg><use xlink:href="../img/sysimage/addabi_1.svg#addabi_1"></use></svg></button>';
                                }
                            }
                        }
        
                        echo '<button name="popup" id="popup" class="etcbtn" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/etc_1.svg#etc_1"></use></svg></button>';
        
                        echo '</div>';
                        echo '</div>';
                    }else{
                        //普通のリユーズ----------------------------------------------------------------------------------------------------
                        //----------------------------------------------------------------------------------------------------------------
                        //----------------------------------------------------------------------------------------------------------------
                        echo '<div class="ru"><a href="/@' . safetext($this->value['account']) . '"><img src="../'.$this->value['iconname'] . '"><p>' . safetext($this->value['username']) . 'さんがリユーズ</p></a></div>';
                        echo '    <div class="flebox">';
                        echo '        <a href="/@' . safetext($org_ueuse['account']) . '"><img src="../'.$org_ueuse['iconname'] . '"></a>';
                        echo '        <a href="/@' . safetext($org_ueuse['account']) . '"><div class="u_name">' . replaceProfileEmojiImages(safetext($org_ueuse['username'])) . '</div></a>';
                        echo '        <div class="idbox">';
                        echo '            <a href="/@' . safetext($org_ueuse['account']) . '">@' . safetext($org_ueuse['account']) . '</a>';
                        echo '        </div>';
                        if(!empty($org_ueuse['sacinfo'])){
                            if($org_ueuse['sacinfo'] === "bot"){
                                echo '<div class="bot">Bot</div>';
                            }
                        }
                        if (false !== strpos($org_ueuse['role'], 'official')) {
                            echo '      <div class="checkicon">';
                            echo '          <div class="check" />';
                            echo '      </div>';
                            echo '</div>';
                        }
                        echo '        <div class="time">';
                        $datetime = strtotime(safetext($org_ueuse['datetime']));
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
                        if($org_ueuse['nsfw'] === "true"){
                            echo '    <div class="nsfw" data-uniqid="' . safetext($org_ueuse['uniqid']) . '">';
                            echo '    <p>NSFW指定がされている投稿です！<br>職場や公共の場での表示には適さない場合があります。<br>表示ボタンを押すと表示されます。</p>';
                            echo '    <div class="btnzone">';
                            echo '    <input type="button" id="nsfw_view" class="mini_irobtn" value="表示">';
                            echo '    </div>';
                            echo '    </div>';
                            echo '    <div class="nsfw_main" data-uniqid="' . safetext($org_ueuse['uniqid']) . '">';
                            echo '    <div class="block">';
                        }
                        echo '    <p>' . replaceEmojisWithImages(processMarkdownAndWrapEmptyLines(replaceURLsWithLinks(nl2br(safetext($org_ueuse['ueuse']))))) . '</h1></h2></h3></font></center></p>'; 
                        
                        if (!empty($org_ueuse['photo4']) && $org_ueuse['photo4'] !== 'none') {
                            echo '    <div class="photo4">';
                            echo '        <a><img src="'.safetext($org_ueuse['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($org_ueuse['photo2']).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($org_ueuse['photo3']).'" alt="画像3" title="画像3" data-id="3" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($org_ueuse['photo4']).'" alt="画像4" title="画像4" data-id="4" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '    </div>';
                        } elseif (!empty($org_ueuse['photo3']) && $org_ueuse['photo3'] !== 'none') {
                            echo '    <div class="photo3">';
                            echo '        <a><img src="'.safetext($org_ueuse['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($org_ueuse['photo2']).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <div class="photo3_btm">';
                            echo '            <a><img src="'.safetext($org_ueuse['photo3']).'" alt="画像3" title="画像3" data-id="3" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        </div>';
                            echo '    </div>';
                        } elseif (!empty($org_ueuse['photo2']) && $org_ueuse['photo2'] !== 'none') {
                            echo '    <div class="photo2">';
                            echo '        <a><img src="'.safetext($org_ueuse['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($org_ueuse['photo2']).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '    </div>';
                        } elseif (!empty($org_ueuse['photo1']) && $org_ueuse['photo1'] !== 'none') {
                            echo '    <div class="photo1">';
                            echo '        <a><img src="'.safetext($org_ueuse['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '    </div>';
                        }
                        if (!empty($org_ueuse['video1']) && $org_ueuse['video1'] !== 'none') {
                            echo '    <div class="video1">';
                            echo '        <video controls src="' . safetext($org_ueuse['video1']) . '"></video>';
                            echo '    </div>';
                        }elseif (!empty(YouTube_and_nicovideo_Links($org_ueuse['ueuse']))) {
                            echo '    <div class="youtube_and_nicovideo_player">';
                            echo '    '.YouTube_and_nicovideo_Links($org_ueuse['ueuse']).'';
                            echo '    </iframe></div>';
                        }
                        if(!($org_ueuse['abi'] == "none")){
                            echo '<div class="abi">';
                            echo '  <div class="back">';
                            echo '<h1>' . replaceProfileEmojiImages(safetext($org_ueuse['username'])) . 'さんが追記しました</h1>';
                            echo '  </div>';
                            echo '<p>'.processMarkdownAndWrapEmptyLines(replaceEmojisWithImages(replaceURLsWithLinks(nl2br(safetext($org_ueuse['abi']))))) . '</p>';
                            echo '<div class="h3s">追記日時 : '. date("Y年m月d日 H:i", strtotime(safetext($org_ueuse['abidate']))) . '</div>';
                            echo '</div>';
                        }
                        if($org_ueuse['nsfw'] === "true"){
                            echo '    </div>';
                            echo '    </div>';
                        } 
                        echo '<hr>';
                        echo '<div class="favbox">';
                        $favoriteList = explode(',', $org_ueuse['favorite']);
                        if (in_array($this->userid, $favoriteList)) {
                            echo '<button class="favbtn favbtn_after" id="favbtn"  data-uniqid="' . safetext($org_ueuse['uniqid']) . '" data-userid2="' . safetext($org_ueuse['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_2.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . safetext($org_ueuse['favcnt']) . '</span></button>';
                        }else{
                            echo '<button class="favbtn" id="favbtn"  data-uniqid="' . safetext($org_ueuse['uniqid']) . '" data-userid2="' . safetext($org_ueuse['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_1.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . safetext($org_ueuse['favcnt']) . '</span></button>';
                        }
                        
                        if ($this->userid == $this->value["account"]) {
                            echo '<button name="reusebtn" id="reusebtn" class="reuse reuse_after" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/reuse_1.svg#reuse_1"></use></svg> <span class="like-count">' . safetext($org_ueuse['reuse_count']) . '</span></button>';
                        }else{
                            echo '<button name="reusebtn" id="reusebtn" class="reuse" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/reuse_1.svg#reuse_1"></use></svg> <span class="like-count">' . safetext($org_ueuse['reuse_count']) . '</span></button>';
                        }
                        
                        echo '<a href="/!'.safetext($org_ueuse['uniqid']). '" class="tuduki"><svg><use xlink:href="../img/sysimage/reply_1.svg#reply_1"></use></svg>'.safetext($org_ueuse['reply_count']).'</a>';            
                        $bookmarkList = explode(',', $org_ueuse['bookmark']);
                        if (in_array($org_ueuse['uniqid'], $bookmarkList)) {
                            echo '<button name="bookmark" id="bookmark" class="bookmark bookmark_after" data-uniqid="' . safetext($org_ueuse['uniqid']) . '" data-userid="' . safetext($org_ueuse['account']) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
                        }else{
                            echo '<button name="bookmark" id="bookmark" class="bookmark" data-uniqid="' . safetext($org_ueuse['uniqid']) . '" data-userid="' . safetext($org_ueuse['account']) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
                        }
                        if($org_ueuse['account'] === $this->userid){
                            if(!($org_ueuse['role'] === "ice")){
                                if($org_ueuse['abi'] === "none"){
                                    echo '<button name="addabi" id="addabi" data-uniqid2="' . safetext($org_ueuse['uniqid']) . '" class="addabi"><svg><use xlink:href="../img/sysimage/addabi_1.svg#addabi_1"></use></svg></button>';
                                }
                            }
                        }
                        echo '<button name="popup" id="popup" class="etcbtn" data-uniqid="' . safetext($org_ueuse['uniqid']) . '" data-userid="' . safetext($org_ueuse['account']) . '"><svg><use xlink:href="../img/sysimage/etc_1.svg#etc_1"></use></svg></button>';
                        echo '</div>';
                        echo '</div>';
                    }
                }else{
                    if(!(empty($this->value['ueuse']))){
                        //引用リユーズで引用元が消えた場合-------------------------------------------------------------------------------------
                        //----------------------------------------------------------------------------------------------------------------
                        //----------------------------------------------------------------------------------------------------------------
                        echo '    <div class="flebox">';
                        echo '        <a href="/@' . safetext($this->value['account']) . '"><img src="'. safetext('../'.$this->value['iconname']) . '"></a>';
                        echo '        <a href="/@' . safetext($this->value['account']) . '"><div class="u_name">' . replaceProfileEmojiImages(safetext($this->value['username'])) . '</div></a>';
                        echo '        <div class="idbox">';
                        echo '            <a href="/@' . safetext($this->value['account']) . '">@' . safetext($this->value['account']) . '</a>';
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
                        $datetime = strtotime(safetext($this->value['datetime']));
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
                            echo '    <div class="nsfw" data-uniqid="' . safetext($this->value['uniqid']) . '">';
                            echo '    <p>NSFW指定がされている投稿です！<br>職場や公共の場での表示には適さない場合があります。<br>表示ボタンを押すと表示されます。</p>';
                            echo '    <div class="btnzone">';
                            echo '    <input type="button" id="nsfw_view" class="mini_irobtn" value="表示">';
                            echo '    </div>';
                            echo '    </div>';
                            echo '    <div class="nsfw_main" data-uniqid="' . safetext($this->value['uniqid']) . '">';
                            echo '    <div class="block">';
                        }
                        echo '    <p>' . replaceEmojisWithImages(processMarkdownAndWrapEmptyLines(replaceURLsWithLinks(nl2br(safetext($this->value['ueuse']))))) . '</h1></h2></h3></font></center></p>';
                        
                        echo '<div class="reuse_box">';
                        echo '<p>リユーズ元のユーズは削除されました。</p>'; 
                        echo '</div>';

                        if (!empty($this->value['photo4']) && $this->value['photo4'] !== 'none') {
                            echo '    <div class="photo4">';
                            echo '        <a><img src="'.safetext($this->value['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($this->value['photo2']).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($this->value['photo3']).'" alt="画像3" title="画像3" data-id="3" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($this->value['photo4']).'" alt="画像4" title="画像4" data-id="4" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '    </div>';
                        } elseif (!empty($this->value['photo3']) && $this->value['photo3'] !== 'none') {
                            echo '    <div class="photo3">';
                            echo '        <a><img src="'.safetext($this->value['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($this->value['photo2']).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <div class="photo3_btm">';
                            echo '            <a><img src="'.safetext($this->value['photo3']).'" alt="画像3" title="画像3" data-id="3" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        </div>';
                            echo '    </div>';
                        } elseif (!empty($this->value['photo2']) && $this->value['photo2'] !== 'none') {
                            echo '    <div class="photo2">';
                            echo '        <a><img src="'.safetext($this->value['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '        <a><img src="'.safetext($this->value['photo2']).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '    </div>';
                        } elseif (!empty($this->value['photo1']) && $this->value['photo1'] !== 'none') {
                            echo '    <div class="photo1">';
                            echo '        <a><img src="'.safetext($this->value['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                            echo '    </div>';
                        }
                        if (!empty($this->value['video1']) && $this->value['video1'] !== 'none') {
                            echo '    <div class="video1">';
                            echo '        <video controls src="' . safetext($this->value['video1']) . '"></video>';
                            echo '    </div>';
                        }elseif (!empty(YouTube_and_nicovideo_Links($this->value['ueuse']))) {
                            echo '    <div class="youtube_and_nicovideo_player">';
                            echo '    '.YouTube_and_nicovideo_Links($this->value['ueuse']).'';
                            echo '    </iframe></div>';
                        }
        
                        if(!($this->value['abi'] == "none")){
                            echo '<div class="abi">';
                            echo '  <div class="back">';
                            echo '<h1>' . replaceProfileEmojiImages(safetext($this->value['username'])) . 'さんが追記しました</h1>';
                            echo '  </div>';
                            echo '<p>'.processMarkdownAndWrapEmptyLines(replaceEmojisWithImages(replaceURLsWithLinks(nl2br(safetext($this->value['abi']))))) . '</p>';
                            echo '<div class="h3s">追記日時 : '. date("Y年m月d日 H:i", strtotime(safetext($this->value['abidate']))) . '</div>';
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
                            echo '<button class="favbtn favbtn_after" id="favbtn"  data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid2="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_2.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . safetext($this->value['favcnt']) . '</span></button>';
                        }else{
                            echo '<button class="favbtn" id="favbtn"  data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid2="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_1.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . safetext($this->value['favcnt']) . '</span></button>';
                        }
                        
                        echo '<button name="reusebtn" id="reusebtn" class="reuse" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/reuse_1.svg#reuse_1"></use></svg> <span class="like-count">' . safetext($this->value['reuse_count']) . '</span></button>';
        
                        echo '<a href="/!'.safetext($this->value['uniqid']). '" class="tuduki"><svg><use xlink:href="../img/sysimage/reply_1.svg#reply_1"></use></svg>'.safetext($this->value['reply_count']).'</a>';
                                    
                        $bookmarkList = explode(',', $this->value['bookmark']);
                        if (in_array($this->value['uniqid'], $bookmarkList)) {
                            echo '<button name="bookmark" id="bookmark" class="bookmark bookmark_after" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
                        }else{
                            echo '<button name="bookmark" id="bookmark" class="bookmark" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
                        }
                            
                        if($this->value['account'] === $this->userid){
                            if(!($this->value['role'] === "ice")){
                                if($this->value['abi'] === "none"){
                                    echo '<button name="addabi" id="addabi" data-uniqid2="' . safetext($this->value['uniqid']) . '" class="addabi"><svg><use xlink:href="../img/sysimage/addabi_1.svg#addabi_1"></use></svg></button>';
                                }
                            }
                        }
        
                        echo '<button name="popup" id="popup" class="etcbtn" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/etc_1.svg#etc_1"></use></svg></button>';
        
                        echo '</div>';
                        echo '</div>';
                    }else{
                        //普通のリユーズのリユーズ元が消えた場合--------------------------------------------------------------------------------
                        //----------------------------------------------------------------------------------------------------------------
                        //----------------------------------------------------------------------------------------------------------------
                        echo '<div class="ru"><a href="/@' . safetext($this->value['account']) . '"><img src="../'.$this->value['iconname'] . '"><p>' . safetext($this->value['username']) . 'さんがリユーズ</p></a></div>';
                        
                        echo '<p>リユーズ元のユーズは削除されました。</p>'; 
                        
                        echo '<hr>';
                        echo '<div class="favbox">';
                        $favoriteList = explode(',', $this->value['favorite']);
                        if (in_array($this->userid, $favoriteList)) {
                            echo '<button class="favbtn favbtn_after" id="favbtn"  data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid2="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_2.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . safetext($this->value['favcnt']) . '</span></button>';
                        }else{
                            echo '<button class="favbtn" id="favbtn"  data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid2="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_1.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . safetext($this->value['favcnt']) . '</span></button>';
                        }

                        echo '<button name="reusebtn" id="reusebtn" class="reuse" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/reuse_1.svg#reuse_1"></use></svg> <span class="like-count">' . safetext($this->value['reuse_count']) . '</span></button>';
        
                        echo '<a href="/!'.safetext($this->value['uniqid']). '" class="tuduki"><svg><use xlink:href="../img/sysimage/reply_1.svg#reply_1"></use></svg>'.safetext($this->value['reply_count']).'</a>';
                                    
                        $bookmarkList = explode(',', $this->value['bookmark']);
                        if (in_array($this->value['uniqid'], $bookmarkList)) {
                            echo '<button name="bookmark" id="bookmark" class="bookmark bookmark_after" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
                        }else{
                            echo '<button name="bookmark" id="bookmark" class="bookmark" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
                        }
        
                        echo '<button name="popup" id="popup" class="etcbtn" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/etc_1.svg#etc_1"></use></svg></button>';
        
                        echo '</div>';
                        echo '</div>';
                    }
                }
            }else{
                //普通のユーズ------------------------------------------------------------------------------------------------------
                //----------------------------------------------------------------------------------------------------------------
                //----------------------------------------------------------------------------------------------------------------
                echo '    <div class="flebox">';
                
                echo '        <a href="/@' . safetext($this->value['account']) . '"><img src="'. safetext('../'.$this->value['iconname']) . '"></a>';
                echo '        <a href="/@' . safetext($this->value['account']) . '"><div class="u_name">' . replaceProfileEmojiImages(safetext($this->value['username'])) . '</div></a>';
                echo '        <div class="idbox">';
                echo '            <a href="/@' . safetext($this->value['account']) . '">@' . safetext($this->value['account']) . '</a>';
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
                $datetime = strtotime(safetext($this->value['datetime']));
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
                    echo '    <div class="nsfw" data-uniqid="' . safetext($this->value['uniqid']) . '">';
                    echo '    <p>NSFW指定がされている投稿です！<br>職場や公共の場での表示には適さない場合があります。<br>表示ボタンを押すと表示されます。</p>';
                    echo '    <div class="btnzone">';
                    echo '    <input type="button" id="nsfw_view" class="mini_irobtn" value="表示">';
                    echo '    </div>';
                    echo '    </div>';
                    echo '    <div class="nsfw_main" data-uniqid="' . safetext($this->value['uniqid']) . '">';
                    echo '    <div class="block">';
                }
                echo '    <p>' . replaceEmojisWithImages(processMarkdownAndWrapEmptyLines(replaceURLsWithLinks(nl2br(safetext($this->value['ueuse']))))) . '</h1></h2></h3></font></center></p>';
                
                if (!empty($this->value['photo4']) && $this->value['photo4'] !== 'none') {
                    echo '    <div class="photo4">';
                    echo '        <a><img src="'.safetext($this->value['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                    echo '        <a><img src="'.safetext($this->value['photo2']).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                    echo '        <a><img src="'.safetext($this->value['photo3']).'" alt="画像3" title="画像3" data-id="3" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                    echo '        <a><img src="'.safetext($this->value['photo4']).'" alt="画像4" title="画像4" data-id="4" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                    echo '    </div>';
                } elseif (!empty($this->value['photo3']) && $this->value['photo3'] !== 'none') {
                    echo '    <div class="photo3">';
                    echo '        <a><img src="'.safetext($this->value['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                    echo '        <a><img src="'.safetext($this->value['photo2']).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                    echo '        <div class="photo3_btm">';
                    echo '            <a><img src="'.safetext($this->value['photo3']).'" alt="画像3" title="画像3" data-id="3" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                    echo '        </div>';
                    echo '    </div>';
                } elseif (!empty($this->value['photo2']) && $this->value['photo2'] !== 'none') {
                    echo '    <div class="photo2">';
                    echo '        <a><img src="'.safetext($this->value['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                    echo '        <a><img src="'.safetext($this->value['photo2']).'" alt="画像2" title="画像2" data-id="2" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                    echo '    </div>';
                } elseif (!empty($this->value['photo1']) && $this->value['photo1'] !== 'none') {
                    echo '    <div class="photo1">';
                    echo '        <a><img src="'.safetext($this->value['photo1']).'" alt="画像1" title="画像1" data-id="1" id="ueuse_image" onerror="this.onerror=null;this.src=\'../img/sysimage/errorimage/image_404.png\'"></a>';
                    echo '    </div>';
                }
                if (!empty($this->value['video1']) && $this->value['video1'] !== 'none') {
                    echo '    <div class="video1">';
                    echo '        <video controls src="' . safetext($this->value['video1']) . '"></video>';
                    echo '    </div>';
                }elseif (!empty(YouTube_and_nicovideo_Links($this->value['ueuse']))) {
                    echo '    <div class="youtube_and_nicovideo_player">';
                    echo '    '.YouTube_and_nicovideo_Links($this->value['ueuse']).'';
                    echo '    </iframe></div>';
                }

                if(!($this->value['abi'] == "none")){
                    echo '<div class="abi">';
                    echo '  <div class="back">';
                    echo '<h1>' . replaceProfileEmojiImages(safetext($this->value['username'])) . 'さんが追記しました</h1>';
                    echo '  </div>';
                    echo '<p>'.processMarkdownAndWrapEmptyLines(replaceEmojisWithImages(replaceURLsWithLinks(nl2br(safetext($this->value['abi']))))) . '</p>';
                    echo '<div class="h3s">追記日時 : '. date("Y年m月d日 H:i", strtotime(safetext($this->value['abidate']))) . '</div>';
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
                    echo '<button class="favbtn favbtn_after" id="favbtn"  data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid2="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_2.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . safetext($this->value['favcnt']) . '</span></button>';
                }else{
                    echo '<button class="favbtn" id="favbtn"  data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid2="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/favorite_1.svg#favorite" alt="いいね"></use></svg> <span class="like-count">' . safetext($this->value['favcnt']) . '</span></button>';
                }
                
                echo '<button name="reusebtn" id="reusebtn" class="reuse" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/reuse_1.svg#reuse_1"></use></svg> <span class="like-count">' . safetext($this->value['reuse_count']) . '</span></button>';

                echo '<a href="/!'.safetext($this->value['uniqid']). '" class="tuduki"><svg><use xlink:href="../img/sysimage/reply_1.svg#reply_1"></use></svg>'.safetext($this->value['reply_count']).'</a>';
                            
                $bookmarkList = explode(',', $this->value['bookmark']);
                if (in_array($this->value['uniqid'], $bookmarkList)) {
                    echo '<button name="bookmark" id="bookmark" class="bookmark bookmark_after" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
                }else{
                    echo '<button name="bookmark" id="bookmark" class="bookmark" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>';
                }
                    
                if($this->value['account'] === $this->userid){
                    if(!($this->value['role'] === "ice")){
                        if($this->value['abi'] === "none"){
                            echo '<button name="addabi" id="addabi" data-uniqid2="' . safetext($this->value['uniqid']) . '" class="addabi"><svg><use xlink:href="../img/sysimage/addabi_1.svg#addabi_1"></use></svg></button>';
                        }
                    }
                }

                echo '<button name="popup" id="popup" class="etcbtn" data-uniqid="' . safetext($this->value['uniqid']) . '" data-userid="' . safetext($this->value['account']) . '"><svg><use xlink:href="../img/sysimage/etc_1.svg#etc_1"></use></svg></button>';

                echo '</div>';
                echo '</div>';
            }
        }
    }
}
?>