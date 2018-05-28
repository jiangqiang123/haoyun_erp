<?php
use app\index\model\Member as Mem;
use app\index\model\Message;
use app\index\model\SmallvAccount;


// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件


/***
*amin function 
*后台公共方法
***/


	#加密方法
	function xv_admin_md5($str, $key = 'SvAdmin'){
		return '' === $str ? '' : md5(sha1($str) . $key);
	}


	#开启 禁用
	function int_to_string(&$data,$map=array('status'=>array(1=>'正常',-1=>'删除',0=>'禁用',2=>'未审核'))) {
        if($data === false || $data === null ){
            return $data;
        }
        $data = (array)$data;
        foreach ($data as $key => $row){
            foreach ($map as $col=>$pair){
                if(isset($row[$col]) && isset($pair[$row[$col]])){
                    $data[$key][$col.'_text'] = $pair[$row[$col]];
                }
            }
        }
        return $data;
    }


    /*
    *生成订单号
    */
    function makeOrderSn($uid) {
        return mt_rand(10,99)
        . sprintf('%010d',time() - 946656000)
        . sprintf('%03d', (float) microtime() * 1000)
        . sprintf('%03d', (int) $uid % 1000);
    }


    /*
    *生成激活码
    */
    function GetfourStr()

    {
        $num=mt_rand(1,6);
        $eng=array('a','b','c','d','e','f','h','i','j','k','m','n','p','r','s','t','u','v','w','x','y');
        if($num==1){
            return mt_rand(0,9).$eng[mt_rand(0,20)]. mt_rand(0,9).$eng[mt_rand(0,20)];
        }elseif($num==2){
            return $eng[mt_rand(0,20)].mt_rand(0,9).$eng[mt_rand(0,20)].mt_rand(0,9);
        }elseif($num==3){
            return $eng[mt_rand(0,20)].$eng[mt_rand(0,20)].mt_rand(0,9).mt_rand(0,9);
        }elseif($num==4){
            return mt_rand(0,9).mt_rand(0,9).$eng[mt_rand(0,20)].$eng[mt_rand(0,20)];
        }elseif($num==5){
            return $eng[mt_rand(0,20)].mt_rand(0,9).mt_rand(0,9).$eng[mt_rand(0,20)];
        }elseif($num==6){
            return mt_rand(0,9).$eng[mt_rand(0,20)].$eng[mt_rand(0,20)].mt_rand(0,9);
        }else{

        }

    }


    /*
    *任务成功将钱所得转入会员余额中
    *@uid  所得的会员的id号
    *@num  所得的余额数量
    */

    function pay_for($uid,$num)
    {
        if(Mem::where(array('uid'=>$uid))->setInc('balance',$num)){
            return true;
        }else{
            return false;
        }
    }


    /*
    *金币或经验的增减
    *@uid  所得的会员的id号  int
    *@num  所得的增减的数量数量 int
    *@is_add  默认1为加    int 
    *@type  加减的对象   1为经验experience  2为金币gold;  int
    */
    function eg_change($uid,$num,$type,$is_add=1)
    {   
        if($type==1)
        {
            $field='experience';
        }else if($type ==2){
            $field='gold';
        }else{
            return false;die;
        }

        if($is_add==1){
            //累加
            if(Mem::where(array('uid'=>$uid))->serInt($field,$num)){
                return true;
            }else{
                return false;
            }
        }else{
            //累减
            $where['uid']=$uid;
            $where[$field]=array('ELT',$num);
            if($member=db('member')->where($where)->find())
            {
                $num=$member[$field];
            }
            if(Mem::where(array('uid'=>$uid))->setDec($field,$num)){
                return true;
            }else{
                return false;
            }
        }
    }


    /*
    *余额日志的存储
    *@action 操作名称 事由 string
    *@to_uid  余额流动对象  int
    *@type  
    *****
    */

    // function points_log($action)
    // {

    // }


    /**
     *查询账号今日已完成几次养号任务
     *@uid 要查询会员id的账号的数组 
     *
     *  
     *
     */

    function select_raise($uid)
    {
        $nowtime=time();
        $day=date('Y-m-d',time());
        $daytime=strtotime($day);
        $where['addtime']=array('between',array($daytime,$nowtime));
        $where['to_user_id']=$uid;
        $where['status']=5;
        $arr=array();
        if(db('order')->where($where)->count() == 1)
        {
            return true;
        }else{
            return false;
        }


    }

    //获取本周时间
    function this_week(){
        $zhou=(int)date('w',time());
        $times=date('y-m-d',time());
        $timess=strtotime($times);
        if($zhou == 0){
            $aa=(int)$timess-6*24*60*60;
        }else{
            $aa=(int)$timess-($zhou-1)*24*60*60;
        }
        return $aa;
    }


 
    //查询当天完成20任务数量  累计加经验和金币
    function day_order($uid,$num)
    {
        $nowtime=time();
        $day=date('Y-m-d',time());
        $daytime=strtotime($day);
        $where['auditing_time']=array('between',array($daytime,$nowtime));
        $where['status']=3;
        $where['to_user_id']=$uid;
        $count=db('sub_order')->where($where)->count()+0;
        if(($count < 20) && ($count+$num>= 20)){
            Mem::where(array('uid'=>$uid))->setInc('experience',5);
            Mem::where(array('uid'=>$uid))->setInc('gold',5);
            return true;
        }else{
            return false;
        }
    }

    //查询当月的600完成量    累计加经验和金币
    function month_order($uid,$num)
    {
        $nowtime=time();
        $weektime=this_week();
        $where['auditing_time']=array('between',array($weektime,$nowtime));
        $where['status']=3;
        $where['to_user_id']=$uid;
        $count=db('sub_order')->where($where)->count()+0;
        if(($count < 600) && ($count+$num>= 600)){
            Mem::where(array('uid'=>$uid))->setInc('experience',300);
            Mem::where(array('uid'=>$uid))->setInc('gold',300);
            return true;
        }else{
            return false;
        }
    }


    //判断封号 uid 会员id  $num减少的信用分
    function close($uid)
    {
        $member=mem::get($uid);
        if($member->credit< 90 && $member->credit>=80){
            //封一天
            if($member->mem_status==0){
                //封号中
                $member->seal_time=$member->seal_time+60*60*24; 
            }else{
                $member->seal_time=time()+60*60*24;
                $member->mem_status=0;
            }
            $a=$member->save();
            if($a){
                $add=array('to_user_id'=>$uid,'type'=>4,'status'=>0,'reason'=>'因信用分少于90,封号1天!');
                Message::create($add,true);
            }

        }else if($member->credit< 80 && $member->credit>=70){
            //封3天
            if($member->mem_status==0){
                //封号中
                $member->seal_time=$member->seal_time+60*60*24*3; 
            }else{
                $member->seal_time=time()+60*60*24*3;
                $member->mem_status=0;
            }
            $a=$member->save();
            if($a){
                $add=array('to_user_id'=>$uid,'type'=>4,'status'=>0,'reason'=>'因信用分少于80,封号3天!');
                Message::create($add,true);
            }
        }else if($member->credit< 70 && $member->credit>=60){
            //封7天
            if($member->mem_status==0){
                //封号中
                $member->seal_time=$member->seal_time+60*60*24*7; 
            }else{
                $member->seal_time=time()+60*60*24*7;
                $member->mem_status=0;
            }
            $a=$member->save();
            if($a){
                $add=array('to_user_id'=>$uid,'type'=>4,'status'=>0,'reason'=>'因信用分少于70,封号7天,若少于60分将永久失去小V资格!');
                Message::create($add,true);
            }
        }else if($member->credit<60){
            //失去小V资格
            $member->mem_status=2;
            $member->gold=0;
            $member->experience=0;
            $a=$member->save();
            if($a){
                // SmallvAccount::where(array('uid'=>$uid))->update(array('uid'=>0,'status'=>1));
                $add=array('to_user_id'=>$uid,'type'=>4,'status'=>0,'reason'=>'因信用分少于60,您已永久失去小V资格！');
                Message::create($add,true);
            }

        }
    }



