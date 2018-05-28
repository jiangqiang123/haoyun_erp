<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/21/021
 * Time: 15:23
 */
namespace app\api\controller;
use Wechat\Wechat;
use Qiniu\Auth as Auth;
use think\Controller;
class Weixin extends  Controller{
    public function index(){
//        for($i=0;$i<9;$i++){
//            $arr[] = authcode(6,'ENCODE',"smallv",config("token_time"));
//        }
//        dump($arr);
    }

    public function card(){
 $img='http://'.$_SERVER['SERVER_NAME']."/static/card.jpg";
 $imgs="http://vs.tommmt.com/static/cards.png";
       $a=$this->fileToBase64($imgs);
        dump($a);   dump($imgs);die;
        $host = "http://yhk.market.alicloudapi.com";
        $path = "/rest/160601/ocr/ocr_bank_card.json";
        $method = "POST";
        $appcode = "cbb70f755e6c45ff858da00a153016d1";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
        $querys = "";
        $bodys = "{
    \"inputs\": [
    {
        \"image\": {
            \"dataType\": 50,                        
            \"dataValue\": \"aHR0cDovL3ZzLnRvbW1tdC5jb20vc3RhdGljL2NhcmQuanBn\"      
        }
    }]
}";
        $url = $host . $path;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
        var_dump(curl_exec($curl));
    }
    function fileToBase64($file){
        $base64_file = '';
        if(file_exists($file)){
            $mime_type= mime_content_type($file);
            $base64_data = base64_encode(file_get_contents($file));
            $base64_file = 'data:'.$mime_type.';base64,'.$base64_data;
        }
        return $base64_file;
    }
    public function weibo(){
        $a=weibo_is_true("15255125517","6360672");
        dump($a);die;

    }


        public function indexs(){

        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = config('Qiniu.ACCESSKEY');
        $secretKey = config('Qiniu.SECRETKEY');
        // 构建鉴权对象
        $auth = new \Qiniu\Auth($accessKey, $secretKey);
        // 要上传的空间
        $bucket = config('Qiniu.BUCKET');
        $token = $auth->uploadToken($bucket);
        return $token;
    }
    public function test(){

   $Wechat=new Wechat(array());
       dump($Wechat->get_access_token());die;
}
    public function back(){
        $Wechat=new Wechat(array());
        dump($Wechat->get_code());

       echo '<br/><a href="'.$Wechat->get_code().'">1111</a>';die;
    }
    public function send(){
          $Wechat=new Wechat(array());
        /***获取模板类型***/
          $msg_data=$Wechat->Wechat_Msg_Template("register_success");
         /****拼接消息**/
           $arr= $Wechat->Wechat_Msg_data(array(
               "openid"=>"olUOnwWhmLhMKuDvYPPWAi_gKYZU",
                "template_id"=>$msg_data['template_id'],
                "url"=>"https://www.baidu.com/",
                'data'=>$msg_data['msg'],
            ));

/***检测access_token是否有效***/
    if(!check_access_token()){

        $get_access_token=$Wechat->get_access_token();
        $access_token=$get_access_token->access_token;
        update_access_token($access_token);
    }else{

        $access_token=check_access_token();
    }

dump($access_token);
        /***发送消息***/
       $a= $Wechat->send_wechat_msg($arr,$access_token);
        dump($a);
       die;
    }
    public function back_url(){
        if (isset($_GET['code'])){
            $Wechat=new Wechat(array());
            header("Content-type:text/html;charset=utf-8");
            $access=$Wechat->code_access_token($_GET['code']);
            dump($access);
            $info=$Wechat->getUserinfo($access);
            $data = array(
                'wechat' => $info->openid,
                'nickname' => $info->nickname,
                'headpic' => $info->headimgurl,
                'address' => $info->country.$info->province.$info->city,
            );
            dump($data);
            die;

        }else{
            echo "NO CODE";
        }
    }

}