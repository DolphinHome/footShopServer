//初始化ueditor
(function(){	
	$(".js-ueditor").each(function(i,e){		
		var id = $(e).attr("id")
	    var ue = UE.getEditor(id,{	
            serverUrl: '//' + location.host + '/admin.php/admin/upload/get_ueditor',  //此处请求服务器的地址			
		}).setHeight(400)
	})
	
})()