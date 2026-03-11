<?php

$params = [
    'url' => 'https://m.jiaoyimao.com/ecbp-api/order/getOrderListData',
    'header' => ':authority: m.jiaoyimao.com
content-type: application/json
cookie: sk_tower_uuid=8222f25b-02ca-4527-9eef-10b48fb773a4; JYM_FIRST_GAME_SWITCH_HISTORY=%7B%22gameId%22%3A%222000402%22%2C%22gameName%22%3A%22%E9%AA%8F%E7%BD%91%E4%B8%80%E5%8D%A1%E9%80%9A%22%7D; cna=PggMIPDM9D4CAavfoGrj/Xq7; track_id=gcmall_1736775374596_66dedc60-a729-403c-8f99-b32e5cc0c696; ctoken=7pFfwEn1p81U_3MXgBVtc0UR; ssids=4231736862992684; _bl_uid=7zmIm5s4w2Fab7lt1v1hj1e9OwjL; track_id=gcmall_1736775374596_66dedc60-a729-403c-8f99-b32e5cc0c696; ieu_member_biz_id=jiaoyimao; cna=vh8NIC0yEDkCAavfoGp1ZLi5; xlly_s=1; ieu_member_biz_id_sig=IpsjlCZxHMt7w1cZ1rcO2tBOsJL5ouVK03I7A1ynMKE; ieu_member_appcode=JYM_H5; ieu_member_appcode_sig=1N8cgNLOZxnWzKkIynis5xTxIaRjGDw-6dlZEMZglE8; ieu_member_passport_id=2881736775408351532; ieu_member_passport_id_sig=m61LnPXlPsEQCia76M9GTvzaVBi0SntevR-YfQKV5x0; ieu_member_uid=1736775408555777; ieu_member_uid_sig=sGub9Wn2pz6UtNdDguY35WwVS8sSsiL9ugcYOqBgK2w; mtop_partitioned_detect=1; logTraceJymId=4800adf24ee6-4729-a4aa-c2e5; session=5791736862992722; Hm_lvt_47366dcc92e834539e7e9c3dcc2441de=1736862993; Hm_lpvt_47366dcc92e834539e7e9c3dcc2441de=1736862993; HMACCOUNT=2656025AEC76DA62; out_member_umidToken=T2gALfQrPEikRKbmCDST_dJu8FQaZTzuf4NTiTkRaR-JOCX424z-EVmG3JwDqYnIrJk=; tfstk=gFwZa9a4zOBNUDqzR8D2U5TqOuMtHx77m-gjmoqmfV0gfGeqgzZjioN16rW05ySt5obTu-u3mGU1XVaF0y48fjMgfhFLPuv_C1HX3AHxna_5FTZ_XxHmNmRppVE3vmjIjCCmoskxna_CFTZTXxU8afMd3kjEcmRmmrvcYvmt-dmDImYhY2nmnq4mnMrnJmDmjrDmsLEgmpuEsicE0m84aclZrl03flwnbUguj2JDniiZ_6Zi8Kv0LW1KaLgGwHgxCVE-brBJBAc4sPon3stx7Wq7CVZy4KZU5kVSgRjA6kyq7b2iT39qQkn0ojPdzLiZCWlzQ5Iv-l4S77DTc37_YbPEwDcH0CD_NuwxZRXwy2G-0zoz7FSyGEn3b72x0XAqsDnEPMSEkgwtjl4OZgOvMXD-Y4sR2IdxsDnEPMSeMIhnJDu52gC..; _m_h5_tk=0e70525deb78e6ecc7a4f8a5b3d743ca_1736871119532; ieu_member_sid_sig=sHsienkjoSDBKatOa_ycSC0Ype-UNHx3-q4RGKWUgXE; jym_session_id=pt112185czm0359442pveqdx2qualwj5uuku4l0zbn; ieu_member_sid=pt112185czm0359442pveqdx2qualwj5uuku4l0zbn; _m_h5_tk_enc=6433a17c0c433693a7a90020920ea8cf; jym_session_id_sig=Kv5oT-8UP_w-uLdSa9W2nF5SMfWxkVN_NHb5hqAp0Ac; web_entry_type=normal; isg=BI2N00-y1urVOXLFKRVdd-venKkHasE8t5Cj3c8SyCSTxq94l7tdD-ZVNFygBtn0; member_umidToken=T2gAITFQX5bBIYf-d4Xf340jIivmYMW8ldtBv33wYDCOAkSpfHlD0rA4vm9wLOe20eg=; out_member_umidToken=T2gAITFQX5bBIYf-d4Xf340jIivmYMW8ldtBv33wYDCOAkSpfHlD0rA4vm9wLOe20eg=; ssids=4231736862992684; Hm_lvt_47366dcc92e834539e7e9c3dcc2441de=1736862993; Hm_lpvt_47366dcc92e834539e7e9c3dcc2441de=1736863835; tfstk=gdCtZevNGkqgraq8b1yHojK-2743ZJbZJG7SinxihMIdxHbcSRRgGSIhDhvbbCfAvM_ciOvD1rFAlat_uFDiMKONtOcDjlRAlgAYquVuZN7w3IZuqLP8zyRXlSxbKdZdBwR0quVutN7wgIZljrA0xBL2AFtjGAaKOE-I1msXfpiByURXGIsfRyL2AnOXGiOQJElrAjx05HcAeh9ERATwvjGf9FZJVNKp8e5pWdK7aHhA76L9B3_Gs_BFve1h9Lvno-ACz9j6JQFxOCpR__pC1klvLiKB31_32Y-5dGjXH9r-ZFdGppLdezcJnCfkFC63Lk9D6UCJO_ZqwUdMbCvCMkNA8BYyE1Oqojbp1UJR_p3jMg_hr1JFMunB0OYyOFIbNkIzp_fRUvok8BH_JyHq3dTEMhTZr_JUB5Tpqym-3xJt8eKuJyHq3dTeJ34n2xk2B25..; _m_h5_tk=91df548492f19f2fb898c13df892a79b_1736871041950; _m_h5_tk_enc=1400819c922735399ffd6dee98847650; tfstk=ghMsh0ZikNb6wMYmtVxUPhfC1M2bGHJrHiZxqmBNDRethXgs5h4N_fkbh2UEQPoaBqNxr4Z4WqlakoUKu1SwjSSit4oRQARg3twgn-LyzLJrbc24HuYSrqXgvoodkZdU6eGsx-8yzLJUZfvbqUuw0-yXdo4LXleYWH_L0yIOHqUT9wE7q-eYHqILpuE5MtUTk6KQmyUYH-3YpHZqC5dzx1aofhTWnFBW2RiTRtBxBLN_PcfVUTkLjPDxXyZUYvZ_1zPHW1SZB2oxQAVHkZwmYbgjM2pCCqgiXxFI3eSLNJUUwPD9014tQXMQQALcZDoQkqwxsnbKY4zswRmVmenQNchYGv8PTDygqxF-hw1nb74gwbPhDtnLZPFoBWsf6Rlr4xV-Bd_u87qjPug15guhUzZ1Fs10WtZQzHtCisb8chbGC0G465E3YDtBAsP06kqQzHtCisVTxkoBAH14i
origin: https://m.jiaoyimao.com
referer: https://m.jiaoyimao.com/order/list/buyer?orderStatus=waitDeliver&title=
sec-ch-ua: "Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1"
sec-ch-ua-mobile: ?1
sec-ch-ua-platform: "Android"
user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1
x-csrf-token: 7pFfwEn1p81U_3MXgBVtc0UR
x-mtee: {"umidToken":"T2gAITFQX5bBIYf-d4Xf340jIivmYMW8ldtBv33wYDCOAkSpfHlD0rA4vm9wLOe20eg=","collinaUa":"140#MmuojBX9zzPxWzo23xrF4pN8s9z0fX/PgaY4H0GD+Yn35C7KQRhd/m8GIqUcFTM8jVMKY6hqzzcreBO+8sszzZ8AIHoqlQzx2DD3VthqzFd/2XU+llfzzPziVWFnlT8I1wba7X53xYYCTdkWsdWE5CTH83TmqZ5i6ePaeMrfG7vcx+1OUWZzHyv2E8oK3gSNINYC/zAn5md3ksMnXat5/+28wBFcdTZZifIoVtjriJIZCwjKskcXN2Z1Pz9BDheqpC0eIg8AazrdLM+mElrFyEhhskUXSQ0RKm9GqDUtWB4VcfffxTg/KxG+C/PhQXNipr+mB4AcS0nNA+WbPj1qnhR5g9le8GZsf/yPii97yto29FYpXCFQVsAfRO9pIuYOgGGuqnpCC3aXwazsoahjm15LNXtHPG2zOZRVkZQptPlPGnKBziJ/JVzBAC0ImQGN61SxiLaKANyXT4y1Usi7BaCz5IzdzVEF6Fd282CRQtDOocXeBUWXPV0jVcarVtVmboFlmxwHS9RGgWVOvP38mOcqtp0vn3+U0Zgn6cyW2v/xMz4hu3VpV3YcY1Z4iTdCcE3BKLvsbZTPce016PnRG06GAELtDRGGnHY+uG5+IBZ/grz24qPZlzMuRqX0yfG08nfA/Ul3aieprGM0R4xaO54D1xJ7NiH9X335+3fCKpDN/fO/iOz935WVMH0XElXgNG7k20aHjeiSMZOEAmEoNlwLWFr32tM/DF40T498D/ihot4iUaAXrSS+w9zzDa5GkjR6zGqt1Pk35JWMC2e7g1eHGJrX0el2lSK72eGYetjYA4uHOP7sNNiKatGWAGGK4UqbswGQcPgWI3eoYtPEwCJStMxYDj3yTQ=="}',
    'body' => '{"params":{"tabType":"all","pageNum":2,"pageSize":4,"userType":1}}'
];

$http = http_post($params);

//print_r($http);

function http_post($params)
    {
        
        $array = get_headers($params['url'],1);
        $params['type']   = $params['type'] ?? '';
        $params['header'] = $params['header'] ?? '';
        $params['header'] = explode("\n",$params['header']);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $params['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$params['header']); //设置头信息的地方
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 10);  //超时时间
        //curl_setopt($ch, CURLOPT_INTERFACE, get_rand_ip());
        
        
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params['body']);
        $output = curl_exec($ch);
        curl_close($ch);
        //打印获得的数据
        
        if($params['type'] =='json'){
           $output = json_decode($output,true);
        }
        
        return $output;
        
    }