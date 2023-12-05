<?php
namespace app\admin\admin;

class Captcha
{
    private $captcha_img=[
        ['bg'=>'/static/admin/captcha/images/1.jpg','icon'=>'/static/admin/captcha/images/1.png','left'=>'184'],
        ['bg'=>'/static/admin/captcha/images/2.jpg','icon'=>'/static/admin/captcha/images/2.png','left'=>'185'],
        ['bg'=>'/static/admin/captcha/images/3.jpg','icon'=>'/static/admin/captcha/images/3.png','left'=>'131'],
        ['bg'=>'/static/admin/captcha/images/4.jpg','icon'=>'/static/admin/captcha/images/4.png','left'=>'90'],
        ['bg'=>'/static/admin/captcha/images/5.jpg','icon'=>'/static/admin/captcha/images/5.png','left'=>'84'],
        ['bg'=>'/static/admin/captcha/images/6.jpg','icon'=>'/static/admin/captcha/images/6.png','left'=>'139'],
        ['bg'=>'/static/admin/captcha/images/7.jpg','icon'=>'/static/admin/captcha/images/7.png','left'=>'153'],
        ['bg'=>'/static/admin/captcha/images/8.jpg','icon'=>'/static/admin/captcha/images/8.png','left'=>'177'],
        ['bg'=>'/static/admin/captcha/images/9.jpg','icon'=>'/static/admin/captcha/images/9.png','left'=>'138'],
        
    ];
    
    public function get()
    {
        $rander = array_rand($this->captcha_img);
        $captchaInfo = $this->captcha_img[$rander];
        
        session('CaptchaLeft', $captchaInfo['left']);

        $result = array();
        $result['success'] = lang('获取验证码');
        $result['bg'] = $captchaInfo['bg'];
        $result['icon'] = $captchaInfo['icon'];
        echo json_encode($result);
    }
    
    public function check()
    {
        $left = input('param.left', '0', 'intval');
        $CaptchaLeft = session('CaptchaLeft');
        if ($left >= $CaptchaLeft - 3 && $left <= $CaptchaLeft + 3) {
            $CaptchaToken = md5(rand(100000, 999999).time().rand(100000, 999999));
            session('CaptchaToken', $CaptchaToken);     
            $result = array();
            $result['success'] = lang('验证成功');
            $result['token'] = $CaptchaToken;
            return json($result);
        } else {
            $result = array();
            $result['error'] = lang('验证失败');
            $result['token'] = '';
            return json($result);
        }
    }
}
