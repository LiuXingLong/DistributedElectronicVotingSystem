function getScrollTop()
{
    var scrollTop=0;
    if(document.documentElement&&document.documentElement.scrollTop)
    {
        scrollTop=document.documentElement.scrollTop;
    }
    else if(document.body)
    {
        scrollTop=document.body.scrollTop;
    }
    return scrollTop;
}
function getClientHeight()
{
    var clientHeight=0;
    if(document.body.clientHeight&&document.documentElement.clientHeight)
    {
        var clientHeight = (document.body.clientHeight<document.documentElement.clientHeight)?document.body.clientHeight:document.documentElement.clientHeight;        
    }
    else
    {
        var clientHeight = (document.body.clientHeight>document.documentElement.clientHeight)?document.body.clientHeight:document.documentElement.clientHeight;    
    }
    return clientHeight;
}
function getScrollHeight()
{
    return Math.max(document.body.scrollHeight,document.documentElement.scrollHeight);
}

function my_getWxOptionBox(_option)
{
	 _div ='<div class="wxop">\
			    <div class="wxopimg">\
			        <a href=' + "userInfo/huid/" + _option.huid + ".html" + '>\
			            <img src="' + _option.perpic + '" alt="' + _option.lables + '" >\
			        </a>\
			    </div>\
			    <div class="wxoptxt">\
			        <a href=' + "userInfo/huid/" + _option.huid + ".html" + '>' + _option.huid+ '.' + '【' + _option.personame + '】 ' + _option.lables + '</a></div>\
			    <div class="wxopvotediv">\
			        <div class="wxvbtn">\
			            <button class="btn btn-info my_wxvotebutton" data-itid="' + _option.huid + '">\
			                <i class="icon-thumbs-up"></i>投票\
			            </button>\
			        </div>\
			        <div class="wxvinfo">\
			            <span>' + _option.daynums + '</span>票\
			        </div>\
			    </div>\
			</div>';
	return _div;
}

function my_showWxOptionListMoreOnerow(limit)
{
	if(loadpagedone) return false;
	wxpage += 1;
	$(".loadingpagealert").show();
	$("#loadmorebtn").hide();
	$.post('index', {page:wxpage,limit:limit},function(data){
		$(".loadingpagealert").hide();
   		for(i in data.data)
    	{
    		_opt = data.data[i];
    		optdiv = my_getWxOptionBox(_opt);
    		$(".leftoptions").append(optdiv);
    	}
    	if(data.num < optionpagenum)
    	{
    		loadpagedone = true;
    		$(".loadingpagealert").html("没有更多了");
    		$(".loadingpagealert").show();
    	}
    	else
    	{
    		$("#loadmorebtn").show();
    	}
	},"json");
}

$(function() {
	$('body').on('click','.my_wxvotebutton',
    function(e) {
        itid = $(this).attr("data-itid");
        $.ajax({
            type: 'POST',
            url: vote_url,
            data: {hxrid: itid},
            success: function(data) {
            	if (data == 1) {
            		$('.msg_title').html('投票成功！');
            		$('.wapalert').show();
            		location.reload();
            	} else if(data == 0) {
                    $('.dofollowalert').show();
            	} else {
            		$('.msg_title').html('投票失败');
                    $('.msg_content').html(data);
                    $('.wapalert').show();
                    setTimeout("$('.wapalert').hide()",1200);
            	}
            }
        });
        return false;
    });
	
    $('#verify_button').on('click',
    function(e) {
    	personame = $('#personame').val().trim();
    	joinflag = $('#joinflag').val().trim();
    	if (personame == '') {
    		alert('姓名不能为空！');
    		return false;
    	}
		if (joinflag == '') {
			alert('身份证后六位不能为空！'); 
    		return false;
    	}
        $.ajax({
            type: 'POST',
            url: verify_url,
            data: {personame:personame,joinflag:joinflag},
            success: function(data) {
            	if (data == 1) {
            		alert('身份验证成功！请选您需要投票的用户进行投票！');
            		$('.dofollowalert').hide();
            	} else {
            		alert(data);
            	}
            }
        });
        return false;
    });
    
    $("#showvotedescinfo").click(function(){
		if($("#votedescinfo").css("display") == "none")
		{
			$("#votedescinfo").show();
			$("#showvotedescinfo .arrow").removeClass("icon-double-angle-down");
			$("#showvotedescinfo .arrow").addClass("icon-double-angle-up");
		}
		else
		{
			$("#votedescinfo").hide();
			$("#showvotedescinfo .arrow").removeClass("icon-double-angle-up");
			$("#showvotedescinfo .arrow").addClass("icon-double-angle-down");
		}
	});
});