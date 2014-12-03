
jQuery(document).ready(function(){
	jQuery('#wp-admin-bar-ticket_report a.thickbox').prev().remove();
	jQuery('#yast_techsubinfos').animate({height:'toggle'},1);
	jQuery('#yast_techsub').click(function(){
		jQuery('#yast_techsubinfos').animate({height:'toggle'});
	});
	
	jQuery('#report_ticketbox form').submit(function(){
		jQuery(this).find('input').attr("disabled", "disabled");
		var datas={
			action:'yastpost',
			from:'ajax',
			title:jQuery('#ticketrep_title').val(),
			description:jQuery('#ticketrep_description').val(),
			priority:jQuery('#ticketrep_priority').val(),
			type:jQuery('#ticketrep_type').val(),
			visibility:jQuery('#ticketrep_visibility').val(),
			reporter:jQuery('#ticketrep_reporter').val(),
			page_url:jQuery('#ticketrep_page_url').val(),
			page_post:jQuery('#ticketrep_page_post').val(),
			navigator_appName:navigator.appName,
			navigator_userAgent:navigator.userAgent,
			navigator_platform:navigator.platform,
			navigator_language:navigator.language,
			navigator_product:navigator.product
		};
		jQuery('#report_ticketbox form .button-primary').hide();
		jQuery.post(yast.ajaxurl, datas, function(retour) {			
			if(retour.success==true){
				alert(retour.message);
				tb_remove();
			}
			else{
				alert(retour.message);
			}
			jQuery('#report_ticketbox form .button-primary').show();
		},'json');
		return false;
	});
	
});
