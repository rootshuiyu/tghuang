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
class Uban100 extends Backend
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
        $this->view->assign('appkey', $this->auth->appkey);
        return $this->view->fetch('yunos/apppage/uban100/index');
        
        
    }
    
    public function h5_login()
    {
        
        
    }


}
