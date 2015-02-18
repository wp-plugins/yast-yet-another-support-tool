<?php
if (is_multisite()) {
    switch_to_blog($this->options['support_site']);
}
$ticket = $this->get_ticket(\filter_input(INPUT_GET, 'ticket', FILTER_VALIDATE_INT)? : NULL);
if (!$this->can_view($ticket)) {
    _e('Sorry, this ticket is untraceable...', 'yast');
    return;
}
$link = 'admin.php?page=yast_list&ticket=' . $ticket->ID . '&referer=' . esc_url(urlencode($_GET['referer']));
$return_link = false;
if (isset($_GET['_wp_http_referer'])) {
    $return_link = urldecode(wp_get_referer());
    $p_return_link = parse_url($return_link);
    $pos = strpos($p_return_link['query'], '_wp_http_referer');
    if (substr($p_return_link['query'], 0, 22) == 'page=yast_list&ticket=' && (-1 < $pos)) {
	$return_link = substr($p_return_link['query'], $pos + 17);
    }
}
if (!is_admin()) {
    $this->scripts_admin();
}
?>
<div class="wrap">
    <div class="icon32" id="icon-yast"><br></div>
    <h2><?php _e('Support ticket', 'yast'); ?> <?php echo $ticket->ID ?>
	<?php if (is_super_admin()): ?>
    	<a href="<?php echo admin_url() ?>/post.php?post=<?php echo $ticket->ID ?>&action=edit&referer=" class="button btn btn-default"><?php _e('Edit ticket', 'yast'); ?></a>
	<?php endif; ?>
    </h2>
    <div  id="poststuff">
	<?php if ($return_link): ?>
    	<a href="<?php echo $return_link ?>">&laquo; <?php _e('Back to support tickets list', 'yast'); ?></a>
	<?php endif; ?>
	<h1><?php echo $ticket->post_title ?></h1>
	<div class="postbox" id="ticket_content">
	    <h3><?php _e('Informations', 'yast'); ?></h3>
	    <div class="inside">
		<time>
		    <span><?php echo date_i18n(get_option('date_format') . ', ' . get_option('time_format'), strtotime($ticket->post_date)); ?></span>
		</time>
		<form action="#" method="post">
		    <?php $this->custom_boxinfo($ticket) ?>
		</form>
	    </div>
	</div>


	<div class="postbox" id="ticket_comments">
	    <h3><?php _e('Process', 'yast'); ?></h3>
	    <div class="inside">
		<?php if (is_super_admin()): ?>
    		<p>
    		    <span class="dashicons dashicons-backup"></span>
			<?php _e('Spent time', 'yast') ?>
    		    <strong><?php echo $this->human_spent_time($this->total_spent_time($ticket->ID)) ?></strong>
    		</p>
		<?php endif; ?>
		<form action="#" method="post">
		    <?php $this->custom_boxprocess($ticket) ?>
		</form>
		<ul>
		    <?php
		    $comments = get_comments(array(
			'post_id' => $ticket->ID,
			'status' => 'approve' //Change this to the type of comments to be displayed
		    ));

		    //Display the list of comments
		    wp_list_comments(array(
			'reverse_top_level' => true, //Show the latest comments at the top of the list
			//'callback'=>array($this,'comment_callback'),
			'end-callback' => array($this, 'comment_endcallback')
			    ), $comments);
		    ?>
		</ul>
		<?php
		if ($this->can_edit($ticket)):
		    if ($ticket->post_status == 'closed'):
			$this->single_action_button($ticket->ID, 'open', __('Re-open this ticket ?', 'yast'));
		    else:
			if (isset($_GET['confirm_reply'])) {
			    $this->single_action_button($ticket->ID, 'closed', __('Close this ticket ?', 'yast'));
			}
			?>
			<a id="yast_comment_link" class="button button-primary btn btn-sm btn-primary"><?php _e('Reply to this ticket', 'yast') ?></a>
			<form action="<?php echo admin_url() ?>admin-post.php?token=<?php echo $ticket->token ?>" method="post" id="yast_comment">
	<?php wp_nonce_field('ticket_comment', 'ticket_comment'); ?>
			    <?php wp_nonce_field('ticket_status', 'ticket_comment'); ?>
			    <input type="hidden" name="action" value="yastupdate">
			    <input type="hidden" name="ticket" value="<?php echo $ticket->ID ?>">
				    <?php if (is_super_admin()): ?>
	    		    <div>
	    			<label for="spent_time_text">
	    <?php _e('Spent time', 'yast') ?></label>
	    			<div class="input-group">
	    			    <input type="number" step="1" min="0" name="spent_time" id="spent_time_text" class="form-control">
	    			    <span class="input-group-addon"><?php _e('minutes', 'yast') ?></span>
	    <?php printf(__("For information, you've opened this page %s ago.", 'yast'), '<a id="yast_realtime"></a>') ?>
	    			    <p><label><input type="checkbox" id="autospent" checked> <?php _e('Automaticaly add spent time before saving', 'yast') ?></label></p>
	    			</div>

	    		    </div>
	<?php endif; ?>
	<?php wp_editor("", 'message', array('teeny' => true)); ?>
			    <p><label><input type="checkbox" name="post_status" value="closed" checked> <?php _e('comment and close', 'yast') ?></label></p>
			    <a id="yast_comment_link_off" class="button button-default btn btn-default"><?php _e('Cancel', 'yast') ?></a>
			    <input type="submit" class="button button-primary btn btn-primary" value="<?php _e('Send', 'yast') ?>">
			</form>
    <?php endif;
endif; ?>
	    </div>
	</div>

		<?php if ($this->can_edit($ticket)): ?>
    	<div class="postbox" id="ticket_tech">
    	    <h3><?php _e('Technical details', 'yast'); ?></h3>
    	    <div class="inside">
    <?php $this->custom_boxtech($ticket) ?>
    	    </div>
    	</div>

    	<div class="yast_actions">
		<?php if ($return_link): ?>
		    <a href="<?php echo $return_link ?>" class="button btn btn-default"><?php _e('Cancel', 'yast'); ?></a>
		<?php endif; ?>
		<?php $this->single_action_button($ticket->ID, 'trash', __('Delete this ticket ?', 'yast')) ?>
	    <?php
	    if ($ticket->post_status != 'closed') {
		$this->single_action_button($ticket->ID, 'closed', __('Close this ticket ?', 'yast'));
	    }
	    ?>
    	</div>
<?php endif; ?>
    </div>
</div>
<?php
if (is_multisite()) {
    restore_current_blog();
}