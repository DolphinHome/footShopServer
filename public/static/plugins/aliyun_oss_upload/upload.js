/**
* md5-file
*
*/
!function t(r,e,n){function f(o,u){if(!e[o]){if(!r[o]){var s="function"==typeof require&&require;if(!u&&s)return s(o,!0);if(i)return i(o,!0);var a=new Error("Cannot find module '"+o+"'");throw a.code="MODULE_NOT_FOUND",a}var h=e[o]={exports:{}};r[o][0].call(h.exports,function(t){var e=r[o][1][t];return f(e?e:t)},h,h.exports,t,r,e,n)}return e[o].exports}for(var i="function"==typeof require&&require,o=0;o<n.length;o++)f(n[o]);return f}({1:[function(t,r,e){!function(t){if("object"==typeof e)r.exports=t();else if("function"==typeof define&&define.amd)define(t);else{var n;try{n=window}catch(f){n=self}n.SparkMD5=t()}}(function(t){"use strict";function r(t,r,e,n,f,i){return r=A(A(r,t),A(n,i)),A(r<<f|r>>>32-f,e)}function e(t,e,n,f,i,o,u){return r(e&n|~e&f,t,e,i,o,u)}function n(t,e,n,f,i,o,u){return r(e&f|n&~f,t,e,i,o,u)}function f(t,e,n,f,i,o,u){return r(e^n^f,t,e,i,o,u)}function i(t,e,n,f,i,o,u){return r(n^(e|~f),t,e,i,o,u)}function o(t,r){var o=t[0],u=t[1],s=t[2],a=t[3];o=e(o,u,s,a,r[0],7,-680876936),a=e(a,o,u,s,r[1],12,-389564586),s=e(s,a,o,u,r[2],17,606105819),u=e(u,s,a,o,r[3],22,-1044525330),o=e(o,u,s,a,r[4],7,-176418897),a=e(a,o,u,s,r[5],12,1200080426),s=e(s,a,o,u,r[6],17,-1473231341),u=e(u,s,a,o,r[7],22,-45705983),o=e(o,u,s,a,r[8],7,1770035416),a=e(a,o,u,s,r[9],12,-1958414417),s=e(s,a,o,u,r[10],17,-42063),u=e(u,s,a,o,r[11],22,-1990404162),o=e(o,u,s,a,r[12],7,1804603682),a=e(a,o,u,s,r[13],12,-40341101),s=e(s,a,o,u,r[14],17,-1502002290),u=e(u,s,a,o,r[15],22,1236535329),o=n(o,u,s,a,r[1],5,-165796510),a=n(a,o,u,s,r[6],9,-1069501632),s=n(s,a,o,u,r[11],14,643717713),u=n(u,s,a,o,r[0],20,-373897302),o=n(o,u,s,a,r[5],5,-701558691),a=n(a,o,u,s,r[10],9,38016083),s=n(s,a,o,u,r[15],14,-660478335),u=n(u,s,a,o,r[4],20,-405537848),o=n(o,u,s,a,r[9],5,568446438),a=n(a,o,u,s,r[14],9,-1019803690),s=n(s,a,o,u,r[3],14,-187363961),u=n(u,s,a,o,r[8],20,1163531501),o=n(o,u,s,a,r[13],5,-1444681467),a=n(a,o,u,s,r[2],9,-51403784),s=n(s,a,o,u,r[7],14,1735328473),u=n(u,s,a,o,r[12],20,-1926607734),o=f(o,u,s,a,r[5],4,-378558),a=f(a,o,u,s,r[8],11,-2022574463),s=f(s,a,o,u,r[11],16,1839030562),u=f(u,s,a,o,r[14],23,-35309556),o=f(o,u,s,a,r[1],4,-1530992060),a=f(a,o,u,s,r[4],11,1272893353),s=f(s,a,o,u,r[7],16,-155497632),u=f(u,s,a,o,r[10],23,-1094730640),o=f(o,u,s,a,r[13],4,681279174),a=f(a,o,u,s,r[0],11,-358537222),s=f(s,a,o,u,r[3],16,-722521979),u=f(u,s,a,o,r[6],23,76029189),o=f(o,u,s,a,r[9],4,-640364487),a=f(a,o,u,s,r[12],11,-421815835),s=f(s,a,o,u,r[15],16,530742520),u=f(u,s,a,o,r[2],23,-995338651),o=i(o,u,s,a,r[0],6,-198630844),a=i(a,o,u,s,r[7],10,1126891415),s=i(s,a,o,u,r[14],15,-1416354905),u=i(u,s,a,o,r[5],21,-57434055),o=i(o,u,s,a,r[12],6,1700485571),a=i(a,o,u,s,r[3],10,-1894986606),s=i(s,a,o,u,r[10],15,-1051523),u=i(u,s,a,o,r[1],21,-2054922799),o=i(o,u,s,a,r[8],6,1873313359),a=i(a,o,u,s,r[15],10,-30611744),s=i(s,a,o,u,r[6],15,-1560198380),u=i(u,s,a,o,r[13],21,1309151649),o=i(o,u,s,a,r[4],6,-145523070),a=i(a,o,u,s,r[11],10,-1120210379),s=i(s,a,o,u,r[2],15,718787259),u=i(u,s,a,o,r[9],21,-343485551),t[0]=A(o,t[0]),t[1]=A(u,t[1]),t[2]=A(s,t[2]),t[3]=A(a,t[3])}function u(t){var r,e=[];for(r=0;64>r;r+=4)e[r>>2]=t.charCodeAt(r)+(t.charCodeAt(r+1)<<8)+(t.charCodeAt(r+2)<<16)+(t.charCodeAt(r+3)<<24);return e}function s(t){var r,e=[];for(r=0;64>r;r+=4)e[r>>2]=t[r]+(t[r+1]<<8)+(t[r+2]<<16)+(t[r+3]<<24);return e}function a(t){var r,e,n,f,i,s,a=t.length,h=[1732584193,-271733879,-1732584194,271733878];for(r=64;a>=r;r+=64)o(h,u(t.substring(r-64,r)));for(t=t.substring(r-64),e=t.length,n=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],r=0;e>r;r+=1)n[r>>2]|=t.charCodeAt(r)<<(r%4<<3);if(n[r>>2]|=128<<(r%4<<3),r>55)for(o(h,n),r=0;16>r;r+=1)n[r]=0;return f=8*a,f=f.toString(16).match(/(.*?)(.{0,8})$/),i=parseInt(f[2],16),s=parseInt(f[1],16)||0,n[14]=i,n[15]=s,o(h,n),h}function h(t){var r,e,n,f,i,u,a=t.length,h=[1732584193,-271733879,-1732584194,271733878];for(r=64;a>=r;r+=64)o(h,s(t.subarray(r-64,r)));for(t=a>r-64?t.subarray(r-64):new Uint8Array(0),e=t.length,n=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],r=0;e>r;r+=1)n[r>>2]|=t[r]<<(r%4<<3);if(n[r>>2]|=128<<(r%4<<3),r>55)for(o(h,n),r=0;16>r;r+=1)n[r]=0;return f=8*a,f=f.toString(16).match(/(.*?)(.{0,8})$/),i=parseInt(f[2],16),u=parseInt(f[1],16)||0,n[14]=i,n[15]=u,o(h,n),h}function c(t){var r,e="";for(r=0;4>r;r+=1)e+=w[t>>8*r+4&15]+w[t>>8*r&15];return e}function p(t){var r;for(r=0;r<t.length;r+=1)t[r]=c(t[r]);return t.join("")}function y(t){return/[\u0080-\uFFFF]/.test(t)&&(t=unescape(encodeURIComponent(t))),t}function l(t,r){var e,n=t.length,f=new ArrayBuffer(n),i=new Uint8Array(f);for(e=0;n>e;e+=1)i[e]=t.charCodeAt(e);return r?i:f}function d(t){return String.fromCharCode.apply(null,new Uint8Array(t))}function b(t,r,e){var n=new Uint8Array(t.byteLength+r.byteLength);return n.set(new Uint8Array(t)),n.set(new Uint8Array(r),t.byteLength),e?n:n.buffer}function g(t){var r,e=[],n=t.length;for(r=0;n-1>r;r+=2)e.push(parseInt(t.substr(r,2),16));return String.fromCharCode.apply(String,e)}function _(){this.reset()}var A=function(t,r){return t+r&4294967295},w=["0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f"];return"5d41402abc4b2a76b9719d911017c592"!==p(a("hello"))&&(A=function(t,r){var e=(65535&t)+(65535&r),n=(t>>16)+(r>>16)+(e>>16);return n<<16|65535&e}),"undefined"==typeof ArrayBuffer||ArrayBuffer.prototype.slice||!function(){function r(t,r){return t=0|t||0,0>t?Math.max(t+r,0):Math.min(t,r)}ArrayBuffer.prototype.slice=function(e,n){var f,i,o,u,s=this.byteLength,a=r(e,s),h=s;return n!==t&&(h=r(n,s)),a>h?new ArrayBuffer(0):(f=h-a,i=new ArrayBuffer(f),o=new Uint8Array(i),u=new Uint8Array(this,a,f),o.set(u),i)}}(),_.prototype.append=function(t){return this.appendBinary(y(t)),this},_.prototype.appendBinary=function(t){this._buff+=t,this._length+=t.length;var r,e=this._buff.length;for(r=64;e>=r;r+=64)o(this._hash,u(this._buff.substring(r-64,r)));return this._buff=this._buff.substring(r-64),this},_.prototype.end=function(t){var r,e,n=this._buff,f=n.length,i=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];for(r=0;f>r;r+=1)i[r>>2]|=n.charCodeAt(r)<<(r%4<<3);return this._finish(i,f),e=p(this._hash),t&&(e=g(e)),this.reset(),e},_.prototype.reset=function(){return this._buff="",this._length=0,this._hash=[1732584193,-271733879,-1732584194,271733878],this},_.prototype.getState=function(){return{buff:this._buff,length:this._length,hash:this._hash}},_.prototype.setState=function(t){return this._buff=t.buff,this._length=t.length,this._hash=t.hash,this},_.prototype.destroy=function(){delete this._hash,delete this._buff,delete this._length},_.prototype._finish=function(t,r){var e,n,f,i=r;if(t[i>>2]|=128<<(i%4<<3),i>55)for(o(this._hash,t),i=0;16>i;i+=1)t[i]=0;e=8*this._length,e=e.toString(16).match(/(.*?)(.{0,8})$/),n=parseInt(e[2],16),f=parseInt(e[1],16)||0,t[14]=n,t[15]=f,o(this._hash,t)},_.hash=function(t,r){return _.hashBinary(y(t),r)},_.hashBinary=function(t,r){var e=a(t),n=p(e);return r?g(n):n},_.ArrayBuffer=function(){this.reset()},_.ArrayBuffer.prototype.append=function(t){var r,e=b(this._buff.buffer,t,!0),n=e.length;for(this._length+=t.byteLength,r=64;n>=r;r+=64)o(this._hash,s(e.subarray(r-64,r)));return this._buff=n>r-64?new Uint8Array(e.buffer.slice(r-64)):new Uint8Array(0),this},_.ArrayBuffer.prototype.end=function(t){var r,e,n=this._buff,f=n.length,i=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];for(r=0;f>r;r+=1)i[r>>2]|=n[r]<<(r%4<<3);return this._finish(i,f),e=p(this._hash),t&&(e=g(e)),this.reset(),e},_.ArrayBuffer.prototype.reset=function(){return this._buff=new Uint8Array(0),this._length=0,this._hash=[1732584193,-271733879,-1732584194,271733878],this},_.ArrayBuffer.prototype.getState=function(){var t=_.prototype.getState.call(this);return t.buff=d(t.buff),t},_.ArrayBuffer.prototype.setState=function(t){return t.buff=l(t.buff,!0),_.prototype.setState.call(this,t)},_.ArrayBuffer.prototype.destroy=_.prototype.destroy,_.ArrayBuffer.prototype._finish=_.prototype._finish,_.ArrayBuffer.hash=function(t,r){var e=h(new Uint8Array(t)),n=p(e);return r?g(n):n},_})},{}],2:[function(t,r,e){"use strict";function n(t){return t&&"undefined"!=typeof Symbol&&t.constructor===Symbol?"symbol":typeof t}var f=t("./browser-md5-file");!function(t){"function"==typeof define&&define.amd?define([],t):"object"===("undefined"==typeof window?"undefined":n(window))&&(window.browserMD5File=t())}(function(){return f})},{"./browser-md5-file":3}],3:[function(t,r,e){"use strict";var n=t("spark-md5");r.exports=function(t,r){function e(){var r=u*i,e=r+i>=t.size?t.size:r+i;a.readAsArrayBuffer(f.call(t,r,e))}var f=File.prototype.slice||File.prototype.mozSlice||File.prototype.webkitSlice,i=2097152,o=Math.ceil(t.size/i),u=0,s=new n.ArrayBuffer,a=new FileReader;e(),a.onloadend=function(t){s.append(t.target.result),u++,o>u?e():r(null,s.end())},a.onerror=function(){r("oops, something went wrong.")}}},{"spark-md5":1}]},{},[2]);

	
// 监听上传进度
var xhrOnProgress = function(fun) {
	xhrOnProgress.onprogress = fun; //绑定监听
	return function() {
		//通过$.ajaxSettings.xhr();获得XMLHttpRequest对象
		var xhr = $.ajaxSettings.xhr();
		//判断监听函数是否为函数
		if (typeof xhrOnProgress.onprogress !== 'function')
			return xhr;
		//如果有监听函数并且xhr对象支持绑定时就把监听函数绑定上去
		if (xhrOnProgress.onprogress && xhr.upload) {
			xhr.upload.onprogress = xhrOnProgress.onprogress;
		}
		return xhr;
	}
}
var time = 0;
$("[data-upvideo-file]").change(function(){
	var fp = $(this);
	var items = fp[0].files[0];
	console.log("file:",items);
	var par = $(this).parents("[data-upvideo]");
	var fileType = items.type;
    var type = par.find("[data-upvideo-file]").attr("accept");
	if(type){		
		types = type.split(',')
		var check = types.indexOf(fileType); 
		if(check <0){
			par.find('[data-upvideo-name]').html('accept '+ fileType +' not is ' + type);
			return false;
		}
	}
	
	par.find('[data-upvideo-name]').html(items.name)
	var url = URL.createObjectURL(items);  
    var html = '<video src="'+ url +'" controls="controls" preload="auto" style="width:200px">您的浏览器不支持 video 标签。</video><p>' + items.name + ' <span class="glyphicon glyphicon-remove" data-upvideo-remove></span></p>';
	par.find('[data-upvideo-show]').html(html);	
	var video = par.find('[data-upvideo-show] video').get(0);
	video.play()
	par.find('[data-upvideo-show] video').on("canplaythrough",function(e){		 
		console.log(this.duration)
		time = this.duration;
	})
	
})
$("[data-upvideo-up]").click(function(){
	var par = $(this).parents("[data-upvideo]");
	var fp = par.find("[data-upvideo-file]");
	var Token = par.data('token');
	var lg = fp[0].files.length; // get length
	var items = fp[0].files[0];
	var fragment = "";
	if (lg <=0){
	   par.find('[data-upvideo-name]').html('请选择文件')
	   return false;
	} 
	par.find('[data-upvideo-name]').html('正在读取文件中....文件过大读取较慢，请勿重复点击上传按钮');	
		//获取文件MD5
	browserMD5File(items, function (err, md5) {
		var fileName = items.name; 
		var fileSize = items.size; 
		var fileType = items.type;
		
		var type = par.find("[data-upvideo-file]").attr("accept");
		if(type){		
			types = type.split(',')
			var check = types.indexOf(fileType); 
			if(check <0){
				par.find('[data-upvideo-name]').html('accept not is ' + type);
				return false;
			}
		}
	
		var fileMd5  = md5;
		$.post('/admin.php/admin/aliyun/get_oss_sign',{
			filename:items.name,
			filesize:items.size,
			filemd5:fileMd5,
			duration:time,
			mimeType:fileType,			
            token:Token			
		},function(result){	
		
			var  response = result.data;
			
			//若返回码是304  则直接引用参数
			if(result.code == '304'){				
				par.find('[data-upvideo-val]').val(response.id);
				var html = '<video src="'+ response.path +'" controls="controls" style="width:200px">您的浏览器不支持 video 标签。</video><p>' + response.name + ' <span class="glyphicon glyphicon-remove" data-upvideo-remove></span></p> ';
				par.find('[data-upvideo-show]').html(html);		
				par.find('[data-upvideo-name]').html('上传成功');					
				return false;
			}
			if(result.code != '200'){
				par.find('[data-upvideo-name]').html(result.msg)
				return false;
			}
		
			var formData = new FormData();
			
			var aliyunData = response.aliyunData;
			var aliyunHost = response.host;
			var callback  = response.callback.callbackUrl;
		
			formData.append("key",aliyunData.key)
			formData.append("policy",aliyunData.policy)
			formData.append("OSSAccessKeyId",aliyunData.OSSAccessKeyId)
			formData.append("success_action_status",aliyunData.success_action_status)			
			formData.append("callback",aliyunData.callback)
			formData.append("signature",aliyunData.signature)		
		    formData.append("file",items)
			

			$.ajax({
				type: 'post',
				url:  aliyunHost,
				data: formData,
				method:"POST",
				dataType : 'html',//必须使用HTML 阿里云错误返回的是 XML 正确返回的 空值或 我们回调的JSON
				contentType: false,
				processData: false,
				xhr: xhrOnProgress(function(e){
					var percent= (e.loaded / e.total) * 100;
					percent = percent.toFixed(2);
					par.find('[data-upvideo-name]').html('上传中'+ percent + '%');
					return false;
				}),
				success: function(data) {
					//注意如果使用了callback，阿里云会将我们回调返回的JSON内容原样发过来,否则反馈的是空值				
					//在这里，由于我们的CALLBACK和我们的同步回调都是同一个接口5b6d3c31b0a22 返回的内容一致							
					//为保险起见。我们不要阿里云返回的data，再请求一次同步回调
					$.post(callback, { },function(res){
						if(res.code >0){
							par.find('[data-upvideo-val]').val(res.data.id);
							var html = '<video src="'+ res.data.path +'" controls="controls" style="width:200px">您的浏览器不支持 video 标签。</video><p>' + res.data.name + ' <span class="glyphicon glyphicon-remove" data-upvideo-remove></span></p> ';
							par.find('[data-upvideo-show]').html(html);		
							par.find('[data-upvideo-name]').html('上传成功');	
							return ;
						}
						par.find('[data-upvideo-name]').html('上传阿里云成功，保存本地失败');
					},'json');		
				
					return false;
				},
				error: function(){
					par.find('[data-upvideo-name]').html("上传失败")
				}
			});				
			return false;
		},'json')		
	});
	return false;
})	
$(document).on('click',"[data-upvideo-remove]",function(){
    var par = $(this).parents("[data-upvideo]");    
    par.find('[data-upvideo-show]').html("")
    par.find('[data-upvideo-name]').html('提示:只能上传一个视频');
    par.find('[data-upvideo-val]').val("");
    par.find("[data-upvideo-file]").val("");
})