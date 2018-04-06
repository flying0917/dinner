<div class="shadow clearfix" id="pCenter">
    <?php echo $pMenu;?>

                <div id="pAccount">
                    <div id="ac_info" class="acc_con">
                        <p class="ac_title">个人信息</p>
                        <div class="ac_content">
                            <table>
                                <colgroup>
                                <col class="info_title">
                                <col class="info_con">
                                <col class="info_title">
                                <col class="info_con">
                                </colgroup>
                                <tbody>
	                                <tr>
	                                    <td>
	                                        <b>我的邮箱</b>
	                                    </td>
	                                    <td>
	                                        <?php echo $member['email'];?>
	                                    </td>
	                                    <td>
	                                        <b>我的名称：</b>
	                                    </td>
	                                    <td>
	                                        <p id="nickName" class="pos_relative">
	                                            <span id="dispName">
	                                            <!--<a href="javascript:;" class="fr" id="chgName">修改昵称</a>-->
	                                            <?php echo $member['name'];?>
	                                            </span>
	                                            <span id="updateName" style="display: none">
	                                                <input name="NewNickname" type="text" value="<?php echo $member['name'];?>" id="NewNickname">
	                                                <input type="submit" name="btn_info" value="确认修改" onclick="return checkNickname();" id="btn_info">
	                                            </span>
	                                            <span id="success" class="tip_layer tl_right br6" style="display: none">修改成功！</span> 
	                                            <span class="tip_layer tl_error br6" style="display: none" id="nicknameNotEmpty">昵称不能为空！</span>
	                                        </p>
	                                    </td>
	                                </tr>
	                                <tr>
	                                    <td>
	                                        <b>账户余额：</b>
	                                    </td>
	                                    <td>
	                                        <em class="fl">￥<?php echo $member['balance'];?></em>
	                                    </td>
	                                    <td>
	                                        <b>绑定手机：</b>
	                                    </td>
	                                    <td>
	                                        <span id="bindPhone" class="fl">未绑定手机</span>
	                                        <!-- <a class="fr click_bind" href="javascript:;">去绑定</a> -->
	                                    </td>
	                                </tr>
                            </tbody>
                            </table>
                        </div>
                    </div>
                </div>
</div>
<!--end of pCenter -->