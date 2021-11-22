<?php

namespace app\adminapi\controller;

use think\Controller;
use think\Request;

class Admin extends BaseApi
{
    public function index()
    {
        $params = input();
        $where = [];
        //搜索条件 多字段相同条件的OR
        if(!empty($params['keyword'])){
            $keyword = $params['keyword'];
            $where['username|email|nickname'] = ['like', "%$keyword%"];
        }
        $listRow = 6;
        if(!empty($params['pagesize'])){
            $listRow = $params['pagesize'];
        }
        $admin =  $this->getAdmin();
        if($admin->id>1){
            $where['shop_id'] = $admin->shop_id;
            if($admin->shop_id == 0){
                $where['id'] = $admin->id;
            }
        }

        $list = \app\common\model\Admin::with('role,user')->where($where)->order('id','desc')->paginate($listRow);

        foreach($list as $v){
             $v['wls_img'] = $this->get_wls_img('admin', $v->id);
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
            'email|邮箱' => 'require|email|unique:User',
            'role_id|所属角色' => 'require|integer|gt:0',
            'password|密码' => 'length:6,20'
        ]);
        if($validate !== true){
            $this->fail($validate);
        }
        if(!$params['nickname']){
            $params['nickname'] = $params['username'];
        }
        $params['status'] = $params['status']?1:0;
        $admin =  $this->getAdmin();
        $params['create_by'] = $admin->username;
        $params['update_by'] = $admin->username;
        $params['shop_id'] = $admin->shop_id;

        $user_data =[
            'name'=>$params['username'],
            'username'=>$params['username'],
            'password'=>$params['password'],
            'email'=>$params['email'],
            'status'=>1,
            'create_by'=>$params['create_by'],
            'update_by'=>$params['update_by']];

        $user = \app\common\model\User::create($user_data, true);
        $params['user_id'] = $user->id;
        $admin = \app\common\model\Admin::create($params, true);
        $this->addImg($params['wls_img'], $admin->id,'admin',150,150);
        $this->ok($admin);
    }


    public function read($id)
    {
        $data = \app\common\model\Admin::with('role')->find($id);
        $data['status'] = $data['status']==1?true:false;
        $data['wls_img_in'] = $this->getImgList('admin',$id);
        $data['wls_img'] = '';
        $data['wls_img_remove'] = [];

        $this->ok($data);
    }


    public function update(Request $request, $id)
    {
        if($id == 1){
            $this->fail('超级管理员，不能修改');
        }
        //接收数据
        $params = input();

        $params['update_by'] = $this->getAdmin()->username;
        \app\common\model\Admin::update($params, ['id' => $id], true);
        $this->addImg($params['wls_img'], $id,'admin',150,150);
        $this->removeImgs($params['wls_img_remove'],null,null);

        $info = \app\common\model\Admin::find($id);
        //返回数据
        $this->ok($info);
    }


    public function status(){
        $params = input();
        $status = $params['status'];
        \app\common\model\Admin::update(['status'=>$params['status']], ['id' => $params['id']], true);
        $this->ok($status);
    }



    public function delete($id)
    {
        //删除数据（不能删除超级管理员Admin、不能删除自己）
        if($id == 1){
            $this->fail('不能删除超级管理员');
        }
        if($id == input('user_id')){
            $this->fail('删除自己? 你在开玩笑嘛');
        }
        $this->removeImgs(null,  $id,'admin');
        \app\common\model\Admin::destroy($id);
        //返回数据
        $this->ok();
    }
}
