<?php
if (isset($_GET['ticket'])) {
    $this->single();
    return;
}
if (is_multisite()) {
    switch_to_blog($this->options['support_site']);
}

$status = ( isset($_GET['post_status']) && in_array($_GET['post_status'], array('all', 'open', 'closed', 'trash')) ) ? $_GET['post_status'] : 'open';
$ticket_type = \filter_input(INPUT_GET, 'ticket_type');
$tickets = new WP_query($this->query());

if (is_multisite()) {
    restore_current_blog();
}

$levels = $this->levels();

$types = $this->default_types();
?>
<div class="wrap">
    <h2><?php _e('Support Tickets', 'yast'); ?></h2>

    <table class="widefat tickets tickets-filter">
	<tr>
	    <td>
		<form action="" method="get">
		    <input type="hidden" name="page" value="yast_list">
		    <select name="post_status">
			<option value="all" <?php selected('all', $status) ?>><?php _e('All', 'yast') ?></option>
			<option value="open" <?php selected('open', $status) ?>><?php _e('Open', 'yast') ?></option>
			<option value="closed" <?php selected('closed', $status) ?>><?php _e('Closed', 'yast') ?></option>
			<option value="trash" <?php selected('trash', $status) ?>><?php _e('Trash', 'yast') ?></option>
		    </select>
		    <select name="ticket_type">
			<option value=""><?php _e('All ticket types', 'yast') ?></option>
			<?php foreach ($types as $type): ?>
    			<option value="<?php echo $type->slug ?>" <?php selected($ticket_type, $type->slug) ?>><?php echo $type->name ?> (<?php echo $type->count ?>)</option>
			    <?php if (sizeof($type->children) > 0): ?>
				<?php foreach ($type->children as $child): ?>
	    			<option value="<?php echo $child->slug ?>" <?php selected($ticket_type, $child->slug) ?>>
					<?php echo (wp_is_mobile() ? '--' : $type->name . '/') ?><?php echo (wp_is_mobile() && strlen($child->name) > 33 ? substr($child->name, 0, 30) . '...' : $child->name) ?>
	    			    (<?php echo $child->count ?>)
	    			</option>
				<?php endforeach ?>
			    <?php else: ?>
			    <?php endif ?>
			<?php endforeach ?>
		    </select>
		    <button type="submit" class="button button-default">
			<span class="dashicons dashicons-yes"> </span>
			<span class="column-label"><?php _e('Filter', 'yast') ?></span>
			<span>(<?php echo $tickets->found_posts ?>)</span>
		    </button>
		</form>
	    </td>
	    <td class="column-ticket-info">
		<nav>
		    <?php $this->liste_paginate($tickets); ?>
		</nav>
	    </td>
	    <td align="right" class="column-ticket-info">
		<?php if (is_super_admin()) : ?>
    		<a href="#TB_inline?width=600&height=400&inlineId=report_ticketbox" class="thickbox button button-primary">
    		    <span class="dashicons dashicons-plus"> </span>
    		    <span class="column-label"><?php _e('New ticket', 'yast') ?></span>
    		</a>
		<?php endif; ?>
	    </td>
	</tr>
    </table>
    <table id="yast_list" class="widefat tickets fixed">

	<thead>
	    <tr>
		<th scope="col" class="column-title">
		    <span class="dashicons dashicons-sos"></span>
		    <span class="column-label"><?php _e('Title', 'yast') ?></span>
		</th>
		<th scope="col" class="column-ticket-info">
		    <span class="dashicons dashicons-tag"></span>
		    <span class="column-label"><?php _e('Type', 'yast') ?></span>
		</th>
		<th scope="col" class="column-ticket-info">
		    <span class="dashicons dashicons-admin-links"></span>
		    <span class="column-label"><?php _e('Page', 'yast') ?></span>
		</th>
		<th scope="col" class="column-priority">
		    <span class="dashicons dashicons-info"></span>
		    <span class="column-label"><?php _e('Priority', 'yast') ?></span>
		</th>
		<th scope="col" class="column-ticket-info">
		    <span class="dashicons dashicons-admin-users"></span>
		    <span class="column-label"><?php _e('Assigned', 'yast') ?></span>
		</th>
		<th scope="col" class="column-ticket-info">
		    <span class="dashicons dashicons-admin-users"></span>
		    <span class="column-label"><?php _e('User', 'yast') ?></span>
		</th>
		<th scope="col" class="column-date">
		    <span class="dashicons dashicons-calendar"></span>
		    <span class="column-label"><?php _e('Date', 'yast') ?></span>
		</th>
		<th scope="col" class="column-comments">
		    <span class="dashicons dashicons-format-chat" title="<?php _e('Answers', 'yast') ?>"></span>
		</th>
		<th scope="col" class="column-time">
		    <?php echo (is_super_admin() ? '<span class="dashicons dashicons-backup" title="' . __('Spent time', 'yast') . '"></span>' : '') ?>
		</th>
		<th scope="col" class="column-status">
		    <span class="dashicons dashicons-admin-generic"></span>
		    <span class="column-label"><?php _e('Actions', 'yast') ?></span>
		</th>
	    </tr>
	</thead>
	<tbody>
	    <?php
	    if (is_multisite()) {
		switch_to_blog($this->options['support_site']);
	    }
	    while ($tickets->have_posts()):
		$tickets->the_post();
		$ticket = $this->get_ticket(get_the_ID());
		$paged = (false !== $paged = \filter_input(INPUT_GET, 'paged')) ? $paged : 1;
		$link = 'admin.php?page=yast_list&ticket=' . $ticket->ID . '&_wp_http_referer=' . urlencode(wp_unslash($_SERVER['REQUEST_URI'])); //urlencode('admin.php?page=yast_list&post_status=' . $status . '&paged=' . $paged);
		$referer = ('admin.php?page=yast_list&post_status=' . $status . '&ticket_type=' . $ticket_type . '&paged=' . $paged);
		$count = count(get_comments(array('post_id' => $ticket->ID)));
		?>
    	    <tr class="yast_<?php echo $ticket->post_status; ?> yast_<?php echo $ticket->visibility ?> yast_<?php echo ($count > 0 ? 'active' : 'waiting') ?>">
    		<td class="column-title">
    		    <a href="<?php echo $link ?>"><span class="dashicons dashicons-edit"></span>
			    <?php
			    if ('' === get_the_title()) {
				echo strip_tags(get_the_excerpt());
			    }
			    else {
				the_title();
			    }
			    ?>
    		    </a>
			<?php if ($ticket->visibility == 'private'): ?>
			    <span class="yast_icon yast_icon-private" title="<?php _e('Private', 'yast'); ?>">P</span>
			<?php endif; ?>
    		</td>
    		<td class="column-ticket-info"><?php echo $ticket->type_display ?></td>
    		<td class="column-ticket-info"><a href="<?php echo $ticket->page['url'] ?>" target="_blank"><?php echo substr($ticket->page['url'], 0, 20) . '...' ?></a></td>
    		<td class="column-priority"><span style="color:<?php echo $levels[$ticket->priority]['color'] ?>;"><?php echo $levels[$ticket->priority]['name'] ?></span></td>
    		<td class="column-ticket-info"><?php echo $this->display_user($ticket->assignedto) ?></td>
    		<td class="column-ticket-info"><?php echo $this->display_user($ticket->reporter) ?></td>
    		<td class="column-date"><?php the_date(); ?></td>
    		<td  class="column-comments"><?php echo $count ?></td>
    		<td class="column-time"><?php echo str_replace(' ', '&nbsp;', $this->human_spent_time($this->total_spent_time($ticket->ID), 'small')) ?></td>
    		<td class="column-status">
    		    <div class="yast_actions">
			    <?php if ($ticket->post_status != 'closed') : ?>
				<form action="admin-post.php" method="post" class="yast_formbutton yast_close">
				    <?php wp_nonce_field('ticket_status', 'ticket_status'); ?>
				    <input type="hidden" name="action" value="yastupdate">
				    <input type="hidden" name="_wp_http_referer" value="<?php echo $referer ?>">
				    <input type="hidden" name="ticket" value="<?php echo $ticket->ID ?>">
				    <input type="hidden" name="post_status" value="closed">
				    <input type="submit" value="C" title="<?php _e('Close', 'yast'); ?>">
				</form>
			    <?php else: ?>
				<form action="admin-post.php" method="post" class="yast_formbutton yast_open">
				    <?php wp_nonce_field('ticket_status', 'ticket_status'); ?>
				    <input type="hidden" name="action" value="yastupdate">
				    <input type="hidden" name="_wp_http_referer" value="<?php echo $referer ?>">
				    <input type="hidden" name="ticket" value="<?php echo $ticket->ID ?>">
				    <input type="hidden" name="post_status" value="open">
				    <input type="submit" value="O" title="<?php _e('Re-Open', 'yast'); ?>">
				</form>
			    <?php endif; ?>
    			<form action="admin-post.php" method="post" class="yast_formbutton yast_delete">
				<?php wp_nonce_field('ticket_status', 'ticket_status'); ?>
    			    <input type="hidden" name="action" value="yastupdate">
    			    <input type="hidden" name="_wp_http_referer" value="<?php echo $referer ?>">
    			    <input type="hidden" name="ticket" value="<?php echo $ticket->ID ?>">
    			    <input type="hidden" name="post_status" value="trash">
    			    <input type="submit" value="X" title="<?php _e('Delete', 'yast'); ?>">
    			</form>
    		    </div>
    		</td>

    	    </tr>
	    <?php endwhile; ?>
	</tbody>
    </table>
    <table class="widefat tickets">
	<tfoot>
	    <tr>
		<td>
		    <nav>
			<?php $this->liste_paginate($tickets); ?>
		    </nav>
		</td>
	    </tr>
	</tfoot>
    </table>
</div>
<a href="#TB_inline?width=600&height=400&inlineId=report_ticketbox" class="thickbox button button-primary" id="yast-add-mobile">
    <span class="dashicons dashicons-plus"> </span>
</a>
<?php
if (is_multisite()) {
    restore_current_blog();
}