var yast_search_table = '<table class="widefat">'
        +'<thead><tr>'
        +'<td colspan="4">'+yast.lng_search_results_title+'</td>'
        +'<tr></thead>'
        +'<tbody></tbody></table>';

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

        jQuery('#ticketrep_title').keypress(function(){
            if(jQuery('#ticketrep_search_results').length===0){
                jQuery('#ticketrep_title').after('<div id="ticketrep_search_results"></div>');
            }
            jQuery('#ticketrep_search_results').html('');
            var datas={
                action:'yastsearch',
                q:jQuery('#ticketrep_title').val(),
                type:jQuery('#ticketrep_type').val()
            };
            jQuery.post(yast.ajaxurl, datas, function(results) {
                if(results.length){
                    jQuery('#ticketrep_search_results').append(yast_search_table);
                    for(r in results){
                        ticket = results[r];
                        line = '<tr class="status-'+(ticket.post_status)+'"><td>'
                                +'<span class="dashicons dashicons-sos"></span> <a href="'+ticket.front_link+'">'+ticket.post_title+'</a></td>'
                                +'<td>'+yast.lng_statuses[ticket.post_status]+'</td>'
                                +'<td><span class="dashicons dashicons-tag"></span> '+ticket.type_display+'</td>'
                                +'<td></span> <span class="post-com-count"><span>'+ticket.comment_count+'</span></span></td>'
                                +'</tr>';
                        jQuery('#ticketrep_search_results tbody').append(line);
                    }
                }
            },'json');
        });
});
