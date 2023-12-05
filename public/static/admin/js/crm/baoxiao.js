$(function($) {
    $("#payment").change( function() {
        var bid = $(this).val();
        $('.form-group').hide();
        $('.form-group').eq(0).show();
        $('.form-group:last-child').show();
        $.get("/admin.php/crm/payment/get_field?id=" + bid, function(res){
            console.log(res)
            if(res.code==1){
                $.each(res.data.data,function (k,v) {
                    $('.form-group').each(function () {
                        if($(this).attr('id')=='form_group_'+v){
                            $(this).show()
                        }
                    })
                })
            }
        });
    });
});
