$(document).on('click', '.mini_irobtn', function(event) {
    event.preventDefault();
    mother = $(this).parent();
    mother2 = $(mother).parent();
    $(mother2).next('.nsfw_main').children().removeClass('block');
    $(mother2).next('.nsfw_main').children().addClass('clear');

    $(mother2).next('.nsfw_main').removeClass('nsfw_main');
    $(mother2).hide();
});