<?php
namespace app\index\controller;

use app\index\controller\Pub;
use app\index\model\Config;
/**
*@平台管理控制器
*/
class Platform extends Pub
{

	#站点设置
    public function config_setting()
    {
        if(IS_POST){
            $info=input('post.');
            $list=array();
            foreach ($info as $k => $v) {
                $list[]=array('id'=>$k,'value'=>$v);
            }
            $conf = new Config;
            $a=$conf->saveAll($list);
            if($conf->saveAll($list)){
                $date['state']='y';
                $date['msg']='设置成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='设置失败';
                return json($date);die;
            }
        }

        $config=db('config')->where('id','neq',62)->select();
		// dump($config);die;
        $this->assign('config',$config);
        return $this->fetch();


    }
	
	
	#意见反馈列表
	public function opinion()
	{
		$list=db("opinion")->order("addtime desc")->paginate(10);
		$page = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $page);
		return $this->fetch();

	}
	
	
	#查看意见详情
	public function opinion_read()
	{
		 $info=input();
		$list=db("opinion")
			  ->alias('a')
			  ->join('v_member b','a.uid = b.uid')
			  ->where(['a.id'=>$info['id']])
			  ->field("a.*,b.mobile,b.nickname")
			  ->find();
		
		if(empty($list))
		{
			return $this->error("暂无数据");
		}else{
			if($list['is_read']==1){
				db("opinion")->where(['id'=>$info['id']])->update(['is_read'=>2]);
			}
			$this->assign('list', $list);
			// dump($list);die;
			return $this->fetch();
		}
		
		
	}

}
