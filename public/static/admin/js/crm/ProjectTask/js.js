$(function() {
	// $('#moduleMemberSubmit').click(function() {
	// 	var project_module = '';
	// 	$('.checkboxProject_module').each(function() {
	// 		if ($(this).is(':checked')) {
	// 			var value = $(this).val();
	// 			if (project_module) {
	// 				project_module += ','
	// 			}
	// 			project_module += value
	// 		}
	// 	})

	// 	var project_member = '';
	// 	$('.checkboxProject_member').each(function() {
	// 		if ($(this).is(':checked')) {
	// 			var value = $(this).val();
	// 			if (project_member) {
	// 				project_member += ','
	// 			}
	// 			project_member += value
	// 		}
	// 	})

	// 	$.ajax({
	// 		type: "POST",
	// 		url: $(this).attr('data-url'),
	// 		data: {
	// 			id: $(this).attr('data-id'),
	// 			project_module: project_module,
	// 			project_member: project_member,
	// 		},
	// 		dataType: "json",
	// 		success: function(data) {
	// 			if (data.code == 0) {
	// 				layer.msg(data.msg);
	// 				return false;
	// 			}
	// 			layer.msg(data.msg);
	// 			// window.location.reload();
	// 		}
	// 	});
	// })

	//有后台、有页面、有接口、有数据库
	$('.project_hasClick').click(function() {
		$.ajax({
			type: "POST",
			url: $(this).attr('data-url'),
			data: {
				value: $(this).is(':checked') ? 1 : 0,
			},
			dataType: "json",
			success: function(data) {
				if (data.code == 0) {
					layer.msg(data.msg);
					return false;
				}
				// layer.msg(data.msg);
			}
		});
	})

	//分配人员
	$('#project_memberSubmit').click(function() {
		var project_member = '';
		$('.checkboxProject_member').each(function() {
			if ($(this).is(':checked')) {
				var value = $(this).val();
				if (project_member) {
					project_member += ','
				}
				project_member += value
			}
		})

		$.ajax({
			type: "POST",
			url: $(this).attr('data-url'),
			data: {
				id: $(this).attr('data-id'),
				project_member: project_member,
			},
			dataType: "json",
			success: function(data) {
				if (data.code == 0) {
					layer.msg(data.msg);
					return false;
				}
				// layer.msg(data.msg);
				window.location.reload();
			}
		});
	})

	//模块选择
	$('#project_moduleSubmit').click(function() {
		var project_module = '';
		$('.checkboxProject_module').each(function() {
			if ($(this).is(':checked')) {
				var value = $(this).val();
				if (project_module) {
					project_module += ','
				}
				project_module += value
			}
		})

		$.ajax({
			type: "POST",
			url: $(this).attr('data-url'),
			data: {
				id: $(this).attr('data-id'),
				project_module: project_module,
			},
			dataType: "json",
			success: function(data) {
				if (data.code == 0) {
					layer.msg(data.msg);
					return false;
				}
				// layer.msg(data.msg);
				window.location = location.href + '&tab=task';
			}
		});
	})

	//修改任务状态
	$('.taskDel').click(function() {
		var thisUrl = $(this).attr('data-url');
		var thisDesc = $(this).attr('data-desc') ? $(this).attr('data-desc') : '确定要删除吗?';
		var thisTab = $(this).attr('data-tab');

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
					// layer.msg(data.msg);

					window.location = location.href + '&tab=' + thisTab;
				}
			});
		});
	})

	//修改任务数量
	$('.taskNumInput').blur(function() {
		$.ajax({
			type: "POST",
			url: $(this).attr('data-url'),
			data: {
				'name': $(this).attr('name'),
				'value': $(this).val(),
			},
			dataType: "json",
			success: function(data) {
				if (data.code == 0) {
					layer.msg(data.msg);
					return false;
				}
				// layer.msg(data.msg);
			}
		});
	})

	//模块Tab
	$('.project_mouldeClick').click(function() {
		var id = $(this).attr('data-id');
		$('.project_mouldeClick').removeClass('selected');
		$(this).addClass('selected');

		if (id == -1) {
			$('.project_mouldeTask').show();
		} else {
			$('.project_mouldeTask').hide();
			$('.project_mouldeTask' + id).show();
		}
	})

	//项目成员Tab
	$('.project_memberClick').click(function() {
		var id = $(this).attr('data-id');
		$('.project_memberClick').removeClass('selected');
		$(this).addClass('selected');

		if (id == -1) {
			$('.project_memberTask').show();
		} else {
			$('.project_memberTask').hide();
			$('.project_memberTask' + id).show();
		}
	})

	//新增任务
	$('#taskFormInput').keyup(function(event) {
		var dom = $(this)
		if (event.keyCode == 13) {
			var moulde_id = 0;
			$('.project_mouldeClick').each(function() {
				if (moulde_id) {
					return true;
				}
				var id = $(this).attr('data-id');
				if ($(this).hasClass('selected') && id > 0) {
					moulde_id = id;
				}
			})

			var value = $(this).val();
			if (!value) {
				layer.msg('新任务不能为空');
				return false;
			}
			$.ajax({
				type: "POST",
				url: $(this).attr('data-url'),
				data: {
					module_id: moulde_id,
					project_id: $(this).attr('data-id'),
					task_name: value,
				},
				dataType: "json",
				success: function(data) {
					if (data.code == 0) {
						layer.msg(data.msg);
						return false;
					}
					// layer.msg(data.msg);
					window.location = location.href + '&tab=task';
				}
			});
		}
	});

	//显示计划任务批注
	$('.showNotesIframe').click(function() {
		var thisName = $(this).attr('data-name')
		var thisUrl = $(this).attr('data-url')
		layer.open({
			type: 2,
			title: thisName,
			shadeClose: true,
			shade: 0.8,
			area: ['600px', '600px'],
			content: thisUrl
		});
	})

	//提交沟通记录
	$('#recordSubmit').click(function() {
		var thisUrl = $(this).attr('data-url');
		var recordContent = $('#recordContent').val();
		if (!recordContent) {
			layer.msg('记录内容不能为空');
			return false;
		}

		$.ajax({
			type: "POST",
			url: thisUrl,
			data: {
				content: recordContent,
			},
			dataType: "json",
			success: function(data) {
				if (data.code == 0) {
					layer.msg(data.msg);
					return false;
				}
				// layer.msg(data.msg);

				window.location = location.href + '&tab=record';
			}
		});
	})

	//回复沟通记录
	$('.replayRecordClick').click(function() {
		var thisUrl = $(this).attr('data-url')
		layer.prompt({
			title: '回复',
			formType: 2
		}, function(value, index) {
			$.ajax({
				type: "POST",
				url: thisUrl,
				data: {
					content: value,
				},
				dataType: "json",
				success: function(data) {
					if (data.code == 0) {
						layer.msg(data.msg);
						return false;
					}
					// layer.msg(data.msg);

					window.location = location.href + '&tab=record';
				}
			});
		});
	})

	//计划任务全选
	$('.checkboxTaskAll').change(function() {
		if ($(this).is(':checked')) {
			$('.checkboxTaskOne').prop("checked", true);
		} else {
			$('.checkboxTaskOne').prop("checked", false);
		}
	})

	//批量完成计划任务
	$('#taskSubmitAll').click(function() {
		var thisStr = '';
		$('.checkboxTaskOne').each(function() {
			if ($(this).is(':checked')) {
				thisStr += $(this).val() + ',';
			}
		})

		if (!thisStr) {
			layer.msg('请选择')
			return false;
		}

		$.ajax({
			type: "POST",
			url: $(this).attr('data-url'),
			data: {
				str: thisStr,
			},
			dataType: "json",
			success: function(data) {
				if (data.code == 0) {
					layer.msg(data.msg);
					return false;
				}
				layer.msg(data.msg);

				window.location = location.href + '&tab=taskAll';
			}
		});
	})
})
