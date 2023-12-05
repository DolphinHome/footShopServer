// 导出
$('.downExcel').click(function() {
	var name = $(this).attr('data-name') ? $(this).attr('data-name') : '导出数据';
	var id = $(this).attr('data-id') ? $(this).attr('data-id') : '';
	var result = []
	var titles = []
	$('#downTitle' + id + ' th').each(function() {
		if (!$(this).hasClass('downNo')) {
			titles.push($(this).text())
		}
	})
	result[result.length] = titles
	$('.downValue' + id).each(function() {
		var values = []
		$(this).find('td').each(function() {
			if (!$(this).hasClass('downNo')) {
				$(this).attr('data-down') ? values.push($(this).attr('data-down')) : values.push($(this).text())
			}
		})
		result[result.length] = values
	})
	// console.log(result)

	// // 设置样式
	// LAY_EXCEL.setExportCellStyle(result, 'A1:C'+result.length, {
	// 	s: {
	// 		alignment: {
	// 			horizontal: 'center',
	// 			vertical: 'center'
	// 		}
	// 	}
	// }, function(cell, newCell, row, config, currentRow, currentCol, fieldKey) {
	// 	// 回调参数，cell:原有数据，newCell:根据批量设置规则自动生成的样式，row:所在行数据，config:传入的配置,currentRow:当前行索引,currentCol:当前列索引，fieldKey:当前字段索引
	// 	// return ((currentRow + currentCol) % 2 === 0) ? newCell : cell; // 隔行隔列上色
	// 	return newCell; // 隔行隔列上色
	// });

	LAY_EXCEL.exportExcel(result, name + '.xlsx', 'xlsx')
})