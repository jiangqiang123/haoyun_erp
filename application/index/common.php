<?php
/**
 * @className：API模块公共方法管理
 * @description：该模块下公用自定义方法
 * @author:通明技术团队
 * Date: 2017/10/30
 * Time: 14:09
 */


/*
 *TOKEN 双向加密解密
 *@param $string  存入uid
 *@param $operation 操作(加密ENCODE,解密DECODE)
 *@param $key   附加组合用于加密
 *@param $expiry  token值有效时间
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
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


/*
 *判断数组键值全部存在,验证前端传参是否正确
 *@param $arr  要验证的数组键名组成的数组
 *@param $data 对象数组
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

/*
 * 系统非常规加密
 *@param $str   加密参数
 *@param $key   加密附加值
 */
function user_md5($str,$key = 'DC')
{
    return '' === $str ? '' : md5(sha1($str) . $key);
}

/*
 *无限分类的树状输出
 *@param $tree 排序对象
 *@param $rootId 父id
 */
function tree($tree, $rootId = 0) {
    $return = array();
    foreach($tree as $leaf) {
        if($leaf['pid'] == $rootId) {
            foreach($tree as $subleaf) {
                if($subleaf['pid'] == $leaf['id']) {
                    $leaf['children'] = tree($tree, $leaf['id']);
                    break;
                }
            }
            $return[] = $leaf;
        }
    }
    return $return;
}




/**根据获取微博账号昵称，获取oid
 * @param $name
 * @return string
 */
function name_to_get_weibo_oid($name){
    $ch = curl_init();//初始化一个CURL会话
    curl_setopt($ch,CURLOPT_URL,"http://open.weibo.com/widget/ajax_getuidnick.php");//设置CURL请求URL
    $data = "nickname=".urlencode($name);//设置POST数据（用户昵称）
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.8.0.11)  Firefox/1.5.0.11;");//设置User-Agent
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER,0);
    $header = array ();
    $header [] = 'Accept-Language: zh-cn';
    $header [] = 'Pragma: no-cache';
    $header [] = 'Referer: http://open.weibo.com/widget/followbutton.php';//经测试，请求必须有Referer，否则将返回NULL
    curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    $page = curl_exec($ch);//运行抓取
    $matches = json_decode($page,true);//将页面返回的json数据解析为数组
    curl_close($ch);
    if(empty($matches['data'])){//判断返回的UID是否为空
        return "ERR_NotFound";
    }else{
        return $matches['data'];
    }
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
