$(function($) {
    if($("#activity_id").val()!=0){
        $.ajax({
            url:"/admin.php/goods/activity_details/getGoodsByCid",
            data:"activity_id="+$("#activity_id").val(),
            dataType:"json",
            type:"post",
            success:function(data){
                $('#sku').html('<tr class="table-empty"><td class="text-center empty-info" colspan="30"><i class="fa fa-database"></i> 抱歉，没有更多了 <br></td></tr>');
                var op='<option value="0">--请选择--</option>';
                if(data.code==1){
                    for(var i in data.list){
                        op+='<option value="'+data.list[i].id+'">商品货号：'+data.list[i].sn+ '名称：'+data.list[i].name+'</option>';
                    }
                    $('#goods_id').html(op);
                }else{
                    layer(data.msg,function(){})
                }
            }
        });
    }
    $("#activity_id").change(function(){
        $.ajax({
            url:"/admin.php/goods/activity_details/getGoodsByCid",
            data:"activity_id="+$(this).val(),
            dataType:"json",
            type:"post",
            success:function(data){
                $('#sku').html('<tr class="table-empty"><td class="text-center empty-info" colspan="30"><i class="fa fa-database"></i> 抱歉，没有更多了 <br></td></tr>');
                var op='<option value="0">--请选择--</option>';
                if(data.code==1){
                    for(var i in data.list){
                        op+='<option value="'+data.list[i].id+'">商品货号：'+data.list[i].sn+ ' 名称：'+data.list[i].name+'</option>';
                    }
                    $('#goods_id').html(op);
                }else{
                    layer(data.msg,function(){})
                }
            }
        });
    });
    $("select[name='sku_id']").change(function(){
        var sku_id=$(this).val();
        $.ajax({
            url:"/admin.php/goods/activity_details/getSkuPrice",
            data:"sku_id="+sku_id,
            dataType:"json",
            type:"post",
            success:function(data){
                if(data.code==1){
                    $("#shop_price").val(data.data.shop_price);
                    $("#stock_all").val(data.data.stock);

                }else{
                    layer(data.msg,function(){})
                }
            }
        });
    });
    $("#discount_way").change(function(){
        if($(this).val()==0){
            $('#deal_value').val(10).parent().parent().find('div[for="deal_value"]').html("减价值");
        }else if($(this).val()==1){
            $('#deal_value').val(70).parent().parent().find('div[for="deal_value"]').html("打折值 %");
        }
    })

    $("#goods_id").change(function(){
        var type = parseInt($("#activity_type").val());
        $.ajax({
            url:"/admin.php/goods/activity_details/getSku",
            data:"goods_id="+$(this).val(),
            dataType:"json",
            type:"post",
            success:function(data){
                console.log(data);
                console.log(type);
                if(data.code==1){

                    if(data.is_goods==0){
                        console.log("23123");
                        var tr='';var th='';
                        for(var key in data.list){
                            tr+='<tr style="text-align:center;">';
                            tr+='<td>'+data.list[key].key_name+'</td>';
                            tr+='<td class="stock_all">'+data.list[key].stock+'</td>';
                            tr+='<td>'+data.list[key].cost_price+'</td>';
                            tr+='<td>'+data.list[key].shop_price+'</td>';
                            tr+='<td>';
                            tr+='<input type="hidden" name="sku_id[]" value="'+data.list[key].sku_id+'">';
                            tr+='<input type="number" class="stock" min="0" style="width:80px;" name="stock[]" value="'+data.list[key].stock+'">';
                            tr+='</td>';
                            tr+='<td><input type="number" class="price" min="0.00" step="0.01" style="width:80px;" name="price[]" value="'+data.list[key].shop_price+'"></td>';
                            if(type != 5){
                                tr+='<input type="hidden" class="price" min="0.00" step="0.01" style="width:80px;" name="member_price[]" value="'+data.list[key].shop_price+'">';
                                // 预售金额 兼容后续会员价
                                tr+='<input type="hidden" class="price" min="0.00" step="0.01" style="width:80px;" name="member_price2[]" value="'+data.list[key].shop_price+'">' +
                                    '<td><input type="number" class="price" min="1" style="width:80px;" name="limit[]" value="1"></td>';
                            }
                            if( type == 8 ){
                                // tr+='<td><input type="number" class="price" min="0.00" step="0.01" style="width:80px;" name="member_price[]" value="'+data.list[key].shop_price+'"></td>';
                                tr+='<td><input type="number" class="price" min="0" step="0" style="width:80px;" name="sales_integral[]" value="0"></td>';
                                tr+='<td><input type="checkbox" class="contactChoice1" name="is_pure_integral[]" value="'+data.list[key].sku_id+'"></td>';

                            }
                            if( type == 10 ){
                                tr+='<td><input type="number" class="price" min="0" step="0" style="width:80px;" name="bargain_max[]" value="0"></td>';
                                tr+='<td><input type="number" class="price" min="0" step="0" style="width:70px;" name="bargain_min[]" value="0"></td>';
                                tr+='<td><input type="number" class="" min="0" step="0" style="width:70px;" name="least_count[]" value="0"></td>';
                            }
                            tr+='<td><a class="del_sku" href="javascript:void(0)">删除</a></td>';
                            tr+='</tr>';
                        }
                        if(type == 3){
                            th+='<th class="pd10" style="text-align:center;">名称</th><th style="text-align:center;">库存</th><th style="text-align:center;">成本价</th><th style="text-align:center;">出售价格</th><th style="text-align:center;">活动库存</th><th style="text-align:center;">预售定金</th><th style="text-align:center;">限买件数</th><th style="text-align:center;">操作</th>';
                        }
                        else if (type == 5){
                            th+='<th class="pd10" style="text-align:center;">名称</th><th style="text-align:center;">库存</th><th style="text-align:center;">成本价</th><th style="text-align:center;">出售价格</th><th style="text-align:center;">活动库存</th><th style="text-align:center;">最低砍至价格</th><th style="text-align:center;">操作</th>';
                        }else if (type == 2) {
                            th+='<th class="pd10" style="text-align:center;">名称</th><th style="text-align:center;">库存</th><th style="text-align:center;">成本价</th><th style="text-align:center;">出售价格</th><th style="text-align:center;">活动库存</th><th style="text-align:center;">普通活动价格</th><th style="text-align:center;">限买件数</th><th style="text-align:center;">操作</th>';
                        } else if(type == 8) { // 积分商品
                            th+='<th  width="50" style="text-align:center;">名称</th><th style="width: auto;">库存</th><th style="text-align:center;">成本价</th><th style="white-space:nowrap;padding:0 6px; text-align:center;">出售价格</th><th style="text-align:center;">活动库存</th><th style="text-align:center;">普通活动价格</th><th style="text-align:center;">限买件数</th><th style="text-align:center;">销售积分</th><th style="text-align:center;white-space:nowrap;">纯积分</th><th style="text-align:center;">操作</th>';
                        } else if(type == 10) {
                            th+='<th  width="50" style="text-align:center;">名称</th><th style="width: auto;">库存</th><th style="text-align:center;">成本价</th><th style="text-align:center;">出售价格</th><th style="text-align:center;">活动库存</th><th style="text-align:center;">普通活动价格</th><th style="text-align:center;">限买件数</th><th style="text-align:center;">第一刀最大占比(%)</th><th style="text-align:center;">第一刀最小占比(%)</th><th style="text-align:center;">砍价次数</th><th style="text-align:center;">操作</th>';

                        } else{
                            th+='<th class="pd10" style="text-align:center;">名称</th><th style="text-align:center;">库存</th><th style="text-align:center;">成本价</th><th style="text-align:center;">出售价格</th><th style="text-align:center;">活动库存</th><th style="text-align:center;">普通活动价格</th><th style="text-align:center;">限买件数</th><th style="text-align:center;">操作</th>';

                        }
                    }else{
                        console.log("23123");
                        for(var key in data.list){
                            tr+='<tr style="text-align:center;">';
                            tr+='<td class="stock_all">'+data.list[key].stock+'</td>';
                            tr+='<td>'+data.list[key].cost_price+'</td>';
                            tr+='<td>'+data.list[key].shop_price+'</td>';
                            tr+='<td>';
                            tr+='<input type="hidden" name="sku_id[]" value="'+data.list[key].sku_id+'">';
                            tr+='<input type="number" class="stock" min="0" style="width:70px;" name="stock[]" value="'+data.list[key].stock+'">';
                            tr+='</td>';
                            tr+='<td><input type="number" class="price" min="0.01" step="0.01" style="width:80px;" name="price[]" value="'+data.list[key].shop_price+'"></td>';
                            if(type != 5){
                                tr+='<input type="hidden" class="price" min="0.01" step="0.01" style="width:80px;" name="member_price[]" value="'+data.list[key].shop_price+'">';
                                tr+='<td><input type="hidden" class="price" min="0.01" step="0.01" style="width:80px;" name="member_price2[]" value="'+data.list[key].shop_price+'"><input type="number" class="price" min="1" style="width:80px;" name="limit[]" value="1"></td>';
                            }
                            if( type == 8 ){
                                // tr+='<td><input type="number" class="price" min="0.00" step="0.01" style="width:80px;" name="member_price[]" value="'+data.list[key].shop_price+'"></td>';
                                tr+='<td><input type="number" class="price" min="0" step="0" style="width:70px;" name="sales_integral[]" value="0"></td>';
                                tr+='<td><input type="checkbox" class="contactChoice1" name="is_pure_integral[]" value="'+data.list[key].sku_id+'"></td>';

                            }
                            if( type == 10 ){
                                tr+='<td><input type="number" class="price" min="0.00" step="0.01" style="width:80px;" name="bargain_max[]" value="0"></td>';
                                tr+='<td><input type="number" class="price" min="0" step="0" style="width:70px;" name="bargain_min[]" value="0"></td>';
                                tr+='<td><input type="number" class="" min="0" step="0" style="width:70px;" name="least_count[]" value="0"></td>';

                            }
                            tr+='</tr>';
                        }
                        if(type == 3){
                            th+='<th class="pd10" style="white-space:nowrap;text-align:center;">库存</th><th style="text-align:center;">成本价</th><th style="text-align:center;">出售价格</th><th style="text-align:center;">活动库存</th><th style="text-align:center;">预售定金</th><th style="text-align:center;">限买件数</th>';
                        }else if (type == 5){
                            th+='<th class="pd10" style="white-space:nowrap;text-align:center;">库存</th><th style="text-align:center;">成本价</th><th style="text-align:center;">出售价格</th><th style="text-align:center;">活动库存</th><th style="text-align:center;">最低砍至价格</th>';
                        } else if (type == 2) {
                            th+='<th class="pd10" style="white-space:nowrap;text-align:center;">库存</th><th style="text-align:center;">成本价</th><th style="text-align:center;">出售价格</th><th style="text-align:center;">活动库存</th><th style="text-align:center;">普通活动价格</th><th style="text-align:center;">限买件数</th>';
                        } else if(type == 8) {
                            th+='<th class="pd10" style="white-space:nowrap;text-align:center;">库存</th><th style="text-align:center;">成本价</th><th style="text-align:center;">出售价格</th><th style="text-align:center;">活动库存</th><th style="text-align:center;">普通活动价格</th><th style="text-align:center;">限买件数</th><th style="text-align:center;">销售积分</th><th style="text-align:center;">纯积分</th>';
                        } else if(type == 10) {
                            th+='<th  width="50">名称</th><th style="width: auto;">库存</th><th style="text-align:center;">成本价</th><th style="text-align:center;">出售价格</th><th style="text-align:center;">活动库存</th><th style="text-align:center;">普通活动价格</th><th style="text-align:center;">限买件数</th><th style="text-align:center;">第一刀最大占比(%)</th><th style="text-align:center;">第一刀最小占比(%)</th><th style="text-align:center;">砍价次数</th><th style="text-align:center;">操作</th>';
                        } else {

                            th+='<th class="pd10" style="white-space:nowrap;text-align:center;">库存</th><th style="text-align:center;">成本价</th><th style="text-align:center;">出售价格</th><th style="text-align:center;">活动库存</th><th style="text-align:center;">普通活动价格</th><th style="text-align:center;">限买件数</th>';
                        }
                    }
                    $('#sku').html(tr);
                    $('#th').html(th);
                }else{
                }
            }
        });
    })
    $('body').on('blur','.stock',function(){
        var val=$(this).val()==null||$(this).val()==''?0:$(this).val();
        $(this).val(parseInt(val));

        if($(this).val()<0){
            $(this).val(0);
        }
        var maxStock=parseInt($(this).parents('tr').find('.stock_all').html());
        if($(this).val()>maxStock){
            $(this).val(maxStock);
        }
    })

    $('body').on('blur','.price',function(){
        var val=$(this).val()==null||$(this).val()==''?0:$(this).val();
        $(this).val(parseFloat(val));
        if(isNaN($(this).val())){
            $(this).val('0.01');
        }
        if($(this).val()<0){
            $(this).val('0.01');
        }
    })

    $('body').on('keyup','.price',function(){
        var obj=$(this);
        obj.val(obj.val().replace(/[^\d.]/g, ""));  //清除“数字”和“.”以外的字符
        obj.val(obj.val().replace(/\.{2,}/g, ".")); //只保留第一个. 清除多余的
        obj.val(obj.val().replace(".", "$#$").replace(/\./g, "").replace("$#$", "."));
        obj.val(obj.val().replace(/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3'));//只能输入两个小数
        if (obj.val().indexOf(".") < 0 && obj.val() != "") {//以上已经过滤，此处控制的是如果没有小数点，首位不能为类似于 01、02的金额
            obj.val(parseFloat(obj.val()));
        }
    })

    $('body').on('keyup','.stock',function(){
        var c=$(this);
        if(/[^\d]/.test(c.val())){//替换非数字字符
            var temp_amount=c.val().replace(/[^\d]/g,'');
            $(this).val(temp_amount);
        }
    })
    $('body').on('click','.del_sku',function(){
        $(this).parents('tr').remove();
    })


});
