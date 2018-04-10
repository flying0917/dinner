<script type="text/javascript">
$(function(){
    //普通用户注册
	$('#RegistIn').click(function(){
		var name = $('#NickName').val();
		var password1 = $('#PassWord1').val();
		var password2 = $('#PassWord2').val();
		var mobile = $('#mobile').val();

		$('.logTips').hide();

		if(!name)
		{
			$('#name_tip').text('姓名不能为空').show();
			return;
		}
        if(!mobile)
        {
            $('#mobile_tip').text('手机号不能为空').show();
            return;
        }
		else if(name.length > 15)
		{
			$('#name_tip').text('姓名太长了').show();
			return;
		}

		if(!password1 || !password2)
		{
			$('#ps2_tip').text('密码不能为空').show();
			return;
		}
		else if(password1.length > 15 || password2.length > 15)
		{
			$('#ps2_tip').text('您设定的密码太长了，不能超过15个字符').show();
			return;
		}
		else if(password1 !== password2)
		{
			$('#ps2_tip').text('两次密码不相等').show();
			return;
		}

		var url = $('#submit_url').val();
		$.ajax({
			type:'POST',
			url:url,
			dataType: "json",
			data:{name:name,password1:password1,password2:password2,mobile:mobile},
			success:function(data){
				if(data.errorCode)
				{
					$('#regist_result').show().text(data.errorText);
				}
				else if(data.success)
				{
					alert('注册成功');
					window.location.href=$('#login_url').val();
				}
			}
		})
	})
    //验证表单
    function validateFormData(formId,cb)
    {
        $(formId+" input[required]").each(function()
        {
            if(!$(this).val())
            {
                var tipText=$(this).attr("data-name")+"不能为空";
                $(this).next(".logTips").text(tipText);
                $(this).next(".logTips").fadeIn();
                cb(false);
            }
            else
            {
                $(this).next(".logTips").fadeOut();
            }
        });

        $(formId+" input[maxlength]").each(function()
        {
            var maxlength=parseInt($(this).attr("maxlength")),
                nowlength=$(this).val().length;
            if(nowlength!==0)
            {
                if(nowlength>maxlength)
                {
                    var tipText="您设定的"+$(this).attr("data-name")+"太长了，"+"不能超过"+maxlength+"字符";
                    $(this).next(".logTips").text(tipText);
                    $(this).next(".logTips").fadeIn();
                    cb(false);
                }
                else
                {
                    $(this).next(".logTips").fadeOut();
                }
            }
        });
        $(formId+" input[data-identical]").each(function()
        {
            var targetValue=$("#"+$(this).attr("data-identical")).val(),
                targetText=$("#"+$(this).attr("data-identical")).attr("data-name"),
                value=$(this).val();
            if(value.length!==0&&targetValue.length!==0)
            {
                if(targetValue!==value)
                {
                    var tipText="两次"+targetText+"不一致";
                    $(this).next(".logTips").text(tipText);
                    $(this).next(".logTips").fadeIn();
                    cb(false);
                }
                else
                {
                    $(this).next(".logTips").fadeOut();
                }
            }
        });
        cb(true);
    }

    //商家用户注册
    $('#shopRegistIn').click(function(){

        validateFormData("#shop_form",function(ret)
        {
            if(ret)
            {
                var fd = new FormData($("#shop_form")[0]);
                var url = $('#shop_register_url').val();
                $.ajax({
                    type:'POST',
                    url:url,
                    dataType: "json",
                    data:fd,
                    processData: false,  // 不处理数据
                    contentType: false,   // 不设置内容类型
                    success:function(data){
                        if(data.errorCode)
                        {
                            $('#regist_result').show().text(data.errorText);
                        }
                        else if(data.success)
                        {
                            alert('注册成功');
                            window.location.href=$('#login_url').val();
                        }
                    }
                })
            }
        });
    })
})
</script>
<style>
    #container .login h3
    {
        color:gray;
        border:none;
        cursor:pointer;
    }
    #container .login .active
    {
        color:#EB781F !important;
        border-bottom:1px solid #EB781F !important;
    }
    .hidden
    {
        display:none;
    }
</style>

