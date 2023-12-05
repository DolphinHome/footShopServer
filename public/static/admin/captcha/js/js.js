$(function () {
	var lMove = 1;
	var lMoveMousedown = 0;
	var lMoveLeftClick = 0; //点击位置
	var lMoveLeft = 0; //滑块位置
	var lCaptchaUrlGet; // 获取验证码图片地址
	var lCaptchaUrlCheck; // 验证验证码图片地址
	var check_success; // 验证回调

	$('.lCaptchaShow').click(function () {
		lCaptchaGet();
	})
	$('.lCaptchaClose').click(function () {
		$('.lCaptcha').hide();
	})
	$('.lCaptchaRefresh').click(function() {
		lCaptchaGet();
	})

	window.captchaInit = function (args) {
		lCaptchaUrlGet = args.lCaptchaUrlGet;
		lCaptchaUrlCheck = args.lCaptchaUrlCheck;
		check_success = args.check_success
		// 滑块移动
		var isTouchDevice = 'ontouchstart' in document.documentElement; // 是否支持触摸事件
		if (isTouchDevice) {
			$("#lCaptchaMoveIcon").on({
				touchstart: function (e) {
					// console.log('屏幕触摸')
					var touch = e.originalEvent.targetTouches[0];
					if (lMove == 0) {
						return false;
					}
					lMove = 0;
					lMoveMousedown = 1;
					lMoveLeftClick = touch.pageX;
				}
			})
			$("html").on({
				touchmove: function (e) {
					var touch = e.originalEvent.targetTouches[0];
					if (lMoveMousedown == 1) {
						lMoveLeft = touch.pageX - lMoveLeftClick;
						if (lMoveLeft < 0) {
							lMoveLeft = 0;
						}
						if (lMoveLeft > 244) {
							lMoveLeft = 244;
						}
						lCaptchaSet();
					}
				},
				touchend: function (e) {
					if (lMoveMousedown == 1) {
						lMoveMousedown = 0;
						lCaptchaCheck();
					}
				}
			})
		} else {
			$("#lCaptchaMoveIcon").on({
				mousedown: function (e) {
					// console.log('鼠标点击')
					if (lMove == 0) {
						return false;
					}
					lMove = 0;

					lMoveMousedown = 1;
					lMoveLeftClick = e.pageX;
				},
			})
			$("html").on({
				mousemove: function (e) {
					if (lMoveMousedown == 1) {
						lMoveLeft = e.pageX - lMoveLeftClick;
						if (lMoveLeft < 0) {
							lMoveLeft = 0;
						}
						if (lMoveLeft > 244) {
							lMoveLeft = 244;
						}
						lCaptchaSet();
					}
				},
				mouseup: function (e) {
					if (lMoveMousedown == 1) {
						lMoveMousedown = 0;
						lCaptchaCheck();
					}
				}
			})
		}
	}

	// 设置滑块位置
	function lCaptchaSet() {
		$("#lCaptchaMoveIcon").css('left', lMoveLeft)
		$("#lCaptchaImageIcon").css('left', lMoveLeft)
	}

	// 获取验证码
	function lCaptchaGet() {
		$.ajax({
			url: lCaptchaUrlGet,
			type: "post",
			dataType: "json",
			data: {},
			timeout: 30000,
			success: function (data) {
				// console.log(data);
				lMove = 1;
				lMoveMousedown = 0;
				lMoveLeftClick = 0; //点击位置
				lMoveLeft = 0; //滑块位置
				$("#lCaptchaImageBg").attr('src', data.bg)
				$("#lCaptchaImageIcon").attr('src', data.icon)
				lCaptchaSet();
				$('.lCaptcha').show();
			},
			error: function () {
				console.log(data);
			}
		});
	}

	// 验证验证码
	function lCaptchaCheck() {
		$.ajax({
			url: lCaptchaUrlCheck,
			type: "post",
			dataType: "json",
			data: {
				'left': lMoveLeft,
			},
			timeout: 30000,
			success: function (data) {
				$('.lCaptcha').hide();
				if (data.error) {
					$('.lCaptchaBtn').removeClass('success').addClass('error');
					$('.lCaptchaBtn').html('验证失败');
					return false;
				}
				$('.lCaptchaBtn').removeClass('error').addClass('success');
				$('.lCaptchaBtn').html('验证成功');
				$('#lCaptchaToken').val(data.token);
				if (check_success) check_success();
			},
			error: function () {
				console.log(data);
			}
		});
	}

	// 获取随机数
	function getRandomNumberByRange(start, end) {
		return Math.round(Math.random() * (end - start) + start)
	}
})
