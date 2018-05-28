<?php
namespace app\index\controller;

use think\Controller;
use think\Session;
use think\Request;
use think\config;

class Pub extends Controller
{
         protected $beforeActionList = [
            //不执行前置操作的方法名 
            //'check_auth_user'=>['except' => 'index,logout'],
            'check_auth_user'
        ];

    protected function check_auth_user()
        {   
            //判断有没有登录 
            if(Session::get('ad_username') == ''){
                return $this->redirect('/index/login/login');
            }
            define('CONTROLLER_NAME',strtolower(Request::instance()->controller()));
            define('ACTION_NAME',Request::instance()->action());
            define('IS_POST',Request::instance()->isPost());
            $node=Session::get('_ACCESS_LIST');
            // 不需要验证的模块
            $nomodel=explode(',',strtolower(config('NOT_AUTH_MODULE')));
            if(!in_array(CONTROLLER_NAME,$nomodel) && Session::get('ADMIN_AUTH_KRY')==null){
                //还需排除不是超级管理员
                if(!in_array(CONTROLLER_NAME.'/'.ACTION_NAME,$node)){
                        return $this->error('你没有该权限',url('index/index'));
                }
            }
            $for=db('config')->select();
            foreach ($for as $k => $v) {
                config($v['name'],$v['value']);
            }

        }



}
