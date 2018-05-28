<?php
namespace app\index\controller;
/*小V管理控制器*/
use app\index\controller\Pub;
use think\Db;
use think\Request;
use app\index\model\SmallvGrade;
use app\index\model\SmallvAccount;
use app\index\model\Increase;
use app\index\model\Member;
use app\index\model\Message;

class Smallv extends Pub{
    #小v等级列表
    public function grade_list(){
        $list = Db::name('smallvGrade')->order('sort asc')->select();
        $this->assign('list',$list);
        return $this->fetch();
    }

    #小v等级修改
    public function grade_edit(){
        if(IS_POST){
            $info = $_POST;
            if($info['name']==''){
                $date['state']='n';
                $date['msg']='等级名称不为空';
                return json($date);die;
            }
            if($info['experience']==''){
                $date['state']='n';
                $date['msg']='等级经验额不为空';
                return json($date);die;
            }
            $file = request()->file('file-2');
            if(!empty($file)){
                $data = $file->move(ROOT_PATH . 'public' . DS . 'uploads'.DS.'member');
                $info['sv_icon'] = $data->getSaveName();
            }
            $grade = new SmallvGrade();
            $res = $grade->editGrade($info);
            if($res){
                $date['state']='y';
                $date['msg']='修改成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='修改失败';
                return json($date);die;
            }
        }else{
            $id= (int)input('id');
            $info =db('smallvGrade')->where('id',$id)->find();
            $this->assign('info',$info);
            return $this->fetch("grade_add");
        }

    }

    #小v等级添加
    public function grade_add(){
        if(IS_POST){
            $info = $_POST;
            if($info['name']==''){
                $date['state']='n';
                $date['msg']='等级名称不为空';
                return json($date);die;
            }
            if($info['experience']==''){
                $date['state']='n';
                $date['msg']='等级经验额不为空';
                return json($date);die;
            }
            $file = request()->file('file-2');
            if(!empty($file)){
                $data = $file->move(ROOT_PATH . 'public' . DS . 'uploads'.DS.'member');
                if($data){
                    $info['sv_icon'] = $data->getSaveName();
                }else {
                    echo $data->getError();
                }
            }
            $grade = new SmallvGrade();
            $res = $grade->addGrade($info);
            if($res){
                $date['state']='y';
                $date['msg']='添加成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='添加失败';
                return json($date);die;
            }
        }else{

            return $this->fetch();
        }
    }

    #小v等级删除
    public function grade_del()
    {
        $id = (int)input('id');
        if ( empty($id) ) {
            $data['msg'] = '请选择要操作的数据';
            $data['state'] = 0;
            echo json_encode($data);die;
        }
        $info = db('smallvGrade')->where(['id'=>$id])->find();
        if($info['status']==1){
            $data['msg'] = '该等级为启用状态,请先禁用!';
            $data['state'] = 1;
            echo json_encode($data);die;
        }
        if(Db::name('smallvGrade')->delete($id)){
            $date['state']='y';
            $date['msg']='删除成功';
            return json($date);die;
        }else{
            $date['state']='n';
            $date['msg']='删除失败';
            return json($date);die;
        }

    }

