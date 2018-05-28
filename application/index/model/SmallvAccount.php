<?php
namespace app\index\model;
use think\Model;

class SmallvAccount extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $createTime = 'addtime';
    protected $updateTime = false;

    #添加账号
    public function addAccount($data)
    {

        if(SmallvAccount::create($data,true))
        {
            return true;
        }else{
            return false;
        }
    }

    #修改账号
    public function editAccount($data)
    {
        $model = new SmallvAccount();
        // $res = $model->save([
            // 'account' => $data['account'],
            // 'pwd' => $data['pwd'],
            // 'enable' => $data['enable'],
            // 'type'=> $data['type'],
            // 'apple'=>$data['apple']
        // ],['id'=>$data['id']]);
		
		$res=$model->where(array('id'=>$data['id']))->update($data);
        return $res;
    }

}