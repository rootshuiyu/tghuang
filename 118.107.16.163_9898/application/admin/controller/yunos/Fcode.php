<?php

namespace app\admin\controller\yunos;

use app\common\controller\Backend;
use think\Db;
use fast\Random;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Fcode extends Backend
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
        
        
        $this->seachlist = [
            'access' => \app\admin\model\yunos\Access::seachlist(),
            'user' => \app\admin\model\Admin::seachlist($this->limitQuery('id')),
            'switch' => [ 1 => '上线' ,0 => '下线' ],
            'pay_type' => [
                1 => 'QQ支付',
                2 => '微信支付',
                3 => '支付宝支付',
            ],
            'limit_day_number' => [
                1  => '日限单' ,
                2  => '日限单' ,
                3  => '日限单' ,
                4  => '日限单' , 
                5  => '日限单' ,
                6  => '日限单' ,
                7  => '日限单' ,
                8  => '日限单' ,
                9  => '日限单' ,
                10 => '日限单' ,
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
                    ->with(['access','user'])
                    ->where($where)
                    ->where('access.id',7)
                    ->where($this->limitQuery())
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','user_id','mid','access_id','limit_fee','in_fee','switch','remarks','runinfo','createtime','name','is_my','in_number','ck','limit_day_number','config','sconfig']);
                $row->visible(['access']);
                $row->visible(['user.nickname','user.username']);
				$row->getRelation('access')->visible(['code','name','image','pay_type']);
				$row->is_my = $row->user_id == $this->auth->id ? true : false;
				$row->ck = queryck($row->ck);
				$row->sconfig = $row->sconfig;
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
        //$params = $this->pro(['access_id','limit_fee','in_fee','config_value'],$params);
        $params['user_id'] = $this->auth->id;
        $params['mid'] = \fast\Random::alnum();
        
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

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
        
        
        $row = $this->model->where($this->limitQuery('user_id',true))->find($ids);
        
        if (!$row) {
            $this->error('不存在或无权限');
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            
            //$config = $row->config ? json_decode($row->config,true) : null;
            
            //$row->config = $config ? $row->config : $row->access->config_tpl;
            
            
            
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        //$params = $this->pro(['access_id','limit_fee','in_fee','config_value'],$params);
        
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
    
    /**
     * 批量更新
     *
     * @param $ids
     * @return void
     */
    public function multi($ids = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $ids = $ids ?: $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        if (false === $this->request->has('params')) {
            $this->error(__('No rows were updated'));
        }
        parse_str($this->request->post('params'), $values);
        
        $count = 0;
        Db::startTrans();
        try {
            $list = $this->model->where($this->model->getPk(), 'in', $ids)->where($this->limitQuery('user_id',true))->select();
            foreach ($list as $item) {
                $count += $item->allowField(true)->isUpdate(true)->save($values);
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were updated'));
    }
    
    public function del($ids = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ?: $this->request->post("ids");
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = $this->model->where($pk, 'in', $ids)->where($this->limitQuery('user_id',true))->select();

        $count = 0;
        Db::startTrans();
        try {
            foreach ($list as $item) {
                $count += $item->delete();
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were deleted'));
    }
    
    public function trade($ids = null)
    {
        
        $row = $this->model->where($this->limitQuery())->find($ids);
        
        if (!$row) {
            return json(['不存在或无权限']);
        }
        $row->ck = queryck($row->ck);
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
    
    public function apptool($ids = null)
    {
        
        $row = $this->model->where($this->limitQuery(true))->find($ids);
        if (!$row) {
            return json(['不存在或无权限']);
        }
        
        $this->view->assign('row', $row);
        return $this->view->fetch('yunos/apptool/tunapp');
        
        
    }

}
