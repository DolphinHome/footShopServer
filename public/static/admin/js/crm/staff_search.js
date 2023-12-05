$(function($) {
    $("select[name=deptid]").change( function() {
        var deptid = $(this).val();
        $.get("/admin.php/crm/dept/getDept?id=" + deptid, function (res) {
            $('select[name=subdivision_id]').html('');
            var first = new Option('无', 0, true, true);
            $('select[name=subdivision_id]').append(first);
            $.each(res, function (i) {
                var option = new Option(res[i], i, true, false);
                $('select[name=subdivision_id]').append(option);
            });
            $('select[name=subdivision_id]').trigger("change");
        });
    });

    $("select[name=subdivision_id]").change( function() {
        var deptid = $(this).val();
        $.get("/admin.php/crm/dept/getDept?id=" + deptid, function (res) {
            $('select[name=group_id]').html('');
            var first = new Option('无', 0, true, true);
            $('select[name=group_id]').append(first);
            $.each(res, function (i) {
                var option = new Option(res[i], i, true, false);
                $('select[name=group_id]').append(option);
            });
            $('select[name=group_id]').trigger("change");
        });
    });
   // $("tbody .contacts").on('click', function() {
   //      var id = $(this).html();
   //      var url = "/admin.php/crm/account_chanel/consume?layer=1&id="+$.trim(id);
   //      layer.open({
   //          type: 2,
   //          title: false,
   //          closeBtn: false,
   //          shadeClose: true,
   //          offset: 'r',
   //          anim: '7',
   //          area: ['66%', '100vh'],
   //          content: url
   //      })
   //  });

});
