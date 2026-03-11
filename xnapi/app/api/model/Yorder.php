<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;

class Yorder extends Model
{
    protected $name = 'yorder';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    
    public function addYorder($data)
    {
        
        if(isset($data['sha1'])){
            if($this->where('sha1',$data['sha1'])->find()){
                return false;
            }
        }
        
        $user_id = Account::where('mid',$data['mid'])->column('user_id');
        
        // 启动事务
        Db::startTrans();
        try {
            $data['user_id'] = $user_id;
            $this->save($data);
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
        return true;
        
        
    }
    
}