<?php

namespace app\adminapi\controller;

use think\Controller;

class Login extends BaseApi
{

    public function login()
    {
        $params = input();
        $rule['username'] = 'require';
        $msg['username.require'] = '用户名不能为空';
        $rule['password'] = 'require';
        $msg['password.require'] = '密码不能为空';

        $validate = $this->validate($params, $rule, $msg);
        if ($validate !== true) {
            $this->fail($validate);
        }
        $info = \app\common\model\Admin::where('username',$params['username'])->where('password', encrypt_password($params['password']))->find();
        if(empty($info)){
            $this->fail('用户名或者密码错误', 403);
        }
        $data = [
            'token' => \tools\jwt\Token::getToken($info['id']),
            'user_id' => $info['id'],
            'username' => $info['username'],
            'shop_id' => $info['shop_id'],
            'role_id' => $info['role_id'],
        ];
        $this->ok($data);
    }

    public function logout()
    {
        $params = input();
        // $user_id =$params['user_id'];
        $token = \tools\jwt\Token::getRequestToken();

        //从缓存中取出 注销的token数组
        $delete_token = cache('delete_token') ?: [];
        //将当前的token 加入到数组中 ['dssfd','dsfds']
        $delete_token[] = $token;
        //将新的数组 重新存到缓存中  缓存1天
        cache('delete_token', $delete_token, 86400);
        //返回数据


        $output = [
            'user_id'=>\tools\jwt\Token::getUserId(),
            'token'=>\tools\jwt\Token::getRequestToken(),
        ];
        $this->ok($output);
    }













}
