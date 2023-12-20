<div class="rightbox">
    <?php 
    require('../notice/notice.php');

    $serversettings_file = "../server/serversettings.ini";
    $serversettings = parse_ini_file($serversettings_file, true);

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
    function replaceURLsWithLinks_forRightbox($postText) {
        $postText = str_replace('&#039;', '\'', $postText);

		// URLを正規表現を使って検出
		$pattern = '/(https:\/\/[^\s<>\[\]\'"]+)/';  // 改良された正規表現
		preg_match_all($pattern, $postText, $matches);

		// 検出したURLごとに処理を行う
		foreach ($matches[0] as $url) {
			// ドメイン部分を抽出
			$parsedUrl = parse_url($url);
			if (!isset($parsedUrl['path'])) {
				$parsedUrl['path'] = '';
			}
			if (!isset($parsedUrl['query'])) {
				$parsedUrl['query'] = '';
			}

			$nochk_domain = $parsedUrl['host'].$parsedUrl['path'].$parsedUrl['query'];

			if(strlen($nochk_domain) > 47){
				$domain = mb_substr($nochk_domain, 0, 48, "UTF-8")."...";
			}else{
				$domain = $nochk_domain;
			}

			// 不要な文字を削除してaタグを生成
			$urlWithoutSpaces = preg_replace('/\s+/', '', $url);
			$link = "<a href='$urlWithoutSpaces' target='_blank' title='$urlWithoutSpaces'>$domain</a>";

			// URLをドメインのみを表示するaタグで置き換え
			$postText = preg_replace('/' . preg_quote($url, '/') . '/', $link, $postText);
		}

		return $postText;
    }
    ?>
    <div class="noticearea">
    <h1>お知らせ</h1>
    <?php if(empty($notices)){?>
        <div class="noticebox">
            <h4>なし</h4>
            <p>おしらせないよ～</p>
            <div class="makeup"><p>編集者 : <a href="/rule/uwuzuabout">@uwuzu</a></p></div>
            <div class="time"><p>いま</p></div>
        </div>
    <?php }else{?> 
    <?php foreach ($notices as $value) {?>
            <div class="noticebox">
                <h4><?php echo $value['title'];?></h4>
                <p><?php echo replaceURLsWithLinks_forRightbox(nl2br($value['note']));?></p>
                <div class="makeup"><p>編集者 : <a href="/@<?php echo $value['account'];?>">@<?php echo $value['account'];?></a></p></div>
                <div class="time"><p><?php echo date('Y年m月d日 H:i', strtotime($value['datetime']));?></p></div>
            </div>
        <?php }}?>
    </div>
    <div class="btmbox">
        <h1>サーバー情報</h1>
        <h2>Server</h2>
        <h3><?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8');?></h3>
        <p><?php echo $domain;?></p>
        <a href="/rule/terms">利用規約</a><a href="/rule/privacypolicy">プライバシーポリシー</a><a href="/rule/serverabout">詳細</a>
        <h2>Software</h2>
        <h3><?php echo $uwuzuinfo[0]?></h3>
        <p>Version : <?php echo $uwuzuinfo[1]?></p>
        <p>Developer : <?php echo $uwuzuinfo[3]?></p>
        <a href="/rule/uwuzuabout">もっと詳しく！</a><a href="/rule/releasenotes">リリースノート</a>
    </div>
</div>