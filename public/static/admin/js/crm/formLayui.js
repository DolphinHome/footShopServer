layui.use(['element', 'form', 'laydate'], function() {
	var form = layui.form;
	var laydate = layui.laydate;

	form.verify({
		pass: [
			/^[\S]{6,16}$/, '密码必须6到16位，且不能出现空格'
		]
	});

	// 日期
	$('.laydateInput').each(function() {
		var thisID = $(this).attr('id')

		laydate.render({
			elem: '#' + thisID,
			type: 'month',
			value: '',
			isInitValue: false,
		});
	})

	// iframe层
	$('.layerOpenRT').click(function() {
		layer.open({
			type: 2,
			title: $(this).attr('data-title'),
			shadeClose: true,
			shade: 0.3,
			offset: 'rt',
			area: ['60%', '100%'],
			content: $(this).attr('data-url')
		});
		return false;
	})

	// 监听提交
	form.on('submit(formDemo)', function(data) {
		console.log(data)
		console.log(data.form.action)

		$.ajax({
			url: data.form.action,
			type: "post",
			dataType: "json",
			data: data.field,
			success: function(data) {
				if (data.code == 0) {
					layer.msg(data.msg, {
						offset: '15px',
						icon: 2,
						time: 3000
					})
					return false;
				}

				layer.msg(data.msg, {
					offset: '15px',
					icon: 1,
					time: 1000
				}, function() {
					if (data.data.href == 'reload') {
						window.location.reload()
					} else if (data.data.href) {
						window.location.href = data.data.href
					} else if (data.data.hrefParent) {
						window.parent.location.href = data.data.hrefParent
					}
				})
			},
			error: function(data) {
				console.log(data);
			}
		})

		return false;
	});
});
