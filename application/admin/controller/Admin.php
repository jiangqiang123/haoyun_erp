<?php
namespace app\admin\controller;

use app\admin\controller\Pub;

/**
**管理员控制器***
**/
class Admin extends Pub
{

	#管理员列表
    public function admin_list()
    {
        //是否启用和禁止
        if($_POST){
            if(db('auth_admin')->where(array('uid'=>$_POST['uid']))->update($_POST)){
                $date['state']='y';
                $date['msg']='修改成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='修改失败';
                return json($date);die;
            }
        }
        $join = [
            ['v_auth_group_access b','a.uid=b.uid'],
            ['auth_group c','c.id=b.group_id'],
        ];

        $member=db('auth_admin')->alias('a')->join($join)->field('a.*,c.title as ctitle,c.id as cid')->order('a.uid asc')->select();
        $count=count($member);
        $this->assign('count',$count);
        $this->assign('member',$member);
        return $this->fetch();
    }

    #管理员添加
    public function admin_add()
    {
        if($_POST){
            //会员的添加
            $info=input();
            if($info['username'] == ''){
                $date['state']='n';
                $date['msg']='账号不为空';
                return json($date);die;
            }
            if($info['password'] ==''){
                $date['state']='n';
                $date['msg']='密码不为空';
                return json($date);die;
            }
            if($info['password'] != $info['pwd']){
                $date['state']='n';
                $date['msg']='密码不一致';
                return json($date);die;
            }

            $data=array(
                    'username' => $info['username'],
                    'password' => xv_admin_md5($info['password']),
                    'remark' => $info['remark'],
                    'addtime' => time()
                );

            if($id=db('auth_admin')->insertGetId($data)){
                $list['group_id']=$info['group_id'];
                $list['uid']=$id;
                db('auth_group_access')->insert($list);
                $date['state']='y';
                $date['msg']='添加成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='添加失败';
                return json($date);die;
            }
        }   
        //用户组选择
        $gorup=db('auth_group')->where(array('status'=>1))->select();
        $this->assign('gorup',$gorup);  
		return $this->fetch();
    }

    #管理员的修改
    public function admin_edit()
    {
        if($_POST){
            $input=$_POST;
            $info=array(
                    'username'=> $input['username'],
                    'remark'  => $input['remark'],
                );         
            if($input['password']!='' &&  $input['password']== $input['pwd']){
                $info['password']=xv_admin_md5($input['password']);
            }

            if(db('auth_admin')->where(array('uid'=>$input['id']))->update($info)){
                db('auth_group_access')->where(array('uid'=>$input['id']))->update(array('group_id'=>$input['group_id']));
                $date['state']='y';
                $date['msg']='成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='修改失败';
                return json($date);die;
            }
        }
        $id=input('id');
        if(!$member=db('auth_admin')->where(array('uid'=>$id))->find()){
            return $this->error('暂无数据','','',1);
        }

        $group=db('auth_group_access')->where(array('uid'=>$member['uid']))->find();
        $list=db('auth_group')->where(array('status'=>1))->select();
        $this->assign('group',$group); 
        $this->assign('list',$list);    
        $this->assign('member',$member); 
        return $this->fetch();
    }

    #管理员的删除
    public function admin_del()
    {
        if(IS_POST){
            $uid=input('uid');
            if($uid != 1){
                $where['uid']=array('in',$uid);
                if(db('auth_admin')->where($where)->delete()){
                    //删除v_auth_group_access
                    db('auth_group_access')->where($where)->delete();
                    $date['state']='y';
                    $date['msg']='成功';
                    return json($date);die;
                }else{
                    $date['state']='n';
                    $date['msg']='删除失败';
                    return json($date);die;
                }
            }
        }  

        $this->error('无效操作','','',1);
    }


    #用户组的添加
    public function group_add()
    {
    	if(IS_POST){
    		//角色的添加
    		$info=input();
            if($info['title']==''){
                $date['state']='n';
                $date['msg']='用户组名称不为空';
                return json($date);die; 
            }

            if(db('auth_group')->where(array('title'=>$info['title']))->find()){
                $date['state']='n';
                $date['msg']='该账号已存在';
                return json($date);die;
            }

            if(isset($info['rules'])){
                $info['rules']=implode(',', $_POST['rules']);
            }else{
                $info['rules']='';
            } 

    		if(db('auth_group')->insert($info)){
    			$date['state']='y';
                $date['msg']='添加成功';
                return json($date);die;
    		}else{
    			$date['state']='n';
                $date['msg']='添加失败';
                return json($date);die;
    		}
    	}
        $create = new \admin\Tree();
        $node=db('auth_rule')->select();
        $node=$create->create($node);
        $this->assign('node',$node);
    	return $this->fetch();
    }

