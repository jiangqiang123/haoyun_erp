<?php
namespace app\api\model;
use think\Model;

class Order extends Model{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $createTime = 'addtime';
    protected $updateTime = false;

    #生成订单
    public function add_order($data,$num){
        $order = new Order();
        $order->startTrans();
        $res1 = $order::create($data,true);   //create返回的是当前模型的对象实例  生成主订单
        $nums = $res1->number;
        $arr = array();
        for($i=0;$i<$nums;$i++){
            $arr[] = array(
				'user_id' => $data['user_id'],
                'to_user_id' => UID,
                'oid' => $res1->id,     //主订单id
                'status' => 1,
                'price' => $data['price'],
                'tid' => $data['order_id'], //任务id
            );
        }
        $suborder = new SubOrder();
        $res2 = $suborder->saveAll($arr);   //生成子订单
        $ids = collection($res2)->toArray();

        $map['num'] = $num+$res1->number;   //增加该任务接单量
        $task = new Tasks();
        $res3 = $task->save([
            'amount' => $map['num'],
        ],['id'=>$data['order_id']]);

        $tasks = Tasks::get($data['order_id']);
        $surplus_num = $tasks['number']-$tasks['amount'];

        if($surplus_num < 1){
            $res4 = $task->save([          //数量为0时，改变主订单状态
                'status'=> 3,
            ],['id'=>$data['order_id']]);
            if($res1 && count($res2)==$nums && $res3 && $res4){
                $order->commit();
                $res['addtime'] = strtotime($res1->addtime);
                $res['status'] = $res1->status;
                return $res;
            }else{
                $order->rollback();
                return false;
            }
        }else{
            if($res1 && count($res2)==$nums && $res3){
//            if($res1 && count($res2)==$nums){
                $order->commit();
                $res['addtime'] = strtotime($res1->addtime);
                $res['status'] = $res1->status;
                $res['sub_id'] = array_column($ids,'id');
                $res['order_id'] = $res1->id;
                return $res;
            }else{
                $order->rollback();
                return false;
            }
        }

    }

    #提交新手任务
    public function add_resultpic($data){
        $model = new Order();
        $res = $model->save([
            'result_pic' => $data['pic'],
            'status' => 2,
            'account' =>0,                          //新手任务执行账号为0
        ],['id'=>$data['id']]);
        return $res;
    }

    #养号任务
    public function add_keep($data){
        $model = new Order();
        $res = $model->save([
            'order_id' => 0,
            'user_id' => 0,
            'to_user_id' => UID,
            'is_novice' => 2,
            'number' => 0,
            'price' => 0,
            'result_pic' => $data['pic'],
            'status' => 2,
            'account' => $data['account'],
            'result_url' => $data['result_url'],
            'account_id' => $data['account_id'],
        ]);
        return $res;
    }


    /***提交任务----微博
     *
     *
     *
     */
    public function get_sub_weibo($data){
        $suborder = new SubOrder();
        $suborder->startTrans();
        $all = array();
        $order = new Order();
        if($data['sub_count'] != $data['count']){                //实际回执数量少于子订单数
            $sub = array_chunk($data['suborder'],$data['count']);   //根据实际回执数量选择子订单

            $check_sub = array_column($sub[0],'id');                //选择子订单进行回执

            foreach ($data['arr'] as $key=>$value){
                $all[$key]['account'] = $value['account'];         //执行账号
                $all[$key]['result_pic'] = $value['result_pic'];      //截图
                $all[$key]['result_url'] = $value['result_url'];      //链接
                $all[$key]['account_id'] = $value['account_id'];      //执行账号id
                $all[$key]['result_writ'] = $value['result_writ'];    //文字
                $all[$key]['id'] =$check_sub[$key];
                $all[$key]['status'] = 2;
            }
            $res1 = $suborder->saveAll($all);                       //将回执信息填写到子订单中

            $datas['status'] = 4;
            $where['id']=array('not in',$check_sub);
            $where['oid'] = $data['id'];
            $res2 = $suborder->save([
                'status' => 5,
            ],$where);                                              //未填回执，直接状态为5（取消执行）

            $res3 = $order->save([
                'number' => $data['count'],
                'status' => 2,
                'submit_time'=>time(),
            ],['id'=>$data['id']]);                                 //改变主订单状态和实际接单数

            $task = new Tasks();
            $num = $data['sub_count']-$data['count'];             //未回执的子订单数
            $nums = $data['task_amount']-$num;
            $res4 = $task->save([
                'amount'=>$nums,
            ],['id'=>$data['task_id']]);
            if(count($res1)==$data['count'] && $res2 && $res3 && $res4){
                $suborder->commit();
                return true;
            }else{
                $suborder->rollback();
                return false;
            }
        }else{                                                      //实际回执数量等于子订单数
            $check_sub = array_column($data['suborder'],'id');
            foreach ($data['arr'] as $key=>$value){
                $all[$key]['account'] = $value['account'];         //执行账号
                $all[$key]['result_pic'] = $value['result_pic'];      //截图
                $all[$key]['result_url'] = $value['result_url'];      //链接
                $all[$key]['account_id'] = $value['account_id'];      //执行账号id
                $all[$key]['result_writ'] = $value['result_writ'];    //文字
                $all[$key]['id'] =$check_sub[$key];
                $all[$key]['status'] = 2;
            }
            $res1 = $suborder->saveAll($all);           //修改子订单信息
            $res2 = $order->save([                      //修改主订单状态
                'status' => 2,
                'submit_time'=>time(),
            ],['id'=>$data['id']]);                                 //改变主订单状态
            if(count($res1)== $data['sub_count'] && $res2){
                $suborder->commit();
                return true;
            }else{
                $suborder->rollback();
                return false;
            }
        }
    }

