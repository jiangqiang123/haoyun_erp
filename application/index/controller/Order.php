<?php
namespace app\index\controller;
/**********订单控制器********/
use app\index\controller\Pub;
use app\index\model\TaskType;
use app\index\model\Tasks;
use app\index\model\Order as Ord;
use app\index\model\Category;
use app\index\model\DataLog;
use app\index\model\Member;
use app\index\model\SubOrder;
use app\index\model\Message;
use think\Db;
use think\Request;
//use think\Session;


class Order extends Pub
{
    
    #订单列表
    public function order_list()
    {
        // $oid=(int)input('get.id');
        $info=input('get.');
		// dump($info);die;
        if(session('USER_AUTH_GROUP')==config("AUTH_GROUP"))
        {
            //$map['group']!=0;
            $map['group']=session('USER_AUTH_KEY');
            $lists=db('member')->where($map)->select();
            $id=array_column($lists,'uid');
            !empty($lists)?$where['a.to_user_id']=array('in',$id):"";

        }
        if(isset($info['status']) && $info['status']!=0){
            $where['a.status'] = (int)$info['status'];
			$if['status']=$info['status'];
        }else{
            $where['a.status'] = array('neq',0);
        }

        $where['a.order_id']=$info['id'];
		// dump($where);die;
		$if['id']=$info['id'];
        $list=db('order')
              ->alias('a')
              ->join('v_member b','a.to_user_id = b.uid')  
			  ->join('v_member_realname c','b.uid = c.uid','LEFT')
              ->field('a.*,b.mobile,c.realname')
              ->where($where)
              ->paginate(8,false,['query'=>$if]);
		$page = $list->render();
        $this->assign('status',$where['a.status']);
        $this->assign('list',$list);
		$this->assign('page',$page);
        return $this->fetch(); 
    }

    
    #审核订单
    public function  order_auditing()
    {
        if(IS_POST){

            $info=input('post.');

            $array=array();  //存放子订单任务失败的订单

            //判断若存在失败时候 原因是否为空
            if(!empty($info['sub_id']))
            {
                foreach ($info['sub_id'] as $k => $v) {
                    if($info['lose'.$v]==''){
                        $date['state']='n';
                        $date['msg']='任务失败原因未填写';
                        return json($date);die;
                    }
                    $array[]=array('id'=>$v,'lose'=>$info['lose'.$v],'status'=>4,'auditing_time'=>time());
                }
            }
            $order=Ord::get($info['id']);
			
			
            if($order->status != 2){
                $date['state']='n';
                $date['msg']='无效操作';
                return json($date);die; 
            }
            $num_sub=db('sub_order')->where(array('oid'=>$order->id,'status'=>2))->count();
            $model=new Ord;
            //判断是不是小V任务
            if(db('tasks')->where('id',$order->order_id)->field('nature')->find()['nature'] != 1){
                
                $increase=db('increase')->where(array('status'=>1))->select();  //任务成长表
            
                //所有的子订单完成 增加经验 增加金币  
                if(empty($array)){

                    $num=$order->number;//成功的子订单数为
                    //member order  sub_order   4个日志表
                    $info_mem['experience']=$num*$increase[0]['experience'];
                    $info_mem['gold']=$num*$increase[0]['gold'];
                    $info_mem['balance']=$num*$order->price;
                                    
                    $res=$model->order_all_true($order,$info_mem,$num_sub);
                    if($res['state']=='y'){
                        //发送消息
                        $mess=new Message;
                        if($res['grade']==1){
                            $action='任务审核完成,增加经验、金币以及账户余额,小V等级提升';
                        }else{
                            $action='任务审核完成,增加经验、金币以及账户余额';
                        }
                        $mess->madd($order->id,$action,$order->to_user_id);
                        //$mess->madd(); 
                    }                   
                    day_order($order->to_user_id,$num_sub);//日统  
                    month_order($order->to_user_id,$num_sub);//月统
                    return json($res);die;

                }else if($order->number > count($array)){
                    //有部分完成 有部分失败  失败的统计 在失败的任务量返回到任务中去
                    $num=$order->number-count($array);//成功的子订单数为
                    $info_mem=array(
                            'experience'=>$num*$increase[0]['experience'],
                            'gold'      =>$num*$increase[0]['gold'],
                            'balance'   =>$num*$order->price,
                            'credit'    =>count($array)*10// 需减少的信用分
                        );
                    $res=$model->order_some_true($order,$info_mem,$num_sub,$array);
                    if($res['state']=='y'){
                        //发送消息
                        close($order->to_user_id);//封号处理
                        $mess=new Message;
                        if($res['grade']==1){
                            $action='任务审核完成,增加经验、金币以及账户余额,小V等级提升。因有部分任务未完成，扣除'.$info_mem['credit'].'信用分';
                        }else{
                            $action='任务审核完成,增加经验、金币以及账户余额。因有部分任务未完成，扣除'.$info_mem['credit'].'信用分';
                        }            
                        $mess->madd($order->id,$action,$order->to_user_id);
                    }     
                    day_order($order->to_user_id,$num);//日统  
                    month_order($order->to_user_id,$num);//月统
                    return json($res);die;               

                }else{
                    //任务全部失败  失败扣10
                    $info_mem['credit']=count($array)*10;
                    $res=$model->order_all_false($order,$info_mem,$array);
                    if($res['state']=='y'){
                        //发送消息
                        close($order->to_user_id);//封号处理
                        $mess=new Message;
                        $action='任务审核完成,任务未完成，扣除'.$info_mem['credit'].'信用分';
                        $mess->madd($order->id,$action,$order->to_user_id); 
                    }
                    return json($res);die; 
                }

            }else{
                //普通任务
                $res=$model->order_commons($order,$array);
                if($res['state']=='y' && empty($array)){
                    //发送消息
                    $mess=new Message;
                    $mess->madd($order->id,'任务审核完成',$order->to_user_id); 
                    if(db('member')->where(array('uid'=>$order->to_user_id,'apply'=>2))->find()){
                        day_order($order->to_user_id,1);//日统
                        month_order($order->to_user_id,1);//月统
                    }
                }
                return json($res);die; 

            }
        }

        $id=(int)input('get.id');
        if(!$list=db('order')->where(['id'=>$id])->find()){
            return $this->error('查无此订单');
        }
		
        if(session('USER_AUTH_GROUP')==config("AUTH_GROUP"))
        {
            $map['group']=session('USER_AUTH_KEY');
            $lists=db('member')->where($map)->select();
			
            $id=array_column($lists,'uid');

            if(!in_array($list['to_user_id'],$id))
            {
                return $this->error("无效操作");
            }        
        }
        

        $list['details']=unserialize($list['details']);
        $list['details']['content']=unserialize($list['details']['content']);

        if($list['is_novice'] == 2){
            //查出类别  转评赞
            $cate=db('category')
                ->where(['id',['in',$list['details']['category']]])
                ->select();
            $a = array_column($cate,'name');
            $list['category']=implode('、',$a);
        }

        //查出任务所属平台
        $list['type']=db('task_type')
                    ->where(array('id'=>$list['details']['type']))
                    ->field('name')
                    ->find()['name'];
        $list['end']=$list['addtime'] + $list['details']['execute_time'];

        $sub=db('subOrder')
            ->where(array('oid'=>$list['id'],'status'=>2))
            ->select();
        foreach ($sub as $k => $v) {
            if($v['result_pic'] != ''){
                $sub[$k]['result_pic']=explode(',', $v['result_pic']);
            }
        }

        // dump($sub);die;
        $this->assign('sub',$sub);
        $this->assign('list',$list);
        return $this->fetch();

    }


