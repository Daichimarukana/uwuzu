<div class="rightbox">
    <?php 
    require('../notice/notice.php');

    $servernamefile = "../server/servername.txt";

    //-------------------------------------
    
    $domain = $_SERVER['HTTP_HOST'];
    
    //------------------------
    
    $softwarefile = "../server/uwuzuinfo.txt";
    $softwaredata = file_get_contents($softwarefile);

    $softwaredata = explode( "\n", $softwaredata );
    $cnt = count( $softwaredata );
    for( $i=0;$i<$cnt;$i++ ){
        $uwuzuinfo[$i] = ($softwaredata[$i]);
    }
    ?>
    <h1>お知らせ</h1>
    <div class="noticearea">
        <?php foreach ($notices as $valuen) {?>
            <div class="noticebox">
                <h4><?php echo $valuen['title'];?></h4>
                <p><?php echo nl2br($valuen['note']);?></p>
                <div class="makeup"><p>編集者 : <a href="/<?php echo $uneinoticeaccount?>"><?php echo htmlspecialchars($uneinoticeaccount)?></a></p></div>
                <div class="time"><p><?php echo date('Y年m月d日 H:i', strtotime($valuen['datetime']));?></p></div>
            </div>
        <?php }?>
    </div>
    <h1>サーバー情報</h1>
    <div class="btmbox">
        <h2>Server</h2>
        <h3><?php echo file_get_contents($servernamefile);?></h3>
        <p><?php echo $domain;?></p>
        <a href="/rule/terms">利用規約</a><a href="/rule/privacypolicy">プライバシーポリシー</a>
        <h2>Software</h2>
        <h3><?php echo $uwuzuinfo[0]?></h3>
        <p>Version : <?php echo $uwuzuinfo[1]?></p>
        <p>Developer : <?php echo $uwuzuinfo[3]?></p>
        <a href="/rule/uwuzuabout">もっと詳しく！</a><a href="/rule/releasenotes">リリースノート</a>
    </div>
</div>