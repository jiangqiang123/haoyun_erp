<?php
namespace Wechat;


class Xwechat
{
    protected $AppID;
    protected $AppSecret;

    /**
     * 构造函数
     * @param $appsecret string 小程序的appsecret
     * @param $appid string 小程序的appid
     */
    public function __construct( $appid,$appsecret)
    {
        $this->AppSecret = isset($appsecret)?$appsecret:config('wechatxcx.AppSecret');
        $this->AppID = isset($appid)?$appid:config('wechatxcx.AppID');
    }


    /***https get***/
    public function http_get($url){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }


    /***https post***/
    /* PHP CURL HTTPS POST */
    function curl_post_https($url,$data){ // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据，json格式
    }


    /***小程序获取用户openid***/
    public function get_member_code($code){
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$this->AppID.'&secret='.$this->AppSecret.'&js_code='.$code.'&grant_type=authorization_code';
        return json_decode(self::http_get($url));
    }

    /***获取 access_token***/
    public function get_access_token(){
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->AppID.'&secret='.$this->AppSecret;
        return json_decode(self::http_get($url));
    }


    /****发送微信模板消息**/
    public function send_wechat_msg($par,$access_token){
        $url='https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$access_token;
        $param=array(
            "touser"=>$par['openid'],
            "template_id"=>$par['template_id'],
            "url"=>isset($par['url'])?$par['url']:"",
            'data'=>$par['data'],

        );
        return json_decode(self::curl_post_https($url,json_encode($param)));
    }


    /****选择微信消息模板**/
    public function Wechat_Msg_Template($type,$par){
        switch($type){
            case "register_success":
                $data['template_id']="bXiMraRYUPIJcDs5qMhbKyug0IrdmW0fRGCcqgIlJPs";
                $data['msg']= array(
                    "keyword1"=>array("value"=>$par['keyword1'],"color"=>"#173177" ),
                    "keyword2"=>array("value"=>$par['keyword2'],"color"=>"#173177"),
                    "keyword3"=>array("value"=>$par['keyword3'],"color"=>"#173177"),
                );
                return $data;
                break;


            default:
                echo 'others';
        }
    }

    /****消息格式**/
    public function Wechat_Msg_data($par){

        $data=array(
            "openid"=>$par['openid'],
            "template_id"=>$par['template_id'],
            "url"=>$par['url'],
            'data'=>$par['data'],
        );
        return $data;
    }
}