    #订单详情
    public function order_details()
    {
        $id=(int)input('get.id');
        $list=db('order')->where(['id'=>$id])->find();
        //任务的基本信息字段
        $list['details']=unserialize($list['details']);
        $list['details']['content']=unserialize($list['details']['content']);

        if($list['is_novice'] == 2){
            //查出类别  转评赞
            $cate=db('category')->where(['id',['in',$list['details']['category']]])->select();
            $a = array_column($cate,'name');
            $list['category']=implode('、',$a);
        }
        
        //查出任务所属平台
        $list['type']=db('task_type')
                    ->where(array('id'=>$list['details']['type']))
                    ->field('name')
                    ->find()['name'];
        $list['end']=$list['addtime'] + $list['details']['execute_time'];
		
		
		if($list['status']==5 || $list['status']==4)
		{
			$map['oid']=$list['id'];
			$map['status']=array('neq',0);
			$sub=db('subOrder')
            ->where($map)
            ->select();
			foreach ($sub as $k => $v) {
				if($v['result_pic'] != ''){
					$sub[$k]['result_pic']=explode(',', $v['result_pic']);
				}
			}
		}
		
		$this->assign('sub',$sub);
        $this->assign('list',$list);
        return $this->fetch();
    }


    #养号任务审核列表
    public function raise_list()
    {

        $list=db('order')->where(array('is_novice'=>2,'status'=>2))->select();
        $this->assign('list',$list);
        return $this->fetch();

    }


