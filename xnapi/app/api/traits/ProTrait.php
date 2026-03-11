<?php

namespace app\api\traits;

trait ProTrait
{
    
    public function model($name = null)
    {
        $class = '\\app\\api\\model\\' . $name;
        $app = new $class;
        return $app;
    }
    
    public function checkInit($account,$data)
    {
        $result = true;
        foreach ($data as $k => $v){
            
            if(!$account[$k]){
                $this->model('Account')->closeAccount($account->id,$v);
                $result = false;
                break;
            }
            
        }
        
        return $result;
        
    }
    
    
    
    
    
}