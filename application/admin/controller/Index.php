<?php
namespace app\admin\controller;

use app\admin\controller\Pub;
use think\Session;
use think\Config;
use app\admin\model\Tasks;

class Index extends Pub
{
    public function index()
    {
       
 		if(Session::get('ad_username') ==  config('AUTH_SUPERADMIN')){
            //超级管理员
           	$rules=db('auth_rule')->where(array('status'=>1,'hide'=>1))->order('id asc')->select();
        }else{
         	$auth = new \admin\Auth();
    		$list=$auth->getGroups(Session::get('USER_AUTH_KEY'));
    		$where['id']=array('in',$list[0]['rules']);
    		$where['hide']=1;
    		$where['status']=1;
    		$rules=db('auth_rule')->where($where)->order('id asc')->select();
        }
		$member=Session::get('ad_username');
		$this->assign('member',$member);
        $this->assign('rules',$rules);
        return $this->fetch();
     }


    public function welcome()
    {
		
		// $a=task_send('olUOnwcRbOzTM1K_MEbQMKSr07wk','这里的内容是根据任务名称进行变动','http://www.baidu.com');
		// $a=db('smallv_account')->where(['is_del'=>2	])->count();
		// dump($a);die;
		
			// $lists=db('smallv_grade')->where('id','<',3)->order('id desc')->field("experience")->find();
			// if($lists['experience']==0)
			// {
				// $where['apply']=1;
			// }
			// dump($lists);die;
	
		
		$list=db('auth_admin')->where(array('uid'=>Session::get('USER_AUTH_KEY')))->field('last_time')->find();
		if($list['last_time']==''){
			$list['last_time']=time();
		}
		$this->assign('list',$list);
        return $this->fetch();
    }
}
