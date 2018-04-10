<div class="shadow clearfix" id="pCenter">
    <?php echo $pMenu;?>
				<div id="pContent">
                    <div id="sysNotice">
                        <h1>
                            系统公告</h1>
                        <div class="sys_con">
                            	<?php if(!$announce):?>
                            	<p class="not_title">
                                <span>暂时还没有系统公告...</span>
                                </p>
                                <?php else:?>
                                <?php foreach ($announce AS $k => $v):?>
                                <p class="not_title">
                                <span><?php echo $v['content'];?>-----------<?php echo $v['create_time'];?></span>
                                </p>
                                <?php endforeach;?>
                                <?php endif;?>
                        </div>
                    </div>
                </div>
</div>
<!--end of pCenter -->