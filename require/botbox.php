<?php ?>
<div class="botbox">
    <div class="lbtnzone">
        <a href="/home" class="btmbutton"><svg><use xlink:href="../img/sysimage/menuicon/home.svg#home"></use></svg></a>
        <?php if($notificationcount > 0){?>
            <a href="/notification" class="btmbutton"><svg><use xlink:href="../img/sysimage/menuicon/notification2.svg#notification"></use></svg></a>
        <?php }else{?>
            <a href="/notification" class="btmbutton"><svg><use xlink:href="../img/sysimage/menuicon/notification.svg#notification"></use></svg></a>
        <?php }?>
        <a href="/search" class="btmbutton"><svg><use xlink:href="../img/sysimage/menuicon/search.svg#search"></use></svg></a>
        <button id="openmenu" class="btmbutton"><svg><use xlink:href="../img/sysimage/menuicon/menu.svg#menu"></use></svg></button>
    </div>
</div>

<div id="bot_box_Modal" class="modal">
    <div class="modal-content">
        <p>メニュー</p>
        <div class="bot_menu_area">
            <a href="/emoji" class="menubutton"><svg><use xlink:href="../img/sysimage/menuicon/emoji.svg#emoji"></use></svg><div>絵文字</div></a>
            <a href="/@<?php echo $userid; ?>" class="menubutton"><svg><use xlink:href="../img/sysimage/menuicon/profile.svg#profile"></use></svg><div>プロフィール</div></a>
            <a href="/settings" class="menubutton"><svg><use xlink:href="../img/sysimage/menuicon/settings.svg#settings"></use></svg><div>設定</div></a>
            <a href="/others" class="menubutton"><svg><use xlink:href="../img/sysimage/menuicon/others.svg#others"></use></svg><div>その他</div></a>
            <a href="/rule/terms" class="menubutton"><svg><use xlink:href="../img/sysimage/menuicon/terms.svg#terms"></use></svg><div>利用規約</div></a>
            <a href="/rule/privacypolicy" class="menubutton"><svg><use xlink:href="../img/sysimage/menuicon/privacypolicy.svg#privacypolicy"></use></svg><div>プライバシー<br>ポリシー</div></a>

            <?php if($res["admin"] === "yes"){?>
                <a href="/emoji/addemoji" class="menubutton"><svg><use xlink:href="../img/sysimage/menuicon/addemoji.svg#addemoji"></use></svg><div>絵文字登録</div></a>
                <a href="/notice/addnotice" class="menubutton"><svg><use xlink:href="../img/sysimage/menuicon/addnotice.svg#addnotice"></use></svg><div>お知らせ配信</div></a>
                <a href="/settings_admin/serveradmin" class="menubutton"><svg><use xlink:href="../img/sysimage/menuicon/server.svg#server"></use></svg><div>サーバー設定</div></a>
            <?php }?>
            
        </div>
        <input type="button" id="bot_cancelButton" class="fbtn" value="とじる">
    </div>
</div>

<script>
    var bot_modal = document.getElementById('bot_box_Modal');
    var bot_cancelButton = document.getElementById('bot_cancelButton'); // 追加
	var modalMain = $('.modal-content');

    document.getElementById("openmenu").addEventListener('click', function(){
        bot_modal.style.display = 'block';
		modalMain.addClass("slideUp");
    	modalMain.removeClass("slideDown");

        bot_cancelButton.addEventListener('click', () => { // 追加
            modalMain.removeClass("slideUp");
			modalMain.addClass("slideDown");
			window.setTimeout(function(){
				bot_modal.style.display = 'none';
			}, 150);
        });
    });
</script>

<?php ?>