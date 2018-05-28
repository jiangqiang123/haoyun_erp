<?php
namespace app\index\model;
use think\Model;

class Tasks extends Model
{

    #添加任务
    public function order_add($info)
    {
			

            if(Tasks::create($info,true))
            {
            	return true;die;

            }else{

            	return false;die;
            }

    }

}