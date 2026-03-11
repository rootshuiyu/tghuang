<?php
include_once('./QrReader/QrReader.php');
$img='./qr.png';//本地路径
$img='http://www.hanchao9999.com/Application/Home/View/default/images/hanchao_wx.png';//远程路径
$qrcode = new QrReader($img);  //图片路径
$text = $qrcode->text(); //返回识别后的文本
echo $text;


/*
//ThinkPHP3.2用法 QrReader放到\ThinkPHP\Library\Vendor\目录
vendor("QrReader.QrReader");
$qrcode = new \QrReader($img);
$text = $qrcode->text();
echo $text;


*/