<div class="login shadow clearfix" style="padding-bottom: 20px;">

                <div class="login-title">
                    <h3 data-target="user" class="active">普通用户注册</h3>
                    <h3 data-target="shop">商家用户注册</h3>
                </div>
                <ul class="login-content" id="user">
                	<li class="login_name">
                        <label>姓名：</label>
                        <input name="name"  type="text" maxlength="20" id="NickName">
                        <p class="logTips" id="name_tip" style="display: none;"></p>
                    </li>
                    <li class="login_password">
                        <label>手机号：</label>
                        <input name="mobile" type="text" maxlength="15" id="mobile">
                        <p class="logTips"  id="mobile_tip"  style="display: none;"></p>
                    </li>
                    <li class="login_password">
                        <label>密码：</label>
                        <input name="password1" type="password" maxlength="15" id="PassWord1">
                        <p class="logTips"  id="ps1_tip"  style="display: none;"></p>
                    </li>
                    <li class="sure_password">
                        <label>
                            确认密码：</label>
                        <input name="password2" type="password" maxlength="15" id="PassWord2">
                        <p class="logTips" id="ps2_tip" style="display: none;"></p>
                    </li>
                    
                    <li class="regist_btn">
                        <input type="submit" name="RegistIn" value="注册" id="RegistIn">
                        <span id="regist_result"></span>
                        <input type="hidden" id="submit_url" value="<?php echo Yii::app()->createUrl('site/doregister');?>">
                        <input type="hidden" id="login_url" value="<?php echo Yii::app()->createUrl('site/login');?>">
                    </li>
                </ul>
                <ul class="login-content hidden" id="shop">
                    <form id="shop_form">
                        <li class="login_name">
                            <label>姓名：</label>
                            <input name="name" data-name="姓名" required id="shop_usern requiredame" type="text" maxlength="20" >
                            <p class="logTips" id="name_tip" style="display: none;"></p>
                        </li>
                        <li class="login_password">
                            <label>密码：</label>
                            <input name="password1" data-name="密码" required id="shop_password"  type="password" maxlength="15">
                            <p class="logTips"  id="ps1_tip"  style="display: none;"></p>
                        </li>
                        <li class="sure_password">
                            <label>
                                确认密码：</label>
                            <input name="password2" data-name="确认密码" required data-identical="shop_password" id="shop_repassword" type="password" maxlength="15">
                            <p class="logTips" id="ps2_tip" style="display: none;"></p>
                        </li>



                        <li>
                            <label>商店名称</label>
                            <input class="text-input small-input" data-name="商店名称" required type="text" id="shop_name" name="Shops[name]" value=""/>
                            <p class="logTips"  style="display: none;"></p>
                        </li>
                        <li>
                            <label>logo</label>
                            <input class="text-input small-input" type="file"  name="logo" />
                        </li>
                        <li>
                            <label>地址</label>
                            <input class="text-input small-input" required data-name="地址" id="shop_address" type="text" name="Shops[address]" value="" />
                            <p class="logTips"  style="display: none;"></p>
                        </li>
                        <li>
                            <label>电话</label>
                            <input class="text-input small-input" data-name="电话" required id="shop_phone" type="text" name="Shops[tel]" value=""/>
                            <p class="logTips"  style="display: none;"></p>
                        </li>
                        <li>
                            <label>联系人</label>
                            <input class="text-input small-input" data-name="联系人" required id="shop_linkman" type="text" name="Shops[linkman]" value=""/>
                            <p class="logTips"  style="display: none;"></p>
                        </li>
                        <li>
                            <label>商家网站链接</label>
                            <input class="text-input small-input" type="text" name="Shops[url]" value=""/>
                        </li>


                        <li class="regist_btn">
                            <input type="button" name="RegistIn" value="注册" id="shopRegistIn">
                            <span id="regist_result"></span>
                            <input type="hidden" id="shop_register_url" value="<?php echo Yii::app()->createUrl('site/shopRegisterAjax');?>">
                        </li>
                    </form>
                </ul>
                <div class="member">
                    <span>已有开吃吧帐号？</span> <a href="<?php echo Yii::app()->createUrl('site/login')?>">请登录</a>
                </div>
</div>
<script>
    $(function()
    {
        $(".login .login-title h3").click(function()
        {
            var targetId=$(this).attr("data-target");
            $(".login .login-title h3").removeClass("active");
            $(this).addClass("active");
            $(".login-content").addClass("hidden");
            $("#"+targetId).removeClass("hidden");
        });
    });
</script>