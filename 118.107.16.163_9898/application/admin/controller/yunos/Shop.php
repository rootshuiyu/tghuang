<?php

namespace app\admin\controller\yunos;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Shop extends Backend
{

    /**
     * Shop模型对象
     * @var \app\admin\model\yunos\Shop
     */
    protected $model = null;
    protected $searchFields   = ''; //快速搜索
    protected $relationSearch = false;  //关联查询
    protected $dataLimit = 'personal';
    protected $dataLimitField = 'user_id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yunos\Shop;
        $this->seachlist = [
            'access' => \app\admin\model\yunos\Access::seachlist(),
        ];
        $this->view->assign("seachlist", $this->seachlist);

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
        $params['user_id'] = $this->auth->id;
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



}
