<?php

$domain = $_SERVER['HTTP_HOST'];
require('../../db.php');
require("../../function/function.php");

header("Content-Type: application/json");
header("charset=utf-8");
header("Access-Control-Allow-Origin: *");

function decode_yajirushi($postText){
    $postText = str_replace('&larr;', '←', $postText);
    $postText = str_replace('&darr;', '↓', $postText);
    $postText = str_replace('&uarr;', '↑', $postText);
    $postText = str_replace('&rarr;', '→', $postText);
    return $postText;
}


$pdo = null;
try {
    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
} catch(PDOException $e) {
    // 接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}

$Get_Post_Json = file_get_contents("php://input");
if(isset($_GET['token']) || (!(empty($Get_Post_Json)))) { 

    //トークン取得
    if(!(empty($_GET['token']))){
        $token = safetext($_GET['token']);
    }else{
        $post_json = json_decode($Get_Post_Json, true);
        if(isset($post_json["token"])){
            $token = safetext($post_json["token"]);
        }else{
            $err = "input_not_found";
            $response = array(
                'error_code' => $err,
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    if($token == ""){
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if(!(empty($_GET['userid']))){
        $userid = $_GET['userid'];
    }elseif(!(empty($post_json["userid"]))){
        $userid = $post_json["userid"];
    }else{
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
        );
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    session_start();

    if( !empty($pdo) ) {
        $userQuery = $pdo->prepare("SELECT username, userid, role FROM account WHERE token = :token");
        $userQuery->bindValue(':token', $token);
        $userQuery->execute();
        $userData = $userQuery->fetch();

        if(empty($userData["userid"])){
            $err = "token_invalid";
            $response = array(
                'error_code' => $err,
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }elseif($userData["role"] === "ice"){
            $err = "this_account_has_been_frozen";
            $response = array(
                'error_code' => $err,
            );
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }else{
            $DataQuery = $pdo->prepare("SELECT username,userid,profile,datetime,follow,follower,iconname,headname,role,sacinfo,admin FROM account WHERE userid = :userid");
            $DataQuery->bindValue(':userid', $userid);
            $DataQuery->execute();
            $userdata = $DataQuery->fetch();
            
            if (empty($userdata)){
                $response = array(
                    'error_code' => "critical_error_userdata_not_found",
                );
            }else{
                $roles = explode(',', $userdata["role"]);
                if(!(empty($roles))){
                    foreach ($roles as $roleId) {
                        $Getrole = $pdo->prepare("SELECT roleidname, rolename, roleauth, rolecolor, roleeffect FROM role WHERE roleidname = :role");
                        $Getrole->bindValue(':role', $roleId);
                        $Getrole->execute();
                        $roleData[$roleId] = $Getrole->fetch();

                        if($roleData[$roleId]['roleeffect'] == '' || $roleData[$roleId]['roleeffect'] == 'none'){
							$role_view_effect = "none";
						}elseif($roleData[$roleId]['roleeffect'] == 'shine'){
							$role_view_effect = "shine";
						}elseif($roleData[$roleId]['roleeffect'] == 'rainbow'){
							$role_view_effect = "rainbow";
						}else{
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
                }else{
                    $role[] = "";
                }

                if(!(empty($userdata["sacinfo"]))){
                    if($userdata["sacinfo"] == "bot"){
                        $isBot = true;
                    }else{
                        $isBot = false;
                    }
                }else{
                    $isBot = false;
                }

                if(!(empty($userdata["admin"]))){
                    if($userdata["admin"] == "yes"){
                        $isAdmin = true;
                    }else{
                        $isAdmin = false;
                    }
                }else{
                    $isAdmin = false;
                }
                if(!(empty($userdata["follow"]))){
                    $followee = preg_split("/,/", decode_yajirushi(htmlspecialchars_decode($userdata["follow"])));
                    array_shift($followee);
                }else{
                    $followee = array();
                }
                if(!(empty($userdata["follower"]))){
                    $follower = preg_split("/,/", decode_yajirushi(htmlspecialchars_decode($userdata["follower"])));
                    array_shift($follower);
                }else{
                    $follower = array();
                }
                
                $followcnts = explode(',', $userdata["follow"]);
                $userdata["follow_cnt"] = (int)count($followcnts)-1;

                $followercnts = explode(',', $userdata["follower"]);
                $userdata["follower_cnt"] = (int)count($followercnts)-1;

                $allueuse = $pdo->prepare("SELECT account FROM ueuse WHERE account = :userid");
                $allueuse->bindValue(':userid', $userdata["userid"]);
                $allueuse->execute();
                $All_ueuse = $allueuse->rowCount(); 

                $response = array(
                    'username' => decode_yajirushi(htmlspecialchars_decode($userdata["username"])),
                    'userid' => decode_yajirushi(htmlspecialchars_decode($userdata["userid"])),
                    'profile' => decode_yajirushi(htmlspecialchars_decode($userdata["profile"])),
                    'user_icon' => decode_yajirushi(htmlspecialchars_decode("https://".$domain."/".$userdata["iconname"])),
                    'user_header' => decode_yajirushi(htmlspecialchars_decode("https://".$domain."/".$userdata["headname"])),
                    'registered_date' => decode_yajirushi(htmlspecialchars_decode($userdata["datetime"])),
                    'followee' => $followee,
                    'followee_cnt' => $userdata["follow_cnt"],
                    'follower' => $follower,
                    'follower_cnt' => $userdata["follower_cnt"],
                    'ueuse_cnt' => $All_ueuse,
                    'isBot' => $isBot,
                    'isAdmin' => $isAdmin,
                    'role' => $role,
                    'language' => "ja-JP",
                );
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
    }
}else{
    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
    );
     
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>