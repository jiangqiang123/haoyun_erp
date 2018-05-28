<?php
namespace app\api\controller;
use think\Controller;
use CodeError\CodeError;
use think\Request;

class Index extends Controller
{

    /*
     * 用户注册
     */
    public function index()
    {


            $img="https://ss0.bdstatic.com/70cFuHSh_Q1YnxGkpoWK1HF6hhy/it/u=467032539,2544687919&fm=26&gp=0.jpg";
            $data = [
                ['img'=>$img,'name'=>'坏笑评论任务1',"time"=>"2017-7-27","type"=>"1",'num'=>95,"prices"=>0.54],
                ['img'=>$img,'name'=>'坏笑评论任务2',"time"=>"2017-7-26","type"=>"2",'num'=>94,"prices"=>0.52],
                ['img'=>$img,'name'=>'坏笑评论任务3',"time"=>"2017-7-25","type"=>"3",'num'=>195,"prices"=>0.55],
                ['img'=>$img,'name'=>'坏笑评论任务4',"time"=>"2017-7-24","type"=>"1",'num'=>45,"prices"=>0.59],
                ['img'=>$img,'name'=>'坏笑评论任务5',"time"=>"2017-7-23","type"=>"3",'num'=>55,"prices"=>0.56],
            ];


            return ['data'=>$data,'code'=>CodeError::CODEEOOR_API_CODE,'message'=>CodeError::CODEEOOR_API_NAME];

    }
	
	public function str()
	{	
		$request = Request::instance();
        $param= $request->param();
		if($request->method()=='POST')
		{
			$a='str';
			return $a;
		}
		
	}
	
	public function arr()
	{
		$request = Request::instance();
        $param= $request->param();
		if($request->method()=='POST')
		{
			$arr=array(1,2,3);
			return $arr;
		}
		
	}
	
}
