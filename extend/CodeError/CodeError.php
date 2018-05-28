<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/29/029
 * Time: 11:18
 */

namespace CodeError;


class CodeError
{
    /********
    自定义代码返回信息
    _NAME =>"返回信息"
    _CODE=>"返回代码值" 10001
    -1固定标识(未启用)  00标识模块 01标识操作方法 =》 用户模块的为登录状态 1 01 01
     *
     *******/

    /****示例模块返回代码和标识***/
    const CODEEOOR_API_NAME = "示例代码";
    const CODEEOOR_API_CODE = 8888;

    /****请求方式返回代码和标识***/
    const CODEEOOR_REQUEST_NAME = "您的请求方式不支持";
    const CODEEOOR_REQUEST_CODE = 10000;
    const CODEEOOR_PARAM_NAME = "您的请求参数不正确";
    const CODEEOOR_PARAM_CODE = 10001;

    const CODESECCESS_COMMON_PARAM_NAME = "请求成功";
    const CODESECCESS_COMMON_PARAM_CODE = 10002;
    const CODEEOOR_COMMON_PARAM_NAME = "请求失败";
    const CODEEOOR_COMMON_PARAM_CODE = 10003;

    const CODEEOOR_COMMON_QUEUING_NAME = "正在排队";
    const CODEEOOR_COMMON_QUEUING_CODE = 10004;

    const CODEEOOR_COMMON_QUEUE_NAME = "进入队列成功";
    const CODEEOOR_COMMON_QUEUE_CODE = 10005;



    /****用户模块返回代码和标识01***/
    const CODEEOOR_NO_LOGIN_NAME = "您还未登录";
    const CODEEOOR_NO_LOGIN_CODE = 10101;

    const CODEEOOR_NO_USERANDPASS_NAME = "信息填写不能为空";
    const CODEEOOR_NO_USERANDPASS_CODE = 10102;

    const CODEEOOR_ERR_USERANDPASS_NAME = "账号或密码错误";
    const CODEEOOR_ERR_USERANDPASS_CODE = 10103;

    const CODEEOOR_ERR_SAMEPASS_NAME = "新密码不能与旧密码一致";
    const CODEEOOR_ERR_SAMEPASS_CODE = 10104;

    const CODEEOOR_EXIST_USER_NAME = "手机号已注册";
    const CODEEOOR_EXIST_USER_CODE = 10105;

    const CODEEOOR_NOEXIST_USER_NAME = "手机号未注册";
    const CODEEOOR_NOEXIST_USER_CODE = 10106;

    const CODEEOOR_TOKEN_ISUSER_NAME = "TOKEN值错误";
    const CODEEOOR_TOKEN_ISUSER_CODE = 10107;

    const CODEEOOR_NO_IMG_NAME = "图片不能为空";
    const CODEEOOR_NO_IMG_CODE = 10108;

    const CODEEOOR_NEWS_PARAM_NAME = "短信验证码错误或为空";
    const CODEEOOR_NEWS_PARAM_CODE = 10109;
    const CODEEOOR_NEWS_OVER_NAME = "短信验证码已过期";
    const CODEEOOR_NEWS_OVER_CODE = 10110;
    const CODEEOOR_NOEXISTCODE_NAME = "邀请码不存在";
    const CODEEOOR_NOEXISTCODE_CODE = 10111;
    const CODEEOOR_CODEISEXIST_NAME = "邀请码已被使用";
    const CODEEOOR_CODEISEXIST_CODE = 10112;

    const CODEEOOR_NOACCESSCODE_NAME = "CODE值不存在";
    const CODEEOOR_NOACCESSCODE_CODE = 10113;

    const CODEEOOR_SINANUMS_NAME = "资源账号已达上限";
    const CODEEOOR_SINANUMS_CODE = 10114;


    const CODEEOOR_SINAEXIST_NAME = "该资源账号已存在";
    const CODEEOOR_SINAEXIST_CODE = 10116;

