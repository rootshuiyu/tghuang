<?php

namespace app\admin\controller\yunos;

use app\common\controller\Backend;
use fast\Random;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Sup extends Backend
{

    /**
     * Sup模型对象
     * @var \app\admin\model\yunos\Sup
     */
    protected $model = null;
    protected $admin = null;
    protected $searchFields   = ''; //快速搜索
    protected $relationSearch = false;  //关联查询
    protected $dataLimit = 'personal';
    protected $dataLimitField = 'user_id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yunos\Sup;
        $this->seachlist = [
            'pay_type' => [
                1 => 'QQ支付',
                2 => '微信支付',
                3 => '支付宝支付',
            ],
            'access' => \app\admin\model\yunos\Access::seachlist()
        
        ];
        $this->admin = model('Admin');
        $this->view->assign("seachlist", $this->seachlist);
        
        $this->view->assign("is_boss", $this->is_boss);

    }


    /**
     * 查看
     */
    public function index()
    {
        
        $admin = $this->admin->find($this->auth->id);
        $row = [
            'appid'   => $admin->appid,
            'appkey'  => $admin->appkey,
            'fee'     => $admin->fee,
            'api_url' => sconfig('api_url')
        ];
        
        $this->view->assign("row", $row);
        return $this->view->fetch();
        
        
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

            $list = $this->model
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','pid','key','des','createtime','user.nickname']);
                $row->user = $row->user;
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
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
        $params['user_id'] = $this->auth->id;
        $params['pid'] = \fast\Random::numeric(8);
        $params['key'] = \fast\Random::uuid();


        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
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
        $params['user_id'] = $this->auth->id;
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
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
