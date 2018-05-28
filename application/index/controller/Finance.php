<?php
namespace app\index\controller;
/********财务管理控制器*******/
use app\index\controller\Pub;
use app\index\model\Bill;

class Finance extends Pub
{
    #账单列表
    public function export()
    {
        $time=date('Y-m',time());
        $list=db('bill')->where('time','neq',$time)->order('id desc')->select();
        $this->assign('list',$list);
 		return $this->fetch();
    }


    #账单详情/导出数据
    public function excel_export()
    {

        if(IS_POST){
            $info=input('post.');
            if(!$bill=db('bill')->where(array('id'=>$info['id']))->find())
            {
                $date['state']='n';
                $date['msg']='无效操作';
                return $date;die;
            }

            if($bill['status']==0 && $info['status'] !=1)
            {
                $date['state']='n';
                $date['msg']='无效操作';
                return $date;die;
            }

            if($bill['status']==1 && $info['status'] !=2)
            {
                $date['state']='n';
                $date['msg']='无效操作';
                return $date;die;
            }

            if(Bill::where(array('id'=>$info['id']))->update(array('status'=>$info['status'])))
            {
                $date['state']='y';
                $date['msg']='操作成功';
                return $date;die;
            }else{
                $date['state']='n';
                $date['msg']='无效操作';
                return $date;die;
            }

        }

        $id=input('id');
        $bill=db('bill')->where(array('id'=>$id))->find();
        $where['auditing_time']=array('between',array($bill['start'],$bill['end']));
        $where['status']=3;
        $member=db('member')->select();
        //会员表联查银行卡表 
        $arr=array();
        foreach ($member as $k => $v){
            $a=db('sub_order')->where($where)->where('to_user_id',$v['uid'])->sum('price');
            if($a>0){
                $arr[$v['uid']]=$a;  
            }
            
        }
        $this->assign('arr',$arr);
        $this->assign('bill',$bill);
        return $this->fetch();

    }

}
