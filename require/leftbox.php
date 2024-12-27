<?php 
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);
?>
<div class="userleftbox">
    <?php if(!empty($serversettings["serverinfo"]["server_logo_home"])){ ?>
    <div class="logo">
        <img src=<?php echo safetext($serversettings["serverinfo"]["server_logo_home"]);?>>
    </div>
    <?php }else{?>
    <div class="logo">
        <img src="../img/uwuzucolorlogo.svg">
    </div>
    <?php }?>
    <hr>
    <div class="lbtnzone">
        <a href="/home" class="leftbutton"><svg><use xlink:href="../img/sysimage/menuicon/home.svg#home"></use></svg>ホーム</a>
        <a href="/search" class="leftbutton"><svg><use xlink:href="../img/sysimage/menuicon/search.svg#search"></use></svg>検索</a>
        <?php if($notificationcount > 0){?>
            <a href="/notification" class="leftbutton"><svg><use xlink:href="../img/sysimage/menuicon/notification2.svg#notification"></use></svg>通知<div class="notipod"><p><?php echo $notificationcount?></p></div></a>
        <?php }else{?>
            <a href="/notification" class="leftbutton"><svg><use xlink:href="../img/sysimage/menuicon/notification.svg#notification"></use></svg>通知</a>
        <?php }?>
        <a href="/bookmark" class="leftbutton"><svg><use xlink:href="../img/sysimage/menuicon/bookmark.svg#bookmark"></use></svg>ブックマーク</a>
        <a href="/emoji" class="leftbutton"><svg><use xlink:href="../img/sysimage/menuicon/emoji.svg#emoji"></use></svg>絵文字</a>
        <a href="/@<?php echo $userid; ?>" class="leftbutton"><svg><use xlink:href="../img/sysimage/menuicon/profile.svg#profile"></use></svg>プロフィール</a>
        <a href="/settings" class="leftbutton"><svg><use xlink:href="../img/sysimage/menuicon/settings.svg#settings"></use></svg>設定</a>
        <a href="/others" class="leftbutton"><svg><use xlink:href="../img/sysimage/menuicon/others.svg#others"></use></svg>その他</a>
        <?php if($res["admin"] === "yes"){?>
            <hr>
            <a href="/notice/addnotice" class="leftbutton"><svg><use xlink:href="../img/sysimage/menuicon/addnotice.svg#addnotice"></use></svg>お知らせ配信</a>
            <a href="/settings_admin/serveradmin" class="leftbutton"><svg><use xlink:href="../img/sysimage/menuicon/server.svg#server"></use></svg>サーバー設定</a>
        <?php }?>
        <hr>
        <form method="post">
            <input type="submit" name="logout" class="leftbutton" value="ログアウト">
        </form>
    </div>
</div>
<?php ?>