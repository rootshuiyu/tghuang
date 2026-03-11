<?php

namespace app\admin\controller\yunos\apptool;

use app\common\controller\Backend;
use think\Db;
use fast\Random;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Alipaygm extends Backend
{

    /**
     * Account模型对象
     * @var \app\admin\model\yunos\Account
     */
    protected $model = null;
    protected $noNeedLogin = ['monitor'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yunos\Account;

    }
    
    public function index($ids = null)
    {
        $row = $this->model->where($this->limitQuery(true))->find($ids);
        if (!$row) {
            return json(['不存在或无权限']);
        }
        $tpl = $row->access->app_tool;
        if(!$tpl){
            return '暂无配置'.$ids;
        }
        
        
        $url = config('site.api_url').':9898/sup.php/yunos/apptool/Alipaygm/monitor?ids='.$row['id'].'&appkey='. hash('sha256',$row['mid']);
        
        $this->view->assign('url', $url);
        
        $this->view->assign('row', $row);
        return $this->view->fetch('index');
        
        
    }
    
    public function monitor($ids = null)
    {
        
        $row = $this->model->where(['id' => $ids])->find($ids);
        
        if (!$row || hash('sha256',$row['mid']) != $this->request->get('appkey')) {
            return json(['不存在或无权限']);
        }
        
        
        $tpl = $row->access->app_tool;
        if(!$tpl){
            return '暂无配置'.$ids;
        }
        $this->view->assign('row', $row);
        return $this->view->fetch();
        
        
    }

}
