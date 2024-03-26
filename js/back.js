
window.onload = function () {
    var url = new URL(window.location.href);

    var ele = document.getElementsByTagName("body")[0];
    var n = Math.floor(Math.random() * 3); // 3枚の画像がある場合
    ele.style.backgroundImage = "url(" + url.protocol + "//" + url.hostname + "/img/titleimg/" + n + ".png)";
}
