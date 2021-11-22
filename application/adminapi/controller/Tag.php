<?php


namespace app\adminapi\controller;
use think\Request;

class Tag extends BaseApi
{
    public function index()
    {
        $params = input();
        $where = [];
        //搜索条件 多字段相同条件的OR
        if(!empty($params['keyword'])){
            $keyword = $params['keyword'];
            $where['name|type|desc'] = ['like', "%$keyword%"];
        }
        $listRow = 5;
        if(!empty($params['pagesize'])){
            $listRow = $params['pagesize'];
        }
        $list = \app\common\model\Tag::where($where)->order(['sort'=>'asc','type'=>'asc' ])->paginate($listRow);
        foreach ($list as $v){
            $v['wls_img'] = $this->get_wls_img($v['type'], $v->id);
        }
        unset($v);
        $this->ok($list);
    }

    public function save(Request $request)
    {
        $params = input();
        $validate = $this->validate($params, [
            'name|标签名' => 'require'
        ]);
        if($validate !== true){
            $this->fail($validate);
        }
        $admin =  $this->getAdmin();
        $params['create_by'] = $admin->username;
        $params['update_by'] = $admin->username;
        $params['shop_id'] = $admin->shop_id;

        $params['is_default'] = $params['is_default']? 1:0;
        $data  = \app\common\model\Tag::create($params, true);


        $tagTypes = tagTypes();
        $tagType = $tagTypes[$params['type']];
        if($tagType['hasImg'] == 1){
            $this->addImg($params['wls_img'], $data['id'],$data['type'],$tagType['imageW'],$tagType['imageH']);
        }
        $this->ok($data);
    }


    public function read($id)
    {
        $tag = \app\common\model\Tag::find($id);
        $tagTypes = tagTypes();
        $tagType = $tagTypes[$tag->type];
        if($tagType['hasImg'] == 1){
            $tag['wls_img_in'] = $this->getImgList($tag['type'],$id);
            $tag['wls_img'] = '';
            $tag['wls_img_remove'] = [];
        }
        $tag['tagType'] = $tagType;
        $tag['is_default'] = $tag['is_default'] == 1? true:false;
        $this->ok($tag);
    }


    public function update(Request $request, $id)
    {
        $params = input();
        $validate = $this->validate($params, [
            'name|标签名' => 'require'
        ]);
        if($validate !== true){
            $this->fail($validate);
        }
        $params['update_by'] = $this->getAdmin()->username;
        $params['is_default'] = $params['is_default']? 1:0;
        $data = \app\common\model\Tag::update($params, ['id' => $id], true);

        $tagTypes = tagTypes();
        $tagType = $tagTypes[$params['type']];
        if($tagType['hasImg'] == 1){
            $this->addImg($params['wls_img'], $id,$params['type'],$tagType['imageW'],$tagType['imageH']);
            $this->removeImgs($params['wls_img_remove'],null,null);
        }
        $this->ok($data);
    }

    public function delete($id)
    {
        $total = \app\common\model\GoodsTag::where('tag_id', $id)->count();
        if($total > 0){
            $this->fail('使用中，无法删除 '.$id);
        }
        $tag = \app\common\model\Tag::find($id);
        $this->removeImgs(null,  $id,$tag->type);
        \app\common\model\Tag::destroy($id);
        $this->ok();
    }


    public function changeDefault(){
        $params = input();
        \app\common\model\Tag::update(['is_default'=>$params['is_default']], ['id' => $params['id']], true);
        $this->ok();
    }

    public function getTagTypes (){
        return  $this->ok(tagTypes ());
    }


    public function  getTagArray(){
        $array = ['tag'=>'商品标签','service'=>'商品服务','tab1'=>'分类标签1','tab2'=>'分类标签2'];
        $tags = [];
        foreach($array as $k=>$v){
            $tag = [];
            $tag['label'] = $v;
            $tag['data'] = \app\common\model\Tag::where('type',$k )->select();
            $tags[ $k] = $tag;
        }
        return  $this->ok($tags);
    }

    public function  getTagCheckArray(){
        $tags_id = [];
        $typeArray = ['tag','service','tab1','tab2'];
        $list = \app\common\model\Tag::where('is_default',1)->where('type','in',$typeArray)->select();
        foreach($list as $v){
            $tags_id[]=$v->id;
        }
        return  $this->ok($tags_id);
    }

}