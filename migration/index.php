<?php

$banuseridfile = "../server/banuserid.txt";
$banuserid_info = file_get_contents($banuseridfile);
$banuserid = preg_split("/\r\n|\n|\r/", $banuserid_info);

$badpassfile = "../server/badpass.txt";
$badpass_info = file_get_contents($badpassfile);
$badpass = preg_split("/\r\n|\n|\r/", $badpass_info);

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

require('../db.php');
//関数呼び出し
//- EXIF
require('../function/function.php');
//hCaptcha--------------------------------------------
require('../settings_admin/hCaptcha_settings/hCaptcha_settings.php');
//Cloudflare_Turnstile--------------------------------------------
require('../settings_admin/CloudflareTurnstile_settings/CloudflareTurnstile_settings.php');
//----------------------------------------------------

session_name('uwuzu_s_id');
session_set_cookie_params(0, '', '', true, true);
session_start();

// 変数の初期化
$current_date = null;
$message_array = array();
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;

if(isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true && isset($_COOKIE['loginid']) && isset($_SESSION['userid'])) {
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
    $acck = $dbh->prepare("SELECT userid, loginid FROM account WHERE userid = :userid");
    $acck->bindValue(':userid', $_SESSION['userid']);
    $acck->execute();
    $acck_data = $acck->fetch();
    if(!empty($acck_data)){
        if($_COOKIE['loginid'] === $acck_data["loginid"] && $_SESSION['userid'] === $acck_data["userid"] ){
            header("Location: /home/index.php");
            exit;
        }
    }
} elseif (isset($_COOKIE['admin_login']) && $_COOKIE['admin_login'] == true && isset($_COOKIE['loginid']) && isset($_COOKIE['userid'])) {
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
    $acck = $dbh->prepare("SELECT userid, loginid FROM account WHERE userid = :userid");
    $acck->bindValue(':userid', $_COOKIE['userid']);
    $acck->execute();
    $acck_data = $acck->fetch();
    if(!empty($acck_data)){
        if($_COOKIE['loginid'] === $acck_data["loginid"] && $_COOKIE['userid'] === $acck_data["userid"] ){
            header("Location: /home/index.php");
            exit;
        }
    }
}
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
    if(htmlspecialchars($serversettings["serverinfo"]["server_account_migration"], ENT_QUOTES, 'UTF-8') === "true"){
        $new_userid = htmlentities($_POST['new_userid'], ENT_QUOTES, 'UTF-8', false);
        $password = htmlentities($_POST['password'], ENT_QUOTES, 'UTF-8', false);
        
        if(htmlspecialchars($serversettings["serverinfo"]["server_invitation"], ENT_QUOTES, 'UTF-8') === "true"){
            $invitationcode = htmlentities($_POST['invitationcode'], ENT_QUOTES, 'UTF-8', false);
        }

        $domain = htmlentities($_POST['moto_server_domain'], ENT_QUOTES, 'UTF-8', false);
        $check_code = htmlentities($_POST['moto_server_account_check'], ENT_QUOTES, 'UTF-8', false);
        $key = htmlentities($_POST['moto_server_account_auth'], ENT_QUOTES, 'UTF-8', false);

        if(empty($domain)) {
            $error_message[] = '移行元のサーバードメインを入力してください。(INPUT_PLEASE)';
        }else{
            $domain_response = @file_get_contents("https://".$domain."/");
            if (empty($domain_response)) {
                $error_message[] = '入力されたドメインに接続できませんでした。(INPUT_PLEASE)';
            }
        }
        
        if(empty($check_code)) {
            $error_message[] = '識別コードを入力してください。(INPUT_PLEASE)';
        }
        if(empty($key)) {
            $error_message[] = '認証コードを入力してください。(INPUT_PLEASE)';
        }

        //招待コードチェック
        if(htmlspecialchars($serversettings["serverinfo"]["server_invitation"], ENT_QUOTES, 'UTF-8') === "true"){
            $query = $pdo->prepare('SELECT * FROM invitation WHERE code = :code limit 1');
    
            $query->execute(array(':code' => $invitationcode));
        
            $result = $query->fetch();
    
            // 招待コードの入力チェック
            if( empty($invitationcode) ) {
                $error_message[] = '招待コードを入力してください。(INVITATION_CODE_INPUT_PLEASE)';
            } else {
                if($result > 0){
                    if($result["used"] === "true"){
                        $error_message[] = 'この招待コード('.$invitationcode.')は既に使用されています。(INVITATION_CODE_SHIYOUZUMI)';
                    }
                }else{
                    $error_message[] = 'この招待コード('.$invitationcode.')は使えません。(INVITATION_CODE_DEAD)';
                }
    
            }
        }

        $key1_code = substr($key, 0, -16);//key1
        $key2_code = substr($key, -16);//key2

        if(!empty(H_CAPTCHA_ONOFF && H_CAPTCHA_ONOFF == "true")){
            if(isset($_POST['h-captcha-response'])){
                $hcaptcha_token = htmlentities($_POST['h-captcha-response']);
                if($hcaptcha_token){
                    $captcha_data = [
                        'secret' => htmlentities(H_CAPTCHA_SEAC_KEY),
                        'response' => $hcaptcha_token,
                        'sitekey' => htmlentities(H_CAPTCHA_SITE_KEY)
                    ];
                    $options = [
                        'http' => [
                            'method'=> 'POST',
                            'header'=> 'Content-Type: application/x-www-form-urlencoded',
                            'content' => http_build_query($captcha_data, '', '&')
                        ]
                    ];
                    $hCaptcha_result = json_decode(file_get_contents('https://hcaptcha.com/siteverify', false, stream_context_create($options)),true);
                    if(!($hCaptcha_result["success"] == true)){
                        $error_message[] = "hCaptchaであなたが人間である確認ができませんでした。(ERROR)";
                    }
                }else{
                    $error_message[] = "hCaptchaであなたが人間である確認ができませんでした。(ERROR)";
                }
            }else{
                $error_message[] = "hCaptchaであなたが人間である確認ができませんでした。(ERROR)";
            }
        }
        if(!empty(CF_TURNSTILE_ONOFF && CF_TURNSTILE_ONOFF == "true")){
            if(isset($_POST['cf-turnstile-response'])){
                $CF_Turnstile_token = htmlentities($_POST['cf-turnstile-response']);
                if($CF_Turnstile_token){
                    $CF_Turnstile_data = [
                        'secret' => htmlentities(CF_TURNSTILE_SEAC_KEY),
                        'response' => $CF_Turnstile_token
                    ];
                    $CF_Turnstile_options = [
                        'http' => [
                            'method'=> 'POST',
                            'header'=> 'Content-Type: application/x-www-form-urlencoded',
                            'content' => http_build_query($CF_Turnstile_data, '', '&')
                        ]
                    ];
                    $CF_Turnstile_result = json_decode(file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, stream_context_create($CF_Turnstile_options)),true);
                    if(!($CF_Turnstile_result["success"] == true)){
                        $error_message[] = "CloudflareTurnstileであなたが人間である確認ができませんでした。(ERROR)";
                    }
                }else{
                    $error_message[] = "CloudflareTurnstileであなたが人間である確認ができませんでした。(ERROR)";
                }
            }else{
                $error_message[] = "CloudflareTurnstileであなたが人間である確認ができませんでした。(ERROR)";
            }
        }

        if(empty($error_message)){
            $data = array();
            $options = [
                'http' => [
                    'method'=> 'POST',
                    'content' => http_build_query($data, '', '&')
                ]
            ];
            $Check_result = json_decode(file_get_contents("https://".$domain."/api/serverinfo-api", false, stream_context_create($options)),true);
            if($Check_result["software"]["name"] == "uwuzu"){
                $version = str_pad(str_replace('.', '', $Check_result["software"]["version"]), 4, 0, STR_PAD_RIGHT);
                
                if($version >= 1360){
                    $Check_Link = "https://".$domain."/api/migration-api?migration_code=".$check_code;
                    $data = array();
                    $options = [
                        'http' => [
                            'method'=> 'GET',
                            'content' => http_build_query($data, '', '&')
                        ]
                    ];
                    $Get_result = json_decode(file_get_contents($Check_Link, false, stream_context_create($options)),true);

                    if(isset($Get_result["data"])){
                        $account_data = openssl_decrypt($Get_result["data"], "AES-256-CBC", $key1_code, 0, $key2_code);
                        if($account_data == false){
                            $error_message[] = "認証コードもしくは確認コードが間違っているようです。(MIGRATION_BAD_AUTH_CODE)";
                        }else{
                            $json_account_data = json_decode($account_data,true);
                            if($json_account_data == false){
                                $error_message[] = "アカウントの移行を最初からやり直してください。(MIGRATION_SORRY)";
                            }else{
                                
                                //アイコン&ヘッダー
                                $icondata = file_get_contents($json_account_data["userdata"]["user_icon"]);
                                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                $mime_type = finfo_buffer($finfo, $icondata);
                                $safe_img_mime = [
                                    "image/gif" => 'gif',
                                    "image/jpeg" => 'jpg',
                                    "image/png" => 'png',
                                    "image/svg+xml" => 'svg',
                                    "image/webp" => 'webp',
                                    "image/bmp" => 'bmp',
                                    "image/x-icon" => 'ico',
                                    "image/tiff" => 'tiff'
                                ];
                                if(isset($safe_img_mime[$mime_type])){
                                    $extension = $safe_img_mime[$mime_type];
                                    $temp_file = tempnam(sys_get_temp_dir(), 'img');
                                    file_put_contents($temp_file, $icondata);

                                    delete_exif($extension, $temp_file);

                                    // 新しいファイル名を生成（uniqid + 拡張子）
                                    $newFilename = uniqid() . '-'.$new_userid.'.png';
                                    
                                    // 保存先のパスを生成
                                    $uploadedPath = 'usericons/' . $newFilename;
                                    
                                    // ファイルを移動
                                    $result = copy($temp_file, "../".$uploadedPath);
                                    
                                    if ($result) {
                                        $iconName = $uploadedPath; // 保存されたファイルのパスを使用
                                    } else {
                                        $errnum = $uploadedFile['error'];
                                        if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
                                        if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
                                        if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
                                        if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
                                        if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
                                        if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
                                        if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
                                        $error_message[] = 'アップロード失敗！(1)エラーコード：' .$uploadedFile['error'].'';
                                    }
                                }
                                //------------------
                                $headdata = file_get_contents($json_account_data["userdata"]["user_header"]);
                                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                $mime_type = finfo_buffer($finfo, $headdata);
                                $safe_img_mime = [
                                    "image/gif" => 'gif',
                                    "image/jpeg" => 'jpg',
                                    "image/png" => 'png',
                                    "image/svg+xml" => 'svg',
                                    "image/webp" => 'webp',
                                    "image/bmp" => 'bmp',
                                    "image/x-icon" => 'ico',
                                    "image/tiff" => 'tiff'
                                ];
                                if(isset($safe_img_mime[$mime_type])){
                                    $extension = $safe_img_mime[$mime_type];
                                    $temp_file = tempnam(sys_get_temp_dir(), 'img');
                                    file_put_contents($temp_file, $headdata);

                                    delete_exif($extension, $temp_file);

                                    // 新しいファイル名を生成（uniqid + 拡張子）
                                    $newFilename = uniqid() . '-'.$new_userid.'.png';
                                    
                                    // 保存先のパスを生成
                                    $uploadedPath = 'userheads/' . $newFilename;
                                    
                                    // ファイルを移動
                                    $result = copy($temp_file, "../".$uploadedPath);
                                    
                                    if ($result) {
                                        $headName = $uploadedPath; // 保存されたファイルのパスを使用
                                    } else {
                                        $errnum = $uploadedFile['error'];
                                        if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
                                        if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
                                        if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
                                        if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
                                        if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
                                        if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
                                        if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
                                        $error_message[] = 'アップロード失敗！(2)エラーコード：' .$uploadedFile['error'].'';
                                    }
                                }

                                $query = $pdo->prepare('SELECT * FROM account WHERE userid = :userid limit 1');
                                $query->execute(array(':userid' => $new_userid));
                                $result = $query->fetch();

                                // ユーザーネームの入力チェック
                                if( empty($json_account_data["userdata"]["user_name"]) ) {
                                    $error_message[] = '表示名を入力してください。(USERNAME_INPUT_PLEASE)';
                                } else {
                                    // 文字数を確認
                                    if( 50 < mb_strlen($json_account_data["userdata"]["user_name"], 'UTF-8') ) {
                                        $error_message[] = 'ユーザーネームは50文字以内で入力してください。(USERNAME_OVER_MAX_COUNT)';
                                    }
                                }

                                // IDの入力チェック
                                if( empty($new_userid) ) {
                                    $error_message[] = 'ユーザーIDを入力してください。(USERID_INPUT_PLEASE)';
                                } else {

                                    // 文字数を確認
                                    if( 20 < mb_strlen($new_userid, 'UTF-8') ) {
                                        $error_message[] = 'IDは20文字以内で入力してください。(USERID_OVER_MAX_COUNT)';
                                    }

                                    if(in_array($new_userid, $banuserid) === true ){
                                        $error_message[] = 'そのIDは登録禁止になっています。(USERID_CONTAINS_PROHIBITED)';
                                    }

                                    if($result > 0){
                                        $error_message[] = 'このID('.$new_userid.')は既に使用されています。他のIDを作成してください。(USERID_SHIYOUZUMI)';
                                    }

                                    if(!(preg_match("/^[a-zA-Z0-9_]+$/", $new_userid))){
                                        $error_message[] = "IDは半角英数字で入力してください。(「_」は使用可能です。)(USERID_DONT_USE_WORD)";
                                    }

                                }
                                // パスワードの入力チェック
                                if( empty($password) ) {
                                    $error_message[] = 'パスワードを入力してください。(PASSWORD_INPUT_PLEASE)';
                                } else {

                                    if(in_array($password, $badpass) === true ){
                                        $error_message[] = "パスワードが弱いです。セキュリティ上変更してください。(PASSWORD_ZEIJAKU)";
                                    }

                                    if( 4 > mb_strlen($password, 'UTF-8') ) {
                                        $error_message[] = 'パスワードは4文字以上である必要があります。(PASSWORD_TODOITENAI_MIN_COUNT)';
                                    }

                                    // 文字数を確認
                                    if( 256 < mb_strlen($password, 'UTF-8') ) {
                                        $error_message[] = 'パスワードは256文字以内で入力してください。(PASSWORD_OVER_MAX_COUNT)';
                                    }
                                }

                                if( empty($error_message) ) {
                                    // トランザクション開始
                                    $pdo->beginTransaction();
                                    $datetime = date("Y-m-d H:i:s");
                                    $username = htmlentities($json_account_data["userdata"]["user_name"], ENT_QUOTES, 'UTF-8', false);
                                    $mailadds = htmlentities($json_account_data["userdata"]["mail_adds"], ENT_QUOTES, 'UTF-8', false);
                                    $profile = htmlentities($json_account_data["userdata"]["user_profile"], ENT_QUOTES, 'UTF-8', false);
                                
                                    try {
                                
                                        $role = "user";
                                        $admin = "none";
                                        $hashpassword = password_hash($password, PASSWORD_DEFAULT);
                                        $loginid = sha1(uniqid(mt_rand(), true));
                                
                                        // SQL作成
                                        $stmt = $pdo->prepare("INSERT INTO account (username, userid, password, loginid, mailadds, profile, iconname, headname, role, datetime, admin) VALUES (:username, :userid, :password, :loginid, :mailadds, :profile, :iconname, :headname, :role, :datetime, :admin )");
                                
                                        // アイコン画像
                                        $stmt->bindValue(':iconname', $iconName, PDO::PARAM_STR);
                                
                                        // ヘッダー画像
                                        $stmt->bindValue(':headname', $headName, PDO::PARAM_STR);
                                
                                        // 他の値をセット
                                        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                                        $stmt->bindParam(':userid', $new_userid, PDO::PARAM_STR);
                                        $stmt->bindParam(':password', $hashpassword, PDO::PARAM_STR);
                                        $stmt->bindParam(':loginid', $loginid, PDO::PARAM_STR);
                                        $stmt->bindParam(':mailadds', $mailadds, PDO::PARAM_STR);
                                        $stmt->bindParam(':profile', $profile, PDO::PARAM_STR);
                                        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
                                        $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);
                                        
                                        $stmt->bindParam(':admin', $admin, PDO::PARAM_STR);
                                
                                        // SQLクエリの実行
                                        $res = $stmt->execute();
                                
                                        // コミット
                                        $res = $pdo->commit();
                                
                                        if(htmlspecialchars($serversettings["serverinfo"]["server_invitation"], ENT_QUOTES, 'UTF-8') === "true"){
                                            $pdo->beginTransaction();
                                
                                            $stmt = $pdo->prepare("UPDATE invitation SET used = :used, datetime = :datetime WHERE code = :code;");
                                
                                            $true = "true";
                                            $stmt->bindParam(':used', $true, PDO::PARAM_STR);
                                            $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);
                                
                                            $stmt->bindValue(':code', $invitationcode, PDO::PARAM_STR);
                                
                                                // SQLクエリの実行
                                            $res = $stmt->execute();
                                
                                            // コミット
                                            $res = $pdo->commit();
                                        }
                                    } catch (Exception $e) {
                                
                                        // エラーが発生した時はロールバック
                                        $pdo->rollBack();
                                    }
                                
                                    if ($res) {
                                        //ここのながい文字列はアカウント移行が完了したことを認証するためのもの！かえないでください！
                                        $encriptdone = openssl_encrypt("QYrLCSQIHqOLHuhJ", "AES-256-CBC", $key1_code, 0, $key2_code);

                                        $Done_Check_Link = "https://".$domain."/api/migration-api?migration_code=".$check_code."&check=".urlencode($encriptdone);
                                        $data = array();
                                        $options = [
                                            'http' => [
                                                'method'=> 'GET',
                                                'content' => http_build_query($data, '', '&')
                                            ]
                                        ];
                                        $Done_result = json_decode(file_get_contents($Done_Check_Link, false, stream_context_create($options)),true);

                                        if(isset($Done_result["data"])){
                                            $done_chk = openssl_decrypt($Done_result["data"], "AES-256-CBC", $key1_code, 0, $key2_code);
                                            $done_data = json_decode($done_chk,true);
                                            if($done_data["done"] == "success"){
                                                $_SESSION['userid'] = $new_userid;
                                                $_SESSION['done'] = true;
                                            }else{
                                                $_SESSION['userid'] = $new_userid;
                                                $_SESSION['done'] = false;
                                            }
                                            $url = '../success';
                                            header('Location: ' . $url, true, 303);
                                            exit;
                                        }else{
                                            $_SESSION['userid'] = $new_userid;
                                            $_SESSION['done'] = false;
                                            $url = '../success';
                                            header('Location: ' . $url, true, 303);
                                            exit;
                                        }
                                    } else {
                                        $error_message[] = '登録に失敗しました。(REGISTERED_DAME)';
                                    }
                                
                                    // プリペアドステートメントを削除
                                    $stmt = null;
                                }
                            }
                        }
                    }else{
                        $error_message[] = "識別コードが間違っているようです。(MIGRATION_BAD_CHECK_CODE)";
                    }
                }else{
                    $error_message[] = "移行元のサーバーのuwuzuバージョンが1.3.6未満のためアカウントの移行はできません。(MIGRATION_FROM_SERVER_BAD_UWUZU_VERSION)";
                }
            }else{
                $error_message[] = "移行元のサーバーのソフトウェアがuwuzuではありません。(MIGRATION_FROM_SERVER_NOT_UWUZU)";
            }
        }
    }else{
        $error_message[] = "このサーバーではアカウントの移行登録を受け入れていません。(MIGRATION_SORRY)";
    }
    
}

// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head prefix="og:http://ogp.me/ns#">
<meta charset="utf-8">
<link rel="stylesheet" href="/css/style.css">
<script src="/js/jquery-min.js"></script>
<script src="/js/unsupported.js"></script>
<?php if(!empty(H_CAPTCHA_ONOFF && H_CAPTCHA_ONOFF == "true")){?>
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
<?php }?>
<?php if(!empty(CF_TURNSTILE_ONOFF && CF_TURNSTILE_ONOFF == "true")){?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php }?>
<link rel="apple-touch-icon" type="image/png" href="/favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="/favicon/icon-192x192.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>アカウントの移行登録 - <?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></title>
</head>
<script src="/js/back.js"></script>
<body>


<div class="leftbox">
    <?php if(!empty(htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8'))){ ?>
        <div class="logo">
            <a href="/index.php"><img src=<?php echo htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8');?>></a>
        </div>
    <?php }else{?>
        <div class="logo">
            <a href="/index.php"><img src="/img/uwuzulogo.svg"></a>
        </div>
    <?php }?>

    <div class="textbox">
        <h1>アカウントの移行登録</h1>
        <p>ここからアカウントの移行登録が可能です！</p>
        <?php if( !empty($error_message) ): ?>
            <ul class="errmsg">
                <?php foreach( $error_message as $value ): ?>
                    <p>・ <?php echo $value; ?></p>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
       
        <?php if(htmlspecialchars($serversettings["serverinfo"]["server_account_migration"], ENT_QUOTES, 'UTF-8') === "true"){?>
            <form class="formarea" enctype="multipart/form-data" method="post">
                <div>
                    <p>新しいユーザーID</p>
                    <div class="p2">このサーバーで使用するユーザーIDを入力してください。</div>
                    <input id="new_userid" type="text" placeholder="" class="inbox" name="new_userid" value="">
                </div>
                <div>
                    <p>新しいパスワード</p>
                    <div class="p2">このサーバーで使用するパスワードを入力してください。</div>
                    <input id="password" type="text" placeholder="" class="inbox" name="password" value="">
                </div>
                <div>
                    <p>アカウント移行元のuwuzuサーバーのドメイン</p>
                    <div class="p2">アカウント移行元のサーバードメインを入力してください。</div>
                    <input id="moto_server_domain" type="text" placeholder="uwuzu.example.com" class="inbox" name="moto_server_domain" value="">
                </div>
                <div>
                    <p>識別コード</p>
                    <div class="p2">アカウント移行元のサーバーで発行された識別コードを入力してください。</div>
                    <input id="moto_server_account_check" type="text" placeholder="" class="inbox" name="moto_server_account_check" value="">
                </div>

                <div>
                    <p>認証コード</p>
                    <div class="p2">アカウント移行元のサーバーで発行された認証コードを入力してください。</div>
                    <input id="moto_server_account_auth" type="text" placeholder="" class="inbox" name="moto_server_account_auth" value="">
                </div>
                <?php if(!empty(H_CAPTCHA_ONOFF && H_CAPTCHA_ONOFF == "true")){?>
                    <div class="captcha_zone">
                        <div class="p2">あなたは人間ですか？<br>もし人間であれば下のチェックボックスにチェックしてください！</div>
                        <div class="h-captcha" data-sitekey="<?php echo htmlentities(H_CAPTCHA_SITE_KEY);?>"></div>
                    </div>
                <?php }?>
                <?php if(!empty(CF_TURNSTILE_ONOFF && CF_TURNSTILE_ONOFF == "true")){?>
                    <div class="captcha_zone">
                        <div class="cf-turnstile" data-sitekey="<?php echo htmlentities(CF_TURNSTILE_SITE_KEY);?>" data-callback="javascriptCallback" data-language="ja"></div>
                    </div>
                <?php }?>
                <?php if(htmlspecialchars($serversettings["serverinfo"]["server_invitation"], ENT_QUOTES, 'UTF-8') === "true"){?>
                    <div>
                        <p>招待コード</p>
                        <div class="p2">招待コードがないとこのサーバーには登録できません。</div>
                        <input id="invitationcode" type="text" placeholder="" class="inbox" name="invitationcode" value="<?php if( !empty($_SESSION['invitationcode']) ){ echo htmlspecialchars( $_SESSION['invitationcode'], ENT_QUOTES, 'UTF-8'); } ?>">
                    </div>
                <?php }?>

                <input type="submit" class = "irobutton" name="btn_submit" value="移行開始">
            </form>
        <?php }else{?>
            <p>このサーバーではアカウントの移行登録を受け入れていません。</p>
        <?php }?>
        

        <div class="btnbox">
                <a href="javascript:history.back();" class="sirobutton">戻る</a>
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


window.addEventListener('DOMContentLoaded', function(){
    $('#file_upload').change(function(e) {
        var file_reader = new FileReader();
        file_reader.addEventListener('load', function(e) {
            $('#img_select').show();
            $('#iconimg').attr('src', file_reader.result);
        });
        file_reader.readAsDataURL(e.target.files[0]);
    });
});
</script>
</body>
</html>