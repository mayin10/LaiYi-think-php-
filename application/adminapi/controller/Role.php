<?php

namespace app\adminapi\controller;

use think\Controller;
use think\Request;

class Role extends BaseApi
{

    public function index()
    {
        //查询数据 (不需要查询超级管理员)
        $list = \app\common\model\Role::where('id', '>', 1)->select();

        $params = input();
        $where = [];
        if(!empty($params['keyword'])){
            $where['role_name|desc'] = ['like', "%{$params['keyword']}%"];
        }

        $listRow = 6;
        if(!empty($params['pagesize'])){
            $listRow = $params['pagesize'];
        }
        $list = \app\common\model\Role::where($where)->order('id asc')->paginate($listRow);
        $this->ok($list);

    }


    public function save(Request $request)
    {
        //接收数据
        $params = input();
        //参数检测
        $validate = $this->validate($params, [
            'role_name' => 'require',

        ]);
        if($validate !== true){
            $this->fail($validate);
        }

        $role = \app\common\model\Role::create($params, true);
        $info = \app\common\model\Role::find($role['id']);

        $this->ok($info);
    }

    public function read($id)
    {
        $info = \app\common\model\Role::field('id, role_name, desc,level')->find($id);
        $this->ok($info);
    }

    public function update(Request $request, $id)
    {
        //接收数据
        $params = input();
        //参数检测
        $validate = $this->validate($params, [
            'role_name' => 'require',
           // 'desc' => 'require'
        ]);
        if($validate !== true){
            $this->fail($validate);
        }
        //修改数据
       // $params['role_auth_ids'] = $params['auth_ids'];
        \app\common\model\Role::update($params, ['id' => $id], true);
        $info = \app\common\model\Role::find($id);
        //返回数据
        $this->ok($info);
    }


    public function delete($id)
    {
        //超级管理员 这个角色 可以设置为不能删除。
        if($id == 1){
            $this->fail('该角色无法删除');
        }
        //如果角色下有管理员，不能删除
        //根据角色id 查询管理员表的role_id字段
        $total = \app\common\model\Admin::where('role_id', $id)->count();
        if($total > 0)
        {
            $this->fail('角色正在使用中，无法删除');
        }
        //删除数据
        \app\common\model\Role::destroy($id);
        //返回数据
        $this->ok();
    }

    public function getRoleList(){
        $list = \app\common\model\Role::where('id','>',1)->order('level asc')->select();
        $this->ok($list);
    }
}
