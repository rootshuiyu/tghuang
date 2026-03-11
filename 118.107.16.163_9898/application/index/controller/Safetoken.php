<?php

namespace app\index\controller;
use app\common\controller\Frontend;
use think\Cookie;

class Safetoken extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';
    protected $model = null;
    
    public function _initialize()
    {                                 
        parent::_initialize();
        $this->model = new \app\index\model\Safetoken;
        
    }

    public function index()
    {
        return $this->view->fetch();
    }
    
    public function authority($id)
    {
       $token = Cookie::get('session_path');
       Cookie::delete('session_path');
       if($this->request->isPost() && $token){
           
           $data = $this->model->where(['sha256' => $id ,'status' => 1])->find();
           if(!$data){
               return json(['success' => false]);
           }
           if(!$data->ua){
                $data->ua = getOs();
                $data->token = $token;
                $data->save();
                
            }
            if($data->token != $token){
                return json(['success' => false]);
            }
            
            return json(['success' => true]);
       }
        
        return $this->view->fetch();
    }
    
    public function get()
    {
        
        $token = Cookie::get('session_path');
        $f = t('s') < 31 ? 1 : 30;
        $str = '-'.t('y') . t('m') .t('d') .t('h') .t('i') . $f .$token;
        $sha1 = sha1($str);
        print_r(getFirstSixCombinedDigits($sha1));
    }

}
