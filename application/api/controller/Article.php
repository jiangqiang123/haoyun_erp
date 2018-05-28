<?php
namespace app\api\controller;
use think\Request;
use CodeError\CodeError;

class Article  extends Home {
    public function reading(){
        $request = Request::instance();
        $param= $request->param();
        switch ($request->method()){
            case 'POST':
                if(!empty($param)){
                    if($param['type']==1){

                    }else{
                        return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                    }
                }else{
                    return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                }
                break;

            case 'GET':
                if(!empty($param)){
                    if($param['type']==1){
                        return $this->article_list($param);         /***首页任务攻略***/
                    }elseif($param['type']==2){
                        return $this->article_info($param);         /****攻略详情***/
                    }elseif($param['type']==3){
                        return $this->banner($param);               /***首页banner***/
                    }else{
                        return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                    }
                }else{
                    return ["code"=>CodeError::CODEEOOR_PARAM_NAME,"message"=>CodeError::CODEEOOR_PARAM_CODE];/****无效请求参数***/
                }
                break;
            default:
                return ["code"=>CodeError::CODEEOOR_REQUEST_CODE,"message"=>CodeError::CODEEOOR_REQUEST_NAME];
        }
    }


    /***首页任务攻略
     * @param $param
     * page  分页
     * type 1 get
     */
    protected function article_list($param){
        if(!key_is_set(array("page"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $count = db('article')->where('catid',1)->count();
        $list = db('article')->where('catid',1)->order('addtime desc')->paginate(10);
        $list = $list->items();
        foreach ($list as $key=>$value){
            if(!empty($value['thumb'])) {
                $list[$key]['thumb'] = 'http://' . $_SERVER['SERVER_NAME'] . '/uploads/article/' . $value['thumb'];
            }
        }
        $data = array(
            'list'=>$list,
            'count' => $count,
        );
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$data,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }

    /***任务攻略详情
     * @param $param
     * @return array
     * id 文章ID
     * type 2 get
     */
    protected function article_info($param){
        if(!key_is_set(array("id"),$param)){
            return ['code'=>CodeError::CODEEOOR_PARAM_CODE,'data'=>null,'message'=>CodeError::CODEEOOR_PARAM_NAME];    //前端请求参数不正确
        }
        $info = db('article')->where('id',$param['id'])->find();
        $info['thumb'] = 'http://'.$_SERVER['SERVER_NAME']. '/uploads/article/'.$info['thumb'];
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$info,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }


    /***首页banner图
     * @param $param
     * @return array
     * type 3 get
     */
    protected function banner($param){
        $list = db('article')->where('catid',2)->order('sort asc')->limit(3)->select();
        foreach ($list as $key=>$value){
            $list[$key]['thumb'] = 'http://'.$_SERVER['SERVER_NAME']. '/uploads/article/'.$value['thumb'];
        }
        return ['code'=>CodeError::CODESECCESS_COMMON_PARAM_CODE,'data'=>$list,'message'=>CodeError::CODESECCESS_COMMON_PARAM_NAME];
    }
}