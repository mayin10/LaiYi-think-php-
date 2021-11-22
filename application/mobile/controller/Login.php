<?php


namespace app\mobile\controller;

use app\mobile\controller\MobileApi;
use think\Request;

class Login  extends MobileApi
{

    public function login(){
        $params = input();

        $rule['username'] = 'require';
        $msg['username.require'] = '用户名不能为空';
        $rule['password'] = 'require';
        $msg['password.require'] = '密码不能为空';

        $validate = $this->validate($params, $rule, $msg);
        if ($validate !== true) {
            $this->fail($validate);
        }
        $password = encrypt_password($params['password']);
        $info = \app\common\model\User::where('username',$params['username'])->where('password', $password)->find();

        if(empty($info)){
            //用户名或者密码错误
            $this->fail('用户名或者密码错误', 403);
        }
        $info['token'] = \tools\jwt\Token::getToken($info['id']);
        $info['face'] = $this->getFace($info->id,'user');
        $this->ok($info);
    }


    private function getFace($type_id,$type){
        $type = getImgType($type);
        $img = \app\common\model\Images::where(['type_id'=> $type_id, 'type'=> $type])->find();
        if($img){
            return WEB_NAME.$img->img_sma;
        }else{
            return  WEB_NAME ."/img/face.jpg";
        }
    }

    public function logout()
    {
        $params = input();
        $user_id = $params['user_id'];
        //返回数据
        $output = [
            'user_id' =>  $user_id,
        ];
        $this->ok($output);
    }

    public function verify(){
        $params = input();
        $user_id = $params['user_id'];
        $user =  \app\common\model\User::find($user_id);
        //进行邮箱验证
        email_verify($user);
        $this->ok();
    }


    //在APP新注册的用户， 可以直接登录， 然后到Home页面
    public function register(){
        $params = input();
        if(array_key_exists('weixin', $params)){
           $weixin_user =  \app\common\model\User::where(['wechat_openid'=>$params['wechat_openid']])->find();
           if( $weixin_user ){
               $weixin_user['token'] = \tools\jwt\Token::getToken($weixin_user['id']);
               $this->ok($weixin_user);
           }
        } else{
            $rule['username'] = 'require|unique:user';
            $msg['username.require'] = '用户名不能为空';
            $msg['username.unique'] = '用户名'.$params['username'].'已经存在';

            $rule['email'] = 'require|email|unique:user';
            $msg['email.require'] = 'email不能为空';
            $msg['email.email'] = 'email 格式错误';
            $msg['email.unique'] = 'email'.$params['email'].'已经存在';

            $rule['password'] = 'require|min:6';
            $msg['password.require'] = '密码不能为空';
            $msg['password.min'] = '密码最少要6位';

            $validate = $this->validate($params, $rule, $msg);
            if ($validate !== true) {
                $this->fail($validate);
            }
        }
        $params['name'] = $params['username'];
        $params['create_by'] =  $params['username'];
        $params['update_by'] =  $params['username'];
        $data = \app\common\model\User::create($params, true);
        $info =  \app\common\model\User::find($data->id);
        //进行邮箱验证
        email_verify($info);
        $info['token'] = \tools\jwt\Token::getToken($info['id']);
        $info['face'] = WEB_NAME ."/img/face.jpg";;
        $this->ok($info);
    }

    public function codeVerify(){
        $params = input();

        $user = \app\common\model\User::where('email',$params['email'])->find();
        if(!$user){
            $this->fail('这个邮箱不存在)');
        }

        if($user['status'] == 0){
            $this->fail('这个邮箱还没有通过验证');
        }

        $token =  mt_rand(100000, 999999);
        $regtime = time();
        \app\common\model\User::update(['token' => $token, 'regtime' => $regtime], ['id' => $user->id], true);
        $this->codeEmail($user,$token);
        $this->ok('', 200, '验证码已经成功发送到信箱');

     }

    private function codeEmail($user,$token){
        $mail = new \MyMail\MyMail();
        $mail->AddAddress($user->email, $user->username);
        $mail->Subject = '验证码';

        $b1 = '<b>'.$token.'</b><br/>';
        $b2 = "此验证码只在15分钟内有效";

        $emailbody = $b1 . $b2;


        $mail->setTemplate('welcome', [
            'name' => $user->username,
            'date' => date('Y-m-d'),
            'table' => $emailbody
        ], 'zh');
        $mail->Send();
    }

    public function passwordReset(){
        $params = input();
        $user = \app\common\model\User::where(['email'=>$params['email'], 'token'=>$params['token']])->find();
        if(!$user){
            $this->fail('验证码不正确');
        }
        $nowtime = time();//60*15 =900
        if($nowtime -$user['regtime']  >900  ) {
            $this->fail('验证码已经失效， 请重新发送');
        }
        \app\common\model\User::update(['password'=>$params['password'], 'token'=>null,'regtime'=>null],['id'=>$user->id],true);
        $this->ok('', 200, '密码已经修改！');
    }

    public function passwordModify(){
        $params = input();
        $password = encrypt_password($params['alt_pwd']);
        $user = \app\common\model\User::where(['id'=>$params['user_id'], 'password'=>$password])->find();
        if(!$user){
            $this->fail('旧密码不正确');
        }
        \app\common\model\User::update(['password'=>$params['new_pwd']],['id'=>$user->id],true);
        $this->ok('', 200, '密码已经修改！');
    }


}