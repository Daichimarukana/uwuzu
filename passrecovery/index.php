<?php

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);


require('../db.php');


// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;

$row["userid"] = "";
$row["password"] = "";

$ruserid = "";
$rpassword = "";

$userid = "";
$_SESSION["userid"]="";

$password = null;
$_SESSION["password"]="";

session_name('uwuzu_s_id');
session_set_cookie_params(0, '', '', true, true);
session_start();
// データベースに接続
try {

    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

} catch(PDOException $e) {

    // 接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}

if( !empty($_POST['btn_submit']) ) {


    //$row['userid'] = "daichimarukn";

    $userid = $_POST['userid'];
    $mailadds = $_POST['mailadds'];


    $options = array(
        // SQL実行失敗時に例外をスルー
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // デフォルトフェッチモードを連想配列形式に設定
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // バッファードクエリを使う（一度に結果セットを全て取得し、サーバー負荷を軽減）
        // SELECTで得た結果に対してもrowCountメソッドを使えるようにする
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    );

    $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);


    $result = $dbh->prepare("SELECT userid, mailadds, loginid, authcode FROM account WHERE userid = :userid");

    $result->bindValue(':userid', $userid);
    // SQL実行
    $result->execute();



    // ... (前略)
        // IDの入力チェック
	if( empty($userid) ) {
		$error_message[] = 'ユーザーIDを入力してください。(USERID_INPUT_PLEASE)';
	} else {

        if( empty($mailadds) ) {
            $error_message[] = 'メールアドレスを入力してください。(INPUT_PLEASE)';
        }
        if(!(preg_match("/^[a-zA-Z0-9_]+$/", $userid))){
            $error_message[] = "IDは半角英数字で作成してください。(「_」は使用可能です。)(USERID_DONT_USE_WORD)";
        }
        if(empty($error_message)){
            if($result->rowCount() > 0) {
                $row = $result->fetch(); // ここでデータベースから取得した値を $row に代入する

                if($row["userid"] == $userid){
                    if($row["mailadds"] == $mailadds){
                        if(empty($row["authcode"])){

                            $_SESSION['userid'] = "";
                            $url = 'badrecovery.php';
                            header('Location: ' . $url, true, 303);

                            // すべての出力を終了
                            exit;
                        }else{
                            $_SESSION['userid'] = $userid;
                            $url = 'startrecovery.php';
                            header('Location: ' . $url, true, 303);

                            // すべての出力を終了
                            exit;
                        }
                    }
                    else{
                        $error_message[] = 'IDまたはメールアドレスが違います(ID_OR_MAILADDS_CHIGAUYANKE)'; 
                    }
                }else{
                    $error_message[] = 'IDまたはメールアドレスが違います(ID_OR_MAILADDS_CHIGAUYANKE)'; 
                }
            }
            else {
                $error_message[] = 'IDまたはメールアドレスが違います(ID_OR_MAILADDS_CHIGAUYANKE)';
            }
        }

    }

    // ... (後略)



}

// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css">
<script src="../js/unsupported.js"></script>
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>パスワードの復元 - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>
</head>

<script src="../back.js"></script>
<body>

<div class="leftbox">
    <?php if(!empty(htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8'))){ ?>
        <div class="logo">
            <a href="../index.php"><img src=<?php echo htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8');?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="../index.php"><img src="../img/uwuzulogo.svg"></a>
        </div>
    <?php }?>

    <div class="textbox">
        <h1>パスワードの復元</h1>

        <p>IDと登録したメールアドレスを入力してください。</p>

            <?php if( !empty($error_message) ): ?>
                <ul class="errmsg">
                    <?php foreach( $error_message as $value ): ?>
                        <p>・ <?php echo $value; ?></p>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form class="formarea" method="post">
                <!--ユーザーネーム関係-->
                <div>
                    <label for="userid">ユーザーID</label>
                    <input onInput="checkForm(this)" id="userid" class="inbox" type="text" name="userid" value="<?php if( !empty($_SESSION['userid']) ){ echo htmlentities( $_SESSION['userid'], ENT_QUOTES, 'UTF-8'); } ?>">
                </div>

                <!--アカウント関連-->
                <div>
                    <label for="mailadds">メールアドレス</label>
                    <input id="mailadds" class="inbox" type="text" name="mailadds" value="<?php if( !empty($_SESSION['mailadds']) ){ echo htmlentities( $_SESSION['mailadds'], ENT_QUOTES, 'UTF-8'); } ?>">
                </div>
                
                <input type="submit" name="btn_submit" class="irobutton" value="次へ">
            </form>

            <div class="btnbox">
                <a href="../index.php" class="sirobutton">戻る</a>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

function checkForm(inputElement) {
    var str = inputElement.value;
    while (str.match(/[^A-Za-z\d_]/)) {
        str = str.replace(/[^A-Za-z\d_]/, "");
    }
    inputElement.value = str;
}

</script>


</body>
</html>