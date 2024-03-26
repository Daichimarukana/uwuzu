<noscript>
	<div class="noscript_modal">
        <div class="inner">
            <div class="oops_icon">⚠️</div>
            <h1>JavaScriptを有効にしてください</h1>
            <p>uwuzuを開いてくださりありがとうございます。<br>
            申し訳ございませんがuwuzuの動作にはJavaScriptが必要です。<br>
            JavaScriptを有効にして再読み込みをしてください。</p>
            <p>JavaScriptを無効にしている状態での使用はできません。</p>
            <p>詳細は下記のリンクよりご確認ください。</p>
            
            <a href="https://uwuzu.com/docs/uwuzusupport" class="infobtn">詳細</a>
            <div class="p2">JS_BLOCKED_ERROR</div>
            
            <div class="center_text">
                <p><?php echo htmlentities($serversettings["serverinfo"]["server_name"]);?></p>
                <p><?php echo $domain;?></p>
                <p><?php echo htmlentities($uwuzuinfo[0]);?><br>Version <?php echo htmlentities($uwuzuinfo[1]);?></p>
            </div>
        </div>
	</div>
</noscript>