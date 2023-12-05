$(function($) {
    $("#customer_id").change( function() {
        var custid = $(this).val();
        $.get("/admin.php/crm/contract/getOptu?customer_id=" + custid, function(res){
            $('#opportunity_id').html('');
            $.each(res, function(i) {
                var option = new Option(res[i], i, true, true);
                $('#opportunity_id').append(option);
            });
            $('#opportunity_id').trigger("change");
        });
    });

    $(".contract_id").on('click', function() {
        var id = $(this).html();
        var url = "/admin.php/crm/contract/sidebar?layer=1&id="+$.trim(id);
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
});
