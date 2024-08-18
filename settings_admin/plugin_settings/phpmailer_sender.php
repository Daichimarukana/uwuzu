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
			<!DOCTYPE HTML>
			<html xmlns="http://www.w3.org/1999/xhtml" lang="ja">
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
					<meta name="viewport" content="width=device-width" />
					<style>
						body{
							margin: 0px;
							background-color: #F5F5F5;
						}
						table{
							max-width: 640px;
							width: 640px;
							margin: 32px auto;
							background-color: #FFFFFF;
							border-radius: 10px;
							border: solid 1px #EEEEEE;
							overflow: hidden;
						}
						table .headtop{
							background-color: #FFC832;
							height: 64px;
							font-size: 32px;
							font-family: 'BIZ UDPGothic', sans-serif;
							font-weight: bold;
							margin: 0px;
							width: 100%;
							padding-left: 16px;
							color: #FFFFFF;
						}
						table .headtop img{
							line-height: 48px;
							height: 48px;
							margin: 8px 0px;
							width: auto;
						}
						table .inner{
							padding: 16px;
						}
						table h1{
							font-size: 32px;
							font-family: 'BIZ UDPGothic', sans-serif;
							font-weight: bold;
							margin: 8px auto;
							color: #252525;
						}
						table p{
							font-family: 'BIZ UDPGothic', sans-serif;
							font-size: 16px;
							margin: 8px auto;
							color: #252525;
						}
						table .footbtm{
							background-color: #252525;
							height: 48px;
							line-height: 48px;
							font-size: 16px;
							font-weight: normal;
							margin: 0px;
							width: 100%;
							padding-left: 16px;
							display: flex;
						}
						table .footbtm p{
							color: #F7F7F7;
							font-family: 'BIZ UDPGothic', sans-serif;
							margin: 0px 8px 0px 0px;
						}
						table .footbtm a{
							margin: 0px 8px 0px 0px;
							display: block;
							font-family: 'BIZ UDPGothic', sans-serif;
							text-decoration: none;
							color: #FFC832;
						}
					</style>
				</head>
				<body>
					<table cellpadding="0" border="0" cellspacing="0">
						<tr>
							<td class="headtop">
								<img src="{$logo_path}">
							</td>
						</tr>
						<tr>
							<td class="inner">
								<h1>{$mail_title}</h1>
								<p>{$mail_text}</p>
							</td>
						</tr>
						<tr>
							<td class="footbtm">
								<p>{$now_date}</p>
								<a href="https://{$domain}">{$s_name}</a>
							</td>
						</tr>
					</table>
				</body>
			</html>

			EOD;
			
			// 送信
			$mail->send();
			
		} catch (Exception $e) {
			// エラーの場合
			$error_message[] = "PHPMailer Error:<br> ".$mail->ErrorInfo."";
			return $error_message;
		}
	}else{
		return $error_message;
	}
}
?>