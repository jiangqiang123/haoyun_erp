<?php
namespace app\index\model;
use think\Model;

class Category extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $createTime = 'addtime';
    protected $updateTime = false;


    // public function addType($data){
    //     $type = new TaskType([
    //         'name' => $data['name'],
    //         'sort' => $data['sort'],
    //         'status' => $data['status']
    //     ]);
    //     $res['ret'] = $type->save(); //返回影响记录行数
    //     return $res;
    // }

    // public function editType($data){
    //     $type = new TaskType();
    //     $res = $type->save([
    //         'name' => $data['name'],
    //         'sort' => $data['sort'],
    //         'status' => $data['status']
    //     ],['id'=>$data['id']]);
    //     return $res;
    // }

}