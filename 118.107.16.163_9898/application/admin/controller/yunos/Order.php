<?php

namespace app\admin\controller\yunos;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{
    protected $noNeedRight = ['recent_count'];

    /**
     * Order模型对象
     * @var \app\admin\model\yunos\Order
     */
    protected $model = null;
    protected $searchFields   = ['orderid','syorder','suporder']; //快速搜索
    protected $relationSearch = false;  //关联查询

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yunos\Order;
        $this->seachlist = [
            'access' => \app\admin\model\yunos\Access::seachlist($this->is_boss),
            'account' => \app\admin\model\yunos\Account::seachlist(),
            'status' => [
                0 => '正在下单',
                1 => '下单失败',
                2 => '等待支付',
                3 => '订单超时',
                10 => '支付成功'
            ],
            'pay_type' => [
                'qqpay'  => 'QQ支付',
                'wxpay'  => '微信支付',
                'alipay' => '支付宝支付',
            ],
            'icon_type'   => [
                'success' => 'fa fa-check-circle',
                'info'    => 'fa fa-random',
                'error'   => 'fa fa-exclamation-circle',
                'pending' => 'fa fa-history',
            ],
            'user' => \app\admin\model\Admin::seachlist($this->limitQuery('id'))
        ];
        $this->view->assign("seachlist", $this->seachlist);
        $this->view->assign("statusList", $this->model->getStatusList());
        
        $this->view->assign("is_boss", $this->is_boss);
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            
            $filter = json_decode($this->request->request('filter'),true);
            $whereA = [];
            if(!empty($filter['suporder'])){
                $whereA['suporder'] = ['like',$filter['suporder'] . '%'];
            }
            
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $limitQuery = $this->limitQuery();  $sup_query = $this->limitQuerySup();

            $list = $this->model
                    //->where($whereA)
                    ->where($where)
                    //->where($this->limitQuery())
                    //->whereOr($this->limitQuerySup())
                    ->where(function ($query) use ($limitQuery,$sup_query) {
                        $query->where($limitQuery)
                              ->whereOr($sup_query);
                    })
                    ->order($sort, $order)
                    ->paginate($limit);

            
            
            $success_fee = $this->model
                    ->where($whereA)
                    ->where($where)
                    //->where($this->limitQuery())
                    ->where(function ($query) use ($limitQuery,$sup_query) {
                        $query->where($limitQuery)
                              ->whereOr($sup_query);
                    })
                    ->where('status' , 10)
                    ->sum('fee');

            foreach ($list as $row) {
                if($this->is_boss){
                    /*if($row->exptime < time() && $row->status == 2){
                        $row->status = 3;
                        $row->save();
                    }*/
                }
                $row->is_my = $row->user_id == $this->auth->id ? true : false;
                
            }

            $result = array("total" => $list->total(), "rows" => $list->items() ,'success_fee' => $success_fee);

            return json($result);
        }
        return $this->view->fetch();
    }
    
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
            
            $logger = new \yunos\Logger();
            $logData = $logger->set('order')->resid($row->orderid)->get();
            //print_r($logData);die;
            $this->view->assign('log', $logData);
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
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
    
    public function callback()
    {
        
        $orderid = $this->request->post('orderid');
        
        if(!$orderid){
            $this->success('订单号不可为空');
        }
        
        $row = $this->model->where($this->limitQuery('user_id',true))->where('orderid',$orderid)->find();
        if (!$row) {
            $this->success('不存在或无权限');
        }
        
        $http = http_post([
                'url'  => config('site.api_url').'/api/pay/sync_callback',
                'body' => ['orderid' => $orderid,'key' => config('site.sync_callback_key', 'f29be29fa30cb5a1d0b89e2c290825ac8bec567c')]
        ]);
        
        if(!$http){
            $this->success('执行无结果，需重试');
        }
        
        $this->success($http);
        
    }
    
    public function http_query()
    {
        
        $orderid = $this->request->post('orderid');
        
        if(!$orderid){
            $this->success('订单号不可为空');
        }
        
        $row = $this->model->where('orderid',$orderid)->find();
        if (!$row) {
            $this->success('不存在或无权限');
        }
        
        $http = http_post([
                'url'  => config('site.api_url').'/api/make/app/'. $row->access->module .'/http_query_order',
                'body' => ['orderid' => $orderid,'key' => config('site.sync_callback_key', 'f29be29fa30cb5a1d0b89e2c290825ac8bec567c')]
        ]);
        
        if(!$http){
            $this->success('执行无结果，需重试');
        }
        
        $this->success($http);
        
    }
    
    public function custom()
    {
        //print_r($whereQuery);die;
        
        $where = $this->limitQuery();  $whereQuery = $this->limitQuerySup();
        $wheres = function ($query) use ($where,$whereQuery){
                    $query->where($where)
                    ->whereOr($whereQuery);
                };
   
        $day_in = $this->model->where($wheres)->where('status', 10)->whereTime('createtime', 'today')->sum('fee');

        $yesterday_in = $this->model->where($wheres)->where('status', 10)->whereTime('createtime', 'yesterday')->sum('fee');
        
        
        $day_count = $this->model->where($wheres)->whereTime('createtime', 'today')->count();
        $day_success_count = $this->model->where($wheres)->where('status', 10)->whereTime('createtime', 'today')->count();
        
        $day_success = $day_count > 0 ? ($day_success_count / $day_count) * 100 : 0;
        
        $count = $this->model->where($wheres)->count();
        $success_count = $this->model->where($wheres)->where('status', 10)->count();
        
        $success = $count > 0 ? ($success_count / $count) * 100 : 0;
        
        $hour_count = $this->model->where($wheres)->whereTime('createtime', '-1 hours')->count();
        $hour_success_count = $this->model->where($wheres)->where('status', 10)->whereTime('createtime', '-1 hours')->count();
        $hour_success = $hour_count > 0 ? ($hour_success_count / $hour_count) * 100 : 0;
        
        $hour_in = $this->model->where($wheres)->where('status', 10)->whereTime('createtime', '-1 hours')->sum('fee');
        
        $result = [
            'day_in'       => $day_in . '元',
            'yesterday_in' => $yesterday_in .'元',
            'day_success'  => round($day_success,3) . '%',
            'success'      => round($success,3) . '%',
            'hour_success' => round($hour_success,3)  . '%',
            'hour_in'      => $hour_in . '元'
        ];
        
        return json($result);
        
    }
    
    /**
     * 新订单通知 - 近期待支付订单数（咪咕对标）
     */
    public function recent_count()
    {
        $this->request->filter(['strip_tags', 'trim']);
        $limitQuery = $this->limitQuery();
        $supQuery   = $this->limitQuerySup();
        $where = function ($query) use ($limitQuery, $supQuery) {
            $query->where($limitQuery)->whereOr($supQuery);
        };
        $count = $this->model
            ->where($where)
            ->where('status', 2)
            ->whereTime('createtime', '>=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->count();
        return json(['code' => 1, 'count' => $count, 'msg' => '']);
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
            $list = $this->model->where($this->model->getPk(), 'in', $ids)->where($this->limitQuerySup())->select();
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
