# checkout 收银台-H5

## 1.基本概述

此项目为商户提供快速接入 checkout 收银台功能，集成了微信,支付宝,银联云闪付,花呗分期等多种支付方式，商户只需要配置相关的支付参数即可用微信，支付宝，银联等 app 进行 h5 扫码支付。

## 2.目录结构介绍

```js
|-MP_verify_ioJ6xLg1sgvV28Kc.txt  //微信支付需要上产到服务器验证的文件
|-cashier.html                    //收银台支付页面
|-config
| |-index.js                      //项目基本配置，包括支付，页面跳转等
|-css
| |-reset.css                     //页面重置的样式表
|-fonts                           //页面字体文件
|-goodsDetail.html                //商品详情页面
|-http.js                         //前端ajax请求封装
|-img                             //项目的图片资源
|-monitor.js                      //项目的前端监控SDK
|-orderConfirm.html               //订单确认页面
|-pay.js                          //支付页面处理逻辑
|-payFail.html                    //支付失败页面
|-paySuccess.html                 //支付成功页面
|-util.js                         //项目用到的工具函数，如获取参数，授权跳转函数等
```

## 3.接入指南

### 3.1 全屏接入指南

在斗拱收银台若模版中选择独立页面，则下载的模板中则为全屏收银台模版页面，只需要在 config/index.js 配置相关的支付配置信息即可拉起支付

<img src="https://us1.myximage.com/2021/12/22/68a52a3563fdb8a6da3240fcc88c5c03.png" alt="image" style="zoom:20%" />

### 3.2 半屏接入指南

若在斗拱收银台若模版中选择底部弹出，则下载的模板中则为半屏收银台模版页面，只需要在 config/index.js 配置相关的支付配置信息即可拉起支付

<img src="https://us1.myximage.com/2021/12/22/fc9e1c7a93580471aecb82a8f3966657.png" alt="image" style="zoom:20%;" />

### 3.3 前端监控 SDK 调用

```js
// SDK初始化
MonitorMinSDK.init({
  pid: 'dg-checkout',
  appToken: 'app-226f04f1-f5ae-4ac3-b420-032b518cd0e9',
  appKey: '24e67f6771036c5cd6225eebe1a99671',
  isDev: false,
});
// SDK自定义埋点上报
MonitorMinSDK.track(
  '0090_00006404',
  {
    huifu_id: 'huifuId', //汇付商户号
    os: 'h5',
    page: 'full', //full：全屏,half：半屏
  },
  true
);
//以上参数无需修改
```

### 3.4 下单成功调用活动埋点数据

```js
function uploadPayInfo() {
  var params = {
    product_id: 'productId', //产品号
    sys_id: 'sysId', //系统号
    data: {
      huifu_id: 'huifuId', //汇付商户号
      resource: 'H5',
      id_code: 'co',
      action: 'CALL',
      ref_req_date: dayjs().format('YYYYMMDD'),
      ref_req_seqid: mer_ord_id, //orderID
    },
  };
  var baseURL = '';
  if (location.host.indexOf(configObj.productionEnvHost) > -1) {
    baseURL = configObj.prodUploadPayAPI;
  } else {
    baseURL = configObj.testUploadPayAPI;
  }
  axios.post(baseURL, params);
}
```

### 3.4 注意事项

* 当用户在自己页面跳转到收银台页面，则需要给收银台页面 url 传递 price 金额，如:http://xxx.xx/cashier.html?price=9.99

## 4.配置参数介绍

带有*的配置项以实际的业务进行调整，可查看 config/index.js
|参数|类型|说明|
|:---- |:---|:---- |
huifuId|string|汇付天下斗拱平台商户号
productId|string|汇付天下斗拱平台产品号
sysId|string|汇付天下斗拱平台系统号码
mobile.payTime|string|移动端收银台支付剩余时间倒计时,单位 min
mobile.backHomeUrl|string|移动端收银台支付完回跳地址(若在微信,支付宝，银联等 app 内完成支付，此链接无效)
isDebug|boolean|默认在 h5 的测试服引入调试插件
api.createPay| string|下单接口(*)
api.queryPay| string|下单查询接口(_)
api.wxAuth|string |获取微信 openId(_)
api.unionpayAuth|string |获取银联 userId(_)
testEnvHost| string|收银台页面测试环境域名(_)
productionEnvHost|string |收银台页面生产环境域名(_)
testBaseURL|string |接口测试 API 前缀(_)
prodBaseURL| string|接口生产 API 前缀(\*)
prodUploadPayAPI| string|下单成功标识 API，必须调用此接口上传数据成功，才可获得对应的奖励

## 参考资料

1.项目流程图
<img src="https://us1.myximage.com/2021/08/26/64e942fb7811a714e80c3c66c3063e03.jpg" style="zoom:50%;" />

2.[微信公众号支付参考文档](https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter4_1_4.shtml)
