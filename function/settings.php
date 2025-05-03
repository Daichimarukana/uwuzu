<?php
header('Content-Type: application/json');

require('../db.php');
require("function.php");
blockedIP($_SERVER['REMOTE_ADDR']);
if (safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey'])) && safetext(isset($_POST['settings_type']))) {
    //------------------------------------------------------
    if(file_exists("../settings_admin/plugin_settings/amazons3_settings.php")){
        require_once '../settings_admin/plugin_settings/amazons3_settings.php';
        if(AMS3_CHKS == "true"){
            if(file_exists("../plugin/aws/aws-autoloader.php")){
                require_once '../plugin/aws/aws-autoloader.php';
            }else{
                actionLog(null, "error", "settings", null, "AWS SDK for PHPが見つかりませんでした！", 4);
            }
        }
    }else{
        actionLog(null, "error", "settings", null, "amazons3_settings.phpが見つかりませんでした！", 3);
    }

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
    
    $userid = safetext($_POST['userid']);
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);

    $is_login = uwuzuUserLoginCheck($loginid, $loginkey, "user");
    if ($is_login === false) {
        echo json_encode(['success' => false, 'error' => '認証に失敗しました。(AUTH_INVALID)']);
        exit;
    }

    $settings_type = safetext($_POST['settings_type']);

    if($settings_type == "icon"){
        if(isset($_FILES["data"])){
            if (!(empty($_FILES['data']['name']))) {
                $uploadedFile = $_FILES['data'];
                if(check_mime($uploadedFile['tmp_name'])){
                    $extension = convert_mime(check_mime($uploadedFile['tmp_name']));
                    delete_exif($extension, $uploadedFile['tmp_name']);
                    resizeImage($uploadedFile['tmp_name'], 512, 512);

                    if(AMS3_CHKS == "true"){
                        $usericonurl = getUserData($pdo, $userid)["iconname"];
                        if(filter_var($usericonurl, FILTER_VALIDATE_URL)){
                            $s3delresult = deleteAmazonS3($usericonurl);
                        }else{
                            $s3delresult = true;
                        }
                        if($s3delresult == true){
                            $s3result = uploadAmazonS3($uploadedFile['tmp_name']);
                        }else{
                            $s3result = false;
                        }
                    }else{
                        if(check_mime($uploadedFile['tmp_name']) == "image/webp"){
                            $newFilename = createUniqId() . '-'.$userid.'.webp';
                        }else{
                            $newFilename = createUniqId() . '-'.$userid.'.' . $extension;
                        }
                        $uploadedPath = 'usericons/' . $newFilename;
                        $result = move_uploaded_file($uploadedFile['tmp_name'], '../'.$uploadedPath);
                        
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
                            $error_message[] = 'アップロード失敗！(1)エラーコード：' .$errcode.'';
                        }
                    }
                    if(isset($s3result)){
                        if($s3result == false){
                            $error_message[] = 'アップロード失敗！(1)エラーコード： S3ERROR';
                        }else{
                            $iconName = $s3result; // S3に保存されたファイルのパスを使用
                        }
                    }   
                }else{
                    $error_message[] = "使用できない画像形式です。(FILE_UPLOAD_DEKINAKATTA)";
                }
            }else{
                $error_message[] = 'アイコン画像を選択してください(PHOTO_SELECT_PLEASE)';
            }
        
            if(empty($error_message)) {
                $currentIconPath = getUserData($pdo, $userid)["iconname"];

                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("UPDATE account SET iconname = :iconname WHERE userid = :userid");
                    $stmt->bindValue(':iconname', $iconName, PDO::PARAM_STR);
                    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
                    $res = $stmt->execute();
                    $res = $pdo->commit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                }
            
                if ($res) {
                    if ($currentIconPath) {
                        unlink('../' . $currentIconPath);
                    }
                    echo json_encode(['success' => true]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'error' => '更新に失敗しました']);
                    exit;
                }
            
                // プリペアドステートメントを削除
                $stmt = null;
            }else{
                echo json_encode(['success' => false, 'error' => $error_message[0]]);
                exit;
            }
        }
    }

    if($settings_type == "header"){
        if(isset($_FILES["data"])){
            if (!(empty($_FILES['data']['name']))) {
                $uploadedFile = $_FILES['data'];
                if(check_mime($uploadedFile['tmp_name'])){
                    $extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
                    delete_exif($extension, $uploadedFile['tmp_name']);
                    resizeImage($uploadedFile['tmp_name'], 2048, 1024);
        
                    if(AMS3_CHKS == "true"){
                        $userheadurl = getUserData($pdo, $userid)["headname"];
                        if(filter_var($userheadurl, FILTER_VALIDATE_URL)){
                            $s3delresult = deleteAmazonS3($userheadurl);
                        }else{
                            $s3delresult = true;
                        }
                        if($s3delresult == true){
                            $s3result = uploadAmazonS3($uploadedFile['tmp_name']);
                        }else{
                            $s3result = false;
                        }
                    }else{
                        if(check_mime($uploadedFile['tmp_name']) == "image/webp"){
                            $newFilename = createUniqId() . '-'.$userid.'.webp';
                        }else{
                            $newFilename = createUniqId() . '-'.$userid.'.' . $extension;
                        }
                        $uploadedPath = 'userheads/' . $newFilename;
                        $result = move_uploaded_file($uploadedFile['tmp_name'], '../'.$uploadedPath);
                        
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
                            $error_message[] = 'アップロード失敗！(2)エラーコード：' .$errcode.'';
                        }
                    }
                    if(isset($s3result)){
                        if($s3result == false){
                            $error_message[] = 'アップロード失敗！(2)エラーコード： S3ERROR';
                        }else{
                            $headName = $s3result; // S3に保存されたファイルのパスを使用
                        }
                    }
                }else{
                    $error_message[] = "使用できない画像形式です。(FILE_UPLOAD_DEKINAKATTA)";
                }
            }else{
                $error_message[] = 'アイコン画像を選択してください(PHOTO_SELECT_PLEASE)';
            }
        
            if(empty($error_message)) {
                $currentHeadPath = getUserData($pdo, $userid)["headname"];
                
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("UPDATE account SET headname = :headname WHERE userid = :userid");
                    $stmt->bindValue(':headname', $headName, PDO::PARAM_STR);
                    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
                    $res = $stmt->execute();
                    $res = $pdo->commit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                }
            
                if ($res) {
                    if ($currentHeadPath) {
                        unlink('../' . $currentHeadPath);
                    }
                    echo json_encode(['success' => true]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'error' => '更新に失敗しました']);
                    exit;
                }
            
                // プリペアドステートメントを削除
                $stmt = null;
            }else{
                echo json_encode(['success' => false, 'error' => $error_message[0]]);
                exit;
            }
        }
    }

} else {
    echo json_encode(['success' => false, 'error' => '必要なパラメータが提供されていません。']);
    exit;
}
 
?>