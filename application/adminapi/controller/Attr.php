<?php


namespace app\adminapi\controller;


use think\Request;

class Attr extends BaseApi
{
    public function index()
    {
        $params = input();
        $where = [];
        if(!empty($params['keyword'])){
            $where['name'] = ['like', "%{$params['keyword']}%"];
        }

        if(!empty($params['type_id'])){
            $where['type_id'] = $params['type_id'];
        }
        if(!empty($params['sel'])){
            $where['sel'] = $params['sel'];
        }

        $list = \app\common\model\Attr::where($where)->order('sort asc')->select();
        $total = \app\common\model\Goods::where(['type_id'=>$params['type_id']])->count();
        $array['arrList'] = $list;
        $array['canEdit'] = $total>0?false:true;
        $this->ok($array);
    }

    public function save(Request $request)
    {
        $data = $request->param();
        $validate = $this->validate($data,[
            'name|参数名称' => 'require|length:1,50',
        ]);
        if($validate!==true){
            return json(['code' => 500, 'msg' => $validate]);
        }

        $attr= \app\common\model\Attr::create($data, true);
        $info = \app\common\model\Attr::find($attr['id']);
        $this->ok($info);
    }

    public function read($id)
    {
        $attr = \app\common\model\Attr::field('id,name,sort')->find($id);
        $this->ok($attr);
    }

    public function update(Request $request, $id)
    {
        $data = $request->param();
        if(!empty($params['name'])){
            $validate = $this->validate($data,['name|参数名称' => 'require|length:1,50',]);
            if($validate!==true){
                return json(['code' => 500, 'msg' => $validate]);
            }
        }
        \app\common\model\Attr::update($data, ['id'=>$id], true);
        $info = \app\common\model\Attr::find($id);
        $this->ok($info);
    }

    public function delete($id)
    {
        \app\common\model\Attr::destroy($id);
        $this->ok('删除成功');
    }

    public function checkAttr(){
        $total = \app\common\model\Goods::where(['type_id'=>input('type_id')])->count();
        if($total > 0){
            $this->fail('规格模型使用中. 参数不能有增删改查的操作');
        }
        $this->ok();
    }



    public function getAttrByGoodsId(){
        $goods_id = input('goods_id');
        $output = [];
        $goods = \app\common\model\Goods::with('type')->find($goods_id);
        $output['goods_name'] = $goods['name'];
        $output['type_name'] = $goods['type_name'];
        $output['attr'] = \app\common\model\Attr::where(['type_id'=>$goods['type_id'],'sel'=>input('sel')])->order('sort asc')->select();
        $this->ok($output);
    }



}