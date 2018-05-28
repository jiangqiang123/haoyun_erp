<?php
namespace app\api\model;
use think\Model;

class Opinion extends Model{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $createTime = 'addtime';
    protected $updateTime = false;

    //添加意见反馈
    public function add_opinion($data){
        $res = Opinion::create($data,true);
        return $res;
    }
}