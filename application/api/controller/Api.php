<?php
/**
 * api示例说明控制器+++++++++++++++++++++++++++++++
 * Created by PhpStorm.                           +
 * User: Administrator                            +
 * Date: 2017/7/29/029                            +
 * Time: 9:52                                     +
 * +++++++++++++++++++++++++++++++++++++++++++++++++
 */
namespace app\api\controller;
use think\Request;
use CodeError\CodeError;
use Queue\Queue;

class Api extends Home{

    public function index(){
        switch ($_SERVER['REQUEST_METHOD']){
            case 'GET'://查询
                return $this->select();
                break;
            case 'POST':    //新增
                return $this->add();
                break;
            case 'PUT':     //修改
                return $this->update();
                break;
            case 'DELETE':  //删除
                return $this->delete();
                break;
            default:
                return ["code"=>CodeError::CODEEOOR_REQUEST_CODE,"message"=>CodeError::CODEEOOR_REQUEST_NAME];
        }
    }
    public function user(){
        // 指明给谁推送，为空表示向所有在线用户推送
        $to_uid = 'v2221';
// 推送的url地址，上线时改成自己的服务器地址
        $push_api_url = "http://vs.tommmt.com:2121/";
        $post_data = array(
            'type' => 'publish',
            'content' => '真的是瞎打的字',
            'to' => $to_uid,
        );
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $push_api_url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
        $return = curl_exec ( $ch );
        curl_close ( $ch );
        var_export($return);


    }
    public function test(){

    }
    /****查看
     ***/
    public function select(){

     $data = [
            ['name'=>'这是查找',"time"=>"2017-7-27","type"=>"1",'num'=>95,"prices"=>0.54],
            ['name'=>'这是查找',"time"=>"2017-7-26","type"=>"2",'num'=>94,"prices"=>0.52],
            ['name'=>'这是查找',"time"=>"2017-7-25","type"=>"3",'num'=>195,"prices"=>0.55],
            ['name'=>'这是查找',"time"=>"2017-7-24","type"=>"1",'num'=>45,"prices"=>0.59],
            ['name'=>'这是查找',"time"=>"2017-7-23","type"=>"3",'num'=>55,"prices"=>0.56],
        ];


        return ['data'=>$data,'code'=>1111,'message'=>CodeError::CODEEOOR_SELECT_SUCCESSS_NAME];

    }
    public function add(){
        //新增

        $data = [
            ['name'=>'这是新增',"time"=>"2017-7-27","type"=>"1",'num'=>95,"prices"=>0.54],
            ['name'=>'这是新增',"time"=>"2017-7-26","type"=>"2",'num'=>94,"prices"=>0.52],
            ['name'=>'这是新增',"time"=>"2017-7-25","type"=>"3",'num'=>195,"prices"=>0.55],
            ['name'=>'这是新增',"time"=>"2017-7-24","type"=>"1",'num'=>45,"prices"=>0.59],
            ['name'=>'这是新增',"time"=>"2017-7-23","type"=>"3",'num'=>55,"prices"=>0.56],
        ];
        return ['data'=>$data,'code'=>CodeError::CODEEOOR_SELECT_SUCCESSS_CODE,'message'=>CodeError::CODEEOOR_SELECT_SUCCESSS_NAME];
    }
    public function update(){
        //修改
        $data = [
            ['name'=>'这是修改',"time"=>"2017-7-27","type"=>"1",'num'=>95,"prices"=>0.54],
            ['name'=>'这是修改',"time"=>"2017-7-26","type"=>"2",'num'=>94,"prices"=>0.52],
            ['name'=>'这是修改',"time"=>"2017-7-25","type"=>"3",'num'=>195,"prices"=>0.55],
            ['name'=>'这是修改',"time"=>"2017-7-24","type"=>"1",'num'=>45,"prices"=>0.59],
            ['name'=>'这是修改',"time"=>"2017-7-23","type"=>"3",'num'=>55,"prices"=>0.56],
        ];
        return ['data'=>$data,'code'=>CodeError::CODEEOOR_SELECT_SUCCESSS_CODE,'message'=>CodeError::CODEEOOR_SELECT_SUCCESSS_NAME];
    }
    public function delete(){
        //删除
        $data = [
            ['name'=>'这是删除',"time"=>"2017-7-27","type"=>"1",'num'=>95,"prices"=>0.54],
            ['name'=>'这是删除',"time"=>"2017-7-26","type"=>"2",'num'=>94,"prices"=>0.52],
            ['name'=>'这是删除',"time"=>"2017-7-25","type"=>"3",'num'=>195,"prices"=>0.55],
            ['name'=>'这是删除',"time"=>"2017-7-24","type"=>"1",'num'=>45,"prices"=>0.59],
            ['name'=>'这是删除',"time"=>"2017-7-23","type"=>"3",'num'=>55,"prices"=>0.56],
        ];
        return ['data'=>$data,'code'=>CodeError::CODEEOOR_SELECT_SUCCESSS_CODE,'message'=>CodeError::CODEEOOR_SELECT_SUCCESSS_NAME];
    }
}