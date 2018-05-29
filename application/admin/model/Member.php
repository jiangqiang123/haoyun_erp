<?php
namespace app\admin\model;
use think\Model;
use app\admin\model\Order;
use app\admin\model\SmallvAccount;
use app\admin\model\MemberRealname;
//use app\index\model\MemberRealname;

class Member extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $createTime = 'addtime';
    protected $updateTime = false;


    #小v申请审核 成功apply=2
   	public function smallv_aud_true($info)
   	{
   		$mem=Member::get($info['uid']);
   		if($mem->apply != 1)
   		{
   			$date['state']='n';
   			$date['msg']='无效操作';
   			return $date;die;
   		}

   		 $mem->startTrans();
   			//申请成功
   			$mem->apply=2;
			$mem->group=$info['group'];
			$mem->team=$info['team'];
   			$res1=$mem->save();
			
   			// $where['is_novice']=1;
   			// $where['to_user_id']=$info['uid'];
            // if(!db('order')->where($where)->find()){
               // $res2=1;
            // }else{
   			   // $res2=Order::where($where)->update(array('status'=>5));
            // }
            
   			//分配账号
            // $code=db('active_code')->where(array('uid'=>$info['uid']))->find();
            // if($code['type']==0){
      			// $map=array('type'=>1,'uid'=>0,'status'=>1,'enable'=>1);
      			// $account=db('smallv_account')->where($map)->limit(3)->select();
               // if(count($account) < 3){
                  // $mem->rollback();
                  // $date['state']='n';
                  // $date['msg']='媒体库账号不足';
                  // return $date;die;
               // }

      			// $account=array_column($account,'id');
      			// $req['id']=array('in',$account);
      			// $res3=SmallvAccount::where($req)->update(array('uid'=>$info['uid'],'status'=>2));
            // }else{
               // $res3=3;
            // }

   			//实名认证
			$res4=MemberRealname::where(array('uid'=>$info['uid']))->update(array('status'=>$info['status']));
			if($res1 && $res4)
			{
					$mem->commit();
					$date['state']='y';
					$date['msg']='审核成功';
					return $date;die;
			}else{
					$mem->rollback();
					$date['state']='n';
					$date['msg']='审核失败';
					return $date;die;
			}
			
			
   			// if(isset($info['status'])){
   				// $res4=MemberRealname::where(array('uid'=>$info['uid']))->update(array('status'=>$info['status'],'lose'=>$info['real_lose']));
   				// if($res1 && $res2 && $res3==3 && $res4)
   				// {
	   				// $mem->commit();
	   				// $date['state']='y';
		   			// $date['msg']='成功';
		   			// return $date;die;
	   			// }else{
	   				// $mem->rollback();
	   				// $date['state']='n';
		   			// $date['msg']='失败';
		   			// return $date;die;
	   			// }

   			// }else{
	   			// if($res1 && $res2 && $res3==3)
	   			// {
	   				// $mem->commit();
	   				// $date['state']='y';
		   			// $date['msg']='成功';
		   			// return $date;die;
	   			// }else{
	   				// $mem->rollback();
	   				// $date['state']='n';
		   			// $date['msg']='失败';
		   			// return $date;die;
	   			// }
   			// }	
   	}


   	#小v 申请审核失败apply=0
   	public  function samllv_aud_false($info)
   	{
   		$mem=Member::get($info['uid']);
   		if($mem->apply != 1)
   		{
   			$date['state']='n';
   			$date['msg']='无效操作';
   			return $date;die;
   		}

   		$mem->startTrans();
   		$mem->apply=0;
   		$res1=$mem->save();
		
		//改订单信息
   		// $count=count($info['oid']);
   		// if(!empty($array)){
            
   			// $order = new Order;
   			// $res2=$order->saveAll($array);
   		// }

		
		
		//实名认证
   		if(isset($info['status'])){
   			$res3=MemberRealname::where(array('uid'=>$info['uid']))->update(array('status'=>$info['status'],'lose'=>$info['real_lose']));
   		}
		
		if($res1 && $res3)
		{
				$mem->commit();
   				$date['state']='y';
	   			$date['msg']='审核成功';
	   			return $date;die;
		}else{
				$mem->rollback();
   				$date['state']='n';
	   			$date['msg']='审核失败';
	   			return $date;die;
		}


   		// if(empty($array)){
   			// if($res1 && $res3)
   			// {
   				// $mem->commit();
   				// $date['state']='y';
	   			// $date['msg']='审核成功';
	   			// return $date;die;
   			// }else{
   				// $mem->rollback();
   				// $date['state']='n';
	   			// $date['msg']='审核失败';
	   			// return $date;die;
   			// }
   		// }else if(!isset($info['status'])){
   			// if($res1 && count($res2)==$count)
	   		// {
	   				// $mem->commit();
	   				// $date['state']='y';
		   			// $date['msg']='审核成功';
		   			// return $date;die;
	   			// }else{
	   				// $mem->rollback();
	   				// $date['state']='n';
		   			// $date['msg']='审核失败';
		   			// return $date;die;
	   		// }
   		// }else{
   			// if($res1 && count($res2)==$count && $res3)
	   			// {
	   				// $mem->commit();
	   				// $date['state']='y';
		   			// $date['msg']='审核成功';
		   			// return $date;die;
	   			// }else{
	   				// $mem->rollback();
	   				// $date['state']='n';
		   			// $date['msg']='审核失败';
		   			// return $date;die;
	   			// }
   		// }

   	} 
    

}