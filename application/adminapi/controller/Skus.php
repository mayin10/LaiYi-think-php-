<?php

namespace app\adminapi\controller;

use think\Controller;
use think\Request;

class Skus extends BaseApi
{

    public function index()
    {
        $params = input();
        $where = [];
        if(!empty($params['keyword'])){
            $where['name'] = ['like', "%{$params['keyword']}%"];
        }
        if(isset($params['keyword']) && !empty($params['keyword'])){
            $keyword = $params['keyword'];
            $where['name|keywords|desc'] = ['like', "%$keyword%"];
        }
        $listRow = 6;
        if(!empty($params['pagesize'])){
            $listRow = $params['pagesize'];
        }
        if(!empty($params['goods_id'])){
            $where['goods_id'] = $params['goods_id'];
        }
        $list = \app\common\model\Skus::with('delivery')->where($where)->order('id desc')->paginate($listRow);
        foreach ($list as $v){
            $v['wls_img'] = $this->get_wls_img('goods_sku', $v->id);
            foreach($v->delivery as $d){
                $d['delivery_type_text'] = getDeliveryType($d->delivery_type);
            }
        }
        unset($v);
        $this->ok($list);
    }

    public function checkSpecs(){
        $params = input();
        $goods_id = input('goods_id');
        $specs = input('specs');
        $total = \app\common\model\Skus::where(['goods_id'=>$goods_id,'specs'=>$specs])->count();
        if($total > 0){
            $this->fail('所选规格已经存在，请重新选择');
        }
        $this->ok(allDeliveryTypes());
    }

    public function allDeliveryTypes (){
        return  $this->ok(allDeliveryTypes());
    }

    public function save(Request $request)
    {
        $params = input();
        $validate = $this->validate($params, [
            'barcode|商品编号' => 'require'
        ]);
        if($validate !== true){
            $this->fail($validate);
        }

        $params['status'] = $params['status']?1:0;
        $admin =  $this->getAdmin();
        $params['create_by'] = $admin->username;
        $params['update_by'] = $admin->username;



        $info = \app\common\model\Skus::create($params, true);
        $this->addImg($params['wls_img'], $info->id,'goods_sku',300,300);

        foreach ( $params['deliveryTypes'] as $type){
            if($type['checked']){
                $data = ['sku_id'=> $info->id,'delivery_type'=>$type['value'],'preis'=>$type['preis'],'create_by'=>$admin->username,'update_by'=>$admin->username];
               \app\common\model\SkuDeliveries::create($data, true);
            }
        }
        $this->ok();
    }


    public function read($id)
    {
        $allDeliveryTypes = [];
        foreach (allDeliveryTypes() as $type){
            $deliveryType = \app\common\model\SkuDeliveries::where(['sku_id'=>$id,'delivery_type'=> $type['value']])->find();
            if($deliveryType != NULL){
                $type['preis'] = $deliveryType->preis;
                $type['checked'] = true;
            }
            $allDeliveryTypes[] = $type;
        }

        $data = \app\common\model\Skus::find($id);
        $data['status'] = $data['status']==1?true:false;
        $data['wls_img_in'] = $this->getImgList('goods_sku',$id);
        $data['wls_img'] = '';
        $data['wls_img_remove'] = [];
        $data['deliveryTypes'] = $allDeliveryTypes;
        $this->ok($data);
    }


    public function update(Request $request, $id)
    {
        $params = input();
        $validate = $this->validate($params, [
            'barcode|商品编号' => 'require'
        ]);
        if($validate !== true){
            $this->fail($validate);
        }
        $params['update_by'] = $this->getAdmin()->username;
        \app\common\model\Skus::update($params, ['id' => $id], true);
        $this->addImg($params['wls_img'], $id,'goods_sku',300,300);
        $this->removeImgs($params['wls_img_remove'],null,null);

        \app\common\model\SkuDeliveries::destroy(['sku_id'=>$id]);
        foreach ( $params['deliveryTypes'] as $type){
            if($type['checked']){
                $data = ['sku_id'=> $id,'delivery_type'=>$type['value'],'preis'=>$type['preis'],'update_by'=>$this->getAdmin()->username];
                \app\common\model\SkuDeliveries::create($data, true);
            }
        }
        $this->ok($params);
    }

    public function statusChange(){
        $params = input();
        \app\common\model\Skus::update(['status'=>$params['status']], ['id' => $params['id']], true);
        $this->ok($params);
    }

    public function delete($id)
    {
        $this->removeImgs(null,  $id,'goods_sku');
        \app\common\model\Skus::destroy($id);
        \app\common\model\SkuDeliveries::destroy(['sku_id'=>$id]);
        $this->ok();
    }
}
