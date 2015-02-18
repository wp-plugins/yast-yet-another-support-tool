<?php

extract(shortcode_atts(
		array(
    'ticket' => '',
    'user_name' => '',
    'user_email' => '',
    'user_id' => '',
    'message' => '',
    'confirm' => true,
    'spent_time' => 0,
		), $atts));

if (is_numeric($ticket)) {
    $ticket = $this->get_ticket($ticket);
}
if (!$this->can_edit($ticket)) {
    wp_die(__('What are you doing here ?', 'yast'));
}
$conf = $this->options;
if ($message != '') {
    $time = current_time('mysql');

    $data = array(
	'comment_post_ID' => $ticket->ID,
	'comment_author' => $user_name,
	'comment_author_email' => $user_email,
	'comment_content' => $message,
	'comment_type' => '',
	'comment_parent' => 0,
	'user_id' => $user_id,
	'comment_approved' => 1,
    );

    if (false !== $comment_id = wp_insert_comment($data)) {

	$dests = $this->comment_dests($ticket);

	if ($spent_time > 0) {
	    add_comment_meta($comment_id, 'spent_time', $spent_time, true);
	}

	$siteurl = basename(get_site_option('siteurl'));
	$headers = array("In-Reply-To: <BT" . $ticket->ID . "@" . $siteurl . ">");
	$this->mailpriority = (4 - $ticket->priority);
	$this->mailfrom = 'noreply-yast@' . $siteurl;
	$this->mailfromName = 'Ticket ' . $siteurl;
	foreach ($dests as $dest) {
	    wp_mail($dest, sprintf(__('[Support Ticket][%1$s] #%2$d %3$s', 'yast'), $ticket->type_display, $ticket->ID, $ticket->post_title), sprintf(__('A new comment has been posted by %4$s.
Subject: %1$s
Comment:
%2$s

--
Issue link : %3$s
', 'yast'), $ticket->post_title, html_entity_decode(strip_tags($message)), $ticket->front_link, $this->display_user($user_name, false)), $headers
	    );
	}

	if ($this->is_server_request()) {
	    echo json_encode(array(
		'success' => true,
		'comment_id' => $comment_id
	    ));
	}
	else {
	    if (!empty($conf['remote_server']) && !empty($conf['remote_token']) && $conf['remote_use'] == 'client') {


		$result = $this->send_to_server('comment', $ticket->server_ticket, $conf['remote_server'], array(
		    'user_name' => $user_name,
		    'user_email' => $user_email,
		    'user_id' => $user_id
		));


		if (is_object($result) && isset($result->success) && $result->success == true && isset($result->comment_id)) {
		    //add_post_meta($ticket_id, 'server_ticket', $result->ticket_id);
		    if ($confirm) {
			_e("Comment is synchronised with the technical server.", 'yast');
		    }
		}
		else {
		    if ($confirm) {
			_e("Comment could'nt be synchronised with the technical server.", 'yast');
		    }
		}
	    }
	    elseif ($conf['remote_use'] == 'server') {
		$result = $this->send_to_server('comment', $ticket->remote['ticket_id'], $ticket->remote['client'], array(
		    'user_name' => $user_name,
		    'user_email' => $user_email,
		    'user_id' => $user_id,
		    'remote_token' => $ticket->remote['token']
		));


		if (is_object($result) && isset($result->success) && $result->success == true && isset($result->comment_id)) {
		    //add_post_meta($ticket_id, 'server_ticket', $result->ticket_id);
		    if ($confirm) {
			_e("Comment is synchronised with the client.", 'yast');
		    }
		}
		else {
		    if ($confirm) {
			_e("Comment could'nt be synchronised with the client.", 'yast');
		    }
		}
	    }
	}
    }
}