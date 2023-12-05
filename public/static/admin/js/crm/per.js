$(".btn-user").on('click', function() {
    var id = $(this).data('id');
    var url = $(this).data('url');
    var start = $('#_filter_time_from').val();
    var end = $('#_filter_time_to').val();
    layer.open({
        type: 2,
        title: false,
        closeBtn: false,
        shadeClose: true,
        scrollbar: true,
        offset: 'r',
        anim: '7',
        area: ['66%', '100vh'],
        content: url+"?layer=1&id="+id+'&_filter_time_from='+start+'&_filter_time_to='+end
    })
});