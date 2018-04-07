<?php

//前端控制器
class FormerController extends CController
{
	public $layout='//layouts/site';

    public function init(){
        parent::init();

        if(!Yii::app()->request->isAjaxRequest)
        {
            $this->redirect(Yii::app()->createUrl('user/login'));
        }
        else
        {

            $this->errorOutput(array('errorCode' => 1,'errorText' => '未登录'));
        }
    }

	//输出错误信息
	protected function errorOutput($data = array())
	{
        if(isset(Yii::app()->user->member_userinfo))
        {
            $data["isGuest"]=false;
        }
        else
        {
            $data["isGuest"]=true;
        }
		echo json_encode($data);
		exit();
	} 
	
	//输出信息
	protected function output($data = array())
	{

        if(isset(Yii::app()->user->member_userinfo))
        {
            $data["isGuest"]=false;
        }
        else
        {
            $data["isGuest"]=true;
        }
		echo json_encode($data);
		exit();
	} 
}