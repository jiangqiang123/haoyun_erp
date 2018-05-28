<?php
use Wechat\Wechat;
use Wechat\Xwechat;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件


/***修改微信公众号access_token
 * @param $access_token
 * @return bool
 */
function update_access_token($access_token){
    if(db("Config")->where(array("name"=>"ACCESS_TOKEN"))->update(array("title"=>$access_token,"value"=>time()))){
        return true;
    }else{
        return false;
    }
}

/***判断微信公众号access_token是否存在
 * @return bool|mixed
 */
function check_access_token(){
    $arr=db("Config")->where(array("name"=>"ACCESS_TOKEN"))->find();
    if($arr){
    if($arr['value']+7198>time()){
        return $arr["title"];
    }else{
        return false;
    }
    }else{
        return false;
    }

}

/***修改微信公众号access_token
 * @param $access_token
 * @return bool
 */
function update_xaccess_token($access_token){
    if(db("Config")->where(array("name"=>"X_ACCESS_TOKEN"))->update(array("title"=>$access_token,"value"=>time()))){
        return true;
    }else{
        return false;
    }
}

/***判断微信公众号access_token是否存在
 * @return bool|mixed
 */
function check_xaccess_token(){
    $arr=db("Config")->where(array("name"=>"X_ACCESS_TOKEN"))->find();
    if($arr){
        if($arr['value']+7198>time()){
            return $arr["title"];
        }else{
            return false;
        }
    }else{
        return false;
    }

}


/***注册成功发送微信消息
 *$openid  用户的openid
 */
function send($openid,$type,$par,$url=null){
    $Wechat=new Wechat(array());
    /***获取模板类型***/
    $msg_data=$Wechat->Wechat_Msg_Template($type,$par); //
    /****拼接消息**/
    $arr= $Wechat->Wechat_Msg_data(array(                   //后期需要修改
        "openid"=>$openid,
        "template_id"=>$msg_data['template_id'],
        "url"=>$url,
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
    /***发送消息***/
    return $Wechat->send_wechat_msg($arr,$access_token);
}

function xsend($openid,$type,$par,$page=null){
    $xwechat = new Xwechat(array());


}


/**
 *	任务发布后发送消息
 *   opendid 
 *	 name  任务名称
 *	 url  跳转地址
 *	 type  任务类型
 **/
 
 function task_send($openid,$name,$type,$url=null){
	 $Wechat=new Wechat(array());
	 $par['template_id']="_nkhgqcGRPoHSUaIUKlxOeYuv-2fUe96hPmHlzVw1DE";
	 $par['msg']=array(
		'first'    => array("value"=>'有新的任务发布！','color'=>'#173177'),
		'keyword1' => array("value"=>$name,'color'=>'#f21c1c'),
		'keyword2' => array("value"=>$type,"color"=>"#173177"),
		'remark'   => array("value"=>'任务接取先到先得,任务量无剩余后任务将自行下线',"color"=>"#173177")
	 );
	 
	 $arr=array(
		"openid"=>$openid,
        "template_id"=>$par['template_id'],
        "url"=>$url,
        'data'=>$par['msg'],
	 );
	 
	  /***检测access_token是否有效***/
    if(!check_access_token()){
		
        $get_access_token=$Wechat->get_access_token();
        $access_token=$get_access_token->access_token;
        update_access_token($access_token);
		
    }else{
        $access_token=check_access_token();
    }
	
	/***发送消息***/
    return $Wechat->send_wechat_msg($arr,$access_token);
	 
 }



/**
 * 验证微博账号密码是否正确
 * @param string $url
 */

function weibo_is_true($u,$p){
    header('Content-type:text/html;charset=utf-8');
    $password = $p;
    $username = base64_encode($u);
    $loginUrl = 'https://login.sina.com.cn/sso/login.php?client=ssologin.js(v1.4.15)&_=1403138799543';
    $loginData['entry'] = 'sso';
    $loginData['gateway'] = '1';
    $loginData['from'] = 'null';
    $loginData['savestate'] = '30';
    $loginData['useticket'] = '0';
    $loginData['pagerefer'] = '';
    $loginData['vsnf'] = '1';
    $loginData['su'] = base64_encode($u);
    $loginData['service'] = 'sso';
    $loginData['sp'] = $password;
    $loginData['sr'] = '1920*1080';
    $loginData['encoding'] = 'UTF-8';
    $loginData['cdult'] = '3';
    $loginData['domain'] = 'sina.com.cn';
    $loginData['prelt'] = '0';
    $loginData['returntype'] = 'TEXT';
    $login = json_decode(loginPost($loginUrl,$loginData),true);
    return $login;
}

/****
微博登录表单提交
 ***/
function loginPost($url,$data){
    global $cookie_file ;
//echo $cookie_file ;exit;
    $tmp = '';
    if(is_array($data)){
        foreach($data as $key =>$value){
            $tmp .= $key."=".$value."&";
        }
        $post = trim($tmp,"&");
    }else{
        $post = $data;
    }
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
    curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie_file);
    curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie_file);
    $return = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return $return;
}

