<?php

namespace app\adminapi\controller;

use think\Controller;
use think\Request;

class Goods extends BaseApi
{

    public function index()
    {
        $params = input();
        $where = [];
        if(isset($params['keyword']) && !empty($params['keyword'])){
            $keyword = $params['keyword'];
            $where['name|name_en|keywords|desc|detail'] = ['like', "%$keyword%"];
        }
        $listRow = 6;
        if(!empty($params['pagesize'])){
            $listRow = $params['pagesize'];
        }
        $list = \app\common\model\Goods::with('type,skus,brand')
            ->where($where)->order('id desc')->paginate($listRow);


        foreach($list as $v){
            $v['wls_logo'] = $this->get_wls_img('goods_logo', $v->id);
            $v['wls_img'] = $this->get_wls_img('goods_cover', $v->id);
        }
        unset($v);
        $this->ok($list);
    }


    public function save(Request $request)
    {
        $params = input();
        $validate = $this->validate($params, [
            'name|商品名' => 'require',
            'name_en|商品英文名' => 'require',
        ]);

        if($validate !== true){
            $this->fail($validate);
        }
        if( array_key_exists('detail', $params)){
          //  $params['detail'] = $request->param('detail', '', 'remove_xss');
        }

        $params['status'] = $params['status']?1:0;

        $admin =  $this->getAdmin();
        $params['create_by'] = $admin->username;
        $params['update_by'] = $admin->username;
        $params['shop_id'] = $admin->shop_id;


        //开启事务
        \think\Db::startTrans();
        try{
            $goods = \app\common\model\Goods::create($params, true);
            //GoodsTag
            $this->addGoodsTag($params['tagCheckList'],$goods['id'],$admin->username);


            //Img
            $this->addImg($params['goods_logo'], $goods['id'],'goods_logo',100,100);
            $this->addImg($params['goods_cover'], $goods['id'],'goods_cover',200,200);
            $this->addImgList($params['goods_lunbo'],$goods['id'],'goods_lunbo',100,100,800,800);
            $this->addImgList($params['goods_detail'],$goods['id'],'goods_detail',100,100,800,800);

            \think\Db::commit();
            //返回数据
            $info = \app\common\model\Goods::find($goods['id']);
            $this->ok($info);

         }catch (\Exception $e){
            \think\Db::rollback();
            $this->fail('操作失败');
            }
    }

    private function addGoodsTag($new, $googd_id, $username){
        if (!empty($new)){
            foreach ($new as $tag_id){
                $data = ['goods_id'=>$googd_id,'tag_id'=>$tag_id,'create_by'=>$username, 'update_by'=>$username];
                    \app\common\model\GoodsTag::create($data, true);
            }
        }
        }

    private function editGoodsTag($new, $googd_id, $username){
            $alt = $this->getGoodsTagID($googd_id);
            foreach($new as $newTagId){
                if(!in_array($newTagId,$alt)){
                    $data = ['goods_id'=>$googd_id,'tag_id'=>$newTagId,'create_by'=>$username, 'update_by'=>$username];
                    \app\common\model\GoodsTag::create($data, true);
                }
            }

            foreach($alt as $altTagId){
                if(!in_array($altTagId,$new)){
                    \app\common\model\GoodsTag::destroy(['tag_id'=>$altTagId, 'goods_id'=>$googd_id]);
                }
            }

        }

    private function getGoodsTagID($goods_id){
        $tag_id = [];
        $goodstags = \app\common\model\GoodsTag::where(['goods_id'=>$goods_id])->select();
        foreach($goodstags as $goodstag){
            $tag_id[] = $goodstag->tag_id;
        }
        return $tag_id;
    }


    public function read($id)
    {
        $goods = \app\common\model\Goods::with('type,brand,group')->find($id);

        $goods['status'] =  $goods['status']==1?true:false;


        $goods['tagCheckList'] = $this->getGoodsTagID($goods->id);

        $goods['goods_logo_in'] = $this->getImgList('goods_logo',$goods->id);
        $goods['goods_logo'] = [];
        $goods['goods_cover_in'] = $this->getImgList('goods_cover',$goods->id);
        $goods['goods_cover'] = [];

        $goods['goods_lunbo_in'] = $this->getImgList('goods_lunbo',$goods->id);
        $goods['goods_lunbo'] = [];
        $goods['goods_detail_in'] = $this->getImgList('goods_detail',$goods->id);
        $goods['goods_detail'] = [];
        $goods['goods_Imgs_remove'] = [];

        $this->ok($goods);
    }


    public function update(Request $request, $id)
    {
        $params = input();
        $validate = $this->validate($params, [
            'name|商品名' => 'require',
        ]);
        if($validate !== true){
            $this->fail($validate);
        }

        if( array_key_exists('detail', $params)){
          //  $params['detail'] = $request->param('detail', '', 'remove_xss');
        }

        $params['status'] = $params['status']?1:0;
        $params['is_free_shipping'] = $params['is_free_shipping']?1:0;

        $params['update_by'] = $this->getAdmin()->username;

        //开启事务
        \think\Db::startTrans();
        try{
            \app\common\model\Goods::update($params, ['id' => $id], true);
            //GoodsTag
            $this->editGoodsTag($params['tagCheckList'],$id,$this->getAdmin()->username);


            $this->addImg($params['goods_logo'], $id,'goods_logo',100,100);
            $this->addImg($params['goods_cover'], $id,'goods_cover',200,200);
            $this->addImgList($params['goods_lunbo'],$id,'goods_lunbo',100,100,800,800);
            $this->addImgList($params['goods_detail'],$id,'goods_detail',100,100,800,800);

            $this->removeImgs($params['goods_Imgs_remove'],null,null);

            \think\Db::commit();
            //返回数据
            $info = \app\common\model\Goods::find($id);
            $this->ok($info);

        }catch (\Exception $e){
            \think\Db::rollback();
            $this->fail('操作失败');
        }

    }


    public function delete($id)
    {
            //开启事务
            \think\Db::startTrans();
            try{
                $this->removeImgs(null,  $id,'goods_logo');
                $this->removeImgs(null,  $id,'goods_cover');
                $this->removeImgs(null,  $id,'goods_lunbo');
                $this->removeImgs(null,  $id,'goods_detail');

                $skuList = \app\common\model\Skus::where(['goods_id'=>$id])->select();
                foreach($skuList as $sku){
                    $this->removeImgs(null,  $sku->id,'goods_sku');
                    \app\common\model\Skus::destroy($sku->id);
                }

                \app\common\model\Goods::destroy($id);

                \think\Db::commit();
                $this->ok();
            }catch (\Exception $e){
                \think\Db::rollback();
                $this->fail('操作失败');
            }
        }


}
