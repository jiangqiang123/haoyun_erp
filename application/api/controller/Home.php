<?php
/**
 * 父类API ++++++++++++++++++++++++++++++++++++++++
 * Created by PhpStorm.                           +
 * User: Administrator                            +
 * Date: 2017/7/29/029                            +
 * Time: 9:52                                     +
 * +++++++++++++++++++++++++++++++++++++++++++++++++
 */
namespace app\api\controller;
use think\Controller;
use CodeError\CodeError;
use think\Request;
use think\Session;
 /****2222*/
class Home extends  Controller
{
    /*
     * @前置操作
     */
    protected $beforeActionList = [
        'add_header',
        //不执行前置操作的方法名
       'check_auth_token'=>['except' => 'user,test,time,back_url,xcx_back_url,bind_back_url'],

    ];


    /*
     * @设置header头信息
     */
    function add_header() {
        /****跨域访问设置*****/
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Credentials: true');
//        header("Access-Control-Allow-Methods: *");
        header('Access-Control-Allow-Methods:POST,GET,PUT,DELETE');

        /****用户信息的token设置*****/
        header("Access-Control-Allow-Headers: content-type, token,vid");


    }
	
	

    /*******
     *
     *@检测用户携带的token
     *
     *****/
    function check_auth_token(){
    //未登录

     if(!array_key_exists("HTTP_TOKEN",$_SERVER) || !array_key_exists("HTTP_VID",$_SERVER)){
         $show_msg['code']=CodeError::CODEEOOR_NO_LOGIN_CODE;
         $show_msg['message']=CodeError::CODEEOOR_NO_LOGIN_NAME;
         echo json_encode($show_msg);die;
        }else{
         config("is_to_debug")?$this->is_to_debug():$this->is_not_debug();
        }
    }
    /***测试状态下的token算法**/
    function is_to_debug(){
       if(urldecode($_SERVER['HTTP_TOKEN'])!=config("is_to_debug_token")){
           $show_msg['code']=CodeError::CODEEOOR_TOKEN_ISUSER_CODE;
           $show_msg['message']=CodeError::CODEEOOR_TOKEN_ISUSER_NAME;
           echo json_encode($show_msg);die;
       }else{
           define('UID',$_SERVER['HTTP_VID']);
       }
    }
    /***正式线上的token算法**/
    function is_not_debug(){
         $token = urldecode($_SERVER['HTTP_TOKEN']);
         $uid =  authcode($token,'DECODE',"smallv",config("token_time"));
       if($uid!=$_SERVER['HTTP_VID']){
            $show_msg['code']=CodeError::CODEEOOR_TOKEN_ISUSER_CODE;
            $show_msg['message']=CodeError::CODEEOOR_TOKEN_ISUSER_NAME;
            echo json_encode($show_msg);die;
        }else{
           define('UID',$uid);
       }
  }

}