<?php

namespace app\common\service;


use think\facade\Db;
use think\facade\Cache;
use app\common\model\v2\User;
use app\common\model\v2\Room;
use app\common\model\v2\Sysmission;

/**
 * Socket服务
 * Class 
 */
class Socket
{

    //建立连接
    public static function onLink($UserInfo)
    {
        $socket_id = $UserInfo['socket_id'];
        print_r($socket_id."【上线】".$UserInfo['nickname']."\n");
        //$this->UserModel = new \app\common\model\User;
        //return '2123333';
        //$user = User::find($UserInfo['id']);
        ////$user->onlinede = 0;
        //$user->save();
        
        
    }
    
    public static function onMsg()
    {
        
        
        
    }
    
    //关闭连接
    public static function offLink($userInfo)
    {
        $socket_id = $userInfo['socket_id'];
        print_r($socket_id."【下线】".$userInfo['nickname']."\n");
        
        $user = User::find($userInfo['id']);
        /*$user->onlinede = 1;
        $user->save();*/
        
        $room = Room::where('mid',$user['mid'])->find();
        
        Sysmission::AddMiss('Disconnect',[
            'user_id' => $user['id'],
            'groupid' => $room['groupid']
        ],true);
        
        
        
        
    }
    
    
    
}