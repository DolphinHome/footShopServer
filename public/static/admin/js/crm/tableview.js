$(function($) {
	$('body').on('dblclick','tr',function () {
		return false;
		
    	var href = $(this).find('a').eq(0).attr('href');
    // console.log(href);
    	location.href = href;
	})
})
