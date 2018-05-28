<?php
namespace app\index\model;
use think\Model;

class DataLog extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $createTime = 'addtime';
    protected $updateTime = false;

    // public function log_add($data){
    //     $info['to_uid']=$data[1];
    //     $info['action']='任务完成';
    //     $info['points']=$data[2];

    //     if(PointLog::create($info,true))
    //         {
    //             return true;die;
    //         }else{
    //             return false;die;
    //         }

    // }


}