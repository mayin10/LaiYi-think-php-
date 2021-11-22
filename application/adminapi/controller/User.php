<?php

namespace app\adminapi\controller;

use think\Controller;
use think\Request;

class User extends BaseApi
{

    public function index()
    {
        //接收参数  keyword  page
        $params = input();
        $where = [];
        //搜索条件 多字段相同条件的OR
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $where['username|email|nickname'] = ['like', "%$keyword%"];
        }
        $listRow = 6;
        if (!empty($params['pagesize'])) {
            $listRow = $params['pagesize'];
        }
        $list = \app\common\model\User::where($where)->order('id', 'desc')->paginate($listRow);
        foreach ($list as $v) {
            $v['wls_img'] = $this->get_wls_img('user', $v->id);
            $v['is_admin'] = 0;
            $admin = \app\common\model\Admin::where(['user_id' => $v->id])->find();
            if ($admin) {
                $v['is_admin'] = 1;
                if ($v['wls_img'] == "") {
                    $v['wls_img'] = $this->get_wls_img('admin', $admin->id);
                }
            }
        }
        unset($v);
        $this->ok($list);
    }



    public function save(Request $request)
    {
        //接收数据
        $params = input();
        //参数检测
        $validate = $this->validate($params, [
            'username|用户名' => 'require|unique:User',
            'email|邮箱' => 'require|email',
            'password|密码' => 'length:6,20'
        ]);
        if($validate !== true){
            $this->fail($validate);
        }

        if(empty($params['password'])){
            $params['password'] = '123456';
        }

        $user = \app\common\model\User::create($params, true);
        $this->addImg($params['wls_img'], $user->id,'user',150,150);
        email_verify($user);
        //返回数据
        $this->ok($user);
    }


    public function read($id)
    {
        //查询数据
        $data = \app\common\model\User::find($id);
        $data['wls_img_in'] = $this->getImgList('user',$id);
        $data['wls_img'] = '';
        $data['wls_img_remove'] = [];
        //返回数据
        $this->ok($data);
    }


    public function update(Request $request, $id)
    {
        if($id == 1){
            $this->fail('超级管理员，不能修改');
        }
        //接收数据
        $params = input();
        $validate = $this->validate($params, [
           // 'name|模型名' => 'require'
        ]);
        if($validate !== true){
           // $this->fail($validate);
        }
        $params['update_by'] = $this->getAdmin()->username;
        \app\common\model\User::update($params, ['id' => $id], true);
        $this->addImg($params['wls_img'], $id,'user',150,150);
        $this->removeImgs($params['wls_img_remove'],null,null);
        $this->ok($params);
    }


    public function toActive(){
        $user = \app\common\model\User::where('id',input('activ_id'))->find();
        if($user == null){
            $this->fail('账户错误');
        }
        email_verify($user);
        $this->ok($user);

    }

    public function active(){
        $params = input();
        $verify = $params['verify'];
        $user = \app\common\model\User::where(['token'=>$verify])->find();

        if($user == null){
            $msg = '<h1>账户错误</h1>';
        }
        $nowtime = time();
        //60*60*24 = 86400
        if($nowtime -$user['regtime']  > 86400  ){
            $msg = '<h1>您的激活有效期已过，<br/>请登录您的帐号重新发送激活邮件.</h1>';
            echo $msg; die;
        }else{
            $data = [
                'token'=>null,
                'regtime'=>null,
                'status'=>1
            ];
            \app\common\model\User::update($data, ['id' => $user->id], true);
            $msg = '<h1>恭喜您，帐号激活成功！</h1><br/>请登录！';
        }
        echo $msg; die;
    }


    
    public function delete($id)
    {
        //删除数据（不能删除超级管理员User、不能删除自己）
        if($id == 1){
            $this->fail('不能删除超级管理员');
        }
        if($id == input('user_id')){
            $this->fail('删除自己? 你在开玩笑嘛');
        }
        $this->removeImgs(null,  $id,'user');
        \app\common\model\User::destroy($id);
        //返回数据
        $this->ok();
    }
}