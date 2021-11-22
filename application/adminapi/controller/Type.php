<?php

namespace app\adminapi\controller;
use think\Controller;
use think\Request;

class Type extends BaseApi
{

    public function index()
    {
        $params = input();
        $where = [];
        if(!empty($params['keyword'])){
            $keyword = $params['keyword'];
            $where['name|desc'] = ['like', "%$keyword%"];
        }
        $listRow = 6;
        if(!empty($params['pagesize'])){
            $listRow = $params['pagesize'];
        }
        $list = \app\common\model\Type::where($where)->order(['sort'=>'asc'])->paginate($listRow);
        foreach ($list as $v){
            $v['wls_img'] = $this->get_wls_img('type', $v->id);
        }
        unset($v);
        $this->ok($list);
    }

    public function datalist(){
        $list = \app\common\model\Type::order(['sort'=>'asc'])->select();
        $this->ok($list);
    }

    public function save(Request $request)
    {
        $params = input();
        $validate = $this->validate($params, [
            'name|模型名' => 'require'
        ]);
        if($validate !== true){
            $this->fail($validate);
        }

        $params['is_hot'] = $params['is_hot']?1:0;
        $admin =  $this->getAdmin();
        $params['create_by'] = $admin->username;
        $params['update_by'] = $admin->username;
        $params['shop_id'] = $admin->shop_id;

        $info = \app\common\model\Type::create($params, true);
        $this->addImg($params['wls_img'], $info->id,'type',530,180);
        $this->ok();
    }

    public function read($id)
    {
        $data = \app\common\model\Type::find($id);
        $data['is_hot'] = $data['is_hot']==1?true:false;
        $data['wls_img_in'] = $this->getImgList('type',$id);
        $data['wls_img'] = '';
        $data['wls_img_remove'] = [];
        $this->ok($data);
    }

    public function update(Request $request, $id)
    {
        $params = input();
        $validate = $this->validate($params, [
            'name|模型名' => 'require'
        ]);
        if($validate !== true){
            $this->fail($validate);
        }
        $params['update_by'] = $this->getAdmin()->username;
        \app\common\model\Type::update($params, ['id' => $id], true);
        $this->addImg($params['wls_img'], $id,'type',530,180);
        $this->removeImgs($params['wls_img_remove'],null,null);
        $this->ok($params);
    }

    public function hotChange(){
        $params = input();
        \app\common\model\Type::update(['is_hot'=>$params['is_hot']], ['id' => $params['id']], true);
        $this->ok($params);
    }



    public function delete($id)
    {
        $total = \app\common\model\Goods::where('type_id', $id)->count();
        if($total > 0){
            $this->fail('使用中，无法删除');
        }
        $this->removeImgs(null,  $id,'type');
        \app\common\model\Type::destroy($id);
        $this->ok();
    }

}

