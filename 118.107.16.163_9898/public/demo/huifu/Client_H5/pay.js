/* eslint-disable */

// h5调试
var hostName = location.host;
if (!(hostName.indexOf(configObj.productionEnvHost) > -1)) {
  if (configObj.isDebug) {
    new window.VConsole();
  }
}
$(document).ready(function () {
  // 解决移动端300ms延迟
  FastClick.attach(document.body);
  // 微信授权码
  var code = '',
    // 订单页面设置的金额
    amount = GetQueryString('price'),
    // 微信SDK的URL
    wxPaySDKURL = 'https://res.wx.qq.com/open/js/jweixin-1.6.0.js',
    // 支付宝SDK的URL
    aliPaySDKURL =
      'https://gw.alipayobjects.com/as/g/h5-lib/alipayjsapi/3.1.1/alipayjsapi.inc.min.js',
    // 订单号
    mer_ord_id =
      localStorage.getItem('mer_ord_id') ||
      dayjs().format('YYYYMMDDHHmmssSSS') + '' + randomNum(100, 999),
    // 流水号
    hf_seq_id = '',
    // 轮训支付状态定时标识
    timer = null,
    // 银联授权码
    userAuthCode = '',
    // IP
    client_ip = '',
    // 银联userID
    union_user_id = '',
    // 银联授权码状态码
    respCode = '',
    // 倒计时时间
    effectTime = configObj.mobile.payTime * 60,
    pay_minute = '20',
    pay_second = '00',
    // 支付类型
    tradeType = '';
  // 埋点
  MonitorMinSDK.init({
    pid: 'dg-checkout',
    appToken: 'app-226f04f1-f5ae-4ac3-b420-032b518cd0e9',
    appKey: '24e67f6771036c5cd6225eebe1a99671',
  });
  const params = {
    huifu_id: configObj.huifuId,
    os: 'h5',
    page:'full'
  };
  

  MonitorMinSDK.track('0090_00006404', params,true);


  // 订单倒计时,全屏幕版才会有倒计时
  if ($('#pay_minute') && $('#pay_minute').length) {
    startCountDown();
  }
  // 弹层收银台时，订单页面显示
  if ($('#orderPage') && $('#orderPage').length) {
    $('#priceOrder').text(amount);
    $('#countOrder').text(amount * 100);
  }
  // 支付环境判断
  payEnvHandle();
  // 取消事件
  $('#cashierCancelBtn').click(function () {
    location.href = jumpLink('orderConfirm.html?price=' + amount);
  });
  // 支付点击事件
  $('#pay-button').click(function () {
    payType();
  });
  // 重新下单
  $('#continuePayBtn').click(function () {
    reOrder();
  });

  // 切换支付类型
  var list = document.getElementsByTagName('li');
  for (let i = 0; i < list.length; i++) {
    list[i].onclick = function (e) {
      $(`#${list[i].id} input`).prop('checked', false);
      $(`#${e.target.id} input`).prop('checked', true);
      if(e.target.id === 'huabeipayItem') {
        tradeType = 'FQ_ALIPAY';
      } else if(e.target.id === 'bankpayItem'){
        tradeType = 'FQ_BANK';
      }
    };
  }

  // 上传标识
  function uploadPayInfo() {
    var params = {
      product_id: configObj.productId,
      sys_id: configObj.sysId,
      data: {
        //
        huifu_id: configObj.huifuId,
        resource: 'H5',
        id_code: 'co',
        action: 'CALL',
        ref_req_date: dayjs().format('YYYYMMDD'), //YYYYMMDD
        // 交易的订单号
        ref_req_seqid: mer_ord_id, //orderID
      },
    };
    axios.post(configObj.prodUploadPayAPI, params);
  }
  // 环境判断，加载不同支付选项
  function payEnvHandle() {
    if (isInWeiXinApp) {
      loadPaySDK(wxPaySDKURL);
      $('#wxpayItem').show();
      $('#bankpayItem').show();
      $('#wxpayInput').attr('checked', true);
      code = GetQueryString('code');
      if (!code) {
        // url重定向带上当前金额
        // 微信code获取
        weixinAuth();
      } else {
        if (!localStorage.getItem('open_id')) {
          try {
            var paymentData = {
              auth_code: code,
              client_type: '1',
            };
            // 获取openid
            service
              .post(configObj.api.wxAuth, paymentData)
              .then((data) => {
                console.log('-log- ~weixin- data', data);
                const { open_id, resp_code } = data;
                if (data && resp_code == '000000' && open_id) {
                  $('#pageLoadingMask').hide();
                  localStorage.setItem('open_id', open_id);
                }
              })
              .catch((error) => {
                console.log('-log- weixin~ error', error);
                alert(data);
              });
          } catch (error) {
            console.log('weixin---', error);
          }
        } else {
          $('#pageLoadingMask').hide();
        }
      }
    } else if (isInUnionApp) {
      console.log('---union--env--');
      $('#quickpasspayItem').show();
      $('#bankpayItem').show();
      $('#quickpasspayInput').attr('checked', true);
      respCode = GetQueryString('respCode');
      userAuthCode = GetQueryString('userAuthCode');
      if (!respCode || respCode.length === 0) {
        unionPayReady();
      } else {
        getUnionUserId(userAuthCode);
      }
    } else {
      console.log('---alipay env---');
      loadPaySDK(aliPaySDKURL);
      $('#alipayItem').show();
      $('#huabeipayItem').show();
      $('#bankpayItem').show();
      $('#alipayInput').attr('checked', true);
      // 页面加载完，隐藏页面loading
      $('#pageLoadingMask').hide();
    }
  }

  // 点击支付时，支付逻辑判断
  function payType() {
    // 银行卡分期 花呗分期
    if (tradeType === 'FQ_BANK' || tradeType === 'FQ_ALIPAY') {
      fenqiPay(tradeType);
      return;
    }
    if (isInWeiXinApp) {
      wxPay();
    } else if (isInUnionApp) {
      $('#loadingMask').show();
      // 银联云闪付，需要传递用户IP
      axios
        .get('https://api.ipify.org/?format=json')
        .then((res) => {
          const { data: ipInfo } = res;
          client_ip = ipInfo.ip;
          unionPay();
        })
        .catch(() => {
          client_ip = '172.31.19.51';
          unionPay();
        });
    } else {
      aliPay();
    }
  }

  // 分期支付
  function fenqiPay(tradeType) {
    try {
      let goods_desc =
        tradeType == 'FQ_BANK' ? '银行卡分期测试商品-无线鼠标' : '花呗分期测试商品-无线鼠标';
      var paymentCreateData = {
        amount: amount,
        trade_type: tradeType,
        goods_desc,
        extra_info: {
          mer_ord_id,
        },
        user_id: '',
      };
      $('#loadingMask').show();
      service
        .post(configObj.createPayURL, paymentCreateData)
        .then((res) => {
          console.log('-log-alipay ~ res', res);
          if (res && res.resp_code === '000000') {
            const { json_data } = res;
            if (json_data.req_seq_id) {
              hf_seq_id = json_data.req_seq_id;
              let payType = tradeType === 'FQ_BANK' ? 'unionpay' : 'alipay';
              var url = `https://hfenqi.cloudpnr.com/h5/qrcode/?huifuId=${configObj.huifuId}&productId=${configObj.productId}&reqSeqId=${json_data.req_seq_id}&payType=${payType}&transAmt=${amount}`;
              window.location.href = url;
            }
          } else {
            $('#loadingMask').hide();
          }
        })
        .catch((error) => {
          console.log('--fenqiPay--error--', error);
          $('#loadingMask').hide();
        });
    } catch (error) {
      console.log(error.toString());
    }
  }

  // 支付方式-支付宝
  function aliPay() {
    try {
      var paymentData = {
        amount: amount,
        trade_type: 'A_NATIVE',
        goods_desc: '支付宝测试商品-无线鼠标',
        extra_info: {
          mer_ord_id,
        },
        user_id: '',
      };
      $('#loadingMask').show();
      service
        .post(configObj.api.createPay, paymentData)
        .then((res) => {
          console.log('-log-alipay ~ res', res);
          // 下单成功上传凭证，获取对应奖励
          uploadPayInfo();
          const { json_data, pay_info } = res;
          if (res && res.resp_code === '000000') {
            if (pay_info && json_data.hf_seq_id) {
              hf_seq_id = json_data.hf_seq_id;
              timer = setInterval(function () {
                queryOrder(hf_seq_id);
              }, 5000);
              window.location.href =
                'alipays://platformapi/startapp?saId=10000007&qrcode=' + pay_info;
            } else {
              alert(json_data.resp_desc);
            }
          } else {
            $('#loadingMask').hide();
          }
        })
        .catch((error) => {
          console.log('--alipay--error--', error);
          $('#loadingMask').hide();
        });
    } catch (error) {
      console.log(error.toString());
    }
  }
  // 支付方式-微信
  function wxPay() {
    $('#loadingMask').show();
    try {
      // 生成订单
      // 当前页面取消支付，重新生成订单号
      mer_ord_id = dayjs().format('YYYYMMDDHHmmssSSS') + '' + randomNum(100, 999);
      localStorage.setItem('mer_ord_id', mer_ord_id);
      var paymentData = {
        amount: amount,
        trade_type: 'T_JSAPI',
        goods_desc: '微信测试商品-无线鼠标',
        extra_info: {
          mer_ord_id,
        },
        // 微信openID
        user_id: localStorage.getItem('open_id'),
      };
      service
        .post(configObj.api.createPay, paymentData)
        .then((res) => {
          console.log('-log-wxPay ~ res', res);
          const { json_data } = res;
          // 下单成功上传凭证，获取对应奖励
          uploadPayInfo();
          if (json_data && json_data.pay_info && json_data.hf_seq_id) {
            hf_seq_id = json_data.hf_seq_id;
            let pay_info = JSON.parse(json_data.pay_info);
            // JSAPI调起支付
            if (typeof WeixinJSBridge == 'undefined') {
              if (document.addEventListener) {
                document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
              } else if (document.attachEvent) {
                document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
                document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
              }
            } else {
              onBridgeReady(pay_info, queryOrder, hf_seq_id);
            }
          }
        })
        .catch((error) => {
          console.log('--weixinPay--error--', error);
        });
    } catch (error) {
      $('#loadingMask').hide();
      alert(error);
      console.log(error.toString());
    }
  }
  // 支付方式-云闪付
  function unionPay() {
    var paymentData = {
      amount: amount,
      trade_type: 'U_JSAPI',
      goods_desc: '银联测试商品-无线鼠标',
      extra_info: {
        mer_ord_id,
        user_id: union_user_id,
      },
      user_id: union_user_id,
      client_ip,
    };
    console.log('-log- ~ paymentData', paymentData);
    $('#loadingMask').show();
    service
      .post(configObj.api.createPay, paymentData)
      .then((res) => {
        console.log('-log-unionPay ~ res', res);
        // 下单成功上传凭证，获取对应奖励
        uploadPayInfo();
        if (res && res.resp_code === '000000') {
          $('#loadingMask').hide();
          const { pay_info } = res;
          console.log('-log- ~ pay_info', pay_info);
          if (pay_info) {
            window.location.href = pay_info;
          }
        } else {
          $('#loadingMask').hide();
        }
      })
      .catch((error) => {
        $('#loadingMask').hide();
        console.log('--unionPay--error--', error);
      });
  }
  // 银联环境跳转
  function unionPayReady() {
    userAuthCode = GetQueryString('userAuthCode');
    console.log('-log- unionPayReady~ userAuthCode', userAuthCode);
    let urlNow = encodeURIComponent(window.location.href);
    let url = 'https://qr.95516.com/qrcGtwWeb-web/api/userAuth?version=1.0.0&redirectUrl=' + urlNow;
    window.location = url;
  }
  // 获取银联用户ID
  function getUnionUserId(userAuthCode) {
    // 隐藏page loading
    $('#pageLoadingMask').show();
    var params = {
      auth_code: decodeURIComponent(userAuthCode),
    };
    console.log('-log- union-getUserId~ params', params);
    service
      .post(configObj.api.unionpayAuth, params)
      .then((res) => {
        console.log('-log- ~union-userInfo- data', res);
        const { jsonData, resp_code, json_data } = res;
        if (resp_code == '000000') {
          $('#pageLoadingMask').hide();
          if (jsonData && jsonData.user_id) {
            union_user_id = jsonData.user_id;
          } else if (json_data && json_data.user_id) {
            union_user_id = json_data.user_id;
          }
        } else {
          // alert(jsonData.return_desc);
          $('#pageLoadingMask').hide();
        }
      })
      .catch((error) => {
        $('#pageLoadingMask').hide();
        console.log('union--getUserId--error', error);
        // alert(JSON.stringify(error));
      });
  }

  // 拉起微信支付
  function onBridgeReady(pay_info, callback, hf_seq_id) {
    if (typeof WeixinJSBridge !== 'undefined') {
      WeixinJSBridge.invoke(
        'getBrandWCPayRequest',
        {
          'appId': pay_info.appId, //appid
          'timeStamp': pay_info.timeStamp, //时间戳，自1970年以来的秒数
          'nonceStr': pay_info.nonceStr, //随机串
          'package': pay_info.package,
          'signType': pay_info.signType, //微信签名方式：
          'paySign': pay_info.paySign, //微信签名
        },
        function (res) {
          console.log('====>: onBridgeReady -> res', res);
          if (res.err_msg == 'get_brand_wcpay_request:ok') {
            // 使用以上方式判断前端返回,微信团队郑重提示：
            //res.err_msg将在用户支付成功后返回ok，但并不保证它绝对可靠。
            callback(hf_seq_id);
          } else if (res.err_msg == 'get_brand_wcpay_request:cancel') {
            $('#loadingMask').hide();
            $('#cancelPay').show();
            $('#continuePay').click(function () {
              $('#cancelPay').hide();
            });
          } else {
            callback(hf_seq_id);
          }
        }
      );
    } else {
      console.log('不是在微信app内');
    }
  }
  // 加载支付SDK
  function loadPaySDK(url) {
    return new Promise((resolve, reject) => {
      let script = document.createElement('script');
      script.type = 'text/javascript';
      script.src = url;
      script.onload = () => {
        resolve(true);
      };
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }

  // 支付成功
  function paySuccess() {
    location.href = jumpLink('paySuccess.html?price=' + amount);
  }
  // 支付失败
  function payFail() {
    location.href = jumpLink('payFail.html?price=' + amount);
  }
  // 查询订单详情
  function queryOrder(hf_seq_id) {
    var queryOrderData;
    if(tradeType === 'FQ_ALIPAY') {
      queryOrderData = {
        org_req_seq_id: hf_seq_id,
      };
    } else {
      queryOrderData = {
        hf_seq_id,
      };
    }
    service
      .post(configObj.api.queryPay, queryOrderData)
      .then((res) => {
        console.log('-log-queryOrder ~ res', res);
        const { json_data, resp_code } = res;
        if (json_data && (resp_code === '000000' || resp_code === '00000000')) {
          if (json_data.trans_stat == 'S') {
            // 支付成功
            clearInterval(timer);
            // 支付成功时间
            localStorage.setItem('end_time', json_data.end_time);
            $('#loadingMask').hide();
            paySuccess();
          } else if (json_data.trans_stat == 'F') {
            // 支付失败
            clearInterval(timer);
            $('#loadingMask').hide();
            payFail();
          }
        } else {
          $('#loadingMask').hide();
          clearInterval(timer);
        }
      })
      .catch((error) => {
        console.log('--queryOrder--error--', error);
      });
  }

   // 查询 online 订单详情 针对分期支付订单
  function queryOnlineOrder(hf_seq_id) {
    var queryOrderData = {
      org_req_seq_id: hf_seq_id,
    };
    service
      .post(configObj.onlineQueryPayURL, queryOrderData)
      .then((res) => {
        console.log('-log-query OnlineOrder ~ res', res);
        const { json_data, resp_code } = res;
        if (json_data && (resp_code === '000000' || resp_code === '00000000')) {
          if (json_data.trans_stat == 'S') {
            // 支付成功
            clearInterval(timer);
            // 支付成功时间
            localStorage.setItem('end_time', json_data.end_time);
            $('#loadingMask').hide();
            paySuccess();
          } else if (json_data.trans_stat == 'F') {
            // 支付失败
            clearInterval(timer);
            $('#loadingMask').hide();
            payFail();
          }
        }
      })
      .catch((error) => {
        console.log('--query OnlineOrder--error--', error);
      });
  }


  // 倒计时
  function startCountDown() {
    let count = 0;
    const startTime = Date.now();
    const fixed = () => {
      count++;
      effectTime -= 1;
      if (effectTime < 0) return;
      const offset = Date.now() - (startTime + count * 1000);
      let nextTime = 1000 - offset;
      if (nextTime < 0) nextTime = 0;
      formatSeconds(effectTime);
      timeoutRemind(effectTime);
      setTimeout(fixed, nextTime);
    };
    setTimeout(fixed, 1000);
  }

  // 格式化时间
  function formatSeconds(seconds) {
    if (seconds < 0) return;
    pay_minute = String((seconds / 60) % 60 | 0).padStart(2, '0');
    pay_second = String(seconds % 60 | 0).padStart(2, '0');
    document.getElementById('pay_minute').innerText = pay_minute;
    document.getElementById('pay_second').innerText = pay_second;
  }

  // 超时提醒
  function timeoutRemind(seconds) {
    if (seconds <= 0) {
      document.getElementById('timeoutRemind').classList.remove('hide');
    }
  }

  // 重新下单
  function reOrder() {
    $('#timeoutRemind').hide();
    const toUrl = jumpLink('goodsDetail.html');
    if (history.replaceState) {
      history.replaceState(null, document.title, toUrl);
      history.go(0);
    } else {
      location.replace(toUrl);
    }
  }

  // 跳转链接
  function jumpLink(urlName) {
    const origin = location.origin;
    const pathname = location.pathname;
    const urlAry = pathname.split('/');
    urlAry.pop();
    urlAry.push(urlName);
    const urlStr = urlAry.join('/');
    const link = origin + urlStr;
    return link;
  }
});