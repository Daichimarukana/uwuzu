$(document).on('click', '.mini_irobtn', function (event) {
    event.preventDefault();
    mother = $(this).parent();
    mother2 = $(mother).parent();
    $(mother2).next('.nsfw_main').children().removeClass('block');
    $(mother2).next('.nsfw_main').children().addClass('clear');

    $(mother2).next('.nsfw_main').removeClass('nsfw_main');
    $(mother2).hide();
});


$(document).on('click', '#ueuse_image', function (event) {
    var imgLink = $(this).attr('src');

    var modal = $('#Big_ImageModal');
    var modalMain = $('.modal-content');
    var modalimg_zone = $('#Big_ImageMain');

    $(modalimg_zone).attr('src',imgLink);

    modal.show();
    modalMain.addClass("slideUp");
    modalMain.removeClass("slideDown");

    modal.on('click', function() {
        modalMain.removeClass("slideUp");
        modalMain.addClass("slideDown");
        window.setTimeout(function(){
            modal.hide();
        }, 150);
    });
});

function view_notify(notify){
    $("#notify").children("p").text(notify);
    $("#notify").show();
    setTimeout(function(){
        $("#notify").hide();
    }, 10000);
}
