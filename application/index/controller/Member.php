<?php
namespace app\index\controller;

use think\Db;
use app\index\controller\Pub;
use app\index\model\MemberRealname;
use app\index\model\ActiveCode;
use app\index\model\AuthAdmin;
use app\index\model\AuthGroupAccess;
use app\index\model\GroupTeam;


class Member extends Pub
{
    /*
     * 用户列表
     */
    public function member_list()
    {
		
		$info=input();
        if(!empty($info['account_type'])){
            if($info['account_type']==1){
                $map['a.experience']=0;
            }else{
                $map['a.experience']=array('gt',0);
            }
        }
		
		if(!empty($info['type']) && !empty($info['val']))
		{
			if($info['type']==1)
			{
				//手机号搜索
				$map['a.mobile'] = $info['val'];
			}else if($info['type']==2)
			{
				//姓名搜索
				$map['c.realname']=array('like','%'.$info['val'].'%');
			}else if($info['type']==3)
			{
				//认证信息搜索
				if($info['val']==4)
				{
					$map['a.apply']=0;//未认证
				}else{
					$map['c.status'] = $info['val'];
				}
			}			
		}
		
		$auth_group=(int)config('AUTH_GROUP');
		if($auth_group==session("USER_AUTH_GROUP"))
		{
			//小组长
			$in[]=null;
			$in[]=session("USER_AUTH_KEY");
			$map['a.group']=array('in',$in);
			$leader=1;
			$admin=session("USER_AUTH_KEY");
			
			$team=db("group_team")->where(['group'=>$admin])->Field("id,name")->select();
			
			
		}elseif(!empty($info['group']))
		{
			$map['a.group'] = $info['group'];
			$team=db("group_team")->where(['group'=>$map['a.group']])->Field("id,name")->select();
		}
		
		if(!empty($info['team']))
		{
			$map['a.team'] = $info['team'];
		}
		
		
		
        $lists = db('member')->alias('a')
                ->join('v_auth_admin b','b.uid = a.group','LEFT')
				->join('v_member_realname c','c.uid = a.uid','LEFT')
				->join('v_group_team d','d.id=a.team','LEFT')
                ->where($map)
                ->field('a.*,b.username,c.realname,c.status as cstatus,d.name as dname')
                ->paginate(10,false,['query'=>$info]);
		
        $list = $lists->items();
        $page = $lists->render();
		$join = [
            ['v_auth_group_access b','a.uid=b.uid']
        ];
        $wheres['b.group_id']=(int)config('AUTH_GROUP');
        $group=db('auth_admin')->alias('a')->join($join)->where($wheres)->field('a.username,a.uid')->select();
        $count=$lists->total();
		
		$this->assign('team',$team);
		$this->assign('leader',$leader);
		$this->assign('admin',$admin);
		$this->assign('group',$group);
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('count',$count);
        $this->assign('map',$info);
        return $this->fetch();
    }
    /*
     * 查看用户详情
     */
    public function member_info(){
        $uid = input('uid');
        $info = db('member')
				->alias('a')
				->join('v_group_team b','b.id=a.team','LEFT')
				->join('v_auth_admin c','c.uid=a.group','LEFT')
				->join('v_member_realname d','d.uid=a.uid','LEFT')
				->where(['a.uid'=>$uid])
				->field('a.*,b.name as bname,c.username as cname,d.cardnum,d.realname')
				->find();
				// dump($info);die;
        $this->assign('info',$info);
        return $this->fetch();
    }


    /*
     *查看实名认证信息
     */
    public function member_real(){
        $uid = input('uid');
        $info = db('memberRealname')->where(['uid'=>$uid])->find();
        $this->assign('info',$info);
        return $this->fetch();
    }

    /*
     * 实名认证列表
     */
    public function realname(){

        $where['a.status']=1;
        $list=db('member_realname')->alias('a')->join('v_member b','b.uid = a.uid')->where($where)->select();
        $count=count($list);
        $this->assign('list',$list);
        $this->assign('count',$count);
        return $this->fetch();
    }

