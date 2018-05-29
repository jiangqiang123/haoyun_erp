<?php
namespace app\admin\model;
use think\Model;
/***实名认证表***/
class Article extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $createTime = 'addtime';
    protected $updateTime = false;
} 