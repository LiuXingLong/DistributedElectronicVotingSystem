//倒计时
$(function () {
    if (isCountDownEndDatetime) {
        $('div#pnlEndDatetimeClock').countdown(endDatetimeString, function (event) {
            var $this = $(this);
            switch (event.type) {
                case "seconds":
                case "minutes":
                case "hours":
                case "days":
                case "weeks":
                case "daysLeft":
                    $this.find('span#' + event.type).html(event.value);
                    break;
                case "finished":
                    $this.empty();
                    $this.text("投票已截止！");
                    $("#pnlButtonWrapper").hide();
                    break;
            }
        });
    }
});

$(function() {
    $('.btn-block').on('click',
    function(e) {
        itid = $(this).attr("data-itid");
        $.ajax({
            type: 'POST',
            url: vote_url,
            data: {hxrid: itid},
            success: function(data) {
            	if (data == 1) {
            		$('#voted').show();
            		window.location.reload();
            	} else if(data == 0) {
                    $('#verify').show();
            	} else {
            		$('#msg_title').html('投票失败');
                    $('#msg_content').html(data);
                    $('#msg').show();
                    setTimeout("$('#msg').hide()",1500);  
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
            		$('#verify').hide();
            	} else {
            		alert(data);
            	}
            }
        });
        return false;
    });
});