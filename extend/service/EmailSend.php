<?php
namespace service;

// 发送邮件验证码
class EmailSend
{
    public static function sendEmail($user_email,$title,$content)
    {
        require "../extend/PHPMailer/class.phpmailer.php";
        $mail = new \PHPMailer(); //实例化 
        $mail->IsSMTP(); // 启用SMTP 
        $mail->SMTPDebug = 0;
        $mail->Host = "smtp.163.com"; //SMTP服务器 以163邮箱为例子 
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;  //邮件发送端口 
        $mail->SMTPAuth   = true;  //启用SMTP认证 
        $mail->CharSet  = "UTF-8"; //字符集 
        $mail->Encoding = "base64"; //编码方式 
        $mail->Username = config('email.web_email');  //你的邮箱 
        $mail->Password = config('email.web_email_pass');  //你的密码 
        $mail->Subject = $title; //邮件标题 
        $mail->From = config('email.web_email');  //发件人地址（也就是你的邮箱） 
        $mail->FromName = "";  //发件人姓名 
        $mail->AddAddress($user_email, "");//添加收件人（地址，昵称） 
        //$mail->AddAttachment($path,'投稿附件.'.$filetype);
        //$mail->AddAttachment('投稿附件.docx',$name); // 添加附件,并指定名称 
        $mail->IsHTML(true); //支持html格式内容 
        //$mail->AddEmbeddedImage("logo.jpg", "my-attach", "logo.jpg"); //设置邮件中的图片 
        $text = $content;
        $mail->Body = $text;
        //发送 
        if(!$mail->Send()) { 
            //echo "Mailer Error: " . $mail->ErrorInfo; 
            return array('error' => $mail->ErrorInfo);
        }else{
            return true;
        }

        
    }

}
