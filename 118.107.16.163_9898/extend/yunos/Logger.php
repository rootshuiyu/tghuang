<?php

namespace yunos;

class Logger {
    private $logName;
    private $logId;
    private $logDir = '/www/wwwroot/118.107.16.163_9898/runtime/yunos_logs/';
    
    // 设置日志名称
    public function set($name) {
        $this->logName = $name;
        return $this;
    }
    
    // 设置日志ID
    public function resid($id) {
        $this->logId = $id;
        return $this;
    }
    
    public function success($title,$content = null) {
        $this->save('success',$title,$content);
    }
    
    public function error($title,$content = null) {
        $this->save('error',$title,$content);
    }
    
    public function info($title,$content = null) {
        $this->save('info',$title,$content);
    }
    
    public function pending($title,$content = null) {
        $this->save('pending',$title,$content);
    }
    
    // 写入日志
    public function save($type,$title,$content = null) {
        if (empty($this->logName) || empty($this->logId)) {
            throw new Exception('Log name and ID must be set before writing');
        }
        
        $data = [
            'res_id'  => $this->logId,
            'type'    => $type,
            'title'   => $title,
            'content' => $content,
            'createtime' => date("Y-m-d H:i:s"),
        ];
        
        $dir = $this->logDir . $this->logName . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $file = $dir . $this->logId . '.log';
        $existingData = [];
        
        // 获取文件锁
        $lockFile = $file . '.lock';
        $lock = fopen($lockFile, 'w');
        if (!flock($lock, LOCK_EX)) {
            throw new Exception('Could not obtain lock for log file');
        }
        
        // 读取现有数据
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $existingData = json_decode($content, true) ?: [];
        }
        
        // 确保existingData是索引数组
        if (!is_array($existingData) || !array_is_list($existingData)) {
            $existingData = [];
        }
        // 确保新数据是关联数组才追加
        if (is_array($data) && !array_is_list($data)) {
            $existingData[] = $data;
        } else {
            $existingData[] = ['data' => $data];
        }
        
        // 写入文件
        file_put_contents($file, json_encode($existingData,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )); //JSON_PRETTY_PRINT
        
        // 释放锁
        flock($lock, LOCK_UN);
        fclose($lock);
        unlink($lockFile);
        
        return $this;
    }
    
    // 读取日志
    public function get() {
        if (empty($this->logName) || empty($this->logId)) {
            throw new Exception('Log name and ID must be set before reading');
        }
        
        $file = $this->logDir . $this->logName . '/' . $this->logId . '.log';
        
        if (!file_exists($file)) {
            return [];
        }
        
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        // 确保始终返回数组
        if (!is_array($data) || !array_is_list($data)) {
            return [];
        }
        return $data;
    }
}