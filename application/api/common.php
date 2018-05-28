<?php

/***************
 * 系统非常规加密
 */


function user_md5($str,$key = 'XiaoV')
{
    return '' === $str ? '' : md5(sha1($str) . $key);
}

/***生成随机数
 * @function
 * @param $len
 * @return string
 */
function GetRandStr($len)
{
    $chars = array(
        "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"
    );
    $charsLen = count($chars) - 1;
    shuffle($chars);
    $output = "";
    for ($i=0; $i<$len; $i++)
    {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}

/**************
 * 判断数组键值全部存在
 * 验证前端传参是否正确
 */
function key_is_set($arr,$data)
{  $state=1;
   foreach ($arr as $key=>$val){
    if(!isset($data[$val])==true){
           $state=0;
       }
 }
 return $state==0 ? false :true;
}

/*********
 * 大于手机验证码
 * @$mobile    手机号码
 * @$type      1.注册验证  2.忘记密码  3.绑定银行卡
 * @$name      媒体名称
 */
function phone_code($mobile,$type=1,$name=''){
    $mobile = (string)$mobile;
//    if($type==1 || $type==2){
    $mobile_code = rand(1000,9999);
    if(empty($mobile)){
        exit('手机号码不能为空');
    }
    vendor('Sms.TopClient');
    vendor('Sms.ResultSet');
    vendor('Sms.RequestCheckUtil');
    vendor('Sms.TopLogger');
    vendor('Sms.AlibabaAliqinFcSmsNumSendRequest');
    $case = new \Sms\TopClient();
    $case->appkey = '23841553';
    $case->secretKey = '46544500f174be03688b31c8f9390e3a';
    $req = new \Sms\AlibabaAliqinFcSmsNumSendRequest();
    $req ->setExtend( "" );
    $req ->setSmsType( "normal" );
    $req ->setSmsFreeSignName( "通明小V" );
//    }
    //将验证码存数据库
    $info = db('smsCode')->where('mobile',$mobile)->find();
    $data['code'] = $mobile_code;
    $data['sendtime'] = time();
    if($info){
        db('smsCode')->where('mobile',$mobile)->update($data);
    }else{
        $data['mobile'] = $mobile;
        db('smsCode')->insert($data);
    }
    //发送验证码
    if($type==1){
        //注册
        $sms='SMS_67196286';
        $req ->setSmsParam( "{code:'".$mobile_code."',product:'通明小V'}" );
    }elseif ($type==2){
        //忘记密码
        $sms='SMS_67196284';
        $req ->setSmsParam( "{code:'".$mobile_code."',product:'通明小V'}" );
    }elseif ($type==3){
        //绑定银行卡
        $sms='SMS_67196283';
        $req ->setSmsParam( "{code:'".$mobile_code."',product:'通明小V'}" );
    }elseif ($type==4){
        //更换绑定手机号码
        $sms='SMS_67196290';
        $req ->setSmsParam( "{code:'".$mobile_code."',product:'通明小V'}" );
    }

    $req ->setRecNum($mobile);
    $req ->setSmsTemplateCode($sms);
    $resp = $case ->execute( $req );
    if($resp->result->success){
        return '提交成功';
    }else{
        return '提交失败';
    }
}

/***
 * @param $code
 * @return 检测邀请码
 */

function check_smallv($code){
    $info = db('activeCode')->where('code',$code)->find();
    if($info){
        return $info;
    }else{
        return false;
    }
}

/***添加新手任务
 * @param $uid
 * @return array
 */
function add_newbie_task($uid){
    $where['status'] = 2;
    $where['novice'] = 1;
    $list = db('tasks')->where($where)->select();  //新手任务
    $count = array();
    foreach ($list as $k=>$v){
        $details=array(
            'name'=>$v['name'],
            'type'=>$v['type'],
            'category'=>$v['category'],
            'execute_time'=>$v['execute_time'],
            'content'=>$v['content']
        );
        $arr = [
            "order_id" => $v['id'],
            'to_user_id' => $uid,
            'status' => 1,
            "is_novice" => 1,
            "addtime" => time(),
            "details" => serialize($details),
            'implement_time' => $v['']
        ];
        $count[] = $arr;
    }
    return $count;
}


/*
 * 检查账号等级（普通/小V）
 *
 */
function account_type($uid){
    $info = db('member')->where('uid',$uid)->find();
    if($info['apply']==2){                  //判断是否为小V账号
        $mapse['experience'] = array('gt',$info['experience']);
        if($grade = db('smallvGrade')->where($mapse)->find()){   //小V账号等级（认证完成后默认为一级）
            $level = $grade['id'];
        }else{                                                  //经验值超出最大等级限制，就为最高等级
            $wheress['experience'] = array('elt',$info['experience']);
            $grade = db('smallvGrade')->where($wheress)->order('experience desc')->find();
            $level = $grade['id'];
        }
    }else{
        $level = 2;                         //普通账号
    }
    return $level;
}

/*
 *****token加密解密
 *@$string  存入uid
 *@$operation
 *@$key
 *@$expiry
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    $ckey_length = 4;

    // 密匙
    $key = md5($key ? $key : $GLOBALS['discuz_auth_key']);

    // 密匙a会参与加解密
    $keya = md5(substr($key, 0, 16));
    // 密匙b会用来做数据完整性验证
    $keyb = md5(substr($key, 16, 16));
    // 密匙c用于变化生成的密文
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length):
        substr(md5(microtime()), -$ckey_length)) : '';
    // 参与运算的密匙
    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，
//解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) :
        sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    // 产生密匙簿
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 核心加解密部分
    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if($operation == 'DECODE') {
        // 验证数据有效性，请看未加密明文的格式
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) &&
            substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        return $keyc.str_replace('=', '', base64_encode($result));
    }
}
/**
 * GET 请求
 * @param string $url
 */

function http_gets($url){

    $oCurl = curl_init();
    if(stripos($url,"https://")!==FALSE){
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
    $sContent = curl_exec($oCurl);dump($sContent);dump(22222222222);
    $aStatus = curl_getinfo($oCurl);
    curl_close($oCurl);
    if(intval($aStatus["http_code"])==200){
        return $sContent;
    }else{
        return false;
    }
}


function img_base($code,$base){
    $host = "http://yhk.market.alicloudapi.com";
    $path = "/rest/160601/ocr/ocr_bank_card.json";
    $method = "POST";
    $appcode = $code;
    $headers = array();

    $img_info = getimagesize($base);
    $img_src = "data:{$img_info['mime']};base64," . base64_encode(file_get_contents($base));

    array_push($headers, "Authorization:APPCODE ".$appcode);
    //根据API的要求，定义相对应的Content-Type
    array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
    $querys = "";
    $bodys = "{
            \"inputs\": [
            {
                \"image\": {
                    \"dataType\": 50,
                    \"dataValue\": \".$img_src.\"
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



//验证微博账号密码
function weibo_login($u,$p,$ip){
    header("Content-type: text/html; charset=utf-8");
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
//var_dump($loginData);exit;
    $login = json_decode(loginPosts($loginUrl,$loginData,$ip),true);
    return $login;
}

/*****模拟POST***/
function loginPosts($url,$data,$ip){
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
    /****伪造ip***/
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_PROXY, $ip);
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
   // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
    curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie_file);
    curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie_file);
    $return = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return $return;
}

/**获取动态IP
 * @function
 * @return string
 */
function GetIp(){
    $str=trim (file_get_contents("http://api.ip.data5u.com/dynamic/get.html?order=d2e2bc67d8782c79ae2e252add6cf918&sep=3"));
    return $str;
}



/**根据微博账号 oid ，获取账号基本信息
 * @param $id
 * @return array|bool
 */
function get_weibo_data($id){
    $urls='https://m.weibo.cn/api/container/getIndex?containerid=100505'.$id;
    $s= file_get_contents($urls);
    $b=json_decode($s,true);
    if(!empty($b) && !empty($b['data']['userInfo'])){
        $info = $b['data']['userInfo'];
        $return = [
            'nickname' => $info['screen_name'],
            'headimg' => $info['profile_image_url'],
            'account_url' => $info['profile_url'],
            'description' => $info['description'],
            'fans' => $info['followers_count'],
            'sex' => $info['gender'],
        ];
        return $return;
    }else{
        return false;
    }
}

/**
 * 对象 转 数组
 *
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj) {
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)object_to_array($v);
        }
    }

    return $obj;
}