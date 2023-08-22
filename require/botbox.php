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
        <a href="/settings" class="btmbutton"><svg><use xlink:href="../img/sysimage/menuicon/settings.svg#settings"></use></svg></a>
    </div>
</div>
<?php ?>