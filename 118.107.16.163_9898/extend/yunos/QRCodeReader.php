<?php

namespace yunos;
/**
 * 二维码识别类库（ImageMagick + ZBar方案）
 * 需要安装：ImageMagick、ZBar-tools
 * Ubuntu安装命令：sudo apt-get install imagemagick zbar-tools
 */

class QRCodeReader {
    private $imagePath;
    private $preprocessedPath;
    private $debug = true;

    /**
     * 初始化识别器
     * @param string $imagePath 原始图片路径
     * @param bool $debug 是否开启调试模式
     */
    public function __construct($imagePath, $debug = false) {
        $this->imagePath = $imagePath;
        $this->debug = $debug;
        $this->preprocessedPath = sys_get_temp_dir() . '/qr_preprocessed_' . md5(time()) . '.png';
    }

    /**
     * 执行二维码识别
     * @return string|null 识别结果，失败返回null
     */
    public function recognize() {
        try {
            // 图像预处理流程
            $this->preprocessImage();
            
            // 使用ZBar进行识别
            $result = $this->decodeWithZBar();
            
            // 清理临时文件
            if (!$this->debug && file_exists($this->preprocessedPath)) {
                unlink($this->preprocessedPath);
            }
            
            return $result;
        } catch (\Exception $e) {
            error_log('QR Recognition Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ImageMagick图像预处理
     */
    private function preprocessImage() {
        $cmd = "convert \"{$this->imagePath}\" \\
            -colorspace Gray \\
            -auto-level \\
            -enhance \\
            -contrast-stretch 0 \\
            -resize 200% \\
            -define filter:blur=0.5 \\
            -define filter:support=2 \\
            \"{$this->preprocessedPath}\" 2>&1";

        $this->executeCommand($cmd, "图像预处理失败");
    }

    /**
     * 使用ZBar进行二维码识别
     */
    private function decodeWithZBar() {
        $cmd = "zbarimg -q --raw \"{$this->preprocessedPath}\" 2>&1";
        $output = $this->executeCommand($cmd, "二维码识别失败");
        
        // 清理不可见字符
        $result = preg_replace('/[^a-zA-Z0-9\:\/\?\=\&\.\-_]/', '', $output);
        return trim($result);
    }

    /**
     * 执行命令行操作
     */
    private function executeCommand($cmd, $errorMessage) {
        exec($cmd, $output, $returnCode);
        
        if ($this->debug) {
            echo "执行的命令：$cmd\n";
            echo "返回状态：$returnCode\n";
            echo "输出结果：" . implode("\n", $output) . "\n";
        }

        if ($returnCode !== 0) {
            throw new \Exception("$errorMessage (错误码：$returnCode)");
        }

        return implode("\n", $output);
    }

    /**
     * 析构函数：清理临时文件
     */
    public function __destruct() {
        if (file_exists($this->preprocessedPath)) {
            unlink($this->preprocessedPath);
        }
    }
}

/******************
 
try {
    $reader = new QRCodeReader('/path/to/your/qrcode.jpg', true);
    $result = $reader->recognize();
    
    if ($result) {
        echo "识别成功：\n" . $result;
    } else {
        echo "未识别到有效二维码";
    }
} catch (Exception $e) {
    echo "发生错误：" . $e->getMessage();
}* 使用示例
 ******************/
?>