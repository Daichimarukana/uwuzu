<?php

$domain = $_SERVER['HTTP_HOST'];
require(__DIR__ . '/../../db.php');
require(__DIR__ . "/../../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");




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
                'success' => false
            );
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    if($token == ""){
        $err = "input_not_found";
        $response = array(
            'error_code' => $err,
            'success' => false
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
            'success' => false
        );
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    session_start();

    if( !empty($pdo) ) {
        $AuthData = APIAuth($pdo, $token, "read:users");
        if($AuthData[0] === true){
            $userdata = getUserData($pdo, $userid);
            
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
                
                $followee = getFolloweeList($pdo, $userdata["userid"]);
                if($followee === false){
                    $followee = array();
                }
                $follower = getFollowerList($pdo, $userdata["userid"]);
                if($follower === false){
                    $follower = array();
                }
                
                $userdata["follow_cnt"] = (int)count($followee);
                $userdata["follower_cnt"] = (int)count($follower);

                $allueuse = $pdo->prepare("SELECT account FROM ueuse WHERE account = :userid");
                $allueuse->bindValue(':userid', $userdata["userid"]);
                $allueuse->execute();
                $All_ueuse = $allueuse->rowCount(); 

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
                    'language' => "ja-JP",
                );
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }else{
            $err = $AuthData[1];
            $response = array(
                'error_code' => $err,
                'success' => false
            );
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
    }
}else{
    $err = "input_not_found";
    $response = array(
        'error_code' => $err,
        'success' => false
    );
     
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>