#php qr decoder 
> php识别二维码, 不需要安装扩展


###使用
```
$img='./qr.png';//本地路径
$img='http://www.hanchao9999.com/Application/Home/View/default/images/hanchao_wx.png';//远程路径


//通用用法
include_once('./QrReader/QrReader.php');
$qrcode = new QrReader($img);  //图片路径
$text = $qrcode->text(); //返回识别后的文本
echo $text;


//ThinkPHP3.2用法 QrReader放到\ThinkPHP\Library\Vendor\目录
vendor("QrReader.QrReader");
$qrcode = new \QrReader($img);
$text = $qrcode->text();
echo $text;

```

### 需要
```
PHP >= 5.3
GD Library
```