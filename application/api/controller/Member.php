<?php
namespace app\api\controller;
use app\api\model\Member as MemberModel;
use app\api\model\MemberRealname;
use app\api\model\Opinion;
use think\Db;
use think\Request;
use CodeError\CodeError;
use think\Validate;
use think\Session;
use Wechat\Wechat;
use Wechat\Xcxwechat;
use Wechat\Xwechat;
/*
 * 用户模块------注册，登陆，忘记密码，获取手机验证码 API
 *@type请求类别
 *@param请求参数（数组）
 *
 */
class Member extends Home
{
    public function user(){
        $request = Request::instance();
        $param= $request->param();
        switch ($request->method()){
            case 'POST':
                if(!empty($param)){
                        if($param['type']==1){                      /***注册***/
                            return $this->register($param);
                        }else if($param['type']==2){                //登录
                            return $this->login($param);
                        }else if($param['type']==3){                //获取验证码
                            return $this->getcode($param);
                        }else if($param['type']==4){                //忘记密码获取验证码
                            return $this->gettercode($param);
                        }else if($param['type']==5){                //忘记密码->修改密码
                            return $this->forgetpwd($param);
                        }elseif($param['type']==6){                 //微信端已有账号普通登陆
                            return $this->wechat_login($param);
                        }elseif($param['type']==7){                 //判断号码是否已注册
                            return $this->is_have_phone($param);
                        }elseif($param['type']==8){                 //绑定推送，验证手机号是否已绑定
                            return $this->is_bind($param);
                        }elseif($param['type']==9){                 //推送绑定微信公众号openid
                            return $this->bind_openid($param);
                        }elseif($param['type']==10){                //推送绑定获取验证码
                            return $this->getbindcode($param);
                        }else{
                            return ["code"=>CodeError::CODEEOOR_PARAM_CODE,"message"=>CodeError::CODEEOOR_PARAM_NAME];/****无效请求参数***/
                        }
                    }else{
                        return ["code"=>CodeError::CODEEOOR_PARAM_CODE,"message"=>CodeError::CODEEOOR_PARAM_NAME];/****无效请求参数***/
                    }
               break;
            case 'GET':
                if(!empty($param)){
                    if($param['type']==1){
                        return $this->wechat_get($param);       //获取用户微信基本信息
                    }elseif ($param['type']==2){
                        return $this->qiniu($param);         //对接七牛云
                    }elseif($param['type']==3){
                        return $this->isCodeReg($param);        //用户注册是否开启邀请码
                    }else{
                        return ["code"=>CodeError::CODEEOOR_PARAM_CODE,"message"=>CodeError::CODEEOOR_PARAM_NAME];/****无效请求参数***/
                    }
                }else{
                    return ["code"=>CodeError::CODEEOOR_PARAM_CODE,"message"=>CodeError::CODEEOOR_PARAM_NAME];/****无效请求参数***/
                }
                break;
            default:
                return ["code"=>CodeError::CODEEOOR_REQUEST_CODE,"message"=>CodeError::CODEEOOR_REQUEST_NAME];
        }
    }

    /**判断号码是否已注册
     * @function
     * @param $param
     * @return bool
     */
    protected function is_have_phone($param){
        if(!key_is_set(array("mobile"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];
        }
        $map = [
            'mobile' => $param['mobile'],
        ];
        $user = db('member')->where($map)->find();
        if($user){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 用户注册是否开启邀请码
     * @function
     * @param $param
     * @return array
     */
    protected function isCodeReg($param){
        $code = db('config')->where('name','IS_CODE_REG')->select();
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$code,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /*
     * 用户登录----------->浏览器端（非微信进入）----------------------------------------------》已取消
     * @$param请求参数
     * @$param['mobile']账号
     * @$param['password']密码
     */
    protected function login($param)
    {
        header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("mobile","password"),$param)){
         return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];
        }
        $data=array(
            'mobile'=>$param['mobile'],
            'password'=>$param['password'],
        );
        $model = new MemberModel();
        $res = $model->login($data);
        if($res['uid']){
            config("is_to_debug")?$res['token'] =  config("is_to_debug_token"):$res['token'] =  authcode($res['uid'],'ENCODE',"smallv",config("token_time"));
            $res['token'] = urlencode($res['token']);
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME;
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_ERR_USERANDPASS_NAME;
            $code = CodeError::CODEEOOR_ERR_USERANDPASS_CODE;
        }
        return ['code'=>$code,'data'=>$res,'message'=>$message];
    }

    /***微信端已有账号-----使用账号密码登陆
     * @param $param
     * @return array
     * mobile password  openid/xopenid  name  pic address sex
     * method  post 6
     */
    protected function wechat_login($param){
        header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("mobile","password"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];
        }

        /*************防止一号多用******/
        //公众号端
        if($param['openid']){
            //先检测账号是否有绑定微信
            $user = db('member')->where('mobile',$param['mobile'])->find();
            if(!empty($user) && !empty($user['openid']) && ($user['openid'] != $param['openid'])){
                return ['code'=>CodeError::CODEEOOR_ERR_WECHAT_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ERR_WECHAT_NAME];                   //绑定的微信与该微信不一致，无法登陆
            }
            //浏览器注册不带openid
            $member = db('member')->where('openid',$param['openid'])->find();
            if(!empty($member) && ($member['mobile'] != $param['mobile'])){
                return ['code'=>CodeError::CODEEOOR_ERR_WECHATEXIST_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ERR_WECHATEXIST_NAME];                   //该微信已被绑定
            }
        }
        //小程序端
        if($param['xopenid']){
            //先检测账号是否有绑定微信
            $user = db('member')->where('mobile',$param['mobile'])->find();
            if(!empty($user) && !empty($user['xopenid']) && ($user['xopenid'] != $param['xopenid'])){
                return ['code'=>CodeError::CODEEOOR_ERR_WECHAT_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ERR_WECHAT_NAME];                   //绑定的微信与该微信不一致，无法登陆
            }
            //浏览器注册不带xopenid
            $member = db('member')->where('xopenid',$param['xopenid'])->find();
            if(!empty($member) && ($member['mobile'] != $param['mobile'])){
                return ['code'=>CodeError::CODEEOOR_ERR_WECHATEXIST_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ERR_WECHATEXIST_NAME];                   //该微信已被绑定
            }
        }
        /***********结束********/

        $model = new MemberModel();
        $res = $model->login_wechat($param);
        if(!empty($res['uid'])){
            config("is_to_debug")?$res['token'] =  config("is_to_debug_token"):$res['token'] =  authcode($res['uid'],'ENCODE',"smallv",config("token_time"));
            $res['token'] = urlencode($res['token']);
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME;
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_ERR_USERANDPASS_NAME;
            $code = CodeError::CODEEOOR_ERR_USERANDPASS_CODE;
        }
        return ['code'=>$code,'data'=>$res,'message'=>$message];
    }


    /***  微信端进入,获取code的url
     * @param $param
     * type 1 GET
     */
    protected function wechat_get($param){
        if(empty($param['cate'])){
            $wechat = new Wechat(array(
                "back_url"=>config("wechat.back_url"),
                "AppID"=>config("wechat.AppID"),
                "AppSecret"=>config("wechat.AppSecret"),
            ));
        }else{
            $wechat = new Wechat(array(
                "back_url"=>config("wechat.bind_back_url"),
                "AppID"=>config("wechat.AppID"),
                "AppSecret"=>config("wechat.AppSecret"),
            ));
        }
        $url = $wechat->get_code();
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$url,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }

