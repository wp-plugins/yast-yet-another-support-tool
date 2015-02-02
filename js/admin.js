var time=new Date();
var init_time=time.getTime();
function yast_realtime(){
    var realtime='';
    var now=new Date();
    var dif=Math.round((now.getTime()-init_time)/1000);

    if(dif>60*60) realtime= Math.floor(dif/(60*60))+' '+yast.locale.hours+' '+Math.round(((dif/3600)-Math.floor(dif/3600))*60)+' '+yast.locale.minutes;
    else if(dif>60) realtime= Math.floor(dif/60)+' '+yast.locale.minutes+' '+Math.round(((dif/60)-Math.floor(dif/60))*60)+' '+yast.locale.seconds;
    else realtime= dif+' '+yast.locale.seconds;
    //realtime+=' ('++' '+yast.locale.minutes+')';
    jQuery('#yast_realtime').html(realtime).click(function(){
        jQuery('#spent_time_text').val(Math.ceil(dif/60));
    });
    setTimeout('yast_realtime()',1000);
}

jQuery(document).ready(function(){
	if(jQuery('#bug-status').length>0){
		jQuery('#minor-publishing-actions').remove();
		jQuery('#post-status-display').parent().remove();
		jQuery('#visibility').remove();
		jQuery('#commentstatusdiv').remove();
		var color=jQuery('#bug_priority').css('color');
		jQuery('#bug_content .inside').css({'border-left':color+' 10px solid'});
	}

	function yast_chooserole(){
		if(jQuery('#yasts_options_remote_use').val()=='server'){
			jQuery('#remote_use_client').hide();
			jQuery('#remote_use_server').show();
		}
		else if(jQuery('#yasts_options_remote_use').val()=='client'){
			jQuery('#remote_use_client').show();
			jQuery('#remote_use_server').hide();
		}
		else{
			jQuery('#remote_use_client').hide();
			jQuery('#remote_use_server').hide();
		}
	}
	jQuery('#yasts_options_remote_use').change(yast_chooserole);
	yast_chooserole();


	jQuery('#yast_comment_link').click(function(){
		jQuery('#yast_comment').show(500);
	});
        jQuery('#yast_comment_link_off').click(function(){
		jQuery('#yast_comment').hide(500);
	});

	jQuery('#bug_preview_link').click(function(){
		jQuery('#bug_preview_iframe').animate({height:'500px'});
	});

	jQuery('#manage_bugtypes input[name=taxonomy]').val('ticket_type');
	jQuery('#manage_bugtypes input[name=post_type]').val('yast');
	jQuery('#toplevel_page_yasts_list a').slice(2,3).attr('href','edit-tags.php?taxonomy=ticket_type');

	jQuery('body.taxonomy-ticket_type #menu-posts').removeClass('wp-has-current-submenu wp-menu-open open-if-no-js').addClass('wp-not-current-submenu');
	jQuery('body.taxonomy-ticket_type #toplevel_page_yasts_list').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu wp-menu-open open-if-no-js');
	jQuery('body.taxonomy-ticket_type #toplevel_page_yasts_list a.menu-top-first').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu');
	jQuery('body.taxonomy-ticket_type #toplevel_page_yasts_list a').slice(2,3).addClass('current').parent().addClass('current');



	jQuery('#yast_assigned, #yast_reassigne').hide();

        jQuery('#ticketrep_reporter').focus(function(){
            jQuery('.ui-autocomplete.ui-front.ui-menu.ui-widget.ui-widget-content.ui-corner-all').css({'z-index':'100000000'});
        });

	jQuery('#yast_assigne').click(function(){
		jQuery(this).hide();
		jQuery('#yast_assigned, #yast_reassigne').show();
	});
	jQuery('#yast_reassigne').click(function(){
		var datas={
			action:'yastassign',
			ticket_id:jQuery('#yast_assigned').data('id'),
			assign:jQuery('#yast_assigned').val()
		};
		jQuery.get(ajaxurl, datas, function(retour) {
			jQuery('#yast_assigned, #yast_reassigne').hide();
			jQuery('#yast_assigne').show();
			jQuery('#yast_assigneto').html(retour);

		},'text');
	});
	jQuery('#yast_merge_button').click(function(){
		jQuery(this).hide(500);
		var datas={
			action:'yastselect',
			ticket_id:jQuery('#yast_merge').data('id')
		};
		jQuery.get(ajaxurl, datas, function(retour) {
			jQuery('#yast_merge').html(retour);
			jQuery('#yast_merge select').change(function(){
				jQuery(this).hide(500);
				var datas={
					action:'yastmerge',
                                        ticket_merge: jQuery('#ticket_merge').val(),
					ticket_id:jQuery('#yast_merge').data('id'),
					post_parent:jQuery(this).val()
				};
				console.log(datas);
				jQuery.post(ajaxurl, datas, function(retour) {
					jQuery('#yast_merge').html(retour);
				},'html');
			});
		},'html');
		return false;
	});



	jQuery('#yasts_options_form input,#yasts_options_form select').change(function(){
		jQuery('#yasts_options_confirm').hide(500);
	});

        if(jQuery('#yast_realtime').length>0){
            yast_realtime();
        };
});
