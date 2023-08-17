<?php
require('../db.php');

if (isset($_POST['uniqid']) && isset($_POST['Rptext']) && isset($_POST['upload_images']) && isset($_POST['upload_images2']) && isset($_POST['upload_videos1'])) {
    $rpuniqid = $_POST['uniqid'];
    $Rptext = $_POST['Rptext'];
    $upload_images = $_POST['upload_images'];
    $upload_images2 = $_POST['upload_images2'];
    $upload_videos1 = $_POST['upload_videos1'];

    $errors = array();


    // メッセージの入力チェック
	if( empty($Rptext) ) {
		$error_message[] = '内容を入力してください。';
	} else {

        // 文字数を確認
        if( 1024 < mb_strlen($Rptext, 'UTF-8') ) {
			$errors[] = '内容は1024文字以内で入力してください。';
		}
    }


	if (empty($upload_images['upload_images']['name'])) {
		$photo1 = "none";
	} else {
		// アップロードされたファイル情報
		$uploadedFile = $upload_images['upload_images'];

		// アップロードされたファイルの拡張子を取得
		$extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
		
		// 新しいファイル名を生成（uniqid + 拡張子）
		$newFilename = uniqid() . '-'.$userid.'.' . $extension;
		
		// 保存先のパスを生成
		$uploadedPath = '../ueuseimages/' . $newFilename;
		
		// ファイルを移動
		$result = move_uploaded_file($uploadedFile['tmp_name'], $uploadedPath);
		
		if ($result) {
			$photo1 = $uploadedPath; // 保存されたファイルのパスを使用
		} else {
			$errors[] = 'アップロード失敗！(1)エラーコード：' . $uploadedFile['error'];
		}
	}

	if (empty($upload_images2['upload_images2']['name'])) {
		$photo2 = "none";
	} else {
		// アップロードされたファイル情報
		$uploadedFile2 = $upload_images2['upload_images2'];

		// アップロードされたファイルの拡張子を取得
		$extension2 = pathinfo($uploadedFile2['name'], PATHINFO_EXTENSION);
		
		// 新しいファイル名を生成（uniqid + 拡張子）
		$newFilename2 = uniqid() . '-'.$userid.'.' . $extension2;
		
		// 保存先のパスを生成
		$uploadedPath2 = '../ueuseimages/' . $newFilename2;
		
		// ファイルを移動
		$result2 = move_uploaded_file($uploadedFile2['tmp_name'], $uploadedPath2);
		
		if ($result2) {
			$photo2 = $uploadedPath2; // 保存されたファイルのパスを使用
		} else {
			$errors[] = 'アップロード失敗！(2)エラーコード：' . $uploadedFile2['error'];
		}
	}

	if (empty($upload_videos1['upload_videos1']['name'])) {
		$video1 = "none";
	} else {
		// アップロードされたファイル情報
		$uploadedFile3 = $upload_videos1['upload_videos1'];
		
		// アップロードされたファイルの拡張子を取得
		$extension3 = strtolower(pathinfo($uploadedFile3['name'], PATHINFO_EXTENSION)); // 小文字に変換

		// サポートされている動画フォーマットの拡張子を配列で定義
		$supportedExtensions = array("mp4", "avi", "mov", "webm");

		if (in_array($extension3, $supportedExtensions)) {
			// 正しい拡張子の場合、新しいファイル名を生成
			$newFilename3 = uniqid() . '-'.$userid.'.' . $extension3;
			// 保存先のパスを生成
			$uploadedPath3 = '../ueusevideos/' . $newFilename3;
		
			// ファイルを移動
			$result3 = move_uploaded_file($uploadedFile3['tmp_name'], $uploadedPath3);
		
			if ($result3) {
				$video1 = $uploadedPath3; // 保存されたファイルのパスを使用
			} else {
				$errors[] = 'アップロード失敗！エラーコード：' . $uploadedFile3['error'];
			}
		} else {
			$errors[] = '対応していないファイル形式です！';
		}
		
		
	}

    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    

	if( empty($error_message) ) {


		
		    // 書き込み日時を取得
            $datetime = date("Y-m-d H:i:s");
			$uniqid = createUniqId();
			$abi = "none";

            // トランザクション開始
            $pdo->beginTransaction();

            try {

                // SQL作成
                $stmt = $pdo->prepare("INSERT INTO ueuse (username, account, uniqid, rpuniqid, ueuse, photo1, photo2, video1, datetime, abi) VALUES (:username, :account, :uniqid, :rpuniqid, :ueuse, :photo1, :photo2, :video1, :datetime, :abi)");
        
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':account', $userid, PDO::PARAM_STR);
				$stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
                $stmt->bindParam(':rpuniqid', $rpuniqid, PDO::PARAM_STR);
                $stmt->bindParam(':ueuse', $Rptext, PDO::PARAM_STR);

				$stmt->bindParam(':photo1', $photo1, PDO::PARAM_STR);
				$stmt->bindParam(':photo2', $photo2, PDO::PARAM_STR);
				$stmt->bindParam(':video1', $video1, PDO::PARAM_STR);
                $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

				$stmt->bindParam(':abi', $abi, PDO::PARAM_STR);

                // SQLクエリの実行
                $res = $stmt->execute();

                // コミット
                $res = $pdo->commit();

				// 書き込み日時を取得
				$datetime = date("Y-m-d H:i:s");
				$title = ''.$userid.'さんが返信しました！'
				$msg = ''.$userid.'さんがあなたの投稿に返信しました！'
				$url = '/!'.$rpuniqid
	
				// トランザクション開始
				$pdo->beginTransaction();
	
				$stmt = $pdo->prepare("INSERT INTO notification (touserid, title, msg, url, datetime) VALUES (:touserid, :title, :msg, :url, :datetime,)");
	
				$stmt->bindParam(':touserid', $touserid, PDO::PARAM_STR);
				$stmt->bindParam(':title', $title, PDO::PARAM_STR);
				$stmt->bindParam(':msg', $msg, PDO::PARAM_STR);
				$stmt->bindParam(':url', $url, PDO::PARAM_STR);
				$stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

				$res = $stmt->execute();
				$res = $pdo->commit();

            } catch(Exception $e) {

                // エラーが発生した時はロールバック
                $pdo->rollBack();
        	}

            if ($res) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => '追加に失敗しました。']);
                exit;
            }
        } catch(PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'データベースエラー：' . $e->getMessage()]);
            exit;
        }

        if (!empty($errors)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        

            // プリペアドステートメントを削除
    $stmt = null;
}


?>
