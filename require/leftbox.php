<?php ?>
<div class="userleftbox">
    <div class="logo">
        <img src="../img/uwuzucolorlogo.svg">
    </div>
    <hr>
    <div class="lbtnzone">
        <a href="/home" class="leftbutton">🏠ホーム</a>
        <a href="/search" class="leftbutton">検索</a>
        <?php if($notificationcount > 0){?>
            <a href="/notification" class="leftbutton">通知<div class="notipod"><p><?php echo $notificationcount?></p></div></a>
        <?php }else{?>
            <a href="/notification" class="leftbutton">通知</a>
        <?php }?>
        <a href="/emoji" class="leftbutton">絵文字</a>
        <a href="/@<?php echo $userid; ?>" class="leftbutton">プロフィール</a>
        <a href="/settings" class="leftbutton">設定</a>
        <a href="/others" class="leftbutton">その他</a>
        <?php if($res["admin"] === "yes"){?>
            <hr>
            <a href="/emoji/addemoji" class="leftbutton">絵文字登録</a>
            <a href="/notice/addnotice" class="leftbutton">お知らせ配信</a>
            <hr>
        <?php }?>
        <form method="post">
            <input type="submit" name="logout" class="leftbutton" value="ログアウト">
        </form>
    </div>
</div>
<?php ?>