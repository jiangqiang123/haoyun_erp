<?php
namespace app\index\model;
use think\Model;
//use app\index\model\Member as Mem;
/***实名认证表***/
class MemberRealname extends Model
{
    # 实名认证审核 
	public function do_realname($save)
    {
            $model=MemberRealname::get($save['id']);
            if($model->status != 1)
            {
                return false;die;
            }
            $uid=$model->uid;
            $model->status=$save['status'];
            $model->lose=$save['lose'];
            $model->overtime=time();
            // if($save['status'] ==2)
            // {
            //     $model->startTrans();
            //     $res1=$model->save();
            //     $res2=Mem::where(array('uid'=>$uid))->update(array('is_realname'=>1));
            //     if( $res1 && $res2)
            //     {
            //         $model->commit();
            //         return true;
            //     }else{
            //         $model->rollback();
            //         return false;
            //     }
            // }else
            if($model->save()){
                return true;
            }else{
               return false; 
            }

    }
}   
