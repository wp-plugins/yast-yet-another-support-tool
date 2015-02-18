<?php

if (isset($_REQUEST['ticket_id']) && isset($_REQUEST['assign']) && is_numeric($_REQUEST['ticket_id'])) {
    if (is_multisite()) {
	switch_to_blog($this->options['support_site']);
    }
    if ((false !== $ticket = $this->get_ticket($_REQUEST['ticket_id'])) && (false !== $assign = get_user_by('login', esc_attr($_REQUEST['assign'])))) {

	global $current_user;
	get_currentuserinfo();

	$level = $this->levels($ticket->priority);
	$server_name = basename(get_site_url());
	if (empty($server_name)) {
	    $server_name = basename(get_site_url());
	}
	$assigned = esc_attr($_REQUEST['assign']);

	$msg_mail = sprintf(__('Congratulations !
You\'ve been assigned to the Support Ticket #%1$s  by %2$s

Subject: %3$s
Type : %4$s
Priority : %5$s

Message:
%6$s

---------------------------
Technical informations :
* Time: %7$s
* URL: %8$s
* Variables: %9$s
* Navigator: %10$s

Regards,
The website %11$s

Issue link : %12$s', 'yast'), $ticket->ID, $current_user->user_nicename, $ticket->post_title, $ticket->type, $level['name'], $ticket->post_content, date_i18n(get_option('date_format') . ', ' . get_option('time_format'), strtotime($ticket->post_date)), $ticket->page['url'], $ticket->page['post'], $this->human_nav_info($ticket->navigator['userAgent']), get_bloginfo('name'), $ticket->lien
	);
	$siteurl = basename(get_site_option('siteurl'));
	$headers = array("In-Reply-To: <BT" . $ticket->ID . "@" . $siteurl . ">");
	$this->mailpriority = (4 - $ticket->priority);
	$this->mailfrom = 'noreply-yast@' . $siteurl;
	$this->mailfromName = 'Ticket ' . $siteurl;
	wp_mail($assign->user_email, sprintf(__('[Support Ticket] #%d', 'yast'), $ticket->ID), $msg_mail, $headers
	);
	update_post_meta($ticket->ID, 'assigned', $assigned);
	echo $this->display_user($assigned);
    }
    else {
	_e('Error while assigning', 'yast');
    }
}
else {
    _e('Missing parameters', 'yast');
}
if (is_multisite()) {
    restore_current_blog();
}
exit;
