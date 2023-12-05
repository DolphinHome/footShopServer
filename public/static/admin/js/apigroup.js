$(function($) {
	$("#module").change( function() {
		var module = $(this).val();
		$.get("/admin.php/admin/apilist/get_group?module=" + module, function(res){
			$('#group').html('');
			$.each(res, function(i) {
				var option = new Option(res[i], i, true, true);
				$('#group').append(option);
		    });
		    $('#group').trigger("change");
		});
	});
});
$("#importData").click(function(){
	var id = api_code;
	if(id == ''){
		layer.msg('项目序列号不能为空');
		return false;
	}
	$.ajax({
        type: "POST",
        url: "/admin.php/admin/apilist/import_data",
        dataType: "JSON",
        data: {
            "code": id,
        },
        beforeSend:function(){
            var index = layer.load(0, {shade: false}); //0代表加载的风格，支持0-2
        },
        success:function(data){
            layer.closeAll('loading');
            if (data.code == 1) {
				layer.msg('操作成功');
				window.location.reload();
			} else {
				layer.msg(data.msg);
			}

        },
        error:function(){
            layer.msg("数据有误，请重试！");
        },
    });
})