    /**用户消息推送绑定
     * @function
     * @return array
     */
    public function bind_back_url(){
        if(isset($_GET['code'])){
            $Wechat=new Wechat(array(
                "back_url"=>config("wechat.bind_back_url"),
                "AppID"=>config("wechat.AppID"),
                "AppSecret"=>config("wechat.AppSecret"),
            ));
            header("Content-type:text/html;charset=utf-8");
            $access=$Wechat->code_access_token($_GET['code']);
            $info=$Wechat->getUserinfo($access);
            $openid = $info->openid;
            $this->redirect(config('front_url').'login.html?openid='.$openid);
        }else{
            return ['code'=>CodeError::CODEEOOR_NOACCESSCODE_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NOACCESSCODE_NAME];         //code不存在返回错误码
        }
    }


    /***请求微信获取用户微信基本信息-------------------------------------------------------》已取消
     * @return array
     *
     */
    public function back_url(){
        if (isset($_GET['code'])){
            $Wechat=new Wechat(array(
                "back_url"=>config("wechat.back_url"),
                "AppID"=>config("wechat.AppID"),
                "AppSecret"=>config("wechat.AppSecret"),
            ));
            header("Content-type:text/html;charset=utf-8");
            $access=$Wechat->code_access_token($_GET['code']);
            $info=$Wechat->getUserinfo($access);
            $user = db("member")->where('openid',$info->openid)->find();
            if($user){                                                  //如果存在用户------直接登陆
                config("is_to_debug")?$res['token'] =  config("is_to_debug_token"):$res['token'] =  authcode($user['uid'],'ENCODE',"smallv",config("token_time"));
                $res['token'] = urlencode($res['token']);
                $res['token_time'] = time()+config("token_time");
                $res['uid'] = $user['uid'];
                $openid = $info->openid;
                $headpic = $info->headimgurl;
                $address = urlencode($info->country).urlencode($info->province).urlencode($info->city);
                $sex = $info->sex;
                if(empty($info->nickname)){
                    $nickname = urlencode("xv_".GetRandStr(5));
                }else{
                    $nickname = urlencode($info->nickname);
                }
                if(!empty($headpic)){
                    $other['headpic'] = $info->headimgurl;
                }
                $this->redirect(config('front_url').'login?type=1&token='.$res['token'].'&token_time='.$res['token_time'].'&uid='.$res['uid'].'&openid='.$openid.'&nickname='.$nickname.'&headpic='.$headpic.'&address='.$address.'&sex='.$sex);
            }else{                                                              //如果不存在用户------注册
                $openid = $info->openid;
                if(empty($info->nickname)){
                    $nickname = urlencode("xv_".GetRandStr(5));
                }else{
                    $nickname = urlencode($info->nickname);
                }
                $headpic = $info->headimgurl;
                $address = urlencode($info->country).urlencode($info->province).urlencode($info->city);
                $sex = $info->sex;
                $this->redirect(config('front_url').'register?type=2&openid='.$openid.'&nickname='.$nickname.'&headpic='.$headpic.'&address='.$address.'&sex='.$sex);
            }
        }else{
            return ['code'=>CodeError::CODEEOOR_NOACCESSCODE_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NOACCESSCODE_NAME];         //code不存在返回错误码
        }
    }


    /**小程序获取用户微信基本信息
     * @function
     * @return array
     */
    public function xcx_back_url(){
        header("Content-type:text/html;charset=utf-8");
        if(isset($_GET['code'])){
            $appid = config('wechatxcx.AppID');
            $secret = config('wechatxcx.AppSecret');
            $xc = new Xwechat($appid,$secret);
            $access = object_to_array($xc->get_member_code($_GET['code']));
            $pc = new Xcxwechat($appid,$access['session_key']);
            $errcode = $pc->decryptData($_GET['encryptedData'],$_GET['iv'],$data);
            $data = json_decode($data);
            if(empty($data)){
                return ['code'=>CodeError::CODEEOOR_SERVER_ERROR_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_SERVER_ERROR_NAME];         //没有获得用户信息，服务器异常，请稍后再试
            }
            $user = db("member")->where('xopenid',$data->openId)->find();
            if($user){                                                  //如果存在用户------直接登陆
                config("is_to_debug")?$res['token'] =  config("is_to_debug_token"):$res['token'] =  authcode($user['uid'],'ENCODE',"smallv",config("token_time"));
                $res['token'] = urlencode($res['token']);
                $res['token_time'] = time()+config("token_time");
                $res['uid'] = $user['uid'];
                $res['xopenid'] = $data->openId;            //小程序openid
                $res['headpic'] = $data->avatarUrl;         //头像
                $res['address'] = urlencode($data->country).urlencode($data->province).urlencode($data->city);  //地址
                $res['sex'] = $data->gender;        //性别
                $res['unionid'] = $data->unionId;   //unionid
                if(empty($data->nickName)){         //昵称
                    $res['nickname'] = urlencode("xv_".GetRandStr(5));
                }else{
                    $res['nickname'] = urlencode($data->nickName);
                }
                $res['status'] = 1;                 //已注册
                return $res;
            }else{                                                              //如果不存在用户------注册
                $res['xopenid'] = $data->openId;
                $res['headpic'] = $data->avatarUrl;
                $res['address'] = urlencode($data->country).urlencode($data->province).urlencode($data->city);
                $res['sex'] = $data->gender;
                $res['unionid'] = $data->unionId;
                if(empty($data->nickName)){
                    $res['nickname'] = urlencode("xv_".GetRandStr(5));
                }else{
                    $res['nickname'] = urlencode($data->nickName);
                }
                $res['status'] = 2;             //未注册
                return $res;
            }

        }else{
            return ['code'=>CodeError::CODEEOOR_NOACCESSCODE_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NOACCESSCODE_NAME];         //code不存在返回错误码
        }
    }


