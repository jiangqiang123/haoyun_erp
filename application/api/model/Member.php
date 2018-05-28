<?php
namespace app\api\model;
use think\Model;


class Member extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $createTime = false;
    protected $updateTime = false;

    //用户登录---->账号密码登陆
    public function login($data){
        $user = Member::get(['mobile'=>$data['mobile']]);
        $res = array();
        if(!$user){
            $res['uid'] = null;
        }elseif ($user->password == user_md5($data['password'])){
            $map['logtime'] = time();
            db('member')->where('uid',$user->uid)->update($map);
            $res['uid'] = $user->uid;
            $res['token_time'] = time()+config("token_time");
        }else{
            $res['uid'] = null;
        }
        return $res;
    }

    //微信普通登陆
    public function login_wechat($data){
        $user = Member::get(['mobile'=>$data['mobile']]);
        $res = array();
        if(!$user){
            $res['uid'] = null;
        }elseif($user->password == user_md5($data['password'])){
            $map['logtime'] = time();
            if($data['openid']){
                $map['openid'] = $data['openid'];
            }
            if($data['xopenid']){
                $map['xopenid'] = $data['xopenid'];
            }
            if($data['unionid']){
                $map['unionid'] = $data['unionid'];
            }
            $map['nickname'] = base64_encode($data['name']);
            $map['headpic'] = $data['pic'];
            $map['address'] = $data['address'];
            $map['sex'] = $data['sex'];
            db('member')->where('uid',$user->uid)->update($map);
            $res['uid'] = $user->uid;
            $res['token_time'] = time()+config("token_time");
        }else{
            $res['uid'] = null;
        }
        return $res;
    }


    //用户注册
    public function reg($data,$reg){
        $user = new Member();
        $user->startTrans();
        if(!empty($data['openid']) || !empty($data['xopenid'])){                                        //微信端
            $user->mobile = $data['mobile'];
            $user->password = user_md5($data['password']);
            $user->regtime = time();
            if($data['openid']){
                $user->openid = $data['openid'];
            }
            if($data['xopenid']){
                $user->xopenid = $data['xopenid'];
            }
            if($data['unionid']){
                $user->unionid = $data['unionid'];
            }
            $user->nickname = base64_encode($data['name']);
            $user->headpic = $data['pic'];
            $user->address = $data['address'];
            $user->sex = $data['sex'];
        }else{                                                                //非微信端
            $user->mobile = $data['mobile'];
            $user->password = user_md5($data['password']);
            $user->regtime = time();
        }
        $res1 = $user->save();         //生成用户
        if($reg==1){                    //当开启邀请码注册时
            $code = new ActiveCode();
            $res2=$code->save([
                'uid' => $user->uid,
            ],['code'=>$data['code']]); //  修改邀请码绑定用户
        }else{
            $res2=1;
        }
        if($res1 && $res2){
            $user->commit();
            return true;
        }else{
            $user->rollback();
            return false;
        }
    }

    //检测手机号码是否已存在
    public function mobileExist($mobile){
        $user = Member::get(['mobile'=>$mobile]);
        return $user;
    }


    #检测微信是否已被绑定
    public function openidExist($openid){
        $user = Member::get(['openid'=>$openid]);
        return $user;
    }

    public function xopenidExist($xopenid){
        $user = Member::get(['xopenid'=>$xopenid]);
        return $user;
    }

    //忘记密码
    public function forget($data){
        $user = new Member();
        $res = $user->save([
            'password' => user_md5($data['password']),
        ],['mobile'=>$data['mobile']]);
        return $res;
    }


    # 申请成为小V
    public function become_smallv($data){
        $model = new Member();
        $res = $model->save([
            'apply' => 1,
            'apply_time' => time(),
        ],['uid'=>UID]);
        return $res;
    }


    #添加小V资源账号
    public function add_account($data){
        $model = new SmallvAccount();
        $res = $model->save([
//            'account' => $data['account'],
//              'wid' => $data['wid'],
            'apple'=>$data['apple'],
            'pwd' => $data['pwd'],
            'enable' => 1,
            'type'=> $data['typeid'],
            'uid' => UID,
            'status' => 2,
            'addtime'=>time(),
        ]);
        return $res;
    }

    #添加银行卡
    public function add_card($data){
        $model = new BankCard();
        $res = $model->save([
            'uid' => UID,
            'cardnum' => $data['card'],
            'status' => 1,
            'addtime' => time()
        ]);
        return $res;
    }

    #更换银行卡
    public function edit_card($data){
        $model = new BankCard();
        $res = $model->save([
            'cardnum' => $data['card'],
            'updatetime' => time()
        ],['uid'=>UID]);
        return $res;
    }
}