<?php
namespace app\admin\model;
use think\Model;
/***管理员表***/
class AuthAdmin extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $createTime = 'addtime';
    protected $updateTime = false;
} 