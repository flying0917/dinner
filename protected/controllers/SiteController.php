<?php
//前端页面控制器
class SiteController extends FormerController
{

	private $order;//购物车里面的订单数据
	//控制几个页面的访问
	public function filters()
	{
		return array(
			'checkLoginControl + confirmorder,orderok,membercenter,myorder,modifypassword,domodify,systemnotice,seeconsume,menus,menusForm,foodorder,foodOrderForm,todayOrder',//检测是否登录
			'checkLoginAjax + myOrderListAjax,getUserInfo,confirmOrderAjax,myOrderAjax,cancelOrder,foodorderAjax,finishOrder,domodifyForApp,userinfo',//检测ajax请求是否登录
			'checkIsCartEmpty + lookcart,confirmorder',//检测购物车是否为空
			/*'checkReqiest + doregister,domodify,submitmessage,replymessage',//判断是不是ajax请求*/
			'checkIsOnTime +lookmenu,lookcart,confirmorder',//判断是否在订餐时间内
		);
	}

    //控制会员是否登录
    public function filtercheckLoginControl($filterChain)
    {
        if(!isset(Yii::app()->user->member_userinfo))
        {
            if(!Yii::app()->request->isAjaxRequest)
            {
                $this->redirect(Yii::app()->createUrl('site/login'));
            }
            else
            {
                $this->errorOutput(array('errorCode' => 1,'errorText' => '未登录'));
            }
        }
        $filterChain->run();
    }

    //控制会员是否登录
    public function filtercheckLoginAjax($filterChain)
    {
        if(!isset(Yii::app()->user->member_userinfo))
        {
            if(!Yii::app()->request->isAjaxRequest)
            {
                $this->errorOutput(array('errorCode' => 1,'errorText' => '未登录'));
            }
        }
        $filterChain->run();
    }
	
	//检测购物车是否为空
	public function filtercheckIsCartEmpty($filterChain)
	{
		$_product = Yii::app()->request->cookies['cart'];
		$order = array();
		if($_product)
		{
			$order = json_decode($_product->value,1);
			if($order['Items'])
			{
				foreach ($order['Items'] AS $k => $v)
				{
					$order['Items'][$k]['smallTotal'] = $v['Count'] * $v['Price'];
				}
			}
		}
		
		//如果购物车里面没有东西就报错
		if(!$order || !$order['Items'])
		{
			throw new CHttpException(404,Yii::t('yii','当前购物车没有美食'));
		}
		
		$this->order = $order;
		$filterChain->run();
	}
	
	//判断是不是ajax请求
	public function filtercheckReqiest($filterChain)
	{
		if(!Yii::app()->request->isAjaxRequest)
		{
			throw new CHttpException(404,Yii::t('yii','非法操作'));
		}
		
		if(!Yii::app()->request->isPostRequest)
		{
			throw new CHttpException(404,Yii::t('yii','非法操作'));
		}
		$filterChain->run();
	}
	
	public function filtercheckIsOnTime($filterChain)
	{
		if(!Yii::app()->check_time->isOnTime())
		{
			throw new CHttpException(404,Yii::t('yii','不在订餐时间内'));
		}
		$filterChain->run();
	}
	
	public function actions()
	{
		return array(
              'captcha' => array(
                    'class'		=>'CCaptchaAction',
                    'maxLength'	=> 4,       // 最多生成几个字符
                    'minLength'	=> 4,       // 最少生成几个字符
					'testLimit' => 999,
					//'fixedVerifyCode' => substr(md5(time()),11,4), //每次都刷新验证码
            ), 
         ); 
	}

	//前台首页
	public function actionIndex()
	{
		//取出商家的数据
		$model = Shops::model()->with('image')->findAll('t.status=:status',array(':status' => 2));
		$shopData = array();
		foreach($model AS $k => $v)
		{
			$shopData[$k] = $v->attributes;
			$shopData[$k]['logo'] = $shopData[$k]['logo']?Yii::app()->params['img_url'] . $v->image->filepath . $v->image->filename:'';
		}
		
		//取出公告数据
		$notice = Announcement::model()->findAll(array('order' => 'create_time DESC','condition' => 'status=:status','params'=>array(':status'=>2)));
		$notice = CJSON::decode(CJSON::encode($notice));
		
		//查询出会员账户余额小于10元的用户
		$members = Members::model()->findAll('balance < :balance',array(':balance' => 20));
		$members = CJSON::decode(CJSON::encode($members));
		//输出数据
		$output = array(
			'shops' 	=> $shopData,
			'announce' 	=> $notice,
			'members'	=> $members,
			'isOnTime'  => Yii::app()->check_time->isOnTime(),
		);		
		$this->render('index',$output);
	}

	//ajax获取饭店
    public function actionGetShop()
    {
        //取出商家的数据
        $model = Shops::model()->with('image')->findAll('t.status=:status',array(':status' => 2));
        $shopData = array();
        foreach($model AS $k => $v)
        {
            $shopData[$k] = $v->attributes;
            $shopData[$k]['logo'] = $shopData[$k]['logo']?Yii::app()->params['img_url'] . $v->image->filepath . $v->image->filename:'';
        }
        $this->output(array('success' =>1,'msg'=>'获取饭店列表成功','data'=>array('shops'=>$shopData,'isOnTime'  => Yii::app()->check_time->isOnTime())));
    }

    //ajax获取公告
    public function actionGetArticle()
    {
        //取出公告数据
        $notice = Announcement::model()->findAll(array('order' => 'create_time DESC','condition' => 'status=:status','params'=>array(':status'=>2)));
        $notice = CJSON::decode(CJSON::encode($notice));
        $this->output(array('success' =>1,'msg'=>'获取公告列表成功','data'=>array('announce'=>$notice)));
    }
	
	//进入某个餐厅查看菜单
	public function actionLookMenu()
	{
		$shop_id = Yii::app()->request->getParam('shop_id');
		if(!isset($shop_id))
		{
			throw new CHttpException(404,Yii::t('yii','请选择一家餐厅'));
		}
		
		//查询出改商店的一些详细信息
		$shopData = Shops::model()->findByPk($shop_id);
		if(!$shopData)
		{
			throw new CHttpException(404,Yii::t('yii','您选择的这家餐厅不存在'));
		}
		$shopData = CJSON::decode(CJSON::encode($shopData));
		
		//判断改商家有没有下市场
		if(intval($shopData['status']) != 2)
		{
		    throw new CHttpException(404,Yii::t('yii','您选择的这家餐厅不存在或者已经倒闭了！'));
		}

		//根据店铺id查询出该店铺的菜单
		$menuData = Menus::model()->with('food_sort','image','shops')->findAll(array('condition' => 't.shop_id=:shop_id AND t.status=:status','params' => array(':shop_id' => $shop_id,':status' => 2)));
		$data = array();
		foreach($menuData AS $k => $v)
		{
			$data[$k] = $v->attributes;
			$data[$k]['index_pic'] = $v->index_pic?Yii::app()->params['img_url'] . $v->image->filepath . $v->image->filename:'';
			$data[$k]['sort_name'] = $v->food_sort->name;
			$data[$k]['shop_name'] = $v->shops->name;
			$data[$k]['create_time'] = Yii::app()->format->formatDate($v->create_time);
			$data[$k]['status'] = Yii::app()->params['menu_status'][$v->status];
			$data[$k]['price'] = $v->price;
		}
		
		//获取该店的留言
		$criteria = new CDbCriteria();
		$criteria->order = 't.order_id DESC';
		$criteria->condition = 't.shop_id=:shop_id AND t.status=:status';
		$criteria->params = array(':shop_id' => $shop_id,':status' => 1);
		$count=Message::model()->count($criteria);
		//构建分页
		$pages = new CPagination($count);
 		$pages->pageSize = Yii::app()->params['pagesize'];
		$pages->applyLimit($criteria);
		$messageMode = Message::model()->with('members','shops','replys')->findAll($criteria);
		$message = array();
		foreach($messageMode AS $k => $v)
		{
			$message[$k] = $v->attributes;
			$message[$k]['shop_name'] = $v->shops->name;
			$message[$k]['user_name'] = $v->members->name;
			$message[$k]['create_time'] = date('Y-m-d H:i:s',$v->create_time);
			$message[$k]['status_text'] = Yii::app()->params['message_status'][$v->status];
			$message[$k]['status_color'] = Yii::app()->params['status_color'][$v->status];
			
			$_replys = Reply::model()->with('members')->findAll(array(
					'condition' => 'message_id=:message_id',
					'params'	=> array(':message_id' => $v->id),
			));
			
			if(!empty($_replys))
			{
				foreach ($_replys AS $kk => $vv)
				{
					$message[$k]['replys'][$kk] = $vv->attributes;
					$message[$k]['replys'][$kk]['create_time'] 	= date('Y-m-d H:i:s',$vv->create_time);
					$message[$k]['replys'][$kk]['user_name'] 	= ($vv->user_id == -1)?'商家说':$vv->members->name;
				}
			}
		}
		
		$this->render('lookmenu',array(
			'menus' 	=> $data,
			'shop' 		=> $shopData,
			'pages'		=> $pages,
			'message'	=> $message,
		));
	}




