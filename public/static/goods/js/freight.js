(function () {

    /***
     * 配送区域表格
     * @param param
     * @constructor
     */
    function Delivery(param) {
        this.tableElement = param.table;
        this.RegionalChoice = new RegionalChoice(param.regional, param.datas);
        this.initCreateRegion();
        this.clickEditEvent();
        this.clickDeleteEvent();
        this.clickMethodEvent();
    }
    Delivery.prototype = {

        /**
         * 初始化添加区域事件
         */
        initCreateRegion: function () {
            var _this = this;
            $(_this.tableElement).find('.add-region').on('click',function () {
                // 渲染地域
                var str = '';
                $(_this.tableElement).find('input[type=hidden]').each(function (index, item) {
                    str += $(item).val() + ',';
                });
                var str2 =''

                $(_this.tableElement).find('input[type=hidden]').each(function (index, item) {
                    str2 += $(item).val() + ',';
                });
                console.log(str2)
                var  alreadyIds = str.length > 0 ? str.substring(0, str.length - 1).split(',') : [];
                console.log('创建事件：',alreadyIds)
                if (alreadyIds.length === 373) {
                    layer.msg('已经选择了所有区域~');
                    return false;
                }
                _this.RegionalChoice.render(alreadyIds);
                _this.showRegionalModal(function () {
                    // 弹窗交互完成
                    var Checked = _this.RegionalChoice.getCheckedContent();
                    let flag =false
                    alreadyIds.forEach(item1=>{
                        if( Checked.ids.includes(item1)){
                            layer.msg('你选择的区域包含重复的地址,请核对后再再添加~');
                            flag = true
                            return false
                        }
                    })
                 if(flag) return false
                    Checked.ids.length > 0 && _this.appendRulesTr(Checked.content, Checked.ids);
                });
            });
        },
 
        /**
         * 创建可配送区域规则
         */
        appendRulesTr: function (regionStr, checkedIds) {
            var $html = $(
                '<tr>' +
                '<td class="am-text-left">' +
                '   <p class="selected-content am-margin-bottom-xs">' +
                '   ' + regionStr +
                '   </p>' +
                '   <p class="operation am-margin-bottom-xs">' +
                '       <a class="edit" href="javascript:;">编辑</a>' +
                '       <a class="delete" href="javascript:;">删除</a>' +
                '   </p>' +
                '   <input type="hidden" name="freight[region][]" value="' + checkedIds + '">' +
                '</td>' +
                '<td>' +
                '   <input type="number" class="form-control input-common harf" name="freight[first][]" value="1" required>' +
                '</td>' +
                '<td>' +
                '   <input type="number" class="form-control input-common harf" name="freight[first_fee][]" value="0.00" required>' +
                '</td>' +
                '<td>' +
                '   <input type="number" class="form-control input-common harf" name="freight[additional][]" value="0">' +
                '</td>' +
                '<td>' +
                '   <input type="number" class="form-control input-common harf" name="freight[additional_fee][]" value="0.00">' +
                '</td>' +
                '</tr>'
            );
            $(this.tableElement).children().find('tr:last').before($html);
        },

        /**
         * 显示区域选择窗口
         * @param callback
         */
        showRegionalModal: function (callback) {
            var _this = this;
            layer.open({
                type: 1,
                shade: false,
                title: '选择可配送区域',
                btn: ['确定', '取消'],
                area: ['820px', '520px'], //宽高
                content: $('.regional-choice'),
                yes: function (index) {
                    callback && callback();
                    layer.close(index);
                },
                end: function () {
                    // 销毁已选中区域
                    console.log(1234)
                    _this.RegionalChoice.destroy();
                }
            });
        },

        /**
         * 编辑区域事件
         */
        clickEditEvent: function () {
            var _this = this
                , $table = $(_this.tableElement);
        
            $table.on('click', '.edit', function () {
                var str = '';
                $(_this.tableElement).find('input[type=hidden]').each(function (index, item) {
                    str += $(item).val() + ',';
                });
                var  alreadyIds = str.length > 0 ? str.substring(0, str.length - 1).split(',') : [];
                console.log('已删除事件',alreadyIds)
                // 渲染地域
                var $html = $(this).parent().parent()
                    , $content = $html.find('.selected-content')
                    , $input = $html.find('input[type=hidden]');
                    console.log( $input.val().split(','))
                _this.RegionalChoice.render(alreadyIds, $input.val().split(','));
                // 显示地区选择弹窗
                _this.showRegionalModal(function () {
                    // 弹窗交互完成
                    var Checked = _this.RegionalChoice.getCheckedContent();
                    if (Checked.ids.length > 0) {
                        $content.html(Checked.content);
                        $input.val(Checked.ids);
                    }
                });
            });
        },

        /**
         * 删除区域事件
         */
        clickDeleteEvent: function () {
            var $table = $(this.tableElement);
            var _this = this
            $table.on('click', '.delete', function () {
                var $delete = $(this);
                layer.confirm('确定要删除吗？', function (index) {
                    $delete.parent().parent().parent('tr').remove();
                    layer.close(index);
                });
            });
        },

        /**
         * 切换计费方式
         */
        clickMethodEvent: function () {
            $('input:radio[name="method"]').change(function (e) {
                var $first = $('.danwei-one')
                    , $additional = $('.danwei-two');
                if (e.currentTarget.value === '1')
                    $first.text('首重 (Kg)') && $additional.text('续重 (Kg)');
                else
                    $first.text('首件 (个)') && $additional.text('续件 (个)');
            });
        },

    };

    window.Delivery = Delivery;

})(window);
