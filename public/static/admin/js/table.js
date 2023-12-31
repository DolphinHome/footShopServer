/*!
 *  Document   : table.js
 *  Author     : caiweiming <314013107@qq.com>
 *  Description: 表格构建器
 */
jQuery(document).ready(function() {
    // 检测图片资源加载失败或者图片不存在时，使用默认图片替换
    function checkImgExist(elem,src, successCallback, errorCallback) {
        var img = new Image();
        img.src = src;
        img.onload = function () {
            successCallback(src);
        }
        img.onerror = function () {
            // 报错返回默认图片
            errorCallback(elem,'/static/images/none.png');
        }
    }
    function successCallback(src) {
        console.log("图片存在")
    }
    function errorCallback(elem,src) {
        elem.src = src;
    }
    $.each($('table img'), function (i, n) {
        checkImgExist($('table img')[i],$('table img')[i].src,successCallback,errorCallback)
    })
    $('[data-magnify]').on('click',function(){
        let len = $('.magnify-modal').length;
        if(len>1){
            $('.magnify-modal')[0].remove();
        }
    })
    if ($.fn.editable) {
        // 快速编辑的url提交地址
        $.fn.editable.defaults.url = lwwan.quick_edit_url;
        // 值为空时显示的信息
        $.fn.editable.defaults.emptytext = '空值';
        // 提交时的额外参数
        $.fn.editable.defaults.params = function (params) {
            params._t       = $(this).data('table') || '';
            params.type     = $(this).data('type') || '';
            params.validate = lwwan.validate;
            params.validate_fields = lwwan.validate_fields;
            return params;
        };
        // 提交成功时的回调函数
        $.fn.editable.defaults.success = function (res) {
            if (res.code) {
                Stars.notify(res.msg, 'success');
            } else {
                return res.msg;
            }
        };
        // 提交失败时的回调函数
        $.fn.editable.defaults.error = function(res) {
            if(res.status === 500) {
                return '服务器内部错误. 请稍后重试.';
            } else {
                return res.responseText;
            }
        };

        // 可编辑单行文本
        $('.text-edit').editable();

        // 可编辑多行文本
        $('.textarea-edit').editable({
            showbuttons: 'bottom'
        });

        // 下拉编辑
        $('.select-edit').editable();
        $('.select2-edit').editable({
            select2: {
                multiple: true,
                tokenSeparators: [',', ' ']
            }
        });

        // 日期时间
        $('.combodate-edit').editable({
            combodate: {
                maxYear: 2036,
                minuteStep: 1
            }
        });
    }

    // 跳转链接
    var goto = function (url, _curr_params, remove_page) {
        var params = {};

        if (remove_page && lwwan.curr_params['page'] !== undefined) {
            delete lwwan.curr_params['page'];
        }

        if ($.isEmptyObject(lwwan.curr_params)) {
            params = jQuery.param(_curr_params);
        } else {
            $.extend(lwwan.curr_params, _curr_params);
            params = jQuery.param(lwwan.curr_params);
        }

        location.href = url + '?'+ params;
    };

    // 初始化搜索
    var search_field = lwwan.search_field;
    var search_input_placeholder = $('#search-input').attr('placeholder');
    if (search_field !== '') {
        $('.search-bar .dropdown-menu a').each(function () {
            var self = $(this);
            if (self.data('field') === search_field) {
                $('#search-btn').html(self.text() + ' <span class="caret"></span>');
                if (self.text() === '不限') {
                    $('#search-input').attr('placeholder', search_input_placeholder);
                } else {
                    $('#search-input').attr('placeholder', '请输入'+self.text());
                }
            }
        })
    }

    // 搜索
    $('.search-bar .dropdown-menu a').click(function () {
        var field = $(this).data('field') || '';
        $('#search-field').val(field);
        $('#search-btn').html($(this).text() + ' <span class="caret"></span>');
        if ($(this).text() === '不限') {
            $('#search-input').attr('placeholder', search_input_placeholder);
        } else {
            $('#search-input').attr('placeholder', '请输入'+$(this).text());
        }
    });
    $('#search-input').on('keyup', function (e) {
        if (e.keyCode === 13) {
            var $url = $(this).data('url');
            var $filed = $('#search-field').val();
            var $keyword = $(this).val();
            var _curr_params = {
                'search_field': $filed || '',
                'keyword': $keyword || ''
            };

            goto($url, _curr_params, true);
        }
    });
    $('#search-submit-btn').click(function () {
        var $url = $('#search-input').data('url');
        var $filed = $('#search-field').val();
        var $keyword = $('#search-input').val();
        var _curr_params = {
            'search_field': $filed || '',
            'keyword': $keyword || ''
        };

        goto($url, _curr_params, true);
    });

    // 筛选
    $('.table-builder .field-filter').click(function () {
        var self             = $(this),
            $field_display   = self.data('field-display'), // 当前表格字段显示的字段名，未必是数据库字段名
            $filter          = self.data('filter'), // 要筛选的字段
            $_type           = self.data('type'), // 筛选方式
            $_filter         = lwwan._filter,
            $_filter_content = lwwan._filter_content,
            $_field_display  = lwwan._field_display,
            $data  = {
                token: self.data('token') || '', // Token
                map: self.data('map') || '', // 筛选条件
                options: self.data('options') || '', // 选项
                list: self.data('list') || ''
            };

        var width = $(window).width();
        if (width > 500) {
            width = 500;
        }

        layer.open({
            type: 1,
            title: '<i class="fa fa-filter"></i> 筛选',
            shadeClose: true,
            area: [width+'px', '530px'],
            btn:['确定', '取消'],
            content: '<div class="block-content" id="filter-check-content"><i class="fa fa-cog fa-spin"></i> 正在读取...</div>',
            success: function () {
                var $curr_filter_content = '';
                var $curr_filter = '';
                if ($_filter !== '') {
                    $curr_filter = $_filter.split('|');
                    var filed_index = $.inArray($filter, $curr_filter);
                    if (filed_index !== -1) {
                        $curr_filter_content = $_filter_content.split('|');
                        $curr_filter_content = $curr_filter_content[filed_index];
                        $curr_filter_content = $curr_filter_content.split(',');
                    }
                }
                // 获取数据
                $.post(lwwan.get_filter_list, $data).success(function(res) {
                    if (1 !== res.code) {
                        $('#filter-check-content').html(res.msg);
                        return false;
                    }

                    var list = '<div class="row push-10"><div class="col-sm-12"><div class="input-group"><div class="input-group-addon"><i class="fa fa-search"></i></div><input class="js-field-search form-control" type="text" placeholder="查找要筛选的字段"></div></div></div>';
                    if ($_type === 'checkbox') {
                        list += '<div class="row"><div class="col-sm-12"><label class="css-input css-checkbox css-checkbox-primary">';
                        list += '<input type="checkbox" id="filter-check-all"><span></span> 全选';
                        list += '</label></div></div>';
                    }
                    list += '<div class="filter-field-list">';
                    for(var key in res.list) {
                        // 如果不是该对象自身直接创建的属性（也就是该属//性是原型中的属性），则跳过显示
                        if (!res.list.hasOwnProperty(key)) {
                            continue;
                        }

                        list += '<div class="row" data-field="'+res.list[key]+'"><div class="col-sm-12">';
                        if ($_type === 'checkbox') {
                            list += '<label class="css-input css-checkbox css-checkbox-primary">';
                            list += '<input type="checkbox" ';
                            if ($curr_filter_content !== '' && $.inArray(key, $curr_filter_content) !== -1) {
                                list += 'checked ';
                            }
                            list += 'value="'+ key +'" class="check-item"><span></span> '+res.list[key];
                            list += '</label>';
                        } else {
                            list += '<label class="css-input css-radio css-radio-primary">';
                            list += '<input type="radio" name="_filter_'+$field_display+'" ';
                            if ($curr_filter_content !== '' && $curr_filter_content == key) {
                                list += 'checked ';
                            }
                            list += 'value="'+ key +'" class="check-item"><span></span> '+res.list[key];
                            list += '</label>';
                        }
                        list += '</div></div>';
                    }
                    list += '</div>';
                    $('#filter-check-content').html(list);

                    // 查找要筛选的字段
                    var $searchItems = jQuery('.filter-field-list > div');
                    var $searchValue = '';
                    var reg;
                    $('.js-field-search').on('keyup', function(){
                        $searchValue = $(this).val().toLowerCase();

                        if ($searchValue.length >= 1) {
                            $searchItems.hide().removeClass('field-show');

                            $($searchItems).each(function(){
                                reg = new RegExp($searchValue, 'i');
                                if ($(this).text().match(reg)) {
                                    $(this).show().addClass('field-show');
                                }
                            });
                        } else if ($searchValue.length === 0) {
                            $searchItems.show().removeClass('field-show');
                        }
                    });
                }).fail(function (res) {
                    Stars.notify($(res.responseText).find('h1').text() || '服务器内部错误~', 'danger');
                });
            },
            yes: function () {
                var filed_index = -1;
                if ($('#filter-check-content input[class=check-item]:checked').length == 0) {
                    // 没有选择筛选字段，则删除原先该字段的筛选
                    $_filter        = $_filter.split('|');
                    filed_index = $.inArray($filter, $_filter);
                    if (filed_index !== -1) {
                        $_filter.splice(filed_index, 1);
                        $filter         = $_filter.join('|');

                        $_field_display = $_field_display.split(',');
                        $_field_display.splice(filed_index, 1);
                        $field_display  = $_field_display.join(',');

                        $_filter_content = $_filter_content.split('|');
                        $_filter_content.splice(filed_index, 1);
                        $fields          = $_filter_content.join('|');
                    }
                } else {
                    // 当前要筛选字段内容
                    var $fields = [];
                    $('#filter-check-content input[class=check-item]:checked').each(function () {
                        if ($(this).val() !== '') {
                            $fields.push($(this).val())
                        }
                    });
                    $fields = $fields.join(',');

                    if ($_filter !== '') {
                        $_filter = $_filter.split('|');
                        filed_index = $.inArray($filter, $_filter);
                        $_filter = $_filter.join('|');

                        if (filed_index === -1) {
                            $filter = $_filter + '|' + $filter;
                            $fields = $_filter_content + '|' + $fields;
                            $field_display = $_field_display + ',' + $field_display;
                        } else {
                            $filter = $_filter;
                            $field_display = $_field_display;
                            $_filter_content = $_filter_content.split('|');
                            $_filter_content[filed_index] = $fields;
                            $fields = $_filter_content.join('|');
                        }
                    }
                }
                var _curr_params = {
                    _filter: $filter || '',
                    _filter_content: $fields || '',
                    _field_display: $field_display || ''
                };

                goto(lwwan.curr_url, _curr_params, true);
            }
        });
        return false;
    });

    // 筛选框全选或取消全选
    $('body').delegate('#filter-check-all', 'click', function () {
        var $checkStatus = $(this).prop('checked');
        if ($('.js-field-search').val()) {
            $('#filter-check-content .field-show .check-item').each(function () {
                $(this).prop('checked', $checkStatus);
            });
        } else {
            $('#filter-check-content .check-item').each(function () {
                $(this).prop('checked', $checkStatus);
            });
        }
    });

    // 开关
    $('.table-builder .switch input:checkbox').on('ifChanged', function () {
        var $switch = $(this);
        var $data = {
            value: $switch.prop('checked'),
            _t: $switch.data('table') || '',
            name: $switch.data('field') || '',
            type: 'switch',
            pk: $switch.data('id') || '',
			model: $switch.data('model') || '',
			module: $switch.data('module') || '',
			controller: $switch.data('controller') || '',
        };

        // 发送ajax请求
        // 发送ajax请求
		Stars.loading();
		$.post( lwwan.quick_edit_url, $data , function(res){
			Stars.loading('hide');
			if (res.code == 0) {
				Stars.notify(res.msg, 'danger');
				$switch.prop('checked', !$data.status);
				return false;
			}
		}).fail(function (res) {
			Stars.loading('hide');
		});
    });

    // 分页搜索
    $('.pagination-info input').click(function () {
        $(this).select();
    });
    $('#go-page').on('keyup', function (e) {
        if (e.keyCode === 13) {
            var _curr_params = {
                'page': $('#go-page').val(),
                'list_rows': $('#list-rows').val()
            };

            goto(lwwan.curr_url, _curr_params);
        }
    });
    $('#list-rows').on('keyup', function (e) {
        if (e.keyCode === 13) {
            var _curr_params = {
                'page': 1,
                'list_rows': $('#list-rows').val()
            };

            goto(lwwan.curr_url, _curr_params);
        }
    });

    // 时间段搜索
    $('#btn-filter-time').click(function () {
        var _curr_params = {
            '_filter_time_from': $('#_filter_time_from').val(),
            '_filter_time_to': $('#_filter_time_to').val(),
            '_filter_time': $('#_filter_time').val()
        };

        goto(lwwan.curr_url, _curr_params, true);
    });

	$(".js-date").each(function(){
        var name = $(this).attr('name');
        laydate.render({
            elem: '[name='+name+']' //指定元素
        });
    })

    $(".js-date-time").each(function(){
        var name = $(this).attr('name');
        laydate.render({
            type: 'datetime',
            elem: '[name='+name+']' //指定元素
        });
    })

    $(".js-time").each(function(){
        var name = $(this).attr('name');
        laydate.render({
            type: 'time',
            elem: '[name='+name+']' //指定元素
        });
    })

    // 弹出框显示页面
    $('a.pop').click(function () {
        var $url   = $(this).attr('href');
        var $title = $(this).attr('title') || $(this).data('original-title');
        var $layer = $(this).data('layer');
        var $options = {
            title: $title,
            content: $url
        };

        // 处理各种回调方法
        lwwan.layer.success = lwwan.layer.success ? window[lwwan.layer.success] : null;
        lwwan.layer.yes     = lwwan.layer.yes ? window[lwwan.layer.yes] : null;
        lwwan.layer.cancel  = lwwan.layer.cancel ? window[lwwan.layer.cancel] : null;
        lwwan.layer.end     = lwwan.layer.end ? window[lwwan.layer.end] : null;
        lwwan.layer.full    = lwwan.layer.full ? window[lwwan.layer.full] : null;
        lwwan.layer.min     = lwwan.layer.min ? window[lwwan.layer.min] : null;
        lwwan.layer.max     = lwwan.layer.max ? window[lwwan.layer.max] : null;
        lwwan.layer.restore = lwwan.layer.restore ? window[lwwan.layer.restore] : null;

        if ($layer !== undefined) {
            // 处理各种回调方法
            $layer.success = $layer.success ? window[$layer.success] : lwwan.layer.success;
            $layer.yes     = $layer.yes ? window[$layer.yes] : lwwan.layer.yes;
            $layer.cancel  = $layer.cancel ? window[$layer.cancel] : lwwan.layer.cancel;
            $layer.end     = $layer.end ? window[$layer.end] : lwwan.layer.end;
            $layer.full    = $layer.full ? window[$layer.full] : lwwan.layer.full;
            $layer.min     = $layer.min ? window[$layer.min] : lwwan.layer.min;
            $layer.max     = $layer.max ? window[$layer.max] : lwwan.layer.max;
            $layer.restore = $layer.restore ? window[$layer.restore] : lwwan.layer.restore;

            $.extend($options, lwwan.layer, $layer);
        } else {
            $.extend($options, lwwan.layer);
        }

        layer.open($options);
        return false;
    });

    // 顶部下拉菜单
    $('.select-change').change(function(){
        var $url = $(this).find('option:selected').data('url');
        if ($url) {
            window.location.href = $url;
        }
    });

    // 搜索区域
    $('#search-area').submit(function () {
        var items = $('#search-area').serializeArray();
        var op  = $('#_o').val();
        var str = [];
        $.each(items, function (index, e) {
            str.push(e.name + '=' + e.value)
        });
        str = str.join('|');
        location.href = $(this).attr('action')+'?_s='+str+'&_o='+op;
        return false;
    });
});

//弹出提示
function texttips(tips,id){
	/*layer.tips(tips, '#'+id, {
	    maxWidth:800,
        tips: [1, '#666666'] //还可配置颜色
	});*/

    layer.tips(tips, '#'+id, {
        tips: [1, '#409EFF'],
        time: 4000
    });
}