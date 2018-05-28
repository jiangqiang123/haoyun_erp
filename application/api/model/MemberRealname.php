<?php
namespace app\api\model;
use think\Model;

class MemberRealname extends Model
{
    //提交实名认证
    public function add_realname($data){
        $member = new MemberRealname();
        $model = new Member();
        $member->startTrans();
        $res = $member->save([
            'realname' => $data['realname'],
            'cardnum' => $data['cardnum'],
            'cardimg' => $data['cardimg'],
            'authtime' => time(),
            'status' => 1,
            'uid' => UID,
        ]);
        $ress = $model->save([
            'apply' => 1,
            'apply_time' => time(),
        ],['uid'=>UID]);
        if($res && $ress){
            $member->commit();
            return true;
        }else{
            $member->rollback();
            return false;
        }
    }

    //修改实名认证
    public function edit_realname($data){
        $member = new MemberRealname();
        $model = new Member();
        $member->startTrans();
        $res = $member->save([
            'realname' => $data['realname'],
            'cardnum' => $data['cardnum'],
            'cardimg' => $data['cardimg'],
            'status' => 1
        ],['uid'=>UID]);
        $ress = $model->save([
            'apply' => 1,
            'apply_time' => time(),
        ],['uid'=>UID]);
        if($res && $ress){
            $member->commit();
            return true;
        }else{
            $member->rollback();
            return false;
        }
    }




}