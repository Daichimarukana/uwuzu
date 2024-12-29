<?php
require('../../db.php');
require("../../function/function.php");

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

if (isset($_FILES['update_zip']) && isset($_POST['userid']) && isset($_POST['account_id'])){
    $postUserid = safetext($_POST['userid']);
    $postZip= $_FILES['update_zip'];
    $loginid = safetext($_POST['account_id']);

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

    $query = $pdo->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

    $query->execute(array(':userid' => $postUserid));

    $result2 = $query->fetch();

    if($result2["loginid"] === $loginid){
        if($result2["admin"] === "yes"){

            $uploadDir = sys_get_temp_dir();
            $uploadFile = $uploadDir . '/' . basename($_FILES['update_zip']['name']);

            if (move_uploaded_file($_FILES['update_zip']['tmp_name'], $uploadFile)) {
                $extractPath = $uploadDir . '/uwuzu_update_' . createUniqId();

                $zip = new ZipArchive;
                if ($zip->open($uploadFile) == true) {
                    $zip->extractTo($extractPath);
                    $zip->close();

                    // JSONファイルを読み込む
                    $jsonFile = $extractPath . '/update.json';
                    if (file_exists($jsonFile)) {
                        $jsonData = json_decode(file_get_contents($jsonFile), true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $response = [
                                'success' => true,
                                'software_name' => safetext($jsonData['software']) ?? '名前がありません',
                                'version' => safetext($jsonData['version']) ?? 'バージョン情報がありません',
                                'release_notes' => safetext($jsonData['release_notes']) ?? 'リリースノートが見つかりません。',
                                'notices' => safetext($jsonData['notices']) ?? '注意事項が見つかりません。',
                                'file_path' => safetext($extractPath)
                            ];
                            echo json_encode($response);
                        } else {
                            echo json_encode(['success' => false, 'error' => 'JSONファイルの読み込みに失敗しました。(ROADING_JSON_ERROR)']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'error' => 'JSONファイルの読み込みに失敗しました。(ROADING_JSON_ERROR)']);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => '読み込みに失敗しました。1(ROADING_ERROR)']);
                }

                if (file_exists($uploadFile)) {
                    if (is_file($uploadFile)) {
                        unlink($uploadFile);
                    }
                }
            }
        }
    }
}else{
    echo json_encode(['success' => false, 'error' => '読み込みに失敗しました。2(ROADING_ERROR)']);
    exit;
}
?>
