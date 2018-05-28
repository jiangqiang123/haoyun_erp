<?php
namespace app\index\model;
use think\Model;
use app\index\model\Member;
use app\index\model\SubOrder;
use app\index\model\Tasks;
use app\index\model\DataLog;
class Order extends Model
{

	#订单审核----所有子订单完成都完成
	/*
	*@order  子订单信息 
	*@info  member表需该的字段信息
	*@对应订单的子订单的总量
	*/
	public function order_all_true($order,$info,$num)
	{
		$time=time();
		$ord=new Order;
		$ord->startTrans();

		#改订单的数据;
		$res1=$ord->where(array('id'=>$order->id))->update(array('status'=>5,'update_time'=>$time));

        #number 累计的数据         
        $member=Member::get($order->to_user_id);

        //判断小V等级有没有升级
        $map['experience']=array('GT',$member->experience);
        $res5=0;
        if($grade=db('smallv_grade')->where($map)->order('experience asc')->find()){
        	if($grade['experience'] < $member->experience+$info['experience']){
        		$res5=1;
        	}
        }
		$member->balance=$member->balance+$info['balance'];
		$member->gold=$member->gold+$info['gold'];
		$member->experience=$member->experience+$info['experience'];
		$res2=$member->save();

		//改子订单
		$where['status']=2;
		$where['oid']=$order->id;
		$res3=SubOrder::where($where)->update(array('status'=>3,'auditing_time'=>$time));

		//存日志;
		$log=array();
		$log[]=array('to_uid'=>$order->to_user_id,'action'=>'任务完成','type'=>1,'num'=>$info['balance'],'oid'=>$order->id);
		$log[]=array('to_uid'=>$order->to_user_id,'action'=>'任务完成','type'=>2,'num'=>$info['experience'],'oid'=>$order->id);
		$log[]=array('to_uid'=>$order->to_user_id,'action'=>'任务完成','type'=>3,'num'=>$info['gold'],'oid'=>$order->id);
		$data=new DataLog;
        $res4=$data->saveAll($log);
		//$res4=DataLog::saveAll($log);


		if($res1 && $res2 && $res3==$num && count($res4)==3)
		{
			$ord->commit();
			if($res5==1){
				$date['grade']=1;
			}else{
				$date['grade']=0;
			}
			$date['state']='y';
			$date['msg']='审核成功';
			return $date;
		}else{
			$ord->rollback();
			$date['state']='n';
			$date['msg']='审核失败';
			return $date;

		}

	}



	#订单审核---有部分子订单完成  部分失败
	/*
	*@order 订单信息
	*@info  member修改的数据
	*@num  子订单的总量
	*@array 子订单失败的数据   数组集合
	*/
	public function order_some_true($order,$info,$num,$array)
	{
		$ord=new Order;
		$ord->startTrans();
		$time=time();

		#改订单的数据;
		$res1=$ord->where(array('id'=>$order->id))->update(array('status'=>5,'update_time'=>$time));

		#number 累计的数据
		//$res2=Member::get($ord->id);
		$member=Member::get($order->to_user_id);

		$map['experience']=array('GT',$member->experience);
        $res7=0;
        if($grade=db('smallv_grade')->where($map)->order('experience asc')->find()){
        	if($grade['experience'] < $member->experience+$info['experience']){
        		$res7=1;
        	}
        }


		$member->balance=$member->balance+$info['balance'];//加余额 金币 经验
		$member->gold=$member->gold+$info['gold'];
		$member->experience=$member->experience+$info['experience'];
		$member->credit=$member->credit-$info['credit'];//减信用积分
		$res2=$member->save();
		
		#该子订单数据
		$sub=new SubOrder;
		$res3=$sub->saveAll($array);
		$where['id']=array('not in',array_column($array,'id'));
		$where['oid']=$order->id;
		$where['status']=2;
		$res4=$sub->where($where)->update(array('status'=>3,'auditing_time'=>$time));


		#任务表  将失败的num  返回
		$count=(int)count($array);
		$task=Tasks::get($order->order_id);
		//$task->serDec('amount',$count);
		$task->amount=$task->amount-$count;
		if($task->status == 3){
			$task->status=2;
		}
		$res5=$task->save();

		//存日志
		$log=array();
		$log[]=array('to_uid'=>$order->to_user_id,'action'=>'任务完成','type'=>1,'num'=>$info['balance'],'oid'=>$order->id);
		$log[]=array('to_uid'=>$order->to_user_id,'action'=>'任务完成','type'=>2,'num'=>$info['experience'],'oid'=>$order->id);
		$log[]=array('to_uid'=>$order->to_user_id,'action'=>'任务完成','type'=>3,'num'=>$info['gold'],'oid'=>$order->id);
		$log[]=array('to_uid'=>$order->to_user_id,'action'=>'任务失败','is_add'=>2,'type'=>4,'num'=>$info['credit'],'oid'=>$order->id);
		$data=new DataLog;
        $res6=$data->saveAll($log);


		if($res1 && $res2 && count($res3)==$count && $res4==($num-$count) && $res5 && count($res6)==4){
			$ord->commit();
			if($res7==1){
				$date['grade']=1;
			}else{
				$date['grade']=0;
			}
			$date['state']='y';
			$date['msg']='审核成功';
			return $date;die;
		}else{
			$ord->rollback();
			$date['state']='n';
			$date['msg']='审核失败';
			return $date;die;
		}


	}

