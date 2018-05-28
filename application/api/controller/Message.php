<?php
namespace app\api\controller;
use think\Request;
use CodeError\CodeError;
use think\Validate;

class Message extends Home {
    public function notice(){
        $request = Request::instance();
        $param = $request->param();
        switch ($request->method()){
            case "POST":
                if(!empty($param)){
                    if($param['type'] == 1){

                    }else{
                        return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                    }
                }else{
                    return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                }
                break;

            case "GET":
                if(!empty($param)){
                    if($param['type'] == 1){                    //消息列表
                        return $this->message_list($param);
                    }elseif($param['type'] == 2){
                        return $this->message_info($param);     //消息详情
                    }elseif($param['type'] == 3){
                        return $this->new_message($param);      //最新消息
                    }elseif($param['type'] == 4){
                        return $this->messagePrompt($param);    //菜单栏消息提醒
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


    /***消息列表
     * @param $param
     * cate 消息类别
     * page 分页
     * type 1 get
     */
    protected function message_list($param){
        if(!key_is_set(array("cate","page"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        if($param['cate'] == 1){                                //订单消息
            $map = array(
                'v_message.type' => 1,
                'v_message.to_user_id' => UID,
            );
        }else{                                                  //系统消息
            $map = array(
                'type' => array('in','2,3,4'),
                'to_user_id' => UID,
            );
        }
        $count = db('message')->where($map)->count();
        $list = db('message')->where($map)->order('addtime desc')->paginate(10);
        $list = $list->items();
        foreach ($list as $key=>$val){
            $list[$key]['addtime'] = date("Y-m-d",$val['addtime']);
        }
        $data = array(
            'list'=>$list,
            'count' => $count,
        );
        $map['is_read'] = 0;
        $message = db('message')->where($map)->select();
        if(!empty($message)){
            foreach ($message as $k=>$v){
                $other = [
                    'is_read'=>1,
                ];
                db("message")->where('id',$v['id'])->update($other);
            }
        }
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /***消息详情
     * @param $param
     * id 消息ID
     * type 2 get
     */
    protected function message_info($param){
        if(!key_is_set(array("id"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $map = array(
            'id' => $param['id'],
            'to_user_id' => UID,
        );
        $info = db('message')->where($map)->find();
//        if($info['is_read']==0){
//            $other = [
//                'is_read'=>1,
//            ];
//            db("message")->where('id',$param['id'])->update($other);
//        }
        $info['addtime'] = date("Y-m-d",$info['addtime']);
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$info,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /***最新消息提示
     * @param $param
     * @return array
     * type 3 get
     */
    protected function new_message($param){
        $map = array(
            'type' => 1,
            'to_user_id' => UID
        );
        $order = db('message')->where($map)->order('addtime desc')->find();                 //最新订单消息
        if(!empty($order)){
            $order['addtime'] = date("Y-m-d",$order['addtime']);
            $map['is_read'] = 0;
            $order['counts'] = db('message')->where($map)->count();
        }

        $maps = array(
            'type' => array('in','2,3,4'),
            'to_user_id' => UID,
        );
        $system = db('message')->where($maps)->order('addtime desc')->find();               //最新任务消息
        if(!empty($system)){
            $system['addtime'] = date("Y-m-d",$system['addtime']);
            $maps['is_read'] = 0;
            $system['counts'] = db('message')->where($map)->count();
        }

        $data = array(
            'order' => $order,
            'system' => $system
        );
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /**菜单栏消息提醒
     * @function
     * @param $param
     * @return array
     */
    protected function messagePrompt($param){
        $map = [
            'to_user_id' => UID,
            'is_read' => 0,
        ];
        $info = db('message')->where($map)->find();
        if($info){
            $data = 1;
        }else{
            $data = 0;
        }
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }
}