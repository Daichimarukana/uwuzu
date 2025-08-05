<?php

$domain = $_SERVER['HTTP_HOST'];
require(__DIR__ . '/../../../db.php');
require(__DIR__ . "/../../../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
    
$pdo = null;
$error_message = array();
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

$Get_Post_Json = file_get_contents("php://input");
if (isset($_GET['token']) || (!(empty($Get_Post_Json)))) {
    //トークン取得
    if (!(empty($_GET['token']))) {
        $token = safetext($_GET['token']);
    } else {
        $post_json = json_decode($Get_Post_Json, true);
        if (isset($post_json["token"])) {
            $token = safetext($post_json["token"]);
        } else {
            $err = "input_not_found";
            $response = array(
                'error_code' => $err,
                'success' => false
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    if ($token == "") {
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
            'success' => false
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!empty($pdo)) {
        $AuthData = APIAuth($pdo, $token, "write:me");
        if ($AuthData[0] === true) {
            if(file_exists(__DIR__ . "/../../../settings_admin/plugin_settings/amazons3_settings.php")){
                require_once __DIR__ . '/../../../settings_admin/plugin_settings/amazons3_settings.php';
                if(AMS3_CHKS == "true"){
                    if(file_exists(__DIR__ . "/../../../plugin/aws/aws-autoloader.php")){
                        require_once __DIR__ . '/../../../plugin/aws/aws-autoloader.php';
                    }else{
                        actionLog(null, "error", "settings", null, "AWS SDK for PHPが見つかりませんでした！", 4);
                    }
                }
            }else{
                actionLog(null, "error", "settings", null, "amazons3_settings.phpが見つかりませんでした！", 3);
            }
            
            $userData = $AuthData[2];
            $userid = $userData["userid"];

            $add_sql = array();

            if (!(empty($_GET['username']))) {
                $username = safetext($_GET['username']);
            } elseif (!(empty($post_json["username"]))) {
                $username = safetext($post_json["username"]);
            } else {
                $username = null;
            }

            // ユーザーネームの入力チェック
            if (!($username === null)) {
                if (empty($username)) {
                    $error_message[] = '表示名を入力してください。(USERNAME_INPUT_PLEASE)';
                } else {
                    // 文字数を確認
                    if (50 < mb_strlen($username, 'UTF-8')) {
                        $error_message[] = 'ユーザーネームは50文字以内で入力してください。(USERNAME_OVER_MAX_COUNT)';
                    }
                }

                $add_sql[] = "username = :username";
            }


            if (!(empty($_GET['profile']))) {
                $profile = safetext($_GET['profile']);
            } elseif (!(empty($post_json["profile"]))) {
                $profile = safetext($post_json["profile"]);
            } else {
                $profile = null;
            }

            if (!($profile === null)) {
                if (1024 < mb_strlen($profile, 'UTF-8')) {
                    $error_message[] = 'プロフィールは1024文字以内で入力してください。(INPUT_OVER_MAX_COUNT)';
                }
                $add_sql[] = "profile = :profile";
            }


            if (!(empty($post_json["icon"]))) {
                $imageData = base64_decode($post_json["icon"], true);

                $tmpFilePath = tempnam(sys_get_temp_dir(), 'upload_' . createUniqId());
                file_put_contents($tmpFilePath, $imageData);

                $IconFiles = [
                    'name' => 'upload.png',
                    'type' => check_mime($tmpFilePath),
                    'tmp_name' => $tmpFilePath,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($imageData),
                ];
            } else {
                $IconFiles = array();
            }


            if (isset($IconFiles)) {
                if (!(empty($IconFiles['name']))) {
                    $uploadedFile = $IconFiles;
                    if (check_mime($uploadedFile['tmp_name'])) {
                        $extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
                        delete_exif($extension, $uploadedFile['tmp_name']);
                        resizeImage($uploadedFile['tmp_name'], 512, 512);

                        if (AMS3_CHKS == "true") {
                            $usericonurl = getUserData($pdo, $userid)["iconname"];
                            if (filter_var($usericonurl, FILTER_VALIDATE_URL)) {
                                $s3delresult = deleteAmazonS3($usericonurl);
                            } else {
                                $s3delresult = true;
                            }
                            if ($s3delresult == true) {
                                $s3result = uploadAmazonS3($uploadedFile['tmp_name']);
                            } else {
                                $s3result = false;
                            }
                        } else {
                            if (check_mime($uploadedFile['tmp_name']) == "image/webp") {
                                $newFilename = createUniqId() . '-' . $userid . '.webp';
                            } else {
                                $newFilename = createUniqId() . '-' . $userid . '.' . $extension;
                            }
                            $uploadedPath = 'usericons/' . $newFilename;
                            $result = rename($uploadedFile['tmp_name'], __DIR__ . '/../../../' . $uploadedPath);

                            if ($result) {
                                $iconName = $uploadedPath; // 保存されたファイルのパスを使用
                                $currentIconPath = getUserData($pdo, $userid)["iconname"];
                            } else {
                                $errnum = $uploadedFile['error'];
                                $errcode = "ERROR";

                                switch ($errnum) {
                                    case 1:
                                        $errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";
                                        break;
                                    case 2:
                                        $errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";
                                        break;
                                    case 3:
                                        $errcode = "FILE_SUKOSHIDAKE_UPLOAD";
                                        break;
                                    case 4:
                                        $errcode = "FILE_UPLOAD_DEKINAKATTA";
                                        break;
                                    case 6:
                                        $errcode = "TMP_FOLDER_NAI";
                                        break;
                                    case 7:
                                        $errcode = "FILE_KAKIKOMI_SIPPAI";
                                        break;
                                    case 8:
                                        $errcode = "PHPINFO()_KAKUNIN";
                                        break;
                                    case 0:
                                        // 成功だったのに move_uploaded_file() が失敗した
                                        if (!is_uploaded_file($uploadedFile['tmp_name'])) {
                                            $errcode = "TMP_FILE_NAI";
                                        } elseif (!is_writable(__DIR__ . '/../../../usericons/')) {
                                            $errcode = "SAVE_FOLDER_KAKIKOMI_KENNAI";
                                        } else {
                                            $errcode = "MOVE_UPLOAD_FILE_SIPPAI";
                                        }
                                        break;
                                    }
                                $error_message[] = 'アップロード失敗！(1)エラーコード：' . $errcode . '';
                            }
                        }
                        if (isset($s3result)) {
                            if ($s3result == false) {
                                $error_message[] = 'アップロード失敗！(1)エラーコード： S3ERROR';
                            } else {
                                $iconName = $s3result; // S3に保存されたファイルのパスを使用
                                $currentIconPath = getUserData($pdo, $userid)["iconname"];
                            }
                        }
                    } else {
                        $error_message[] = "使用できない画像形式です。(FILE_UPLOAD_DEKINAKATTA)";
                    }
                }
            }
            if (!(empty($iconName))) {
                $add_sql[] = "iconname = :iconname";
            }


            if (!(empty($post_json["header"]))) {
                $imageData = base64_decode($post_json["header"], true);

                $tmpFilePath = tempnam(sys_get_temp_dir(), 'upload_' . createUniqId());
                file_put_contents($tmpFilePath, $imageData);

                $HeadFiles = [
                    'name' => 'upload.png',
                    'type' => check_mime($tmpFilePath),
                    'tmp_name' => $tmpFilePath,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($imageData),
                ];
            } else {
                $HeadFiles = array();
            }


            if (isset($HeadFiles)) {
                if (!(empty($HeadFiles['name']))) {
                    $uploadedFile = $HeadFiles;
                    if (check_mime($uploadedFile['tmp_name'])) {
                        $extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
                        delete_exif($extension, $uploadedFile['tmp_name']);
                        resizeImage($uploadedFile['tmp_name'], 2048, 1024);

                        if (AMS3_CHKS == "true") {
                            $userheadurl = getUserData($pdo, $userid)["headname"];
                            if (filter_var($userheadurl, FILTER_VALIDATE_URL)) {
                                $s3delresult = deleteAmazonS3($userheadurl);
                            } else {
                                $s3delresult = true;
                            }
                            if ($s3delresult == true) {
                                $s3result = uploadAmazonS3($uploadedFile['tmp_name']);
                            } else {
                                $s3result = false;
                            }
                        } else {
                            if (check_mime($uploadedFile['tmp_name']) == "image/webp") {
                                $newFilename = createUniqId() . '-' . $userid . '.webp';
                            } else {
                                $newFilename = createUniqId() . '-' . $userid . '.' . $extension;
                            }
                            $uploadedPath = 'userheads/' . $newFilename;
                            $result = rename($uploadedFile['tmp_name'], __DIR__ . '/../../../' . $uploadedPath);

                            if ($result) {
                                $headName = $uploadedPath; // 保存されたファイルのパスを使用
                                $currentHeadPath = getUserData($pdo, $userid)["headname"];
                            } else {
                                $errnum = $uploadedFile['error'];
                                $errcode = "ERROR";

                                switch ($errnum) {
                                    case 1:
                                        $errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";
                                        break;
                                    case 2:
                                        $errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";
                                        break;
                                    case 3:
                                        $errcode = "FILE_SUKOSHIDAKE_UPLOAD";
                                        break;
                                    case 4:
                                        $errcode = "FILE_UPLOAD_DEKINAKATTA";
                                        break;
                                    case 6:
                                        $errcode = "TMP_FOLDER_NAI";
                                        break;
                                    case 7:
                                        $errcode = "FILE_KAKIKOMI_SIPPAI";
                                        break;
                                    case 8:
                                        $errcode = "PHPINFO()_KAKUNIN";
                                        break;
                                    case 0:
                                        // 成功だったのに move_uploaded_file() が失敗した
                                        if (!is_uploaded_file($uploadedFile['tmp_name'])) {
                                            $errcode = "TMP_FILE_NAI";
                                        } elseif (!is_writable(__DIR__ . '/../../../usericons/')) {
                                            $errcode = "SAVE_FOLDER_KAKIKOMI_KENNAI";
                                        } else {
                                            $errcode = "MOVE_UPLOAD_FILE_SIPPAI";
                                        }
                                        break;
                                    }
                                $error_message[] = 'アップロード失敗！(2)エラーコード：' . $errcode . '';
                            }
                        }
                        if (isset($s3result)) {
                            if ($s3result == false) {
                                $error_message[] = 'アップロード失敗！(2)エラーコード： S3ERROR';
                            } else {
                                $headName = $s3result; // S3に保存されたファイルのパスを使用
                                $currentHeadPath = getUserData($pdo, $userid)["headname"];
                            }
                        }
                    } else {
                        $error_message[] = "使用できない画像形式です。(FILE_UPLOAD_DEKINAKATTA)";
                    }
                }
            }

            if (!(empty($headName))) {
                $add_sql[] = "headname = :headname";
            }

            if(empty($add_sql)) {
                $err = "input_not_found";
                $response = array(
                    'error_code' => $err,
                    'success' => false
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }else{
                $add_sql = implode(", ", $add_sql);
            }

            if (empty($error_message)) {
                // トランザクション開始
                $pdo->beginTransaction();

                try {

                    $stmt = $pdo->prepare("UPDATE account SET ".$add_sql." WHERE userid = :userid;");

                    // 他の値をセット
                    if (!(empty($username))) {
                        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
                    }
                    if (!(empty($profile))) {
                        $stmt->bindValue(':profile', $profile, PDO::PARAM_STR);
                    }
                    if (!(empty($iconName))) {
                        $stmt->bindValue(':iconname', $iconName, PDO::PARAM_STR);
                    }
                    if (!(empty($headName))) {
                        $stmt->bindValue(':headname', $headName, PDO::PARAM_STR);
                    }
                    
                    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
                    $res = $stmt->execute();

                    // コミット
                    if($res) {
                        $pdo->commit();
                        if (!(empty($iconName))) {
                            if ($currentIconPath && !filter_var($currentIconPath, FILTER_VALIDATE_URL)) {
                                $filePath = realpath(__DIR__ . '/../../../' . $currentIconPath);
                                if ($filePath && file_exists($filePath)) {
                                    unlink($filePath);
                                }
                            }
                        }
                        if (!(empty($headName))) {
                            if ($currentHeadPath && !filter_var($currentHeadPath, FILTER_VALIDATE_URL)) {
                                $filePath = realpath(__DIR__ . '/../../../' . $currentHeadPath);
                                if ($filePath && file_exists($filePath)) {
                                    unlink($filePath);
                                }
                            }
                        }

                        $response = array(
                            'success' => true
                        );
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        exit;
                    } else {
                        $pdo->rollBack();
                        $err = "update_failed";
                        $response = array(
                            'error_code' => $err,
                            'success' => false
                        );
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                } catch (Exception $e) {
                    $pdo->rollBack();
                    actionLog($userid, "error", "user-settings-api", null, $e, 4);
                    $err = "update_failed";
                    $response = array(
                        'error_code' => $err,
                        'success' => false
                    );
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $stmt = null;
            }else{
                $err = $error_message;
                $response = array(
                    'error_code' => $err,
                    'success' => false
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            $err = $AuthData[1];
            $response = array(
                'error_code' => $err,
                'success' => false
            );

            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
} else {
    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
        'success' => false
    );

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