    #用户组的列表
    public function group_list()
    {
        //是否启用和禁止
        if(IS_POST){  
            if(db('auth_group')->where(array('id'=>$_POST['id']))->update($_POST)){
                $date['state']='y';
                $date['msg']='修改成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='修改失败';
                return json($date);die;
            }
        }
        $group=db('auth_group')->select();
        $count=count($group);
        $this->assign([
            'count'  => $count,
            'group'  => $group
       ]);
    	return $this->fetch();
    }

    #给用户组分配权限
    public function group_edit()
    {
        if($_POST){
            //dump($_POST);die;
            if(isset($_POST['node_id'])){
                $info['rules']=implode(',', $_POST['node_id']);
            }else{
                $info['rules']='';
            } 
            $info['title']=$_POST['title'];
            $id=$_POST['id'];
            if(db('auth_group')->where(array('id'=>$id))->update($info)){
                $date['state']='y';
                $date['msg']='设置成功';
                return json($date);die;
            }else{
                $date['state']='y';
                $date['msg']='设置失败';
                return json($date);die;
            }

        }
        if(!$role=db('auth_group')->where('id',input('id'))->find())
        {
            $this->error('暂无数据','','',1);
        }
        $create = new \admin\Tree();
        $node=db('auth_rule')->select();
        $node=$create->create($node);
        $data=array();//存放用户的相应的权限
        foreach ($node as $key => $value) {
            if($role['rules'] == ''){
                //没有权限
                $value['access']=0;
            }else{
                $rules=explode(',', $role['rules']);
                if(in_array($value['id'],$rules)){
                   $value['access']=1; 
                }else{
                    $value['access']=0;
                }
            }
            $data[]=$value;

        }
        $this->assign('node',$data);
        $this->assign('role',$role);
        return $this->fetch();
    }

    #用户组删除
    public function group_del()
    {
        if($_POST){
            $id=input('id');
            $where['id']=array('in',$id);
            if(db('auth_group_access')->where($where)->find()){
                $date['state']='n';
                $date['msg']='该用户组有管理员在使用';
                return json($date);die;
            }

            if($id != 1)
            {
                if(db('auth_group')->where($where)->delete())
                {
                    $date['state']='y';
                    $date['msg']='成功';
                    return json($date);die;
                }else{
                    $date['state']='n';
                    $date['msg']='删除失败';
                    return json($date);die;
                }
            }else{
                $date['state']='n';
                $date['msg']='该用户组不能删除';
                return json($date);die;
            }
        }   

        return $this->error('无效操作','','',1);
    }

    #权限列表 节点表
    public function rule_list()
    {
        $create = new \admin\Tree();
        $node=db('auth_rule')->select();
        $node=$create->create($node);
        $count=count($node);
        $this->assign('count',$count);
        $this->assign('node',$node);
        return $this->fetch();
    }

    #权限节点的添加
    public function rule_add()
    {
        if(IS_POST)
        {
            $info=input();
            if($info['name']==''){
                $date['state']='n';
                $date['msg']='英文名称不为空';
                return json($date);die;
            }
            if($info['title']==''){
                $date['state']='n';
                $date['msg']='中文名称不为空';
                return json($date);die;
            }

            if($info['pid']==0 && $info['icon']==''){
                $date['state']='n';
                $date['msg']='图标不为空';
                return json($date);die;
            }

            if(db('auth_rule')->where(array('name'=>$info['name']))->find()){
                $date['state']='n';
                $date['msg']='该权限已存在';
                return json($date);die;
            }

            if(db('auth_rule')->insert($info))
            {
                $date['state']='y';
                $date['msg']='添加成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='添加失败';
                return json($date);die;
            }
        } 
        $fath=db('auth_rule')->where(array('status'=>1,'pid'=>0))->select();
        $this->assign('fath',$fath); 
        return $this->fetch();
    }

    #权限修改
    public function rule_edit()
    {
        if($_POST){
            $info=$_POST;
            if(db('auth_rule')->where(array('id'=>$info['id']))->update($info)){
                $date['state']='y';
                $date['msg']='修改成功';
                return json($date);die; 
            }else{
                $date['state']='n';
                $date['msg']='修改失败';
                return json($date);die;
            }
        }

        $id=input('id');

        if(!$rule=db('auth_rule')->where(array('id'=>$id))->find())
        {
            return $this->error('暂无数据','','',1);
        }else{  
            $this->assign('rule',$rule);   
        } 
        $fath=db('auth_rule')->where(array('status'=>1,'pid'=>0))->select();
        $this->assign('fath',$fath); 
               return $this->fetch();
    }

    #权限的删除
    public function rule_del()
    {
        if($_POST){
            $where['pid']=array('in',$_POST['id']);
            if(db('auth_rule')->where($where)->find())
            {
                $date['state']='n';
                $date['msg']='删除失败,该权限下存在子权限';
                return json($date);die;  
            }
            $map['id']=array('in',$_POST['id']);
            if(db('auth_rule')->where($map)->delete()){
                $date['state']='y';
                $date['msg']='删除成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='删除失败';
                return json($date);die;
            }
        }

        return $this->error('无效操作','','',1);
    }




}
