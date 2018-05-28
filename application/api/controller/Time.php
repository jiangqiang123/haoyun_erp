<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/8/18
 * Time: 10:25
 */
namespace app\api\controller;
use app\api\model\Order;


class Time{
    public function order_list_deal(){
        $redis = new \Redis();
        $redis->connect('127.0.0.1',6379);
        $password = '123456';
        //      $redis->auth($password);
       $len= $redis->lsize("order_lists");
        if($len!=false){
            $len>5?$len=5:$len;
            for ($x=0; $x<=$len; $x++) {
                $value = $redis->lpop('order_lists');//*****出队列
                   /***改变订单状态（主订单子订单）**/
                $order = db('order')->where('id',$value)->find();    //查询主订单获取任务id
                $task = db('tasks')->where('id',$order['order_id'])->find();   //查询任务详情
                $model = new Order();
                $data = array(
                    'order_id' => $value,
                    'task_id' => $task['id'],
                );
                $uid = $model->order_list($data);                             //状态处理反馈
                // 指明给谁推送，为空表示向所有在线用户推送
                $to_uid = 'v'.$uid;
                // 推送的url地址，上线时改成自己的服务器地址
                $push_api_url = "http://vs.tommmt.com:2121/";
                $post_data = array(
                    'type' => 'publish',
                    'content' => '接单成功',
                    'to' => $to_uid,
                );
                $ch = curl_init ();
                curl_setopt ( $ch, CURLOPT_URL, $push_api_url );
                curl_setopt ( $ch, CURLOPT_POST, 1 );
                curl_setopt ( $ch, CURLOPT_HEADER, 0 );
                curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
                curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
                $return = curl_exec ( $ch );
                curl_close ( $ch );
                var_export($return);

                //    $redis->lpush("mylist",$value);/**如果有错误重新加入队列**/
            }

        }else{
            dump("over");die;
        }

    }
}