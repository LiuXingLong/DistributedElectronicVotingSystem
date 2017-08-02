$(function(){
	var flag = 1;
    //菜单点击
    $(".J_menuItem").on('click',function() {
    	if(flag < 3){
    		if(flag == 2){
    			$(".J_menuItem.main").removeClass('main');
    		}
    		flag++;
    	}
        var url = $(this).attr('href');
        $("#J_iframe").attr('src',url);
        return false;
    });
    $(".J_menuItem.main").click();
});