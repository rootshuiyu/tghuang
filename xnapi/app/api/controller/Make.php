<?php
namespace app\api\controller;

use Webman\RedisQueue\Client;
use support\Request;
use think\facade\Db;
use Zxing\QrReader;
use app\api\traits\Pro;
use Bilulanlv\ThinkCache\facade\ThinkCache;



class Make extends Api
{
    
    protected $noNeedLogin = ['index'];
    protected $order   = null;
    protected $card    = null;
    
    public function __construct(Request $request = null)
    {
        parent::__construct();
        $this->order    = new \app\api\model\Order;
        $this->card     = new \app\api\model\Card;
        
        
    }
    
    public function order_get(Request $request)
    {
        $center  = $request->get();
        $app = $this->app('Huya');
        $data = $app->get($request->get());
        if(!$data){
            $this->error($app->getError());
        }
        
        $this->success('ok',$data);
        
    }
    
    public function order_ok(Request $request)
    {
        $center  = $request->get();
        $app = $this->app('Huya');
        $data = $app->ok($request->post());
        
        $this->success('ok',$data);
        
    }
    
    public function order_error(Request $request)
    {
      
      
      
    }
    
    public function qr(Request $request)
    {
        
       /* $qrurl = $request->post('qr');
        
        //$imageData = str_replace(['data:image/png;base64,', 'data:image/jpeg;base64,', 'data:image/bmp;base64,'], '', $base64);
        //$imageData = base64_decode($imageData);
        $imageData = file_get_contents($qrurl);
        $filePath = 'runtime/' . rand(100000,999999) . '.png';
        file_put_contents($filePath, $imageData);
        // 创建二维码实例时传入内容
        // 1. 指定二维码图片路径
         
        // 2. 创建QRCode Reader实例
        $qrcode = new QrReader($filePath);
         
        // 3. 解析二维码内容
        $text = $qrcode->text();*/
        $this->success($request->param());
      
    }
    
    public function robot_bind(Request $request)
    {
        
        $username = $request->get('username');
        if(!$username){
            $this->error('登录账号不可为空');
        }
        
        $user = Pro::model('User')->where('username',$username)->find();
        if(!$user){
            $this->error('用户不存在');
        }
        
        $this->success('绑定成功');
        
    }
    
    public function robot_query(Request $request)
    {
        
        $key = 'order_success_cache';
        $cache = ThinkCache::get($key);
        if($cache){
            return json($cache);
        }
      
        $users = Pro::model('User')->seachlist();
        
        $list = Pro::model('Order')->where('status',10)->order('id desc')->limit(50)->select();
        
        $result = [];
        
        foreach ($list as $order){
            $result[] = [
                'username' => $users[$order['sup_user_id']],
                'orderid'  => $order['orderid'],
                'fee'      => $order['fee'],
                'paytime'  => $order['paytime'],
            ];
            $result[] = [
                'username' => $users[$order['user_id']],
                'orderid'  => $order['orderid'],
                'fee'      => $order['fee'],
                'paytime'  => $order['paytime'],
            ];
        }

        ThinkCache::set($key,$result,60);
        
        return json($result);
        
    }
    
    public function app()
    {
        
        $uri = request()->path();
        $uri = explode('/',$uri);
        if(count($uri) != 6){
            return 'error';
        }
        $className  = ucfirst($uri[4]);
        $methodName = $uri[5];
        
        $params = request()->all();
      
        $fullClassName = '\\app\\api\\module\\' . $className;
        
        $instance = new $fullClassName();

        if (!class_exists($fullClassName)) {
            return 'error1';
        }
        
        if (!method_exists($instance, $methodName) || !is_callable([$instance, $methodName])) {
            return 'error2';
        }
        
        return $instance->$methodName($params);
      
    }
    
}