<?php

//前端控制器
class FormerController extends CController
{
	public $layout='//layouts/site';
	
	//输出错误信息
	protected function errorOutput($data = array())
	{
        $member_id = Yii::app()->user->member_userinfo['id'];
        if(isset($member_id))
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
        $member_id = Yii::app()->user->member_userinfo['id'];
        if(isset($member_id))
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