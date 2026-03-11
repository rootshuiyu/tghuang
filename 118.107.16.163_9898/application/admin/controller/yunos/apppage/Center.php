<?php

namespace app\admin\controller\yunos\apptool;

use app\common\controller\Backend;
use think\Db;
use fast\Random;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Center extends Backend
{

    /**
     * Account模型对象
     * @var \app\admin\model\yunos\Account
     */
    protected $model = null;
    protected $searchFields   = ['title','remarks','mid','access.code']; //快速搜索
    protected $relationSearch = false;  //关联查询

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yunos\Account;

    }
    
    public function index($ids = null)
    {
        $app = new Alipaygm;
        return $app->index($ids);
        
        
    }

}
