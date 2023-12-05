$(function() {
	$('body').on('dblclick','tr',function () {
    	var href = $(this).find('a').eq(0).attr('href');
    	location.href = href;
	})
	$('.add_info').click(function(){
	    var html ='<tr>';
		    html+='<td><input class="form-control" type="text" name="use[]" value=""></td>';
			html+='<td><input class="form-control" type="text" name="mon[]" value="" placeholder="请输入"></td>';
			html+='<td><a class="del_tr" href="javascript:void(0)">删除</a></td>';
		    html+='</tr>';
		$('#details').append(html);						  
	})
	$('body').on('click','.del_tr',function () {
    	$(this).parents('tr').remove();
	})
	var data='';
	if($('#approver').attr('data-json')){
	 	data=$('#approver').attr('data-json').split(',');
	}
	$('#approver').select2({
    	language: 'zh-CN',
    	width: '100%',	
		placeholder:'',
   // maximumSelectionLength: 10
	});
	if(data){
	   $("#approver").val(data).trigger("change");
	}
	$("#approver").on("select2:select",function(e){
	   var uid=e.params.data.id;
	   var account_id=$("#approver").attr('account_id');
	   $.ajax({
	    	type:"POST",
			url:"/admin.php/crm/reimbursement/setapprove",
			data:"setType=add&uid="+uid+'&account_id='+account_id,
			dataType:"json",
			success:function(result) {
                
            },
	   })										 										
	}); 
// 移除完毕事件。配置allowClear: true后触发
	$("#approver").on("select2:unselect", function(e) {
	    var uid=e.params.data.id;
	   var account_id=$("#approver").attr('account_id');
	   $.ajax({
	    	type:"POST",
			url:"/admin.php/crm/reimbursement/setapprove",
			data:"setType=del&uid="+uid+'&account_id='+account_id,
			dataType:"json",
			success:function(result) {
                
            },
	   })															  
	}); 
})
