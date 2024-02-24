<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

function send_html_mail($mailadds,$mailtitle,$mailtext,$server_file_backslash){

    if(!empty(MAIL_CHKS && MAIL_CHKS == "true")){
        if(file_exists("".$server_file_backslash."plugin/PHPMailer/")){
            require(''.$server_file_backslash.'plugin/PHPMailer/src/PHPMailer.php');
            require(''.$server_file_backslash.'plugin/PHPMailer/src/Exception.php');
            require(''.$server_file_backslash.'plugin/PHPMailer/src/SMTP.php');
        }else{
            $error_message[] = "pluginフォルダにPHPMailerがありません<br>ファイル名などをお間違いではありませんか？(PLUGIN_NOT_FOUND)";
        }
    }else{
        $error_message[] = "メール送信プラグインは無効です。(PLUGIN_MUKOU)";
    }

	$serversettings = parse_ini_file("".$server_file_backslash."server/serversettings.ini", true);
	
	$domain = $_SERVER['HTTP_HOST'];
	
	$mail_adds = htmlentities($mailadds);
	$mail_title = htmlentities($mailtitle);
	$x_mailtext = htmlentities($mailtext);
	$mail_text = str_replace( '  ', '<br>', $x_mailtext );
	
	if(!(filter_var($mail_adds, FILTER_VALIDATE_EMAIL))){
		$error_message[] = 'メールアドレスが正しくありません...(MAILADDS_CHECK_DAME)';
	}
	
	if(empty($error_message)){
		if(htmlentities(MAIL_SSL_) == "NONE"){
			$Mail_SSL = false;
		}elseif(htmlentities(MAIL_SSL_) == "SSL"){
			$Mail_SSL = "ssl";
		}elseif(htmlentities(MAIL_SSL_) == "TLS"){
			$Mail_SSL = "tls";
		}
		$s_name = htmlspecialchars($serversettings['serverinfo']['server_name'], ENT_QUOTES, 'UTF-8');
		$now_date = date("Y-m-d H:i:s");
		$logo_path = htmlspecialchars($serversettings["serverinfo"]["server_logo_login"], ENT_QUOTES, 'UTF-8');
        if(empty($logo_path)){
            $logo_path = "https://".$domain."/img/uwuzulogo.svg";
        }
		mb_language('uni');
		mb_internal_encoding('UTF-8');
	
		$mail = new PHPMailer(true);
	
		$mail->CharSet = 'utf-8';
		
		try {
		// SMTPサーバの設定
		$mail->isSMTP();                          // SMTPの使用宣言
		$mail->Host       = htmlentities(MAIL_HOST);   // SMTPサーバーを指定
		$mail->SMTPAuth   = true;                 // SMTP authenticationを有効化
		$mail->Username   = htmlentities(MAIL_USER);   // SMTPサーバーのユーザ名
		$mail->Password   = htmlentities(MAIL_PASS);           // SMTPサーバーのパスワード
		$mail->SMTPSecure = $Mail_SSL;  // 暗号化を有効（tls or ssl）無効の場合はfalse
		$mail->Port       = (int)htmlentities(MAIL_PORT); // TCPポートを指定（tlsの場合は465や587）
	
		$mail->setFrom(htmlentities(MAIL_ADDS), htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8')); // 送信者
		$mail->addAddress(htmlentities($mail_adds));   // 宛先
		$mail->addReplyTo(htmlspecialchars($serversettings["serverinfo"]["server_admin_mailadds"], ENT_QUOTES, 'UTF-8'), 'お問い合わせ'); // 返信先
		$mail->Sender = htmlspecialchars($serversettings["serverinfo"]["server_admin_mailadds"], ENT_QUOTES, 'UTF-8'); // Return-path
		
		// 送信内容設定
		$mail->isHTML(true);
		$mail->Subject = $mail_title;
		$mail->Body    = <<<EOD
		<html lang="ja">
		<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,initial-scale=1">
		
		</head>
		<div class="header" style="background-color:#FFC832; width:100%; height: 48px;">
		<a href="https://{$domain}"><img src="{$logo_path}" style="height: 32px;width:auto;margin-top:8px; margin-left:12px; margin-bottom:8px;"></a>
		</div>
	
		<body style="width: 100%;">
			<main>
				<h1 style="margin-left:12px; margin-right:12px; font-family: 'BIZ UDPGothic', sans-serif; font-weight: bold;font-size: 32px;text-align: left;color: #252525;">{$mail_title}</h1>
				<p style="margin-left:12px; margin-right:12px; font-family: 'BIZ UDPGothic', sans-serif; font-weight: normal;font-size: 16px; line-height: 125%; text-align: left;color: #252525;">{$mail_text}</p>
				<a href="https://{$domain}" style="margin-left:12px; margin-right:12px; font-family: 'BIZ UDPGothic', sans-serif; font-weight: normal;font-size: 16px;text-align: left;color: #4e4428;">{$s_name}</a>
				<p style="margin-right:12px; margin-left:12px; font-family: 'BIZ UDPGothic', sans-serif; font-weight: normal;font-size: 16px;text-align: left;color: #252525;">{$now_date}</p>
			</main>
		</body
	
		</html>
		EOD;
		
		// 送信
		$mail->send();
		
		} catch (Exception $e) {
		// エラーの場合
		$error_message[] = "PHPMailer Error:<br> ".$mail->ErrorInfo."";
		}
	}
	return $error_message;
}
?>