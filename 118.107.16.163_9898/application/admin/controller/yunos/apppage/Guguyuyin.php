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
class Guguyuyin extends Backend
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
        return $this->view->fetch('yunos/apppage/guguyuyin/index');
        
        
    }
    
    public function send_sms()
    {
        $phone = $this->request->post('phone');
        $timestamp = time();
        $sign = $this->md5_sign_verbose('BIWoZ3nM8HkOsu1XbUiuIBEArVqyqS9NPG3fDnbQ5NBeSrJre4jpm8iP1ASMOh/f2uw2foLb/96wbqaB7x79B7w==',1762081511);
return $sign;
        $http = (new \yunos\HttpRequest())
        ->url('http://103.207.69.252:9898/demo/gugu/decode.html')
        ->method('POST')
        ->header('')
        ->body('phone='. $phone .'&public_app_channel=BPOURAPPLE&public_app_flg=bpour&public_app_os=ios&public_app_version=1.0.3&public_app_version_code=188&public_device_id=1418e9600de9419ea7b04f0cbf3dd039&public_idfa=00000000-0000-0000-0000-000000000000&sign=8cd73e68413f1550e3f12cbc3013e5e5&timestamp=1762081511')
        ->ex();
        if(!$http->getBody()){
            $this->error('失败');
        }
        $data = $http->getBody();
        
        
    }
    
    public function device_id(){
        return md5(rand(10000,99999));
    }
    
    public function md5_sign_verbose($token = '', $timestamp = '') {
        /*
         * MD5签名函数（详细版本）
         */
        
        $data = $token . "_" . $timestamp . "_pVnL4xDq8WfTzB7s";
        
        $hex_digest = md5($data);
        return $hex_digest;
    }
    
    public function decrypt_aes_cbc($encrypted_data) {
        // 配置参数
        $key = 'jLPxjtrryxNMF3fP';
        $iv = 'aI0noh23QBCVYk0T';
        
        // 步骤1: Base64解码
        $encrypted_bytes = base64_decode($encrypted_data);
    
        // 步骤2: AES/CBC解密
        $decrypted_padded = openssl_decrypt($encrypted_bytes, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        
        if ($decrypted_padded === false) {
            return 'AES解密失败';
        }
        
        //return $decrypted_padded;
        
        // 步骤3: 去除PKCS7填充
        //$pad = ord($decrypted_padded[strlen($decrypted_padded) - 1]);
        //$decrypted_data = substr($decrypted_padded, 0, -$pad);
        
        // 步骤4: Base64解码
        $base64_decoded = base64_decode($decrypted_padded);
    
        // 步骤5: Gzip解压缩
        $decompressed_data = gzdecode($base64_decoded);
        
        if ($decompressed_data === false) {
            return 'Gzip解压缩失败';
        }
        
        return $decompressed_data;
    }


}
