<?php

namespace app\admin\controller\yunos;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Fund extends Backend
{

    /**
     * Fund模型对象
     * @var \app\admin\model\yunos\Fund
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
        $this->model = new \app\admin\model\yunos\Fund;
        $this->admin = model('Admin');
        $this->seachlist = [
            'action' => [0 => '支出', 1 => '收入'],
            'type'   => [0 => '系统变动', 1 => '手动变动']
        ];
        $this->view->assign("seachlist", $this->seachlist);
        $this->view->assign("is_boss", $this->is_boss);

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
                    ->with(['user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                
                $row->getRelation('user')->visible(['username','nickname']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    
    public function add($ids=null)
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        
        if(!in_array($params['action'],[0,1])){
            $this->error('---'.$params['action']);
        }
        
        $result = false;
        Db::startTrans();
        try {
            $admin = $this->admin->where('id' , $ids)->lock(true)->find();
            if(!$admin){
                throw new \Exception('---****---');
            }
            
            $old = $admin->fee;
            if($params['action'] == 0){
                if($admin->fee >= $params['value']){
                    $admin->fee -= $params['value'];
                    $result = true;
                }else{
                    throw new \Exception('当前商户金额不足');
                }
                
            }else{
                
                $admin->fee += $params['value'];
                $result = true;
                
            }
            
            if($result){
                $admin->save();
                
                $content = $this->auth->username.'-操作【原'. $old .' => 变为 '.$admin->fee.' 】' .$params['content'];
                
                $this->model->create([
                        'user_id' => $ids,
                        'action'  => $params['action'],
                        'type'    => 1,
                        'value'   => $params['value'],
                        'content' => $content
                    ]);
            }
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        
        $this->success();
    }

}