    #养号任务审核
    public function raise_edit()
    {

        if(IS_POST){
            $info=input('post.');
            if($info['status']==4 && $info['lose']=='')
            {
                $date['state']='n';
                $date['msg']='请选择失败原因';
                return json($date);die; 
            }

            if($info['status']=='')
            {
                $date['state']='n';
                $date['msg']='请选择审核结果';
                return json($date);die; 
            }

            $order=Ord::get($info['id']);
            if($order->status!= 2)
			{
                $date['state']='n';
                $date['msg']='无效操作';
                return json($date);die;
            }

            $order->status=$info['status'];
            $order->lose=$info['lose'];

            if($info['status']==5 && select_raise($order->to_user_id)){
                 $order->startTrans();
                 $res1=$order->save();
                 $res2=eg_change($order->to_user_id,5,1);
                 $res3=eg_change($order->to_user_id,5,2);
				 // $res3=ag_change($order->to_user_id,5,2);
                 if($res1 && $res2 && $res3){
                    $order->commit();
                    $date['state']='y';
                    $date['msg']='审核完成';
                    return json($date);die; 
                }else{
                    $order->rollback();
                    $date['state']='n';
                    $date['msg']='操作失败';
                    return json($date);die; 
                }

            }else{
                if($order->save())
                {
                    $date['state']='y';
                    $date['msg']='审核完成';
                    return json($date);die; 
                }else{
                    $date['state']='n';
                    $date['msg']='操作失败';
                    return json($date);die; 
                }
            }

        }

        $id=input('id');
        if(!$list=db('order')->where(array('id'=>$id))->find())
        {
            $this->error('无效操作');
        }
        $list['member']=db('member')->where(array('uid'=>$list['to_user_id']))->field('mobile')->find()['mobile'];
        $list['result_pic']=unserialize($list['result_pic']);
        $this->assign('list',$list);
        return $this->fetch();
    }

    //养号任务批量审核cg
    public function raise_batch(){
        if(IS_POST)
        {

            //$where['id']=array('in',input('id'));
            $id=explode(',', input('post.id'));

            //$order=new Ord;
            foreach ($id as $k => $v) {
                $order=Ord::get($v);

                $order->status=5;
                
                if(select_raise($order->to_user_id)){
                    $order->startTrans();
                    //给经验 该订单状态
                    $res1=$order->save();
                    $res2=eg_change($order->to_user_id,5,1);
                    $res3=eg_change($order->to_user_id,5,2);
                    if($res1 && $res2 && $res3){
                        $order->commit();
                    }else{
                        $order->rollback();
                    }
                }else{
                    $order->save();
                }
            }

            $date['state']='y';
            $date['msg']='操作完成';
            return json($date);die; 

        }


    }


