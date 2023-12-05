function comSelect(){
	$(document).on("coptionck",".gf-select select option",function(){
		$(this).closest(".gf-select").css("z-index",100);
		$(".gf-select select").hide();
		if($(this).next("select").children().length>4){
			$(this).next("select").css({"height":154,"overflow":"auto"});
		}else{
			$(this).next("select").css({"height":"auto"});
		}
		$(this).next("select").show();
	});
	$(document).on("coptionck",".gf-select select option",function(){
		var parent = $(this).closest("span").next("select");
		parent.hide();
		return false;
	});			
	$(document).on("coptionck",".gf-select select option",function(){
		var parent = $(this).closest("select");
		var select = $(this).closest(".gf-select");
		var value = $(this).attr("data-value");
		var text = $(this).text();
		if($(this).closest(".gf-select").hasClass("nocoptionck")){
			parent.hide();
			return false;
		}				
		select.css("z-index",1);
		select.find("em").html(text);
		select.find("input[type='hidden']").val(value!=0?value:"");
		parent.hide();
	});
	$(document).on("coptionck",function(e){
		if($(e.target).closest(".gf-select").length == 0){
			$(".gf-select").css("z-index",1);
			$(".gf-select select").hide();
		}
	});
}
function selectCity(options){
	var config = {
		domSelect : ["#province","#city","#area"],
		domInit : ["请选择省份","请选择城市","请选择区县"]
	}
	var opts = $.extend(config,options);
	var $jsondata = {};
	var provinceItemEvent = function(){
		var json = $jsondata;
		var item = ['<option>'+opts.domInit[1]+'</option>'];
		var name = $(this).attr("name");
		if(name && name != ""){
			var data = json["city"][name];

			for(var i=0;i<data.length;i++){
				item.push('<option data-value="'+data[i]["id"]+'" name="'+data[i]["id"]+'">'+data[i]["name"]+'</option>');
			}
			$(opts.domSelect[1]).find("select").html(item.join("\n"));
		}else{
			$(opts.domSelect[1]).find("select").html(item.join("\n"));
		}
		$(opts.domSelect[1]).find("select option:first").trigger("coptionck");
	}
	var cityItemEvent = function(){
		var json = $jsondata;
		var item = ['<option>'+opts.domInit[2]+'</option>'];
		var name = $(this).attr("name");
		if(name && name != ""){
			var data = json["district"][name];
			for(var i=0;i<data.length;i++){
				item.push('<option data-value="'+data[i]["id"]+'" name="'+data[i]["id"]+'">'+data[i]["name"]+'</option>');
			}
			$(opts.domSelect[2]).find("select").html(item.join("\n"));				
		}else{
			$(opts.domSelect[2]).find("select").html(item.join("\n"));							
		}
		$(opts.domSelect[2]).find("select option:first").trigger("coptionck");
	}
	var initSelectEvent = function(json){
		var item = ['<option>'+opts.domInit[0]+'</option>'];
		var data = json["province"];
		var initProvinVal = $(opts.domSelect[0]).find("input").val();
		var initCityVal = $(opts.domSelect[1]).find("input").val();
		var initAreaVal = $(opts.domSelect[2]).find("input").val();
		
		for(var i=0;i<data.length;i++){
			item.push('<option data-value="'+data[i]["id"]+'" name="'+data[i]["id"]+'">'+data[i]["name"]+'</option>');
		}
		$(opts.domSelect[0]).find("select").html(item.join("\n"));
		$jsondata = json;
		
		if(initProvinVal!=""){
			$(opts.domSelect[0]).find("select option[data-value='"+initProvinVal+"']").coptionck();
		}

		if(initCityVal!=""){
			$(opts.domSelect[1]).find("select option[data-value='"+initCityVal+"']").coptionck();
		}

		if(initAreaVal!=""){
			$(opts.domSelect[2]).find("select option[data-value='"+initAreaVal+"']").coptionck();
		}
	}
	var ajaxConfig = {
		url : "allcity.js",
		dataType : "jsonp",
		jsonpCallback : "callback",
		success : initSelectEvent				
	}
	$.ajax(ajaxConfig);
	$(document).on("coptionck",opts.domSelect[0]+" option",provinceItemEvent);
	$(document).on("coptionck",opts.domSelect[1]+" option",cityItemEvent);			
}