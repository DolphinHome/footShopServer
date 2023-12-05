$(function() {
	//新增任务
	$('#taskFormInput').keyup(function(event) {
		var dom = $(this)
		if (event.keyCode == 13) {
			var value = $(this).val();
			if (!value) {
				layer.msg('新任务不能为空');
				return false;
			}
			$.ajax({
				type: "POST",
				url: $(this).attr('data-url'),
				data: {
					module_id: 0,
					project_id: $(this).attr('data-id'),
					task_name: value,
				},
				dataType: "json",
				success: function(data) {
					if (data.code == 0) {
						layer.msg(data.msg);
						return false;
					}
					layer.msg(data.msg);
					window.location.reload();
					dom.val('');
				}
			});
		}
	});

	//选中多选框
	$('.taskListFirCheckbox').click(function() {
		var thisModule = $(this).attr('data-module');
		var thisID = $(this).attr('data-id');
		if ($(this).is(":checked")) {
			$('#taskListSecUl' + thisModule + '-' + thisID).show();
		} else {
			$('#taskListSecUl' + thisModule + '-' + thisID).hide();
		}
	})

	//提交任务分配
	$('.taskListSubmit').click(function() {
		var dom = $(this)
		var str = '';
		var strDel = '';
		$('.taskListFirCheckbox').each(function() {
			var thisModule = $(this).attr('data-module');
			var thisID = $(this).attr('data-id');
			if ($(this).is(":checked")) {
				var str2 = '';
				$('.checkInput' + thisModule + '-' + thisID).each(function() {
					if ($(this).is(":checked")) {
						if (str2) {
							str2 += ',' + $(this).val()
						} else {
							str2 += thisID + ':' + thisModule + ':' + $(this).val()
						}
					}
				})
				// console.log(str2)
				if (str2) {
					str = str ? str + '#' + str2 : str + str2;
				}
			} else if(thisID > 0){
				strDel = strDel + ',' + thisID;
			}
		})
		// console.log(str)
		$.ajax({
			type: "POST",
			url: $(this).attr('data-url'),
			data: {
				project_id: dom.attr('data-id'),
				value: str,
				del_str: strDel,
			},
			dataType: "json",
			success: function(data) {
				if (data.code == 0) {
					layer.msg(data.msg);
					return false;
				}
				layer.msg(data.msg);
				window.location.reload();
			}
		});
	})

	//删除任务
	$('.taskDel').click(function() {
		var thisUrl = $(this).attr('data-url');
		var thisDesc = $(this).attr('data-desc') ? $(this).attr('data-desc') : '确定要删除吗?';

		layer.confirm(thisDesc, {
			btn: ['确定', '取消']
		}, function() {
			$.ajax({
				type: "POST",
				url: thisUrl,
				data: {},
				dataType: "json",
				success: function(data) {
					if (data.code == 0) {
						layer.msg(data.msg);
						return false;
					}
					layer.msg(data.msg);
					window.location.reload();
				}
			});
		});
	})
})
