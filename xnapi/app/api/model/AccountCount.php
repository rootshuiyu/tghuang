<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;


class AccountCount extends Model
{
    protected $name = 'account_count';
    protected $pk = 'id';
    
    public function getRecord($account_id,$type = 'Ymd'){
        
        $date = date($type);
        $sha1 = sha1($account_id.$date);
        return $this->where('sha1',$sha1)->find();
        
    }
    
    public function record($account_id,$fee = 0){
        
        $this->saveData($account_id,$fee,$type = 'Ym');
        $this->saveData($account_id,$fee,$type = 'Ymd');
        //$this->saveData($account_id,$fee,$type = 'YmdH');
        
    }
    
    public function saveData($account_id,$fee = 0,$type = 'Ymd'){
        
        // 启动事务
        Db::startTrans();
        try {
            
            $date = date($type);
            $sha1 = sha1($account_id.$date);
            $model = $this->where('sha1',$sha1)->lock(true)->find();

            if(!$model){
                $data = [
                    'account_id' => $account_id,
                    'type' => $type,
                    'date' => $date,
                    'in_number'   => 0,
                    'pull_number' => 1,
                    'fee' => $fee,
                    'sha1' => $sha1
                ];
                $model = $this->create($data);
            }else{
                
                if($fee > 0){
                    $model->in_number += 1;
                    $model->fee       += $fee;
                }else{
                    
                    $model->pull_number += 1;
                }
                
                $model->save();
                
            }
            
            
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
        if($type == 'Ymd'){
            $this->setLimitData($account_id,$model);
        }
        
        return $model;
        
    }
    
    public function setLimitData($account_id,$limitData){
        
        $account = Account::where('id',$account_id)->find();

        if( $account->limit_day_in_number > 0 && 
            $limitData['in_number'] >= $account->limit_day_in_number){
            $account->lock_time = date("Ymd");
            $account->runinfo = '订单达到限制';
            $account->switch = 0;
        }

        if( $account->limit_day_pull_number > 0 && 
            $limitData['pull_number'] >= $account->limit_day_pull_number ){
            $account->lock_time = date("Ymd");
            $account->runinfo = '订单达到限制';
            $account->switch = 0;
        }
        
        $account->save();
        
    }
    
}