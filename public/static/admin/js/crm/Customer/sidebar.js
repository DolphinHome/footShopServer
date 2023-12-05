var trackAddLoad = false;
var trackReplyLoad = false;
var trackReadIng;
var vueDome = new Vue({
	el: '#vueDome',
	data: {
		title: 'VUE',
		itemsTrack: [],
	},
	created: function() {
		var that = this;
		this.listAll();
	},
	mounted() {},
	methods: {
		listAll: function() {
			this.trackList();
		},
		trackList() {
			// 沟通记录
			var thisVue = this;
			$.ajax({
				url: $('#trackListUrl').val(),
				type: "post",
				dataType: "json",
				data: {},
				success: function(data) {
					thisVue.itemsTrack = data.data.list;
					$('#trackListULShow').show();
				}
			})
		},
		trackAdd(e) {
			// 沟通记录 新增
			if (trackAddLoad) {
				return false;
			}
			trackAddLoad = true;
			var thisLoad = layer.load(0, {
				shade: false
			});

			var that = this;
			var thisDom = $('#doTrackForm');
			var thisUrl = $('#doTrackForm').attr('action');
			var thisData = $('#doTrackForm').serializeArray();
			thisData.push({
				'name': 'purpose_id',
				'value': e.target.dataset.status
			})

			$.ajax({
				url: thisUrl,
				type: "post",
				dataType: "json",
				data: thisData,
				success: function(data) {
					trackAddLoad = false;
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

					that.trackList();
					$('#trackResult').val('');
				}
			})
		},
		trackReply(e) {
			// 沟通记录 回复
			if (trackReplyLoad) {
				return false;
			}
			trackReplyLoad = true;
			var thisLoad = layer.load(0, {
				shade: false
			});

			var that = this;
			var id = e.target.dataset.id;
			var ids = e.target.dataset.ids;
			var value = $('#trackReply' + id).val();
			if (!value) {
				return false;
			}

			$.ajax({
				url: $('#trackReplyUrl').val(),
				type: "post",
				dataType: "json",
				data: {
					'id': id,
					'value': value,
					'ids': ids,
				},
				success: function(data) {
					trackReplyLoad = false;
					layer.close(thisLoad);

					if (data.code == 0) {
						layer.msg(data.msg, {
							offset: '15px',
							icon: 2,
							time: 3000
						})

						return false;
					}

					that.trackList();
					$('#trackReply' + id).val('');
				}
			})
		},
		inputTrackReply(e) {
			// 项目分析回复输入框
			var id = event.target.dataset.id;
			var value = e.target.value;
			var valueLast = value.substr(value.length - 1, 1);
			if (valueLast == '@' && e.inputType != 'deleteContentBackward') {
				$('.fixedStaff').show();
				$('.fixedStaffSubmit').attr('data-id', id);
				$('.fixedStaffSubmit').attr('data-type', 'Reply');
				
				$('#fixedStaffForm xm-select').css({
					'border-color': 'rgb(0, 150, 136)'
				});
				$('#fixedStaffForm .xm-icon').addClass('xm-icon-expand');
				$('#fixedStaffForm .dis').removeClass('dis');
				$('#fixedStaffForm .xm-search-input').focus();
			}

			var dom = $('#trackReply' + id);
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
		},
		trackRead(id, type) {
			var that = this;
			// console.log('id：' + id);
			if (type == 2 && trackReadIng) {
				// console.log('移除')
				clearTimeout(trackReadIng)
			} else if (type == 1) {
				// console.log('开始')
				trackReadIng = setTimeout(function() {
					// console.log('发送请求')
					$.ajax({
						url: $('#trackReadUrl').val(),
						type: "post",
						dataType: "json",
						data: {
							id: id
						},
						success: function(data) {
							if (data.success && data.success == '标记成功') {
								that.trackList();
							}
						}
					})
				}, 3000)
			} else if (type == 3) {
				$.ajax({
					url: $('#trackReadUrl').val(),
					type: "post",
					dataType: "json",
					data: {
						id: id
					},
					success: function(data) {
						if (data.success && data.success == '标记成功') {
							that.trackList();
						}
					}
				})
			}
		},
	}
})
