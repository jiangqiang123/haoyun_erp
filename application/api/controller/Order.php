<?php
namespace app\api\controller;
use app\api\model\Order as OrderModel;
use app\api\model\SubOrder;
use think\Request;
use CodeError\CodeError;
use think\Validate;

class Order extends Home {
    public function implement(){
        $request = Request::instance();
        $param= $request->param();
        switch ($request->method()){
            case 'POST':
                if(!empty($param)){
                    if($param['type']==1){              /***接单***/
                        return $this->implement_task($param);
                    }elseif($param['type']==2){         /***提交新手任务***/          //取消功能
                        return $this->put_newbie($param);
                    }elseif($param['type']==3){         /***最大接单数量***/
                        return $this->meet_num($param);
                    }elseif($param['type']==4){         /***提交养号任务**/
                        return $this->keep_task($param);
                    }elseif($param['type']==5){         /***提交日常任务---微博**/
                        return $this->put_task_weibo($param);
                    }elseif($param['type']==7){         /***提交日常任务**/
                        return $this->put_task($param);
                    }elseif($param['type']==6){         /***放弃任务***/
                        return $this->cancel_order($param);
                    }else{
                        return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                    }
                }else{
                    return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                }
                break;

            case 'GET':
                if(!empty($param)){
                    if($param['type']==1){
                        return $this->my_task($param);          /***我的订单***/
                    }elseif ($param['type']==2){
                        return $this->newbie_task($param);      /***新手任务订单***/              //已取消
                    }elseif ($param['type']==3) {
                        return $this->suborder_info($param);    /***订单详情***/
                    }elseif ($param['type']==4){
                        return $this->receipt($param);          /***订单回执页面***/
                    }elseif ($param['type']==5){
                        return $this->result($param);           /****订单回执详情***/
                    }else{
                        return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                    }
                }else{
                    return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                }
                break;
            default:
                return ["code"=>CodeError::CODEEOOR_REQUEST_CODE,"message"=>CodeError::CODEEOOR_REQUEST_NAME];
        }
    }




    /***限制最大接取任务次数
     * @param $param
     * id  任务ID
     * type 3 post
     */
    protected function meet_num($param){
        $task = db('tasks')->where('id',$param['id'])->find();
            $map = array(               //查询账号分配列表
                'uid' => UID,           //使用者uid
                'type' => $task['type'],//账号类型（微博，微信）
                'enable' => 1,          //启用状态
                "is_del" => 1,
            );
        $num = db('smallvAccount')->where($map)->count();   //小V账号
        if($num == 1){
            $data['status'] = 'y';
        }elseif($num == 0){                                     //是小V但没分配账号，只能接一次(之前是小V，针对刚入驻的)
            $data['status'] = 'y';
        }else{
            $data['status'] = 'n';
        }
        $data['num'] = $num;
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }

    /****接单--------------------->??????
     * @param $param请求参数
     * @ oid 任务id
     * @ number 接取订单数量
     * type 1    post
     */

