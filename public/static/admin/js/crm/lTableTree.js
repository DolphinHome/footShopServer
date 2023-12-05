$(function() {
	// 表格树
	// 默认显示几层
	var levelShow = $('.lTableTree').attr('data-level');
	$('.lTableTree tbody tr').each(function() {
		// 初始化
		var dom = $(this);
		var level = dom.attr('data-level');
		if (level <= levelShow) {
			dom.show().addClass('show');
			if (level < levelShow) {
				dom.find('.lTableTreeClick').addClass('selected');
			} else {
				dom.find('.lTableTreeClick').removeClass('selected');
			}
		} else {
			dom.hide().removeClass('selected').removeClass('show');
		}
	})

	// 点击事件
	$('.lTableTreeClick').click(function() {
		var dom = $(this);
		var id = dom.attr('data-id');
		if (dom.hasClass('selected')) {
			var selected = 1;
			dom.removeClass('selected')
		} else {
			var selected = 0;
			dom.addClass('selected')
		}
		// 上级字符串
		var ids = ',' + id + ',';

		$('.lTableTree tbody tr').each(function() {
			var idC = $(this).attr('data-id');
			var idP = $(this).attr('data-pid');
			if (idP == id) {
				// 当前的下层（直接）
				if (selected) {
					$(this).removeClass('show');
				} else {
					$(this).addClass('show');
				}

				ids += ',' + idC + ',';
				if (selected) {
					$(this).hide();
				} else {
					$(this).show();
				}
			} else if (ids.indexOf(',' + idP + ',') != -1) {
				// 当前的下层（间接）
				ids += ',' + idC + ',';
				if (selected) {
					$(this).hide();
				} else if ($(this).hasClass('show')) {
					$(this).show();
				}
			}
		})
	})
})