    #小v分配账号列表
    public function account_list()
    {
		$map=input();
		
		if(!empty($map['type']))
		{
			
			if($map['type']==1)
			{
				$where['c.realname']=array('like','%'.$map['val'].'%');
				$maps['c.realname']=array('like','%'.$map['val'].'%');
			}else if($map['type']==2)
			{
				$if['is_del']=1;
				$if['account']=array('like','%'.$map['val'].'%');
				$a=Db::name('smallvAccount')->where($if)->column('uid');
				$a=array_unique($a);
				$where['a.uid']=array('in',$a);
				$maps['a.uid']=array('in',$a);
			}else if($map['type']==3)
			{
				$where['a.mobile']=$map['val'];
				$maps['a.mobile']=$map['val'];
			}
		}
		
		$where['a.apply']=2;
		if(session("USER_AUTH_GROUP")==config('AUTH_GROUP'))
		{
			// 小组长
			$where['a.group']=session("USER_AUTH_KEY");
			$leader=1;
			$admin=session("USER_AUTH_KEY");	
			$team=db("group_team")->where(['group'=>$admin])->Field("id,name")->select();
			$maps['a.group']=session("USER_AUTH_KEY");
		}elseif(!empty($map['group'])){
			$where['a.group']=$map['group'];
			$team=db("group_team")->where(['group'=>$map['group']])->Field("id,name")->select();
			$maps['a.group']=$map['group'];
		}
		
		if(!empty($map['team']))
		{
			$where['a.team']=$map['team'];
			$maps['a.team']=$map['team'];
		}
		
		$maps['is_del']=1;
		$join = [
            ['v_auth_group_access b','a.uid=b.uid']
        ];
        $wheres['b.group_id']=(int)config('AUTH_GROUP');
        $group=db('auth_admin')->alias('a')->join($join)->where($wheres)->field('a.username,a.uid')->select();
		
		$number=Db::name('member')
			  ->alias('a')
			  ->join('v_group_team b','b.id=a.team','LEFT')
			  ->join('v_member_realname c','c.uid=a.uid')
			  ->join('v_smallv_account d','d.uid=a.uid')
			  ->where($maps)
			  ->count();
		$lists=Db::name('member')
			  ->alias('a')
			  ->join('v_group_team b','b.id=a.team','LEFT')
			  ->join('v_member_realname c','c.uid=a.uid')
			  ->where($where)
			  ->field('a.*,b.name as bname,c.realname')
			  ->paginate(10,false,['query'=>$map]);
		$list=$lists->items();
		foreach($list as $k => $v)
		{
			$list[$k]['account']=Db::name('smallvAccount')
			     ->where(['is_del'=>1,'uid'=>$v['uid']])
				 ->count();
		}

		$page=$lists->render();
		$this->assign('number',$number);
		$this->assign('map',$map);
		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->assign('team',$team);
		$this->assign('leader',$leader);
		$this->assign('admin',$admin);
		$this->assign('group',$group);
		return $this->fetch();
    }
	
	
	#账号媒体信息详情
	public function account_xq()
	{
		$uid=input("uid");
		$where=array(
				'a.is_del' => 1,
				'uid'      => $uid
			);
		 $list = Db::name('smallvAccount')
				         ->alias('a')
						 ->join('v_task_type b','b.id = a.type')
						 ->field('a.*,b.name')
						 ->where($where)
						 ->order('addtime desc')
						 ->select();
		$this->assign('list',$list);
		return $this->fetch();
	}

    #小v分配账号添加
    public function account_add(){
        if(IS_POST){
            $info = $_POST;
            if($info['apple']==''){
                $date['state']='n';
                $date['msg']='账号不为空';
                return json($date);die;
            }
            if($info['pwd']==''){
                $date['state']='n';
                $date['msg']='密码不为空';
                return json($date);die;
            }
            if($info['enable']==''){
                $date['state']='n';
                $date['msg']='请选择启用状态';
                return json($date);die;
            }

            if(db('smallvAccount')->where(array('apple'=>$info['apple']))->find()){
                $date['state']='n';
                $date['msg']='该账号已存在';
                return json($date);die;
            }

            $account = new SmallvAccount();
            $res = $account->addAccount($info);
            if($res){
                $date['state']='y';
                $date['msg']='添加成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='添加失败';
                return json($date);die;
            }
        }else{
            $data = db('taskType')->where(['status'=>1,'type'=>1])->select();
            $this->assign('data',$data);
            return $this->fetch();
        }
    }

    #小v分配账号编辑
    public function account_edit(){
        if(IS_POST){
            $info = $_POST;
			// dump($info);die;
            if($info['pwd']==''){
                $date['state']='n';
                $date['msg']='密码不为空';
                return json($date);die;
            }
            
            $account = new SmallvAccount();
            $res = $account->editAccount($info);
            if($res){
                $date['state']='y';
                $date['msg']='修改成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='修改失败';
                return json($date);die;
            }
        }else{
            $id= (int)input('id');
            $info =db('smallvAccount')->where('id',$id)->find();
            $data = db('taskType')->where(['status'=>1])->select();
            $this->assign('data',$data);
            $this->assign('info',$info);
            return $this->fetch("account_add");
        }
    }

