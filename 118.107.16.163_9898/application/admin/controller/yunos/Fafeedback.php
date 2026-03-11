<?php

namespace app\admin\controller\yunos;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Fafeedback extends Backend
{

    /**
     * Fafeedback模型对象
     * @var \app\admin\model\yunos\Fafeedback
     */
    protected $model = null;
    protected $searchFields   = ''; //快速搜索
    protected $relationSearch = false;  //关联查询

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yunos\Fafeedback;
        $this->seachlist = [];
        $this->view->assign("seachlist", $this->seachlist);

    }





}
