
<script type="text/javascript">
$(function(){
	$('.deduct_money').click(function(){
		if(confirm('您确认接单？'))
		{
			var url = $(this).attr('_href');
			$.ajax({
				type:'GET',
				url:url,
				dataType: "json",
				success:function(data){
					if(data.errorCode)
					{
						alert(data.errorText);
					}
					else if(data.success)
					{
						alert('接单成功');
						window.location.reload();
					}
				}
			})
		}
	})

	$('.cancel_order').click(function(){
		if(confirm('您确认要取消订单'))
		{
			var url = $(this).attr('_href');
			$.ajax({
				type:'GET',
				url:url,
				dataType: "json",
				success:function(data){
					if(data.errorCode)
					{
						alert(data.errorText);
					}
					else if(data.success)
					{
						alert('取消订单成功');
						window.location.reload();
					}
				}
			})
		}
	})

	//根据日期查询订单
	$('#searchOrder').click(function(){
		var date = $('#date').val();
		if(!date)
		{
			alert('请输入要查询订单的日期，格式如：2014-05-20');
			return;
		}

		var url = "<?php echo Yii::app()->createUrl('site/todayorder');?>";
		window.location.href = url+'&date='+date;
	})

	//当天订单一健扣款
	$('#onekey').click(function(){
		if(confirm('您确定要接收所有订单吗？'))
		{
			var url = "<?php echo Yii::app()->createUrl('foodorder/onekey');?>";
			$.ajax({
				type:'GET',
				url:url,
				dataType: "json",
				success:function(data){
					if(data.errorCode)
					{
						alert(data.errorText);
					}
					else if(data.success)
					{
						alert(data.successText);
						window.location.reload();
					}
				}
			})
		}
	})
})
</script>
<style>
    ul.content-box-tabs
    {
        padding:3px 15px 0 0 !important;
    }
    #container h3
    {
        margin-top:0;
        padding:10px;
    }
    .button
    {
        padding:0;
        line-height:normal;
    }
    table tr
    {
        height:40px;
        line-height: 40px;
    }
    table td
    {
        min-width:15px;
    }
    table tr:nth-of-type(even)
    {
        background-color: #e6e6e6;
    }
    .pull-left
    {
        float:left;
    }
</style>
<div class="shadow clearfix" id="pCenter">
    <?php echo $pMenu;?>

    <div id="pAccount" class="content-box">
        <!-- Start Content Box -->
        <div class="content-box-header">
            <h3>订单信息</h3>
            <ul class="content-box-tabs">
                <li><a href="#tab1" class="default-tab">列表</a></li>
            </ul>
            <div class="clear"></div>
        </div>
        <!-- End .content-box-header -->
        <div style="padding:15px;">
            <input type="button" value="一键接单" class="button" id="onekey" style="margin-right:15px;"/>
            <label style="color: red">温馨提示：只扣除今天的未付款订单，账户余额不足的不予处理</label>
        </div>
        <div class="content-box-content" style="padding:15px;">
            <div class="tab-content default-tab" id="tab1">
                <table style="width:100%;">
                    <thead>
                    <tr>
                        <th>
                            <input class="check-all" type="checkbox" />
                        </th>
                        <th>订单号</th>
                        <th>所属商家</th>
                        <th>用户名</th>
                        <th>订单状态</th>
                        <th>总价</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(isset($data)):?>
                        <?php foreach ($data AS $k => $v):?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="infolist[]" value="<?php echo $v['id'];?>" />
                                </td>
                                <td><?php echo $v['order_number'];?></td>
                                <td><?php echo $v['shop_name'];?></td>
                                <td><?php echo $v['user_name'];?></td>
                                <td style="cursor:pointer;color:<?php echo $v['status_color'];?>"><?php echo $v['status_text'];?></td>
                                <td><?php echo $v['total_price'];?></td>
                                <td><?php echo $v['create_time'];?></td>
                                <td>
                                    <!-- Icons -->
                                    <a style="padding:0 !important;" href="javascript:void(0);" _href="<?php echo Yii::app()->createUrl('foodorder/deductmoney',array('id' => $v['id']));?>" class="deduct_money button">接单</a>
                                    <a style="padding:0 !important;" href="javascript:void(0);" _href="<?php echo Yii::app()->createUrl('foodorder/cancelorder',array('id' => $v['id']));?>" class="cancel_order button">取消订单</a>
                                    <a href="<?php echo Yii::app()->createUrl('site/FoodOrderForm',array('id' => $v['id']));?>" title="查看"><img src="<?php echo Yii::app()->baseUrl;?>/assets/images/icons/information.png" alt="查看" /></a>
                                    <a href="javascript:void(0);"  _href="<?php echo Yii::app()->createUrl('foodorder/deleteajax',array('id' => $v['id']));?>"  class="remove_row_ajax"><img src="<?php echo Yii::app()->baseUrl;?>/assets/images/icons/cross.png" alt="Delete" /></a>
                                </td>
                            </tr>
                        <?php endforeach;?>
                    <?php endif;?>
                    </tbody>
                </table>
                <div>
                <div>
                    <div class="pull-left" style="margin-right:5px;">
                        <a href="<?php echo Yii::app()->createUrl('site/todayorder');?>" class="button">今日订单统计快速通道</a>
                    </div>
                    <div class="pull-left" style="margin-right:5px;">
                        <label>按日期查询订单：</label>
                    </div>
                    <div class="pull-left" style="margin-right:5px;">
                        <input class="text-input small-input" type="date"  id="date"  style="margin-right:5px;"/>
                        <input type="button" class="button" value="查询" id="searchOrder" />
                    </div>
                    <div class="pull-left">
                        <?php $this->widget('application.widgets.MyLinkPager', array(
                            'pages' 			=> $pages,
                            'firstPageLabel' 	=> '首页',
                            'lastPageLabel' 	=> '末页',
                            'prevPageLabel' 	=> '前一页',
                            'nextPageLabel' 	=> '下一页',
                            'firstPageLabel' 	=> '首页',
                            'maxButtonCount' 	=> '5',
                            'header'			=> '',
                        ));
                        ?>
                        <!-- End .pagination -->
                        <div class="clear"></div>
                    </div>
                </div>
                </div>
            </div>
        </div>
        <!-- End .content-box-content -->
    </div>
</div>