    #小v分配账号删除
    public function account_del(){
        $id = (int)input('id');
        if ( empty($id) ) {
            $date['msg'] = '请选择要操作的数据';
            $date['state'] = 0;
            echo json_encode($date);die;
        }
        $info = db('smallvAccount')->where(['id'=>$id])->find();
        if($info['status']==2){
            $date['msg'] = '该账号为已分配状态,暂不能删除!';
            $date['state'] = 1;
            echo json_encode($date);die;
        }
        if($info['enable']==1){
            $date['msg'] = '该账号为启用状态,暂不能删除!';
            $date['state'] = 2;
            echo json_encode($date);die;
        }
        if(Db::name('smallvAccount')->delete($id)){
            $date['state']='y';
            $date['msg']='删除成功';
            return json($date);die;
        }else{
            $date['state']='n';
            $date['msg']='删除失败';
            return json($date);die;
        }
    }



    #检测微博账号账号密码是否正确
    public function account_check(){
        $info=input('post.');
        $res=weibo_is_true($info['apply'],$info['pwd']);
        if($res['retcode']==0){
            //正确
            $date['state']='y';
            $date['msg']=$res['nick'];
        }else{
            $date['state']='n';
            $date['msg']=$res['reason'];
        }
        return json($date);die;
    }


    #小V审核列表
    // public function sv_auditing_list()
    // {

    // }

    #小V成长体系列表
    public function grow_list()
    {

        $list=db('increase')->select();
        $this->assign('list',$list);
        return $this->fetch();
    } 

    #小v成长体系的添加
    public function grow_add()
    {
        if(IS_POST){
            $info=input('post.');
            if($info['type']=='' || $info['experience']=='' || $info['gold'] == '')
            {
                $date['state'] = 'n';
                $date['msg'] = '必填项不为空';
                return json($date);die;
            } 
            if(Increase::create($info,true))
            {
                $date['state'] = 'y';
                $date['msg'] = '添加成功';
                return json($date);die;
            }else{
                $date['state'] = 'n';
                $date['msg'] = '添加失败';
                return json($date);die;
            }
        }  
        return $this->fetch();
    }

