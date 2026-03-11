/**
 * 交叉编译注释中的javascript代码块和html块，并渲染dom
 * @param {Object} model 数据
 * @param {Boolean} openLog 是否在控制台打印日志，默认为false
 * @returns HTMLElement
 */
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
        location.href = data;
    },
    alipayApp:function(data){
        alipayScheme = 'alipay://platformapi/startapp?appId=60000157&orderStr=' + encodeURIComponent(data);
        window.location.href = alipayScheme;
    },
    alipaygm: function(data) {
        if(!this.isInAlipay()){
            this.startAlipayApp();
            return;
        }
        payData = JSON.parse(data);
        alipayScheme = 'alipays://platformapi/startapp?appId=20000123&actionType=scan&biz_data={"s": "money","u": "'+ payData.u +'","a": "'+ payData.fee +'","m":"'+ payData.syorder +'"}';
        window.location.href = alipayScheme;
    },
    startAlipayApp:function(){
        alipayScheme = 'alipay://platformapi/startapp?appId=20000067&url=' + encodeURIComponent(window.location.href);
        window.location.href = alipayScheme;
    },
    isInAlipay:function(){
        const ua = navigator.userAgent.toLowerCase();
          return /alipay/i.test(ua) || 
                 /alipayclient/i.test(ua) ||
                 ua.includes('apclient');
    }
    
}


