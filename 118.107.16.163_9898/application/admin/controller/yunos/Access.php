<?php

namespace app\admin\controller\yunos;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Access extends Backend
{

    /**
     * Access模型对象
     * @var \app\admin\model\yunos\Access
     */
    protected $model = null;
    protected $searchFields   = ['name','code']; //快速搜索
    protected $relationSearch = false;  //关联查询

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yunos\Access;
        
        $this->seachlist = [
            'switch'   => [0 => '下线' , 1 => '上线'],
            'active_state'   => [0 => '关闭' , 1 => '开启'],
            'pay_type' => [
                'qqpay'  => 'QQ支付',
                'wxpay'  => '微信支付',
                'alipay' => '支付宝支付',
            ],
            'pay_tpl' => [
                'payment'         => '默认跳转',
                'auto_target'      => '自动跳转页面',
                'payment_auto'    => 'http+支付宝协议自动跳转',
                'alipay_url_jump' => '唤起支付宝+自动跳转H5',
                'alipay_url'      => '唤起支付宝',
                'alipay_order_str' => '支付宝APP支付参数',
                'pendent'          => '独立支付页面'
            ],
        ];
        $this->view->assign("seachlist", $this->seachlist);

    }

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $wheres = [];
            if(!$this->is_boss){
                $wheres = ['switch' => 1];
            }
            $list = $this->model
                    ->where($where)
                    ->where($wheres)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','code','name','image','switch','createtime','pay_type']);
                
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    
    /**
     * 收银台地址已迁移至 yunos/cashier（咪咕收银台地址），此处仅做重定向
     */
    public function payment()
    {
        $this->redirect('yunos/cashier/index');
    }

    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        
        $params['code'] = strtoupper(\fast\Random::alnum());

        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }

    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $pay_type_arr = [];
        foreach ($params['pay_type'] as $v){
            $pay_type_arr[] = $v;
        }

        $params['pay_type'] = implode(',',$pay_type_arr);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

}
