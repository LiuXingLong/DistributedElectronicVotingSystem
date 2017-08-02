$(function() {
    $('.voting').on('click',
    function(e) {
        itid = $(this).attr("data-itid");
        $.ajax({
            type: 'POST',
            url: vote_url,
            data: {hxrid: itid},
            success: function(data) {
            	if (data == 1) {
            		$('#voted').show();
            	} else if(data == 0) {
                    $('#verify').show();
            	} else {
            		$('#voting_title').html('投票失败');
                    $('#voting_content').html(data);
                    $('#voting').show();
                    setTimeout("$('#voting').hide()",1200);
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