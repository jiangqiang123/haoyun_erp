<?php
namespace app\index\controller;
use think\Controller;

class Index extends Controller
{
    public function index(){
        echo "欢迎来到TP5!";
        echo user_md5("123456");
    }
}