<?php
namespace app\index\controller;
/**********任务控制器********/
use app\index\controller\Pub;
use app\index\model\TaskType;
use app\index\model\Tasks;
use app\index\model\Category;
use app\index\model\AuthGroupAccess;
use think\Db;
use think\Request;
//use think\Session;


class Task extends Pub
{
    #任务类型列表
    public function type()
    {
        $list=collection(TaskType::select(function($query){
                                $query->order('sort', 'asc');}))
                ->toArray();
        int_to_string($list,array(
                'status'=>array(1=>'启用',0=>'禁用'),
            ));
        $this->assign('list',$list);
        return $this->fetch(); 
    }


    #任务类型的添加
    public function type_add()
    {
        if(IS_POST){
            $info=$_POST;
            if(isset($info['pid'])){
                $info['pid']=implode(',', $info['pid']);
            }else{
                $date['state']='n';
                $date['msg']='任务类别不为空';
                return json($date);die;
            }

            if($info['name']==''){
                $date['state']='n';
                $date['msg']='任务类型名称不为空';
                return json($date);die;
            }

            //图标
            $file = request()->file('file-2');
            
            if(!empty($file)){
                $data = $file->move(ROOT_PATH . 'public' . DS . 'uploads'.DS.'type');
                if($data){
                    $info['tp_icon'] = $data->getSaveName();
                }else {
                    $date['state']='n';
                    $date['msg']=$data->getError();
                    return json($date);die;
                }
            }else{
                $date['state']='n';
                $date['msg']='图标不为空';
                return json($date);die;
            }

            $type = new TaskType();
            $res = $type->addType($info);
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

            $category=db('category')->select();
            $this->assign('category',$category);
            return $this->fetch();
        }
    }


    #任务类型的修改
    public function type_edit()
    {
        if(IS_POST){
            $info = $_POST;
            if($info['name']=='')
            {
                $date['state']='n';
                $date['msg']='任务类型名称不为空';
                return json($date);die;
            }

            if(isset($info['pid']))
            {
                $info['pid']=implode(',', $info['pid']);
            }else{
                $date['state']='n';
                $date['msg']='任务类别不为空';
                return json($date);die;
            }

            $file = request()->file('file-2');
            if(!empty($file)){
                $data = $file->move(ROOT_PATH . 'public' . DS . 'uploads'.DS.'type');
                if($data){
                    $info['tp_icon'] = $data->getSaveName();
                }else {
                    $date['state']='n';
                    $date['msg']=$data->getError();
                    return json($date);die;
                }
            }

            $type = new TaskType();
            $res = $type->editType($info);
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
            $info =db('taskType')->where('id',$id)->find();
            $info['pid']=explode(',', $info['pid']);
            //任务的类别  点赞 转发 评论
            $category=db('category')->select();
            foreach ($category as $k => $v) {
                if(in_array($v['id'],$info['pid'])){
                    $category[$k]['checked']=1;
                }else{
                    $category[$k]['checked']=0;
                }
            }
            // dump($info);die;
            $this->assign('category',$category);
            $this->assign('info',$info);
            return $this->fetch("type_add");
        }

    }

    #任务类型的删除
    public function type_del()
    {
        $id = (int)input('id');
        if ( empty($id) ) {
            $data['msg'] = '请选择要操作的数据';
            $data['state'] = 0;
            echo json_encode($data);die;
        }
        $info = db('taskType')->where(['id'=>$id])->find();
        if($info['status']==1){
            $data['msg'] = '该任务类型为启用状态,请先禁用!';
            $data['state'] = 1;
            echo json_encode($data);die;
        }
        if(Db::name('taskType')->delete($id)){
            $date['state']='y';
            $date['msg']='删除成功';
            return json($date);die;
        }else{
            $date['state']='n';
            $date['msg']='删除失败';
            return json($date);die;
        }
    }

