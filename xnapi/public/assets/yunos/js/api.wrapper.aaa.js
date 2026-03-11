

var $_GET = (function(){
    var url = window.document.location.href.toString();
    var u = url.split("?");
    if(typeof(u[1]) == "string"){
        u = u[1].split("&");
        var get = {};
        for(var i in u){
            var j = u[i].split("=");
            get[j[0]] = j[1];
        }
        return get;
    } else {
        return {};
    }
})();



const $appPay = {
    load: function(action ,data) {

        this[action](data);
    },
    payment: function(data) {
        window.location.href = data;
    },
    payment_auto: function(data) {
        if(data.includes('http://') || data.includes('https://')){
            return window.location.href = data;
        }
        window.location.href = this.protocol() + data;
    },
    alipay_url_jump:function(data){
        alipayScheme = this.protocol() + 'platformapi/startapp?appId=20000067&url=' + encodeURIComponent(data);
        window.location.href = alipayScheme;
        if(data.includes("http")){
            setTimeout(() => {
                window.location.href = data;
            }, 1000);
        }
        
    },
    alipay_url:function(data){
        alipayScheme = this.protocol() + 'platformapi/startapp?appId=20000067&url=' + encodeURIComponent(data);
        window.location.href = alipayScheme;
    },
    alipay_order_str:function(data){
        alipayScheme = this.protocol() + 'platformapi/startapp?appId=60000157&orderStr=' + encodeURIComponent(data);
        window.location.href = alipayScheme;
    },
    alipaygm: function(data) {
        if(!this.isInAlipay()){
            this.startAlipayApp();
            return;
        }
        payData = JSON.parse(data);
        alipayScheme = this.protocol() + 'platformapi/startapp?appId=20000123&actionType=scan&biz_data={"s": "money","u": "'+ payData.u +'","a": "'+ payData.fee +'","m":"'+ payData.syorder +'"}';
        window.location.href = alipayScheme;
    },
    
    isInAlipay:function(){
        const ua = navigator.userAgent.toLowerCase();
          return /alipay/i.test(ua) || 
                 /alipayclient/i.test(ua) ||
                 ua.includes('apclient');
    },
    protocol:function(){
        
        return 'alipay://';
        
        if(this.detectOS() == 'iOS'){
            return 'alipay://';
        }
        
        return 'intent://';
        
    },
    detectOS:function() {
      const userAgent = navigator.userAgent;
      const platform = navigator.platform;
      
      // 优先检测Android（兼容不同浏览器）
      const androidRegex = /(android|adr)/i;
      if (androidRegex.test(userAgent.toLowerCase())) {
        return 'Android';
      }
      
      // 检测iOS（iPhone/iPad/iPod）
      const iOSRegex = /(iPhone|iPad|iPod)/i;
      if (iOSRegex.test(userAgent)) {
        return 'iOS';
      }
      
      // 备用检测：通过navigator.platform
      if (/iP(ad|hone|od)/i.test(platform)) {
        return 'iOS';
      }
      
      // 无法识别时返回未知
      return 'unknown';
    }
}
        
const randomString = len => 
  Array.from({length: len}, 
    () => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'[
      Math.floor(Math.random() * 62)
    ]
  ).join('');