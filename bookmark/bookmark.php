<?php
header('Content-Type: application/json');
require('../db.php');
require("../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

if (safetext(isset($_POST['uniqid'])) && safetext(isset($_POST['userid'])) && safetext(isset($_POST['account_id'])) && safetext(isset($_COOKIE['loginkey']))) {
    $postUniqid = safetext($_POST['uniqid']);
    $userId = safetext($_POST['userid']);
    $loginid = safetext($_POST['account_id']);
    $loginkey = safetext($_COOKIE['loginkey']);

    $is_login = uwuzuUserLoginCheck($loginid, $loginkey, "user");
    if ($is_login === false) {
        echo json_encode(['success' => false, 'error' => '認証に失敗しました。(AUTH_INVALID)']);
        exit;
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

        // データベース接続の設定
        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));

        $query = $dbh->prepare('SELECT * FROM account WHERE userid = :userid limit 1');

        $query->execute(array(':userid' => $userId));

        $result2 = $query->fetch();

        if($result2["loginid"] === $loginid){

            try {
                // Bookmark情報を取得
                $stmt = $pdo->prepare("SELECT bookmark FROM account WHERE userid = :userid");
                $stmt->bindValue(':userid', $userId, PDO::PARAM_STR);
                $stmt->execute();
                $post = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($post) {
                    // ユーズ情報を取得
                    $ueusestmt = $pdo->prepare("SELECT uniqid FROM ueuse WHERE uniqid = :uniqid LIMIT 1");
                    $ueusestmt->bindValue(':uniqid', $postUniqid, PDO::PARAM_STR);
                    $ueusestmt->execute();
                    $ueuse = $ueusestmt->fetch(PDO::FETCH_ASSOC);
                    if(!(empty($ueuse))){
                        $bookmarkList = explode(',', $post['bookmark']);
                        $index = array_search($postUniqid, $bookmarkList);

                        if ($index === false) {
                            // UniqIDを追加
                            $bookmarkList[] = $postUniqid;
                        } else {
                            // UniqIDを削除
                            array_splice($bookmarkList, $index, 1);
                        }

                        // 新しいいいね情報を更新
                        $newbookmark = implode(',', $bookmarkList);
                        $updateQuery = $pdo->prepare("UPDATE account SET bookmark = :bookmark WHERE userid = :userid");
                        $updateQuery->bindValue(':bookmark', $newbookmark, PDO::PARAM_STR);
                        $updateQuery->bindValue(':userid', $userId, PDO::PARAM_STR);
                        $res = $updateQuery->execute();

                        if ($res) {
                            echo json_encode(['success' => true, 'newbookmark' => 'success']);
                            exit;
                        } else {
                            echo json_encode(['success' => false, 'error' => 'ブックマークの更新に失敗しました。']);
                            exit;
                        }
                    } else {
                        echo json_encode(['success' => false, 'error' => 'ユーズが見つかりません。']);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'アカウントが見つかりません。']);
                    exit;
                }
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'データベースエラー']);
                exit;
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => '必要なパラメータが提供されていません。']);
        exit;
    }
?>
