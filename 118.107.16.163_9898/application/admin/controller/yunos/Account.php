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
class Account extends Backend
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
            'access' => \app\admin\model\yunos\Access::seachlist($this->is_boss),
            'user' => \app\admin\model\Admin::seachlist($this->limitQuery('id')),
            'switch' => [ 1 => '上线' ,0 => '下线' ],
            'pay_type' => [
                1 => 'QQ支付',
                2 => '微信支付',
                3 => '支付宝支付',
            ],
            'number_list' => [
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
    public function index($type = 0)
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
                    ->where('ck_pool',$type)
                    ->where($where)
                    ->where($this->limitQuery())
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','user_id','mid','access_id','limit_fee','in_fee','switch','remarks','runinfo','createtime','name','is_my','in_number','ck','limit_day_in_number','limit_day_pull_number','active_time','account_state','lock_time','counts','match_number','sup_info','value1','value2','value3','value4','value5']);
                $row->visible(['access']);
                $row->visible(['user.nickname','user.username']);
				$row->getRelation('access')->visible(['code','name','image','pay_type']);
				
				$row->is_my = $row->user_id == $this->auth->id ? true : false;
				$row->ck = queryck($row->ck);
				
				$active_time_str  = amitime($row->active_time);
				$row->active_time = $active_time_str;
				
				if($row->lock_time >= date("Ymd")){
				    $row->lock_time = true;
				}else{
				    $row->lock_time = false;
				}
				
				$row->counts = $row->counts($row->id,'Ymd');
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        if($type != 1){
            
            $access = $this->model('Access')->lists($this->is_boss);
            foreach ($access as &$v){
                $v['pool'] = $v->pool($v['pool']);
            }
            $this->view->assign('access', $access);
            return $this->view->fetch();
        }
        
        $access_id = $this->request->get('access_id');
        $data = [
            'title' => $this->seachlist['access'][$access_id],
            'access_id' => $access_id
        ];
        
        $access = $this->model('Access')->find($access_id);
        $pool   = $access->pool($access->pool);
        $this->view->assign('pool', $pool);
 
        $this->view->assign('data', $data);
        return $this->view->fetch('ckaccount');
    }
    
    public function add()
    {
        
        if (false === $this->request->isPost()) {
            
            $access = $this->model('Access')->find($this->request->get('access_id'));
            if(!$access){
                return '通道不存在';
            }
            error_reporting(0);
            $row = [
                'access_id' => $access['id'],
                'remarks' => '',
                'limit_min_fee' => 0,
                'limit_max_fee' => 0,
                'limit_fee' => 0,
                'limit_day_in_number' => 0,
                'limit_day_pull_number' => 0,
            ];
            
            $this->view->assign('row', $row);
            return $this->view->fetch('yunos/account/moudel/tpl-'.$access['id']);
        }
        
        $params = $this->request->post('row/a');
        
        if(!isset($params['config'])){
            $params['config'] = [];
        }
        $params['config'] = json_encode($params['config']);
        
        $params['user_id'] = $this->auth->id;

        $params['mid'] = strtoupper(\fast\Random::alnum());
        
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
        $row = $this->model->RenderField($row);
        //return json_encode($row);die;

        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            error_reporting(0);
            $this->view->assign('row', $row);
            return $this->view->fetch('yunos/account/moudel/tpl-'.$row['access_id']);
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        if(!isset($params['config'])){
            $params['config'] = [];
        }
        $params['config'] = json_encode($params['config']);

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
    
    public function app_load($ids = null)
    {
        $page = $this->request->get('page');
        $row = $this->model->where($this->limitQuery('user_id'))->find($ids);
        if (!$row) {
            return json(['不存在或无权限']);
        }
        $name = $row->access->module;//strtolower($row->access->module);
       
        $class = '\\app\\admin\\controller\\yunos\\apppage\\' . $name;

        $app = new $class;
        
        
        return $app->$page();
        
    }
    
    public function ckaccount_inport()
    {
        
        $access_id = $this->request->post('access_id');
        $content   = $this->request->post('excel');
        
        if(!$access_id || !$content){
            $this->error('缺少参数');
        }
        
        $array = $this->readExcel($content);

        if(!$array){
            $this->error('格式错误');
        }
        
        foreach ($array as $v){
            
            if($v['name']){
                $is_data = $this->model->where(['user_id' => $this->auth->id ,'name' => $v['name'] ,'access_id' => $access_id])->find();
                if($is_data){
                    continue;
                }
            }
            $data = $v;
            $data['user_id']   =  $this->auth->id;
            $data['access_id'] =  $access_id;
            $data['ck_pool']   =  1;
            
            $this->model->insert($data);
            
        }
        
        $this->success('导入成功');

    }
    
    public function readExcel($str){

        if(!$str){
            return [];
        }
        $row = explode("\n",$str);

        if(!$row){
            return [];
        }
        unset($row[0]);
        $result = [];
        foreach ($row as $column){
            if(!$column){
                continue;
            }
            $item = explode("\t",$column);
            if(count($item) < 1){
                break;
            }else{
                
                $result[] = [
                    'remarks'  => isset($item[0]) ? $item[0] : '',
                    'name'     => isset($item[1]) ? $item[1] : '',
                    'value1'   => isset($item[2]) ? $item[2] : '',
                    'value2'   => isset($item[3]) ? $item[3] : '',
                    'value3'   => isset($item[4]) ? $item[4] : '',
                    'value4'   => isset($item[5]) ? $item[5] : '',
                    'value5'   => isset($item[6]) ? $item[6] : '',
                    'ck'       => isset($item[7]) ? $item[7] : ''
                ];
            }
            
        }
        
        return $result;
        
    }

}
