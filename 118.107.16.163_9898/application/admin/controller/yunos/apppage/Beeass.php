<?php

namespace app\admin\controller\yunos\apppage;

use app\common\controller\Backend;
use think\Db;
use think\Cache;
use fast\Random;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Beeass extends Backend
{

    /**
     * Account模型对象
     * @var \app\admin\model\yunos\Account
     */
    protected $model = null;
    protected $noNeedLogin = ['monitor'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yunos\Account;

    }
    
    public function index()
    {

        $ids = $this->request->get('ids');
        $row = $this->model->where($this->limitQuery('user_id'))->find($ids);
        if (!$row) {
            return json(['不存在或无权限']);
        }
        $this->view->assign('row', $row);
        return $this->view->fetch('yunos/apppage/beeass/index');
        
        
    }
    
    public function h5_login()
    {
        
        $ids    = $this->request->post('ids');
        $mobile = $this->request->post('account');
        $code    = $this->request->post('code');
        if(!$mobile || !$code){
            $this->error('手机号或验证码不可为空');
        }
        $row = $this->model->where($this->limitQuery('user_id'))->find($ids);
        if(!$row){
           $this->error('通道账号不存在');
        }
        if(!$row['value4']){
            $row->value4 = $this->deviceId();
            $row->save();
        }

        //app - $enbody = $this->enTokenBody('{"registerFrom":"FG_SYSTEM","clientVersionId":"36","channelId":"FZSSJIOS","extendInfo":{"idfa":"'. $row->value4 .'","os":"iOS16.7","model":"iPhone","modelid":"16","extendType":"SMS","templateid":"42","smsValiCode":"'. $code .'"},"mobile":"'. $mobile .'","deviceId":"'. $row->value4 .'","loginType":"1"}');
        
        $enbody = $this->enTokenBody('{"registerFrom":"FG_SYSTEM","clientVersionId":"36","channelId":"FZSSJIOS","extendInfo":{"idfa":"'. $row->value4 .'","os":"iOS16.7","model":"iPhone","modelid":"9","extendType":"SMS","templateid":"14","smsValiCode":"'. $code .'"},"mobile":"'. $mobile .'","deviceId":"'. $row->value4 .'","loginType":"1"}');
        
        $http = http_post([
            'lsp_id' => "Beeass_login_$mobile",
            'url'  => 'https://www.phone580.com/fzsUserApi/api/user/authloginv2-encrypt',
            'header' => 'Host: www.phone580.com
Content-Type: application/json; charset=utf-8
AppDnsPreliminaryAnalysis: AppDnsPreliminaryAnalysis
Connection: keep-alive
Accept: application/json
User-Agent: FZS/10.8.0 (iPhone; iOS 16.7; Scale/3.00)
Accept-Language: zh-Hans-CN;q=1',
            'body' => '{"body":"'. $enbody .'"}'
        ]);

        if(!$http){
            $this->error('KM信息,等待3秒后重试');
        }
        $data = json_decode($http,true);
        if($data['success'] != true){
            $this->error('登录信息：'.$data['message']);
        }
        $row->name   = $mobile;
        $row->ck     = $data['valueObject']['authToken'];
        $row->value2 = $data['valueObject']['exjf']['userId'];
        $row->value3 = $data['valueObject']['reAuthToken'];
        $row->account_state = 0;
        $row->active_time = time();
        $row->save();
        
        $this->success('登录成功',null,$data);
        //$html = file_get_contents('https://promotion.phone580.com/activity/fzsapp-xch5/#/');
        //$this->view->assign('html', $html);
        //return $this->view->fetch('yunos/apppage/beeass/h5_login');
        
    }
    
    public function enTokenBody($plaintext, $key = 'wae6j5goa6521awe') {
        
        //$blockSize = 16;
        //$pad = $blockSize - (strlen($plaintext) % $blockSize);
        //$plaintext .= str_repeat(chr($pad), $pad); // PKCS7 padding
    
        return base64_encode(openssl_encrypt($plaintext, 'AES-128-ECB', $key, OPENSSL_RAW_DATA));
    }
    
    public function deviceId(){
        // 生成各部分的随机十六进制字符串
        $part1 = bin2hex(random_bytes(4));  // 8字符
        $part2 = bin2hex(random_bytes(2));  // 4字符
        $part3 = bin2hex(random_bytes(2));  // 4字符
        $part4 = bin2hex(random_bytes(2));  // 4字符
        $part5 = bin2hex(random_bytes(6));  // 12字符
     
        // 组合并转换为大写
        return strtoupper(sprintf(
            "%s-%s-%s-%s-%s",
            $part1,
            $part2,
            $part3,
            $part4,
            $part5
        ));
    }
    
    public function auto_login_index()
    {

        $ids = $this->request->get('ids');
        $row = $this->model->where($this->limitQuery('user_id'))->find($ids);
        if (!$row) {
            return json(['不存在或无权限']);
        }
        $this->view->assign('row', $row);
        return $this->view->fetch('yunos/apppage/beeass/auto_login_index');
        
    }
    
    public function auto_login()
    {

        $ids    = $this->request->get('ids');
        $number = $this->request->post('number');
        $api_user = $this->request->post('api_user');
        $api_pass = $this->request->post('api_pass');
        
        $row = $this->model->where($this->limitQuery('user_id'))->find($ids);

        if(!$row){
           $this->error('通道账号不存在');
        }
        $this->error('通道账号不存在');
        if(!$number){
            $this->error('上号数量不可为空');
        }
        if(!is_numeric($number)){
            $this->error('上号数量必须为数字');
        }
        
        if($number > 30){
            $this->error('不要一次性添加太多账号');
        }
        
        $token = Cache::get('token_'.$api_user);
        if(!$token){
            $http = http_get([
                'url' => 'https://api.haozhuma.com/sms/?api=login&user='. $api_user .'&pass='.$api_pass
            ]);
            if(!$http){
                $this->error('接码平台登录失败');
            }
            $arr = json_decode($http,true);
            if(!isset($arr['token'])){
                $this->error('接码平台登录失败重新尝试');
            }
            Cache::set('token_'.$api_user,$arr['token']);
            $this->success($arr['token']);
        }
        
        for ($i = 1; $i <= $number; $i++) {
           $this->moduled('Beeass','batch_add_account',[
            'user_id' => $this->auth->id ,
            'access_id' => $row['access_id'] ,
            'token'  => $token,
            ]);
        }
        
        $this->success('任务已提交');
        
        return $this->auth->id;
        
        $response = http_post([
            'url'  => config('site.api_url').'/api/make/app/Beeass/batch_auto_login',
            'body' => []
        ]);
        
        if(!$response){
            $this->error('任务提交失败，请重试一次');
        }
        
        $this->success($response);

        
        
    }

}
