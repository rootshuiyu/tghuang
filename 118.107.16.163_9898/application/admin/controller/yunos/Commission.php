<?php

namespace app\admin\controller\yunos;

use app\common\controller\Backend;
use app\admin\model\yunos\Fund;

/**
 * 抽佣点位记录（咪咕对标）
 * 展示系统自动扣佣记录（Fund 中 action=0 支出、type=0 系统）
 */
class Commission extends Backend
{
    protected $noNeedRight = ['index'];
    protected $layout = '';
    protected $model = null;
    protected $dataLimit = 'personal';
    protected $dataLimitField = 'user_id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new Fund;
    }

    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->with(['user'])
                ->where($where)
                ->where('action', 0)
                ->where('type', 0)
                ->where($this->limitQuery())
                ->order($sort ?: 'id', $order ?: 'desc')
                ->paginate($limit);
            foreach ($list as $row) {
                $row->getRelation('user')->visible(['username', 'nickname']);
            }
            return json(['total' => $list->total(), 'rows' => $list->items()]);
        }
        return $this->view->fetch('yunos/commission/migu_index');
    }
}
