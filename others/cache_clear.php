<?php
function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}

require("../function/function.php");
header( 'Cache-Control: no-store, no-cache, must-revalidate' );
header( 'Cache-Control: post-check=0, pre-check=0', FALSE );
header( 'Pragma: no-cache' );
if(isset($_SERVER['HTTP_REFERER'])){
    $back = htmlentities($_SERVER['HTTP_REFERER']);
}else{
    $back = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . "/index.php";
}
?>
<head>
<meta charset="utf-8">
<script src="../js/jquery-min.js?v=<?php echo createUniqId();?>"></script>
<script src="../js/unsupported.js?v=<?php echo createUniqId();?>"></script>
<script src="../js/console_notice.js?v=<?php echo createUniqId();?>"></script>
<script src="../js/nsfw_event.js?v=<?php echo createUniqId();?>"></script>
<link rel="manifest" href="../manifest/manifest.json?v=<?php echo createUniqId();?>" />
<link rel="stylesheet" href="../css/home.css?v=<?php echo createUniqId();?>">
<title>キャッシュクリア中</title>
</head>
<script>
window.addEventListener('load', function(){
    window.location.href = "<?php echo $back?>";
});
</script>