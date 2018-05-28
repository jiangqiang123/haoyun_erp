<?php
namespace app\index\model;
use think\Model;

class SmallvGrade extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $createTime = 'addtime';
    protected $updateTime = false;

    #添加等级
    public function addGrade($data){
        $model = new SmallvGrade();
        $res = $model->save([
            'name' => $data['name'],
            'experience' => $data['experience'],
            'sort' => $data['sort'],
            'sv_icon'=> $data['sv_icon'],
            'status' => $data['status']
        ]);
        return $res;
    }

    #修改等级
    public function editGrade($data){
        $model = new SmallvGrade();
        $res = $model->save([
            'name' => $data['name'],
            'experience' => $data['experience'],
            'sort' => $data['sort'],
            'status' => $data['status'],
            'sv_icon'=> $data['sv_icon'],
        ],['id'=>$data['id']]);
        return $res;
    }
}