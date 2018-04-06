<?php 
if(isset($data) && $data)
{
	$a = 'update';
	$op_text = '更新';
	$_action = Yii::app()->createUrl('menus/updateAjax');
}
else 
{
	$a = 'create';
	$op_text = '创建';
	$_action = Yii::app()->createUrl('menus/createAjax');
}
?>
<script>
    function submits()
    {
        $form=$("#form_data");
        var formData = new FormData($form[0]);//表单id
        var url=$form.attr("_href");
        $.ajax({
            url:url ,
            type: 'POST',
            data: formData,
            async: false,
            cache: false,
            dataType:"json",
            contentType: false,
            processData: false,
            success: function (ret) {
                if(ret.success)
                {
                    alert("创建成功");
                    window.location.reload();

                }
                else
                {
                    alert(ret.errorText);
                }
            }
        });
    }
</script>
<div class="shadow clearfix" id="pCenter">
    <?php echo $pMenu;?>
<div class="content-box" id="pContent">
      <!-- Start Content Box -->
      <div class="content-box-header">
        <h3><?php echo $op_text;?>菜单</h3>
        <ul class="content-box-tabs">
          <li><a href="#tab1" class="default-tab">表单</a></li>
        </ul>
        <div class="clear"></div>
      </div>
	  <div class="content-box-content">
		<div class="tab-content default-tab" id="tab1">
          <form _href="<?php echo $_action;?>" id="form_data"  enctype="multipart/form-data">
            <fieldset>
            <p>
              <label>菜名</label>
              <input class="text-input small-input" type="text"  name="Menus[name]" value="<?php echo CHtml::encode($data['name']); ?>"/>
            </p>
            
            <p>
              <label>图片</label>
              <input class="text-input small-input" type="file"  name="index_pic" />
            </p>
            
            <p>
             	<img src="<?php echo CHtml::encode($data['index_pic']);?>" width="160" hieght="120" />
            </p>
            
            <!--  
            <p>
              <label>分类</label>
              <input class="text-input small-input" type="text" name="Menus[sort_id]" value="<?php echo CHtml::encode($data['sort_id']); ?>" />
            </p>
            -->
            <!--商家-->
                <input type="hidden" name="Menus[shop_id]" value="<?php echo $shopData['id']?>">
            
            <p>
              <label>价格</label>
              <input class="text-input small-input" type="text" name="Menus[price]" value="<?php echo CHtml::encode($data['price']); ?>"/> 元/份
            </p>
            
            <p>
              <label>简介</label>
              <textarea class="text-input textarea" name="Menus[brief]" cols="79" rows="15"><?php echo CHtml::encode($data['brief']); ?></textarea>
            </p>
            
            <p>
              <input type="hidden" name="id" value="<?php echo $data['id'];?>" />
              <input class="button" id="save" type="button" onclick="submits()" value="<?php echo $op_text;?>" />
            </p>
            </fieldset>
            <div class="clear"></div>
            <!-- End .clear -->
          </form>
        </div>
     </div>
 </div>
</div>