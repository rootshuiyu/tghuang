<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;


class Huborder extends Model
{
    protected $name = 'huborder';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    
    public function exHubdata($data = []){
        
        if(isset($data['sha1'])){
            $huborder = $this->where('sha1',$data['sha1'])->find();
            if($huborder){
                return;
            }
        }
        
        // 启动事务
        Db::startTrans();
        try {
            
            $this->create($data);
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
    }
    
}