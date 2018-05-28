<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/25/025
 * Time: 14:24
 */
return [
    "is_to_debug"=> true,/****状态的token值不一样***/
    "token_time"=>2*60*60,/****token有效期s***/
    "is_to_debug_token"=>"88888888",/****c测试状态下的token值***/
    "m_name"=>"api",
    'default_return_type'=>'json',
    /****微信配置***/
    'wechat'=>[
        "AppID"=>"wxfa7c96174eb1d11d",
        "AppSecret"=>"f4073be5cb6fcc85ac483eb8cc472123",
        "back_url"=>'http://'.$_SERVER['SERVER_NAME']."/api/Member/back_url.html",
        "bind_back_url"=>'http://'.$_SERVER['SERVER_NAME']."/api/Member/bind_back_url.html",
    ],

    /****七牛云配置***/
    'Qiniu'=>[
        'ACCESSKEY' => 'bBAv6Q6-HLMdjm_dHQlwmJihXx20d-Y-BbaXO8Ti',//你的accessKey
        'SECRETKEY' => 'b67jcbTDAS-QdGvtwvVGzN3YR37vvEgH4-wMS27y',//你的secretKey
        'BUCKET' => 'smallv',//上传的空间
        'DOMAIN'=>'http://images.zyglz.com/'//空间绑定的域名
    ],
    /****阿里云配置***/
    'aliyun' => [
        /****银行卡***/
        "card"=>"cbb70f755e6c45ff858da00a153016d1",
    ],

    /***微信小程序配置***/
    'wechatxcx'=>[
        "AppID"=>'wxa2aa70a515acbf7a',
        "AppSecret"=>'a82c57bc5f0b025cd63b317bb7d9e6b9',
        "back_url"=>'https://'.$_SERVER['SERVER_NAME']."/api/Member/xcx_back_url.html",
    ],
];