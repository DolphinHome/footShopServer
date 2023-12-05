$(function() {
	$('.projectAddByContract').click(function() {
		layer.open({
			type: 2,
			title: $(this).attr('data-title'),
			shadeClose: true,
			shade: 0.3,
			area: ['60%', '100%'],
			offset: 'rt',
			content: $(this).attr('href')
		});
		return false;
	})

	//显示Form
	$('.projectAddShow').click(function() {
		$('.projectAddHide').show();
		$('.projectAddShow').hide();

		var thisID = $(this).attr('data-id');
		$('.projectAddForm').hide();
		$('#projectAddForm' + thisID).show();
		$('.projectList').hide();
	})

	//隐藏Form
	$('.projectAddHide').click(function() {
		$('.projectAddHide').hide();
		$('.projectAddShow').show();

		$('.projectAddForm').hide();
		$('.projectList').show();
	})

	//合同改变直接变更项目名称
	$('.contract_idSelect').change(function() {
		var thisID = $(this).attr('data-id');
		var thisValue = $(this).val();
		var thisText = $(this).find('option:selected').text();
		if (thisValue > 0) {
			$('#projectName' + thisID).val(thisText);
		} else {
			$('#projectName' + thisID).val('');
		}
	})

	//删除任务
	$('.projectDel').click(function() {
		var thisID = $(this).attr('data-id');
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
					if (data.error) {
						layer.msg(data.error);
						return false;
					}
					layer.msg(data.success);
					$('#projectListTr' + thisID).remove();
					// window.location.href = location.href + '&shouul=4';
				}
			});
		});
	})
})
