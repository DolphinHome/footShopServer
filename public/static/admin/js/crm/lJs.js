layui.use(['element', 'form', 'laypage', 'laydate', 'upload'], function() {
	var element = layui.element;
	var form = layui.form;
	var laypage = layui.laypage;
	var laydate = layui.laydate;
	var upload = layui.upload;

	form.verify({
		pass: [
			/^[\S]{6,16}$/, '密码必须6到16位，且不能出现空格'
		]
	});

	// 清空多选框
	$('.clearCheckboxBtn').click(function() {
		$('.clearCheckbox' + $(this).attr('data-class')).prop('checked', false);
		form.render();
	})

	// 日期实例化
	$('.lInputDate').each(function() {
		laydate.render({
			elem: '#lInputDate' + $(this).attr('data-date')
		});
	})

	// 日期实例化
	$('.lInputDatetime').each(function() {
		laydate.render({
			elem: '#lInputDatetime' + $(this).attr('data-date'),
			type: 'datetime',
		});
	})

	// 日期实例化(确认后提交异步请求)
	$('.lInputDateChange').each(function() {
		var dom = $(this);
		laydate.render({
			elem: '#lInputDate' + dom.attr('data-date'),
			done: function(value, date) {
				$.ajax({
					url: dom.attr('data-url'),
					type: "post",
					dataType: "json",
					data: {
						'db': dom.attr('data-db'),
						'value': value,
					},
					success: function(data) {
						dataResult(data)
					}
				})
			}
		});
	})

	// 日期实例化 区间
	$('.lInputDateRange').each(function() {
		laydate.render({
			elem: '#lInputDateRange' + $(this).attr('data-date'),
			range: true,
		});
	})

	// 日期实例化 区间
	$('.lInputDateRangeYear').each(function() {
		laydate.render({
			elem: '#lInputDateRangeYear' + $(this).attr('data-date'),
			type: 'year',
			range: true,
		});
	})

	// 日期实例化 年月
	$('.lInputDateMonth').each(function() {
		laydate.render({
			elem: '#lInputDateMonth' + $(this).attr('data-date'),
			type: 'month'
		});
	})

	// 日期实例化 年
	$('.lInputDateYear').each(function() {
		laydate.render({
			elem: '#lInputDateYear' + $(this).attr('data-date'),
			type: 'year'
		});
	})

	// 日期实例化 回调
	$('.lInputDateAjax').each(function() {
		var thisDate = $(this).attr('data-date');
		var thisElem = '#lInputDate' + thisDate;

		laydate.render({
			elem: thisElem,
			done: function(value, date, endDate) {
				var dom = $(thisElem);

				$.ajax({
					url: dom.attr("data-url"),
					type: "post",
					dataType: "json",
					data: {
						'db': dom.attr("data-db"),
						'value': value,
					},
					success: function(data) {
						dataResult(data)
					}
				})
			}
		});
	})

	// 显示隐藏悬浮窗
	$('.lFixedDivShow').click(function() {
		$('.lFixedDiv' + $(this).attr('data-id')).show();
	})
	$('.lFixedDivHide').click(function() {
		$('.lFixedDiv').hide();
	})
	// 显示隐藏悬浮窗

	// 全选
	form.on('checkbox(lCheckboxAll)', function(data) {
		if (data.elem.checked) {
			$('.lCheckboxOne').prop('checked', true);
		} else {
			$('.lCheckboxOne').prop('checked', false);
		}
		form.render();
	});

	form.on('checkbox(lCheckboxOne)', function(data) {
		if (!data.elem.checked) {
			$('.lCheckboxAll').prop('checked', false);
			form.render();
			return false;
		}

		var lCheckboxAll = true;
		$('.lCheckboxOne').each(function() {
			if (!$(this).is(':checked')) {
				lCheckboxAll = false;
				return false;
			}
		})

		if (lCheckboxAll) {
			$('.lCheckboxAll').prop('checked', true);
			form.render();
		}
	});
	// 全选

	//监听提交
	form.on('submit(formDemo)', function(data) {
		console.log(data)
		console.log(data.form.action)

		$.ajax({
			url: data.form.action,
			type: "post",
			dataType: "json",
			data: data.field,
			success: function(data) {
				dataResult(data)
			},
			error: function(data) {
				console.log(data);
			}
		})

		return false;
	});

	// 提交表单
	$('.lFormSubmit').click(function() {
		var id = $(this).attr('data-id');
		var form = id ? $("#lFromFrom" + id) : $("#lFromFrom");

		$.ajax({
			url: form.attr('action'),
			type: "post",
			dataType: "json",
			data: form.serialize(),
			success: function(data) {
				dataResult(data)
			},
			error: function(data) {
				console.log(data);
			}
		});
	})

	// 打开新窗口
	$('.layerOpenBtn').click(function() {
		layer.open({
			type: 2,
			title: $(this).attr('data-title'),
			shadeClose: true,
			shade: 0.3,
			area: ['90%', '90%'],
			content: $(this).attr('href')
		});

		return false;
	})

	// 打开新窗口
	function layerOpenBtnRT() {
		console.log('打开新窗口')
		$('.layerOpenBtnRT').click(function() {
			var width = $(this).attr('data-width') ? $(this).attr('data-width') : '60%';
			var title = $(this).attr('data-title') == 0 ? false : $(this).attr('data-title');

			layer.open({
				type: 2,
				title: title,
				shadeClose: true,
				offset: 'rt',
				shade: 0.3,
				move: false,
				anim: 5,
				isOutAnim: false,
				area: [width, '100%'],
				content: $(this).attr('href')
			});

			return false;
		})
	}
	layerOpenBtnRT();

	// 弹出输入框
	$('.layerInputBtn').click(function() {
		var thisTitle = $(this).attr('data-title') ? $(this).attr('data-title') : '回复';
		var thisType = $(this).attr('data-type') ? $(this).attr('data-type') : 2;
		var thisUrl = $(this).attr('data-url');
		layer.prompt({
			title: thisTitle,
			formType: thisType
		}, function(value, index) {
			$.ajax({
				type: "POST",
				url: thisUrl,
				data: {
					content: value,
				},
				dataType: "json",
				success: function(data) {
					dataResult(data);
				}
			});
		});
	})

	// 提示框
	$('.layerDelBtn').click(function() {
		var thisUrl = $(this).attr('data-url') ? $(this).attr('data-url') : $(this).attr('href');
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
					dataResult(data)
				}
			});
		});

		return false;
	})

	// 排序提交
	$('.lSortChange').change(function() {
		$.ajax({
			url: $(this).attr('data-url'),
			type: "post",
			dataType: "json",
			data: {
				'db': $(this).attr('data-db'),
				'value': $(this).val(),
			},
			success: function(data) {
				dataResult(data)
			}
		})
	})

	// input框提交
	$('.lInputEnter').keyup(function(event) {
		if (event.keyCode == 13) {
			$.ajax({
				url: $(this).attr('data-url'),
				type: "post",
				dataType: "json",
				data: {
					'db': $(this).attr('data-db'),
					'value': $(this).val(),
				},
				success: function(data) {
					dataResult(data)
				}
			})
		}
	});

	$('.lInputEnterCtrl').keyup(function(event) {
		if (event.ctrlKey && event.keyCode == 13) {
			$.ajax({
				url: $(this).attr('data-url'),
				type: "post",
				dataType: "json",
				data: {
					'db': $(this).attr('data-db'),
					'value': $(this).val(),
				},
				success: function(data) {
					dataResult(data)
				}
			})
		}
	});

	// 下拉框变更提交数据
	form.on('select(saveFieldSelect)', function(data) {
		var dom = $(data.elem);

		$.ajax({
			url: dom.attr("data-url"),
			type: "post",
			dataType: "json",
			data: {
				'db': dom.attr("data-db"),
				'value': data.value,
			},
			success: function(data) {
				dataResult(data)
			}
		})
	});

	// 多选框变更提交数据
	form.on('checkbox(saveFieldCheckbox)', function(data) {
		var dom = $(data.elem);
		var thisDb = dom.attr("data-db");
		var thisValue = data.elem.checked || thisDb == 'project_member' ? data.value : '';

		$.ajax({
			url: dom.attr("data-url"),
			type: "post",
			dataType: "json",
			data: {
				'db': thisDb,
				'value': thisValue,
			},
			success: function(data) {
				dataResult(data)
			}
		})
	});

	// 筛选
	$('.lFormSearch').click(function() {
		var thisUrl = $(this).attr('data-url')
		var param = '';
		$('.iSearchSelect').each(function() {
			var name = $(this).attr('name')
			var value = $(this).val()
			if (name && value) {
				if (param) {
					param += '&' + name + '=' + value;
				} else {
					param += '?' + name + '=' + value;
				}
			}
		})
		thisUrl += param;
		window.location.href = thisUrl
	})

	// 返回结果处理
	function dataResult(data) {
		if (data.code != 1) {
			layer.msg(data.msg, {
				offset: '15px',
				icon: 2,
				time: 3000
			})

			return false;
		}

		if (data.data.noAlert) {
			if (data.data.href == 'reload') {
				window.location.reload()
			} else if (data.data.href) {
				window.location.href = data.data.href
			} else if (data.data.hrefParent == 'reload') {
				window.parent.location.reload()
			} else if (data.data.hrefParent) {
				window.parent.location.href = data.data.hrefParent
			}
		} else {
			layer.msg(data.msg, {
				offset: '15px',
				icon: 1,
				time: 1000
			}, function() {
				if (data.data.href == 'reload') {
					window.location.reload()
				} else if (data.data.hrefParent == 'reload') {
					window.parent.location.reload()
				} else if (data.data.href) {
					window.location.href = data.data.href
				} else if (data.data.hrefParent) {
					window.parent.location.href = data.data.hrefParent
				}
			})
		}
	}

	$('.iCloseLayerClick').click(function() {
		//当你在iframe页面关闭自身时
		var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
		parent.layer.close(index); //再执行关闭   
	})

	// 上传图片 项目管理
	$('.LAY_imageUploadProject').each(function() {
		var classId = 'projectFileDel';
		var path = $(this).attr('data-path');

		upload.render({
			url: "/admin.php/crm/project_file/add?path=" + path,
			elem: ".LAY_imageUploadProject" + path,
			accept: 'file',
			done: function(data) {
				if (data.error) {
					layer.msg(data.error);
					return false;
				}

				var str = "";
				str += '<tr id="' + classId + data.id + '">';
				str += '<td>';
				str += '<a href="' + data.fileShow + '" class="layerOpenBtnRT" target="_blank" style="font-size: 12px;">' +
					data.name + '</a>';
				str += '<input type="hidden" name="files[]" value="' + data.id + '">';
				str += '</td>';
				str += '<td>' + data.staff_name + '</td>';
				str += '<td><a href="' + data.file + '" target="_blank" style="color: #666;">点击下载</a></td>';
				str += '<td class="' + classId + '" data-id="' + data.id + '">删除</td></tr>';
				$('#projectFileTbody').append(str);

				setTimeout(function() {
					projectFileDel();
					layerOpenBtnRT();
				}, 200)
			}
		})
	})


	function projectFileDel() {
		$('.projectFileDel').stop().click(function() {
			var thisId = $(this).attr('data-id');

			$('#projectFileDel' + thisId).remove();
		})
	}
	projectFileDel();

	$('.LAY_imageUploadProjectSave').each(function() {
		var classId = 'projectFileDelSave';
		var id = $(this).attr('data-id');
		var path = $(this).attr('data-path');
		upload.render({
			url: "/admin.php/crm/project_file/add?project_id=" + id + '&path=' + path,
			elem: ".LAY_imageUploadProjectSave" + path,
			accept: 'file',
			done: function(data) {
				if (data.error) {
					layer.msg(data.error);
					return false;
				}

				var str = "";
				str += '<tr id="' + classId + data.id + '">';
				str += '<td><a href="' + data.fileShow + '" class="layerOpenBtnRT" target="_blank">' + data.name +
					'</a></td>';
				str += '<td>' + data.file_name + '</td>';
				str += '<td>' + data.staff_name + '</td>';
				str += '<td><a href="' + data.file + '" target="_blank" style="color: #666;">点击下载</a></td>';
				str += '<td class="' + classId + '" data-id="' + data.id + '" data-value="' + data.file + '">删除</td>';
				str += '</tr>';
				$('#projectFileTbodySave').append(str);

				setTimeout(function() {
					projectFileDelSave();
					layerOpenBtnRT();
				}, 200)
			}
		})
	})

	$('.layerCloseAll').click(function() {
		console.log('你好')
		layer.closeAll();
		parent.layer.closeAll();
	})

	function projectFileDelSave() {
		$('.projectFileDelSave').stop().click(function() {
			var thisId = $(this).attr('data-id');
			layer.confirm('确定要删除此附件？', {
				btn: ['取消', '确定']
			}, function() {
				layer.closeAll();
			}, function() {
				$.ajax({
					type: "POST",
					url: '/admin.php/crm/project_file/del?id=' + thisId,
					data: {},
					dataType: "json",
					success: function(data) {
						if (data.error) {
							layer.msg(data.error);
							return false;
						}

						layer.msg(data.success);
						$('#projectFileDelSave' + thisId).remove();
						$('.projectFileDelSavePlan' + thisId).remove();
					}
				});
			});
		})
	}
	projectFileDelSave();
	// 上传图片 项目管理

	// 上传图片 项目管理 -> 项目计划
	var projectPlanId = $('.LAY_imageUploadProjectPlan').attr('data-id');
	var path = "plan";
	upload.render({
		url: "/admin.php/crm/project_file/add?project_id=" + projectPlanId + '&path=' + path,
		elem: ".LAY_imageUploadProjectPlan",
		accept: 'file',
		done: function(data) {
			if (data.error) {
				layer.msg(data.error);
				return false;
			}
			var str = "";
			str += '<tr class="projectFileDelSavePlan' + data.id + '">';
			str += '<td>计划附件</td>';
			str += '<td><a href="' + data.file + '" target="_blank">' + data.file_name + '</a></td>';
			str += '<td>' + data.staff_name + '</td>';
			str += '<td><a href="' + data.file + '" target="_blank">点击下载</a></td>';
			str += '<td class="projectFileDelSave" data-id="' + data.id + '">删除</td>';
			str += '</tr>';
			$('#projectPlanFileTbody').append(str);
			$('#projectFileTbodySave').append(str);

			setTimeout(function() {
				projectFileDelSave();
			}, 200)
		}
	})

	function projectPlanFileDel() {
		$('.projectPlanFileDel').stop().click(function() {
			var thisId = $(this).attr('data-id');
			var thisKey = $(this).attr('data-key');
			var thisValue = $(this).attr('data-value');

			layer.confirm('确定要删除此附件？', {
				btn: ['确定', '取消']
			}, function() {
				$.ajax({
					type: "POST",
					url: '/admin.php/crm/project/delFilePlan?peoject_id=' + projectPlanId,
					data: {
						value: thisValue
					},
					dataType: "json",
					success: function(data) {
						if (data.error) {
							layer.msg(data.error);
							return false;
						}

						layer.msg(data.success);
						$('#projectPlanFileDel' + thisKey).remove();
					}
				});
			});
		})
	}
	projectPlanFileDel();
	// 上传图片 项目管理 -> 项目计划

	// 导入数据
	var importUrl = $('#importButton').attr('data-url');
	upload.render({
		elem: '#importButton',
		url: importUrl,
		accept: 'file',
		exts: 'xls|xlsx',
		done: function(data) {
			if (data.code != 0) {
				layer.msg(data.msg, {
					offset: '15px',
					icon: 2,
					time: 3000
				})
				$('.lImportResult').hide()
				return false;
			}

			layer.msg(data.msg, {
				offset: '15px',
				icon: 1,
				time: 3000
			})

			$('.lImportResult').show()
			$('#lImportSuccess').html(data.data.successStr)
			$('#lImportFail').html(data.data.failStr)
		}
	});
	// 导入数据
});
