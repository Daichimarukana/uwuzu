<?php
function createUniqId() {
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec . floor($msec * 1000000);

    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime, 10, 36);
}

require('../db.php');

// データベースに接続
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

if (isset($_GET['userid']) && isset($_GET['account_id']) && isset($_GET['search_query']) && isset($_GET['view_mode'])) {
    $userid = htmlentities($_GET['userid'], ENT_QUOTES, 'UTF-8', false);
    $loginid = htmlentities($_GET['account_id'], ENT_QUOTES, 'UTF-8', false);
    $search_word = str_replace(":","",htmlentities($_GET['search_query'], ENT_QUOTES, 'UTF-8', false));
    $viewmode = htmlentities($_GET['view_mode'], ENT_QUOTES, 'UTF-8', false);

    $itemsPerPage = 50;
    $pageNumber = htmlentities(isset($_GET['page'])) ? htmlentities(intval($_GET['page'])) : 1;
    $offset = ($pageNumber - 1) * $itemsPerPage;

    // データベース接続の設定
    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ));

    $query = $dbh->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

    $query->execute(array(':userid' => $userid));

    $result2 = $query->fetch();

    if(!(empty($result2["loginid"]))){
        if($result2["loginid"] === $loginid){
            $custom_emoji = array();
            if (!(empty($pdo))) {

                if(!(empty($search_word))){
                    $custom_emoji_Query = $pdo->prepare("SELECT emojifile,emojiname,emojiinfo,emojidate FROM emoji WHERE emojiname LIKE :keyword OR emojiinfo LIKE :keyword ORDER BY emojidate DESC");
                    $custom_emoji_Query->bindValue(':keyword', '%' . $search_word . '%', PDO::PARAM_STR);
                    $custom_emoji_Query->execute();
                }else{
                    $custom_emoji_Query = $pdo->prepare("SELECT emojifile,emojiname,emojiinfo,emojidate FROM emoji ORDER BY emojidate DESC LIMIT :offset, :itemsPerPage");
                    $custom_emoji_Query->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $custom_emoji_Query->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
                    $custom_emoji_Query->execute();
                }

                $custom_array = $custom_emoji_Query->fetchAll();

                $custom_emoji = array();
                foreach ($custom_array as $row) {
                    $custom_emoji[] = $row;
                }
                
                if((!(empty($custom_emoji)))&&(!(empty($viewmode)))){
                    if($viewmode == "page"){
                        foreach ($custom_emoji as $value) {
                            echo '<div class="emjtex">';
                            echo '<div class="fx">';
                            echo '<img src="../' . htmlentities($value["emojifile"], ENT_QUOTES, 'UTF-8', false) . '">';
                            echo '<div class="btm_zone">';
                            echo '<h3>:'.htmlentities($value["emojiname"], ENT_QUOTES, 'UTF-8', false).':</h3>';
                            echo '<p>'.htmlentities($value["emojiinfo"], ENT_QUOTES, 'UTF-8', false).'</p>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    }elseif($viewmode == "picker"){
                        foreach ($custom_emoji as $value) {
                            echo '<div class="one_emoji">';
                            echo '<img src="../' . htmlentities($value["emojifile"], ENT_QUOTES, 'UTF-8', false) . '" alt=":'.htmlentities($value["emojiname"], ENT_QUOTES, 'UTF-8', false).':" title=":'.htmlentities($value["emojiname"], ENT_QUOTES, 'UTF-8', false).':">';
                            echo '</div>';
                        }
                    }else{
                        echo '<div class="tokonone" id="noemoji"><p>取得に失敗しました。</p></div>';
                    }
                }else{
                    echo '<div class="tokonone" id="noemoji"><p>カスタム絵文字がありません</p></div>';
                }
                

            }else{
                echo '<div class="tokonone" id="noemoji"><p>取得に失敗しました。</p></div>';
            }

        }else{
            echo '<div class="tokonone" id="noemoji"><p>カスタム絵文字がありません</p></div>';
        }
        
        $pdo = null;

    }else{
        echo '<div class="tokonone" id="noemoji"><p>取得に失敗しました。</p></div>';
    }
}else{
    echo '<div class="tokonone" id="noemoji"><p>取得に失敗しました。</p></div>';
}