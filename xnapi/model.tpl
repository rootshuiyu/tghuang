<?php

/**
 * 作者：__TIME__
 * QQ：743395324
 * __TIME__
 */

namespace app\common\model\v2;

use think\Model;

class __MODLE__ extends Model
{
    protected $name = '__TABLENAME__';
    protected $pk = 'id';
    
    // 设置字段信息
    /*protected $schema = [
        'id'          => 'int',
        'name'        => 'string',
        'status'      => 'int',
        'score'       => 'float',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];*/
    
    //protected $connection = 'db_config';
    //protected $field = [];
    
    //protected $createTime = 'create_at';
    //protected $updateTime = 'update_at';
    
    
    
}


//一键生成MODEL类文件

$list = \think\facade\Db::query("SHOW TABLES");
        
        $Tplmodel = file_get_contents('model.tpl');
        
        $to = 'app/common/model/v2/'; //model_dev
        foreach ($list as $key => $row) {
            $field = reset($row);
            //print_r($field);
            $nosuffix = str_replace('xbb_','',$field);
            $field = $this->underscore_to_camelcase($nosuffix);
            $tableList[reset($row)] = $field;
            
            $tpl = str_replace('__TIME__',date("Y-m-d H:i:s"),$Tplmodel);
            $tpl = str_replace('__MODLE__',$field,$tpl);
            $tpl = str_replace('__TABLENAME__',$nosuffix,$tpl);
            
            $filePath = $to .$field.'.php';
            if(!file_exists($filePath)){
                file_put_contents($to .$field.'.php',$tpl);
            }
        }

