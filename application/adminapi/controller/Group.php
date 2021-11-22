<?php


namespace app\adminapi\controller;


use think\Request;

class Group extends BaseApi
{

    public function index()
    {

        //接收参数  keyword  page
        $params = input();
        $where = [];
        $where['level'] = 0;
        //搜索条件 多字段相同条件的OR
        if(!empty($params['keyword'])){
            $keyword = $params['keyword'];
            $where['cate_name|pid_path_name'] = ['like', "%$keyword%"];
        }
        $listRow = 6;
        if(!empty($params['pagesize'])){
            $listRow = $params['pagesize'];
        }
        $list = \app\common\model\Group::where($where)->order('id','desc')->paginate($listRow);
        foreach($list as $v1){
            $list1 = \app\common\model\Group::where(['pid'=>$v1->id,'level'=>1])->order('id','asc')->select();
            $v1['children'] = $list1;
            foreach($list1 as $v11){
                $list11 = \app\common\model\Group::where(['pid'=>$v11->id,'level'=>2])->order('id','asc')->select();
                $v11['children'] = $list11;
            }
        }
        unset($v1);
        unset($v11);
        $this->ok($list);
    }

    public function parentList(){
        $params = input();
        $type = 0;
        if(!empty($params['type'])){
            $type = $params['type'];
        }
        $list = \app\common\model\Group::where(['level'=>0])->order('id','desc')->select();
        foreach($list as $v1){
            $list1 = \app\common\model\Group::where(['pid'=>$v1->id,'level'=>1])->order('id','asc')->select();
            if($list1){
                $v1['children'] = $list1;
                if($type == 3){
                    foreach($list1 as $v11){
                        $list11 = \app\common\model\Group::where(['pid'=>$v11->id,'level'=>2])->order('id','asc')->select();
                        if($list11){
                            $v11['children'] = $list11;
                        }
                    }
                }
            }
        }
        unset($v1);
        unset($v11);
        $this->ok($list);
    }

    public function save(Request $request)
    {
        $data = $request->param();
        $validate = $this->validate($data,[
            'cate_name|菜单名称' => 'require|length:1,50'
        ]);
        if($validate!==true){
            return json(['code' => 500, 'msg' => $validate]);
        }
        if($data['pid'] == 0){
            $data['level'] = 0;
            $data['pid_path'] = 0;
        }else{
            $p_group= \app\common\model\Group::find($data['pid']);
            $data['level'] = $p_group['level'] + 1;
            $data['pid_path'] = $p_group['pid_path'] . '_' . $p_group['id'];
        }
       // $data['is_show'] =  $data['radio'] =="1" ? 1:0;
        $group= \app\common\model\Group::create($data, true);
        $info = \app\common\model\Group::find($group['id']);
        $this->ok($info);
    }


    public function read($id)
    {
        //查询数据
        $group = \app\common\model\Group::find($id);
        if($group){
            if($group->pid > 0){
                $p_group =  \app\common\model\Group::get($group->pid);
                $group['parents'] = $p_group->cate_name;
            }
            $group['radio'] = strval($group->is_show);
        }
        //返回数据
        $this->ok($group);
    }


    public function update(Request $request, $id)
    {
        $data = $request->param();
        $validate = $this->validate($data,[
            'cate_name|菜单名称' => 'require|length:1,50',
        ]);
        if($validate!==true){
            return json(['code' => 500, 'msg' => $validate]);
        }
       // $data['is_show'] =  $data['radio'] =="1" ? 1:0;
        \app\common\model\Group::update($data, ['id'=>$id], true);
        //返回数据
        $info = \app\common\model\Group::find($id);
        $this->ok($info);
    }

    public function groupshow(){
        $params = input();
        $show = 1-$params['is_show'];
        \app\common\model\Group::update(['is_show'=>$show], ['id' => $params['id']], true);
        if($show == 0){
            \app\common\model\Group::update(['is_show'=>$show], ['pid' => $params['id']], true);
        }
        $this->ok($show);
    }

    public function delete($id)
    {
        //判断是否有子权限
        $total = \app\common\model\Group::where('pid', $id)->count();
        if($total > 0){
            $this->fail('有子菜单，无法删除');
        }
        //删除数据
        \app\common\model\Group::destroy($id);
        //返回数据
        $this->ok();
    }

}