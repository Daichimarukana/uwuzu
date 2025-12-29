<?php

$domain = $_SERVER['HTTP_HOST'];
require(__DIR__ . '/../../../db.php');
require(__DIR__ . "/../../../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

//----------------------------------------------------------------
//--------------------------管理者向けAPI---------------------------
//----------------------------------------------------------------

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

    if(!(empty($post_json["userid"]))) {
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

    session_start();

    if (!empty($pdo)) {
        $AuthData = APIAuth($pdo, $token, "read:admin:users");
        if ($AuthData[0] === true && $AuthData[2]["admin"] == "yes") {
            $userdata = getUserData($pdo, $userid);

            if (empty($userdata)) {
                $response = array(
                    'error_code' => "critical_error_userdata_not_found",
                );
            } else {
                $roles = explode(',', $userdata["role"]);
                if (!(empty($roles))) {
                    foreach ($roles as $roleId) {
                        $Getrole = $pdo->prepare("SELECT roleidname, rolename, roleauth, rolecolor, roleeffect FROM role WHERE roleidname = :role");
                        $Getrole->bindValue(':role', $roleId);
                        $Getrole->execute();
                        $roleData[$roleId] = $Getrole->fetch();

                        if ($roleData[$roleId]['roleeffect'] == '' || $roleData[$roleId]['roleeffect'] == 'none') {
                            $role_view_effect = "none";
                        } elseif ($roleData[$roleId]['roleeffect'] == 'shine') {
                            $role_view_effect = "shine";
                        } elseif ($roleData[$roleId]['roleeffect'] == 'rainbow') {
                            $role_view_effect = "rainbow";
                        } else {
                            $role_view_effect = "none";
                        }

                        $roleinfo = array(
                            "name" => decode_yajirushi(htmlspecialchars_decode($roleData[$roleId]['rolename'])),
                            "color" => decode_yajirushi(htmlspecialchars_decode($roleData[$roleId]['rolecolor'])),
                            "effect" => decode_yajirushi(htmlspecialchars_decode($role_view_effect)),
                            "id" => decode_yajirushi(htmlspecialchars_decode($roleData[$roleId]['roleidname'])),
                        );

                        $role[] = $roleinfo;
                    }
                } else {
                    $role[] = "";
                }

                if (!(empty($userdata["sacinfo"]))) {
                    if ($userdata["sacinfo"] == "bot") {
                        $isBot = true;
                    } else {
                        $isBot = false;
                    }
                } else {
                    $isBot = false;
                }

                if (!(empty($userdata["admin"]))) {
                    if ($userdata["admin"] == "yes") {
                        $isAdmin = true;
                    } else {
                        $isAdmin = false;
                    }
                } else {
                    $isAdmin = false;
                }

                $isPublicOnlineStatus = val_OtherSettings("isPublicOnlineStatus", $userdata["other_settings"]);
                if ($isPublicOnlineStatus === true) {
                    if (!(empty($userdata["last_login_datetime"]))) {
                        $lastLogin = new DateTime($userdata["last_login_datetime"]);
                        $now = new DateTime();

                        $interval = $now->diff($lastLogin);

                        $minutesPast = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

                        $status_datetime = $userdata["last_login_datetime"];

                        if ($minutesPast <= 5) {
                            $online_status = "Online";
                            $real_online_status = "Online";
                        } elseif ($minutesPast <= 15) {
                            $online_status = "Away";
                            $real_online_status = "Away";
                        } else {
                            $online_status = "Offline";
                            $real_online_status = "Offline";
                        }
                    } else {
                        $online_status = "Offline";
                        $real_online_status = "Offline";
                    }
                } else {
                    $online_status = null;
                    if (!(empty($userdata["last_login_datetime"]))) {
                        $lastLogin = new DateTime($userdata["last_login_datetime"]);
                        $now = new DateTime();

                        $interval = $now->diff($lastLogin);

                        $minutesPast = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

                        $status_datetime = $userdata["last_login_datetime"];

                        if ($minutesPast <= 5) {
                            $real_online_status = "Online";
                        } elseif ($minutesPast <= 15) {
                            $real_online_status = "Away";
                        } else {
                            $real_online_status = "Offline";
                        }
                    } else {
                        $real_online_status = "Offline";
                    }
                }

                $followee = getFolloweeList($pdo, $userdata["userid"]);
                if ($followee === false) {
                    $followee = array();
                }
                $follower = getFollowerList($pdo, $userdata["userid"]);
                if ($follower === false) {
                    $follower = array();
                }

                $userdata["follow_cnt"] = (int)count($followee);
                $userdata["follower_cnt"] = (int)count($follower);

                $allueuse = $pdo->prepare("SELECT account FROM ueuse WHERE account = :userid");
                $allueuse->bindValue(':userid', $userdata["userid"]);
                $allueuse->execute();
                $All_ueuse = $allueuse->rowCount();

                if (!(empty($userdata["encryption_ivkey"]))) {
                    $view_mailadds = DecryptionUseEncrKey($userdata["mailadds"], GenUserEnckey($userdata["datetime"]), $userdata["encryption_ivkey"]);
                    $view_ip_addr = DecryptionUseEncrKey($userdata["last_ip"], GenUserEnckey($userdata["datetime"]), $userdata["encryption_ivkey"]);
                } else {
                    $view_mailadds = $userdata["mailadds"];
                    $view_ip_addr = $userdata["last_ip"];
                }

                if (!empty($userdata["authcode"])) {
                    $is_2fa_configured = true;
                } else {
                    $is_2fa_configured = false;
                }

                $response = array(
                    'success' => true,
                    'username' => decode_yajirushi(htmlspecialchars_decode($userdata["username"])),
                    'userid' => decode_yajirushi(htmlspecialchars_decode($userdata["userid"])),
                    'profile' => decode_yajirushi(htmlspecialchars_decode($userdata["profile"])),
                    'user_icon' => decode_yajirushi(htmlspecialchars_decode(localcloudURLtoAPI(localcloudURL($userdata["iconname"])))),
                    'user_header' => decode_yajirushi(htmlspecialchars_decode(localcloudURLtoAPI(localcloudURL($userdata["headname"])))),
                    'registered_date' => decode_yajirushi(htmlspecialchars_decode($userdata["datetime"])),
                    'followee' => $followee,
                    'followee_cnt' => $userdata["follow_cnt"],
                    'follower' => $follower,
                    'follower_cnt' => $userdata["follower_cnt"],
                    'ueuse_cnt' => $All_ueuse,
                    'isBot' => $isBot,
                    'isAdmin' => $isAdmin,
                    'role' => $role,
                    'online_status' => $online_status,
                    'real_online_status' => $real_online_status,
                    'last_login_datetime' => $userdata["last_login_datetime"],
                    'last_login_ipaddress' => $view_ip_addr,
                    'mailaddress' => $view_mailadds,
                    'is_2fa_configured' => $is_2fa_configured,
                    'language' => "ja-JP",
                );
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
