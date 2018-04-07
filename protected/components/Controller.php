<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController
{
	/**
	 * @var string the default layout for the controller view. Defaults to '//layouts/column1',
	 * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
	 */
	public $layout='//layouts/admin';
	/**
	 * @var array context menu items. This property will be assigned to {@link CMenu::items}.
	 */
	public $menu=array();
	/**
	 * @var array the breadcrumbs of the current page. The value of this property will
	 * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
	 * for more details on how to specify this property.
	 */
	public $breadcrumbs=array();
	
	//动作执行之前判断登录的状态
	protected function beforeAction($action)
	{
		//如果未登录跳转到登录页
		if(isset(Yii::app()->user->member_userinfo) && Yii::app()->user->member_userinfo['id'])
		{
            // 查询出用户的基本信息
            $member_id = Yii::app()->user->member_userinfo['id'];
            $criteria=new CDbCriteria;
            $criteria->select = 'roleid,name,sex,avatar,email,balance';
            $criteria->condition = 'id=:id';
            $criteria->params = array(':id' => $member_id);
            $memberData = Members::model()->find($criteria);
            $memberData = CJSON::decode(CJSON::encode($memberData));
            if($memberData["roleid"]==1){
                // 商家用户
                // 可以访问
            } else {
                // 普通用户
                $this->redirect(Yii::app()->createUrl('user/login'));
            }
		} else {
		    // 后台用户
            if(!isset(Yii::app()->user->admin_userinfo) && (!defined('NO_LOGIN') || !NO_LOGIN))
            {
                $this->redirect(Yii::app()->createUrl('user/login'));
            }
        }
		return true;
	}
	
	/*****************************整合smarty的两个操作*************************/
	public function assign($name,$value)
	{
		Yii::app()->smarty->assign($name,$value);
	}
	
	public function display($tpl)
	{
		Yii::app()->smarty->display($tpl);
	}
	/*****************************整合smarty的两个操作*************************/
	
	//输出错误信息
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
}