    #任务的批量审核
    public function order_batch()
    {
        if(IS_POST)
        {
            $id=explode(',',input('post.id'));

            $increase=db('increase')
                    ->where(array('status'=>1))
                    ->order('id asc')
                    ->select();//成长表查询
            foreach ($id as $k => $v) {
                $order=Ord::get($v);
                $order->startTrans();
                $balance=$order->number*$order->price;

                if(unserialize($order->details)['nature']==1){
                    //普通任务 只加余额 和该订单状态
                    $res1=Ord::where(array('id'=>$v,'status'=>2))->update(array('status'=>5,'update_time'=>time()));
                    $res2=Member::where(array('uid'=>$order->to_user_id))->setInc('balance',$balance);
                    $res3=SubOrder::where(array('oid'=>$v,'status'=>2))->update(array('status'=>3,'auditing_time'=>time()));
                    $log=array('to_uid'=>$order->to_user_id,'action'=>'任务完成','type'=>1,'num'=>$order->price,'oid'=>$order->id);
                    $res4=DataLog::create($log,true);
                    if($res1 && $res2 && $res3==$order->number && $res4)
                    {
                        $order->commit();
                        $mess=new Message;
                        $mess->madd($v,'任务审核完成',$order->to_user_id);
                        if(db('member')->where(array('uid'=>$order->to_user_id,'apply'=>2))->find()){
                            day_order($order->to_user_id,1);//日统
                            month_order($order->to_user_id,1);//月统 
                        }
                        
                    }else{
                        $order->rollback();
                    }

                }else{
                    //小V任务 加余额 金币 经验 该订单状态
                    $gold=$order->number*$increase[0]['gold'];
                    $experience=$order->number*$increase[0]['experience'];
                    $res1=Ord::where(array('id'=>$v,'status'=>2))->update(array('status'=>5,'update_time'=>time()));
                    $member=db('member')->where(array('uid'=>$order->to_user_id))->find();
                    $res6=0;
                    $map['experience']=array('GT',$member['experience']);
                    if($grade=db('smallv_grade')->where($map)->order('experience asc')->find()){
                        if($grade['experience'] < $member['experience']+$experience){
                            $res6=1;
                        }
                    }
                    $res2=Member::where(array('uid'=>$order->to_user_id))->setInc('balance',$balance);
                    $res3=Member::where(array('uid'=>$order->to_user_id))->setInc('gold',$gold);
                    $res4=Member::where(array('uid'=>$order->to_user_id))->setInc('experience',$experience);
                    $res5=SubOrder::where(array('oid'=>$v,'status'=>2))->update(array('status'=>3,'auditing_time'=>time()));
                    
                    if($res1 && $res2 && $res3 && $res4 && $res5==$order->number){
                        $order->commit();
                        $log=array();
                        $log[]=array('to_uid'=>$order->to_user_id,'action'=>'任务完成','type'=>1,'num'=>$balance,'oid'=>$order->id);
                        $log[]=array('to_uid'=>$order->to_user_id,'action'=>'任务完成','type'=>2,'num'=>$experience,'oid'=>$order->id);
                        $log[]=array('to_uid'=>$order->to_user_id,'action'=>'任务完成','type'=>3,'num'=>$gold,'oid'=>$order->id);
                        $data=new DataLog;
                        $data->saveAll($log);
                        //存消息 $v=>oid  reason="任务完成" to_user_id=>$order->to_user_id  
                        $mess=new Message;
                        if($res6==1){
                            $action='任务审核完成,增加经验、金币以及账户余额,小V等级提升。';
                        }else{
                            $action='任务审核完成,增加经验、金币以及账户余额。';
                        } 
                        $mess->madd($v,$action,$order->to_user_id);
                        day_order($order->to_user_id,$order->number);//日统
                        month_order($order->to_user_id,$order->number);//月统  
                    }else{
                        $order->rollback();
                    }


                }
            }

            $date['state']='y';
            $date['msg']='操作完成';
            return json($date);die;
        }
    }


 
    #订单一键审核  日志未存
    public function edit_all()
    {
        $id=input('post.id');
        $task=db('tasks')
            ->where(array('id'=>$id))
            ->find();

        $where['order_id']=$id;
        $where['status']=2;
        if(!$order=db('order')->where($where)->select())
        {
            $date['state']='n';
            $date['msg']='暂无需审核订单';
            return json($date);die; 
        }
        $increase=db('increase')
                    ->where(array('status'=>1))
                    ->order('id asc')
                    ->select();//成长表查询


        if($task['nature']==1){
            // 普通任务
            
            //$oid=array_column($order,'id');
           // $array=array();
            foreach ($order as $k => $v) {
                $balance=$v['number']*$v['price'];
                $order=new Order;
                $order->startTrans();

                $res1=$order->where(array('id'=>$v['id'],'status'=>2))->update(array('status'=>5));
                $res2=Member::where(array('uid'=>$v['to_user_id']))->setInc('balance',$balance);
                $res3=SubOrder::where(array('oid'=>$v['id'],'status'=>2))->update(array('status'=>3));

                if($res1 && $res2 && $res3==$order['number'])
                {
                    $order->commit();
                }else{
                    $order->rollback();
                }
            }



        }else{
            //小v任务 日志未存
            foreach ($order as $k => $v) {
                $balance=$v['number']*$v['price'];
                $gold=$v['number']*$increase[0]['gold'];
                $experience=$v['number']*$increase[0]['experience'];
                $order=new Order;
                $order->startTrans();
                $res1=$order->where(array('id'=>$v['id'],'status'=>2))->update(array('status'=>3));
                $res2=Member::where(array('uid'=>$v['to_user_id']))->setInc('balance',$balance);
                $res3=Member::where(array('uid'=>$v['to_user_id']))->setInc('gold',$gold);
                $res4=Member::where(array('uid'=>$v['to_user_id']))->setInc('experience',$experience);
                $res5=SubOrder::where(array('oid'=>$v['id'],'status'=>2))->update(array('status'=>3));
                if($res1 && $res2 && $res3 && $res4 && $res5==$order['number']){
                    $order->commit();
                }else{
                    $order->rollback();
                } 

            }

        }

        $date['state']='y';
        $date['msg']='操作成功';
        return json($date);die;

    }




   


}
