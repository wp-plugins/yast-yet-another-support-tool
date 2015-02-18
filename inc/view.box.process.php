<?php
wp_enqueue_script('user-suggest', admin_url('/js/user-suggest.min.js'), array('jquery'), false, true);
$conf = $this->options;

if (!$ticket || !isset($ticket->assignedto)) {
    $ticket = $this->get_ticket(isset($_GET['post']) && is_numeric($_GET['post']) ? $_GET['post'] : NULL);
}
?>
<div class="yast_ticket_navigator">
    <ul>

	<li>
	    <?php _e('Assigned to:', 'yast') ?>
	    <span id="yast_assigneto"><?php echo $this->display_user($ticket->assignedto) ?></span>
	    <?php
	    if ($conf['remote_use'] != 'client' &&
		    (!is_multisite() && user_can(get_current_user_id(), 'manage_options') ||
		    is_multisite() && user_can(get_current_user_id(), 'manage_network_options'))
	    ) {
		?>
    	    <input name="assigned" data-id="<?php echo $ticket->ID ?>" type="text" id="yast_assigned" class="wp-suggest-user" value="<?php echo $ticket->assigned->user_login ?>" />
    	    <a id="yast_reassigne" class="button"><?php _e('Assign', 'yast') ?></a>
    	    <a id="yast_assigne"><?php _e('Change', 'yast') ?></a>
	    <?php } ?>
	</li>

	<?php wp_nonce_field('ticket_merge', 'ticket_merge') ?>
	<li id="yast_merge" data-id="<?php echo $ticket->ID ?>">

	    <?php if (is_super_admin() && $ticket->post_parent == 0) { ?>
    	    <a id="yast_merge_button" class="button button-default btn btn-sm btn-default"><?php _e('Merge with another ticket', 'yast') ?></a>
		<?php
	    }
	    else {
		if ($ticket->post_parent > 0) {
		    $parent = $this->get_ticket($ticket->post_parent);
		    ?>
		    <a href="<?php echo $parent->lien ?>">
			<?php printf(__('Merged with ticket #%d : %s', 'yast'), $ticket->post_parent, $parent->post_title); ?>
		    </a>
		<?php }
	    } ?>
	</li>

    </ul>
</div>
<input id="comment_status" type="hidden" value="open" name="comment_status">
<input id="ping_status" type="hidden" value="open" name="ping_status">
