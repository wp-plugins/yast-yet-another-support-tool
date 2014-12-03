jQuery(document).ready(function(){
	jQuery('#yast_list .yast_formbutton input, .yast_icon').css('font','400 15px/1 dashicons;');
	jQuery('#yast_list .yast_icon-private').text('');
	jQuery('#yast_list form.yast_delete input[type=submit]').val('');
	jQuery('#yast_list form.yast_open input[type=submit]').val("").mouseover(function(){jQuery(this).val("");}).mouseout(function(){jQuery(this).val("");});
	jQuery('#yast_list form.yast_close input[type=submit]').val("").mouseover(function(){jQuery(this).val("");}).mouseout(function(){jQuery(this).val("");});
});