    /**
     *注册（同时生成新手任务）
     * @$param请求参数
     * @$param['mobile']注册账号
     * @$param['password']密码
     * @$param['send_code']短信验证码
     * @$param['code']邀请码
     **/
    protected function register($param){
//        dump($param);die;
        //判断 param 所带参数，字段是否存在，
        //"openid","name","img","address","sex"
        if(!key_is_set(array("mobile","password","send_code","code"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];
        }
        //自动验证
        $validate = new Validate([
            'mobile'  => 'require|max:11',
            'password' => 'require',
            'send_code'=> 'require',
        ]);
        if(!$validate->check($param)){
            return ['code'=>CodeError::CODEEOOR_NO_USERANDPASS_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NO_USERANDPASS_NAME];
        }
        $model = new MemberModel();
        //判断账号是否已被注册
        $mobileExist = $model->mobileExist($param['mobile']);
        //短信验证码
        $info = db('smsCode')->where('mobile',$param['mobile'])->find();

        $codemeg = db('config')->where('name','IS_CODE_REG')->find();    //用户注册是否开启邀请码
        if($codemeg['value']==1){
            $codes = check_smallv($param['code']);         //检测邀请码是否存在
        }
        if(!empty($param['openid'])){
            $openid = $model->openidExist($param['openid']);    //检测openid公众号
        }
        if(!empty($param['xopenid'])){
            $xopenid = $model->xopenidExist($param['xopenid']);    //检测xopenid小程序
        }
        if($mobileExist){
            $message = CodeError::CODEEOOR_EXIST_USER_NAME;//手机号已注册
            $code = CodeError::CODEEOOR_EXIST_USER_CODE;
        }elseif($param['send_code']!= $info['code'] || $param['send_code'] == ''){
            $message = CodeError::CODEEOOR_NEWS_PARAM_NAME; //短信验证码错误
            $code = CodeError::CODEEOOR_NEWS_PARAM_CODE;
        }elseif (time()>($info['sendtime']+15*60)){
            $message = CodeError::CODEEOOR_NEWS_OVER_NAME; //短信验证码已过期
            $code = CodeError::CODEEOOR_NEWS_OVER_CODE;
        }elseif($codemeg['value']==1 && empty($codes)) {

                $message = CodeError::CODEEOOR_NOEXISTCODE_NAME; //邀请码不存在
                $code = CodeError::CODEEOOR_NOEXISTCODE_CODE;
        }elseif ($codemeg['value']==1 && $codes['uid'] != 0) {
                $message = CodeError::CODEEOOR_CODEISEXIST_NAME; //邀请码已被使用
                $code = CodeError::CODEEOOR_CODEISEXIST_CODE;

        }elseif (!empty($param['openid']) && !empty($openid)){
            $message = CodeError::CODEEOOR_ERR_WECHATEXIST_NAME; //微信已被绑定
            $code = CodeError::CODEEOOR_ERR_WECHATEXIST_CODE;
        }elseif (!empty($param['xopenid']) && !empty($xopenid)){
            $message = CodeError::CODEEOOR_ERR_WECHATEXIST_NAME; //微信已被绑定
            $code = CodeError::CODEEOOR_ERR_WECHATEXIST_CODE;
        }else{
//            $param['types'] = $codes['type'];         //验证码类型
//            $param['group'] = $codes['group'];       //验证码所属管理员
            $res = $model->reg($param,$codemeg['value']);
            if($res){
                if(!empty($param['openid'])){
                    $par = array(
                        'first' => '您好，你已经成功注册成为通明小V会员',
                        'keyword1'=> $param['mobile'],
                        'keyword2'=> $param['password'],
                        'remark' => '感谢您的注册',
                    );
                    send($param['openid'],"register_success",$par);
                }
                $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //注册成功
                $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
            }else{
                $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//注册失败-----> 一般不会出现
                $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
            }
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }


    /** 绑定推送，验证手机号，微信openid是否被使用
     * @function
     * @param $param  mobile  openid
     * @return array
     */
    protected function is_bind($param){
        if(!key_is_set(array("mobile"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];
        }
        $model = new MemberModel();
        $mobileExist = $model->mobileExist($param['mobile']);   //判断账号是否存在

        if(empty($mobileExist)){
            return ['code'=>CodeError::CODEEOOR_NOEXIST_USER_CODE,'data'=>false,'message'=>CodeError::CODEEOOR_NOEXIST_USER_NAME];
        }elseif (!empty($mobileExist['openid'])){
            return ['code'=>CodeError::CODEEOOR_OPENIDEXIST_CODE,'data'=>false,'message'=>CodeError::CODEEOOR_OPENIDEXIST_NAME];
        }else{
            return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>true,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
        }
    }


    /**推送绑定微信公众号openid
     * @function
     * @param $param
     * @return array
     */
    protected function bind_openid($param){
        if(!key_is_set(array("mobile","send_code","openid"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];
        }

        if(empty($param['openid'])){
            return ['code'=>CodeError::CODEEOOR_NO_USERANDPASS_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NO_USERANDPASS_NAME];
        }
        //短信验证码
        $model = new MemberModel();
        $openid = $model->openidExist($param['openid']);    //检测openid公众号
        $info = db('smsCode')->where('mobile',$param['mobile'])->find();
        if($param['send_code']!= $info['code'] || $param['send_code'] == ''){
            return ['code'=>CodeError::CODEEOOR_NEWS_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NEWS_PARAM_NAME];
        }elseif (time()>($info['sendtime']+15*60)){
            return ['code'=>CodeError::CODEEOOR_NEWS_OVER_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NEWS_OVER_NAME];
        }elseif(!empty($openid)){
            return ['code'=>CodeError::CODEEOOR_ERR_WECHATEXIST_CODE,'data'=>false,'message'=>CodeError::CODEEOOR_ERR_WECHATEXIST_NAME];
        }else{
            $map = [
                'openid' => $param['openid'],
            ];
            $user = db('member')->where('mobile',$param['mobile'])->update($map);
            if($user){
                $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //修改成功
                $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
            }else{
                $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//修改失败-----> 一般不会出现
                $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
            }
            return ['code'=>$code,'data'=>null,'message'=>$message];
        }
    }



    /***获取手机验证码
     * @$param请求参数
     * @$param['mobile'] 手机号
     *
     */
    protected function getcode($param){
        if(!key_is_set(array("mobile"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $model = new MemberModel();
        //判断账号是否已被注册
        $mobileExist = $model->mobileExist($param['mobile']);
        if($mobileExist){
            $message = CodeError::CODEEOOR_EXIST_USER_NAME;//手机号已注册
            $code = CodeError::CODEEOOR_EXIST_USER_CODE;
        }else{
            if(phone_code($param['mobile'])=='提交成功'){
                $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //发送短信成功
                $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
            }else{
                $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//发送短信失败-----> 一般不会出现
                $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
            }
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }

    /**忘记密码获取手机验证码
     * @$param请求参数
     * @$param['mobile']手机号
     */
    protected function gettercode($param){
        if(!key_is_set(array("mobile"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $model  = new MemberModel();
        $mobileExist = $model->mobileExist($param['mobile']);
        if(!$mobileExist){
            $message = CodeError::CODEEOOR_NOEXIST_USER_NAME;//手机号不存在
            $code = CodeError::CODEEOOR_NOEXIST_USER_CODE;
        }else{
            if(phone_code($param['mobile'],2)=='提交成功'){
                $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //发送短信成功
                $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
            }else{
                $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//发送短信失败-----> 一般不会出现
                $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
            }
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }

    /**推送绑定获取验证码
     * @function
     * @param $param
     * @return array
     */
    protected function getbindcode($param){
        if(!key_is_set(array("mobile"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $model  = new MemberModel();
        $mobileExist = $model->mobileExist($param['mobile']);
        if(!$mobileExist){
            $message = CodeError::CODEEOOR_NOEXIST_USER_NAME;//手机号不存在
            $code = CodeError::CODEEOOR_NOEXIST_USER_CODE;
        }else{
            if(phone_code($param['mobile'],4)=='提交成功'){
                $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //发送短信成功
                $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
            }else{
                $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//发送短信失败-----> 一般不会出现
                $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
            }
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }

    /****忘记密码修改密码
     * @$param请求参数
     * @$param['mobile']手机号
     * @$param['send_code']短信验证码
     * @$param['password']密码
     */
    protected function forgetpwd($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("mobile","password","send_code"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        //自动验证
        $validate = new Validate([
            'mobile'  => 'require|max:11',
            'password' => 'require',
            'send_code'=> 'require',
        ]);
        if(!$validate->check($param)){
            return ['code'=>CodeError::CODEEOOR_NO_USERANDPASS_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NO_USERANDPASS_NAME]; //填写信息不完整
        }
        $model = new MemberModel();
        $mobileExist = $model->mobileExist($param['mobile']);
        $info = db('smsCode')->where('mobile',$param['mobile'])->find();
        $member = db('member')->where('mobile',$param['mobile'])->find();

        if(!$mobileExist){
            $message = CodeError::CODEEOOR_NOEXIST_USER_NAME;//手机号不存在
            $code = CodeError::CODEEOOR_NOEXIST_USER_CODE;
        }else if($param['send_code']!= $info['code'] || $param['send_code'] == ''){
            $message = CodeError::CODEEOOR_NEWS_PARAM_NAME; //短信验证码错误
            $code = CodeError::CODEEOOR_NEWS_PARAM_NAME;
        }else if(time()>($info['sendtime']+15*60)){
            $message = CodeError::CODEEOOR_NEWS_OVER_NAME; //短信验证码已过期
            $code = CodeError::CODEEOOR_NEWS_OVER_NAME;
        }else if(user_md5($param['password']) == $member['password']){
            $message = CodeError::CODEEOOR_ERR_SAMEPASS_NAME;//新密码不能与旧密码一致
            $code = CodeError::CODEEOOR_ERR_SAMEPASS_CODE;
        }else{
            $res = $model->forget($param);
            if($res){
                $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //修改成功
                $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
            }else{
                $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//修改失败-----> 一般不会出现
                $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
            }
        }

        return ['code'=>$code,'data'=>null,'message'=>$message];
    }


    /***七牛云
     * @param $param
     * @return string
     * type 2 get
     */
    protected function qiniu($param){
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







    /*
    * 用户信息模块------修改昵称 头像 年龄 性别 个人信息展示 实名认证 个人钱包等等 API
    *@type请求类别
    *@param请求参数（数组）
    *
    */

    public function user_info(){
        $request = Request::instance();
        $param= $request->param();
        switch ($request->method()){
            case 'POST':
                if(!empty($param)){
                    if($param['type']==1){                      /***提交实名认证资料***/
                        return $this->realname($param);
                    }elseif ($param['type']==2){                /***编辑个人资料***/
                        return $this->save_info($param);
                    }elseif ($param['type']==3){                /***提交身份证照片***/
                        return $this->cardimg($param);
                    }elseif($param['type']==4){                 //申请成为小V    取消
                        return $this->put_smallv($param);
                    }elseif($param['type']==5){                 //提交意见反馈
                        return $this->feedback($param);
                    }elseif($param['type']==6){                 //提交成为小V   取消
                        return $this->click_sub($param);
                    }elseif($param['type']==7){
                        return $this->proving($param);          //验证微博账号        取消
                    }elseif($param['type']==8){
                        return $this->add_account($param);      //添加小V资源账号
                    }elseif($param['type']==9){
                        return $this->bank_test($param);        //绑定银行卡
                    }elseif($param['type']==10){
                        return $this->cardcode($param);         //绑定银行卡发送短信
                    }elseif ($param['type']==11){
                        return $this->getSaveCode($param);      //更换绑定手机号-----发短信
                    }elseif ($param['type']==12){
                        return $this->edit_account($param);      //更新小V资源账号
                    }elseif($param['type']==13){
                        return $this->del_account($param);      //假删除小V资源账号
                    }elseif ($param['type']==10086){
                        return $this->testAccount($param);      //检查绑定账号密码
                    }else{
                        return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                    }
                }else{
                    return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                }
                break;

            case 'GET':
                if(!empty($param)){
                    if($param['type']==1){                  /**个人基本资料**/
                        return $this->member_info($param);
                    }elseif ($param['type']==2){            /**实名认证**/
                        return $this->realname_info($param);
                    }elseif($param['type']==3){             /**小V中心**/
                        return $this->smallv_info($param);
                    }elseif($param['type']==4){             /***账号库**/
                        return $this->my_account($param);
                    }elseif($param['type']==5){             /***账号信息***/
                        return $this->account_info($param);
                    }elseif($param['type']==6){
                        return $this->money_list($param);   /***收益记录****/
                    }elseif($param['type']==7){
                        return $this->wallet($param);       /***我的钱包***/
                    }elseif($param['type']==8){
                        return $this->history($param);      /***历史账单***/
                    }elseif($param['type']==9){
                        return $this->history_info($param); /***历史账单详细***/
                    }elseif($param['type']==10){
                        return $this->getcard($param);      /***绑定银行卡页面****/
                    }elseif($param['type']==11){
						return $this->agreement($param);    /***小V认证协议***/
					}elseif ($param['type']==12){
					    return $this->taskTypeList($param);     /***可选媒体的类别***/
                    }else{
                        return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                    }
                }else{
                    return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                }
                break;
            default:
                return ["code"=>CodeError::CODEEOOR_REQUEST_CODE,"message"=>CodeError::CODEEOOR_REQUEST_NAME];
        }
    }

    /**
     * 可选媒体的类别
     * @function
     * @param $param
     * @return array
     */
    protected function taskTypeList($param){
        $where = [
            'status' => 1,
        ];
        $list = db('taskType')->where($where)->select();
        if($list){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //发送短信成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//发送短信失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>$list,'message'=>$message];
    }



    /**更换绑定手机号-----发短信
     * @$param请求参数
     * @$param['mobile']手机号
     * @$param['cate']  1为给原先号码   2为给新号码
     */
    protected function getSaveCode($param){
        if(!key_is_set(array("mobile","cate"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $model  = new MemberModel();
        $mobileExist = $model->mobileExist($param['mobile']);
        if($param['cate']==1){
            if(!$mobileExist){
                $message = CodeError::CODEEOOR_NOEXIST_USER_NAME;//手机号不存在
                $code = CodeError::CODEEOOR_NOEXIST_USER_CODE;
            }else{
                if(phone_code($param['mobile'],4)=='提交成功'){
                    $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //发送短信成功
                    $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
                }else{
                    $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//发送短信失败-----> 一般不会出现
                    $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
                }
            }
        }elseif($param['cate']==2){
            if($mobileExist){
                $message = CodeError::CODEEOOR_EXIST_USER_NAME;//手机号已注册
                $code = CodeError::CODEEOOR_EXIST_USER_CODE;
            }else{
                if(phone_code($param['mobile'],3)=='提交成功'){
                    $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //发送短信成功
                    $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
                }else{
                    $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//发送短信失败-----> 一般不会出现
                    $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
                }
            }
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }






    /*
     **** 编辑个人资料
     * @ $param['category'] 操作类别
     * @ $param['nickname']昵称
     * @ $param['sex']性别
     * @ $param['age']年龄
     * @ $param['email']邮箱
     * @ $file 头像
     */

    protected function save_info($param){
        $file = request()->file('file');
        if($param['category'] == 1 && $param['nickname'] != ''){              //昵称
            $map = array(
                'nickname'=>base64_encode($param['nickname']),
                'edittime' => time(),
            );
            $res = db('member')->where('uid',UID)->update($map);
            if($res){
                $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //修改成功
                $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
                $result = $param['nickname'];
            }else{
                $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//修改失败-----> 一般不会出现
                $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
                $result = null;
            }
        }elseif ($param['category']==2 && $param['sex'] != ''){              //性别
            $map = array(
                'sex'=>$param['sex'],
                'edittime' => time(),
            );
            $res = db('member')->where('uid',UID)->update($map);
            if($res){
                $info = db('member')->where('uid',UID)->find();
                $sex = '';
                if($info['sex']==1){
                    $sex = '男';
                }elseif ($info['sex']==2){
                    $sex = '女';
                }
                $message = CodeError::CODESECCESS_COMMON_PARAM_NAME;
                $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
                $result = $sex;
            }else{
                $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;
                $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
                $result = null;
            }
        }elseif ($param['category']==3 && $param['email'] != ''){              //邮箱
            $map = array(
                'email'=>$param['email'],
                'edittime' => time(),
            );
            $res = db('member')->where('uid',UID)->update($map);
            if($res){
                $message = CodeError::CODESECCESS_COMMON_PARAM_NAME;
                $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
                $result = null;
            }else{
                $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;
                $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
                $result = null;
            }
        }elseif ($param['category']==4 && $param['codes'] !='' && $param['mobile'] !=''){       //原号码手机验证码核实
            $info = db('smsCode')->where('mobile',$param['mobile'])->find();

            if($param['codes']!= $info['code'] || $param['codes'] == ''){
                $message = CodeError::CODEEOOR_NEWS_PARAM_NAME; //短信验证码错误
                $code = CodeError::CODEEOOR_NEWS_PARAM_CODE;
                $data = null;
            }else if(time()>($info['sendtime']+15*60)){
                $message = CodeError::CODEEOOR_NEWS_OVER_NAME; //短信验证码已过期
                $code = CodeError::CODEEOOR_NEWS_OVER_CODE;
                $data = null;
            }else{
                $message = CodeError::CODESECCESS_COMMON_PARAM_NAME;
                $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
                $result = 1;
            }
        }elseif ($param['category']==7 && $param['code'] !='' && $param['mobile'] !=''){        //新号码修改
            $info = db('smsCode')->where('mobile',$param['mobile'])->find();
            if($param['code']!= $info['code'] || $param['code'] == ''){
                $message = CodeError::CODEEOOR_NEWS_PARAM_NAME; //短信验证码错误
                $code = CodeError::CODEEOOR_NEWS_PARAM_CODE;
                $data = null;
            }else if(time()>($info['sendtime']+15*60)){
                $message = CodeError::CODEEOOR_NEWS_OVER_NAME; //短信验证码已过期
                $code = CodeError::CODEEOOR_NEWS_OVER_CODE;
                $data = null;
            }else{
                $map = array(
                    'mobile'=>$param['mobile'],
                    'edittime' => time(),
                );
                $res = db('member')->where('uid',UID)->update($map);
                if($res){
                    $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //修改成功
                    $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
                    $result = $param['mobile'];
                }else{
                    $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//修改失败-----> 一般不会出现
                    $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
                    $result = null;
                }
            }
        }elseif ($param['category']==5 && $file != ''){                                //头像
            $data = $file->move(ROOT_PATH . 'public' . DS . 'uploads'.DS.'member');
            if($data){
                $param['headpic'] = $data->getSaveName();
                $headpic = 'http://'.$_SERVER['SERVER_NAME']."/uploads/member/".$param['headpic'];
            }else {
                return ['code'=>CodeError::CODEEOOR_NO_IMG_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NO_IMG_NAME]; //头像不能为空
            }
            $res = db('member')->where('uid',UID)->update(['headpic'=>$headpic]);
            if($res){
                $message = CodeError::CODESECCESS_COMMON_PARAM_NAME;
                $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
                $result['headpic'] = $headpic;
            }else{
                $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;
                $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
                $result = null;
            }
        }elseif ($param['category']==6 && $param['year']!=''&& $param['month'] !='' && $param['day'] != ''){          //年龄
            $time = mktime(0,0,0,$param['month'],$param['day'],$param['year']);
            $age = floor((time()-$time)/(60*60*24*365));
            $map = array(
                'age'=>$age,
                'edittime' => time(),
            );
            $res = db('member')->where('uid',UID)->update($map);
            if($res){
                $message = CodeError::CODESECCESS_COMMON_PARAM_NAME;
                $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
                $result = $age;
            }else{
                $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;
                $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
                $result = null;
            }
        }else{
            $message = CodeError::CODEEOOR_PARAM_NAME;  //请求参数不正确
            $code = CodeError::CODEEOOR_PARAM_CODE;
            $result = null;
        }
        return ['code'=>$code,'data'=>$result,'message'=>$message];
    }


    /*** 实名认证提交照片
     * @param $param
     * @return array
     */
    protected function cardimg($param){
        $file = request()->file('file');
        if($file){
            $data = $file->move(ROOT_PATH . 'public' . DS . 'uploads'.DS.'realname');
            if($data){
                $param['cardimg'] = $data->getSaveName();
            }else {
                return ['code'=>CodeError::CODEEOOR_NO_IMG_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NO_IMG_NAME]; //手持身份证正面照不能为空
            }
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME;
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
            $result = 'http://'.$_SERVER['SERVER_NAME']."/uploads/realname/".$param['cardimg'];
        }else{
            $message = CodeError::CODEEOOR_PARAM_NAME;  //请求参数不正确
            $code = CodeError::CODEEOOR_PARAM_CODE;
            $result = null;
        }
        return ['code'=>$code,'data'=>$result,'message'=>$message];
    }


    /***
     * 实名认证提交
     * @ $param['realname']  真实姓名
     * @ $param['cardnum'] 身份证号
     * @ $param['cardimg'] 身份证照
     */
    protected function realname($param){
        //        判断 param 所带参数，字段是否存在，
        if (!key_is_set(array("realname", "cardnum", "cardimg"), $param)) {
            return ['code' => CodeError::CODEEOOR_PARAM_CODE, 'data' => null, 'message' => CodeError::CODEEOOR_PARAM_NAME]; //请求参数不正确
        }
        //自动验证
        $validate = new Validate([
            'realname' => 'require',
            'cardnum' => 'require',
            'cardimg' => 'require',
        ]);
        if (!$validate->check($param)) {
            return ['code' => CodeError::CODEEOOR_NO_USERANDPASS_CODE, 'data' => null, 'message' => CodeError::CODEEOOR_NO_USERANDPASS_NAME]; //填写信息不完整
        }
        $other = [
            'cardnum' => $param['cardnum'],
            'status' => 2,
        ];
        $member = db('memberRealname')->where($other)->find();
        if(!empty($member)){
            return ['code' => CodeError::CODEEOOR_CARDNUMEXIST_CODE, 'data' => null, 'message' => CodeError::CODEEOOR_CARDNUMEXIST_NAME]; //身份证已被绑定
        }

        $info = db('memberRealname')->where('uid',UID)->find();
        if(empty($info)) {
            $model = new MemberRealname();
            $res = $model->add_realname($param);    //添加
        }elseif($info && $info['status']==3){
            $model = new MemberRealname();
            $res = $model->edit_realname($param);    //编辑
        }
        if ($res) {
            $data = db('memberRealname')->where('uid', UID)->find();
            $user = db('member')->where('uid',UID)->find();
            $data['apply_status'] = $user['apply'];
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        } else {
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
            $data = null;
        }
        return ['code'=>$code,'data'=>$data,'message'=>$message];
    }


    /*******实名认证详情
     * @param $param
     * @return array
     */
    protected function realname_info($param){
        $info = db('memberRealname')->where('uid',UID)->find();
        if($info){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        } else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>$info,'message'=>$message];

    }

    /***
     * @param $param 个人基本信息
     * type 1 get
     *
     */
    protected function member_info($param){
        $info = db('member')->field('mobile,headpic,age,sex,email,nickname,experience,apply')->where('uid',UID)->find();
        if($info){
            $info['nickname'] = base64_decode($info['nickname']);
            $realname = db('memberRealname')->where('uid',UID)->find();
            if(!empty($realname)){
                $info['realname'] = $realname['realname'];
                $info['cardnum'] = $realname['cardnum'];
            }
            if($info['sex']){
                if($info['sex']==1){
                    $info['sex'] = '男';
                }elseif ($info['sex']==2){
                    $info['sex'] = '女';
                }
            }
            $mapse['experience'] = array('gt',$info['experience']);
            $mapse['status'] = 1;
            $grade = db('smallvGrade')->where($mapse)->find();
            if($info['experience']==0 && $info['apply']!=2){
                $other['experience'] = 0;
                $other['status'] = 1;
                $level = db('smallvGrade')->where($other)->find();

                $info['level'] = $level['id'];
                $info['level_name'] = $level['name'];
                $info['sv_icon'] = 'http://'.$_SERVER['SERVER_NAME']. "/uploads/member/".$level['sv_icon'];

            }elseif($grade){   //小V账号等级（认证完成后默认为一级）
                $info['level'] = $grade['id'];
                $info['level_name'] = $grade['name'];
                $info['sv_icon'] = 'http://'.$_SERVER['SERVER_NAME']. "/uploads/member/".$grade['sv_icon'];
            }else{                                                  //经验值超出最大等级限制，就为最高等级
                $wheress['experience'] = array('elt',$info['experience']);
                $wheress['status'] = 1;
                $grade = db('smallvGrade')->where($wheress)->order('experience desc')->find();
                $info['level'] = $grade['id'];
                $info['level_name'] = $grade['name'];
                $info['sv_icon'] = 'http://'.$_SERVER['SERVER_NAME']. "/uploads/member/".$grade['sv_icon'];
            }
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>$info,'message'=>$message];
    }


    /***收益记录
     * @param $param
     * type  6  get
     * page 分页
     */
    protected function money_list($param){
        if(!key_is_set(array("page"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $maps = array(
            'v_sub_order.to_user_id' => UID,
            'v_sub_order.status' => 3,
        );
        $lists = db('subOrder')->alias('a')->join('v_order b','a.oid = b.id')->join('v_tasks c','c.id = b.order_id')->field('a.price as num,a.auditing_time,c.name as taskname,c.type as tasktype')->where($maps)->paginate(10);
        $lists = $lists->items();
        foreach ($lists as $key =>$value){
            $other = $value['tasktype'];                            //任务类别
            $types = db('taskType')->where('id',$other)->find();
            $lists[$key]['tasktype'] = 'http://'.$_SERVER['SERVER_NAME']. '/uploads/type/'.$types['tp_icon'];
            $lists[$key]['auditing_time'] = date("Y-m-d",$value['auditing_time']);
        }
        $data['list'] = $lists;
        $list =  db('subOrder')->where($maps)->select();
        $data['count'] = count($list);
        $money = array_column($list,'price');
        $data['money'] = round(array_sum($money),1);
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }



    /***意见反馈表
     * @param $param
     * content 内容
     * type 5 post
     */
    protected function feedback($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("content"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $data = [
            'content' => $param['content'],
            'uid' => UID,
            'addtime' => time(),
        ];
        $model = new Opinion();
        $res = $model->add_opinion($data);
        if($res){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }



    /*** 申请成为小V页面,成为小v的几个条件
     * @param $param
     * type 4 post
     */
    protected function put_smallv($param){
        $info = db('memberRealname')->where('uid',UID)->find(); //查看是否提交实名认证
        if(!empty($info) && (($info['status']==2))){
            $data['is_realname'] = 2; //实名认证通过
        }else{
            $data['is_realname'] = 1;//未通过
        }
        $user = db('member')->where('uid',UID)->find();
//        if(empty($user['openid'])){
//            $data['is_wechat'] = 2; //未绑定微信
//        }else{
//            $data['is_wechat'] = 1; //已绑定
//        }
//        $other = db('activeCode')->where('uid',UID)->find();//判断原先是否为小V用户
//        if($other && ($other['type'] == 0)){           //新注册用户，按正常流程走
//            $map = array(
//                'novice' => 1,  //新手任务
//                'status' => 2, //发布中状态
//            );
//            $newbie_count = db('tasks')->where($map)->count();   //新手任务数量
//            $where = array(
//                'is_novice' => 1, //新手任务订单
//                'status' => array('in','2,5'), //已执行
//                'to_user_id' => UID,
//            );
//            $order_count = db('order')->where($where)->count();
//            if($newbie_count == $order_count){
//                $data['is_newbie'] = 1;              //新手任务全部提交
//            }else{
//                $data['is_newbie'] = 2;
//            }
//        }else{
//            $data['is_newbie'] = 1;
//        }
        $data['status'] = $user['apply'];           //申请小V状态
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];

    }



    /***点击申请成为小V
     * @param $param
     * type 6 post
     */
    protected function click_sub($param){
        $model = new MemberModel();
        $res = $model->become_smallv($param);
        if($res){
            $user = db('member')->where('uid',UID)->find();
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
            $data['status'] = $user['apply'];
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
            $data['status'] = null;
        }
        return ['code'=>$code,'data'=>$data,'message'=>$message];
    }

    /*** 小V中心
     * @param $param
     *
     * type  3 get
     */
    protected function smallv_info($param){
        $info  = db('member')->where('uid',UID)->find();
        if($info['headpic']){
            $data['headpic'] = $info['headpic'];
        }else{
            $data['headpic'] = null;
        }
        if($info['nickname']){
            $data['nickname'] = base64_decode($info['nickname']);
        }else{
            $data['nickname'] = null;
        }
        if($info['mem_status'] == 0){       //暂时封号
            $data['seal_time'] = $info['seal_time'];
        }elseif ($info['mem_status'] == 2){         //失去小V资格
            $data['seal_time'] = 99999999999;
        }
        $data['experience'] = $info['experience'];  //经验值
        $data['credit'] = $info['credit'];      //信用值
        $data['gold'] = $info['gold'];  //拥有金币数量
        $mapse['experience'] = array('gt',$info['experience']);
        if($grade = db('smallvGrade')->where($mapse)->find()){   //小V账号等级（认证完成后默认为一级）
            $data['level'] = $grade['id'];
            $data['level_name'] = $grade['name'];
        }else{                                                  //经验值超出最大等级限制，就为最高等级
            $wheress['experience'] = array('elt',$info['experience']);
            $grade = db('smallvGrade')->where($wheress)->order('experience desc')->find();
            $data['level'] = $grade['id'];
            $data['level_name'] = $grade['name'];
        }
        $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;  //本日0点-24点时间

        $where['addtime'] = array('between',[$beginToday,$endToday]);
        $where['status'] = 5;
        $where['to_user_id'] = UID;

        $map['is_novice'] = 0;
        $plain_tasks = db('order')->where($where)->where($map)->count();            //今日完成任务数量
        if($plain_tasks == 20){
            $data['plain_status'] = 1;                                             //完成日常任务
            $data['plain_tasks'] = $plain_tasks;
        }else{
            $data['plain_status'] = 2;                                             //未完成
            $data['plain_tasks'] = $plain_tasks;
        }

        $maps['is_novice'] = 2;
        $keep_tasks = db('order')->where($where)->where($maps)->count();            //今日完成养号任务数量
        if($keep_tasks == 2){
            $data['keep_status'] = 1;                                             //完成养号任务
            $data['keep_tasks'] = $keep_tasks;
        }else{
            $data['keep_status'] = 2;                                             //未完成
            $data['keep_tasks'] = $keep_tasks;
        }


        $beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
        $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));       //获取本月起始结束时间
        $wheres['addtime'] = array('between',[$beginThismonth,$endThismonth]);
        $wheres['status'] = 5;
        $wheres['to_user_id'] = UID;


        $map['is_novice'] = 0;
        $all_plain_tasks = db('order')->where($wheres)->where($map)->count();            //本月完成任务数量
        if($all_plain_tasks == 600){
            $data['all_plain_status'] = 1;                                             //完成日常任务
            $data['all_plain_tasks'] = $all_plain_tasks;
        }else{
            $data['all_plain_status'] = 2;                                             //未完成
            $data['all_plain_tasks'] = $all_plain_tasks;
        }

        $maps['is_novice'] = 2;
        $all_keep_tasks = db('order')->where($wheres)->where($maps)->count();            //本月完成养号任务数量
        if($all_keep_tasks == 60){
            $data['all_keep_status'] = 1;                                             //完成养号任务
            $data['all_keep_tasks'] = $all_keep_tasks;
        }else{
            $data['all_keep_status'] = 2;                                             //未完成
            $data['all_keep_tasks'] = $all_keep_tasks;
        }

        $any['id'] = array('in','2,3,4,5');
        $any['status'] = 1;
        $increase = db('increase')->field('experience,gold,number')->where($any)->select();
        $data['plain_day'] = $increase[0];
        $data['keep_day'] = $increase[1];
        $data['plain_month'] = $increase[2];
        $data['keep_month'] = $increase[3];

        $garde = db('smallvGrade')->where('status',1)->select();
        array_shift($garde);
        foreach ($garde as $key=>$val){
            $garde[$key]['sv_icon'] = 'http://'.$_SERVER['SERVER_NAME'].'/uploads/member/'.$val['sv_icon'];
        }
        $data['grades'] = $garde;

        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }




    /***我的账号库
     * @param $param
     * type 4 get
     */
    protected function my_account($param){
        $map = array(
            'type' => 1,     //媒体库开启
            'status'=> 1    //启用状态
        );
        $type = db('taskType')->where($map)->select();
        $arr = [];
        $where = array(
            'uid' => UID,
            'enable' => 1,       //该账号是否启用
            'is_del' => 1
        );
        foreach ($type as $k=>$v){
            $arr[$k]['name']=$v['name'];
            $arr[$k]['id']=$v['id'];
            $arr[$k]['count']=db('smallvAccount')->where($where)->where('type',$v['id'])->count();
        }
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$arr,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /*** 分配账号信息
     * @param $param
     *id 账号类型id
     * type 5 get
     */
    protected function account_info($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("id"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $map = array(
            'uid' =>UID,
            'type' => $param['id'],
            'enable' => 1,
            'is_del' =>1

        );
        $list = db('smallvAccount')->where($map)->select();
        $count = count($list);
//        $user = db('activeCode')->where('uid',UID)->find();
        $data = array(
            'list' => $list,
            'count' => $count,
//            'is_smallv' => $user['type']
        );
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /***检测微博是否存在
     * @param $param
     * @return array
     * apple        z账号
     * pwd          密码
     * type   7   post
     */
    protected function proving($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("apple","pwd"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $info = weibo_login($param['apple'],$param['pwd']);
        if($info['retcode'] == 0 && !empty($info['uid']) && !empty($info['nick'])){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
            $data = $info['nick'];
        }else{
            $message = CodeError::CODEEOOR_SINANOINFO_NAME; //                       该账号未检测到微博信息
            $code = CodeError::CODEEOOR_SINANOINFO_CODE;
            $data = null;
        }
        return ['code'=>$code,'data'=>$data,'message'=>$message];
    }



    /*** 小V添加账号
     * @param $param
     * apple        z账号
     * pwd          密码
     * typeid       账号类型
     * type 8 post
     */
    protected function add_account($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("apple","pwd","typeid"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $map = array(
            'type' => $param['typeid'],                    //账号类型
            'uid' => UID,
            'is_del' =>1
        );
        $count = db('smallvAccount')->where($map)->count();
        if($count > 3){
            return ['code'=>CodeError::CODEEOOR_SINANUMS_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_SINANUMS_NAME];    //资源账号已达上限
        }
        $types = db('taskType')->where('id',$param['typeid'])->find();
        if($types['type']==0){
            return ['code'=>CodeError::CODEEOOR_TYPENOOPEN_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_TYPENOOPEN_NAME];    //该类型媒体库暂未开放
        }
        $maps = [
            'type' => $param['typeid'],
            'apple' => $param['apple'],
            'is_del' => 1,
        ];
        if(db('smallvAccount')->where($maps)->find()){
            return ['code'=>CodeError::CODEEOOR_SINAEXIST_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_SINAEXIST_NAME];    //该资源账号已存在
        }
        $model = new MemberModel();
        if($param['typeid']==1){        //针对微博核实账号密码
            $ip = GetIp();
            $info = weibo_login($param['apple'],$param['pwd'],$ip);
            if(!empty($info)) {
                if ($info['retcode'] == 0) {
                    $sina = get_weibo_data($info['uid']);
                    if (empty($sina)) {
                        return ['code' => CodeError::CODEEOOR_SINA_NONICKNAME_CODE, 'data' => null, 'message' => CodeError::CODEEOOR_SINA_NONICKNAME_NAME];     //根据微博id，获取信息
                    }
                    $infos = [
                        'account' => $sina['nickname'],
                        'wid' => $info['uid'],
                        'apple' => $param['apple'],
                        'pwd' => $param['pwd'],
                        'enable' => 1,
                        'type' => $param['typeid'],
                        'uid' => UID,
                        'status' => 2,
                        'addtime' => time(),
                    ];
                    $res = db('smallvAccount')->insert($infos);
                } elseif ($info['retcode'] == 2089) {
                    return ['code' => CodeError::CODEEOOR_SINA_PROTECTACCOUNT_CODE, 'data' => null, 'message' => CodeError::CODEEOOR_SINA_PROTECTACCOUNT_NAME];   //您已开启登录保护,请先解除
                } elseif ($info['retcode'] == 4069) {
                    return ['code' => CodeError::CODEEOOR_SINA_LONGNOLOGIN_CODE, 'data' => null, 'message' => CodeError::CODEEOOR_SINA_LONGNOLOGIN_NAME];   //帐号太久未登录,请前往微博激活
                } elseif ($info['retcode'] == 101) {
                    return ['code' => CodeError::CODEEOOR_SINANOINFO_CODE, 'data' => null, 'message' => CodeError::CODEEOOR_SINANOINFO_NAME];   // 登录名或密码错误
                } else {
                    return ['code' => CodeError::CODEEOOR_NETWORK_ERROR_CODE, 'data' => null, 'message' => CodeError::CODEEOOR_NETWORK_ERROR_NAME];     //异常情况
                }
            }else{
                return ['code' => CodeError::CODEEOOR_SERVER_ERROR_CODE, 'data' => null, 'message' => CodeError::CODEEOOR_SERVER_ERROR_NAME];     //服务器异常，请稍后再试
            }
        }else{
            $res = $model->add_account($param);
        }
        if($res){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }

    /**编辑小V账号
     * @function
     * apple        z账号
     * pwd          密码
     * accountid    账号id
     * @param $param
     * @return array
     */
    protected function edit_account($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("apple","pwd","accountid"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }

        $account = db('smallvAccount')->where('id',$param['accountid'])->find();
//        $maps = [
//            'type' => $account['type'],
//            'apple' => $param['apple'],
//            'is_del' => 1,
//        ];
//        if(db('smallvAccount')->where($maps)->find()){
//            return ['code'=>CodeError::CODEEOOR_SINAEXIST_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_SINAEXIST_NAME];    //该资源账号已存在
//        }
        if($account['type']==1){        //针对微博核实账号密码
            $ip = GetIp();
            $info = weibo_login($param['apple'],$param['pwd'],$ip);
            if(!empty($info)){
                if($info['retcode'] == 0 ){
                    $sina = get_weibo_data($info['uid']);
                    if(empty($sina)){
                        return ['code'=>CodeError::CODEEOOR_SINA_NONICKNAME_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_SINA_NONICKNAME_NAME];     //根据微博id，获取信息
                    }
                    $infos = [
                        'account' => $sina['nickname'],
                        'wid' => $info['uid'],
                        'apple'=>$param['apple'],
                        'pwd' => $param['pwd'],
                    ];
                    $res = db('smallvAccount')->where('id',$param['accountid'])->update($infos);
                }elseif ($info['retcode']==2089){
                    return ['code'=>CodeError::CODEEOOR_SINA_PROTECTACCOUNT_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_SINA_PROTECTACCOUNT_NAME];   //您已开启登录保护,请先解除
                }elseif ($info['retcode'] == 4069){
                    return ['code'=>CodeError::CODEEOOR_SINA_LONGNOLOGIN_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_SINA_LONGNOLOGIN_NAME];
                }elseif($info['retcode'] == 101){
                    return ['code'=>CodeError::CODEEOOR_SINANOINFO_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_SINANOINFO_NAME];   // 登录名或密码错误
                }else{
                    return ['code'=>CodeError::CODEEOOR_NETWORK_ERROR_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NETWORK_ERROR_NAME];     //异常情况
                }
            }else{
                return ['code' => CodeError::CODEEOOR_SERVER_ERROR_CODE, 'data' => null, 'message' => CodeError::CODEEOOR_SERVER_ERROR_NAME];     //服务器异常，请稍后再试
            }
        }else{
            $infos = [
                'apple'=>$param['apple'],
                'pwd' => $param['pwd'],
            ];
            $res = db('smallvAccount')->where('id',$param['accountid'])->update($infos);
        }
        if($res){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }


    /**假删小V账号
     * @function
     * accountid    账号id
     * @param $param
     * @return array
     */
    protected function del_account($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("accountid"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $where = [
            'to_user_id' => UID,
            'account_id' => $param['accountid'],
            'status' => 2,      //执行中的状态
        ];
        $list = db('subOrder')->where($where)->find();
        if(!empty($list)){
            return ['code'=>CodeError::CODEEOOR_ACC_TOWORK_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ACC_TOWORK_NAME];    //该账号正在执行任务，无法删除
        }
        $info = db('smallvAccount')->where('id',$param['accountid'])->find();
        if($info['uid'] != UID){
            return ['code'=>CodeError::CODEEOOR_ABNORMAL_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ABNORMAL_NAME];
        }
        $map = [
            'is_del' => 2,          //假删除
        ];
        $res = db('smallvAccount')->where('id',$param['accountid'])->update($map);
        if($res){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }



    /***我的钱包
     * @param $param
     * @return array
     *page 分页
     * type 7 get
     */
    protected function wallet($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("page"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $bank = db('bankCard')->where('uid',UID)->find();
        if(!empty($bank)){
            $state = 1;
        }else{
            $state = 0;
        }
        $time = date('Y-m');
        $bill = db('bill')->where('time',$time)->find();     //查询当月的账单状态
        $map = array(
            'to_user_id' => UID,
            'status' => 3,
            'auditing_time' => array('between',array($bill['start'],$bill['end'])),
        );
        $list = db('subOrder')->field('price,auditing_time')->where($map)->order('auditing_time desc')->paginate(10);            //当月完成的子订单
        $list = $list->items();
        foreach ($list as $key=>$value){
            $list[$key]['auditing_time'] = date('Y-m-d H:i',$value['auditing_time']);
        }
        $lists = db('subOrder')->where($map)->select();
        $count = count($lists);
        $price = array_column($lists,'price');
        $money = round(array_sum($price),1);
        if($bill['status']<2){
            $status = '入账中';
        }else{
            $status = '已入账';
        }
        $data = array(
            'time' => $time,                                    //当前月
            'status' => $status,                               //账单状态
            'money' => $money,                                 //当月总收入
            'list' => $list,                                   //当月收入详细
            'count' => $count,
            'state' => $state
        );
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /***账单历史纪录
     * @param $param
     * @return array
     * page 分页
     * type 8 get
     */
    protected function history($param){
        if(!key_is_set(array("page"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $bank = db('bankCard')->where('uid',UID)->find();               //查看用户是否绑定了银行卡
        if(empty($bank)){
            return ['code'=>CodeError::CODEEOOR_BANK_NOEXIST_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_BANK_NOEXIST_NAME];           //未绑定银行卡
        }
        $addtime = date('Y-m-d',1504075523);                      //根据用户绑定卡的时间进行账单查询  '1504075523'  $bank['addtime']
        $map = array(
            'end' => array('>=',$addtime),
        );
        $bill = db('bill')->where($map)->order('addtime desc')->paginate(10);
        $count = db('bill')->where($map)->count();
        $bill = $bill->items();
        foreach ($bill as $key=>$value){
            $map = array(
                'to_user_id' => UID,
                'status' => 3,
                'auditing_time' => array('between',array($value['start'],$value['end'])),
            );
            $list = db('subOrder')->where($map)->select();
            $price = array_column($list,'price');
            $bill[$key]['money'] = round(array_sum($price),1);
            if($value['status']<2){
                $bill[$key]['status'] = '入账中';
            }else{
                $bill[$key]['status'] = '已入账';
            }
        }
        $data = array(
            'list' => $bill,
            'count' => $count
        );
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }

    /***历史账单详细
     * @param $param
     * @return array
     * page 分页
     * time 查询月份
     * type 9 get
     */
    protected function history_info($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("page","time"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $bill = db('bill')->where('time',$param['time'])->find();           //获取查询月的时间戳
        $map = array(
            'to_user_id' => UID,
            'status' => 3,
            'auditing_time' => array('between',array($bill['start'],$bill['end'])),
        );
        $list = db('subOrder')->field('price,auditing_time')->where($map)->order('auditing_time desc')->paginate(10);            //当月完成的子订单
        $list = $list->items();
        foreach ($list as $key=>$value){
            $list[$key]['auditing_time'] = date('Y-m-d H:i',$value['auditing_time']);
        }
        $lists = db('subOrder')->where($map)->select();
        $count = count($lists);
        $price = array_column($lists,'price');
        $money = round(array_sum($price),1);
        if($bill['status']<2){
            $status = '入账中';
        }else{
            $status = '已入账';
        }
        $bank = db('bankCard')->where('uid',UID)->find();
        if(!empty($bank)){
            $state = 1;
        }else{
            $state = 0;
        }
        $data = array(
            'time' => $param['time'],                                    //当前月
            'status' => $status,                               //账单状态
            'money' => $money,                                 //当月总收入
            'list' => $list,                                   //当月收入详细
            'count' => $count,                                  //总数
            'state' => $state
        );
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /***绑定银行卡获取验证码
     * @param $param
     * @return array
     *type 10 post
     */
    protected function cardcode($param){
        $member = db('member')->where('uid',UID)->find();
        if(phone_code($member['mobile'],3)=='提交成功'){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //发送短信成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//发送短信失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }



    /***识别银行卡
     * @param $param
     *card 银行卡号
     * code 短信验证码
     * type 9 post
     */
    protected function bank_test($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("card","code",),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $member = db('member')->where('uid',UID)->find();
        $info = db('smsCode')->where('mobile',$member['mobile'])->find();

         if($param['code']!= $info['code'] || $param['code'] == ''){
             $message = CodeError::CODEEOOR_NEWS_PARAM_NAME; //短信验证码错误
             $code = CodeError::CODEEOOR_NEWS_PARAM_CODE;
             $data = null;
        }else if(time()>($info['sendtime']+15*60)){
             $message = CodeError::CODEEOOR_NEWS_OVER_NAME; //短信验证码已过期
             $code = CodeError::CODEEOOR_NEWS_OVER_CODE;
             $data = null;
        }else{
            $model = new MemberModel();
            $info = db('bankCard')->where('uid',UID)->find();
            if(empty($info)) {
                $res = $model->add_card($param);    //添加
            }else{
                $res = $model->edit_card($param);    //编辑
            }
            if ($res) {
                $data = db('bankCard')->where('uid', UID)->find();
                $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
                $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
            }else {
                $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//提交失败-----> 一般不会出现
                $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
                $data = null;
            }
        }

        return ['code'=>$code,'data'=>$data,'message'=>$message];
    }


    /***绑定银行卡页面
     * @param $param
     * @return array
     * type 10 get
     */
    protected function getcard($param){
        $carde = db('bankCard')->where('uid',UID)->find();
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$carde,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }
	
	/***小V认证协议
     * @param $param
     * @return array
     * type 11 get
     */
	protected function agreement($param)
	{
		$list=db("smallv_agreement")->find();
		if(empty($list))
		{
			 return ['code'=>CodeError::CODEEOOR_COMMON_PARAM_CODE,'data'=>$list,'message'=>CodeError::CODEEOOR_COMMON_PARAM_NAME];
		}
		
		return $list;
	}



	protected function testAccount($param){
        $ip = GetIp();
        dump($ip);
        $info = weibo_login($param['apple'],$param['pwd'],$ip);
        dump($info);
        $sina = get_weibo_data($info['uid']);
        dump($sina);

//        $list = db('member')->field('uid,nickname')->select();
//        $count = 0;
//        foreach ($list as $key=>$value){
//
//            if($value['nickname']){
//                $map = [
//                    'nickname' => base64_encode($value['nickname']),
//                    ];
//                if(db('member')->where('uid',$value['uid'])->update($map)){
//                    $count +=1;
//                };
//            }
//        }
//        return $count;
    }


}