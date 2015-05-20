<?php

global $YAST_tools;
if (
// Default form, from admin bar, for logged in users
	(is_user_logged_in() && !\filter_input(INPUT_POST, 'from')) ||
	// Non logged in users
	(!is_user_logged_in() &&
	(
	// Custom form from shortcode
	\filter_input(INPUT_POST, 'from') != 'shortcode' &&
	// Trusted external sites
	!in_array($YAST_tools->host(\filter_input(INPUT_SERVER, 'HTTP_REFERER')), $this->options['trusted_hosts'])
	)
	)
) {
    $this->bad_redirect();
}
if (is_multisite()) {
    switch_to_blog($this->options['support_site']);
}
$datas = array();

$description = '';
if (is_array($_REQUEST['description'])) {
    foreach ($_REQUEST['description'] as $key => $value) {
	$description.=esc_attr($key) . ' : ' . "\n" . esc_attr($value) . "\n\n";
    }
}
else {
    $description = wp_kses_post($_REQUEST['description']);
}

$datas['post_title'] = sanitize_text_field($_REQUEST['title']);
$datas['post_content'] = $description;
$datas['post_type'] = 'yast';
$datas['post_status'] = 'open';
$datas['comment_status'] = 'open';
$datas['ping_status'] = 'open';

if (0 === $ticket_id = wp_insert_post($datas)) {
    if ($_POST['from'] == 'ajax') {
	echo json_encode(array(
	    'success' => false,
	    'message' => __("Could'nt create ticket !", 'yast')
	));
    }
    else {
	wp_redirect(site_url() . '?message=in');
	exit;
	wp_redirect(esc_url($_POST['page_url']) . (strstr($_POST['page_url'], '?') ? '&' : '?') . 'message=ko');
    }
    exit;
}
else {
    global $current_user;
    get_currentuserinfo();

    $reporter = esc_attr($_REQUEST['reporter']);
    if (false !== $author = \filter_input(INPUT_POST, 'user')) {
	if(is_super_admin()){
	    $reporter = $author;
	}
	elseif(is_user_logged_in()){
	    $reporter = $current_user->user_email;
	}
	else{
	    $reporter = $this->decrypt($author);
	    $current_user->user_email = $reporter;
	}
    }
    $meta_page = array(
	'url' => esc_url($_REQUEST['page_url']),
	'post' => $this->recursive_sanitize(unserialize($_REQUEST['page_post']))
    );
    $meta_navigator = array(
	'appName' => isset($_REQUEST['navigator_appName']) ? esc_attr($_REQUEST['navigator_appName']) : '',
	'userAgent' => isset($_REQUEST['navigator_userAgent']) ? esc_attr($_REQUEST['navigator_userAgent']) : '',
	'platform' => isset($_REQUEST['navigator_platform']) ? esc_attr($_REQUEST['navigator_platform']) : '',
	'language' => isset($_REQUEST['navigator_language']) ? esc_attr($_REQUEST['navigator_language']) : '',
	'product' => isset($_REQUEST['navigator_product']) ? esc_attr($_REQUEST['navigator_product']) : '',
    );
    add_post_meta($ticket_id, 'priority', esc_attr($_REQUEST['priority']));
    add_post_meta($ticket_id, 'page', $meta_page);
    add_post_meta($ticket_id, 'navigator', $meta_navigator);
    add_post_meta($ticket_id, 'reporter', $reporter);
    add_post_meta($ticket_id, 'visibility', esc_attr($_REQUEST['visibility']));

    wp_set_object_terms($ticket_id, array(esc_attr($_REQUEST['type'])), 'ticket_type');

    $level = $this->levels($_REQUEST['priority']);

    $ticket = $this->get_ticket($ticket_id);

    $conf = $this->options;

    $dests = $this->comment_dests($ticket);

    $msg_mail = sprintf(__('[Support Ticket][%4$s] #%13$d %3$s

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

Issue link : %12$s', 'yast'), $reporter, $current_user->user_email, $ticket->post_title, $ticket->type_display, $level['name'], $datas['post_content'], date_i18n(get_option('date_format') . ', ' . get_option('time_format')), esc_url($_REQUEST['page_url']), wp_kses_post($_REQUEST['page_post']), $this->human_nav_info($_REQUEST['navigator_userAgent']), get_bloginfo('name'), $ticket->front_link, $ticket->ID
    );

    $confirm_message = __("Report succesfully registered !", 'yast');

    $siteurl = basename(get_site_option('siteurl'));
    $this->mailid = ($ticket->ID);
    $this->mailpriority = (4 - $ticket->priority);
    $this->mailfrom = 'noreply-yast@' . $siteurl;
    $this->mailfromName = 'Ticket ' . $siteurl;
    $sent = false;
    foreach ($dests as $dest) {
	if (wp_mail(
			$dest, sprintf(__('[Support Ticket][%s] #%d %s', 'yast'), $ticket->type_display, $ticket_id, $ticket->post_title), $msg_mail
		)) {
	    $sent = true;
	}
    }
    if ($sent) {
	$confirm_message.="\n" . __("An e-mail has been sent", 'yast');
    }

    // We are on a Ticket server
    if ($this->is_server_request()) {
	add_post_meta($ticket_id, 'remote', array(
	    'ticket_id' => sanitize_text_field($_REQUEST['remote_ticket']),
	    'client' => sanitize_text_field($_REQUEST['remote_client']),
	    'token' => sanitize_text_field($_REQUEST['client_token']),
	));
	echo json_encode(array(
	    'success' => true,
	    'ticket_id' => $ticket_id
	));
    }
    // We are on a ticket client
    else {
	if (!empty($conf['remote_server']) && !empty($conf['remote_token'])) {
	    // Envoi au serveur de tickets
	    $result = $this->send_to_server('yast', $ticket_id, $conf['remote_server']);


	    if (is_object($result) && isset($result->success) && $result->success == true && isset($result->ticket_id)) {
		add_post_meta($ticket_id, 'server_ticket', $result->ticket_id);
		$confirm_message.="\n" . __("Report is synchronised with the technical server.", 'yast');
	    }
	    else {
		$confirm_message.="\n" . __("Report could'nt be synchronised with the technical server.", 'yast');
	    }
	}
	if ($_POST['from'] == 'ajax') {
	    echo json_encode(array(
		'success' => true,
		'message' => $confirm_message
	    ));
	}
	else {
	    wp_redirect(esc_url($_POST['page_url']) . (strstr($_POST['page_url'], '?') ? '&' : '?') . 'form_message=ok');
	}
    }

    exit;
}
if (is_multisite()) {
    restore_current_blog();
}
return 'Bad parameters';