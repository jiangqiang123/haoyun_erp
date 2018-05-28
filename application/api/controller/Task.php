<?php
namespace app\api\controller;
use app\api\model\Order;
use think\Request;
use CodeError\CodeError;
use think\Validate;
use think\Db;

/*
 * 任务模块------>任务列表，接单等
 *@type请求类别
 *@param请求参数（数组）
 *
 */
class Task extends Home {
    public function operation(){
        $request = Request::instance();
        $param= $request->param();
        switch ($request->method()){
            case 'POST':
                if(!empty($param)){
                    if($param['type']==1){

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
                        return $this->tasktype($param);         /***任务大厅可选任务种类***/
                    }elseif ($param['type']==2){
                        return $this->task_index_list($param);  /***首页任务列表***/
                    }elseif ($param['type']==3) {
                        return $this->task_hall_list($param);   /***任务大厅列表***/
                    }elseif($param['type']==4) {
                        return $this->taskinfo($param);         /***任务详情***/
                    }elseif($param['type']==5){
                        return $this->keep_list($param);        /***养号任务界面***/      //功能取消
                    }elseif($param['type']==6){
                        return $this->typesort($param);         /****首页任务种类***/
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


    /***任务大厅---任务类别
     * @return array
     * type 1     get
     */
    protected function tasktype($param){
        $tasktype = db('taskType')->field('id,name')->where('status',1)->select();
        array_unshift($tasktype, [ 'id' => '0','name' => '查看全部',]);
         if ($tasktype){
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>$tasktype,'message'=>$message];
    }


    /***首页任务类型
     * @param $param
     * @return array
     *type 6   get
     */
    protected function typesort($param){
        $task = db('taskType')->field('id,name')->where('status',1)->order('sort asc')->limit(4)->select();
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$task,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /***
     * @ 首页任务列表
     *
     * type 2  get
     */
    protected function task_index_list($param){
        $member = db('member')->where('uid',UID)->find();
        $where=array(
//            'v_tasks.surplus'=>['>',0],          //剩余数量
//            'v_tasks.active_time'=>['>',time()],//有效时间
            'v_tasks.status' => 2,               //任务中
            'v_tasks.novice' => 2,               //小v任务
            'v_tasks.uid' => array('in','0,'.$member['group']),   //所属分组
        );
        $level = account_type(UID);
        if($level==1){
            $where['v_tasks.nature'] = 2;
            $where['v_tasks.recommend'] = 1;     //推荐任务
            $cate = db('tasks')->alias('a')->join('v_task_type b','a.type = b.id')->field('a.*,b.name as typename')->where($where)->where('a.number != a.amount')->order('a.addtime desc')->limit(20)->select();
        }else{
            $where['v_tasks.nature'] = ['<=',$level];  //直显示不超过当前账号等级的任务
            $map['v_order.to_user_id'] = UID;
            $info = db('tasks')->alias('a')->join('v_order b','b.order_id = a.id')->field('a.*')->where($where)->where($map)->select();

            if(!empty($info)){          //去除已接单的任务
                $one = array_column($info,'id');
                $where['v_tasks.id'] = array("not in",$one);
                $where['v_tasks.recommend'] = 1;     //推荐任务
                $cate = db('tasks')->alias('a')->join('v_task_type b','a.type = b.id')->field('a.*,b.name as typename')->where($where)->where('a.number != a.amount')->order('a.addtime desc')->limit(20)->select();

            }else{
                $where['v_tasks.recommend'] = 1;     //推荐任务
                $cate = db('tasks')->alias('a')->join('v_task_type b','a.type = b.id')->field('a.*,b.name as typename')->where($where)->where('a.number != a.amount')->order('a.addtime desc')->limit(20)->select();
            }
        }

        foreach ($cate as $key =>$value){
            $cate[$key]['content'] = unserialize($cate[$key]['content']);
            $cate[$key]['online_time'] = $value['active_time']-$value['addtime'];
            $maps['id']=array('in',$value['category']);
            $arr = db('category')->where($maps)->select();   //查看任务行为（转、赞、评）类型
            $one = array_column($arr,'icon');
            foreach ($one as $k=>$v){
                $one[$k] = 'http://'.$_SERVER['SERVER_NAME']. '/uploads/category/'.$v;
            }
            $cate[$key]['category']=$one;

            $other = $value['type'];                            //任务类别
            $types = db('taskType')->where('id',$other)->find();
            $cate[$key]['type'] = 'http://'.$_SERVER['SERVER_NAME']. '/uploads/type/'.$types['tp_icon'];
        }
        $data['list'] = $cate;
//        $data['count'] = db('tasks')->where($where)->count();
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /***
     * 任务大厅
     * @ $param['cate'] 任务类型
     * @ $param['sort'] 排序
     * @ $param['page'] 分页
     * type 3  get
     *
     */
    protected function task_hall_list($param){

        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("cate","sort","page"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];
        }
        $sort = '';
        if($param['sort'] == 2){
            $sort = 'a.price desc';
        }elseif($param['sort'] == 1){
            $sort = 'a.addtime desc';
        }elseif($param['sort'] == 3){
            $sort = 'a.amount desc';
        }
        $member = db('member')->where('uid',UID)->find();
        $where=array(
//            'v_tasks.surplus'=>['>',0],          //剩余数量
//            'v_tasks.active_time'=>['>',time()],//有效时间
            'v_tasks.status' => 2,               //任务中
            'v_tasks.novice' => 2,               //非新手任务
            'v_tasks.uid' => array('in','0,'.$member['group']),   //所属分组
        );
        $level = account_type(UID);         //查看账号等级
        if($level==1){
            $where['v_tasks.nature'] = 2;
            $cate = db('tasks')->alias('a')->join('v_task_type b','a.type = b.id')->field('a.*,b.name as typename')->where($where)->where('a.number != a.amount')->order('a.addtime desc')->limit(20)->select();
            $count = db('tasks')->alias('a')->join('v_task_type b','a.type = b.id')->field('a.*,b.name as typename')->where($where)->where('a.number != a.amount')->count();
        }else{
            $where['v_tasks.nature'] = ['<=',$level];  //直显示不超过当前账号等级的任务
            if($param['cate'] != 0){
                $where['v_tasks.type'] = $param['cate'];       //任务类型
            }
            $map['v_order.to_user_id'] = UID;
            $info = db('tasks')->alias('a')->join('v_order b','b.order_id = a.id')->field('a.*')->where($where)->where($map)->order("a.addtime desc")->select();//查询已接过的任务
            if(!empty($info)){
                $one = array_column($info,'id');
                $where['v_tasks.id'] = array('not in',$one);
                $cate = db('tasks')->alias('a')->join('v_task_type b','a.type = b.id')->field('a.*,b.name as typename')->where($where)->where('a.number != a.amount')->order($sort)->paginate(20);
                $count = db('tasks')->alias('a')->join('v_task_type b','a.type = b.id')->field('a.*,b.name as typename')->where($where)->where('a.number != a.amount')->count();
            }else{
                $cate = db('tasks')->alias('a')->join('v_task_type b','a.type = b.id')->field('a.*,b.name as typename')->where($where)->where('a.number != a.amount')->order($sort)->paginate(20);
                $count = db('tasks')->alias('a')->join('v_task_type b','a.type = b.id')->field('a.*,b.name as typename')->where($where)->where('a.number != a.amount')->count();
            }
        }


        $cate =$cate->items();
        foreach ($cate as $key =>$value){
            $cate[$key]['content'] = unserialize($value['content']);
            $cate[$key]['online_time'] = $value['active_time']-$value['addtime'];
            $maps['id']=array('in',$value['category']);
            $arr = db('category')->where($maps)->select();   //查看任务行为（转、赞、评）类型
            $one = array_column($arr,'icon');
            foreach ($one as $k=>$v){
                $one[$k] = 'http://'.$_SERVER['SERVER_NAME']. '/uploads/category/'.$v;
            }
            $cate[$key]['category']=$one;

            $other = $value['type'];                            //任务类别
            $types = db('taskType')->where('id',$other)->find();
            $cate[$key]['type'] = 'http://'.$_SERVER['SERVER_NAME']. '/uploads/type/'.$types['tp_icon'];
        }
        $data['list'] = $cate;                  //任务详细

        $data['count'] = $count;               //进行中的任务总数

        $any['status'] = array('in','1,2');
        $any['to_user_id'] = UID;
        $any['is_novice'] = array('in','0,2');
        $data['numbers'] = db('order')->where($any)->count();//进行中的订单

        $others = array(
            'to_user_id' => UID,
            'status' => 3,
        );
        $lists = db('subOrder')->where($others)->select();
        $money = array_column($lists,'price');
        $data['money'] = round(array_sum($money),1);

        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /***打开任务时详情
     * @param $param请求参数
     * @id  任务id
     * type 4  get
     */
    protected function taskinfo($param){
        //判断 param 所带参数，字段是否存在，
        if(!key_is_set(array("id"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $info = db('tasks')->where('id',$param['id'])->find();
        $other = array(
            'id' => ['in',$info['category']],
        );
        $category = db('category')->where($other)->select();
        $one = array_column($category,'name');
        $info['category'] = $one;
        $map = array(
            'to_user_id' => UID,
            'order_id' => $param['id'],
        );
        $order = db("order")->where($map)->find();
//        $redis = new \Redis();
//        $redis->connect('127.0.0.1',6379);
        if($order){
//            $message = CodeError::CODEEOOR_ABNORMAL_NAME;//防止按物理键返回
//            $code = CodeError::CODEEOOR_ABNORMAL_CODE;
            $info['state'] = 0;           //--->已领取
        }else{
            $info['state'] = 1;                                     //---->可领取
        }
        if($info){
            $info['content'] = unserialize($info['content']);
            $info['ostatus'] = 1;                                   //用于提交按钮
            $info['surplus'] = $info['number']-$info['amount'];  //剩余数量
            $info['online_time'] = $info['active_time']-$info['addtime'];
            $message = CodeError::CODESECCESS_COMMON_PARAM_NAME; //提交成功
            $code = CodeError::CODESECCESS_COMMON_PARAM_CODE;
        }else{
            $message = CodeError::CODEEOOR_COMMON_PARAM_NAME;//没有数据时出现,提交失败-----> 一般不会出现
            $code = CodeError::CODEEOOR_COMMON_PARAM_CODE;
        }
        return ['code'=>$code,'data'=>$info,'message'=>$message];
    }


    /***养号任务页面
     *@param $param请求参数
     * type 5 get
     */
    protected function keep_list($param){
        $map = array(
            'uid' => UID,
            'enable' => 1,    //启用状态
            'is_del' => 1,
        );
        $user = db('smallvAccount')->where($map)->select();
        $arr = array_column($user,'account');
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$arr,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }




}