    #任务列表
    public function lists()
    {
        if(input('get.novice')==1){
            $where['a.novice']=1;
        }else{
            $where['a.novice']=2;
        }
		
		if(session("USER_AUTH_GROUP")==config('AUTH_GROUP'))
		{
			//小组长
			$arr[]=0;
			$arr[]=session("USER_AUTH_KEY");
			$where['a.uid']=array('in',$arr);
		}
		
		
		
        //搜索条件
        if(input('get.search') != null){
            $where['a.name']=array('like','%'.input('get.search').'%');
        }
		
        //if(input(''))
       $list=db('tasks')->alias('a')
             ->join('v_task_type b','b.id = a.type')
			 ->join('v_auth_admin c','c.uid=a.uid','LEFT')
             ->field('a.*,b.name as bname,c.username as cname')
             ->where($where)
             ->order('a.addtime','desc')
             ->paginate(8,false,['query'=>input('get.')]);
        $page = $list->render();
        $list=$list->items();
        foreach ($list as $k => $v) {
			   $num=array();
			   $num=Db::name('sub_order')
				  ->alias('a')
				  ->where(['a.tid'=>$v['id'],'a.status'=>['not in','0,5']])
				  ->column('a.status');
				  // ->count();
				$list[$k]['s_num']=count($num);
				$num=array_count_values($num);
				$list[$k]['complete']=$num[3]+0;
				$list[$k]['shen']=$num[2]+0;
               $list[$k]['name']=mb_strcut($v['name'],0,50,'utf-8');
               $map['id']=array('in',$v['category']);
               $cate=db('category')->field('name')->where($map)->select();
               $a = array_column($cate,'name');
               $cate=implode('、', $a);
               $list[$k]['category']=$cate;

               if($v['novice']==2){
                $list[$k]['nature']= db('smallv_grade')->where(array('id'=>$v['nature']))->field('name')->find()['name'];
               }
           } 
           // dump($list);die;

        if($where!=''){
           $this->assign('where',input('get.')); 
        }
        $this->assign('page',$page);
        $this->assign('novice',$where['a.novice']);
        $this->assign('list',$list);
        return $this->fetch(); 
    }


