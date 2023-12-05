$(function($) {
    if($("#activity_id").val()!=0){
        $.ajax({
            url:"/admin.php/goods/goods_question/getGoodsByCid",
            data:1,
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
    
    
    

});
