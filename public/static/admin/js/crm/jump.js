function opensidebar(id){
    layer.open({
        type: 2,
        title: false,
        closeBtn: false,
        shadeClose: true,
        scrollbar: true,
        offset: 'r',
        anim: '7',
        area: ['66%', '100vh'],
        content: "/admin.php/crm/customer/sidebar.html?layer=1&id="+id
    })
}