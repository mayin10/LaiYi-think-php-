<?php


namespace app\mobile\controller;


class Goods extends MobileApi
{

    public function goods(){
        $params = input();
        $where['status'] = 1;
        if(array_key_exists('tag_id', $params)){
            if( $params['tag_id'] > 0){
                $where['id']  = ['in', $this->getGoods_ids($params['tag_id'])];
            }
        }

        if(isset($params['keyword']) && !empty($params['keyword'])){
            $keyword = $params['keyword'];
            $where['name|keywords|desc|detail'] = ['like', "%$keyword%"];
        }

        $goods= \app\common\model\Goods::where($where)->order(['create_time'=>'desc'])->paginate(6);
        foreach ($goods as $good){
            $good['img'] = $this->getImg($good->id,'goods_cover');
            $good['tags'] = \app\common\model\Tag::field('id,type,name,desc')->where(['type'=>'tag'])
                ->where('id','in',$this->getTags_ids($good->id))->select();
            $good['price'] = $this->getGoodPrice($good->id);
        }
        $this->ok($goods);





        if(array_key_exists('order', $params)){
            $order =$this->getOrder($params['order']);
        }
        if(array_key_exists('sort', $params)){
            $sort  = $params['sort'] == 0 ? 'asc':'desc' ;
        }


    }

   private function getOrder($index){
       // order  1-综合 2-新品  3-价格  4-信用
        $order = ['create_time','sale_count','review_count'];
        return $order[$index];
   }

   private function getGoodPrice($goods_id)
   {
       $min = 0;
       $max = 0;
       $skuList = \app\common\model\Skus::where(['goods_id' => $goods_id, 'status' => 1])->order(['price_1' => 'asc'])->select();
       $count = count($skuList);
       $index = 0;
       foreach ($skuList as $sku) {
           if ($index == 0) {
               $min = $sku->price_1;
           }
           $index++;
           if ($index == $count - 1) {
               $max = $sku->price_1;
           }
       }
       if ($max > 0) {
           return strval($min) . '€ - ' . strval($max) . '€';
       } else {
           return strval($min) . '€';
       }
   }

    private function getGoods_ids($tag_id){
        $goods_ids= [];
        $altGoodsTags = \app\common\model\GoodsTag::where('tag_id',$tag_id)->select();
        if($altGoodsTags){
            foreach ($altGoodsTags as $altGoodsTag) {
                $goods_ids[] = $altGoodsTag['goods_id'];
            }
        }
        return $goods_ids;
    }

    private function getTags_ids($goods_id){
        $tags_ids= [];
        $altGoodsTags = \app\common\model\GoodsTag::where('goods_id',$goods_id)->select();
        if($altGoodsTags){
            foreach ($altGoodsTags as $altGoodsTag) {
                $tags_ids[] = $altGoodsTag['tag_id'];
            }
        }
        return $tags_ids;
    }



    public function detail($id){
       // $id = 13;
        $goods = \app\common\model\Goods::where('id',$id)->find();
        $skus =  \app\common\model\Skus::where(['goods_id'=>$goods->id,'status'=>1])->order('sort','asc')->select();
        $selectedSku = null;
        foreach($skus as $sku){
            $sku['img'] = $this->getImg($sku->id, 'goods_sku');
            if($selectedSku == null){
                $selectedSku = $sku;
            }
            $deliveries  = \app\common\model\SkuDeliveries::where(['sku_id'=>$sku->id])->order('delivery_type','asc')->select();
            $selected = true;
            foreach($deliveries as $d){
                $d['delivery_type_label'] = getDeliveryType($d->delivery_type);
                $d['selected'] = $selected;
                if($selected){
                    $selected = false;
                }
            }
            $sku['delivery'] = $deliveries;
        }

        $attrs = \app\common\model\Attr::where(['type_id'=>$goods['type_id'],'sel'=>'many'])->order('sort asc')->select();
        foreach($attrs as $attr){
            $value = [];
            $values = [];
            foreach(explode(' ', $attr['vals']) as $v){
                $value['attr_id'] = $attr['id'];
                $value['val'] = $v;
                $value['name_val'] = $attr['name'].':'.$v;
                $value['valid'] = true;
                $value['selected'] = $this->selecteAttr($selectedSku,$attr['name'], $v);
                $values[] = $value;
            }
            $attr['value'] = $values;
        }
        $goods['skus'] =$skus;
        $goods['selectedSku'] =$selectedSku;
        $goods['attr'] = $attrs;
        $goods['goods_lunbo'] = $this->getImgs($id, 'goods_lunbo');
        $goods['goods_detail'] = $this->getImgs($id, 'goods_detail');
        $goods['service'] = \app\common\model\Tag::field('id,type,name,desc')->where(['type'=>'service'])
            ->where('id','in',$this->getTags_ids($id))->select();
        $this->ok($goods);
    }




    private function selecteAttr($sku, $name, $value){
        if($sku !=null){
            if((strpos($sku->specs, $name)>-1)&& (strpos($sku->specs, $value)>-1)){
                return true;
            }
        }
        return false;
    }


    public function isKeep(){
        $params = input();
        $is_keep = false;
        $where = [
            'user_id'=>$params['user_id'],
            'goods_id'=>(int)$params['goods_id']
        ];
        $keep = \app\common\model\Keep::where($where)->find();
        if($keep == null){
            $is_keep = false;
        }else{
            $is_keep = true;
        }
        $this->ok($is_keep);
    }

    public function updateKeep(){
        $params = input();
        $is_keep = false;
        $where = [
            'user_id'=>$params['user_id'],
            'goods_id'=>(int)$params['goods_id']
        ];
        $keep = \app\common\model\Keep::where($where)->find();
        if($keep == null){
            \app\common\model\Keep::create($where, true);
            $is_keep = true;
        }else{
            \app\common\model\Keep::where($where)->delete();
            $is_keep = false;
        }
        $this->ok($is_keep);
    }

   public function getKeepGoods(){
       $params = input();
       $where = [
           'user_id'=>$params['user_id']
       ];
       $keepList = \app\common\model\Keep::with('goods')->where($where)->select();
       foreach ($keepList as $keep){
           $keep['img'] = $this->getImg($keep->goods_id,'goods_cover');
           $keep['tags'] = \app\common\model\Tag::field('id,type,name,desc')->where(['type'=>'tag'])
               ->where('id','in',$this->getTags_ids($keep->goods_id))->select();
           $keep['price'] = $this->getGoodPrice($keep->goods_id);
       }

       $this->ok($keepList);
   }


    public function category(){
        $types = \app\common\model\Type::with('goods')->order('sort', 'asc')->select();
        foreach ($types as $t){
            foreach ($t->goods as $g){
                $g['img'] =  $this->getImg($g['id'],'goods_logo');
            }
            $t['img']  =  $this->getImg($t['id'],'type');
        }
        $this->ok($types);
    }


}