    /**提交任务其他类型
     * @function
     * @param $data
     * @return bool
     */
    public function get_sub($data){
        $suborder = new SubOrder();
        $suborder->startTrans();
        $order = new Order();
        $res1 = $suborder->save([
            'result_pic'  => $data['arr']['result_pic'],
            'result_url'=> $data['arr']['result_url'],
            'result_writ' => $data['arr']['result_writ'],
            'status' => 2,
        ],['id' => $data['suborder'][0]['id']]);    //修改子订单信息

        $res2 = $order->save([
            'status' => 2,
            'submit_time'=>time(),
        ],['id'=>$data['id']]);     //改变主订单状态
        if($res1 && $res2){
            $suborder->commit();
            return true;
        }else{
            $suborder->rollback();
            return false;
        }
    }

    /***放弃任务
     * @param $data
     */
    public function order_cancel($data){
        $tasks = new Tasks();
        $tasks->startTrans();
        $info = db('order')->where('id',$data['id'])->find();
        $count = db('subOrder')->where('oid',$data['id'])->count();
        $task = db('tasks')->where('id',$info['order_id'])->find();
        $num = $task['amount']-$count;
        $res3 = $tasks->save([
            'amount' => $num,
        ],['id'=>$task['id']]);                             //修改任务接单量
        $suborder = db('subOrder')->where('oid',$data['id'])->select();
        $sub_id = array_column($suborder,'id');
        $res1 = SubOrder::destroy($sub_id);     //删除子订单
        $res2 = Order::destroy($data['id']);                 //删除主订单
        if($res1==$count && $res2 && $res3){
            $tasks->commit();
            return true;
        }else{
            $tasks->rollback();
            return false;
        }

    }


     //定时器执行主订单子订单修改状态（任务数量充足的状态下）
     public function order_list($data){
         $order = new Order();
         $tasks = new Tasks();
         $order_info = $order::get($data['order_id']);
         if($order_info->status == 0){                              //先将主订单状态改为1
             $res1 = $order->save([
                 'status'=>1,
                 'update_time'=>time(),
             ],['id'=>$data['order_id']]);
         }
         $suborder_list = db('subOrder')->where('oid',$data['order_id'])->order('id asc')->select();     //查看该订单下的所有子订单
         foreach ($suborder_list as $v){                            //遍历子订单
             $task = Tasks::get($data['task_id']);
             $num = $task['number']-$task['amount'];                //查看任务剩余数量
             if($num>0){
                 $suborder = new SubOrder();
                 $res2 = $suborder->save([                          //改变子订单状态
                     'status'=>1,
                 ],['id'=>$v['id']]);
                 $amount = $task->amount + 1;                      //任务接单量+1
                 $res3 = $tasks->save([
                     'amount'=> $amount,
                 ],['id'=>$data['task_id']]);
             }else{
                 $map = array(
                     'oid' => $data['order_id'],
                     'status' => 1,
                 );
                 $list = db('subOrder')->where($map)->find();   //查看该主订单下是否已经存在状态为1的子订单
                 if($list){
                      db("subOrder")->delete($v['id']);      //若存在,只删除子订单，主订单保留
                 }else{
                     $arr = db("subOrder")->where('oid',$data['order_id'])->select();   //如不存在，删除主订单及全部子订单
                     $arr_id = array_column($arr,'id');
                     db('subOrder')->delete($arr_id);
                     db('order')->delete($data['order_id']); //
                 }
                 if($task->status == 2){
                     $res4 = $tasks->save([
                         'status'=>3,                          //改变任务状态
                     ],['id'=>$data['task_id']]);
                 }
             }
         }
         $uid = $order_info->to_user_id;
         return $uid;
     }
}