    /*
     *实名认证审核页面
     */
    public function do_realname(){
        if(IS_POST){
            $save=$_POST;
            if(!isset($save['status'])){
                $date['state']='n';
                $date['msg']='请选择审核结果';
                return json($date);die;
            }

            if($save['status']==3 && $save['lose']==''){
                $date['state']='n';
                $date['msg']='请填写失败原因';
                return json($date);die;
            }
            $a=new MemberRealname;
            if($a->do_realname($save))
            {
                $date['state']='y';
                $date['msg']='审核完成';
            }else{
                $date['state']='n';
                $date['msg']='审核失败';
            }
            return json($date);die;

        }

        $id = input('id');
        if(!$info = Db::name('memberRealname')->where(['id'=>$id])->find()){
            return $this->error('无效操作','','',1);
        }
        $this->assign('info',$info);
        return $this->fetch();
    }


    /*
    *激活码列表
    */
    public function code_list()
    {
        if(input('get.search') == 1){
            $where['uid']=0;
        }else if(input('get.search') == 2){
            $where['uid'] = array('neq',0);
        }else{
            $where='';
        }

        $code=db('active_code')->where($where)->order('addtime','desc')->paginate(10,false,['query'=>input('get.')]);
        $this->assign('where',input('get.search'));
        $this->assign('code',$code);
        return $this->fetch();

    }
	
	/*
    *激活码列表
    */
    public function code_excel()
    {
		$where['uid']=0;
        $code=db('active_code')->where($where)->order('id','asc')->Field('id,code')->select();
		$num=count($code);
		$this->assign('code',$code);
        $this->assign('num',$num);
        return $this->fetch();
    }


    /*
    *批量添加激活码
    */
    public function code_add()
    {
 
        if(IS_POST){
            $num=(int)input('post.num');
            if($num == 0){
                $num=1;
            }
            $x=1;
            while($x<=$num) {
				$code='';
                $code=GetfourStr();
                if(!db('active_code')->where(array('code'=>$code))->find()){
                    if(ActiveCode::create(array('code'=>$code),true)){
                        $x++;
                    }else{
                        $x;
                    }  
                }else{
                    $x;
                }
            }
            $date['state']='y';
            $date['msg']='成功';
            return json($date);die;
        }

        $join = [
            ['v_auth_group_access b','a.uid=b.uid']
        ];
        $where['b.group_id']=(int)config('AUTH_GROUP');
        $info=db('auth_admin')->alias('a')->join($join)->where($where)->select();
        $this->assign('info',$info);
       return $this->fetch(); 
    }

    #小组详情列表
    public function group_list()
    {
		
        //小组管理员
        $join = [
            ['v_auth_group_access b','a.uid=b.uid']
        ];
        $where['b.group_id']=(int)config('AUTH_GROUP');
        $admin=db('auth_admin')->alias('a')->join($join)->where($where)->select();
        
        $member=db('Member')->where('group','neq',0)->select();
        $uid = array_column($member,'group');	
		unset($member);
		$number=count($uid);
        $num = array_count_values($uid);
		unset($uid);
        foreach ($admin as $k => $v) {
            $admin[$k]['num']=$num[$v['uid']]+0;
			if($v['uid']==17)
			{
				// 测试组
				$number=$number-$num[$v['uid']];
			}
        }
		
		$this->assign('number',$number);
        $this->assign('admin',$admin);
        return $this->fetch(); 
    }
	
