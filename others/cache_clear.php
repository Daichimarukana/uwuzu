<?php
require('../db.php');
require("../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

header( 'Cache-Control: no-store, no-cache, must-revalidate' );
header( 'Cache-Control: post-check=0, pre-check=0', FALSE );
header( 'Pragma: no-cache' );
if(isset($_SERVER['HTTP_REFERER'])){
    $back = safetext($_SERVER['HTTP_REFERER']);
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
<link rel="manifest" href="../manifest/manifest.json" />
<link rel="stylesheet" href="../css/home.css?v=<?php echo createUniqId();?>">
<title>キャッシュクリア中</title>
</head>
<script>
window.addEventListener('load', function(){
    if ("serviceWorker" in navigator) {
        navigator.serviceWorker.ready.then(function(registration) {
            if (registration.active) {
                registration.active.postMessage({ action: 'clearCache' });
            }
        });
    }

    window.location.href = "<?php echo $back?>";
});
</script>