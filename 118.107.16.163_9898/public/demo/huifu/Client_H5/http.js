/* eslint-disable */
var baseURL = '';
if (location.host.indexOf(configObj.productionEnvHost) > -1) {
  baseURL = configObj.prodBaseURL;
} else {
  baseURL = configObj.testBaseURL;
}
var service = axios.create({
  baseURL: baseURL,
  withCredentials: false, // send cookies when cross-domain requests
  timeout: 20000, // request timeout
  headers: {
    'Content-Type': 'application/json;charset=uft-8',
  },
});

// request interceptor
service.interceptors.request.use(
  (config) => {
    return config;
  },
  (error) => {
    console.log(error); // for debug
    return Promise.reject(error);
  }
);

// response interceptor
service.interceptors.response.use(
  (response) => {
    const res = response.data;
    console.log('-log- ~ res', res);
    if (res.resp_code !== '000000' && res.resp_code !== '00000000') {
      console.log('---error-res--', res);
      // 查询接口不弹出
      if (response.config.url !== configObj.queryPayURL) {
        alert(res.resp_msg || 'api error');
      }
      return Promise.reject(new Error(res));
    } else {
      return res;
    }
  },
  (error) => {
    console.log('--error--', error);
  }
);