	#小组长编辑页面
	public function group_update()
	{
		if(IS_POST)
		{
			$input=input('post.');
			if($input['uid']=='' || $input['username']=='')
			{
				$date['state']='n';
                $date['msg']='账号不为空';
                return json($date);die;
			}
			
			if($input['is_pwd']==1){
				if($input['password'] != $input['pwd'])
				{
					$date['state']='n';
					$date['msg']='2次密码不一致';
					return json($date);die;
				}else{
					 $input['password']=xv_admin_md5($input['password']);
				}
			}else{
				unset($input['password']);
				unset($input['pwd']);
			}
			
			$map['uid']=array('neq',$input['uid']);
			$map['username']=$input['username'];
			if(db('auth_admin')->where($map)->find())
			{
				$date['state']='n';
				$date['msg']='修改的账号名称已存在';
				return json($date);die;
			}
			
			$yz=db('auth_group_access')->where(array('uid'=>$input['uid']))->find();
			if($yz['group_id'] != (int)config('AUTH_GROUP'))
			{
					$date['state']='n';
					$date['msg']='无效操作';
					return json($date);die;
			}
			
			$admin=new AuthAdmin;
			if($admin->allowField(true)->save($input,['uid' => $input['uid']]))
			{
				$date['state']='y';
                $date['msg']='修改成功';
                return json($date);die;
			}else{
				$date['state']='n';
                $date['msg']='修改失败';
                return json($date);die;
			}

		}

		$uid=input('uid');
		if($uid==''){
			return $this->error('无效操作','','',1);
		}
		
		$admin=db('auth_admin')->where(['uid'=>$uid])->find();
		
		if(empty($admin))
		{
			return $this->error('无效操作','','',1);
		}
		
		$this->assign('admin',$admin);
		return $this->fetch(); 
	}
	
	
	#小组长添加
	public function group_add()
	{
		if(IS_POST)
		{
			$info=input('post.');
			if($info['username']=='' )
			{
				$date['state']='n';
                $date['msg']='小组长账号不为空';
                return json($date);die;
			}
			
			if($info['password']=='' || $info['pwd']=='')
			{
				$date['state']='n';
                $date['msg']='密码不为空';
                return json($date);die;
			}
			
			if($info['password'] != $info['pwd'])
			{
				$date['state']='n';
                $date['msg']='两次密码不一致';
                return json($date);die;
			}else{
				$info['password']=xv_admin_md5($info['password']);
			}
			
			
			if(db('auth_admin')->where(['username'=>$info['username']])->find())
			{
				$date['state']='n';
                $date['msg']='该账号已存在';
                return json($date);die;
			}
			
			$admin=new AuthAdmin;
			$admin->startTrans();
			$res1=$admin->create($info,true);
			
			$group=array(
					'uid' => 		$res1->uid,
					'group_id' =>   config('AUTH_GROUP')
			);
			
			$res2=AuthGroupAccess::create($group);
			if($res1 && $res2)
			{
				$admin->commit();
				$date['state']='y';
				$date['msg']='添加成功';
				return $date;die;
			}else{
				$admin->rollback();
				$date['state']='n';
				$date['msg']='添加失败';
				return $date;die;
			}
			
			
			
		}
		
		return $this->fetch();
	}
	
	
	//小组长所管小组列表
	public function groups_team()
	{
		$auth_group=(int)config('AUTH_GROUP');
		if($auth_group==session("USER_AUTH_GROUP"))
		{
			$where['a.group']=session("USER_AUTH_KEY");
		}
		$list=db("group_team")
				->alias("a")
				->join('v_auth_admin b','b.uid = a.group')
				->where($where)
				->Field('a.*,b.username')
				->select();
		$this->assign('list',$list);
		return $this->fetch();
	}
	
	// 添加所管小组
	public function team_add()
	{
		if(IS_POST)
		{
			$info=input('post.');
			
			if($info['name']=='')
			{
				$date['state']='n';
				$date['msg']='小组名称不为空';
				return $date;die;
			}
			
			if($info['group']=='')
			{
				$date['state']='n';
				$date['msg']='请选择所属组长';
				return $date;die;
			}
			
			$old=GroupTeam::getByName($info['name']);
			if(!empty($old))
			{
				$date['state']='n';
				$date['msg']='该小组名称已存在';
				return $date;die;
			}
			
			if(GroupTeam::create($info,true))
			{
				$date['state']='y';
				$date['msg']='添加成功';
				return $date;die;
			}else{
				$date['state']='n';
				$date['msg']='添加失败';
				return $date;die;
			}
		}
		
		if(session("USER_AUTH_GROUP")==config('AUTH_GROUP'))
		{
			$list['type']=1;
			$list['group']=session("USER_AUTH_KEY");
		}else{
			$auth_group=(int)config('AUTH_GROUP');
			$list['type']=2;
			$list['group']=AuthGroupAccess::with('admins')->where(['group_id'=>$auth_group])->select();
		}
		$this->assign('list',$list);
		return $this->fetch();
	}
	
	
	
	
	

}