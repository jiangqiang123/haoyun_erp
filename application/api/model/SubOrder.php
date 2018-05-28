<?php
namespace app\api\model;
use think\Model;

class SubOrder extends Model{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $createTime = 'addtime';
    protected $updateTime = false;




}