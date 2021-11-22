<?php
namespace app\mobile\controller;

class Index extends MobileApi
{
    public function index()
    {
    }


    public function testapi(){
        $params = input();
        $limit =  $params['limit'];
        $list= \app\common\model\Burger::paginate($limit);
        $this->ok($list);
    }



    public function home(){
        $home =[];
        $types= ['swiper','banner','tab1','tab2'];
        foreach($types as $type){
            $tags= \app\common\model\Tag::where('type', $type)->order('sort asc')->select();
            if($type != 'tab2'){
                foreach ($tags as $v){
                    $v['img'] = $this->getImg($v['id'],$v['type']);
                }
                unset($v);
            }
            $home[$type] = $tags;
        }
        $this->ok($home);
    }

    public function search(){
        $search =["巧克力001","巧克力002","巧克力003","巧克力004"];
        $this->ok($search);
    }


    public function searchList(){
        $search1 =[
            'name'=>'折扣与服务',
            'value'=>'A',
            'child'=>[['name'=>'自营','value'=>'201'],['name'=>'直降','value'=>'202']],
                ];
        $search2 =[
            'name'=>'分类',
            'value'=>'B',
            'child'=>[['name'=>'手机通讯','value'=>'301'],['name'=>'日用百货','value'=>'302']],
             ];
        $search3 =[
            'name'=>'品牌',
            'value'=>'C',
            'child'=>[['name'=>'小米','value'=>'401'],['name'=>'华为','value'=>'402'],['name'=>'苹果','value'=>'403']],
        ];
        $search4 =[
            'name'=>'机身内存',
            'value'=>'D',
            'child'=>[['name'=>'64G','value'=>'501'],['name'=>'128G','value'=>'502'],['name'=>'苹果','256G'=>'503']],
        ];
        $search = [$search1,$search2,$search3,$search4];
        $this->ok($search);
    }


    public function upload()
    {


         $file = $_FILES['file'];
         $user_id = $_POST['user_id'];
         $path  = DS.'uploads' . DS.'mobile_app'.DS .$user_id.'_'.$_FILES['file']['name'];
       // $this->ok($user);
       // $file = request()->file('file');
        if(empty($file)){
            $this->fail('必须上传文件');
        }
        $imageSavePath = ROOT_PATH .'public' . $path;
        if(move_uploaded_file($_FILES['file']['tmp_name'], $imageSavePath)){
            $this->addUser($user_id, $path);
            $this->ok($path);
        }else{
            $this->fail('error');
        }
    }



    private function addUser($user_id, $path){
        $type = getImgType('user');
        $data = ['type_id'=>$user_id,'type'=>$type];
        $img = \app\common\model\Images::where($data)->find();
        if(!$img){
            $img_data = [
                'shop_id'=>0,
                'type_id'=>$user_id,
                'type'=>$type,
                'img_sma'=> $path,
                'create_by'=>'app',
                'update_by'=>'app'];
            \app\common\model\Images::create($img_data,true);
        } else{
            $alt =  ROOT_PATH .'public'.$img->img_sma;
            if(is_file($alt)){unlink($alt);}
            \app\common\model\Images::update(['img_sma' => $path, 'update_by'=>'app'], ['id'=>$img->id]);
        }
    }

}