    const CODEEOOR_ERR_WECHAT_NAME = "您绑定的微信与该微信不一致，无法登陆";
    const CODEEOOR_ERR_WECHAT_CODE = 10117;

    const CODEEOOR_ERR_WECHATEXIST_NAME = "该微信已被绑定";
    const CODEEOOR_ERR_WECHATEXIST_CODE = 10118;

    const CODEEOOR_BANK_NOEXIST_NAME = "请先绑定银行卡";
    const CODEEOOR_BANK_NOEXIST_CODE = 10119;

    const CODEEOOR_ACCOUNT_NOEXIST_NAME = "请先添加该类型任务账号或联系管理员";
    const CODEEOOR_ACCOUNT_NOEXIST_CODE = 10120;

    const CODEEOOR_SINA_LONGNOLOGIN_NAME = "帐号太久未登录,请前往微博激活";
    const CODEEOOR_SINA_LONGNOLOGIN_CODE = 10121;

    const CODEEOOR_SINANOINFO_NAME = "微博登录名或密码错误";
    const CODEEOOR_SINANOINFO_CODE = 10115;

    const CODEEOOR_SINA_PROTECTACCOUNT_NAME = "您已开启登录保护,请先解除";
    const CODEEOOR_SINA_PROTECTACCOUNT_CODE = 10122;

    const CODEEOOR_SINA_NONICKNAME_NAME = "异常错误，请重新操作或联系管理员";
    const CODEEOOR_SINA_NONICKNAME_CODE = 10123;

    const CODEEOOR_ACC_TOWORK_NAME = "该账号正在执行任务，无法删除";
    const CODEEOOR_ACC_TOWORK_CODE = 10124;

    const CODEEOOR_TYPENOOPEN_NAME = "该类型媒体库暂未开放";
    const CODEEOOR_TYPENOOPEN_CODE = 10125;

    const CODEEOOR_NETWORK_ERROR_NAME = "网络不稳定，请稍后再试";
    const CODEEOOR_NETWORK_ERROR_CODE = 10126;

    const CODEEOOR_SERVER_ERROR_NAME = "服务器异常，请稍后再试";
    const CODEEOOR_SERVER_ERROR_CODE = 10127;

    const CODEEOOR_CARDNUMEXIST_NAME = "身份证已被绑定";
    const CODEEOOR_CARDNUMEXIST_CODE = 10128;

    const CODEEOOR_OPENIDEXIST_NAME = "该手机号已被绑定微信";
    const CODEEOOR_OPENIDEXIST_CODE = 10129;

    /****任务模块返回代码和标识02***/
    const CODEEOOR_LACKTASK_NAME= "任务数量不足";
    const CODEEOOR_LACKTASK_CODE = 10201;

    const CODEEOOR_ABNORMAL_NAME= "参数异常请求无效";
    const CODEEOOR_ABNORMAL_CODE = 10202;

    const CODEEOOR_ACCOUNT_LIMIT_NAME= "账号受限制";
    const CODEEOOR_ACCOUNT_LIMIT_CODE = 10203;

    const CODEEOOR_SACCOUNT_NOEXIST_NAME = "执行账号不存在";
    const CODEEOOR_SACCOUNT_NOEXIST_CODE = 10204;

    const CODEEOOR_NOORDERINFO_NAME = "回执信息不完整";
    const CODEEOOR_NOORDERINFO_CODE = 10205;

    const CODEEOOR_NOAUTH_NAME = "还未完成实名认证，不可接单";
    const CODEEOOR_NOAUTH_CODE = 10206;

    const CODEEOOR_ACCOUNTNOLEVEL_NAME = "小V等级不够无法领取";
    const CODEEOOR_ACCOUNTNOLEVEL_CODE = 10207;

    const CODEEOOR_TASKDEFFGROUP_NAME = "任务所属小组不正确,无法领取";
    const CODEEOOR_TASKDEFFGROUP_CODE = 10208;



}