    protected function implement_task($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("oid","number"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $members = db('member')->where('uid',UID)->find();
        if($members['apply'] != 2){
            return ['code'=>CodeError::CODEEOOR_NOAUTH_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NOAUTH_NAME];    //还未完成实名认证
        }
        $map = array(
            'to_user_id' => UID,
            'order_id' => $param['oid'],                        //任务ID
        );
        $orders = db("order")->where($map)->find();           //判断是否已经接过单
        if($orders){
            return ['code'=>CodeError::CODEEOOR_ABNORMAL_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ABNORMAL_NAME];   //防止多次请求
        }
        $order = db('tasks')->where('id',$param['oid'])->find();                //任务详情
        // if($members['group'] != 0 && $members['group'] != $order['uid']){
            // return ['code'=>CodeError::CODEEOOR_TASKDEFFGROUP_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_TASKDEFFGROUP_NAME];   //任务所属小组不正确
        // }
		
		if($members['group']=='')
		{
			return ['code'=>CodeError::CODEEOOR_NOAUTH_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NOAUTH_NAME];//为实名认证
		}
		
		if($order['uid'] !=0 && $members['group'] != $order['uid'])
		{
			return ['code'=>CodeError::CODEEOOR_TASKDEFFGROUP_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_TASKDEFFGROUP_NAME];   //任务所属小组不正确
		}
		
        $level = account_type(UID);         //查看账号等级
        if($level<$order['nature']){
            return ['code'=>CodeError::CODEEOOR_ACCOUNTNOLEVEL_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ACCOUNTNOLEVEL_NAME];   //小V等级不够无法领取
        }
        $them = array(
            'uid' => UID,           //使用者uid
            'type' => $order['type'],//账号类型（微博，微信）
            'is_del' => 1,
        );
        $nums = db('smallvAccount')->where($them)->count();                     //统计该任务类型下的账号数
        if($nums == 0 && $order['type']==1){  //微博未手动添加资源账号
            return ['code'=>CodeError::CODEEOOR_ACCOUNT_NOEXIST_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ACCOUNT_NOEXIST_NAME];           //未添加小V资源账号
        }
        $surplus = $order['number'] - $order['amount'];                         //剩余任务数量
        if($surplus<$param['number']){                                           //剩余数量少于接单数量
            return ['code'=>CodeError::CODEEOOR_LACKTASK_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_LACKTASK_NAME];                     //任务数量不足
        }

        if($members['mem_status'] != 1){
            if($members['mem_status'] == 0){       //暂时封号
                $date['seal_time'] = $members['seal_time'];
            }elseif ($members['mem_status'] == 2){         //失去小V资格
                $date['seal_time'] = 99999999999;
            }
            return ['code'=>CodeError::CODEEOOR_ACCOUNT_LIMIT_CODE,'data'=>$date,'message'=>CodeError::CODEEOOR_ACCOUNT_LIMIT_NAME];         //账号被限制（封号，失去小V资格等）
        }

//        $is_bank = db('bankCard')->where('uid',UID)->find();                    //是否绑定银行卡
//        if(empty($is_bank)){
//            return ['code'=>CodeError::CODEEOOR_BANK_NOEXIST_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_BANK_NOEXIST_NAME];           //未绑定银行卡
//        }

        $details=array(
            'name'=>$order['name'],
            'type'=>$order['type'],
            'category'=>$order['category'],
            'execute_time'=>$order['execute_time'],
            'content'=>$order['content'],
            'nature' => $order['nature']
        );
        $data = [
            'order_id' => $param['oid'],
            'user_id' => $order['uid'],//后台系统发放
            'to_user_id' => UID,
            'number' => $param['number'],
            'price' => $order['price'],
            'status' => 1,
            'details' => serialize($details) ,
            'implement_time'=>$order['execute_time']+time(),
        ];
        $num = $order['amount'];
        $model = new OrderModel();
        $res = $model->add_order($data,$num);//先生成订单及子订单    状态为0
        if($res){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
            $result = [
                'addtime'=> $res['addtime'],
                'execute_time'=>$order['execute_time'],
                'status'=>$res['status'],
            ];
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
            $result = null;
        }

        return ['code'=>$code,'data'=>$result,'message'=>$message];

        //            $redis = new \Redis();                                          //开启radis,进行排队
//            $redis->connect('127.0.0.1',6379);
//            $codes = false;
//            for ($i=1;$i<=$account_nums;$i++){
//                if($redis->scontains('order_lists', UID."%".$param['oid']."%".$i)){
//                    $codes = true;
//                }
//            }
//         if($codes){
//             return ['code'=>CodeError::CODEEOOR_COMMON_QUEUING_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_COMMON_QUEUING_NAME];     //正在排队
//         }else{
//             $redis->sadd('order_lists',UID."%".$param['oid']."%".$param['number']);
//             return ['code'=>CodeError::CODEEOOR_COMMON_QUEUE_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_COMMON_QUEUE_NAME];     //进入队列成功
//         }


        //                $redis = new \Redis();
//                $redis->connect('127.0.0.1',6379);
//                $password = '123456';
        //      $redis->auth($password);
//                foreach ($res['sub_id'] as $v){
//                    $redis->rpush('order_lists',$v);           //排队
//                 }
//                $redis->rpush('order_lists',$res['order_id']);           //排队
//                $message = CodeError::CODEEOOR_COMMON_QUEUING_NAME; //正在排队
//                $code = CodeError::CODEEOOR_COMMON_QUEUING_CODE;
    }



    /***我的任务列表
     * @param $param
     * cate 任务状态
     * page 页码
     * type 1    get
     */
    protected function my_task($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("cate","page"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }

        if($param['cate']==1){
            $map['v_order.status'] = 1;                             //待执行
        }elseif ($param['cate']==2){
            $map['v_order.status'] = 2;                             //已执行
        }elseif ($param['cate']==3){
            $map['v_order.status'] = array('in','3,4,5');        //已完成
        }else{
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $map['v_order.to_user_id'] = UID;
        $map['v_order.is_novice'] =array('in',[0,2]);

        $list = db('Order')->alias('a')->join('v_tasks b','a.order_id = b.id')->field('a.id as oid,a.number as snumber,a.addtime as oaddtime,a.implement_time,b.*')->where($map)->paginate(10);
        $count = db('Order')->alias('a')->join('v_tasks b','a.order_id = b.id')->field('a.id as oid,a.number as snumber,a.addtime as oaddtime,a.implement_time,b.*')->where($map)->count();
        $list = $list->items();
        foreach ($list as $key=>$value){
            $maps['id']=array('in',$value['category']);
            $arr = db('category')->where($maps)->select();   //查看任务行为（转、赞、评）类型

            $one = array_column($arr,'icon');

            foreach ($one as $k=>$v){
                $one[$k] = 'http://'.$_SERVER['SERVER_NAME']. '/uploads/category/'.$v;
            }

            $list[$key]['category']=$one;

            $other = $value['type'];                            //任务类别
            $types = db('taskType')->where('id',$other)->find();
            $list[$key]['type'] = 'http://'.$_SERVER['SERVER_NAME']. '/uploads/type/'.$types['tp_icon'];

        }
        $data = array(
            'list'=>$list,                          //每页显示
            'count' => $count,                      //总的数量
        );
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }




    /***订单详情
     * @param $param
     * id 订单id
     * type 3  get
     */
    protected function suborder_info($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("id"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $info = db('Order')->alias('a')->join('v_tasks b','a.order_id = b.id')->field('a.id as oid,a.implement_time,a.status as ostatus,a.number as onumber,a.addtime as oaddtime,b.*')->where('a.id',$param['id'])->find();
        if(empty($info)){
            return ['code'=>CodeError::CODEEOOR_ABNORMAL_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ABNORMAL_NAME];
        }
        $other = array(
            'id' => ['in',$info['category']],
        );
        $category = db('category')->where($other)->select();
        $one = array_column($category,'name');
        $info['category'] = $one;
        if($info){
            $info['content'] = unserialize($info['content']);
            $info['surplus'] = $info['number']-$info['amount'];  //剩余数量
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
            $result = $info;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
            $result = null;
        }
        return ['code'=>$code,'data'=>$result,'message'=>$message];
    }

    /*** 进入任务回执页面
     * @param $param
     *  id   订单id
     * type 4 get
     */
    protected function receipt($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("id"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $info = db('Order')->alias('a')->join('v_tasks b','a.order_id = b.id')->field('a.id as oid,a.status as ostatus,a.number,b.type,a.implement_time,b.id as tid')->where('a.id',$param['id'])->find();
        $level = account_type(UID);
        if($level > 1){                           //判断是否为小V账号
            $map = array(
                'type' => $info['type'],
                'uid' => UID,
                'enable' => 1,
                'is_del' =>1
            );
            $account_count = db('smallvAccount')->where($map)->count();   //查询小V在该种类型任务所拥有的账号数量
            if($account_count>0){
                $user = db('smallvAccount')->field('id,account')->where($map)->select(); //账号信息
            }else{
                $user = array(         //没有账号情况下，账号信息默认为0
                    'id' => 0,
                    'account'=>0
                );
            }
        }else{
            $user = array(            //普通账号情况下，账号信息默认为0
                'id' => 0,
                'account'=>0
            );
        }
        $result['user'] = $user;
        $result['oid'] = $info['oid'];
        $result['num'] = $info['number'];
        if($info['ostatus'] == 2){          //已回执
            $result['state'] = 2;
        }elseif($info['ostatus'] == 1){
            $result['state'] = 1;           //未回执
        }
        $task = db('Tasks')->where('id',$info['tid'])->find();
        $result_type = explode(',',$task['result_type']);
        if(in_array(1,$result_type)){
            $result['have_pic'] = 1;        //必须上传截图
        }
        if(in_array(2,$result_type)){
            $result['have_url'] = 1;        //必须填写url
        }
        if(in_array(3,$result_type)){
            $result['have_writ'] = 1;       //必须填写文字
        }
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$result,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /*** 提交任务--微博
     * @param $param
     *  id  主订单id
     *  content  提交的信息
     * type 5 post
     */

    protected function put_task_weibo($param){
        if(!key_is_set(array("id",'content'),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        //自动验证
        $validate = new Validate([
            'content' => 'require',
            'id' => 'require',
        ]);
        if(!$validate->check($param)){
            return ['code'=>CodeError::CODEEOOR_NO_USERANDPASS_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NO_USERANDPASS_NAME]; //填写信息不完整
        }
        $orders = db('order')->where('id',$param['id'])->find();
        if(!empty($orders['submit_time'])){
            return ['code'=>CodeError::CODEEOOR_ABNORMAL_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ABNORMAL_NAME];   //防止多次请求
        }
        $task = db('tasks')->alias('a')->join('v_order b','b.order_id = a.id')->field('a.*')->where('b.id',$param['id'])->find();    //任务详情
        $result_type = explode(',',$task['result_type']); //必填项
        $where = array(
            'to_user_id' => UID,
            'oid' => $param['id']
        );
        $suborder = db('subOrder')->where($where)->select();      //该主订单下的子订单
        $sub_count = count($suborder);                             //子订单数
        $list = object_to_array($param['content']);
        foreach ($list as $key=>$value){
                if(empty($value['account']) || empty($value['account_id'])){
                    return ['code'=>CodeError::CODEEOOR_SACCOUNT_NOEXIST_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_SACCOUNT_NOEXIST_NAME];   //执行账号不存在
                }
                if(in_array(1,$result_type) && empty($value['result_pic'])){       //截图
                    return ['code'=>CodeError::CODEEOOR_NOORDERINFO_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NOORDERINFO_NAME];     //回执信息不完整
                }
                if(in_array(2,$result_type) && empty($value['result_url'])){      //链接
                    return ['code'=>CodeError::CODEEOOR_NOORDERINFO_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NOORDERINFO_NAME];     //回执信息不完整
                }
                if(in_array(3,$result_type) && empty($value['result_writ'])){      //文字
                    return ['code'=>CodeError::CODEEOOR_NOORDERINFO_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NOORDERINFO_NAME];     //回执信息不完整
                }
        }
        $count = count($list);                                       //统计上传任务数量
        $model = new OrderModel();
        $data = array(
            'suborder' => $suborder,                 //该主订单下的子订单
            'sub_count' => $sub_count,               //子订单数
            'arr' => $list,                            //上传回执信息
            'count' => $count,                        //统计上传任务数量
            'id' => $param['id'],                     //主订单ID
            'task_id' => $task['id'],                //任务ID
            'task_amount' => $task['amount']        //任务接单量
        );
        $res = $model->get_sub_weibo($data);
        if($res){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }


    /*** 提交任务--其他的任务
     * @param $param
     *  id  主订单id
     *  content  提交的信息
     * type 7 post
     */
    protected function put_task($param){
        if(!key_is_set(array("id",'content'),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $orders = db('order')->where('id',$param['id'])->find();
        if(!empty($orders['submit_time'])){
            return ['code'=>CodeError::CODEEOOR_ABNORMAL_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ABNORMAL_NAME];   //防止多次请求
        }
        //自动验证
        $validate = new Validate([
            'content' => 'require',
            'id' => 'require',
        ]);
        if(!$validate->check($param)){
            return ['code'=>CodeError::CODEEOOR_NO_USERANDPASS_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NO_USERANDPASS_NAME]; //填写信息不完整
        }
        $task = db('tasks')->alias('a')->join('v_order b','b.order_id = a.id')->field('a.*')->where('b.id',$param['id'])->find();    //任务详情
        $result_type = explode(',',$task['result_type']); //必填项
        $list = object_to_array($param['content']);
        $list = $list[0];
        if(in_array(1,$result_type) && empty($list['result_pic'])){       //截图
            return ['code'=>CodeError::CODEEOOR_NOORDERINFO_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NOORDERINFO_NAME];     //回执信息不完整
        }
        if(in_array(2,$result_type) && empty($list['result_url'])){      //链接
            return ['code'=>CodeError::CODEEOOR_NOORDERINFO_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NOORDERINFO_NAME];     //回执信息不完整
        }
        if(in_array(3,$result_type) && empty($list['result_writ'])){      //文字
            return ['code'=>CodeError::CODEEOOR_NOORDERINFO_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NOORDERINFO_NAME];     //回执信息不完整
        }
        $where = array(
            'to_user_id' => UID,
            'oid' => $param['id']
        );
        $suborder = db('subOrder')->where($where)->select();      //该主订单下的子订单

        $model = new OrderModel();
        $data = array(
            'suborder' => $suborder,                 //该主订单下的子订单
            'arr' => $list,                            //上传回执信息
            'id' => $param['id'],                     //主订单ID
        );

        $res = $model->get_sub($data);
        if($res){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }




    /***订单回执详情
     * @param mixed $param
     * id 主订单ID
     * type 5 get
     */
    protected function result($param){
        if(!key_is_set(array("id"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $map = array(
            'oid' => $param['id'],
            'to_user_id' => UID,
        );
        $list = db('subOrder')->field("account,result_pic,status,result_url,result_writ")->where($map)->select();

        if(!empty($list)){
            foreach ($list as $key=>$value){
                $list[$key]['result_pic'] = explode(',',$value['result_pic']);
            }
            $data = $list;
        }else{
            $data = null;
        }
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /***放弃任务
     * @param $param
     * id 主订单ID
     * type 6 post
     */
    protected function cancel_order($param){
        if(!key_is_set(array("id"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $order = db('order')->where('id',$param['id'])->find();

        if(empty($order)){
            return ['code'=>CodeError::CODEEOOR_ABNORMAL_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ABNORMAL_NAME];
        }
        if($order['status']!=1){
            return ['code'=>CodeError::CODEEOOR_ABNORMAL_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_ABNORMAL_NAME];
        }

        $model = new OrderModel();
        $res = $model->order_cancel($param);
        if($res){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];


    }



    /***新手任务列表              已取消
     * @param $param
     * novice 新手任务
     * type 2   get
     */
    protected function newbie_task($param){
        $map['v_order.to_user_id'] = UID;
        $map['v_order.is_novice'] = 1;
        $list = db('tasks')->alias('a')
            ->join('v_task_type b','a.type = b.id')          //任务类别
            ->join('v_order c','c.order_id = a.id')          //订单状态
            ->field('a.name as taskname,a.type as tasktype,a.category,a.novice,b.name as typename,c.status as ostatus,c.id as oid')->where($map)->select();
        foreach ($list as $k=>$v){
            $where['id']=array('in',$v['category']);
            $cate=db('category')->where($where)->select();   //查看任务行为（转、赞、评）
            $one = array_column($cate,'icon');
            foreach ($one as $key=>$val){
                $one[$key] = 'http://'.$_SERVER['SERVER_NAME']. '/uploads/category/'.$val;
            }
            $list[$k]['category']=$one;

            $other = $v['tasktype'];                            //任务类别
            $types = db('taskType')->where('id',$other)->find();
            $list[$k]['type'] = 'http://'.$_SERVER['SERVER_NAME']. '/uploads/type/'.$types['tp_icon'];

        }
        $data['count'] = count($list);                          //任务总数
        $data['list'] = $list;
        $map['status'] = 5;
        $data['more'] = db('order')->where($map)->count();     //已完成数量
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /***新手任务提交              已经取消
     * @param $param
     * id 订单id
     * pic 图片地址
     * type 2 post
     */
    protected function put_newbie($param){
        if(!key_is_set(array("id","pic"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        //自动验证
        $validate = new Validate([
            'id'  => 'require',
            'pic' => 'require',
        ]);
        if(!$validate->check($param)){
            return ['code'=>CodeError::CODEEOOR_NO_USERANDPASS_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NO_USERANDPASS_NAME]; //填写信息不完整
        }
        $param['pic'] = rtrim($param['pic'],',');
        $param['pic'] = explode(',',$param['pic']);
        $param['pic'] = serialize($param['pic']);
        $model = new OrderModel();
        $res = $model->add_resultpic($param);
        if($res){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }



    /***提交养号任务
     * @param $param
     * pic  执行截图
     * account  执行账号
     * account_id  执行账号
     * result_url 执行链接
     * type 4 post
     */
    protected function keep_task($param){
        if(!key_is_set(array("pic","account"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        //自动验证
        $validate = new Validate([
            'pic' => 'require',
            'account' => 'require'
        ]);
        if(!$validate->check($param)){
            return ['code'=>CodeError::CODEEOOR_NO_USERANDPASS_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NO_USERANDPASS_NAME]; //填写信息不完整
        }
        if(empty($param['account_id'])){
            return ['code'=>CodeError::CODEEOOR_SACCOUNT_NOEXIST_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_SACCOUNT_NOEXIST_NAME];   //执行账号不存在
        }
        if(empty($param['pic']) && empty($param['result_url'])){
            return ['code'=>CodeError::CODEEOOR_NOORDERINFO_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_NOORDERINFO_NAME];     //执行链接或图片不存在
        }
        $param['pic'] = rtrim($param['pic'],',');
        $param['pic'] = explode(',',$param['pic']);
        $param['pic'] = serialize($param['pic']);
        $model = new OrderModel();
        $res = $model->add_keep($param);
        if($res){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>null,'message'=>$message];
    }

}