<?php
namespace app\index\controller;
/***任务订单模块定时器控制器***/

use think\Db;
use think\Controller;
use app\index\model\Tasks;
use app\index\model\Order;
use app\index\model\SubOrder;
use app\index\model\Member;
use app\index\model\DataLog;
use app\index\model\Message;
use app\index\model\Bill;


class Time extends controller
{
    public function index()
    {
        $this->bill_add();
        $this->task_over();
        $this->single_flow();
        $this->task_end();
        $this->messages();
        $this->option();
        $this->credit_reset();
    }

    //task表当任务status==2 任务在先时间到了之后的时候 改为3
    protected function task_over()
    {
        $where['status']=2;
        $where['active_time']=array('ELT',time());
        $map['active_time']=array('neq',0);
        $where['novice']=2;
        //时间到期
        $task=db('tasks')->where($where)->where($map)->select();
		
        foreach ($task as $k => $v) {
            Tasks::where(array('id'=>$v['id']))->update(array('status'=>3));
        }
        $tasks=db('tasks')->where('number=amount')->where(array('status'=>2,'novice'=>2))->select();
        foreach ($tasks as $k => $v) {
            Tasks::where(array('id'=>$v['id']))->update(array('status'=>3));
        }

    }

    //子订单流单 (不是新手任务和养号任务)
    protected function single_flow()
    {
        $where['is_novice']=0;
        $where['status']=1;
        $where['implement_time']=array('ELT',time());
        $order=db('order')->where($where)->select();
		// dump($order);die;

        if(!empty($order)){
            foreach ($order as $k => $v) {

                $model=new Tasks;
                $model->startTrans();
                $num=$v['number'];// 流单次数
               
                $res1=$model->where(array('id'=>$v['order_id']))->setDec('amount',$num);//将失败数量返回
                
                $res2=Order::where(array('id'=>$v['id']))->update(array('status'=>3,'update_time'=>time()));

                $res3=SubOrder::where(array('oid'=>$v['id'],'status'=>1))->update(array('status'=>6,'auditing_time'=>time()));
               

                if(unserialize($v['details'])['nature'] != 1)
                {
                    //小V任务
                    $credit=$num*5;//扣除信用分
                    $res4=Member::where(array('uid'=>$v['to_user_id']))->setDec('credit',$credit);

                    //日志
                    $log=array('to_uid'=>$v['to_user_id'],'action'=>'任务流单','is_add'=>2,'type'=>4,'num'=>$credit);
                    $res5=DataLog::create($log,true);
                    
                    if($res1 && $res2 && $res3==$num && $res4 && $res5)
                    {
                        $model->commit();
                        close($v['to_user_id']); //封号处理
                        $mess=new Message;
                        $action='任务流单，扣除'.$credit.'点信用积分';
                        $mess->madd($v['id'],$action,$v['to_user_id']); 

                    }else{
                        $model->rollback();
                    }

                }else if($res1 && $res2 && $res3==$num){
                    $model->commit();
                    $mess=new Message;
                    $mess->madd($v['id'],'任务流单',$v['to_user_id']);  
                }else{
                    $model->rollback();
                }
                
                   
            }
        }
        
    }

    //当订单状态为3时候 子订单完成数量达到 改为任务完成
    protected function task_end()
    {
        $where['status']=3;
        $where['novice']=2;
        $task=db('tasks')->where('number=amount')->where($where)->select();
        foreach ($task as $k => $v) {
            $a=db('order')->where(array('order_id'=>$v['id']))->select();
            $oid=array_column($a,'id');
            $map['oid']=array('in',$oid);
            $map['status']=3;
            $count=db('sub_order')->where($map)->count();
            if($count==$v['number'])
            {
                Tasks::where(array('id'=>$v['id']))->update(array('status'=>5));
            }
        }
    }


    // 任务执行结束前10分钟发站内信息
    protected function messages()
    {
        $where['status']=1;
        $where['implement_time']  = array('elt',time()+10*60);
        $where['is_novice']=0;
        $order=db('order')->where($where)->select();
        if(!empty($order)){      
            foreach ($order as $k => $v) {
                $add=array();
                $action='任务'.unserialize($v['details'])['name'].'将在10分钟后结束,请尽快完成任务并提交回执';
                $add=array('to_user_id'=>$v['to_user_id'],'reason'=>$action,'oid'=>$v['id']);
                if(!db('message')->where($add)->find()){
                    Message::create($add,true);
                }
            }
        }
    }



    //账号解封
    protected function option()
    {
        $where['mem_status']=0;
        $where['seal_time']=array('LT',time());
        $member=db('member')->where($where)->select();
        if(!empty($member)){
            foreach ($member as $k => $v) {
                Member::where(array('uid'=>$v['uid']))->update(array('mem_status'=>1));
            }
        }
        
    }


    //每月信用度重置100
    protected function credit_reset()
    {

        $time=(int)date('m',time());
        $where['reset']=array('neq',$time);
        $where['apply']=2;
        $where['mem_status']=array('neq',2);
        $member=db('member')->where($where)->select();
        if(!empty($member)){
            foreach ($member as $k => $v) {
                if($v['reset']==''){
                    Member::where(array('uid'=>$v['uid']))->update(array('reset'=>$time));
                }else{
                    Member::where(array('uid'=>$v['uid']))->update(array('reset'=>$time,'credit'=>100));
					$add=array();
					$action='本月信用重置到100。';
					$add=array(
						'to_user_id' => $v['uid'],
						'type'	 =>	 4,
						'reason' =>  $action,
						'status' =>  0
					);
					Message::create($add,true);				
                }
            }
        }

    }


    //每月账单生成
    protected function bill_add()
    {

        $thismonth = date('m');
        $thisyear  = date('Y');
        $time=$thisyear . '-' . $thismonth;        
        if(!db('bill')->where(array('time'=>$time))->find()){
            $time_num=(int)($thisyear.$thismonth);
            $startDay = $thisyear . '-' . $thismonth . '-1';
            $endDay   = $thisyear . '-' . $thismonth . '-' . date('t', strtotime($startDay)).' 23:59:59';
            $action       = strtotime($startDay);//当前月起始时间戳
            $end       = strtotime($endDay);//当前月结束时间戳
            $add=array(
                'time'=>$time,
                'time_num'=>$time_num,
                'start' =>$action,
                'end'=>$end,
                );
            Bill::create($add,true);
        }

    }



}