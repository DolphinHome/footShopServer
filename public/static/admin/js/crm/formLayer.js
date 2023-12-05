//异步提交
var canFormSubmit = 1;
$('.zzFormSubmitEnter').bind('keyup', function(event) {
	if (event.keyCode == "13") {
		$('.zzFormSubmit').click();
	}
});

$('.zzFormSubmit').click(function() {
	if (canFormSubmit != 1) {
		return false;
	}
	canFormSubmit = 0;
	var thisLoad = layer.load(0, {
		shade: false
	});

	// if ($(this).attr('data-editor')) { editor.sync(); }
	let thisID = $(this).attr('data-id') ? $(this).attr('data-id') : '#zzFormForm';
	var form = $(thisID);
	$.ajax({
		url: form.attr('action'),
		type: "post",
		dataType: "json",
		data: form.serialize(),
		timeout: 30000,
		success: function(data) {
			layer.close(thisLoad);

			var error_msg = data.error_msg;
			var error = data.error;
			var success = data.success;

			if (error_msg) {
				canFormSubmit = 1;
				layer.msg(error_msg);
			} else if (error) {
				canFormSubmit = 1;
				layer.msg(error);
			} else {
				if (data.noAlert) {
					if (data.href && data.href == 'onload') {
						window.location.reload();
					} else if (data.hrefParent) {
						window.parent.location.href = data.hrefParent;
					}  else if (data.href) {
						window.location.href = data.href;
					}
				} else {
					if (data.hrefName && data.href1 && data.href1Name && data.href2 && data.href2Name) {
						layer.open({
							type: 1,
							title: '操作提示',
							closeBtn: false,
							area: '400px;',
							shade: 0.5,
							id: 'LAY_layuipro',
							btn: [data.hrefName, data.href1Name, data.href2Name],
							moveType: 1,
							content: '<div style="padding: 10px; line-height: 22px; background-color: #393D49; color: #fff; font-weight: 300;">' +
								success + '</div>',
							success: function(layero) {
								var btn = layero.find('.layui-layer-btn');
								btn.css('text-align', 'center');
								btn.find('.layui-layer-btn0').attr({
									href: data.href
								});
								btn.find('.layui-layer-btn1').attr({
									href: data.href1
								});
								btn.find('.layui-layer-btn2').attr({
									href: data.href2
								});
							}
						});
					} else if (data.hrefName && data.href1 && data.href1Name) {
						layer.open({
							type: 1,
							title: '操作提示',
							closeBtn: false,
							area: '400px;',
							shade: 0.5,
							id: 'LAY_layuipro',
							btn: [data.hrefName, data.href1Name],
							moveType: 1,
							content: '<div style="padding: 10px; line-height: 22px; background-color: #393D49; color: #fff; font-weight: 300;">' +
								success + '</div>',
							success: function(layero) {
								var btn = layero.find('.layui-layer-btn');
								btn.css('text-align', 'center');
								btn.find('.layui-layer-btn0').attr({
									href: data.href
								});
								btn.find('.layui-layer-btn1').attr({
									href: data.href1
								});
							}
						});
					} else if (data.hrefParent) {
						window.parent.location.href = data.hrefParent;
					} else {
						layer.alert(success, {
							skin: 'layui-layer-molv',
							closeBtn: 0
						}, function() {
							if (data.href && data.href == 'onload') {
								window.location.reload();
							} else if (data.href) {
								window.location.href = data.href;
							}
						});
					}
				}
			}
		},
		error: function() {
			console.log(data);
		}
	});
})
