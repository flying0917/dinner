<?php
/*加载js*/
Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . "/assets/js/jquery-1.3.2.min.js");
Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . "/assets/js/common.js");
Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . "/assets/js/jquery.wysiwyg.js");
Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . "/assets/js/facebox.js");
Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . "/assets/js/simpla.jquery.configuration.js");

/*加载css*/
Yii::app()->clientScript->registerCssFile(Yii::app()->baseUrl . "/assets/css/reset.css");
Yii::app()->clientScript->registerCssFile(Yii::app()->baseUrl . "/assets/css/style.css");
Yii::app()->clientScript->registerCssFile(Yii::app()->baseUrl . "/assets/css/invalid.css");
?>
<?php if($memberData["roleid"]==1){ ?>


    <div id="pMenu" class="gray" style="height: 519px;">
        <div class="border">
            <dl>
                <dt>商家中心</dt>
                <dd><a href="<?php echo Yii::app()->createUrl('site/membercenter');?>" class="n1">我的帐户</a></dd>
                <dd><a href="<?php echo Yii::app()->createUrl('site/modifypassword');?>" class="n10">修改密码</a></dd>
            </dl>
        </div>
        <div class="border">
            <dl>
                <dt>订单中心</dt>
                <dd><a href="<?php echo Yii::app()->createUrl('site/foodorder',array('today' => 1));?>" class="n2">订单管理</a></dd>

            </dl>
        </div>
        <div class="border">
            <dl>
                <dt>菜单中心</dt>
                <dd><a href="<?php echo Yii::app()->createUrl('site/menus',array('shop_id' => $shopdata['id']));?>" class="n2">菜单管理</a></dd>
            </dl>
        </div>

        <div class="border">
            <dl>
                <dt>评论中心</dt>
                <dd><a href="<?php echo Yii::app()->createUrl('site/message',array('shop_id' => $shopdata['id']));?>" class="n2">留言管理</a></dd>
            </dl>
        </div>
        <div class="border">
            <dl>
                <dt>信息中心</dt>
                <dd><a href="<?php echo Yii::app()->createUrl('site/systemnotice');?>" class="n7">系统公告</a></dd>
            </dl>
        </div>
    </div>

<?php }else{ ?>
    <div id="pMenu" class="gray" style="height: 519px;">
    <div class="border">
        <dl>
            <dt>个人中心</dt>
            <dd><a href="<?php echo Yii::app()->createUrl('site/membercenter');?>" class="n1">我的帐户</a></dd>
            <dd><a href="<?php echo Yii::app()->createUrl('site/modifypassword');?>" class="n10">修改密码</a></dd>
        </dl>
    </div>
    <div class="border">
        <dl>
            <dt>订单中心</dt>
            <dd><a href="<?php echo Yii::app()->createUrl('site/myorder',array('today' => 1));?>" class="n2">今日订单</a></dd>
            <dd><a href="<?php echo Yii::app()->createUrl('site/myorder');?>" class="n3">历史订单</a></dd>
            <dd><a href="<?php echo Yii::app()->createUrl('site/seeconsume');?>" class="n3">消费记录</a></dd>
        </dl>
    </div>
    <div class="border">
        <dl>
            <dt>信息中心</dt>
            <dd><a href="<?php echo Yii::app()->createUrl('site/systemnotice');?>" class="n7">系统公告</a></dd>
        </dl>
    </div>
    </div>
<?php } ?>