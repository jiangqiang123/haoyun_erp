<?php
namespace app\admin\controller;
use app\admin\controller\Pub;
use app\admin\model\ArticleType;
use app\admin\model\Article as Artic;

use think\Db;
use think\Request;

class Article extends Pub
{
    //文章栏目添加
    public function type_add()
    {
        //分类的添加
        if(IS_POST)
        {
            $info=input('post.');
            if($info['hide']=='')
            {
                $date['state']='n';
                $date['msg']='未启用状态设置';
                return json($date);die;
            }

            if($info['title']=='')
            {
                $date['state']='n';
                $date['msg']='栏目名不为空';
                return json($date);die;
            }

            if(ArticleType::create($info,true))
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
        return $this->fetch();
    }

    //文章栏目列表
    public function type_list()
    {
        $list=db('ArticleType')->select();
        $this->assign('list',$list);
        return $this->fetch();        
    } 

    //文章栏目的修改
    public function type_edit()
    {
        if(IS_POST){
            $update=input('post.');
            if(ArticleType::where(array('id'=>$update['id']))->update($update))
            {
                $date['state']='y';
                $date['msg']='修改成功';
                return json($date);die;
            }else{
                $date['state']='y';
                $date['msg']='修改失败';
                return json($date);die;
            }
        }
        $id=input('id');
        $info=db('ArticleType')->where(array('id'=>$id))->find();
        $this->assign('info',$info);
        return $this->fetch("type_add");
    }

    //文章列表  
    public function article_list()
    {
        $list=db('article')->select();
        $this->assign('list',$list);
        return $this->fetch();
        
    }

    //文章添加
    public function article_add()
    {
        if(IS_POST){
            $info=input('post.');
            if($info['title']=='')
            {
                $date['state']='n';
                $date['msg']='标题不为空';
                return json($date);die; 
            }
            if($info['editorContact1']=='')
            {
                $date['state']='n';
                $date['msg']='内容不为空';
                return json($date);die; 
            }

            $info['content']=$info['editorContact1'];

            $file = request()->file('file-2');
             if(!empty($file)){
                $data = $file->move(ROOT_PATH . 'public' . DS . 'uploads'.DS.'article');
                if($data){
                    $info['thumb'] = $data->getSaveName();
                }else {
                    $date['state']='n';
                    $date['msg']=$data->getError();
                    return json($date);die;
                }
            }

            if(Artic::create($info,true)){
                $date['state']='y';
                $date['msg']='添加成功';
                return json($date);die;

            }else{
                $date['state']='n';
                $date['msg']='添加失败';
                return json($date);die;
            }

           



        }
        $cateid=db('ArticleType')->where(array('hide'=>1))->select();
        $this->assign('cateid',$cateid);
        return $this->fetch();
    }


    //文章内容图片接受
   public function uploadss() {

        header("Content-Type: text/html; charset=utf-8");
       
        $action = $_GET['action'];
        if('uploadimage' == $action) { //上传图片
        $file = request()->file("upfile"); 
        // 移动到框架应用根目录/public/uploads/ 目录下   限制为2M图片
        $info = $file->validate(['size'=>1024*5*1000,'ext'=>'jpg,png,gif'
            ])->move(ROOT_PATH . 'public' . DS . 'uploads'. DS . 'article');
        $pic=$info->getInfo();
            if($info){
                    // 成功上传后 获取上传信息
                    $arr = array(
                        'state'=>'SUCCESS',
                        'url'=>'http://'.$_SERVER['SERVER_NAME'].'/uploads/article/'.$info->getSaveName(),
                        'title'=>$info->getSaveName(),
                        'original'=>$pic['name'],
                        'type'=>$info->getExtension(),
                        'size'=>$pic['size'],
                    );

                    $result = json_encode($arr);
                }else{
                    // 上传失败获取错误信息
                    echo $file->getError();
                }
        }
        /* 输出结果 */

        echo $result;

    }


    public function article_edit()
    {
        if(IS_POST){
            $update=input('post.');
            if($update['title']=='')
            {
                $date['state']='n';
                $date['msg']='标题不为空';
                return json($date);die; 
            }
            if($update['editorContact1']=='')
            {
                $date['state']='n';
                $date['msg']='内容不为空';
                return json($date);die; 
            }

            $update['content']=$update['editorContact1'];
            unset($update['editorContact1']);
            $file = request()->file('file-2');
            if(!empty($file)){
                $data = $file->move(ROOT_PATH . 'public' . DS . 'uploads'.DS.'article');
                if($data){
                    $update['thumb'] = $data->getSaveName();
                }else {
                    $date['state']='n';
                    $date['msg']=$data->getError();
                    return json($date);die;
                }
            }

            if(Artic::where(array('id'=>$update['id']))->update($update)){
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
        $info=db('article')->where(array('id'=>$id))->find();
        $cateid=db('ArticleType')->where(array('hide'=>1))->select();
        $this->assign('cateid',$cateid);
        $this->assign('info',$info);
        return $this->fetch('article_add');
    }
	
	
	/**
	 *	文章的删除
	 **/
	 public function article_del()
	 {
		 if(IS_POST)
		 {
			$del=input('post.');
			
			if($del['id']=='')
            {
                $date['state']='n';
                $date['msg']='该文章不存在';
                return json($date);die; 
            }
			
			if(Artic::destroy($del['id']))
			{
			    $date['state']='y';
                $date['msg']='修改成功';
                return json($date);die;
            }else{
                $date['state']='n';
                $date['msg']='修改失败';
                return json($date);die;
            }
			
		 }
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
}