$(".detail").on('click', function() {
    var id = $(this).html();
    var url = "/admin.php/crm/account_chanel/consume?layer=1&id="+$.trim(id);
    layer.open({
        type: 2,
        title: false,
        closeBtn: false,
        shadeClose: true,
        offset: 'r',
        anim: '7',
        area: ['66%', '100vh'],
        content: url
    })
});