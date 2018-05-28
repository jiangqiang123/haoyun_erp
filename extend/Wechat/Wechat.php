<?php
namespace Wechat;


class Wechat
{
    protected $AppID;//
    protected $AppSecret;//
    protected $back_url;//
    const AppID="wxfa7c96174eb1d11d";
    const AppSecret="f4073be5cb6fcc85ac483eb8cc472123";
    public function __construct($options){
        $this->AppID= isset($options['appid'])?$options['appid']:"wxfa7c96174eb1d11d";
        $this->AppSecret=isset($options['appsecret'])?$options['appsecret']:"f4073be5cb6fcc85ac483eb8cc472123";
        $this->back_url=isset($options['back_url'])?$options['back_url']:"http://vs.tommmt.com/api/weixin/back_url.html";
    }
    /**
     * GET 请求
     * @param string $url
     */

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
    /**
     * POST 请求
     * @param string $url
     * @param array $param
     * @param boolean $post_file 是否文件上传
     * @return string content
     */
    public function http_post($url,$param,$post_file=false){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param) || $post_file) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach($param as $key=>$val){
                $aPOST[] = $key."=".urlencode($val);
            }
            $strPOST =  join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($oCurl, CURLOPT_POST,true);
        if(PHP_VERSION_ID >= 50500){
            curl_setopt($oCurl, CURLOPT_SAFE_UPLOAD, FALSE);
        }
        curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);

        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }


    /****获取access_token**/
  public function get_access_token(){
      $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->AppID.'&secret='.$this->AppSecret.'';

      return json_decode($this->http_get($url));
  }
    /****获取code**/
    public function get_code(){
      $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->AppID.'&redirect_uri='.$this->back_url.'&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect';
        return $url;
    }
    /****以code获取用户access_token**/
    public function code_access_token($code){
     $url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->AppID.'&secret='.$this->AppSecret.'&code='.$code.'&grant_type=authorization_code';
         return json_decode(self::http_get($url));
    }
    /****检测用户access_token是否有效**/
    public function check_access_token($access_token,$openid){
        $url='https://api.weixin.qq.com/sns/auth?access_token='.$access_token.'&openid='.$openid;
        return self::http_get($url);
    }
    /****刷新access_token**/
    public function refresh_access_token($refresh_token){
        $url='https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='.$this->AppID.'&grant_type=refresh_token&refresh_token='.$refresh_token;
        return self::http_get($url);
    }
    /****获取用户微信信息**/
    public function getUserinfo($access){
        $url='https://api.weixin.qq.com/sns/userinfo?access_token='.$access->access_token.'&openid='.$access->openid.'&lang=zh_CN';
       return json_decode(self::http_get($url));
    }
    /****发送微信模板消息**/
    public function send_wechat_msg($par,$access_token){

        $url='https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
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
             $data['template_id']="eZoxHNVMw74gQ_qFB3zYEraMRWXWRR-Lz43GUBfJ_7s";
              $data['msg']= array(
                    "first"=>array("value"=>$par['first'], "color"=>"#173177"),
                    "keyword1"=>array("value"=>$par['keyword1'],"color"=>"#173177" ),
                    "keyword2"=>array("value"=>$par['keyword2'],"color"=>"#173177"),
                    "remark"=>array("value"=>$par['remark'],"color"=>"#173177"),
                );
             return $data;
                break;
            case "realname_success":
                $data['template_id']="s3aAd_qz6b5E95zdfQEEq8TdfZGxtbSznOIU7vBhMak";
                $data['msg']= array(
                    "first"=>array("value"=>$par['first'], "color"=>"#173177"),
                    "keyword1"=>array("value"=>$par['keyword1'],"color"=>"#173177" ),
                    "keyword2"=>array("value"=>$par['keyword2'],"color"=>"#173177"),
                    "remark"=>array("value"=>$par['remark'],"color"=>"#173177"),
                );
                return $data;
                break;

            case "levelup_success":
            $data['template_id']="N0sahK_enMNHJbvJDwGMemg8c9Hd5IYLMCHY0vF7ycs";
            $data['msg']= array(
                "first"=>array("value"=>$par['first'], "color"=>"#173177"),
                "keyword1"=>array("value"=>$par['keyword1'],"color"=>"#173177" ),
                "keyword2"=>array("value"=>$par['keyword2'],"color"=>"#173177"),
                "remark"=>array("value"=>$par['remark'],"color"=>"#173177"),
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