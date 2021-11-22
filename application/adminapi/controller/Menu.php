<?php


namespace app\adminapi\controller;


use think\Request;

class Menu extends BaseApi
{
    public function index()
    {
        $params = input();
        $where = [];
        if(!empty($params['keyword'])){
            // where menu_name like '%%'
            $where['menu_name'] = ['like', "%{$params['keyword']}%"];
        }
        $where['level'] = 0;
        $listRow = 6;
        if(!empty($params['pagesize'])){
            $listRow = $params['pagesize'];
        }
        $list = \app\common\model\Menu::where($where)->order('sort asc')->paginate($listRow);

        //树形数据
        foreach($list as $v){
            $list1 = \app\common\model\Menu::where(['pid'=>$v->id])->order('sort asc')->select();
            foreach($list1 as $v1){
                $v1['is_show'] = $v1['is_show'] == 1? true:false;
                $v1['status'] = $v1['status'] == 1? true:false;
            }

            $v['children'] = $list1;
            $v['is_show'] = $v['is_show'] == 1? true:false;
            $v['status'] = $v['status'] == 1? true:false;
        }
        $this->ok($list);

    }


    public function save(Request $request)
    {
        $data = $request->param();
        $validate = $this->validate($data,[
            'menu_name|菜单名称' => 'require|length:1,50',
            'menu_path|菜单路径' => 'require|length:1,50',
        ]);
        if($validate!==true){
            return json(['code' => 500, 'msg' => $validate]);
        }
        if($data['pid'] == 0){
            $data['level'] = 0;
            $data['pid_path'] = 0;
        }else{
            $p_menu= \app\common\model\Menu::find($data['pid']);
            $data['level'] = $p_menu['level'] + 1;
            $data['pid_path'] = $p_menu['pid_path'] . '_' . $p_menu['id'];
        }

        $data['is_show'] =  $data['is_show'] ? 1:0;
        $data['status'] =  $data['status'] ? 1:0;

        $admin =  $this->getAdmin();
        $data['create_by'] = $admin->username;
        $data['update_by'] = $admin->username;

        $menu= \app\common\model\Menu::create($data, true);
        $info = \app\common\model\Menu::find($menu['id']);
        $this->ok($info);
    }


    public function read($id)
    {
        $menu = \app\common\model\Menu::find($id);
        if($menu){
            if($menu->pid > 0){
                $p_menu =  \app\common\model\Menu::get($menu->pid);
                $menu['parents'] = $p_menu->menu_name;
            }else{
                $menu['parents'] = '';
            }
            $menu['is_show'] = $menu['is_show'] == 1? true:false;
            $menu['status'] = $menu['status'] == 1? true:false;
        }
        $this->ok($menu);
    }


    public function update(Request $request, $id)
    {
        $data = $request->param();
        $validate = $this->validate($data,[
            'menu_name|菜单名称' => 'require|length:1,50',
            'menu_path|菜单路径' => 'require|length:1,50',
        ]);
        if($validate!==true){
            return json(['code' => 500, 'msg' => $validate]);
        }
        $data['is_show'] =  $data['is_show']? 1:0;
        $data['status'] =  $data['status'] ? 1:0;
        $admin =  $this->getAdmin();
        $data['update_by'] = $admin->username;
        \app\common\model\Menu::update($data, ['id'=>$id], true);
        $info = \app\common\model\Menu::find($id);
        $this->ok($info);
    }

    public function menushow(){
        $params = input();
        $show = $params['is_show']?1:0;
        \app\common\model\Menu::update(['is_show'=>$show], ['id' => $params['id']], true);
        if($show == 0){
            \app\common\model\Menu::update(['is_show'=>$show], ['pid' => $params['id']], true);
        }
        $this->ok($show);
    }

    public function menuStatus(){
        $params = input();
        $status = $params['status']?1:0;
        \app\common\model\Menu::update(['status'=>$status], ['id' => $params['id']], true);
        if($status == 0){
            \app\common\model\Menu::update(['status'=>$status], ['pid' => $params['id']], true);
        }
        $this->ok($status);
    }

    public function delete($id)
    {
        //判断是否有子权限
        $total = \app\common\model\Menu::where('pid', $id)->count();
        if($total > 0){
            $this->fail('有子菜单，无法删除');
        }
        //删除数据
        \app\common\model\Menu::destroy($id);
        //返回数据
        $this->ok();
    }

    public function menupath()
    {
        $user_id = input('user_id');
        $info = \app\common\model\Admin::find($user_id);
        $role_id = $info['role_id'];
        $where['status'] = 1;
        if($role_id == 1){
            $data = \app\common\model\Menu::where($where)->order('sort asc')->select();
        }else{
            $where['is_show'] = 1;
            $data = \app\common\model\Menu::where($where)->order('sort asc')->select();
        }
        $data = (new \think\Collection($data))->toArray();
        $data = get_tree_list($data);
        $this->ok($data);
    }

    public function parentMenu(){
        $params = input();
        $type = 0;
        if(!empty($params['type'])){
            $type = $params['type'];
        }
        $list = \app\common\model\Menu::where(['level'=>0])->order('sort','asc')->select();
        if($type == 1){
            $this->ok($list);
        }
        foreach($list as $v1){
            $list1 = \app\common\model\Menu::where(['pid'=>$v1->id,'level'=>1])->order('sort','asc')->select();
            $v1['children'] = $list1;
            if($type == 3){
                foreach($list1 as $v11){
                    $list11 = \app\common\model\Menu::where(['pid'=>$v11->id,'level'=>2])->order('sort','asc')->select();
                    $v11['children'] = $list11;
                }
            }
        }
        unset($v1);
        unset($v11);
        $this->ok($list);
    }
}
