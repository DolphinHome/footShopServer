var logAddIng = 0;
var logReplayIng = 0;
var recordAddIng = 0;
var recordReplayIng = 0;
var project_id = $('#project_id').val();
var record_url = $('#record_url').val();
var record_url_read = $('#record_url_read').val();
var log_url = $('#log_url').val();
var log_url_read = $('#log_url_read').val();
var log_url_del = $('#log_url_del').val();
var bugs_url = $('#bugs_url').val();
var task_url = $('#task_url').val();
var plan_url = $('#plan_url').val();
var plan_url_this = $('#plan_url_this').val();
var readRecordIng;
var readLogIng;
var vueDome = new Vue({
	el: '#vueDome',
	data: {
		title: 'VUE',
		itemsRecord: [],
		itemsLog: [],
		itemsBugs: [],
		itemsBugsSeverityArr: [],
		itemsBugsStatusArr: [],
		itemsBugsTypeArr: [],
		itemsTask: [],
		itemsPlan: [],
		itemsPlanDetail: {},
		itemsPlanThis: {},
		fugai: false,
		zzbg: false,
		nummers: 1
	},
	created: function() {
		var that = this;
		this.listAll();

		layui.use(['upload'], function() {
			var layuiUpload = layui.upload;

			// 导入测试记录
			var projectPlanId = $('.LAY_imageUploadProjectBug').attr('data-id');
			layuiUpload.render({
				url: "/admin.php/crm/project_bugs/import?project_id=" + projectPlanId,
				elem: ".LAY_imageUploadProjectBug",
				accept: 'file',
				done: function(data) {
					if (data.error) {
						layer.msg(data.error);
						return false;
					}

					layer.msg(data.success);
					that.bugslist();
				}
			})
			// 导入测试记录
		});
	},
	mounted() {
		/*this.$refs.scrolltoplist.addEventListener(
	      	"scroll",
	      	this.handleScroll,
	      	true
	    );*/
	    // 监听（绑定）滚轮 滚动事件
	},
	methods: {

		loadMore(){
			var thisVue = this;
			var page = $("#page_num").val();
			var next=Number(page)+1;
			var length = 10;
			var staff_id = $("#staff_id_log").val();
			var arr = [];
			$.ajax({
				url: log_url,
				type: "post",
				dataType: "json",
				data: {
					page: page,
					length: length,
					project_id: project_id,
					staff_id: staff_id,
				},
				success: function(data) {
					$("#page_num").val(next);
					arr = thisVue.itemsLog.concat(data.data.list);
					thisVue.itemsLog = arr;
					$('.notesListULShow').show();
					if(data.data.list==''){
						$("#more_str").html("暂无数据");
					}
				}
			})
		},

		random() {
			var rand1 = 0;
			var useRand = 0;
			images = new Array;
			images[1] = new Image();
			images[1].src = "/btad.png";
			// images[1] = new Image();
			// images[1].src = "/static/service/tired/first.png";
			// images[2] = new Image();
			// images[2].src = "/static/service/tired/second.png";
			// images[3] = new Image();
			// images[3].src = "/static/service/tired/thirth.png";
			//console.log(images)
			var imgnum = images.length - 1;
			do {
				var randnum = Math.random();
				rand1 = Math.round((imgnum - 1) * randnum) + 1;
			} while (rand1 == useRand);
			useRand = rand1;
			document.randimg.src = images[useRand].src;
		},
		tains() {
			console.log("111")
			return false;
			console.log("222")
			let that = this
			var myDate = new Date()
			var h = myDate.getHours(); //获取系统时，
			var m = myDate.getMinutes(); //分
			var s = myDate.getSeconds(); //秒
			var timeslong = Number(h * 3600) + Number(m * 60) + Number(s); //当前
			var samll = Number(18 * 3600) + Number(30 * 60) + Number(1); //6.30	
			var big = Number(23 * 3600) + Number(59 * 60) + Number(59); //11.59
			if (timeslong > samll && timeslong < big && that.nummers == 1) {
				that.zzbg = true
				that.fugai = true
				this.random()
				that.nummers = 2
			} else {
				//console.log("当前时间未处于6点半到12点之间")
				that.zzbg = false
				that.fugai = false
			}
		},
		offs: function() {
			let that = this
			that.zzbg = false
			that.fugai = false
		},
		listAll: function() {
			this.recordlist();
			this.loglist();
			this.bugslist();
			// this.tasklist();
			// this.planlist();
			// this.planlistThis();
		},
		recordlist: function() {
			// 项目分析
			var thisVue = this;
			$.ajax({
				url: record_url,
				type: "post",
				dataType: "json",
				data: {
					project_id: project_id
				},
				success: function(data) {
					//console.log('项目分析')
					//console.log(data)

					thisVue.itemsRecord = data.data.list;
					$('.notesListULShow').show();
				}
			})
		},
		readRecord(id, type) {
			var that = this;
			// console.log('id：' + id);
			if (type == 2 && readRecordIng) {
				// console.log('移除')
				clearTimeout(readRecordIng)
			} else if (type == 1) {
				// console.log('开始')
				readRecordIng = setTimeout(function() {
					// console.log('发送请求')
					$.ajax({
						url: record_url_read,
						type: "post",
						dataType: "json",
						data: {
							id: id
						},
						success: function(data) {
							// console.log(data)
							if (data.success && data.success == '标记成功') {
								that.recordlist();
							}
						}
					})
				}, 3000)
			} else if (type == 3) {
				$.ajax({
					url: record_url_read,
					type: "post",
					dataType: "json",
					data: {
						id: id
					},
					success: function(data) {
						// console.log(data)
						if (data.success && data.success == '标记成功') {
							that.recordlist();
						}
					}
				})
			}
		},

		open(aid) {
        	var that = this;

        	layer.confirm('确定要删除吗？', {
				btn: ['确定', '取消']
			}, function() {
				$.ajax({
					url: log_url_del,
					type: "post",
					dataType: "json",
					data: {
						aid: aid
					},
					success: function(data) {
						if (data.success && data.success == '删除成功') {
							var length = that.itemsLog.length;
							that.new_data(length);
							layer.msg("删除成功");
						}
					}
				})
			});
	  	},

		loglist: function() {
			// 工作日志
			var thisVue = this;
			var staff_id = $("#staff_id_log").val();
			$.ajax({
				url: log_url,
				type: "post",
				dataType: "json",
				data: {
					project_id: project_id,
					staff_id: staff_id
				},
				success: function(data) {
					thisVue.itemsLog = data.data.list;
					$('.notesListULShow').show();
				}
			})
		},
		readLog(id, type) {
			var that = this;
			// console.log('id：' + id);
			if (type == 2 && readLogIng) {
				// console.log('移除')
				clearTimeout(readLogIng)
			} else if (type == 1) {
				// console.log('开始')
				readLogIng = setTimeout(function() {
					// console.log('发送请求')
					$.ajax({
						url: log_url_read,
						type: "post",
						dataType: "json",
						data: {
							id: id
						},
						success: function(data) {
							// console.log(data)
							if (data.success && data.success == '标记成功') {
								var length = that.itemsLog.length;
								that.new_data(length);
							}
						}
					})
				}, 3000)
			} else if (type == 3) {
				$.ajax({
					url: log_url_read,
					type: "post",
					dataType: "json",
					data: {
						id: id
					},
					success: function(data) {
						//console.log(data)
						if (data.success && data.success == '标记成功') {
							var length = that.itemsLog.length;
							that.new_data(length);
						}
					}
				})
			}
		},

		new_data(length){
			var thisVue = this;
			$.ajax({
				url: log_url,
				type: "post",
				dataType: "json",
				data: {
					page: 0,
					length: length,
					project_id: project_id,
				},
				success: function(data) {
					thisVue.itemsLog = data.data.list;
					$('.notesListULShow').show();
				}
			})
		},

		bugslist: function() {
			var param = '';
			$('.iBugslistSelect').each(function() {
				var name = $(this).attr('name');
				var value = $(this).val();
				if (name && value) {
					if (param) {
						param += '&' + name + '=' + value
					} else {
						param += '?' + name + '=' + value
					}
				}
			})

			// 测试记录
			var thisVue = this;
			$.ajax({
				url: bugs_url + param,
				type: "post",
				dataType: "json",
				data: {
					project_id: project_id
				},
				success: function(data) {
					//console.log('测试记录')
					//console.log(data)

					thisVue.itemsBugs = data.data.list;
					thisVue.itemsBugsSeverityArr = data.data.severityArr;
					thisVue.itemsBugsStatusArr = data.data.statusArr;
					thisVue.itemsBugsTypeArr = data.data.typeArr;
				}
			})
		},
		tasklist: function() {
			// 计划任务
			var thisVue = this;
			$.ajax({
				url: task_url,
				type: "post",
				dataType: "json",
				data: {
					project_id: project_id
				},
				success: function(data) {
					//console.log('计划任务')
					//console.log(data)

					thisVue.itemsTask = data.data.list;
				}
			})
		},
		planlist: function() {
			// 周计划
			var thisVue = this;
			$.ajax({
				url: plan_url,
				type: "post",
				dataType: "json",
				data: {
					project_id: project_id
				},
				success: function(data) {
					//console.log('周计划')
					//console.log(data)

					thisVue.itemsPlan = data.data.list;
				}
			})
		},
		planlistThis: function(param = '') {
			// 本周计划
			var thisVue = this;
			$.ajax({
				url: plan_url_this + param,
				type: "post",
				dataType: "json",
				data: {
					project_id: project_id
				},
				success: function(data) {
					//console.log('本周计划')
					//console.log(data)

					thisVue.itemsPlanThis = data.data.info;
				}
			})
		},
		planlistThisSearch: function(event) {
			// 筛选任务
			var str = '';
			$('.iPlanlistThisSelect').each(function() {
				var name = $(this).attr('name');
				var value = $(this).val();
				if (name && value) {
					if (str) {
						str += '&' + name + '=' + value
					} else {
						str += '?' + name + '=' + value
					}
				}
			})
			this.planlistThis(str);
		},
		recordAdd: function(event) {
			if (recordAddIng == 1) {
				return false;
			}
			recordAddIng = 1;
			var thisLoad = layer.load(0, {
				shade: false
			});

			// 项目分析 新增
			var thisVue = this;
			var form = $("#lFromFromRecord");

			$.ajax({
				url: form.attr('action'),
				type: "post",
				dataType: "json",
				data: form.serialize(),
				success: function(data) {
					recordAddIng = 0;
					layer.close(thisLoad);

					if (data.code == 0) {
						layer.msg(data.msg, {
							offset: '15px',
							icon: 2,
							time: 3000
						})
						return false;
					}
					// $('#clearCheckboxBtnRecord').click();
					$('#recordContent').val('');

					$('.layui-inputZgq').css({
						'background-color': '#F2F2F2'
					}).removeClass('lInputDate')
					$('.layui-inputZcb').css({
						'background-color': '#F2F2F2'
					}).attr('readonly', 'readonly')

					thisVue.recordlist();
				},
				error: function(data) {
					//console.log(data);
				}
			});
		},
		ajaxAdd: function(event) {
			if (recordAddIng == 1) {
				return false;
			}
			recordAddIng = 1;
			var thisLoad = layer.load(0, {
				shade: false
			});
			// 项目分析 新增
			var thisVue = this;
			var thisId = event.target.dataset.id;
			var form = $("#" + thisId);
			$.ajax({
				url: form.attr('action'),
				type: "post",
				dataType: "json",
				data: form.serialize(),
				success: function(data) {
					recordAddIng = 0;
					layer.close(thisLoad);
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
						time: 3000
					})
				},
				error: function(data) {
					//console.log(data);
				}
			});
		},
		logAdd: function(event) {
			if (logAddIng == 1) {
				return false;
			}
			logAddIng = 1;
			var thisLoad = layer.load(0, {
				shade: false
			});

			// 工作日志 新增
			var thisVue = this;
			var form = $("#lFromFromLog");

			$.ajax({
				url: form.attr('action'),
				type: "post",
				dataType: "json",
				data: form.serialize(),
				success: function(data) {
					logAddIng = 0;
					layer.close(thisLoad);

					if (data.code == 0) {
						layer.msg(data.msg, {
							offset: '15px',
							icon: 2,
							time: 3000
						})
						return false;
					}
					// $('#clearCheckboxBtnLog').click();
					// $('#logCount_page').val('');
					// $('#logCount_api').val('');
					$('#logPage_number').val('');
					$('#logContent').val('');

					thisVue.loglist();
				},
				error: function(data) {
					//console.log(data);
				}
			});
		},
		enteringRecord(e) {
			// 项目分析回复输入框
			var id = event.target.dataset.id;
			var value = e.target.value;
			var valueLast = value.substr(value.length - 1, 1);
			if (valueLast == '@' && e.inputType != 'deleteContentBackward') {
				$('.fixedStaff').show();
				$('.fixedStaffSubmit').attr('data-id', id);
				$('.fixedStaffSubmit').attr('data-type', 'Record');

				$('#fixedStaffForm xm-select').css({
					'border-color': 'rgb(0, 150, 136)'
				});
				$('#fixedStaffForm .xm-icon').addClass('xm-icon-expand');
				$('#fixedStaffForm .dis').removeClass('dis');
				$('#fixedStaffForm .xm-search-input').focus();
			}

			var dom = $('#recordReplay' + id);
			var staffIdsNames = dom.attr('data-idsnames');
			var staffIdsNamesJson = staffIdsNames ? JSON.parse(staffIdsNames) : [];
			var staffNames = dom.val();

			var staffIds = ',';
			var staffIdsNames = [];
			var i = 0;
			for (i in staffIdsNamesJson) {
				var value = '@' + staffIdsNamesJson[i]['name'];
				if (staffNames.indexOf(value) != -1) {
					staffIds += staffIdsNamesJson[i]['value'] + ',';
				}
			}

			dom.attr('data-ids', staffIds);
			// console.log(staffIds)
			// console.log(staffNames)
			// console.log(staffIdsNamesJson)
			// return false;
		},
		enteringLog(e) {
			// 项目日志回复输入框
			var id = event.target.dataset.id;
			var value = e.target.value;
			var valueLast = value.substr(value.length - 1, 1);
			if (valueLast == '@' && e.inputType != 'deleteContentBackward') {
				$('.fixedStaff').show();
				$('.fixedStaffSubmit').attr('data-id', id);
				$('.fixedStaffSubmit').attr('data-type', 'Log');
				
				$('#fixedStaffForm xm-select').css({
					'border-color': 'rgb(0, 150, 136)'
				});
				$('#fixedStaffForm .xm-icon').addClass('xm-icon-expand');
				$('#fixedStaffForm .dis').removeClass('dis');
				$('#fixedStaffForm .xm-search-input').focus();
			}

			var dom = $('#logReplay' + id);
			var staffIdsNames = dom.attr('data-idsnames');
			var staffIdsNamesJson = staffIdsNames ? JSON.parse(staffIdsNames) : [];
			var staffNames = dom.val();

			var staffIds = ',';
			var staffIdsNames = [];
			var i = 0;
			for (i in staffIdsNamesJson) {
				var value = '@' + staffIdsNamesJson[i]['name'];
				if (staffNames.indexOf(value) != -1) {
					staffIds += staffIdsNamesJson[i]['value'] + ',';
				}
			}

			dom.attr('data-ids', staffIds);
			// console.log(staffIds)
			// console.log(staffNames)
			// console.log(staffIdsNamesJson)
			// return false;
		},
		recordReply: function(event) {
			if (recordReplayIng == 1) {
				return false;
			}
			recordReplayIng = 1;
			var thisLoad = layer.load(0, {
				shade: false
			});

			// 项目分析 回复
			var that = this;
			var thisUrl = event.target.dataset.url;
			var thisId = event.target.dataset.id;
			var thisContent = $('#recordReplay' + thisId).val();
			var thisIds = $('#recordReplay' + thisId).attr('data-ids');

			$.ajax({
				type: "POST",
				url: thisUrl,
				data: {
					pid: thisId,
					content: thisContent,
					ids: thisIds
				},
				dataType: "json",
				success: function(data) {
					recordReplayIng = 0;
					layer.close(thisLoad);

					if (data.code == 0) {
						layer.msg(data.msg, {
							offset: '15px',
							icon: 2,
							time: 3000
						})
						return false;
					}

					// layer.closeAll();
					that.recordlist();
					$('#recordReplay' + thisId).val('');
					$('#recordReplay' + thisId).attr('data-ids', '');
					$('#recordReplay' + thisId).attr('data-idsnames', '');
				}
			});
		},
		recordDel: function(event) {
			// 项目分析 删除
			var thisVue = this;
			var thisUrl = event.target.dataset.url;
			var thisId = event.target.dataset.id;
			layer.confirm('确定要删除此项目分析？', {
				btn: ['确定', '取消']
			}, function() {
				$.ajax({
					type: "POST",
					url: thisUrl + '?id=' + thisId,
					data: {},
					dataType: "json",
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
							time: 3000
						})

						thisVue.recordlist();
					}
				});
			});
		},

		logReply: function(event) {
			if (logReplayIng == 1) {
				return false;
			}
			logReplayIng = 1;
			var thisLoad = layer.load(0, {
				shade: false
			});

			// 工作日志 回复
			var that = this;
			var thisUrl = event.target.dataset.url;
			var thisId = event.target.dataset.id;
			var thisContent = $('#logReplay' + thisId).val();
			var thisIds = $('#logReplay' + thisId).attr('data-ids');

			$.ajax({
				type: "POST",
				url: thisUrl,
				data: {
					pid: thisId,
					content: thisContent,
					ids: thisIds
				},
				dataType: "json",
				success: function(data) {
					logReplayIng = 0;
					layer.close(thisLoad);

					if (data.code == 0) {
						layer.msg(data.msg, {
							offset: '15px',
							icon: 2,
							time: 3000
						})
						return false;
					}

					// layer.closeAll();
					that.loglist();
					$('#logReplay' + thisId).val('');
					$('#logReplay' + thisId).attr('data-ids', '');
					$('#logReplay' + thisId).attr('data-idsnames', '');
				}
			});
		},
		moduleSubmit: function(event) {
			// 提交计划任务（从公共模块选择）
			var thisVue = this;
			var form = $("#lFromFromModule")

			$.ajax({
				url: form.attr('action'),
				type: "post",
				dataType: "json",
				data: form.serialize(),
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
						time: 3000
					})

					thisVue.listAll();
				},
				error: function(data) {
					//console.log(data);
				}
			});
		},
		planSubmit: function(event) {
			// 提交周计划
			var thisVue = this;
			var form = $("#lFromFromPlan")

			$.ajax({
				url: form.attr('action'),
				type: "post",
				dataType: "json",
				data: form.serialize(),
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
						time: 3000
					})
					$('.lFixedDivPlan').hide();

					thisVue.listAll();
				},
				error: function(data) {
					//console.log(data);
				}
			});
		},
		taskAdd: function(event) {
			// 计划任务 新增
			var thisVue = this;
			var thisUrl = event.target.dataset.url;
			var thisPid = event.target.dataset.pid;
			layer.prompt({
				title: '新增计划任务',
				formType: 2
			}, function(value, index) {
				$.ajax({
					type: "POST",
					url: thisUrl + '?pid=' + thisPid,
					data: {
						name: value,
					},
					dataType: "json",
					success: function(data) {
						if (data.code == 0) {
							layer.msg(data.msg, {
								offset: '15px',
								icon: 2,
								time: 3000
							})
							return false;
						}

						layer.closeAll();
						thisVue.listAll();
					}
				});
			});
		},
		taskAddAlert: function(event) {
			$('#lFixedDivTaskAdd').show();
			$('#lFixedDivTaskAddTitle').val('所属任务：' + event.target.dataset.name);
			$('#lFixedDivTaskAddPid').val(event.target.dataset.pid);
		},
		taskAddSubmit: function(event) {
			// 计划任务 新增
			var thisVue = this;
			var thisUrl = event.target.dataset.url;
			var thisPid = $('#lFixedDivTaskAddPid').val();
			var thisType = $('#lFixedDivTaskAddType').val();
			var thisName = $('#lFixedDivTaskAddName').val();
			if (!thisName) {
				layer.msg('名称不能为空');
				return false;
			}

			$.ajax({
				type: "POST",
				url: thisUrl + '?pid=' + thisPid,
				data: {
					module_type: thisType,
					name: thisName,
				},
				dataType: "json",
				success: function(data) {
					if (data.code == 0) {
						layer.msg(data.msg, {
							offset: '15px',
							icon: 2,
							time: 3000
						})
						return false;
					}

					$('#lFixedDivTaskAdd').hide();
					thisVue.listAll();
				}
			});
		},
		taskAddEnter: function(event) {
			// 计划任务 快速新增
			var thisVue = this;
			var thisUrl = event.target.dataset.url;
			var thisValue = event.target.value
			if (!thisValue) {
				return false;
			}

			$.ajax({
				type: "POST",
				url: thisUrl,
				data: {
					name: thisValue,
				},
				dataType: "json",
				success: function(data) {
					if (data.code == 0) {
						layer.msg(data.msg, {
							offset: '15px',
							icon: 2,
							time: 3000
						})
						return false;
					}
					$('#taskAddEnter').val('');
					layer.closeAll();
					thisVue.listAll();
				}
			});
		},
		taskDel: function(event) {
			// 计划任务 删除 (通用)
			var thisVue = this;
			var thisUrl = event.target.dataset.url;
			var thisAid = event.target.dataset.aid;
			var thisName = event.target.dataset.name;
			layer.confirm('确定要删除？', {
				btn: ['确定', '取消']
			}, function() {
				// console.log(thisUrl)
				$.ajax({
					type: "POST",
					url: thisUrl + '?aid=' + thisAid,
					data: {},
					dataType: "json",
					success: function(data) {
						if (data.code == 0) {
							layer.msg(data.msg, {
								offset: '15px',
								icon: 2,
								time: 3000
							})
							return false;
						}

						layer.closeAll();
						if (thisName == 'bugs') {
							thisVue.bugslist();
						} else {
							thisVue.listAll();
						}
					}
				});
			});
		},
		planDetail: function(event) {
			var index = event.target.dataset.index;
			this.itemsPlanDetail = this.itemsPlan[index];
		},
		planDetailHide: function(event) {
			this.itemsPlanDetail = {};
		},
		taskLingqu: function(event) {
			// 领取当天任务
			var thisVue = this;
			var thisUrl = event.target.dataset.url;
			var thisAid = event.target.dataset.aid;
			$.ajax({
				type: "POST",
				url: thisUrl + '?aid=' + thisAid,
				data: {},
				dataType: "json",
				success: function(data) {
					if (data.code == 0) {
						layer.msg(data.msg, {
							offset: '15px',
							icon: 2,
							time: 3000
						})
						return false;
					}

					thisVue.listAll();
				}
			});
		},
		taskZhipai: function(event) {
			// 指派当天任务
			var thisAid = event.target.dataset.aid;
			var thisName = event.target.dataset.name;
			var thisName1 = event.target.dataset.name1;
			var thisName2 = event.target.dataset.name2;
			$('#lFixedDivTaskZhipai').show();
			$('#lFixedDivTaskZhipaiTitle').val('任务：' + thisName1 + ' - ' + thisName2 + ' - ' + thisName);
			$('#lFixedDivTaskZhipaiId').val(thisAid);
		},
		taskZhipaiSubmit: function(event) {
			var thisAid = $('#lFixedDivTaskZhipaiId').val();
			var thisStaff_id = $('#lFixedDivTaskZhipaValue').val();
			if (!thisStaff_id) {
				layer.msg('请选择负责人');
				return false;
			}

			var thisVue = this;
			var thisUrl = event.target.dataset.url;
			$.ajax({
				type: "POST",
				url: thisUrl + '?aid=' + thisAid,
				data: {
					'staff_id': thisStaff_id
				},
				dataType: "json",
				success: function(data) {
					if (data.code == 0) {
						layer.msg(data.msg, {
							offset: '15px',
							icon: 2,
							time: 3000
						})
						return false;
					}
					$('#lFixedDivTaskZhipai').hide();

					thisVue.listAll();
				}
			});
		},
		taskWancheng: function(event) {
			// 完成任务
			var thisVue = this;
			var thisUrl = event.target.dataset.url;
			var thisAid = event.target.dataset.aid;
			$.ajax({
				type: "POST",
				url: thisUrl + '?aid=' + thisAid,
				data: {},
				dataType: "json",
				success: function(data) {
					if (data.code == 0) {
						layer.msg(data.msg, {
							offset: '15px',
							icon: 2,
							time: 3000
						})
						return false;
					}

					thisVue.listAll();
				}
			});
		},
		taskShenhe: function(event) {
			// 完成任务
			var thisVue = this;
			var thisUrl = event.target.dataset.url;
			var thisAid = event.target.dataset.aid;
			$.ajax({
				type: "POST",
				url: thisUrl + '?aid=' + thisAid,
				data: {},
				dataType: "json",
				success: function(data) {
					if (data.code == 0) {
						layer.msg(data.msg, {
							offset: '15px',
							icon: 2,
							time: 3000
						})
						return false;
					}

					thisVue.listAll();
				}
			});
		},
		taskReset: function(event) {
			// 重置任务
			var thisVue = this;
			var thisUrl = event.target.dataset.url;
			var thisAid = event.target.dataset.aid;
			$.ajax({
				type: "POST",
				url: thisUrl + '?aid=' + thisAid,
				data: {},
				dataType: "json",
				success: function(data) {
					if (data.code == 0) {
						layer.msg(data.msg, {
							offset: '15px',
							icon: 2,
							time: 3000
						})
						return false;
					}

					thisVue.listAll();
				}
			});
		},
		taskChildSH: function(event) {
			// 计划任务 子任务显示/隐藏
			var thisVue = this;
			var thisIndex = event.target.dataset.index;
			var thisIndex2 = event.target.dataset.index2;
			if (thisIndex2 == -1) {
				thisVue.itemsTask[thisIndex]['childHide'] = thisVue.itemsTask[thisIndex]['childHide'] == 1 ? 0 : 1;
			} else {
				var childHide = thisVue.itemsTask[thisIndex]['child'][thisIndex2]['childHide'];
				thisVue.itemsTask[thisIndex]['child'][thisIndex2]['childHide'] = childHide == 1 ? 0 : 1;
			}
		}
	}
})
