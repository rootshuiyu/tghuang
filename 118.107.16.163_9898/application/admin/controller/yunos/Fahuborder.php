<?php

namespace app\admin\controller\yunos;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Fahuborder extends Backend
{

    /**
     * Fahuborder模型对象
     * @var \app\admin\model\yunos\Fahuborder
     */
    protected $model = null;
    protected $layout = '';
    protected $searchFields   = ''; //快速搜索
    protected $relationSearch = false;  //关联查询

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yunos\Fahuborder;
        $this->seachlist = [
            'moudel' => [
                'Miaoappcard' => '交易猫卡',
            ],
        ];
        $this->view->assign("seachlist", $this->seachlist);

    }
    
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch('yunos/fahuborder/migu_index');
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->model
            ->where($where)
            ->where($this->limitQuery('user_id'))
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }


}
