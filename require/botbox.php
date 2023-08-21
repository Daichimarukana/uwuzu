<?php ?>
<div class="botbox">
    <div class="lbtnzone">
        <a href="/home" class="btmbutton">ホーム</a>
        <?php if($notificationcount > 0){?>
            <a href="/notification" class="btmbutton_on">通知</a>
        <?php }else{?>
            <a href="/notification" class="btmbutton">通知</a>
        <?php }?>
        <a href="/search" class="btmbutton">検索</a>
        <a href="/settings" class="btmbutton">設定</a>
    </div>
</div>
<?php ?>