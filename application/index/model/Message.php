<?php
namespace app\index\model;
use think\Model;

class Message extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $createTime = 'addtime';
    protected $updateTime = false;

    //添加消息 审核完成后添加消息 任务消息
    public function madd($oid,$reason,$to_user_id,$status=1)
    {
        $info=array(
            'oid'=>$oid,
            'reason'=>$reason,
            'to_user_id'=>$to_user_id,
            'status'=>$status
            );

        if(Message::create($info,true))
        {
            return true;
        }else{
            return false;
        }
    }


}