    //进入某个餐厅信息ajax
    public function actionGetShopInfo()
    {
        $shop_id = Yii::app()->request->getParam('shop_id');
        if(!isset($shop_id))
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '请选择一家餐厅'));
        }

        //查询出改商店的一些详细信息
        $shopData= Shops::model()->with("image")->findByPk($shop_id);

        $shopData['logo'] = $shopData['logo']?Yii::app()->params['img_url'] . $shopData["image"]->filepath . $shopData["image"]->filename:'';

        if(!$shopData)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '您选择的这家餐厅不存在'));
        }
        $shopData = CJSON::decode(CJSON::encode($shopData));

        //判断改商家有没有下市场
        if(intval($shopData['status']) != 2)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '您选择的这家餐厅不存在或者已经倒闭了！'));
        }




        $resData=array(
            'shop' 		=> $shopData
        );

        $this->output(array('success'=>1,'data'=>$resData,'msg'=>'获取饭店信息成功'));
    }


    //获取留言
    public function actionGetMessageAjax()
    {
        if(Yii::app()->request->getParam('shop_id'))
        {
            $shop_id=Yii::app()->request->getParam('shop_id');
            //获取该店的留言
            $criteria = new CDbCriteria();
            $criteria->order = 't.order_id DESC';
            $criteria->condition = 't.shop_id=:shop_id AND t.status=:status';
            $criteria->params = array(':shop_id' => $shop_id,':status' => 1);
            //构建分页
            $currentPage = Yii::app()->request->getParam('page');
            $pageSize = Yii::app()->request->getParam('pagesize');
            if(!empty($currentPage)) {
                $currentPage = intval( $currentPage );
            } else {
                $currentPage = 1;
            }
            $limit = !empty( $pageSize ) ? intval( $pageSize ) : 10;
            $offset = ($currentPage-1) * $limit;
            $criteria->offset = $offset;
            $criteria->limit = $limit;
            $messageMode = Message::model()->with('members','shops','replys')->findAll($criteria);
            $message = array();
            foreach($messageMode AS $k => $v)
            {
                $message[$k] = $v->attributes;

                $message[$k]['shop_name'] = $v->shops->name;
                $message[$k]['user_name'] = $v->members->name;
                $message[$k]['create_time'] = date('Y-m-d H:i:s',$v->create_time);
                $message[$k]['status_text'] = Yii::app()->params['message_status'][$v->status];
                $message[$k]['status_color'] = Yii::app()->params['status_color'][$v->status];

                $_replys = Reply::model()->with('members')->findAll(array(
                    'condition' => 'message_id=:message_id',
                    'params'	=> array(':message_id' => $v->id),
                ));

                if(!empty($_replys))
                {
                    foreach ($_replys AS $kk => $vv)
                    {
                        $message[$k]['replys'][$kk] = $vv->attributes;
                        $message[$k]['replys'][$kk]['create_time'] 	= date('Y-m-d H:i:s',$vv->create_time);
                        $message[$k]['replys'][$kk]['user_name'] 	= ($vv->user_id == -1)?'商家说':$vv->members->name;
                    }
                }
            }
            $resData=array(
                'message' 		=> $message
            );

            $this->output(array('success'=>1,'data'=>$resData,'msg'=>'获取饭店信息成功'));
        }
    }



    //菜单页面ajax
    public function actionMenusAjax()
    {
        //创建查询条件
        $menuname = Yii::app()->request->getParam('k');

        $member_id = Yii::app()->user->member_userinfo['id'];
        $s_criteria=new CDbCriteria;
        $s_criteria->select = 'roleid,name,sex,avatar,email,balance';
        $s_criteria->condition = 'id=:id';
        $s_criteria->params = array(':id' => $member_id);
        $memberData = Members::model()->find($s_criteria);
        $memberData = CJSON::decode(CJSON::encode($memberData));
        $shopdata = array();
        if($memberData["roleid"]==1){
            //商家用户
            $shopdata = Shops::model()->find('useid=:id',array(':id'=>$member_id));
            $shopdata = CJSON::decode(CJSON::encode($shopdata));
        }

        $shopId = $shopdata['id'];
        $criteria = new CDbCriteria();
        $criteria->condition = 'shop_id=:shop_id';
        $criteria->params = array(':shop_id' => $shopId);
        $criteria->order = 't.order_id DESC';
        if($menuname)
        {
            $criteria->compare('t.name',$menuname,true);
        }

        if($shopId)
        {
            $criteria->compare('t.shop_id',$shopId);
        }
        $count=Menus::model()->count($criteria);

        //构建分页
        $currentPage = Yii::app()->request->getParam('page');
        $pageSize = Yii::app()->request->getParam('pagesize');
        if(!empty($currentPage)) {
            $currentPage = intval( $currentPage );
        } else {
            $currentPage = 1;
        }
        $limit = !empty( $pageSize ) ? intval( $pageSize ) : 10;
        $offset = ($currentPage-1) * $limit;
        $criteria->offset = $offset;
        $criteria->limit = $limit;

        $model = Menus::model()->with('food_sort','image','shops')->findAll($criteria);
        $data = array();
        foreach($model AS $k => $v)
        {
            $data[$k] = $v->attributes;
            $data[$k]['index_pic'] = $v->index_pic?Yii::app()->params['img_url'] . $v->image->filepath . $v->image->filename:'';
            $data[$k]['sort_name'] = $v->food_sort->name;
            $data[$k]['shop_name'] = $v->shops->name;
            $data[$k]['create_time'] = Yii::app()->format->formatDate($v->create_time);
            $data[$k]['status_text'] = Yii::app()->params['menu_status'][$v->status];
            $data[$k]['status_color'] = Yii::app()->params['status_color'][$v->status];
            $data[$k]['price'] = $v->price . '元/份';
        }



        //取出所有店家供前端选择
        $shops = Shops::model()->findAll();
        $shops = CJSON::decode(CJSON::encode($shops));
        //输出到前端
        $this->output(array(
            'data' 	=> $data,
            'shops' => $shops
        ));
    }




    //菜单页面
    public function actionMenus()
    {
        //创建查询条件
        $pMenu = $this->getCentermenuView(array());
        $menuname = Yii::app()->request->getParam('k');

        $member_id = Yii::app()->user->member_userinfo['id'];
        $s_criteria=new CDbCriteria;
        $s_criteria->select = 'roleid,name,sex,avatar,email,balance';
        $s_criteria->condition = 'id=:id';
        $s_criteria->params = array(':id' => $member_id);
        $memberData = Members::model()->find($s_criteria);
        $memberData = CJSON::decode(CJSON::encode($memberData));
        $shopdata = array();
        if($memberData["roleid"]==1){
            //商家用户
            $shopdata = Shops::model()->find('useid=:id',array(':id'=>$member_id));
            $shopdata = CJSON::decode(CJSON::encode($shopdata));
        }

        $shopId = $shopdata['id'];
        $criteria = new CDbCriteria();
        $criteria->condition = 'shop_id=:shop_id';
        $criteria->params = array(':shop_id' => $shopId);
        $criteria->order = 't.order_id DESC';
        if($menuname)
        {
            $criteria->compare('t.name',$menuname,true);
        }

        if($shopId)
        {
            $criteria->compare('t.shop_id',$shopId);
        }
        $count=Menus::model()->count($criteria);
        //构建分页
        $pages = new CPagination($count);
        $pages->pageSize = Yii::app()->params['pagesize'];
        $pages->applyLimit($criteria);
        $model = Menus::model()->with('food_sort','image','shops')->findAll($criteria);
        $data = array();
        foreach($model AS $k => $v)
        {
            $data[$k] = $v->attributes;
            $data[$k]['index_pic'] = $v->index_pic?Yii::app()->params['img_url'] . $v->image->filepath . $v->image->filename:'';
            $data[$k]['sort_name'] = $v->food_sort->name;
            $data[$k]['shop_name'] = $v->shops->name;
            $data[$k]['create_time'] = Yii::app()->format->formatDate($v->create_time);
            $data[$k]['status_text'] = Yii::app()->params['menu_status'][$v->status];
            $data[$k]['status_color'] = Yii::app()->params['status_color'][$v->status];
            $data[$k]['price'] = $v->price . '元/份';
        }



        //取出所有店家供前端选择
        $shops = Shops::model()->findAll();
        $shops = CJSON::decode(CJSON::encode($shops));
        //输出到前端
        $this->render('menus', array(
            'data' 	=> $data,
            'shops' => $shops,
            'pages'	=> $pages,
            'pMenu'=>$pMenu
        ));
    }
    //菜单表单页
    public function actionMenusForm()
    {
        $id = Yii::app()->request->getParam('id');
        if($id)
        {
            $model = Menus::model()->with('food_sort','image')->findByPk($id);
            $data = CJSON::decode(CJSON::encode($model));
            $data['index_pic'] = $data['index_pic']?Yii::app()->params['img_url'] . $model->image->filepath . $model->image->filename:'';
        }

        //查询出商家的信息
        $shops = Shops::model()->findAll();
        $shops = CJSON::decode(CJSON::encode($shops));

        $member_id = Yii::app()->user->member_userinfo['id'];
        $criteria=new CDbCriteria;
        $criteria->select = 'roleid,name,sex,avatar,email,balance';
        $criteria->condition = 'id=:id';
        $criteria->params = array(':id' => $member_id);
        $memberData = Members::model()->find($criteria);
        $memberData = CJSON::decode(CJSON::encode($memberData));
        $shopdata = array();
        if($memberData["roleid"]==1){
            //商家用户
            $shopdata = Shops::model()->find('useid=:id',array(':id'=>$member_id));
            $shopdata = CJSON::decode(CJSON::encode($shopdata));
        }
        //菜单
        $pMenu = $this->getCentermenuView(array());
        $this->render('menus_form',array(
            'data' 	=> $data,
            'shops' => $shops,
            'pMenu'=>$pMenu,
            'shopData'=>$shopdata
        ));
    }








    //进入某个餐厅获取菜单ajax
    public function actionGetMenu()
    {
        $shop_id = Yii::app()->request->getParam('shop_id');
        if(!isset($shop_id))
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '请选择一家餐厅'));
        }

        //查询出改商店的一些详细信息
        $shopData = Shops::model()->findByPk($shop_id);
        if(!$shopData)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '您选择的这家餐厅不存在'));
        }
        $shopData = CJSON::decode(CJSON::encode($shopData));

        //判断改商家有没有下市场
        if(intval($shopData['status']) != 2)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '您选择的这家餐厅不存在或者已经倒闭了！'));
        }

        //根据店铺id查询出该店铺的菜单
        $menuData = Menus::model()->with('food_sort','image','shops')->findAll(array('condition' => 't.shop_id=:shop_id AND t.status=:status','params' => array(':shop_id' => $shop_id,':status' => 2)));
        $data = array();
        foreach($menuData AS $k => $v)
        {
            $data[$k] = $v->attributes;
            $data[$k]['index_pic'] = $v->index_pic?Yii::app()->params['img_url'] . $v->image->filepath . $v->image->filename:'';
            $data[$k]['sort_name'] = $v->food_sort->name;
            $data[$k]['shop_name'] = $v->shops->name;
            $data[$k]['create_time'] = Yii::app()->format->formatDate($v->create_time);
            $data[$k]['status'] = Yii::app()->params['menu_status'][$v->status];
            $data[$k]['price'] = $v->price;
        }


        $resData=array(
            'menus' 	=> $data,
            'shop'=>$shopData
        );
        $this->output(array('success'=>1,'data'=>$resData,'msg'=>'获取饭店菜单成功'));
    }

	//查看购物车
	public function actionLookCart()
	{
		$this->render('lookcart',array('order' => $this->order));
	}


	//确认下单ajax
    public function actionConfirmOrderAjax()
    {
        $orderFromApp = Yii::app()->request->getPost('order');
        $address=Yii::app()->request->getPost('address');
        if(isset($orderFromApp))
        {
            $orderFromApp = json_decode($orderFromApp, true);
            //确认订单之前查看用户余额够不够付

            /*$memberInfo = Members::model()->find('id=:id',array(':id' => Yii::app()->user->member_userinfo['id']));
            if($memberInfo->balance < $orderFromApp['Total'] && !in_array(Yii::app()->user->member_userinfo['id'], Yii::app()->params['allow_user_id']))
            {
                throw new CHttpException(404,Yii::t('yii','亲！您的账户余额不足，不能下单哦，到前台妹子交钱吧！'));
            }*/

            //构建数据
            $foodOrder = new FoodOrder();
            $foodOrder->shop_id = $orderFromApp['shop_id'];
            $foodOrder->order_number = date('YmdHis',time()) . Common::getRandNums(6);
            $foodOrder->food_user_id = Yii::app()->user->member_userinfo['id'];
            $foodOrder->total_price = $orderFromApp['Total'];
            $foodOrder->create_time = time();
            $foodOrder->product_info = serialize($orderFromApp['Items']);
            $foodOrder->address = $address;
            if($foodOrder->save())
            {
                //记录订单动态
                $foodOrderLog = new FoodOrderLog();
                $foodOrderLog->food_order_id = $foodOrder->id;
                $foodOrderLog->create_time = time();
                if($foodOrderLog->save())
                {
                    $this->output(array("success"=>1,"msg"=>"下单成功，请等待配送","data"=>array("order_id"=>$foodOrderLog->food_order_id)));
                }

            }
            else
            {
                $this->errorOutput(array("errorCode"=>1,"errorText"=>"下单失败"));
            }
        }
        else
        {
            $this->errorOutput(array("errorCode"=>1,"errorTet"=>"购物车为空"));
        }
    }
	
	//确认下单
	public function actionConfirmOrder()
	{
		//确认订单之前查看用户余额够不够付

		/*$memberInfo = Members::model()->find('id=:id',array(':id' => Yii::app()->user->member_userinfo['id']));
		if($memberInfo->balance < $this->order['Total'] && !in_array(Yii::app()->user->member_userinfo['id'], Yii::app()->params['allow_user_id']))
		{
			throw new CHttpException(404,Yii::t('yii','亲！您的账户余额不足，不能下单哦，到前台妹子交钱吧！'));
		}*/
		
		//构建数据
		$foodOrder = new FoodOrder();
		$foodOrder->shop_id = $this->order['shop_id'];
		$foodOrder->order_number = date('YmdHis',time()) . Common::getRandNums(6);
		$foodOrder->food_user_id = Yii::app()->user->member_userinfo['id'];
		$foodOrder->total_price = $this->order['Total'];
		$foodOrder->create_time = time();
		$foodOrder->product_info = serialize($this->order['Items']);
		
		if($foodOrder->save())
		{
			//记录订单动态
			$foodOrderLog = new FoodOrderLog();
			$foodOrderLog->food_order_id = $foodOrder->id;
			$foodOrderLog->create_time = time();
			if($foodOrderLog->save())
			{
				//清空购物车
				unset(Yii::app()->request->cookies['cart']);
				//$this->redirect(Yii::app()->createUrl('site/orderok',array('ordernumber' => $foodOrder->order_number)));
			}
		}
		else 
		{
			throw new CHttpException(404,Yii::t('yii','下单失败'));
		}
	}
	
	//下单成功页面
	public function actionOrderOk()
	{
		//判断有没有该订单
		$ordernumber = Yii::app()->request->getParam('ordernumber');
		if(!$ordernumber)
		{
			throw new CHttpException(404,Yii::t('yii','没有订单号'));
		}
		
		//根据当前用户的id与订单号查询出有没有该订单
		$criteria=new CDbCriteria;
		$criteria->select = 'order_number,total_price,create_time';
		$criteria->condition = 'order_number = :order_number AND food_user_id = :food_user_id';
		$criteria->params = array(':order_number' => $ordernumber,':food_user_id' => Yii::app()->user->member_userinfo['id']);
		$data = FoodOrder::model()->find($criteria);
		if(!$data)
		{
			throw new CHttpException(404,Yii::t('yii','您没有该订单'));
		}
		
		$data = CJSON::decode(CJSON::encode($data));
		$data['create_time'] = date('Y年m月d日 H时i分s秒',$data['create_time']);
		$data['username'] = Yii::app()->user->member_userinfo['username'];
		$this->render('orderok',array('order_info' => $data));
	}
	
	//用户中心
	public function actionMemberCenter()
	{
		//查询出用户的基本信息
		$member_id = Yii::app()->user->member_userinfo['id'];
		$criteria=new CDbCriteria;
		$criteria->select = 'roleid,name,sex,avatar,email,balance';
		$criteria->condition = 'id=:id';
		$criteria->params = array(':id' => $member_id);
		$memberData = Members::model()->find($criteria);
		$memberData = CJSON::decode(CJSON::encode($memberData));
        $pMenu = $this->getCentermenuView( $memberData);
		if($memberData["roleid"]==1){
            //商家用户
		    $shopdata = Shops::model()->find('useid=:id',array(':id'=>$member_id));
            $shopdata = CJSON::decode(CJSON::encode($shopdata));
		    $view='shopcenter';
            $this->render($view,array('member' => $memberData,'shop'=>$shopdata,'pMenu' => $pMenu));
        }else{
		    //普通用户
            $view='membercenter';
            $this->render($view,array('member' => $memberData,'pMenu' => $pMenu));
        }
	}

    //用户信息
    public function actionUserinfo()
    {
        //查询出用户的基本信息
        $member_id = Yii::app()->user->member_userinfo['id'];
        $criteria=new CDbCriteria;
        $criteria->select = 'roleid,name,sex,avatar,email,balance';
        $criteria->condition = 'id=:id';
        $criteria->params = array(':id' => $member_id);
        $memberData = Members::model()->find($criteria);
        $memberData = CJSON::decode(CJSON::encode($memberData));
        if($memberData["roleid"]==1){
            //商家用户
            $shopdata = Shops::model()->find('useid=:id',array(':id'=>$member_id));
            $shopdata = CJSON::decode(CJSON::encode($shopdata));
            $this->output(array('success'=>1,'member' => $memberData,'shop'=>$shopdata));
        }else{
            //普通用户
            $this->output(array('success'=>1,'member' => $memberData));
        }
    }



    //商家订单页面ajax
    public function actionFoodorderAjax()
    {
        //查询出用户的基本信息
        $member_id = Yii::app()->user->member_userinfo['id'];
        $criteria=new CDbCriteria;
        $criteria->select = 'roleid,name,sex,avatar,email,balance';
        $criteria->condition = 'id=:id';
        $criteria->params = array(':id' => $member_id);
        $memberData = Members::model()->find($criteria);
        $memberData = CJSON::decode(CJSON::encode($memberData));

        if($memberData["roleid"]==0) {
            //普通用户
            $this->actionMemberCenter();
            exit();
        }
        //商家用户
        $shopdata = Shops::model()->find('useid=:id',array(':id'=>$member_id));
        $shopdata = CJSON::decode(CJSON::encode($shopdata));
        //创建查询条件
        $shop_criteria = new CDbCriteria();
        $shop_criteria->condition = 'shop_id=:shop_id';
        $shop_criteria->params = array(':shop_id' =>$shopdata['id']);
        $shop_criteria->order = 't.create_time DESC';
        $count = FoodOrder::model()->count($shop_criteria);
        //构建分页
        $currentPage = Yii::app()->request->getParam('page');
        $pageSize = Yii::app()->request->getParam('pagesize');
        if(!empty($currentPage)) {
            $currentPage = intval( $currentPage );
        } else {
            $currentPage = 1;
        }
        $limit = !empty( $pageSize ) ? intval( $pageSize ) : 10;
        $offset = ($currentPage-1) * $limit;
        $shop_criteria->offset = $offset;
        $shop_criteria->limit = $limit;

        $model = FoodOrder::model()->with('shops', 'members')->findAll($shop_criteria);
        $data = array();

        foreach ($model AS $k => $v) {
            $data[$k] = $v->attributes;
            $data[$k]['shop_name'] = $v->shops->name;
            $data[$k]['user_name'] = $v->members->name;
            $data[$k]['create_time'] = date('Y-m-d H:i:s', $v->create_time);
            $data[$k]['status_text'] = Yii::app()->params['order_status'][$v->status];
            $data[$k]['status_color'] = Yii::app()->params['status_color'][$v->status];

            //给订单信息添加描述
            $data[$k]['product_info'] = unserialize($v->product_info);


            $data[$k]["Count"]=0;
            foreach($data[$k]["product_info"] AS $ks=>$vs)
            {
                $data[$k]["product_info"][$ks]["total"]=intval($vs["Count"])*floatval($vs["Price"]);
                $data[$k]["total"]+=$data[$k]["product_info"][$ks]["total"];

                $data[$k]["Count"]+=intval($vs["Count"]);

            }
            $orderText="";
            foreach ($data[$k]['product_info'] AS $key=>$value)
            {
                if($key<2)
                {
                    $orderText.=$value["Name"].'+';
                }
            }
            $orderText.=" 等".$data[$k]["Count"].'件商品';
            $data[$k]['order_text']=$orderText;
        }

        $this->output(array("success"=>1,"msg"=>"获取订单成功","data"=>$data));

    }

    //商家订单页面
    public function actionFoodorder()
    {
        //查询出用户的基本信息
        $member_id = Yii::app()->user->member_userinfo['id'];
        $criteria=new CDbCriteria;
        $criteria->select = 'roleid,name,sex,avatar,email,balance';
        $criteria->condition = 'id=:id';
        $criteria->params = array(':id' => $member_id);
        $memberData = Members::model()->find($criteria);
        $memberData = CJSON::decode(CJSON::encode($memberData));

        $pMenu = $this->getCentermenuView( array());

        if($memberData["roleid"]==0) {
            //普通用户
            $this->actionMemberCenter();
            exit();
        }
        //商家用户
        $shopdata = Shops::model()->find('useid=:id',array(':id'=>$member_id));
        $shopdata = CJSON::decode(CJSON::encode($shopdata));
        //创建查询条件
        $shop_criteria = new CDbCriteria();
        $shop_criteria->condition = 'shop_id=:shop_id';
        $shop_criteria->params = array(':shop_id' =>$shopdata['id']);
        $shop_criteria->order = 't.create_time DESC';
        $count = FoodOrder::model()->count($shop_criteria);
        //构建分页
        $pages = new CPagination($count);
        $pages->pageSize = Yii::app()->params['pagesize'];
        $pages->applyLimit($shop_criteria);
        $model = FoodOrder::model()->with('shops', 'members')->findAll($shop_criteria);
        $data = array();
        foreach ($model AS $k => $v) {
            $data[$k] = $v->attributes;
            $data[$k]['shop_name'] = $v->shops->name;
            $data[$k]['user_name'] = $v->members->name;
            $data[$k]['create_time'] = date('Y-m-d H:i:s', $v->create_time);
            $data[$k]['status_text'] = Yii::app()->params['order_status'][$v->status];
            $data[$k]['status_color'] = Yii::app()->params['status_color'][$v->status];
        }

        //输出到前端
        $this->render('foodorder', array(
            'data' => $data,
            'pages' => $pages,
            'pMenu'=> $pMenu
        ));
    }
    //商家表单页
    public function actionFoodOrderForm()
    {
        $id = Yii::app()->request->getParam('id');
        if($id)
        {
            $model = FoodOrder::model()->with('shops','members')->findByPk($id);
            $data = CJSON::decode(CJSON::encode($model));
            $data['product_info'] = $data['product_info']?unserialize($data['product_info']):array();
            $data['user_name'] = $model->members->name;
            $data['shop_name'] = $model->shops->name;
            $data['create_time'] = date('Y-m-d H:i:s',$model->create_time);

            //菜单
            $pMenu= $pMenu = $this->getCentermenuView( array());
        }
        else
        {
            throw new CHttpException(404,Yii::t('yii','没有id'));
        }

        $this->render('order_form',array(
            'data' 	=> $data,
            'pMenu'=>$pMenu
        ));
    }
    //商家订单统计
    public function actionTodayOrder()
    {
        //创建查询条件
        $criteria = new CDbCriteria();
        $criteria->order = 't.create_time DESC';//按时间倒序排

        //如果没有指定日期，默认查询当天的订单统计
        $date = Yii::app()->request->getParam('date');
        if($date)
        {
            $today = strtotime(date($date));
            if(!$today)
            {
                throw new CHttpException(404,'日期格式设置有误');
            }
            else if($today > time())
            {
                throw new CHttpException(404,'设置的日期不能超过今天');
            }
            $tomorrow = $today + 24*3600;
        }
        else
        {
            $today = strtotime(date('Y-m-d'));
            $tomorrow = strtotime(date('Y-m-d',time()+24*3600));
        }

        $criteria->condition = '(t.status = :status1 OR t.status = :status2) AND t.create_time > :today AND t.create_time < :tomorrow';
        $criteria->params = array(':status1' => 1,':status2' => 2,':today' => $today,':tomorrow' => $tomorrow);
        $model = FoodOrder::model()->with('shops','members')->findAll($criteria);
        $data = array();
        $_total_price = 0;
        $tongji = array();
        foreach($model AS $k => $v)
        {
            $_total_price += $v->total_price;
            $data[$k] = $v->attributes;
            $data[$k]['product_info'] = unserialize($v->product_info);
            $data[$k]['shop_name'] = $v->shops->name;
            $data[$k]['user_name'] = $v->members->name;
            $data[$k]['create_time'] = date('Y-m-d H:i:s',$v->create_time);
            $data[$k]['status_text'] = Yii::app()->params['order_status'][$v->status];
            $data[$k]['status_color'] = Yii::app()->params['status_color'][$v->status];
            //统计
            $tongji[$v->shop_id]['name'] = $v->shops->name . '(' . $v->shops->tel . ')';
            $tongji[$v->shop_id]['product'][] = unserialize($v->product_info);
        }

        //统计结果
        $result = array();
        foreach ($tongji AS $k => $v)
        {
            $result[$k]['name'] = $v['name'];
            $shop_total_price = 0;
            foreach($v['product'] AS $_k => $_v)
            {
                foreach ($_v AS $kk => $vv)
                {
                    $shop_total_price += $vv['smallTotal'];
                    $result[$k]['product'][$vv['Id']]['name'] = $vv['Name'];
                    if($result[$k]['product'][$vv['Id']]['count'])
                    {
                        $result[$k]['product'][$vv['Id']]['count'] += $vv['Count'];
                    }
                    else
                    {
                        $result[$k]['product'][$vv['Id']]['count'] = $vv['Count'];
                    }

                    if($result[$k]['product'][$vv['Id']]['smallTotal'])
                    {
                        $result[$k]['product'][$vv['Id']]['smallTotal'] += $vv['smallTotal'];
                    }
                    else
                    {
                        $result[$k]['product'][$vv['Id']]['smallTotal'] = $vv['smallTotal'];
                    }
                }
            }
            $result[$k]['shop_total_price'] = $shop_total_price;
        }

        //菜单
        $pMenu= $pMenu = $this->getCentermenuView( array());

        //输出到前端
        $this->render('today_order', array(
            'data' 	=> $data,
            'statistics' => $result,
            'total_price' => $_total_price,
            'date' => $date,
            'pMenu'=>$pMenu
        ));
    }









    /**
     * 显示公共部分
     * @return string
     */
    protected function getCentermenuView( $params) {
        // 查询出用户的基本信息
        $member_id = Yii::app()->user->member_userinfo['id'];
        $criteria=new CDbCriteria;
        $criteria->select = 'roleid,name,sex,avatar,email,balance';
        $criteria->condition = 'id=:id';
        $criteria->params = array(':id' => $member_id);
        $memberData = Members::model()->find($criteria);
        $memberData = CJSON::decode(CJSON::encode($memberData));
        $shopdata = array();
        if($memberData["roleid"]==1){
            //商家用户
            $shopdata = Shops::model()->find('useid=:id',array(':id'=>$member_id));
            $shopdata = CJSON::decode(CJSON::encode($shopdata));
        }
        $params['shopdata'] = $shopdata;
        $params['memberData'] = $memberData;
        return $this->renderPartial('centermenu', $params, true);
    }


    //获取用户数据通过ajax
    public function actionGetUserInfo()
    {
        //查询出用户的基本信息
        $member_id = Yii::app()->user->member_userinfo['id'];
        $criteria=new CDbCriteria;
        $criteria->select = 'name,sex,avatar,email,balance,mobile,roleid';
        $criteria->condition = 'id=:id';
        $criteria->params = array(':id' => $member_id);
        $memberData = Members::model()->find($criteria);
        $memberData = CJSON::decode(CJSON::encode($memberData));
        if(isset($memberData))
        {
            $this->output(array('success' =>1,'msg'=>'获取用户信息成功','data'=>array('member'=>$memberData)));
        }
        else
        {
            $this->errorOutput(array('error' => 1,'msg'=>'获取用户信息失败'));
        }
    }

    //通过id查询用户订单信息ajax
    public function actionMyOrderAjax()
    {
        $order_id = Yii::app()->request->getParam('order_id');
        $member_id = Yii::app()->user->member_userinfo['id'];
        $criteria = new CDbCriteria;
        $criteria->order = 't.create_time DESC';
        $criteria->select = '*';
        $criteria->condition = 'food_user_id=:food_user_id';
        $criteria->params = array(':food_user_id' => $member_id);

        //构建分页
        $count=FoodOrder::model()->count($criteria);
        $pages = new CPagination($count);
        $pages->pageSize = Yii::app()->params['pagesize'];
        $pages->applyLimit($criteria);
        //按条件获取数据

        $orderDatas = FoodOrder::model()->with('shops','food_log')->findByPk($order_id);
        $orderData=array();
        $orderData["product_info"]=unserialize($orderDatas->product_info);
        $orderData["total"]=0;
        foreach($orderData["product_info"] AS $key=>$value)
        {
            $orderData["product_info"][$key]["total"]=intval($value["Count"])*floatval($value["Price"]);
            $orderData["total"]+=$orderData["product_info"][$key]["total"];
        }
        $orderData["shop_name"] = $orderDatas->shops->name;
        $orderData["shop_id"] = $orderDatas->shops->id;
        $shopLogo=Material::model()->findByPk($orderData['shop_id']);
        $orderData['shop_logo'] = $orderDatas->shops->logo?Yii::app()->params['img_url'] .$shopLogo->filepath .$shopLogo->filename:'';
        $orderData["address"] = $orderDatas->address;
        $orderData["create_order_date"] = date('Y-m-d',$orderDatas->create_time);
        $orderData["create_time"] = date('H:i:s',$orderDatas->create_time);
        $orderData["status_text"] = Yii::app()->params['order_status'][$orderDatas->status];
        $orderData["order_number"] = $orderDatas->order_number;

        //订单状态日志
        $status_log = CJSON::decode(CJSON::encode($orderDatas->food_log));

        foreach ($status_log AS $kk => $vv)
        {
            $status_log[$kk]['status_text'] = Yii::app()->params['order_status'][$vv['status']];
            $status_log[$kk]['create_time'] = date('H:i:s',$vv['create_time']);
        }

        $orderData['status_log'] = $status_log;
        $orderData = CJSON::decode(CJSON::encode($orderData));
        $this->output(array('success' => 1,'successText' => '获取订单信息成功',"data"=>$orderData));
    }
    //查询用户自己的订单列表AJAX
    public function actionMyOrderListAjax()
    {
       /* $is_today = Yii::app()->request->getParam('today');*/
        $member_id = Yii::app()->user->member_userinfo['id'];
        $shopname = Yii::app()->request->getParam('k');
        $criteria = new CDbCriteria;

        $criteria->order = 't.create_time DESC';
        $criteria->select = '*';
        $criteria->condition = 'food_user_id=:food_user_id';
        $criteria->params = array(':food_user_id' => $member_id);

        //构建分页
        $currentPage = Yii::app()->request->getParam('page');
        $pageSize = Yii::app()->request->getParam('pagesize');
        if(!empty($currentPage)) {
            $currentPage = intval( $currentPage );
        } else {
            $currentPage = 1;
        }
        $limit = !empty( $pageSize ) ? intval( $pageSize ) : 10;
        $offset = ($currentPage-1) * $limit;
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        //按条件获取数据


        if(!empty($shopname))
        {
            $criteria->compare('shops.name',$shopname,true,'AND');
        }
        $model = FoodOrder::model()->with('shops','food_log')->findAll($criteria);
        $orderData = array();
        foreach ($model AS $k => $v)
        {
            /*if($is_today)
            {
                //只取今天的订单
                if(date('Ymd',$v->create_time) != date('Ymd',time()))
                {
                    continue;
                }
            }
            else
            {
                //排除今天的订单
                if(date('Ymd',$v->create_time) == date('Ymd',time()))
                {
                    continue;
                }
            }*/

            $orderData[$k] = $v->attributes;
            $orderData[$k]['shop_name'] = $v->shops->name;
            $orderData[$k]['shop_id'] = $v->shops->id;
            $shopLogo=Material::model()->findByPk($orderData[$k]['shop_id']);
            $orderData[$k]['shop_logo'] = $v->shops->logo?Yii::app()->params['img_url'] .$shopLogo->filepath .$shopLogo->filename:'';
            $orderData[$k]['product_info'] = unserialize($v->product_info);
            //添加总个数和总价信息
            $orderData[$k]["total"]=0;
            $orderData[$k]["Count"]=0;
            foreach($orderData[$k]["product_info"] AS $ks=>$vs)
            {
                $orderData[$k]["product_info"][$ks]["total"]=intval($vs["Count"])*floatval($vs["Price"]);
                $orderData[$k]["total"]+=$orderData[$k]["product_info"][$ks]["total"];

                $orderData[$k]["Count"]+=intval($vs["Count"]);

            }

            //给订单信息添加描述
            $orderText="";
            foreach ($orderData[$k]['product_info'] AS $key=>$value)
            {
                if($key<2)
                {
                    $orderText.=$value["Name"].'+';
                }
            }
            $orderText.=" 等".$orderData[$k]["Count"].'件商品';
            $orderData[$k]['order_text']=$orderText;

            $orderData[$k]['create_order_date'] = date('Y-m-d',$v->create_time);
            $orderData[$k]['create_time'] = date('H:i:s',$v->create_time);
            $orderData[$k]['status_text'] = Yii::app()->params['order_status'][$v->status];
            //订单状态日志
            $status_log = CJSON::decode(CJSON::encode($v->food_log));
            foreach ($status_log AS $kk => $vv)
            {
                $status_log[$kk]['status_text'] = Yii::app()->params['order_status'][$vv['status']];
                $status_log[$kk]['create_time'] = date('H:i:s',$vv['create_time']);
            }
            $orderData[$k]['status_log'] = $status_log;
        }
        $this->output(array('success' => 1,'successText' => '获取订单信息成功',"data"=>$orderData));
    }
	//查询用户自己的订单
	public function actionMyOrder()
	{
		$is_today = Yii::app()->request->getParam('today');
		$member_id = Yii::app()->user->member_userinfo['id'];
		$criteria = new CDbCriteria;
		$criteria->order = 't.create_time DESC';
		$criteria->select = '*';
		$criteria->condition = 'food_user_id=:food_user_id';
		$criteria->params = array(':food_user_id' => $member_id);

        //菜单
        $pMenu= $pMenu = $this->getCentermenuView( array());
		//构建分页
		$count=FoodOrder::model()->count($criteria);
		$pages = new CPagination($count);
 		$pages->pageSize = Yii::app()->params['pagesize'];
		$pages->applyLimit($criteria);
		//按条件获取数据
		
		$model = FoodOrder::model()->with('shops','food_log')->findAll($criteria);
		$orderData = array();
		foreach ($model AS $k => $v)
		{
			if($is_today)
			{
				//只取今天的订单
				if(date('Ymd',$v->create_time) != date('Ymd',time()))
				{
					continue;
				}
			}
			else 
			{
				//排除今天的订单
				if(date('Ymd',$v->create_time) == date('Ymd',time()))
				{
					continue;
				}
			}

			$orderData[$k] = $v->attributes;
			$orderData[$k]['shop_name'] = $v->shops->name;
			$orderData[$k]['product_info'] = unserialize($v->product_info);
			$orderData[$k]['create_order_date'] = date('Y-m-d',$v->create_time);
			$orderData[$k]['create_time'] = date('H:i:s',$v->create_time);
			$orderData[$k]['status_text'] = Yii::app()->params['order_status'][$v->status];
			//订单状态日志
			$status_log = CJSON::decode(CJSON::encode($v->food_log));
			foreach ($status_log AS $kk => $vv)
			{
				$status_log[$kk]['status_text'] = Yii::app()->params['order_status'][$vv['status']];
				$status_log[$kk]['create_time'] = date('H:i:s',$vv['create_time']);
			}
			$orderData[$k]['status_log'] = $status_log;
		}
		$cur_title = $is_today?'今日订单':'历史订单';
		$this->render('myorder',array('order' => $orderData,'cur_title' => $cur_title,'pages' => $pages,'pMenu'=> $pMenu));
	}
	
	//前台会员登陆界面
	public function actionLogin()
	{
		//如果已经登陆就直接跳到订单中心（用户中心）
		if(isset(Yii::app()->user->member_userinfo))
		{
			$this->redirect(Yii::app()->createUrl('site/membercenter'));
		}
		else 
		{
			$this->render('login');	
		}
	}
	
	//执行登录操作
	public function actionDoLogin() 
	{
		$name = Yii::app()->request->getParam('name');
		$password = Yii::app()->request->getParam('password');

		if(!$name)
		{
			$this->errorOutput(array('error' => 1,'msg'=>'用户名不能为空'));
		}
		
		if(!$password)
		{
			$this->errorOutput(array('error' => 2,'msg'=>'密码不能为空'));
		}
		
		//利用MemberIdentity来验证
		$identity=new MemberIdentity($name,$password);
		$identity->authenticate();
		
		//登录成功
		if($identity->errorCode===MemberIdentity::ERROR_NONE)
		{
			$duration = 3600*24*30;//保持一个月
			Yii::app()->user->login($identity,$duration);
			$this->output(array('success' =>1,'msg'=>'登录成功'));
		}
		else
		{
			$this->errorOutput(array('error' => 3,'msg'=>'用户名或者密码错误'));
		}		
	}
	
	//会员注册页面
	public function actionRegister()
	{
		$this->render('register');
	}
	
	//执行注册操作
	public function actionDoRegister()
	{
		$name = Yii::app()->request->getPost('name');
		$password1 = Yii::app()->request->getPost('password1');
		$password2 = Yii::app()->request->getPost('password2');
		$mobile = Yii::app()->request->getPost('mobile');

		if(!$name)
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '姓名不能为空'));
		}
        if(!$mobile)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '手机号不能为空'));
        }
		else if(strlen($name) > 15)
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '姓名太长不能超过15个字符'));
		}

		if(!$password1 || !$password2)
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '密码不能为空'));
		}
		else if(strlen($password1) > 15 || strlen($password2) > 15)
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '两次密码不能超过15个字符'));
		}
		else if($password1 !== $password2)
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '两次密码不相符'));
		}
		
		//判断该用户是不是已经存在了
		$_member = Members::model()->find('name=:name',array(':name' => $name));
		if($_member)
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '该用户已经存在'));
		}
		
		//随机长生一个干扰码
		$salt = Common::getGenerateSalt();
		$model = new Members();
		$model->name = $name;
		$model->salt = $salt;
		$model->mobile = $mobile;
		$model->password = md5($salt . $password1);
		$model->create_time = time();
		$model->update_time = time();
		if($model->save())
		{
			$model->order_id = $model->id;
			$model->save();
			$this->output(array('success' => 1,'successText' => '注册成功'));
		}
		else 
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '注册失败'));
		}
	}
    //前端注册店铺和商家账号
    public function actionShopRegisterAjax()
    {
        //商家账号注册
        $name = $_POST['name'];
        $password1 = $_POST['password1'];
        $password2 =$_POST['password2'];

        if(!$name)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '姓名不能为空'));
        }
        else if(strlen($name) > 15)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '姓名太长不能超过15个字符'));
        }

        if(!$password1 || !$password2)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '密码不能为空'));
        }
        else if(strlen($password1) > 15 || strlen($password2) > 15)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '两次密码不能超过15个字符'));
        }
        else if($password1 !== $password2)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '两次密码不相符'));
        }

        //判断该用户是不是已经存在了
        $_member = Members::model()->find('name=:name',array(':name' => $name));
        if($_member)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '该用户已经存在'));
        }

        //随机长生一个干扰码
        $salt = Common::getGenerateSalt();
        $memberModel = new Members();
        $memberModel->name = $name;
        $memberModel->salt = $salt;
        $memberModel->roleid=1;
        $memberModel->password = md5($salt . $password1);
        $memberModel->create_time = time();
        $memberModel->update_time = time();

        if($memberModel->save())
        {
            $memberModel->order_id = $memberModel->id;
            $user_id= $memberModel->id;
            $memberModel->save();
            //创建商铺
            $model=new Shops();
            //处理图片
            if($_FILES['logo'] && !$_FILES['logo']['error'])
            {
                $imgInfo = Yii::app()->material->upload('logo');
                if($imgInfo)
                {
                    $_POST['Shops']['logo'] = $imgInfo['id'];
                }
            }
            if(isset($user_id))
            {
                if(isset($_POST['Shops']))
                {
                    $shopData=array();
                    $shopData=$_POST['Shops'];
                    $shopData['useid']=$user_id;
                    $model->attributes=$shopData;
                    $model->useid = $user_id;
                    //跳过审核
                    $model->status = 2;
                    $model->create_time = time();
                    $model->update_time = time();
                    if($model->save())
                    {
                        $model->order_id = $model->id;
                        $model->save();
                        $this->output(array('success' => 1,'successText' => '注册成功'));
                    }
                    else
                    {
                        throw new CHttpException(404,'创建失败');
                    }
                }
                else
                {
                    throw new CHttpException(404,'no post param');
                }
            }

        }
        else
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '注册失败'));
        }

    }
	
	//会员退出
	public function actionLogout()
	{
		if(isset(Yii::app()->user->member_userinfo))
		{
			unset(Yii::app()->user->member_userinfo);
		}
		$this->redirect(array('site/login'));
	}
    //会员退出ajax
    public function actionLogoutAjax()
    {
        if(isset(Yii::app()->user->member_userinfo))
        {
            unset(Yii::app()->user->member_userinfo);
            $this->output(array('success'=>1,'msg'=>'退出登录成功'));
        }
        else
        {
            $this->errorOutput(array('errorCode'=>0,'errorText'=>'你已经退出登录了'));
        }
    }

    //评论
    public function actionMessageAjax()
    {
        //创建查询条件
        $criteria = new CDbCriteria();
        $criteria->order = 't.order_id DESC';
        //构建分页
        $currentPage = Yii::app()->request->getParam('page');
        $pageSize = Yii::app()->request->getParam('pagesize');
        if(!empty($currentPage)) {
            $currentPage = intval( $currentPage );
        } else {
            $currentPage = 1;
        }
        $limit = !empty( $pageSize ) ? intval( $pageSize ) : 10;
        $offset = ($currentPage-1) * $limit;
        $criteria->offset = $offset;
        $criteria->limit = $limit;

        $member_id = Yii::app()->user->member_userinfo['id'];
        $shopdata = Shops::model()->find('useid=:id',array(':id'=>$member_id));

        $criteria->condition="shop_id=:shop_id";
        $criteria->params=array(':shop_id' =>$shopdata->id);
        $model = Message::model()->with('members','shops','replys')->findAll($criteria);
        $data = array();
        foreach($model AS $k => $v)
        {
            $data[$k] = $v->attributes;
            $data[$k]['shop_name'] = $v->shops->name;
            $data[$k]['user_name'] = $v->members->name;
            //$data[$k]['create_time'] = Yii::app()->format->formatDate($v->create_time);
            $data[$k]['create_time'] = date('Y-m-d H:i:s',$v->create_time);
            $data[$k]['status_text'] = Yii::app()->params['message_status'][$v->status];
            $data[$k]['status_color'] = Yii::app()->params['status_color'][$v->status];

            $_replys = Reply::model()->with('members')->findAll(array(
                'condition' => 'message_id=:message_id',
                'params'	=> array(':message_id' => $v->id),
            ));

            if(!empty($_replys))
            {
                foreach ($_replys AS $kk => $vv)
                {
                    $data[$k]['replys'][$kk] = $vv->attributes;
                    $data[$k]['replys'][$kk]['create_time'] 	= date('Y-m-d H:i:s',$vv->create_time);
                    $data[$k]['replys'][$kk]['user_name'] 	= ($vv->user_id == -1)?'商家回复：':$vv->members->name;
                }
            }
        }

        //输出到前端
        $this->output(array(
            'data' 	=> $data,
            'success'=>1
        ));
    }


	//评论
    public function actionMessage()
    {
        //创建查询条件
        $criteria = new CDbCriteria();
        $criteria->order = 't.order_id DESC';
        $count=Message::model()->count($criteria);
        //构建分页
        $pages = new CPagination($count);
        $pages->pageSize = Yii::app()->params['pagesize'];
        $pages->applyLimit($criteria);

        $member_id = Yii::app()->user->member_userinfo['id'];
        $shopdata = Shops::model()->find('useid=:id',array(':id'=>$member_id));

        $criteria->condition="shop_id=:shop_id";
        $criteria->params=array(':shop_id' =>$shopdata->id);
        $model = Message::model()->with('members','shops')->findAll($criteria);
        $data = array();
        foreach($model AS $k => $v)
        {
            $data[$k] = $v->attributes;
            $data[$k]['shop_name'] = $v->shops->name;
            $data[$k]['user_name'] = $v->members->name;
            $data[$k]['create_time'] = Yii::app()->format->formatDate($v->create_time);
            $data[$k]['status_text'] = Yii::app()->params['message_status'][$v->status];
            $data[$k]['status_color'] = Yii::app()->params['status_color'][$v->status];
        }
        $pMenu= $pMenu = $this->getCentermenuView(array());
        //输出到前端
        $this->render('message', array(
            'data' 	=> $data,
            'pages'	=> $pages,
            'pMenu'=>$pMenu
        ));
    }
	
	//修改密码页面
	public function actionmodifyPassword()
	{
	    $pMenu= $pMenu = $this->getCentermenuView( array());
		$this->render('modifypassword',array("pMenu"=>$pMenu));
	}

    //确认修改
    public function actionDomodifyForApp()
    {
        $cur_password = Yii::app()->request->getPost('cur_password');
        $new_password = Yii::app()->request->getPost('new_password');
        $comfirm_password = Yii::app()->request->getPost('comfirm_password');

        if(!$cur_password)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '当前密码不能为空'));
        }

        if(!$new_password || !$comfirm_password)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '新密码不能为空'));
        }
        else if(strlen($new_password) > 15 || strlen($comfirm_password) > 15)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '新密码不能超过15个字符'));
        }
        else if($new_password !== $comfirm_password)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '两次密码不相符'));
        }

        //判断该用户是不是已经存在了
        $_member = Members::model()->find('id=:id',array(':id' => Yii::app()->user->member_userinfo['id']));
        if(!$_member)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '当前用户不存在'));
        }
        else if(md5($_member->salt . $cur_password) != $_member->password)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '当前密码输入错误'));
        }

        //随机长生一个干扰码
        $salt = Common::getGenerateSalt();
        $_member->salt = $salt;
        $_member->password = md5($salt . $new_password);
        $_member->update_time = time();
        if($_member->save())
        {
            $this->output(array('success' => 1,'successText' => '修改成功'));
        }
        else
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '修改失败'));
        }
    }
	
	//确认修改
	public function actionDomodify()
	{
		$cur_password = Yii::app()->request->getPost('cur_password');
		$new_password = Yii::app()->request->getPost('new_password');
		$comfirm_password = Yii::app()->request->getPost('comfirm_password');

		if(!$cur_password)
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '当前密码不能为空'));
		}

		if(!$new_password || !$comfirm_password)
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '新密码不能为空'));
		}
		else if(strlen($new_password) > 15 || strlen($comfirm_password) > 15)
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '新密码不能超过15个字符'));
		}
		else if($new_password !== $comfirm_password)
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '两次密码不相符'));
		}
		
		//判断该用户是不是已经存在了
		$_member = Members::model()->find('id=:id',array(':id' => Yii::app()->user->member_userinfo['id']));
		if(!$_member)
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '当前用户不存在'));
		}
		else if(md5($_member->salt . $cur_password) != $_member->password)
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '当前密码输入错误'));
		}
		
		//随机长生一个干扰码
		$salt = Common::getGenerateSalt();
		$_member->salt = $salt;
		$_member->password = md5($salt . $new_password);
		$_member->update_time = time();
		if($_member->save())
		{
			$this->output(array('success' => 1,'successText' => '修改成功'));
		}
		else 
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '修改失败'));
		}
	}
	
	//系统公告
	public function actionSystemNotice()
	{
		//查询出公告数据
		$notice = Announcement::model()->findAll(array('order' => 'create_time DESC','condition' => 'status=:status','params'=>array(':status'=>2)));
		$notice = CJSON::decode(CJSON::encode($notice));
        $pMenu = $this->getCentermenuView( array());
		foreach($notice AS $k => $v)
		{
			$notice[$k]['create_time'] = date('Y-m-d',$v['create_time']);
		}
		$this->render('systemnotice',array('announce' => $notice,'pMenu' => $pMenu));
	}
	
	//用户取消订单
	public function actionCancelOrder()
	{
			$food_order_id = Yii::app()->request->getParam('id');
			if(!$food_order_id)
			{
				$this->errorOutput(array('errorCode' => 1,'errorText' => '没有id'));
			}

			//判断当前时间有没有已经过了订餐的时间，如果过了订餐的时间，就不能取消订单了，只能让妹子操作后台取消
    		if(!Yii::app()->check_time->isOnTime())
    		{
    			$this->errorOutput(array('errorCode' => 1,'errorText' => '已经过了订餐时间，您暂时不能取消订单，如果确实需要取消，请联系前台妹子'));
    		}

			$orderInfo = FoodOrder::model()->find('id=:id AND food_user_id=:food_user_id',array(':id' => $food_order_id,':food_user_id' => Yii::app()->user->member_userinfo['id']));
			if(!$orderInfo)
			{
				$this->errorOutput(array('errorCode' => 1,'errorText' => '该订单不存在'));
			}
			else if($orderInfo->status != 1)
			{
				$this->errorOutput(array('errorCode' => 1,'errorText' => '该订单不能被取消'));
			}

			$orderInfo->status = 3;
			if($orderInfo->save())
			{
				//创建一条订单日志
				$foodOrderLog = new FoodOrderLog();
				$foodOrderLog->food_order_id = $food_order_id;
				$foodOrderLog->status = $orderInfo->status;
				$foodOrderLog->create_time = time();
				if($foodOrderLog->save())
				{
					$this->output(array('success' => 1,'successText' => '取消订单成功'));
				}
				else
				{
					$this->errorOutput(array('errorCode' => 1,'errorText' => '更新订单状态失败'));
				}
			}
			else
			{
				$this->errorOutput(array('errorCode' => 1,'errorText' => '取消订单失败'));
			}
	}

    //用户确认收到订单
    public function actionFinishOrder()
    {
        $food_order_id = Yii::app()->request->getParam('id');
        if(!$food_order_id)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '没有id'));
        }

        //判断当前时间有没有已经过了订餐的时间，如果过了订餐的时间，就不能取消订单了，只能让妹子操作后台取消
        if(!Yii::app()->check_time->isOnTime())
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '已经过了订餐时间，您暂时不能取消订单，如果确实需要取消，请联系前台妹子'));
        }

        $orderInfo = FoodOrder::model()->find('id=:id AND food_user_id=:food_user_id',array(':id' => $food_order_id,':food_user_id' => Yii::app()->user->member_userinfo['id']));
        if(!$orderInfo)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '该订单不存在'));
        }
        else if($orderInfo->status != 2)
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '该订单不能被确认'));
        }

        $orderInfo->status = 5;
        if($orderInfo->save())
        {
            //创建一条订单日志
            $foodOrderLog = new FoodOrderLog();
            $foodOrderLog->food_order_id = $food_order_id;
            $foodOrderLog->status = $orderInfo->status;
            $foodOrderLog->create_time = time();
            if($foodOrderLog->save())
            {
                $this->output(array('success' => 1,'successText' => '完成订单成功'));
            }
            else
            {
                $this->errorOutput(array('errorCode' => 1,'errorText' => '更新订单状态失败'));
            }
        }
        else
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '完成订单失败'));
        }
    }
	
	//美食分享
	public function actionFoodShare()
	{
		$this->render('foodshare');
	}
	
	//提交留言
	public function actionSubmitMessage()
	{
		$content = Yii::app()->request->getParam('content');
		$validate_code = Yii::app()->request->getParam('validate_code');
		$shop_id = Yii::app()->request->getParam('shop_id');
		
		if(!isset(Yii::app()->user->member_userinfo))
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '你还未登录，请先去登录'));
		}
		else 
		{
			$user_id = Yii::app()->user->member_userinfo['id'];
		}
		
		if(!$content)
		{
			$this->errorOutput(array('errorCode' => 2,'errorText' => '留言内容不能为空'));
		}
		
		if(!$validate_code)
		{
			$this->errorOutput(array('errorCode' => 3,'errorText' => '验证码不能为空'));
		}
		
		if(!$shop_id)
		{
			$this->errorOutput(array('errorCode' => 4,'errorText' => '没有商店id'));
		}
		
		//验证验证码是否正确
		if(!$this->createAction('captcha')->validate($validate_code,false))
		{
			$this->errorOutput(array('errorCode' => 5,'errorText' => '验证码有误'));
		}
		
		$model = new Message();
		$model->shop_id = $shop_id;
		$model->user_id = $user_id;
		$model->content = $content;
		$model->create_time = time();
		if($model->save())
		{
			$model->order_id = $model->id;
			if($model->save())
			{
				$this->output(array('success' => 1,'successText' => '留言成功'));
			}
			else 
			{
				$this->errorOutput(array('errorCode' => 6,'errorText' => '留言失败'));
			}
		}
		else 
		{
			$this->errorOutput(array('errorCode' => 6,'errorText' => '留言失败'));
		}
	}

    //提交留言
    public function actionSubmitMessageForApp()
    {
        $content = Yii::app()->request->getParam('content');
        $shop_id = Yii::app()->request->getParam('shop_id');

        if(!isset(Yii::app()->user->member_userinfo))
        {
            $this->errorOutput(array('errorCode' => 1,'errorText' => '你还未登录，请先去登录'));
        }
        else
        {
            $user_id = Yii::app()->user->member_userinfo['id'];
        }

        if(!$content)
        {
            $this->errorOutput(array('errorCode' => 2,'errorText' => '留言内容不能为空'));
        }


        if(!$shop_id)
        {
            $this->errorOutput(array('errorCode' => 4,'errorText' => '没有商店id'));
        }


        $model = new Message();
        $model->shop_id = $shop_id;
        $model->user_id = $user_id;
        $model->content = $content;
        $model->create_time = time();
        if($model->save())
        {
            $model->order_id = $model->id;
            if($model->save())
            {
                $this->output(array('success' => 1,'successText' => '留言成功'));
            }
            else
            {
                $this->errorOutput(array('errorCode' => 6,'errorText' => '留言失败'));
            }
        }
        else
        {
            $this->errorOutput(array('errorCode' => 6,'errorText' => '留言失败'));
        }
    }
	
	//回复留言
	public function actionReplyMessage()
	{
		$message_id = Yii::app()->request->getParam('reply_id');
		$reply_content = Yii::app()->request->getParam('reply_content');
		
		if(!isset(Yii::app()->user->member_userinfo))
		{
			$this->errorOutput(array('errorCode' => 1,'errorText' => '你还未登录，请先去登录'));
		}
		else 
		{
			$user_id = Yii::app()->user->member_userinfo['id'];
		}
		
		if(!$reply_content)
		{
			$this->errorOutput(array('errorCode' => 2,'errorText' => '回复内容不能为空'));
		}
		
		if(!$message_id)
		{
			$this->errorOutput(array('errorCode' => 3,'errorText' => '未选择回复留言'));
		}
		
		$model = new Reply();
		$model->message_id = $message_id;
		$model->user_id = $user_id;
		$model->content = $reply_content;
		$model->create_time = time();
		if($model->save())
		{
			$this->output(array('success' => 1,'successText' => '回复成功'));
		}
		else 
		{
			$this->errorOutput(array('errorCode' => 4,'errorText' => '回复失败'));
		}
	}
	
	//查看消费记录（充值与扣款的记录）
	public function actionSeeConsume()
	{
	    $userId = Yii::app()->user->member_userinfo['id'];
	    $criteria = new CDbCriteria;
		$criteria->condition = 't.user_id=:user_id';
		$criteria->order = 't.create_time DESC';
		$criteria->params=array(':user_id' => $userId);
		
		$count = RecordMoney::model()->count($criteria);
		//构建分页
		$pages = new CPagination($count);
 		$pages->pageSize = Yii::app()->params['pagesize'];
		$pages->applyLimit($criteria);
		$record = RecordMoney::model()->with('members')->findAll($criteria);
		$data = array();
		foreach ($record AS $k => $v)
		{
			$data[$k] = $v->attributes;
			$data[$k]['type_text'] = Yii::app()->params['record_money'][$v['type']];
			$data[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
			$data[$k]['user_name'] = $v->members->name;
		}
		$this->render('seeconsume',array(
			'data' => $data,
			'pages'	=> $pages
		));
	}
}