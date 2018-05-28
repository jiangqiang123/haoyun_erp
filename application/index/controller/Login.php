<?php
namespace app\index\controller;

use think\Controller;
use think\Session;
use think\Request;
use think\Cookie;

class Login extends Controller
{
	public function login()
	{
		if($_POST){
			// dump(input('username'));die;
			$where['username']=input('username');
			$where['password']=xv_admin_md5(input('password'));
			if($arr=db('auth_admin')->where($where)->find())
			{
				$group=db('authGroupAccess')->where(array('uid'=>$arr['uid']))->find()['group_id'];
				//登录成功
				Cookie::delete('jq_active');
				Cookie::delete('jq_url');
				Session::set('ad_username',$where['username']);
				Session::set('USER_AUTH_KEY',$arr['uid']);
				Session::set('USER_AUTH_GROUP',$group);
				if($arr['username'] == config('AUTH_SUPERADMIN')){
					//超级管理员
					Session::set('ADMIN_AUTH_KRY',true);
				}else{
					//不是超级管理员将数据遍历初来	
					$auth = new \admin\Auth();
					$node=$auth->getAuthList(Session::get('USER_AUTH_KEY'),1);
					Session::set('_ACCESS_LIST',$node);
				 }
				 // dump(Session::get('ad_username'));die;
				 
				return $this->success('登录成功',url('index/index'),'',1);
			}else{
				return $this->error('密码错误',url('login/login'),'',2);
			}
		}

		if(Session::get('ad_username') != ''){
            return $this->redirect('/index/index/index');
        }
		return $this->fetch();
	}


	public function logout()
	{
		db('auth_admin')->where(array('uid'=>Session::get('USER_AUTH_KEY')))->update(['last_time'=>time()]);
		Session::delete('ad_username');
		Session::delete('USER_AUTH_KEY');
		Session::delete('ADMIN_AUTH_KRY');
		Session::delete('_ACCESS_LIST');
		Session::delete('USER_AUTH_GROUP');
		Cookie::delete('jq_active');
		Cookie::delete('jq_url');

		return $this->success('退出成功',url('login/login'),'',1);
	}

}