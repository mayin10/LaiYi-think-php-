<?php


namespace app\adminapi\controller;


use think\Request;

class Shop extends BaseApi
{
    public function index()
    {
        //接收参数  keyword  page
        $params = input();
        $where = [];
        //搜索条件 多字段相同条件的OR
        if(!empty($params['keyword'])){
            $keyword = $params['keyword'];
            $where['name_de|name_cn|email|principal|desc|desc_de'] = ['like', "%$keyword%"];
        }
        $listRow = 6;
        if(!empty($params['pagesize'])){
            $listRow = $params['pagesize'];
        }
        $list = \app\common\model\Shop::where($where)->order('id','desc')->paginate($listRow);
        foreach($list as $v){
            $v['wls_img'] = $this->get_wls_img('shop', $v->id);
        }
        unset($v);
        $this->ok($list);
    }


    public function save(Request $request)
    {
        $params = input();
        $validate = $this->validate($params, [
            'name_cn|店铺名称' => 'require|unique:Shop',
        ]);
        if($validate !== true){
            $this->fail($validate);
        }

        $admin =  $this->getAdmin();
        $params['create_by'] = $admin->username;
        $params['update_by'] = $admin->username;

        $shop = \app\common\model\Shop::create($params, true);
        $this->addLogo($params,$shop->id,'shop',150,150);

        //添加shop_id
        if($admin->id > 1){
            \app\common\model\Admin::update(['shop_id' =>$shop['id'],'update_by'=>$admin->username], ['id' => $admin->id], true);
        }
        $ok = \app\common\model\Shop::find($shop['id']);

        $this->ok($ok);
    }


    public function read($id)
    {
        $shop = \app\common\model\Shop::find($id);
        $this->ok($shop);
    }

    public function getShopByUserId(){
        $user_id = input('user_id');
        $admin = \app\common\model\Admin::find($user_id);
        if($admin->shop_id > 0){
            $shop = \app\common\model\Shop::find($admin->shop_id);
            $shop['imgFileList'] = $this->getImg('shop',$shop->id);
            $shop['wls_img'] = $this->getWls_img('shop',$shop->id);
            $this->ok($shop);
        }
        $this->fail('no Shop');
    }




    public function update(Request $request, $id)
    {
        $params = input();

        $validate = $this->validate($params, [
            'name_cn|店铺名称' => 'require',
        ]);
        if($validate !== true){
            $this->fail($validate);
        }
        \app\common\model\Shop::update($params, ['id' => $id], true);
        $this->addLogo($params,$id,'shop',150,150);
        $info = \app\common\model\Shop::find($id);
        $this->ok($info);
    }


    public function delete($id)
    {
        //判断是否有子权限
        $total = \app\common\model\Admin::where('shop_id', $id)->count();
        if($total > 0){
            $this->fail('店铺正在使用中， 不可以删除');
        }
        \app\common\model\Shop::destroy($id);
        //返回数据
        $this->ok();
    }

}