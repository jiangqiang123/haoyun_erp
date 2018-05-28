<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/8/008
 * Time: 14:20
 */
namespace Queue;
class Data {
    //数据
    private $data;

    public function __construct($data){
        $this->data=$data;
        echo $data.":哥进队了！<br>";
    }

    public function getData(){
        return $this->data;
    }

}