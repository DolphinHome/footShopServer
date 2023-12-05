$('body').append('<div class="mjad-20200210-modal-popup" data-mjpopup="mjad-20200210-modal-td">\n' +
    '            <div class="mjad_title" style="font-weight: bold;color: #000;margin-bottom: 5px;font-size: 16px;"></div>\n' +
    '            <div class="mjad_content"></div>\n' +
    '        </div>');

if($(window).width() > 1024){
    var mjtimer = null
    $(document).on('mousemove', function(e){
        clearTimeout(mjtimer)
        mjtimer = setTimeout(function(){
            if(e.originalEvent.path.findIndex(function(item){ return $(item).attr("data-mjpopup") == 'mjad-20200210-modal-td'}) == -1){
                $('.mjad-20200210-modal-popup').css({
                    display: 'none'
                })
            }
            // console.log(e)
        }, 80)
    })
    $('.mjad-20200210-modal-td').hover(function(e){
        e.stopPropagation();
        e.preventDefault();
        $('.mjad-20200210-modal-popup').find('.mjad_title').html($(this).find('.mjad-20200210-modal-source').attr('data-title'));
        $('.mjad-20200210-modal-popup').find('.mjad_content').html($(this).find('.mjad-20200210-modal-source').html());
        $('.mjad-20200210-modal-popup').css({
            left: $(this)[0].getBoundingClientRect().left - 700,
            top: ($(this)[0].getBoundingClientRect().top > 400 ? $(this)[0].getBoundingClientRect().top - 100 : $(this)[0].getBoundingClientRect().top ),
            display: 'block'
        })
    })
    $('*').click(function(e){
        if(e.currentTarget.className != 'mjad-20200210-modal-td'){
            $('.mjad-20200210-modal-popup').css({
                display: 'none'
            })
        }
        is_need_change = true;
    })
}