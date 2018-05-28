<?php
namespace app\index\controller;

/**********任务控制器********/
use app\index\controller\Pub;

use think\Db;
use think\Request;
use think\Session;


class Test
{
  public function index(){

      echo phpinfo();die;
  }
    public function redis(){

        $redis = new \Redis();
        $redis->connect('127.0.0.1',6379);
        $password = '123456';
//      $redis->auth($password);

        $arr = array('111','112','113','114','115','116','1117','118','119','110');

        foreach($arr as $k=>$v){

            $redis->rpush("mylist",$v);

        }


    }
    public function out(){

        $redis = new \Redis();

        $redis->connect('127.0.0.1',6379);
        //  $redis->auth($password);

//list类型出队操作

        $value = $redis->lpop('mylist');


        if($value){

            echo "出队的值".$value;
            sleep(10);
            $redis->lpush("mylist",$value);
            echo "不好出队错误".$value;

        }else{

            echo "出队完成";

        }

    }
    public function is_have(){

        $redis = new \Redis();

        $redis->connect('127.0.0.1',6379);
        //  $redis->auth($password);

//list类型出队操作

        var_dump($redis->scontains('mylist', '111'));


    }
    public function time(){

        if(Db::table('v_redis_test') ->where('id', 1) ->setInc('num')){
            dump(1111);die;
        }else{
            dump(122222);die;
        };
    }
}