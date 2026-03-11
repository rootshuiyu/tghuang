<?php

namespace app\api\model;

use think\Model;

class OrderLog extends Model
{
    protected $name = 'order_log';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    
    public function msg($orderid,$type = 'success',$title = '',$content = null)
    {
        //success info error pending
        $data = [
            'orderid' => $orderid,
            'type'    => $type,
            'title'   => $title,
            'content' => $content,
        ];
        
        $this->create($data);
    }
    
    public function success($orderid,$title = '',$content = null)
    {
        $this->msg($orderid,'success',$title,$content);
    }
    
    public function info($orderid,$title = '',$content = null)
    {
        $this->msg($orderid,'info',$title,$content);
    }
    
    public function error($orderid,$title = '',$content = null)
    {
        $this->msg($orderid,'error',$title,$content);
    }
    
    public function pending($orderid,$title = '',$content = null)
    {
        $this->msg($orderid,'pending',$title,$content);
    }
   
    
    
    
}