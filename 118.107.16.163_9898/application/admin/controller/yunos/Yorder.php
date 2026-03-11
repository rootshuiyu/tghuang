<?php

namespace app\admin\controller\yunos;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Yorder extends Backend
{

    /**
     * Yorder模型对象
     * @var \app\admin\model\yunos\Yorder
     */
    protected $model = null;
    protected $searchFields   = ''; //快速搜索
    protected $relationSearch = false;  //关联查询
    protected $dataLimit = 'personal';
    protected $dataLimitField = 'user_id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yunos\Yorder;
        $this->seachlist = [
            'pay_type' => [
                1 => 'QQ支付',
                2 => '微信支付',
                3 => '支付宝支付',
            ]
        ];
        $this->view->assign("seachlist", $this->seachlist);

    }


    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                    ->with(['access','account'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','syorder','fee','content','createtime']);
                $row->visible(['access']);
				$row->getRelation('access')->visible(['code','pay_type']);
				$row->visible(['account']);
				$row->getRelation('account')->visible(['mid']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

}
