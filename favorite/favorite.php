<?php
require('../db.php');

if (htmlentities(isset($_POST['uniqid'])) && htmlentities(isset($_POST['userid'])) && htmlentities(isset($_POST['account_id']))) {
    $postUniqid = htmlentities($_POST['uniqid']);
    $userId = htmlentities($_POST['userid']);
    $loginid = htmlentities($_POST['account_id']);

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
                $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS);

                // 投稿のいいね情報を取得
                $stmt = $pdo->prepare("SELECT favorite FROM ueuse WHERE uniqid = :uniqid");
                $stmt->bindValue(':uniqid', $postUniqid, PDO::PARAM_STR);
                $stmt->execute();
                $post = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($post) {
                    $favoriteList = explode(',', $post['favorite']);
                    $index = array_search($userId, $favoriteList);

                    if ($index === false) {
                        // ユーザーIDを追加
                        $favoriteList[] = $userId;
                    } else {
                        // ユーザーIDを削除
                        array_splice($favoriteList, $index, 1);
                    }

                    // 新しいいいね情報を更新
                    $newFavorite = implode(',', $favoriteList);
                    $updateQuery = $pdo->prepare("UPDATE ueuse SET favorite = :favorite WHERE uniqid = :uniqid");
                    $updateQuery->bindValue(':favorite', $newFavorite, PDO::PARAM_STR);
                    $updateQuery->bindValue(':uniqid', $postUniqid, PDO::PARAM_STR);
                    $res = $updateQuery->execute();

                    if ($res) {
                        echo json_encode(['success' => true, 'newFavorite' => $newFavorite]);
                        exit;
                    } else {
                        echo json_encode(['success' => false, 'error' => 'いいねの更新に失敗しました。']);
                        exit;
                    }


                } else {
                    echo json_encode(['success' => false, 'error' => '投稿が見つかりません。']);
                    exit;
                }
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'データベースエラー：' . $e->getMessage()]);
                exit;
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => '必要なパラメータが提供されていません。']);
        exit;
    }
?>
