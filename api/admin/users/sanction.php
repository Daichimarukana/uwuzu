<?php

$domain = $_SERVER['HTTP_HOST'];
require_once(__DIR__ . '/../../../db.php');
require_once(__DIR__ . "/../../../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

//----------------------------------------------------------------
//--------------------------管理者向けAPI---------------------------
//----------------------------------------------------------------

$serversettings_file = __DIR__ . "/../../../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);
//phpmailer--------------------------------------------
require_once(__DIR__ . '/../../../settings_admin/plugin_settings/phpmailer_settings.php');
require_once(__DIR__ . '/../../../settings_admin/plugin_settings/phpmailer_sender.php');
//------------------------------------------------------
//2fa---------------------------------------------------
require_once(__DIR__ . '/../../../authcode/GoogleAuthenticator.php');
//------------------------------------------------------

$pdo = null;
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error_code' => 'method_not_allowed',
        'success' => false
    ]);
    exit;
}

$Get_Post_Json = file_get_contents("php://input");
if ((!(empty($Get_Post_Json)))) {
    //トークン取得
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
    
    if ($token == "") {
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
            'success' => false
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!(empty($post_json["userid"]))) {
        $userid = safetext($post_json["userid"]);
    } else {
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
            'success' => false
        );

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!(empty($post_json["type"]))) {
        $type = safetext($post_json["type"]);
    } else {
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
            'success' => false
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    session_start();

    if (!empty($pdo)) {
        $AuthData = APIAuth($pdo, $token, "write:admin:user-sanction");
        if ($AuthData[0] === true && $AuthData[2]["admin"] == "yes") {
            $userdata = getUserData($pdo, $userid);

            if (empty($userdata)) {
                $response = array(
                    'error_code' => "critical_error_userdata_not_found",
                );
            } else {
                if (!(empty($userdata["encryption_ivkey"]))) {
                    $view_mailadds = DecryptionUseEncrKey($userdata["mailadds"], GenUserEnckey($userdata["datetime"]), $userdata["encryption_ivkey"]);
                    $view_ip_addr = DecryptionUseEncrKey($userdata["last_ip"], GenUserEnckey($userdata["datetime"]), $userdata["encryption_ivkey"]);
                } else {
                    $view_mailadds = $userdata["mailadds"];
                    $view_ip_addr = $userdata["last_ip"];
                }

                if ($type == "notification") {
                    if (!(empty($post_json["notification_title"]))) {
                        $notice_title = safetext($post_json["notification_title"]);
                    } else {
                        $err = "input_not_found";
                    }

                    if (!(empty($post_json["notification_message"]))) {
                        $notice_msg = safetext($post_json["notification_message"]);
                    } else {
                        $err = "input_not_found";
                    }

                    if (empty($notice_title)) {
                        $err = "input_not_found";
                    } elseif (mb_strlen($notice_title) > 512) {
                        $err = "content_to_512_characters";
                    }
                    if (empty($notice_msg)) {
                        $err = "input_not_found";
                    } elseif (mb_strlen($notice_msg) > 16777216) {
                        $err = "content_to_16777216_characters";
                    }
                    if (empty($err)) {
                        $url = safetext("/rule/serverabout");
                        $response = send_notification($userdata['userid'], "uwuzu-fromsys", $notice_title, $notice_msg, $url, "system");
                        if ($response == true) {
                            actionLog($AuthData[2]["userid"], "info", "admin-user-sanction-api-send_notification", $userdata['userid'], $userdata['userid'] . "さんに" . $AuthData[2]["userid"] . "さんが通知を送信しました。\n" . $notice_msg, 0);
                            $response = array(
                                'success' => true,
                                'userid' => $userdata['userid']
                            );
                        } else {
                            actionLog($AuthData[2]["userid"], "error", "admin-user-sanction-api-send_notification", $userdata['userid'], $userdata['userid'] . "さんに" . $AuthData[2]["userid"] . "さんが通知を送信できませんでした。\n" . $notice_msg, 4);
                            $response = array(
                                'error_code' => "could_not_complete",
                                'success' => false
                            );
                        }
                    } else {
                        $response = array(
                            'error_code' => $err,
                            'success' => false
                        );
                    }
                } elseif ($type == "frozen") {
                    if(!($userdata["role"] === "ice")){
                        if (!(empty($post_json["notification_message"]))) {
                            $notice_msg = safetext($post_json["notification_message"]);
                        } else {
                            $err = "input_not_found";
                        }

                        // --- バリデーション ---
                        if (empty($notice_msg)) {
                            $err = "input_not_found";
                        } elseif (mb_strlen($notice_msg) > 16777216) {
                            $err = "content_to_16777216_characters";
                        }

                        if (empty($err)) {
                            $touserid = $userdata['userid'];
                            $newrole = "ice";
                            $newtoken = "ice";
                            $newadmin = "none";

                            $pdo->beginTransaction();
                            try {
                                $stmt = $pdo->prepare("UPDATE account SET role = :role, token = :newtoken, admin = :newadmin WHERE userid = :userid");
                                $stmt->bindValue(':role', $newrole, PDO::PARAM_STR);
                                $stmt->bindValue(':newtoken', $newtoken, PDO::PARAM_STR);
                                $stmt->bindValue(':newadmin', $newadmin, PDO::PARAM_STR);
                                $stmt->bindValue(':userid', $touserid, PDO::PARAM_STR);
                                $stmt->execute();
                                $pdo->commit();
                                $account_updated = true;
                            } catch (Exception $e) {
                                $pdo->rollBack();
                                $account_updated = false;
                                $err_msg = $e->getMessage();
                            }

                            if ($account_updated) {
                                $notice_title = "🧊お使いのアカウントは凍結されました。🧊";
                                $full_msg = "サービス管理者からのメッセージは以下のものです。\n" . $notice_msg . "\n異議申し立てする場合は連絡用メールに異議申し立てをする旨を記載し送信をしてください。";
                                $url = safetext("/rule/serverabout");

                                $notif_res = send_notification($touserid, "uwuzu-fromsys", $notice_title, $full_msg, $url, "system");

                                if (false !== strpos($userdata["mail_settings"], 'important')) {
                                    if (!empty(MAIL_CHKS) && MAIL_CHKS == "true") {
                                        if (!empty($view_mailadds) && filter_var($view_mailadds, FILTER_VALIDATE_EMAIL)) {
                                            $mail_title = "お使いの" . safetext($serversettings["serverinfo"]["server_name"]) . "アカウントは凍結されました";
                                            $mail_text = "".$userdata["username"]."(".$userdata["userid"].")さん    いつもuwuzuをご利用いただきありがとうございます。  ご利用のアカウント(".$userdata["userid"].")が".safetext($serversettings["serverinfo"]["server_name"])."管理者により凍結されたためお知らせいたします。  サービス管理者からのメッセージは以下のものです。    ". safetext($notice_msg) ."    異議申し立てする場合は[".safetext($serversettings["serverinfo"]["server_admin_mailadds"])."]まで異議申し立てをする旨を記載し送信をしてください。";
                                            $sendmail_error_message[] = send_html_mail($view_mailadds, $mail_title, $mail_text, "../../../");
                                            if(!(empty($sendmail_error_message))){
                                                actionLog($AuthData[2]["userid"], "error", "admin-user-sanction-api-frozen", $userdata['userid'], $sendmail_error_message, 3);
                                            }
                                        }
                                    }
                                }

                                actionLog($AuthData[2]["userid"], "info", "admin-user-sanction-api-frozen", $touserid, $touserid . "さんを" . $AuthData[2]["userid"] . "さんが凍結しました。\n理由: " . $notice_msg, 0);
                                $response = array(
                                    'success' => true,
                                    'userid' => $touserid
                                );
                            } else {
                                actionLog($AuthData[2]["userid"], "error", "admin-user-sanction-api-frozen", $touserid, $err_msg, 4);
                                $response = array(
                                    'error_code' => "could_not_complete",
                                    'success' => false
                                );
                            }
                        } else {
                            $response = array(
                                'error_code' => $err,
                                'success' => false
                            );
                        }
                    }else{
                        $response = array(
                            'error_code' => "already_been_completed",
                            'success' => false
                        );
                    }
                } elseif ($type == "unfrozen") {
                    if($userdata["role"] === "ice"){
                        $touserid = $userdata['userid'];
                        $newrole = "user";
                        $newtoken = "";
                        $newadmin = "none";

                        $pdo->beginTransaction();
                        try {
                            $stmt = $pdo->prepare("UPDATE account SET role = :role, token = :newtoken, admin = :newadmin WHERE userid = :userid");
                            $stmt->bindValue(':role', $newrole, PDO::PARAM_STR);
                            $stmt->bindValue(':newtoken', $newtoken, PDO::PARAM_STR);
                            $stmt->bindValue(':newadmin', $newadmin, PDO::PARAM_STR);
                            $stmt->bindValue(':userid', $touserid, PDO::PARAM_STR);
                            $stmt->execute();
                            
                            $pdo->commit();
                            $account_updated = true;
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $account_updated = false;
                            $err_msg = $e->getMessage();
                        }

                        if ($account_updated) {
                            $notice_title = "🫗お使いのアカウントが解凍されました！🫗";
                            $full_msg = "サービス管理者によりお使いのアカウントは解凍されました！\n今まで通りご利用いただけます。";
                            $url = safetext("/home");

                            $notif_res = send_notification($touserid, "uwuzu-fromsys", $notice_title, $full_msg, $url, "system");

                            if (false !== strpos($userdata["mail_settings"], 'important')) {
                                if (!empty(MAIL_CHKS) && MAIL_CHKS == "true") {
                                    if (!empty($view_mailadds) && filter_var($view_mailadds, FILTER_VALIDATE_EMAIL)) {
                                        $mail_title = "お使いの" . safetext($serversettings["serverinfo"]["server_name"]) . "アカウントは解凍されました！";
                                        $mail_text = "".$userdata["username"]."(".$userdata["userid"].")さん    いつもuwuzuをご利用いただきありがとうございます。  ご利用のアカウント(".$userdata["userid"].")が解凍されたためお知らせいたします。  今後、ご利用のuwuzuアカウントは今まで通りご利用いただけます。";
                                        
                                        $sendmail_error_message[] = send_html_mail($view_mailadds, $mail_title, $mail_text, "../../../");
                                        if(!(empty($sendmail_error_message))){
                                            actionLog($AuthData[2]["userid"], "error", "admin-user-sanction-api-unfrozen", $userdata['userid'], $sendmail_error_message, 3);
                                        }
                                    }
                                }
                            }

                            actionLog($AuthData[2]["userid"], "info", "admin-user-sanction-api-unfrozen", $touserid, $touserid . "さんを" . $AuthData[2]["userid"] . "さんが解凍しました", 0);
                            
                            $response = array(
                                'success' => true,
                                'userid' => $touserid
                            );
                        } else {
                            actionLog($AuthData[2]["userid"], "error", "admin-user-sanction-api-unfrozen", $touserid, $err_msg, 4);
                            $response = array(
                                'error_code' => "could_not_complete",
                                'success' => false
                            );
                        }
                    }else{
                        $response = array(
                            'error_code' => "already_been_completed",
                            'success' => false
                        );
                    }
                } elseif ($type == "ban") {
                    if($userdata["role"] === "ice"){
                        if (!(empty($post_json["really"]))) {
                            $really = safetext($post_json["really"]);
                        } else {
                            $err = "input_not_found";
                        }
                        
                        if (empty($really)) {
                            $err = "input_not_found";
                        }else{
                            if(!(empty($AuthData[2]["authcode"]))){
                                if(!(empty($AuthData[2]["encryption_ivkey"])) && (!(mb_strlen($AuthData[2]["authcode"]) === 16))){
                                    $private_authcode = DecryptionUseEncrKey($AuthData[2]["authcode"], GenUserEnckey($AuthData[2]["datetime"]), $AuthData[2]["encryption_ivkey"]);
                                }else{
                                    $private_authcode = $AuthData[2]["authcode"];
                                }

                                $chkauthcode = new PHPGangsta_GoogleAuthenticator();
                                $checkResult = $chkauthcode->verifyCode($private_authcode, $really, 2);
                                if ($checkResult == false) {
                                    $err = "input_not_found";
                                }
                            }else{
                                if(!($really === "yes_i_will_delete_".safetext($userdata["userid"]))){
                                    $err = "input_not_found";
                                }
                            }
                        }

                        if (empty($err)) {
                            try{
                                $res = addJob($pdo, $userdata['userid'], "deleteUser", "stop_account");
                            
                                if ($res) {
                                    actionLog($AuthData[2]["userid"], "info", "admin-user-sanction-api-ban", $userdata['userid'], $AuthData[2]["userid"]."さんが".$userdata['userid']."さんをBANしました", 4);
                                    $response = array(
                                        'success' => true,
                                        'userid' => $userdata['userid']
                                    );
                                    //BAN通知メール
                                    if(false !== strpos($userdata["mail_settings"], 'important')) {
                                        if(!empty(MAIL_CHKS)){
                                            if(MAIL_CHKS == "true"){
                                                if( !empty($view_mailadds) ){
                                                    if(filter_var($view_mailadds, FILTER_VALIDATE_EMAIL)){
                                                        $mail_title = "お使いの".safetext($serversettings["serverinfo"]["server_name"])."アカウントはBANされました";
                                                        $mail_text = "".$userdata["username"]."(".$userdata["userid"].")さん    いつもuwuzuをご利用いただきありがとうございます。  この度、ご利用のアカウント(".$userdata["userid"].")が".safetext($serversettings["serverinfo"]["server_name"])."管理者によりBAN(削除)されたためお知らせいたします。  今後は今までご利用いただいた".safetext($serversettings["serverinfo"]["server_name"])."アカウントは利用できません。  ".safetext($serversettings["serverinfo"]["server_name"])."サーバー上から今までご利用いただいていたアカウントの情報は削除されたためログインなどもできません。    ご理解とご協力のほどよろしくお願いします。";

                                                        $error_message[] = send_html_mail($view_mailadds,$mail_title,$mail_text,"../../../");
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    //------------
                                } else {
                                    $error_message[] = 'アカウント削除に失敗しました。(ACCOUNT_DELETE_DAME)';
                                    actionLog($AuthData[2]["userid"], "error", "admin-user-sanction-api-ban", $userdata['userid'], $error_message, 4);
                                }
                            } catch (Exception $e) {
                                $pdo->rollBack();
                                actionLog($AuthData[2]["userid"], "error", "admin-user-sanction-api-ban", $userdata['userid'], $e, 4);
                            }
                        }else{
                            $response = array(
                                'error_code' => $err,
                                'success' => false
                            );
                        }
                    }else{
                        $response = array(
                            'error_code' => "user_not_frozen_cant_be_banned",
                            'success' => false
                        );
                    }
                } else {
                    $response = array(
                        'error_code' => "input_not_found",
                        'success' => false
                    );
                }
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            $err = $AuthData[1];
            $response = array(
                'error_code' => $err,
                'success' => false
            );

            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
    }
} else {
    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
        'success' => false
    );

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
