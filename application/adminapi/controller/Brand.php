<?php

namespace app\adminapi\controller;

use think\Request;

class Brand extends BaseApi
{

    public function index()
    {
        $params = input();
        $where = [];
        if(!empty($params['keyword'])){
            $keyword = $params['keyword'];
            $where['name|desc'] = ['like', "%$keyword%"];
        }
        $listRow = 5;
        if(!empty($params['pagesize'])){
            $listRow = $params['pagesize'];
        }
        $list = \app\common\model\Brand::where($where)->order(['sort'=>'asc'])->paginate($listRow);
        foreach ($list as $v){
            $v['wls_img'] = $this->get_wls_img('brand', $v->id);
        }
        unset($v);
        $this->ok($list);
    }

    public function datalist(){
        $list = \app\common\model\Brand::order(['sort'=>'asc'])->select();
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

        $info = \app\common\model\Brand::create($params, true);
        $this->addImg($params['wls_img'], $info->id,'brand',200,200);
        $this->ok();
    }

    public function read($id)
    {
        $data = \app\common\model\Brand::find($id);
        $data['is_hot'] = $data['is_hot']==1?true:false;
        $data['wls_img_in'] = $this->getImgList('brand',$id);
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
        \app\common\model\Brand::update($params, ['id' => $id], true);
        $this->addImg($params['wls_img'], $id,'brand',200,200);
        $this->removeImgs($params['wls_img_remove'],null,null);
        $this->ok($params);
    }

    public function hotChange(){
        $params = input();
        \app\common\model\Brand::update(['is_hot'=>$params['is_hot']], ['id' => $params['id']], true);
        $this->ok($params);
    }

    public function delete($id)
    {
        $total = \app\common\model\Goods::where('brand_id', $id)->count();
        if($total > 0){
            $this->fail('使用中，无法删除');
        }
        $this->removeImgs(null,  $id,'type');
        \app\common\model\Brand::destroy($id);
        $this->ok();
    }

}