    #小v成长体系的修改
    public  function grow_edit()
    {
        if(IS_POST)
        {
            $update=input('post.');
            if(Increase::where(array('id'=>$update['id']))->update($update))
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

        $id=input('id');
        $info=db('increase')->where(array('id'=>$id))->find();
        $this->assign('info',$info);
        return $this->fetch("grow_add");
    }


     #小V申请审核列表  对用户所有的子订单进行审核
    public function smallv_aud_list()
    {
        $where['apply']=1;
        $where['experience']=0;
        $member=db('member')->where($where)->select();
        $this->assign('member',$member);
        return $this->fetch();
    }


    #小v的申请审核
    public function smallv_auditing()
    {

        if(IS_POST)
        {
            $info=input('post.');
            // $array=array();
            // if(!empty($info['oid']))
            // {
                // foreach ($info['oid'] as $k => $v) {
                    // if($info['lose'.$v]==''){
                        // $date['state']='n';
                        // $date['msg']='新手任务失败原因未填写';
                        // return json($date);die;
                    // }
					// 失败的任务
                    // $array[]=array('id'=>$v,'lose'=>$info['lose'.$v],'status'=>4);
                // }
            // }

            if($info['real_lose']=='' && $info['status']==3){
                $date['state']='n';
                $date['msg']='失败原因未填写';
                return json($date);die;

            }
			
			if($info['status']==2 && $info['group']=='')
			{
				$date['state']='n';
                $date['msg']='请选择所属小组长';
                return json($date);die;
			}
			
			if($info['status']==2 && $info['team']=='')
			{
				$date['state']='n';
                $date['msg']='请选择所属小组';
                return json($date);die;
			}
			

            $model=new Member;

           
            if($info['status']==3)
            {
                //审核失败    

                $res=$model->samllv_aud_false($info);
                if($res['state']=='y'){
                    $action='小V审核失败，失败原因请查看详情';
                    $add=array('to_user_id'=>$info['uid'],'type'=>3,'reason'=>$action,'status'=>1);
                    Message::create($add,true);  
                }
                return json($res);die;
            }else{
                //成功
                $res=$model->smallv_aud_true($info);
                if($res['state']=='y'){
                    $openid=db('member')->where(array('uid'=>$info['uid']))->find()['openid'];
                    $type = 'levelup_success';
                    $par = array(
                            'first'=>'恭喜小V审核通过',
                            'keyword1'=>'V0',
                            'keyword2'=>'V1',
                            'remark' => '如有问题，请联系客服',
                        );
                    send($openid,$type,$par);
                }
                return json($res);die;
            }
            
        }

		//小v任务的审核
        // $where['to_user_id']=input('id');
        // $where['is_novice']=1;
		
        // $order=db('order')->where($where)->select();

        // foreach ($order as $k => $v) {
            // $order[$k]['details']=unserialize($v['details']);
            // $order[$k]['result_pic']=unserialize($v['result_pic']);    
        // }
		
        $real=db('member_realname')->where(array('uid'=>input('id'),'status'=>1))->find();
		
		// 分组信息
		$join = [
            ['v_auth_group_access b','a.uid=b.uid']
        ];
        $where['b.group_id']=(int)config('AUTH_GROUP');
        $info=db('auth_admin')->alias('a')->join($join)->where($where)->field("a.username,a.uid")->select();
		$this->assign('info',$info);
        $this->assign('real',$real);
        // $this->assign('order',$order);
        return $this->fetch();

    }
	
	#小V认证协议
	public function agreement()
	{
		if(IS_POST)
		{
			$info=input('post.');
			
			if($info['content']=='' ){
                $date['state']='n';
                $date['msg']='认证协议未填写';
                return json($date);die;
            }
			
			
			$res=Db::name('smallv_agreement')
			     ->where('id', 1)
                 ->update($info);
			if($res)
			{
				$date['state']='y';
				$date['msg']='保存成功';
			}else{
				$date['state']='n';
				$date['msg']='保存失败';
			}

			return json($date);die;
			
		}
		
		
		$list=Db::name('smallv_agreement')->find();
		$this->assign('list',$list);
		return $this->fetch();
	}
	
	
	#小V解封列表
	public function unblocking_list()
	{
		
		// 被封账号
		$where=array(
					'a.apply'      => 2,
					'a.mem_status' => ['neq',1],
			   );
			   
		if(session("USER_AUTH_GROUP")==config('AUTH_GROUP'))
		{
			//小组长
			$where['a.group']=session("USER_AUTH_KEY");
		}
		
		$member=Db::name('member')
			->alias("a")
			->join('v_member_realname b','b.uid=a.uid')
			->where($where)->Field("a.apply,a.mem_status,a.seal_time,a.uid,a.mobile,b.realname")
			->select();
		
		foreach($member as $k => $v)
		{
			$start=strtotime(date('Y-m-01 00:00:00'));
			$end = strtotime(date('Y-m-d H:i:s'));
			$map['auditing_time'] = array('between',array($start,$end));
			$map['to_user_id']=$v['uid'];
			$map['status']=array('in','4,6');
			$num=array();
			$num=db('sub_order')->where($map)->field('status')->column('status');
			$num=array_count_values($num);
			$member[$k]['single']=$num[6]+0;
			$member[$k]['fail']=$num[4]+0;
		}
		$this->assign('list',$member);
		return $this->fetch();
	}
	
	
	#小V解封操作
	public function unblocking()
	{
		if(IS_POST)
		{
			
			$info=input('post.');
			$list=Member::get($info['uid']);
			if($list->mem_status==1)
			{
				$data['state']='n';
				$data['msg']='无效操作';
				return json($date);die;
			}
			
			$list->mem_status=1;
			$list->seal_time=null;
			
			if($list->save())
			{
				$date['state']='y';
				$date['msg']='保存成功';
			}else{
				$date['state']='n';
				$date['msg']='保存失败';
			}
			
			return json($date);die;
		
			
		}
		
		
		$list=db('sub_order')->where(['to_user_id'=>36,'status'=>array('in','4,6')])->select();
		// dump($list);die;
		
		
	}
	
	//根据小组长id获取小组
	public function obtain_team()
	{
		if(IS_POST)
		{
			$group=input('post.group');
			$list=db("group_team")->where(['group'=>$group])->select();
			
			if(empty($list))
			{
				$date['state']='n';
				$date['msg']='该小组长未添加小组';
			}else{
				$date['state']='y';
				$date['msg']=$list;
			}
			
			return json($date);die;
		}
	}




}