    #任务的发布
    public function release()
    {
        if(IS_POST){
            $info=$_POST;
            if($info['name']=='')
            {
                $date['state']='n';
                $date['msg']='任务名不为空';
                return json($date);die;
            }
			
			if($info['uid']=='')
			{
				$date['state']='n';
                $date['msg']='请选择小组';
                return json($date);die;
			}

            if($info['price']=='' && $info['novice']==2)
            {
                $date['state']='n';
                $date['msg']='任务单价不为空';
                return json($date);die;
            }
			
			if($info['result_type'] == '')
			{
				$date['state']='n';
                $date['msg']='任务回执选项不为空';
                return json($date);die;
				
			}else{
				
				$info['result_type']=implode(',',$info['result_type']);
			}

            if($info['number']=='' && $info['novice']==2)
            {
                $date['state']='n';
                $date['msg']='任务数量不为空';
                return json($date);die;
            }


            if($info['execute_time'] == '' && $info['novice']==2)
            {
                $date['state']='n';
                $date['msg']='执行时间不为空';
                return json($date);die;
            }

            if(!isset($info['category']))
            {
                $date['state']='n';
                $date['msg']='任务类别不为空';
                return json($date);die;
            }else{
				
				$info['category']=implode(',', $info['category']);
			}

            if($info['type'] == '')
            {
                $date['state']='n';
                $date['msg']='类型不为空';
                return json($date);die;
            }
			
			
    		$info['addtime']=time();

            //任务执行时间
            if($info['time']=='i')
            {
                //分钟
                $info['execute_time']=$info['execute_time']*60;
            }else if($info['time']=='h'){
                //小时
                $info['execute_time']=$info['execute_time']*60*60;
            }else if($info['time']=='d'){
                //天
                $info['execute_time']=$info['execute_time']*60*60*24;
            }
            unset($info['time']);

            //任务在先时间
            if($info['active_time'] !== '')
            {
                if($info['time_active']=='i')
                {
                    //分钟
                    $info['active_time']=$info['active_time']*60+time();
                }else if($info['time_active']=='h'){
                    //小时
                    $info['active_time']=$info['active_time']*60*60+time();
                }else if($info['time_active']=='d'){
                    //天
                    $info['active_time']=$info['active_time']*60*60*24+time();
                }
            }
            unset($info['time_active']);

           

            //任务内容
            
            $info['content']='';
            foreach ($info as $key => $val) 
            {
                if(substr($key,0,13)=='editorContact')
                {
                    if($val !='')
                    {
                        $info['content'][]=$val;
                    }
                    unset($info[$key]);
                }
            }

            $info['order_num']=makeOrderSn(0);
			
			if($info['content'] != '')
            {
                $info['content']=serialize($info['content']);
            }
			
			if($info['content']=='' && $info['task_link']=='')
			{
				$date['state']='n';
                $date['msg']='请填写任务链接或任务内容';
                return json($date);die;
			}
			
			
            $model=new Tasks;
            if($model->order_add($info))
            {
                $date['state']='y';
                $date['msg']='发布任务成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='发布任务失败';
                return json($date);die;
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

        $type=db('task_type')->where(['status'=>1])->order('sort', 'asc')->select();
        $category=db('category')->where(['status'=>1])->order('sort','asc')->select();

        if(input('get.novice')==2){
            $nature=db('smallv_grade')->order('experience asc')->select();
            $this->assign('nature',$nature);
        }
        
        $this->assign('category',$category);
        $this->assign('type',$type);
		$this->assign('list',$list);
        return $this->fetch(); 
    }
	
	
	//将任务信息发送消息到微信   id  任务id
	public function message_go()
	{
		if(IS_POST)
		{
			$info=input("post.");
			$task=Tasks::get($info['id']);
			if($task->status!= 2 || $task->msg==1)
			{	
				$data['state']='n';
				$data['msg']='该任务不能发送信息';
				return json($date);die;
			}
			
			$task->msg=1;
			if($task->save())
			{
				$grade=Db::name('smallv_grade')->where('id','<',$task->nature)->order('id desc')->field("experience")->find();
				$type=Db::name('task_type')->where(['id'=>$task->type])->find();
				$types=$type['name'].'任务';
				$where['apply']=2;
				$where['mem_status']=1;
				if($grade['experience'] != 0){
					//不是V1任务
					$where['experience']=array('gt',$grade['experience']);						
				}
				
				if($task->uid!=0)
				{
					$where['group']=$task->uid;
				}
				$member=Db::name("member")->where($where)->field("openid")->select();
				foreach($member as $k => $v)
				{
                    if($v['openid'] != ''){
					   task_send($v['openid'],$task->name,$types);
                    }
				}
				
				$date['state']='y';
                $date['msg']='发送成功';
                return json($date);die;
				
			}else
			{
				$date['state']='n';
                $date['msg']='发送失败';
                return json($date);die;
			}
			
		}
	}



    #编辑器接收图片 
    public function uploadss() {
        header("Content-Type: text/html; charset=utf-8");
       
        $action = $_GET['action'];
        if('uploadimage' == $action) { //上传图片
        $file = request()->file("upfile"); 
        // 移动到框架应用根目录/public/uploads/ 目录下   限制为2M图片
        $info = $file->validate(['size'=>1024*2*1000,'ext'=>'jpg,png,gif'
            ])->move(ROOT_PATH . 'public' . DS . 'uploads'. DS . 'editor');
        $pic=$info->getInfo();
            if($info){
                    // 成功上传后 获取上传信息
                    $arr = array(
                        'state'=>'SUCCESS',
                        'url'=>'http://'.$_SERVER['SERVER_NAME'].'/uploads/editor/'.$info->getSaveName(),
                        'title'=>$info->getSaveName(),
                        'original'=>$pic['name'],
                        'type'=>$info->getExtension(),
                        'size'=>$pic['size'],
                    );

                    $result = json_encode($arr);
                }else{
                    // 上传失败获取错误信息
                    echo $file->getError();
                }
        }
        /* 输出结果 */

        echo $result;

    }


    #查看任务详情
    public function task_details(){
        $id=(int)input('get.id');

        if(!$list=db('tasks')->where('id','eq',$id)->find()){
            $this->error('该任务不存在',url('task/lists'),'',1);
        }

        if($list['content'] !=''){
            $list['content']=unserialize($list['content']);
        }
        $this->assign('list',$list);
        return $this->fetch(); 
    }


    #任务类别列表
    public function category(){
        $list=db('category')->select();
        $this->assign('list',$list);
        return $this->fetch(); 
    }


    #任务类别添加
    public function category_add(){
        if(IS_POST){ 
            $info=input('post.');
            $file = request()->file('file-2');
            if(!empty($file)){
                $data = $file->move(ROOT_PATH . 'public' . DS . 'uploads'.DS.'category');
                $info['icon'] = $data->getSaveName();
            }else{
                $date['state']='n';
                $date['msg']='图标不为空';
                return json($date);die;
            } 

             if(Category::create($info,true)){
                $date['state']='y';
                $date['msg']='添加成功';
                return json($date);die;
             }else{
                $date['state']='n';
                $date['msg']='添加失败';
                return json($date);die;
             }  
        }

        return $this->fetch(); 
    }


    #任务类别修改
    public function category_edit(){
        if(IS_POST){
            $info=input('post.');
            $file = request()->file('file-2');
            if(!empty($file)){
                $data = $file->move(ROOT_PATH . 'public' . DS . 'uploads'.DS.'category');
                if($data){
                    $info['icon'] = $data->getSaveName();
                }else {
                    $date['state']='n';
                    $date['msg']=$data->getError();
                    return json($date);die;
                }
            }

            if(Category::where(array('id'=>$info['id']))->update($info)){
                $date['state']='y';
                $date['msg']='修改成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='修改失败';
                return json($date);die;
            }
        }
        $id=(int)input('id');
        if(!$info=Category::get($id)){
            $thid->error('无效操作');
        }

        $this->assign('info',$info);
        $this->assign('type',$type);
        return $this->fetch("category_add"); 
    }

    #任务类别的删除
    public function category_del()
    {
        $id = (int)input('id');
        if ( empty($id) ) {
            $data['msg'] = '请选择要操作的数据';
            $data['state'] = 0;
            echo json_encode($data);die;
        }
        $info = db('category')->where(['id'=>$id])->find();
        if($info['status']==1){
            $data['msg'] = '该任务类型为启用状态,请先禁用!';
            $data['state'] = 'n';
            echo json_encode($data);die;
        }
        if(Db::name('category')->delete($id)){
            $date['state']='y';
            $date['msg']='删除成功';
            return json($date);die;
        }else{
            $date['state']='n';
            $date['msg']='删除失败';
            return json($date);die;
        }
    }


    #在任务中的时候  手动停止任务  status=2时候  改为3
    public function stop_task()
    {
        if(IS_POST)
        {
            $info=input("post.");
            $order=Tasks::get($info['id']);
            if($order->status != 2){
                $date['state']='n';
                $date['msg']='无效操作,请刷新后重试';
                return json($date);die; 
            }
            $order->status=3;
            if($order->save()){
                $date['state']='y';
                $date['msg']='任务已下架';
                return json($date);die; 
            }else{
                $date['state']='n';
                $date['msg']='操作失败';
                return json($date);die; 
            }
            // dump($order);die;

        }

        return $this->error('无效操作');
    }



    #任务的编辑 在3或者4的状态下
    public function task_edit()
    {

        if(IS_POST){
            $info=input("post.");
            $task=Tasks::get($info['id']);
            $time=$task['active_time'];
            $num=$task['number']-$task['amount'];
            if($time<= time() &&  $info['active_time']=='')
            {
                $date['state']='n';
                $date['msg']='任务在线时间已过，无法重新发布';
                return json($date);die; 
            }

            if($num<=0 && $info['number']=='')
            {
                $date['state']='n';
                $date['msg']='任务剩余量为0,无法重新发发布';
                return json($date);die;
            }

            if($info['active_time'] != '')
            {
                $update['addtime']=time();

                if($info['time_active']=='i')
                {
                    //分钟
                    $update['active_time']=$info['active_time']*60+$update['addtime'];
                }else if($info['time_active']=='h'){
                    //小时
                    $update['active_time']=$info['active_time']*60*60+$update['addtime'];
                }else if($info['time_active']=='d'){
                    //天
                    $update['active_time']=$info['active_time']*60*60*24+$update['addtime'];
                }

                
            
            }


            if($info['number'] != '')
            {
                if($info['is_add']==1)
                {
                    $update['number']=$task['number']+$info['number'];
                }else if($num-$info['number'] > 0 ){
                    $update['number']=$task['number']-$info['number'];
                }else{
                    $date['state']='n';
                    $date['msg']='设置的任务减少量过大';
                    return json($date);die; 
                }
            }
            $update['status']=2;
            if(Tasks::where('id',$info['id'])->update($update))
            {
                $date['state']='y';
                $date['msg']='重新发布成功';
                return json($date);die; 
            }else{
                $date['state']='n';
                $date['msg']='重新发布失败';
                return json($date);die; 
            }
        }
        $id=(int)input('get.id');

        if(!$list=db('tasks')->where('id','eq',$id)->find()){
            $this->error('该任务不存在',url('task/lists'),'',1);
        }

        if($list['content'] !=''){
            $list['content']=unserialize($list['content']);
        }

        $where['id']=array('in',$list['category']);
        $category=db('category')->where($where)->select();
        $a = array_column($category,'name');
        $cate=implode('、', $a);
        $list['category']=$cate;

        $list['nature']=db('smallv_grade')->where('id',$list['nature'])->field('name')->find()['name'];

        $list['type']=db('task_type')->where('id',$list['type'])->field('name')->find()['name'];
        //dump($list);die;
        $this->assign('list',$list);
        return $this->fetch(); 
    }
	
	
	#任务统计
	public function  task_count()
	{
		
		$info=input();
		
		if($info['type']!='')
		{
			$end=time();
			if($info['type']==1)
			{
				//日统计
				$star=strtotime(date("Y-m-d 00:00:00"));
				$where['b.addtime']=array('BETWEEN',[$star,$end]);
				$num['type']=1;
				
			}else if($info['type']==2)
			{
				//月统
				$star=strtotime(date("Y-1-1 00:00:00"));
				$where['b.addtime']=array('BETWEEN',[$star,$end]);
				$num['type']=2;
		
			}else if($info['type']==4 && $info['time']!='')
			{
				//指定时间
				$star=strtotime(date($info['time']." 00:00:00"));
				$end=strtotime(date($info['time']." 23:59:59"));
				$where['b.addtime']=array('BETWEEN',[$star,$end]);
				$num['type']=4;
				$num['time']=$info['time'];
			}else{
				$num['type']=3;
			}
			
			
			if(session("USER_AUTH_GROUP")==config('AUTH_GROUP'))
			{
				//小组长
				$arr[]=0;
				$arr[]=session("USER_AUTH_KEY");
				$map['b.uid']=array('in',$arr);
				$maps['a.user_id']=array('in',$arr);
				
			}
			
			if($info['tean'] !='')
			{
				$where['m.team']=$info['tean'];
			}
			
				$a=Db::name("tasks")
							->alias("b")
							->join("v_member m",'m.uid=b.uid')
							->where($where)
							->where($map)
							->column("number");
				$num['t_num']=count($a);
				$num['o_num']=array_sum($a);
				
				$where['a.status']=array('neq',0);
				$b=Db::name("sub_order")
					->alias('a')
					->join('v_tasks b','b.id=a.tid')
					->join("v_member m",'m.uid=a.user_id')
					->field("a.price,a.status")
					->where($where)
					->where($maps)
					->select();
				$status=array_count_values(array_column($b, 'status'));
				$num['ok']=$status[3]+0;	//任务成功
				$num['liu']=$status[6]+0;	//任务流单
				$num['nok']=$status[4]+0;	//任务失败
				$num['give']=$status[5]+0; //取消执行
				$num['shen']=$status[2]+0; //审核中
				$num['ing']=$status[1]+0;	//任务中
				$num['mem']=0;
				foreach($b as $v){
					if($v['status']==3){
						$num['mem']=$num['mem']+$v['price'];
					}
				}
				$num['mem']=round($num['mem'],1);
		}
		
		$this->assign('num',$num);
        return $this->fetch(); 
	}
 



}
