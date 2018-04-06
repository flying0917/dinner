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
<div class="content-box" id="pContent">
      <!-- Start Content Box -->
      <div class="content-box-header">
        <h3>订单详情</h3>
        <ul class="content-box-tabs">
          <li><a href="#tab1" class="default-tab">表单</a></li>
        </ul>
        <div class="clear"></div>
      </div>
	  <div class="content-box-content">
		<div class="tab-content default-tab" id="tab1">
          <form action="#" method="post" enctype="multipart/form-data">
            <fieldset>
	            <p>
	              <label>订单号：<?php echo $data['order_number'];?></label>
	            </p>
	            
	            <p>
	              <label>餐厅名：<?php echo $data['shop_name'];?></label>
	            </p>
	            
	            <p>
	              <label>下单人：<?php echo $data['user_name'];?></label>
	            </p>
	            
	            <p>
	              <label>总价：<?php echo $data['total_price'];?>元</label>
	            </p>
	            
	            <p>
	               <?php foreach($data['product_info'] AS $k => $v):?>
	              <label><?php echo $v['Name'];?>x<?php echo $v['Count'];?>------------------------<?php echo $v['Price'];?>元</label>
	              <?php endforeach;?>
	            </p>
	            
	            <p>
	              <label>下单时间：<?php echo $data['create_time'];?></label>
	            </p>
	            
	            <p>
	              <label>订单动态</label>
	            </p>
	            
            </fieldset>
            <div class="clear"></div>
            <!-- End .clear -->
          </form>
        </div>
     </div>
 </div>
</div>