	#订单审核  全部失败
	public function order_all_false($order,$info,$array)
	{
		$ord=new Order;
		$ord->startTrans();

		#改订单的数据;
		$res1=$ord->where(array('id'=>$order->id))->update(array('status'=>4,'update_time'=>time()));

		#改member表的数据
		//$member=Member::get($order->to_user_id);
		$res2=Member::where(array('uid'=>$order->to_user_id))->setDec('credit',$info['credit']);

		#改子订单表
		$sub=new SubOrder;
		$res3=$sub->saveAll($array);

		#改任务表
		$count=(int)count($array);
		$task=Tasks::get($order->order_id);
		$task->amount=$task->amount-$count;
		//$task->serDec('amount',$count);
		if($task->status == 3){
			$task->status=2;
		}
		$res4=$task->save();

		$log=array(
			'to_uid'=>$order->to_user_id,
			'action'=>'任务失败',
			'is_add'=>2,
			'type'	=>4,
			'num'	=>$info['credit']
			);
		$res5=DataLog::create($log,true);

		if($res1 && $res2 && count($res3)==$count && $res4 && $res5)
		{
			$ord->commit();
			$date['state']='y';
			$date['msg']='审核成功';
			return $date;
		}else{
			$ord->rollback();
			$date['state']='n';
			$date['msg']='审核失败';
			return $date;
		}

	}

	#普通任务订单审核
	public function order_commons($order,$array)
	{
		$ord=new Order;
		$ord->startTrans();
		$res1=$ord->where(array('id'=>$order->id))->update(array('status'=>5));

		if(empty($array)){
			//任务成功
			$res2=SubOrder::where(array('oid'=>$order->id,'status'=>2))->update(array('status'=>3,'auditing_time'=>time()));
			$member=Member::get($order->to_user_id);
			$res3=$member->setInc('balance',$order->price);
			//$res3=$member->save();

		}else{
			//任务失败
			$res2=SubOrder::where(array('oid'=>$order->id,'status'=>2))->update(array('status'=>4,'auditing_time'=>time()));

			$task=Tasks::get($order->order_id);
			$task->amount=$task->amount-1;
			if($task->status == 3){
				$task->status=2;
			}
			$res3=$task->save();
			
		}


		if($res1 && $res2 && $res3)
		{
			$ord->commit();
			if(empty($array))
			{
				$log=array('to_uid'=>$order->to_user_id,'action'=>'任务完成','type'=>1,'num'=>$order->price,'oid'=>$order->id);
				DataLog::create($log,true);
			}
			$date['state']='y';
			$date['msg']='审核成功';
			return $date;die;
		}else{
			$ord->rollback();
			$date['state']='n';
			$date['msg']='审核失败';
			return $date;die;
		}


	}



}