<?php
/*
  Plugin Name: YAST : Yet Another Support Tool
  Plugin URI: http://ecolosites.eelv.fr/yast/
  Description: Support Tickets management, throw classic site, multisite plateform or external server
  Version: 1.0.3
  Author: bastho, n4thaniel, ecolosites
  Author URI: http://ecolosites.eelv.fr/
  License: GPLv2
  Text Domain: yast
  Domain Path: /languages/
  Tags: support, ticket, support tickets, helpdesk, multisite, help-desk
  Icons: from http://icomoon.io, under GPL/ CC BY 3.0 licences
 */

global $YAST_tools;
$_YAST = new YAST_class();

class YAST_class {

    public $types;
    public $mailid;
    public $mailpriority;
    public $mailfrom;
    public $mailfromName;
    public $options;

    function YAST_class() {
	load_plugin_textdomain('yast', false, 'yast-yet-another-support-tool/languages');
	$this->mailid = '';
	$this->mailpriority = '';
	$this->mailfrom = '';
	$this->mailfromName = '';
	$this->options = $this->get_conf();
	add_action('init', array(&$this, 'init'));

	add_action('admin_bar_menu', array(&$this, 'bar'), 100);
	add_action('admin_head', array(&$this, 'head'), 100);
	add_action('wp_footer', array(&$this, 'footer'));
	add_action('admin_footer', array(&$this, 'footer'));
	add_action('admin_menu', array(&$this, 'menu'));

	add_action('phpmailer_init', array(&$this, 'mailer'));

	//Shortcodes
	add_shortcode('BugTickets_form', array(&$this, 'form'));
	add_shortcode('yast_form', array(&$this, 'form'));

	// POST
	add_action('admin_post_yastsaveoptions', array(&$this, 'post_options'));
	add_action('save_post', array(&$this, 'savepost'));
	add_action('admin_post_yastupdate', array(&$this, 'update'));
	add_action('admin_post_yastform', array(&$this, 'create_post'));
	add_action('admin_post_nopriv_yastform', array(&$this, 'create_post'));

	//AJAX
	add_action('wp_ajax_yastselect', array(&$this, 'select'));
	add_action('wp_ajax_yastassign', array(&$this, 'assign'));
	add_action('wp_ajax_yastmerge', array(&$this, 'merge'));
	add_action('wp_ajax_yastpost', array(&$this, 'create'));
	add_action('wp_ajax_nopriv_yastpost', array(&$this, 'create'));

	add_action('add_meta_boxes', array(&$this, 'add_custom_box'));
	add_filter('manage_yast_posts_columns', array(&$this, ''));
	add_action('manage_yast_posts_custom_column', array(&$this, 'columns_content'), 10, 2);
	add_action('post_submitbox_misc_actions', array(&$this, 'submitbox'));

	// Scripts
	add_action('admin_enqueue_scripts', array(&$this, 'scripts_admin'));
	add_action('wp_enqueue_scripts', array(&$this, 'scripts_front'));
    }

    function init() {

	if (is_admin()) {
	    include_once (plugin_dir_path(__FILE__) . 'tools.php');
	}

	register_post_status('open', array(
	    'label' => __('Open', 'yast'),
	    'public' => false,
	    'exclude_from_search' => true,
	    'show_in_admin_all_list' => true,
	    'show_in_admin_status_list' => true,
	    'label_count' => _n_noop('Open <span class="count">(%s)</span>', 'Open <span class="count">(%s)</span>'),
	));
	register_post_status('closed', array(
	    'label' => __('Closed', 'yast'),
	    'public' => false,
	    'exclude_from_search' => true,
	    'show_in_admin_all_list' => true,
	    'show_in_admin_status_list' => true,
	    'label_count' => _n_noop('Closed <span class="count">(%s)</span>', 'Closed <span class="count">(%s)</span>'),
	));
	register_post_type('yast', array(
	    'label' => __('Ticket', 'yast'),
	    'description' => '',
	    'public' => false,
	    'show_ui' => true,
	    'show_in_menu' => false,
	    'capability_type' => 'post',
	    'hierarchical' => true,
	    'rewrite' => array('slug' => ''),
	    'query_var' => true,
	    'has_archive' => true,
	    'supports' => array('title', 'editor','comments','custom-fields'),
	    'show_in_menu' => false,
	    'labels' => array(
		'name' => __('tickets', 'yast'),
		'singular_name' => __('ticket', 'yast'),
		'menu_name' => __('tickets', 'yast'),
		'add_new_item' => __('Add', 'yast'),
		'edit' => __('Edit', 'yast'),
		'edit_item' => __('Edit ticket', 'yast'),
		'new_item' => __('New ticket', 'yast'),
		'view' => __('View', 'yast'),
		'view_item' => __('Preview ticket', 'yast'),
		'search_items' => __('Search a ticket', 'yast'),
		'not_found' => __('No entry has been made', 'yast'),
		'not_found_in_trash' => __('No ticket Found in Trash', 'yast'),
		'parent' => __('Parent ticket', 'yast'),
	    )
	));

	register_taxonomy(
	    'ticket_type', array('yast'), array(
	    'hierarchical' => true,
	    'show_ui' => true,
	    'query_var' => true,
	    'public' => false,
	    'show_in_nav_menus' => false,
	    'show_admin_column' => true,
	    'rewrite' => array('slug' => ''),
	    'labels' => array(
		'name' => __('Ticket types', 'yast'),
		'singular_name' => __('Ticket type', 'yast'),
		'search_items' => __('Search Ticket type', 'yast'),
		'all_items' => __('All Ticket types', 'yast'),
		'edit_item' => __('Edit Ticket type', 'yast'),
		'update_item' => __('Update Ticket type', 'yast'),
		'add_new_item' => __('Add new Ticket type', 'yast'),
		'new_item_name' => __('New Ticket type', 'yast'),
		'menu_name' => __('Ticket types', 'yast'),
	    )
		)
	);

	if (is_multisite()){
	    switch_to_blog($this->options['support_site']);
	}
	$this->types = get_terms('ticket_type', array('parent' => 0, 'hide_empty' => false));
	foreach ($this->types as $k => $type) {
	    $this->types[$k]->children = get_terms('ticket_type', array('child_of' => $type->term_id, 'hide_empty' => false));
	}
	if (is_multisite()){
	    restore_current_blog();
	}
    }

    function head() {
	if (isset($_GET['alert'])) {
	    if ($_GET['alert'] == 'open') {
		echo'<div class="updated">' . __('Ticket has been re-open', 'yast') . '</div>';
	    }
	    if ($_GET['alert'] == 'closed') {
		echo'<div class="updated">' . __('Ticket has been closed', 'yast') . '</div>';
	    }
	    if ($_GET['alert'] == 'trash') {
		echo'<div class="updated">' . __('Ticket has been moved to trash', 'yast') . '</div>';
	    }
	}
    }

    function menu() {
	add_menu_page(__('Support Tickets', 'yast'), __('Support Tickets', 'yast'), 'manage_options', 'yast_list', array(&$this, 'liste'));
	add_submenu_page('yast_list', __('Ticket types', 'yast'), __('Ticket types', 'yast'), 'manage_options', 'edit-tags.php?taxonomy=ticket_type');
	add_submenu_page('yast_list', __('Options', 'yast'), __('Options', 'yast'), 'manage_options', 'yast_options', array(&$this, 'options'));
    }

    function human_spent_time($minutes_var, $format = 'full') {
	if (!is_super_admin()){
	    return '';
	}
	if ($format == 'full') {
	    $str_hour = __('hour', 'yast');
	    $str_hours = __('hours', 'yast');
	    $str_minute = __('minute', 'yast');
	    $str_minutes = __('minutes', 'yast');
	    $str_none = __('No time lost', 'yast');
	}
	elseif ($format == 'tiny') {
	    $str_hour = _x('h','time', 'yast');
	    $str_hours = _x('h','time', 'yast');
	    $str_minute = _x('min','time', 'yast');
	    $str_minutes = _x('min','time', 'yast');
	    $str_none = __('None', 'yast');
	}
	else {
	    $str_hour = 'h';
	    $str_hours = 'h';
	    $str_minute = 'm';
	    $str_minutes = 'm';
	    $str_none = '-';
	}
	$minutes = abs($minutes_var);
	if ($minutes == 0) {
	    return $str_none;
	}
	if ($minutes > 60) {
	    $float = $minutes / 60;
	    $hours = round($float);
	    $min = round(($float - floor($float)) * 60);
	    return $hours . ' ' . ($hours == 1 ? $str_hour : $str_hours) . ' ' . $min . ' ' . ($min == 1 ? $str_minute : $str_minutes);
	}
	return $minutes . ' ' . ($minutes == 1 ? $str_minute : $str_minutes);
    }

    function total_spent_time($ticket_id) {
	global $wpdb;
	$req = "SELECT SUM(m.`meta_value`) FROM "
		. "`" . $wpdb->posts . "` as t, "
		. "`" . $wpdb->comments . "` as c, "
		. "`" . $wpdb->commentmeta . "` as m "
		. "WHERE "
		. "t.`ID`=" . $ticket_id . " AND "
		. "c.`comment_post_ID`=t.`ID` AND "
		. "m.`comment_id`=c.`comment_ID` AND "
		. "m.`meta_key`='spent_time'";
	$res = $wpdb->get_row($req, ARRAY_N);
	return $res[0];
    }

    function recursive_sanitize($array){
	foreach($array as $k=>$v){
	    if(is_array($v)){
		$array[$k] = $this->recursive_sanitize($v);
	    }
	    else{
		$array[$k]=sanitize_text_field($v);
	    }
	}
    }

    function comment_endcallback($comment) {
	$spent_time = get_comment_meta($comment->comment_ID, 'spent_time', true);
	echo'<span class="dashicons dashicons-backup"></span>';
	if ($spent_time) {
	    echo '<span class="yast_spet-time">' . sprintf(__('%s spent on this.', 'yast'), $this->human_spent_time($spent_time), 'tiny') . '</span>';
	}
	else {
	    echo '<span class="yast_spet-time">' . __('No time lost', 'yast') . '</span>';
	}
    }

    function single() {
	if (is_multisite()){
	    switch_to_blog($this->options['support_site']);
	}

	$ticket = $this->get_ticket(isset($_GET['ticket']) && is_numeric($_GET['ticket']) ? $_GET['ticket'] : NULL);
	$link = 'admin.php?page=yast_list&ticket=' . $ticket->ID . '&referer=' .esc_url(urlencode($_GET['referer']));
	?>
	<div class="wrap">
	    <div class="icon32" id="icon-yast"><br></div>
	    <h2><?php _e('Support ticket', 'yast'); ?> <?php echo  $ticket->ID ?>
		<?php if (is_super_admin()): ?>
		<a href="post.php?post=<?php echo $ticket->ID?>&action=edit&referer=" class="button"><?php _e('Edit ticket', 'yast'); ?></a>
		<?php endif; ?>
	    </h2>
	    <div  id="poststuff">
	<?php if (isset($_GET['referer'])): ?>
	    	<a href="<?php echo  esc_url($_GET['referer']) ?>">&laquo; <?php _e('Back to support tickets list', 'yast'); ?></a>
	<?php endif; ?>
		<h1><?php echo  $ticket->post_title ?></h1>
		<div class="postbox" id="ticket_content">
		    <h3><?php _e('Informations', 'yast'); ?></h3>
		    <div class="inside">
			<time>
			    <span><?php echo  date_i18n(get_option('date_format').', '.get_option('time_format'), strtotime($ticket->post_date)); ?></span>
			</time>
			<form action="#" method="post">
	<?php $this->custom_boxinfo($ticket) ?>
			</form>
		    </div>
		</div>


		<div class="postbox" id="ticket_comments">
		    <h3><?php _e('Process', 'yast'); ?></h3>
		    <div class="inside">

			<p>
			    <span class="dashicons dashicons-backup"></span>
			    <?php _e('Spent time', 'yast') ?>
			    <strong><?php echo  $this->human_spent_time($this->total_spent_time($ticket->ID)) ?></strong>
			</p>

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
	<?php if ($ticket->post_status == 'closed'): ?>
	    		<form action="admin-post.php" method="post" id="sent" class="yast_close">
	    <?php wp_nonce_field('ticket_status', 'ticket_status'); ?>
	    		    <input type="hidden" name="action" value="yastupdate">
	    		    <input type="hidden" name="referer" value="<?php echo  esc_url($_GET['referer']) ?>">
	    		    <input type="hidden" name="ticket" value="<?php echo  $ticket->ID ?>">
	    co		    <input type="hidden" name="post_status" value="open">
	    		    <input type="submit" value="<?php _e('Re-open this ticket ?', 'yast') ?>" class="button-primary">
	    		</form>
			    <?php else: ?>

	    <?php if (isset($_GET['confirm_reply'])) { ?>
				<form action="admin-post.php" method="post" id="sent" class="yast_close">
		<?php wp_nonce_field('ticket_status', 'ticket_status'); ?>
				    <input type="hidden" name="action" value="yastupdate">
				    <input type="hidden" name="referer" value="<?php echo  esc_url($_GET['referer']) ?>">
				    <input type="hidden" name="ticket" value="<?php echo  $ticket->ID ?>">
				    <input type="hidden" name="post_status" value="closed">
				    <input type="submit" value="<?php _e('Close this ticket ?', 'yast') ?>" class="button-primary">
				</form>
	    <?php } ?>
	    		<a id="yast_comment_link"><?php _e('Reply to this ticket', 'yast') ?></a>
	    		<form action="admin-post.php" method="post" id="yast_comment">
				<?php wp_nonce_field('ticket_comment', 'ticket_comment'); ?>
	    		    <input type="hidden" name="action" value="yastupdate">
	    		    <input type="hidden" name="referer" value="<?php echo  esc_url($_GET['referer'] . '#sent') ?>">
	    		    <input type="hidden" name="ticket" value="<?php echo  $ticket->ID ?>">
					<?php if (is_super_admin()): ?>
				    <p>
					<label>
		<?php _e('Spent time', 'yast') ?>
					    <input type="number" step="1" min="0" name="spent_time" id="spent_time_text"><?php _e('minutes', 'yast') ?><br>
		<?php printf(__("For information, you've opened this page %s ago.", 'yast'), '<a id="yast_realtime"></a>') ?>

					</label>


				    </p>
			    <?php endif; ?>
	    <?php wp_editor("", 'message', array('teeny' => true)); ?>
	    		    <input type="submit" class="button-primary" value="<?php _e('Send', 'yast') ?>">
	    		</form>
	<?php endif; ?>
		    </div>
		</div>

		<div class="postbox" id="ticket_tech">
		    <h3><?php _e('Technical details', 'yast'); ?></h3>
		    <div class="inside">
	<?php $this->custom_boxtech($ticket) ?>
		    </div>
		</div>

		<div class="yast_actions">
		    <form action="admin-post.php" method="post" id="delete" class="yast_delete">
	<?php wp_nonce_field('ticket_status', 'ticket_status'); ?>
			<input type="hidden" name="action" value="yastupdate">
			<input type="hidden" name="referer" value="<?php echo  esc_url($_GET['referer']) ?>">
			<input type="hidden" name="ticket" value="<?php echo  $ticket->ID ?>">
			<input type="hidden" name="post_status" value="trash">
			<input type="submit" value="<?php _e('Delete this ticket ?', 'yast') ?>" class="button">
		    </form>
		    <form action="admin-post.php" method="post" id="close" class="yast_close">
	<?php wp_nonce_field('ticket_status', 'ticket_status'); ?>
			<input type="hidden" name="action" value="yastupdate">
			<input type="hidden" name="referer" value="<?php echo  esc_url($_GET['referer']) ?>">
			<input type="hidden" name="ticket" value="<?php echo  $ticket->ID ?>">
			<input type="hidden" name="post_status" value="closed">
			<input type="submit" value="<?php _e('Close this ticket ?', 'yast') ?>" class="button">
		    </form>
		</div>

	    </div>
	</div>
	<?php
	if (is_multisite()){
	    restore_current_blog();
	}
    }

    function query($pages = 20, $status = 'open,publish', $orderby = null) {
	if (is_multisite()){
	    switch_to_blog($this->options['support_site']);
	}
	$paged = ( \filter_input(INPUT_GET,'paged',FILTER_SANITIZE_NUMBER_INT) && is_numeric($_GET['paged']) ) ? $_GET['paged'] : 1;
	if($post_status = \filter_input(INPUT_GET,'post_status',FILTER_SANITIZE_STRING)){
	    if (in_array($post_status, array('open','closed','trash','all'))){
		$status = $post_status;
	    }
	}
	$meta_query = array();
	$user_id = get_current_user_id();
	if (
		!is_multisite() && !user_can($user_id, 'manage_options') ||
		is_multisite() && !user_can($user_id, 'manage_network_options')
	) {
	    $meta_query = array(
		'relation' => 'OR',
		array(
		    'key' => 'visibility',
		    'value' => 'public',
		    'compare' => '='
		),
		array(
		    'key' => 'visibility',
		    'value' => '',
		    'compare' => '='
		),
		array(
		    'key' => 'reporter',
		    'value' => $this->current_reporter(),
		    'compare' => '='
		),
		array(
		    'key' => 'assigned',
		    'value' => $this->current_reporter(),
		    'compare' => '='
		)
	    );
	}

	$query_arg = array(
	    'post_type' => 'yast',
	    'post_parent' => 0,
	    'post_status' => $status,
	    'posts_per_page' => $pages,
	    'paged' => $paged,
	    'meta_query' => $meta_query,
	    'orderby' => $orderby
	);
	if (is_multisite()){
	    restore_current_blog();
	}
	return $query_arg;
    }

    function liste() {
	if (isset($_GET['ticket'])) {
	    $this->single();
	    return;
	}
	if (is_multisite()){
	    switch_to_blog($this->options['support_site']);
	}
	$paged = ( isset($_GET['paged'])&& is_numeric($_GET['paged']) ) ? $_GET['paged'] : 1;
	$status = ( isset($_GET['post_status'])  && in_array($_GET['post_status'], array('open','closed','trash')) ) ? $_GET['post_status'] : 'open';

	$meta_query = array();
	$user_id = get_current_user_id();

	$tickets = new WP_query($this->query());

	if (is_multisite()){
	    restore_current_blog();
	}
	$big = 999999999; // need an unlikely integer
	$page_arg = array(
	    'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
	    'format' => 'admin.php?page=yast_list&paged=%#%',
	    'current' => $paged,
	    'total' => $tickets->max_num_pages
	);

	$levels = $this->levels();
	?>
	<div class="wrap">
	    <div class="icon32" id="icon-yast"><br></div>
	    <h2><?php _e('Support Tickets', 'socialmap'); ?></h2>
	    <table class="widefat">
		<tr>
		    <td colspan=11">
			<nav>
			    <a href="admin.php?page=yast_list&post_status=all"><?php _e('All', 'yast') ?></a>
			    <a href="admin.php?page=yast_list&post_status=open"><?php _e('Open', 'yast') ?></a>
			    <a href="admin.php?page=yast_list&post_status=closed"><?php _e('Closed', 'yast') ?></a>
			    <a href="admin.php?page=yast_list&post_status=trash"><?php _e('Trash', 'yast') ?></a>

			</nav>
		    </td>
		    <td>
			<?php echo paginate_links($page_arg); ?>
		    </td>
		    <td align="right">
	<?php if (is_super_admin()) : ?>
	    		<a href="#TB_inline?width=600&height=400&inlineId=report_ticketbox" class="thickbox button button-primary"><?php _e('New ticket', 'yast') ?></a>
	<?php endif; ?>
		    </td>
		</tr>
	    </table>
	    <table id="yast_list" class="widefat">

		<thead>

		    <tr>
			<th><span class="icon-yast-bug"></span>&nbsp;<?php _e('Title', 'yast') ?></th>
			<th><span class="dashicons dashicons-tag"></span>&nbsp;<?php _e('Type', 'yast') ?></th>
			<th><span class="dashicons dashicons-admin-links"></span>&nbsp;<?php _e('Page', 'yast') ?></th>
			<th><span class="dashicons dashicons-info"></span>&nbsp;<?php _e('Priority', 'yast') ?></th>
			<th><span class="dashicons dashicons-admin-users"></span>&nbsp;<?php _e('Assigned', 'yast') ?></th>
			<th><span class="dashicons dashicons-admin-users"></span>&nbsp;<?php _e('User', 'yast') ?></th>
			<th><span class="dashicons dashicons-calendar"></span>&nbsp;<?php _e('Date', 'yast') ?></th>
			<th><span class="dashicons dashicons-admin-comments" title="<?php _e('Answers', 'yast') ?>"></span></th>
			<th><?php echo  (is_super_admin() ? '<span class="dashicons dashicons-backup" title="'.__('Spent time', 'yast').'"></span>' : '') ?></th>
			<th colspan="2"><?php _e('Status', 'yast') ?></th>
			<th><?php _e('Actions', 'yast') ?></th>
		    </tr>
		</thead>
		<tbody>
		    <?php
		    if (is_multisite()){
			switch_to_blog($this->options['support_site']);
		    }
		    while ($tickets->have_posts()):
			$tickets->the_post();
			$ticket = $this->get_ticket(get_the_ID());
			$types = get_the_terms($ticket->ID, 'ticket_type');
			if (!$types){
			    $types = array();
			}
			$link = 'admin.php?page=yast_list&ticket=' . $ticket->ID . '&referer=' . urlencode('admin.php?page=yast_list&post_status=' . $status . '&paged=' . $paged);
			$referer = ('admin.php?page=yast_list&post_status=' . $status . '&paged=' . $paged);
			$count = count(get_comments(array('post_id' => $ticket->ID)));
			?>
	    	    <tr class="yast_<?php echo  $ticket->post_status; ?> yast_<?php echo  $ticket->visibility ?> yast_<?php echo  ($count > 0 ? 'active' : 'waiting') ?>">
			<td>
			    <a href="<?php echo  $link ?>"><span class="dashicons dashicons-edit"></span>
				    <?php
				    if('' === get_the_title()){
					echo strip_tags(get_the_excerpt());
				    }
				    else{
					the_title();
				    }
				    ?>
			    </a>
			</td>
	    		<td><?php
		    foreach ($types as $type) {
			echo $type->name . ' ';
		    }
		    ?></td>
	    		<td><a href="<?php echo  $ticket->page['url'] ?>" target="_blank"><?php echo  substr($ticket->page['url'], 0, 20) . '...' ?></a></td>
	    		<td><span style="color:<?php echo  $levels[$ticket->priority]['color'] ?>;"><?php echo  $levels[$ticket->priority]['name'] ?></span></td>
	    		<td><?php echo  $this->display_user($ticket->assignedto) ?></td>
	    		<td><?php echo  $this->display_user($ticket->reporter) ?></td>
	    		<td><?php the_date(); ?></td>
	    		<td align="center"><?php echo $count ?></td>
	    		<td><?php echo  str_replace(' ','&nbsp;',$this->human_spent_time($this->total_spent_time($ticket->ID), 'small')) ?></td>
	    		<td><?php echo  __(ucfirst($ticket->post_status), 'yast'); ?></td>
			<td><?php if ($ticket->visibility == 'private'): ?>
				    <span class="yast_icon yast_icon-private" title="<?php _e('Private', 'yast'); ?>">P</span>
	    <?php endif; ?></td>
	    		<td>
	    		    <div class="yast_actions">
	    <?php if ($ticket->post_status != 'closed') : ?>
					<form action="admin-post.php" method="post" class="yast_formbutton yast_close">
					<?php wp_nonce_field('ticket_status', 'ticket_status'); ?>
					    <input type="hidden" name="action" value="yastupdate">
					    <input type="hidden" name="referer" value="<?php echo  $referer ?>">
					    <input type="hidden" name="ticket" value="<?php echo  $ticket->ID ?>">
					    <input type="hidden" name="post_status" value="closed">
					    <input type="submit" value="C" title="<?php _e('Close', 'yast'); ?>">
					</form>
	    <?php else: ?>
					<form action="admin-post.php" method="post" class="yast_formbutton yast_open">
					<?php wp_nonce_field('ticket_status', 'ticket_status'); ?>
					    <input type="hidden" name="action" value="yastupdate">
					    <input type="hidden" name="referer" value="<?php echo  $referer ?>">
					    <input type="hidden" name="ticket" value="<?php echo  $ticket->ID ?>">
					    <input type="hidden" name="post_status" value="open">
					    <input type="submit" value="O" title="<?php _e('Re-Open', 'yast'); ?>">
					</form>
	    <?php endif; ?>
	    			<form action="admin-post.php" method="post" class="yast_formbutton yast_delete">
	    <?php wp_nonce_field('ticket_status', 'ticket_status'); ?>
	    			    <input type="hidden" name="action" value="yastupdate">
	    			    <input type="hidden" name="referer" value="<?php echo  $referer ?>">
	    			    <input type="hidden" name="ticket" value="<?php echo  $ticket->ID ?>">
	    			    <input type="hidden" name="post_status" value="trash">
	    			    <input type="submit" value="X" title="<?php _e('Delete', 'yast'); ?>">
	    			</form>
	    		    </div>
	    		</td>

	    	    </tr>
	<?php endwhile; ?>
		</tbody>
	    </table>
	    <div id="yast_stat">

	    </div>
	</div>
	<?php
	if (is_multisite()){
	    restore_current_blog();
	}
    }

    function levels($level = false) {
	$levels = array(
	    0 => array('name' => __('Low', 'yast'), 'color' => '#DC0'),
	    1 => array('name' => __('Normal', 'yast'), 'color' => '#F90'),
	    2 => array('name' => __('High', 'yast'), 'color' => '#930'),
	    3 => array('name' => __('Critical', 'yast'), 'color' => '#F00')
	);
	if ($level !== false && isset($levels[$level])) {
	    return $levels[$level];
	}
	return $levels;
    }

    function scripts_front() {
	global $wp_styles;
	wp_enqueue_style('yast', plugins_url('/css/style.css', __FILE__), false, null);
	wp_enqueue_style('yast-font-ie7', plugins_url('/css/yast-icomoon-ie7.css', __FILE__), false, null);
	$wp_styles->add_data('yast-font-ie7', 'conditional', 'IE 7');


	wp_enqueue_script('yast', plugins_url('/js/front.js', __FILE__), array('jquery'), false, true);
	$ajaxurl = admin_url('admin-ajax.php');
	if ($this->options['force_ssl'] == 1) {
	    $ajaxurl = str_replace('http://', 'https://', $ajaxurl);
	}
	wp_localize_script('yast', 'yast', array(
	    'ajaxurl' => $ajaxurl,
	));
    }

    function scripts_admin() {
	global $wp_styles;
	wp_enqueue_style('yast', plugins_url('/css/style.css', __FILE__), false, null);
	wp_enqueue_style('yast-font-ie7', plugins_url('/css/yast-icomoon-ie7.css', __FILE__), false, null);
	$wp_styles->add_data('yast-font-ie7', 'conditional', 'IE 7');
	wp_enqueue_script('yast', plugins_url('/js/front.js', __FILE__), array('jquery'), false, true);
	wp_enqueue_script('yast_admin', plugins_url('/js/admin.js', __FILE__), array('jquery'), false, true);
	wp_enqueue_script('yast_icons', plugins_url('/js/icons.js', __FILE__), array('jquery'), false, true);
	wp_localize_script('yast', 'yast', array(
	    'ajaxurl' => admin_url('admin-ajax.php'),
	    'locale' => array(
		'hours' => __('hours', 'yast'),
		'minutes' => __('minutes', 'yast'),
		'seconds' => __('seconds', 'yast')
	    )
	));
    }
    /*     * ******* LIST PAGE * */

    // ADD NEW COLUMN
    function columns_head($defaults) {
	$defaults['etat'] = __('Status', 'yast');
	$defaults['level'] = __('Level', 'yast');
	return $defaults;
    }

    // COLUMN CONTENT
    function columns_content($column_name, $post_ID) {
	if ($column_name == 'etat') {
	    echo _x(ucfirst(get_post_status()), 'yast');
	}
	if ($column_name == 'level') {
	    $ticket = $this->get_ticket($post_ID);
	    $level = $this->levels($ticket->priority);
	    echo' <span style="color:' . $level['color'] . ';">' . $level['name'] . '</span>';
	}
    }
    /*     * ********* EDIT PAGE ** */

    function submitbox($str) {
	global $post;
	if ($post->post_type == 'yast') {
	    ?>
	    <div class="misc-pub-section" id="ticket-status">
	        <label>
	    <?php _e('Status:', 'yast') ?>

		    <?php wp_nonce_field( 'ticket_save', 'ticket_save') ?>
	    	<select id="post_status" name="ticket_status">
		    <option></option>
	    	    <option value="open" <?php echo  $post->post_status == 'open' || $post->post_status == 'publish' ? 'selected' : '' ?>><?php _e('Open', 'yast') ?></option>
	    	    <option value="closed" <?php echo  $post->post_status == 'closed' ? 'selected' : '' ?>><?php _e('Closed', 'yast') ?></option>
	    	</select>
	        </label>
		<p>
		<a href="admin.php?page=yast_list&ticket=<?php echo $post->ID?>&referer=admin.php%3Fpage%3Dyast_list"><?php _e('Go back to ticket', 'yast') ?></a>
		</p>
	    </div>
	    <?php
	}
    }

    function add_custom_box() {
	#add_meta_box('ticket_content', __('Description', 'yast'), array(&$this, 'custom_boxinfo'), 'yast', 'normal', 'high');
	add_meta_box('ticket_tech', __('Technical details', 'yast'), array(&$this, 'custom_boxtech'), 'yast', 'normal', 'high');
	add_meta_box('custom_boxauthor', __('Reporter', 'yast'), array(&$this, 'custom_boxauthor'), 'yast', 'normal', 'high');
    }


    function human_nav_info($nav) {
	global $YAST_tools;
	$nav = $YAST_tools->navigator($nav);
	$nav_info='';
	foreach ($nav as $prop => $vals) {
	    foreach ($vals as $type => $val) {
		$nav_info.= ' ' . $val;
	    }
	    $nav_info.= ', ';
	}
	$nav_info = substr($nav_info, 0, -2);
	return $nav_info;
    }

    function label($str = '', $icon = 'admin-posts', $class = '', $style = '', $tag = 'li', $iconpack = 'dashicons') {
	return '<' . $tag . ($style != '' ? ' style="' . $style . '"' : ' ') . ($class != '' ? ' class="' . $class . '"' : ' ') . '>
                    <span class="' . $iconpack . ' ' . $iconpack . '-' . $icon . '"></span>
                    ' . $str . '
                </' . $tag . '>';
    }

    function custom_boxauthor($ticket = false){
	if (!$ticket || !isset($ticket->assignedto)){
	    $ticket = $this->get_ticket(isset($_GET['post']) && is_numeric($_GET['post']) ? $_GET['post']: NULL);
	}
	wp_enqueue_script('user-suggest', admin_url('/js/user-suggest.min.js'), array('jquery'), false, true);
	?>
	<input name="yast_reporter" type="text" id="yast_reporter" class="wp-suggest-user widefat" value="<?php echo  $ticket->reporter ?>" />

	<?php
    }

    function custom_boxinfo($ticket = false) {
	$conf = $this->options;

	if (!$ticket || !isset($ticket->assignedto)){
	    $ticket = $this->get_ticket(isset($_GET['post'])&& is_numeric($_GET['post'])  ? $_GET['post']: NULL);
	}
	$types = get_the_terms($ticket->ID, 'ticket_type');
	if (!$types) {
	    $types = array();
	}
	?>
	<div class="ticket_description">
		<?php echo  apply_filters('the_content', $ticket->post_content) ?>
	</div>
	<div class="yast_ticket_navigator">
	    <ul>
	<?php echo $this->label($this->display_user($ticket->reporter), 'admin-users'); ?>


		<?php
		if ($ticket->page != NULL) {
		    echo $this->label('<a href="' . $ticket->page['url'] . '" target="_blank">' . $ticket->page['url'] . '</a>', 'admin-links');
		}
		?>
	    </ul>

	    <ul>

		<?php
		if (is_numeric($ticket->priority)) {
		    $level = $this->levels($ticket->priority);
		    echo $this->label($level['name'], 'info', 'yast-level-' . $ticket->priority);
		}
		?>

		<?php
		foreach ($types as $type) {
		    echo $this->label($type->name, 'tag');
		}
		?>


	<?php
	if ($ticket->visibility == 'private') {
	    echo $this->label(__('Private', 'yast'), 'lock', 'yast-private');
	}
	else {
	    echo $this->label(__('Public', 'yast'), 'lock', 'yast-public');
	}
	?>

		<?php
		if ($ticket->navigator != NULL):
		    global $YAST_tools;
		    $nav = $YAST_tools->navigator($ticket->navigator['userAgent']);

		    foreach ($nav as $prop => $vals) {
			$str = '';
			$class = '';
			foreach ($vals as $type => $val) {
			    $str.=$val.=' ';
			    $class.=' icon-yast-' . $val;
			}
			if (!empty($str)) {
			    echo $this->label($str, strtolower($class), strtolower('yast-' . $prop . ' ' . $str), NULL, 'li', 'icon-yast');
			}
		    }
		    ?>

	<?php endif ?>

	    </ul>
	</div>



	<input id="comment_status" type="hidden" value="open" name="comment_status">
	<input id="ping_status" type="hidden" value="open" name="ping_status">

	    <?php
	    $children = new WP_Query('post_type=yast&post_status=all&posts_per_page=-1&post_parent=' . $ticket->ID);
	    if (sizeof($children->posts) > 0) {
		?>
	    <h3><?php _e('Merged tickets :', 'yast') ?></h3>
	    <?php foreach ($children->posts as $child) {
		$child = $this->get_ticket($child->ID);
		?>
		<div class="ticket_description">
		    <h4>
			<a href="<?php echo  $child->lien ?>">
		<?php echo  $child->post_title ?>
			</a>
		<?php echo  $this->display_user($child->reporter) ?>
		    </h4>
		<?php echo  apply_filters('the_content', $child->post_content) ?>
		    <p><a href="<?php echo  $child->page['url'] ?>" target="_blank"><?php echo  $child->page['url'] ?></a></p>
		</div>
		<?php
	    }
	}
    }

    function custom_boxprocess($ticket = false) {
	wp_enqueue_script('user-suggest', admin_url('/js/user-suggest.min.js'), array('jquery'), false, true);
	$conf = $this->options;

	if (!$ticket || !isset($ticket->assignedto)){
	    $ticket = $this->get_ticket(isset($_GET['post'])&& is_numeric($_GET['post']) ? $_GET['post']: NULL);
	}
	?>


	<div class="yast_ticket_navigator">
	    <ul>

		<li>
		    <?php _e('Assigned to:', 'yast') ?>
		    <span id="yast_assigneto"><?php echo  $this->display_user($ticket->assignedto) ?></span>
		    <?php
		    if ($conf['remote_use'] != 'client' &&
			    (!is_multisite() && user_can(get_current_user_id(), 'manage_options') ||
			    is_multisite() && user_can(get_current_user_id(), 'manage_network_options'))
		    ) {
			?>
	    	    <input name="assigned" data-id="<?php echo  $ticket->ID ?>" type="text" id="yast_assigned" class="wp-suggest-user" value="<?php echo  $ticket->assigned->user_login ?>" />
	    	    <a id="yast_reassigne" class="button"><?php _e('Assign', 'yast') ?></a>
	    	    <a id="yast_assigne"><?php _e('Change', 'yast') ?></a>
	<?php } ?>
		</li>

		    <?php wp_nonce_field( 'ticket_merge', 'ticket_merge' ) ?>
		<li id="yast_merge" data-id="<?php echo  $ticket->ID ?>">
	<?php if ($ticket->post_parent == 0) { ?>
	    	    <button><?php _e('Merge with another ticket', 'yast') ?></button>
	<?php
	}
	else {
	    $parent = $this->get_ticket($ticket->post_parent);
	    ?>
	    	    <a href="<?php echo  $parent->lien ?>">
	    <?php printf(__('Merged with ticket #%d : %s', 'yast'), $ticket->post_parent, $parent->post_title); ?>
	    	    </a>
	<?php } ?>
		</li>

	    </ul>
	</div>



	<input id="comment_status" type="hidden" value="open" name="comment_status">
	<input id="ping_status" type="hidden" value="open" name="ping_status">

	    <?php
	}

	function custom_boxtech($ticket = false) {
	    if (!$ticket || !isset($ticket->assignedto)){
		$ticket = $this->get_ticket(isset($_GET['post'])&& is_numeric($_GET['post']) ? $_GET['post']: NULL);
	    }
	    ?>
	<?php if ($ticket->navigator != ''): ?>
	    <div class="ticket_navigator">
	        <h5><?php _e('Environment', 'yast') ?></h5>
	        <p><?php _e('User Agent:', 'yast') ?> <?php echo  $ticket->navigator['userAgent'] ?></p>
	    </div>
	<?php endif ?>

	<?php if ($ticket->page != NULL): ?>
	    <div class="ticket_case_url">
	        <h5><?php _e('Case URL', 'yast') ?></h5>
	        <p><a href="<?php echo  $ticket->page['url'] ?>" target="ticket_preview" id="ticket_preview_link"><?php echo  $ticket->page['url'] ?></a></p>
	    <?php if (sizeof($ticket->page['post']) > 1): ?>
		    <b>POST:</b>
		    <pre><?php print_r($ticket->page['post']) ?></pre>
	    <?php else: ?>
		    <p><?php _e('No POST variables', 'yast') ?></p>
		    <?php endif ?>
	        <iframe src="about:blank" height="200" width="100%" name="ticket_preview" id="ticket_preview_iframe"></iframe>
	    </div>
		<?php endif ?>


	<?php
    }

    function select() {
	if (!current_user_can('manage_options')){
	    return;
	}
	?>
	<select>
	    <option></option>
	    <option value='0'>*** <?php _e('None', 'yast') ?> ***</option>
	    <optgroup label="<?php _e('Open tickets', 'yast') ?>">
	<?php
	$tickets = new WP_query($this->query(-1, 'open', 'post_title'));
	while ($tickets->have_posts()): $tickets->the_post();
	    ?>
	    	<option value="<?php the_ID(); ?>"><?php the_title(); ?>  - <?php the_date() ?></option>
	<?php endwhile; ?>
	    </optgroup>
	    <optgroup label="<?php _e('Closed tickets', 'yast') ?>">
	<?php
	$tickets = new WP_query($this->query(-1, 'closed', 'post_title'));
	while ($tickets->have_posts()): $tickets->the_post();
	    ?>
	    	<option value="<?php the_ID(); ?>"><?php the_title(); ?>  - <?php the_date() ?></option>
	<?php endwhile; ?>
	    </optgroup>
	</select>
	<?php
	exit;
    }

    function post_options() {
	if (!current_user_can('manage_options')){
	    return;
	}
	if (!isset($_POST['ticket_set_options']) || !wp_verify_nonce($_POST['ticket_set_options'], 'ticket_set_options')) {
	    wp_die(__('Security error', 'yast'));
	}
	if (isset($_POST['yast_options'])) {
	    $yast_options = $_POST['yast_options'];
	    $yast_options['alert_emails'] = explode(',', $yast_options['alert_emails']);



	    update_site_option('yast_options', $yast_options);
	    wp_redirect('admin.php?page=yast_options&confirm=options_saved');
	    exit;
	}
    }

    function options() {
	if (isset($_GET['confirm']) && $_GET['confirm'] == 'options_saved') {
	    ?>
	    <div class="updated" id="yast_options_confirm"><strong><?php _e('YAST Support-Tickets options saved !', 'yast') ?></strong></div>
	    <?php
	}
	$yast_options = $this->options;
	?>
	<div class="wrap">
	    <div class="icon32" id="icon-yast"><br></div>
	    <h2><?php _e('Support Tickets Options', 'yast'); ?></h2>
	    <form name="form1" id="yast_options_form" method="post" action="admin-post.php">
		<input type="hidden" name="action" value="yastsaveoptions">
		<?php wp_nonce_field('ticket_set_options', 'ticket_set_options'); ?>
		<h3><?php _e('Alerts', 'yast'); ?></h3>
		<p>
		    <label>
	<?php _e('Send email alert to:', 'yast') ?>
			<input type="text" name="yast_options[alert_emails]" value="<?php echo implode(',', $yast_options['alert_emails']); ?>" />
		    </label>
		</p>
		<h3><?php _e('Support Form', 'yast'); ?></h3>
		<p>
		    <label>
	<?php _e('Ticket type to use in adminbar', 'yast') ?>
			<select name="yast_options[default_type]">
			    <option value=''></option>
		<?php foreach ($this->types as $type) { ?>
	    		    <option value='<?php echo  $type->slug ?>' <?php
	    if ($yast_options['default_type'] == $type->slug){
		echo'selected';
	    }
	    ?>><?php echo  $type->name ?></option>
			<?php } ?>
			</select>
		    </label>
		</p>
		<p>
		    <label>
				<?php _e('Force SSL', 'yast') ?>
			<select name="yast_options[force_ssl]">
			    <option value='0' <?php echo  ($yast_options['force_ssl'] == 0 ? 'selected' : '') ?>><?php _e('No', 'yast') ?></option>
			    <option value='1' <?php echo  ($yast_options['force_ssl'] == 1 ? 'selected' : '') ?>><?php _e('Yes', 'yast') ?></option>
			</select>
		    </label>
		</p>
	<?php if (is_multisite()): ?>
	    	<h3><?php _e('Multisite', 'yast'); ?></h3>
	    	<p>
	    	    <label>
	    <?php _e('Use this site as Support site', 'yast') ?>
	    		<select id="yast_options_remote_use" name="yast_options[remote_use]">
	    <?php
	    $blogs_list = wp_get_sites(array('limit' => 0, 'deleted' => false, 'archived' => false, 'spam' => false));
	    foreach ($blogs_list as $blog):
		?>
				    <option value="<?php echo  $blog['blog_id'] ?>" <?php echo ($yast_options['support_site'] === $blog['blog_id'] ? 'selected' : ''); ?>>
		<?php echo  $blog['domain'] ?>
				    </option>
	    <?php endforeach; ?>
	    		</select>
	    	    </label>
	    	</p>
	<?php endif; ?>
		<p class="submit">
		    <input type="submit" value="<?php _e('Apply settings', 'yast'); ?>" class="button button-primary" id="submit" name="submit">
		</p>
	    </form>
	</div>
	<?php
    }
    /*     * *************** CORE FUNCTIONS * */

    function get_conf() {
	$options = get_site_option('yast_options', array());

	// Get default values
	$default = array(
	    'alert_emails' => array(),
	    'remote_server' => '',
	    'remote_token' => '',
	    'local_token' => '',
	    'remote_use' => 'none',
	    'default_type' => '',
	    'force_ssl' => 0,
	    'support_site' => 1
	);
	$options = shortcode_atts($default, $options);
	if ($options['local_token'] == '') {
	    $options['local_token'] = sha1(time());
	    update_site_option('yast_options', $options);
	}
	return $options;
    }

    function get_ticket($post_id = null) {
	if (is_multisite()){
	    switch_to_blog($this->options['support_site']);
	}
	$post = get_post($post_id);
	if (is_object($post)) {
	    $post->navigator = get_post_meta($post->ID, 'navigator', true);
	    $post->page = get_post_meta($post->ID, 'page', true);
	    $post->priority = get_post_meta($post->ID, 'priority', true);
	    $post->reporter = get_post_meta($post->ID, 'reporter', true);
	    $post->assignedto = get_post_meta($post->ID, 'assigned', true);
	    $post->assigned = $this->assigned($post->ID);
	    $post->visibility = get_post_meta($post->ID, 'visibility', true);
	    if (empty($post->visibility)){
		$post->visibility = 'public';
	    }
	    $post->server_ticket = get_post_meta($post->ID, 'server_ticket', true);
	    $post->spent_time = get_post_meta($post->ID, 'spent_time', true);
	    $post->lien = admin_url() . 'admin.php?page=yast_list&ticket=' . $post->ID . '&referer=admin.php%3Fpage%3Dyast_list';
	    return $post;
	}
	if (is_multisite()){
	    restore_current_blog();
	}
	return false;
    }

    function merge() {
	if (!is_user_logged_in()){
	    return;
	}
	if (!current_user_can('manage_options')){
	    return;
	}
	if (!isset($_POST['ticket_merge']) || !wp_verify_nonce($_POST['ticket_merge'], 'ticket_merge')) {
	    wp_die(__('Security error', 'yast'));
	}
	if (is_multisite()){
	    switch_to_blog($this->options['support_site']);
	}
	if (is_numeric($_POST['ticket_id']) && is_numeric($_POST['post_parent'])) {
	    wp_update_post(array(
		'ID' => $_POST['ticket_id'],
		'post_parent' => $_POST['post_parent'],
		'post_status' => 'closed'
	    ));
	    if ($_POST['post_parent'] == 0) {
		_e('An error occured !', 'yast');
	    }
	    else {
		$ticket = $this->get_ticket($_POST['ticket_id']);
		$parent = $this->get_ticket($_POST['post_parent']);
		echo '<a href="' . $parent->lien . '">' . $parent->post_title . '</a>';
		global $current_user;
		get_currentuserinfo();

		$this->reply(
			array(
			    'ticket' => $ticket,
			    'user_name' => $current_user->user_login,
			    'user_email' => $current_user->user_email,
			    'user_id' => $current_user->ID,
			    'message' => sprintf(__('Support Ticket merge width ticket #%d : %s
%s', 'yast'), $parent->ID, $parent->post_title, $parent->lien),
			    'confirm' => false,
			    'spent_time' => 0,
			)
		);
	    }
	}
	if (is_multisite()){
	    restore_current_blog();
	}
	exit;
    }

    function assign() {
	if (isset($_REQUEST['ticket_id']) && isset($_REQUEST['assign']) && is_numeric($_REQUEST['ticket_id'])) {
	    if (is_multisite()){
		switch_to_blog($this->options['support_site']);
	    }
	    if ((false !== $ticket = $this->get_ticket($_REQUEST['ticket_id'])) && (false !== $assign = get_user_by('login', esc_attr($_REQUEST['assign'])))) {

		global $current_user;
		get_currentuserinfo();

		$level = $this->levels($ticket->priority);
		$server_name = basename(get_site_url());
		if (empty($server_name)){
		    $server_name = basename(get_site_url());
		}
		$assigned = esc_attr($_REQUEST['assign']) . '@' . $server_name;

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

Issue link : %12$s', 'yast'),
			$ticket->ID,
			$current_user->user_nicename,
			$ticket->post_title,
			$ticket->type,
			$level['name'],
			$ticket->post_content,
			date_i18n(get_option('date_format').', '.get_option('time_format'),strtotime($ticket->post_date)),
			$ticket->page['url'],
			$ticket->page['post'],
			$this->human_nav_info($ticket->navigator['userAgent']),
			get_bloginfo('name'),
			$ticket->lien
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
	if (is_multisite()){
	    restore_current_blog();
	}
	exit;
    }

    function assigned($post_id) {
	$assigned = get_post_meta($post_id, 'assigned', true);
	$ass = explode('@', $assigned);
	if (isset($ass[1]) && $ass[1] == basename(get_site_option('siteurl'))) {
	    return get_user_by('login', $ass[0]);
	}
	elseif (is_string($assigned)) {
	    return get_user_by('login', $assigned);
	}
	return get_userdata($assigned);
    }

    function display_user($user, $html = true) {
	if(empty($user)){
	    return '?';
	}

	$pos = strrpos($user, '@');
	if ($pos <= 0){
	    return $user;
	}
	$user_name = substr($user, 0, $pos);
	//$server_name = substr($user, $pos + 1);
	if (!$html){
	    return $user_name;
	}
	if(false !== $wpuser = get_user_by('login', $user_name)){
	    return '<span class="yast_user">' . $wpuser->display_name . '</span>';
	}
	return '<span class="yast_user">' . $user_name . '</span>';
	//<span class="yast_delimit">(</span><span class="yast_server">' . $server_name . '</span><span class="yast_delimit">)</span>';
    }

    function current_reporter() {
	global $current_user;
	get_currentuserinfo();
	return $current_user->user_login; // . '@' . basename(get_site_option('siteurl'));
    }

    function is_server_request() {
	if (filter_input(INPUT_POST, 'remote_ticket',FILTER_SANITIZE_NUMBER_INT) && filter_input(INPUT_POST, 'remote_client',FILTER_SANITIZE_STRING) && filter_input(INPUT_POST, 'remote_token',FILTER_SANITIZE_STRING)) {
	    return true;
	}
	return false;
    }

    function default_types() {
	return $this->types;
    }
    /*
     * comment_dests
     * retreive a list of users concerned by a particular ticket
     *
     * @return: array of users
     *
     */
    function comment_dests($ticket) {
	$dests = array();
	//Notify assigned user
	if (isset($ticket->assigned->user_email)) {
	    $dests[$ticket->assigned->user_email] = $ticket->assigned->user_email;
	}
	//Notify ticket reporter
	if (false !== $reporter = get_user_by('login', substr($ticket->reporter, 0, strrpos($ticket->reporter, '@')))) {
	    $dests[$reporter->user_email] = $reporter->user_email;
	}
	//Notify any ticket-comment users
	$comments = get_comments(array(
	    'post_id' => $ticket->ID,
	    'status' => 'approve'
	));
	foreach ($comments as $comment) {
	    $dests[$comment->comment_author_email] = $comment->comment_author_email;
	}
	//Notify any attached-ticket reporters
	$children = new WP_Query('post_type=yast&post_status=all&posts_per_page=-1&post_parent=' . $ticket->ID);
	if (sizeof($children->posts) > 0) {
	    foreach ($children->posts as $child) {
		$child = $this->get_ticket($child->ID);
		if (false !== $reporter = get_user_by('login', substr($child->reporter, 0, strrpos($child->reporter, '@')))) {
		    $dests[$reporter->user_email] = $reporter->user_email;
		}
	    }
	}
	return $dests;
    }

    function send_to_server($action, $ticket_id, $url, $more = array()) {
	return;
    }
    /*
     * reply
     * registers a comment and send notifications
     *
     * @return: void
     *
     */
    function reply($atts) {
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

	if (is_numeric($ticket)){
	    $ticket = $this->get_ticket($ticket);
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
		    wp_mail($dest, sprintf(__('[Support Ticket] #%d', 'yast'), $ticket->ID), sprintf(__('A new comment has been posted by %4$s.
Subject: %1$s
Comment:
%2$s

--
Issue link : %3$s
', 'yast'), $ticket->post_title, strip_tags($message), $ticket->lien, $this->display_user($user_name, false)), $headers
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
			    if ($confirm){
				_e("Comment is synchronised with the technical server.", 'yast');
			    }
			}
			else {
			    if ($confirm){
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
			    if ($confirm){
				_e("Comment is synchronised with the client.", 'yast');
			    }
			}
			else {
			    if ($confirm){
				_e("Comment could'nt be synchronised with the client.", 'yast');
			    }
			}
		    }
		}
	    }
	}
    }
    /*
     * savepost
     * called by savepost hook
     * overwrite post status of the ticket being saved
     *
     */
    function savepost($ticket_id){
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
            return;
	}
	if (!isset($_POST['post_type']) || $_POST['post_type']!='yast'){
	    return;
	}
	if (!isset($_POST['ticket_save']) || !wp_verify_nonce($_POST['ticket_save'], 'ticket_save')) {
	    wp_die(__('Security error', 'yast'));
	}
	if(isset($_POST['ticket_status']) && in_array($_POST['ticket_status'], array('open','closed','trash'))){


	    update_post_meta($ticket_id,'reporter',esc_attr($_POST['yast_reporter']));
	    header('TestYast2: ok');
	    remove_action('save_post', array(&$this, 'savepost'));
	    wp_update_post(array(
		'ID' => $ticket_id,
		'post_status' => $_POST['ticket_status']
	    ));
	}

    }

    /*
     * Update a ticket within posting a comment
     *
     */
    function update() {
	if (!is_user_logged_in()) {
	    wp_die(__('Security error', 'yast'));
	}
	if (!isset($_POST['ticket']) || !is_numeric($_POST['ticket'])) {
	    wp_die(__('Missing ticket ID', 'yast'));
	}
	if (is_multisite()){
	    switch_to_blog($this->options['support_site']);
	}

	$ticket = $this->get_ticket($_POST['ticket']);
	$link = 'admin.php?page=yast_list&ticket=' . $ticket->ID . '&referer=' . urlencode(esc_url($_POST['referer']));

	if (isset($_POST['message']) && trim(strip_tags($_POST['message'])) != '') {
	    if (!isset($_POST['ticket_comment']) || !wp_verify_nonce($_POST['ticket_comment'], 'ticket_comment')) {
		wp_die(__('Security error', 'yast'));
	    }
	    else {
		global $current_user;
		get_currentuserinfo();
		$this->reply(
			array(
			    'ticket' => $ticket,
			    'user_name' => $current_user->user_login,
			    'user_email' => $current_user->user_email,
			    'user_id' => $current_user->ID,
			    'message' => esc_attr(stripslashes($_POST['message'])),
			    'confirm' => false,
			    'spent_time' => isset($_POST['spent_time']) ? abs($_POST['spent_time']) : 0,
		));
		wp_redirect($link . '&confirm_reply#yast_comment_link');
		exit;
	    }
	}
	elseif (isset($_POST['post_status']) && in_array($_POST['post_status'], array('open','closed','trash'))) {
	    if (!isset($_POST['ticket_status']) || !wp_verify_nonce($_POST['ticket_status'], 'ticket_status')) {
		wp_die(__('Security error', 'yast'));
	    }
	    else {
		wp_update_post(array(
		    'ID' => $ticket->ID,
		    'post_status' => $_POST['post_status']
		));
		global $current_user;
		get_currentuserinfo();
		$message = __('Support Ticket changed', 'yast');
		if ($_POST['post_status'] == 'closed') {
		    $message = __('Support Ticket closed', 'yast');
		}
		elseif ($_POST['post_status'] == 'open') {
		    $message = __('Support Ticket re-opened', 'yast');
		}
		$this->reply(
			array(
			    'ticket' => $ticket,
			    'user_name' => $current_user->user_login,
			    'user_email' => $current_user->user_email,
			    'user_id' => $current_user->ID,
			    'message' => $message,
			    'confirm' => false,
			    'spent_time' => isset($_POST['spent_time']) ? abs($_POST['spent_time']) : 0,
			)
		);
		wp_redirect(esc_url($_REQUEST['referer']) . '&alert=' . $_POST['post_status']);
		exit;
	    }
	}
	wp_die(__('Missing content... What did you want to do ?', 'yast'));
	exit;
    }

    function create_post() {
	$this->create();
	exit;
    }

    /*
     * mailer
     * Fill headers values for mail sending
     */
    function mailer() {
	global $phpmailer;
	if (!empty($this->mailid)) {
	    $phpmailer->MessageID = '<BT' . $this->mailid . '@' . basename(get_site_option('siteurl')) . '>';
	}
	if (!empty($this->mailpriority)) {
	    $phpmailer->Priority = $this->mailpriority;
	}
	if (!empty($this->mailfrom)) {
	    $phpmailer->From = $this->mailfrom;
	}
	if (!empty($this->mailfromName)) {
	    $phpmailer->FromName = $this->mailfromName;
	}
    }

    /*
     * create
     * Creates a ticket from $_POST datas
     *
     */
    function create() {
	if (!isset($_POST['from']) || (!is_user_logged_in() && $_POST['from'] != 'shortcode')) {
	    if ($_POST['from'] == 'ajax') {
		echo json_encode(array(
		    'success' => false,
		    'message' => __("Capabilities error !", 'yast')
		));
	    }
	    else {
		wp_redirect(get_option('siteurl') . '?message=out');
	    }
	    exit;
	}
	if (is_multisite()){
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
		wp_redirect(get_option('siteurl') . '?message=in');
		exit;
		wp_redirect(esc_url($_POST['page_url']) . (strstr($_POST['page_url'], '?') ? '&' : '?') . 'message=ko');
	    }
	    exit;
	}
	else {

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
	    add_post_meta($ticket_id, 'reporter', esc_attr($_REQUEST['reporter']));
	    add_post_meta($ticket_id, 'visibility', esc_attr($_REQUEST['visibility']));


	    wp_set_object_terms($ticket_id, array(esc_attr($_REQUEST['type'])), 'ticket_type');

	    global $current_user;
	    get_currentuserinfo();

	    $level = $this->levels($_REQUEST['priority']);

	    $ticket = $this->get_ticket($ticket_id);

	    $conf = $this->options;

	    $dests = array($current_user->user_email);
	    $dests = array_merge($dests, $conf['alert_emails']);

	    $msg_mail = sprintf(__('[Support Ticket] New ticket report from %1$s (%2$s)

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

Issue link : %12$s', 'yast'),
		    $current_user->user_nicename,
		    $current_user->user_email,
		    $datas['post_title'],
		    sanitize_text_field($_REQUEST['type']),
		    $level['name'],
		    $datas['post_content'],
		    date_i18n(get_option('date_format').', '.get_option('time_format')),
		    esc_url($_REQUEST['page_url']),
		    wp_kses_post($_REQUEST['page_post']),
		    $this->human_nav_info($_REQUEST['navigator_userAgent']),
		    get_bloginfo('name'),
		    $ticket->lien
	    );

	    $confirm_message = __("Report succesfully registered !", 'yast');

	    $siteurl = basename(get_site_option('siteurl'));
	    $this->mailid = ($ticket->ID);
	    $this->mailpriority = (4 - $ticket->priority);
	    $this->mailfrom = 'noreply-yast@' . $siteurl;
	    $this->mailfromName = 'Ticket ' . $siteurl;

	    if (wp_mail(
			    $dests, sprintf(__('[Support Ticket] #%d', 'yast'), $ticket_id), $msg_mail
		    )) {
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
	if (is_multisite()){
	    restore_current_blog();
	}
	return 'Bad parameters';
    }

    /*
     * extract_fields
     * used by the form shortcode to split description into many fields
     *
     */
    function extract_fields($str) {
	while (strstr($str, '  ')) {
	    $str = str_replace('  ', ' ', $str);
	}
	$str = str_replace(array('&lt;', '&gt;'), array('<', '>'), $str);
	preg_match_all('#\<([a-z]+) ([a-zA-Z0-9\_]+)( \(.+\))?( ".+")?( \*)?\>#i', $str, $outs, PREG_PATTERN_ORDER);
	//print_r($outs);
	if (is_array($outs) && is_array($outs[0]) && sizeof($outs[0]) > 0) {
	    $rep = array();
	    foreach ($outs[0] as $o => $pattern) {
		$rep[$o]['pattern'] = str_replace(array('<', '>'), array('&lt;', '&gt;'), $pattern);
		$rep[$o]['type'] = $outs[1][$o];
		$rep[$o]['name'] = $outs[2][$o];
		$label = substr(trim($outs[3][$o]), 1, -1);
		$rep[$o]['label'] = !empty($label) ? $label : '';
		$values = substr(trim($outs[4][$o]), 1, -1);
		$rep[$o]['values'] = in_array($outs[1][$o], array('radio', 'checkbox', 'select')) ? explode(',', $values) : $values;
		$rep[$o]['required'] = $outs[5][$o] == '*' ? true : false;
	    }
	    return $rep;
	}
	return false;
    }

    function footer() {
	add_thickbox();
	?>
	<div id="report_ticketbox">
	<?php echo $this->form(array('class' => 'in_thickbox')) ?>
	</div>
	<?php
    }

    /*
     * form
     * Display a submit form for users to add a ticket
     */
    function form($atts = NULL, $content = NULL) {
	$conf = $this->options;

	$atts = shortcode_atts(array(
	    'type' => $conf['default_type'],
	    'only_known' => true,
	    'class' => '',
	    'force_ssl' => $conf['force_ssl'],
	    'title' => __('Hi %s! Do you want to open a ticket about this page ?', 'yast')
		), $atts);

	if ($atts['only_known'] === true && !is_user_logged_in()){
	    return;
	}
	if (is_multisite()){
	    switch_to_blog($this->options['support_site']);
	}
	$types = $this->default_types();
	if (is_multisite()){
	    restore_current_blog();
	}
	$foradmin = (!isset($_GET['page']) || $_GET['page'] != 'yast_list') ? false : true;

	global $current_user;
	get_currentuserinfo();
	$currentUrl = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
	$admin_url = admin_url();
	if ($atts['force_ssl'] == true) {
	    $admin_url = str_replace('http://', 'https://', $admin_url);
	}
	$ret = '';

	if (isset($_REQUEST['form_message'])) {
	    if ($_REQUEST['form_message'] == 'ok') {
		$ret.='<div class="yast_alert">' . __('Report succesfully registered !', 'yast') . '</div>';
	    }
	}
	$ret.='
	  <form method="POST" action="' . $admin_url . 'admin-post.php?action=yastform" id="report_ticketform"  class="yast_reportform ' . $atts['class'] . '">'
		. '<input type="hidden" name="action" value="yastform" />
	  	   <input type="hidden" name="from" value="shortcode" />';
	if (!$foradmin) {
	    $ret.=(!empty($atts['title']) ? '<h3>' . sprintf($atts['title'], $current_user->display_name) . '</h3>' : '') . ''
		    . '<input type="hidden" name="reporter" id="ticketrep_reporter" value="' . $this->current_reporter() . '" />';
	}
	else {
	    wp_enqueue_script('user-suggest', admin_url('/js/user-suggest.min.js'), array('jquery'), false, true);
	    $ret.='<label>' . __('From:', 'yast') . ' <input type="text" name="reporter" id="ticketrep_reporter" class="wp-suggest-user" value="' . $this->current_reporter() . '" /></label>';
	    //<input name="assigned" data-id="<?php echo  $ticket->ID " type="text" id="yast_assigned" value="<?php echo  $ticket->assigned->user_login " />
	}
	$ret.='<p><label>
	  	' . __('Priority:', 'yast') . '
		<select name="priority" id="ticketrep_priority">';
	$levels = $this->levels();
	foreach ($levels as $id => $level) {
	    $ret.=' <option value="' . $id . '" style="color:' . $level['color'] . '">' . $level['name'] . '</option>';
	}
	$ret.='</select>
		</label>

		<label>
	  	' . __('Report type:', 'yast') . '
		<select name="type" id="ticketrep_type">';
	foreach ($types as $type) {
	    if ($foradmin || empty($atts['type']) || $atts['type'] == $type->slug) {
		if (sizeof($type->children) > 0) {
		    $ret.='<optgroup label="' . $type->name . '">';
		    foreach ($type->children as $child) {
			$ret.='<option value="' . $child->slug . '">' . $child->name . '</option>';
		    }
		    $ret.='</optgroup>';
		}
		else {
		    $ret.='<option value="' . $type->slug . '">' . $type->name . '</option>';
		}
	    }
	}
	$ret.='</select>
	  	</label>

	  	<label>
	 	' . __('Visibility:', 'yast') . '
		<select name="visibility" id="ticketrep_visibility">
		 	<option value="public">' . __('Public', 'yast') . '</option>
		 	<option value="private">' . __('Private', 'yast') . '</option>
		</select>
	  	</label>
	  </p>
	  <h3>' . __('Why do you  open a ticket ?', 'yast') . '</h3>
	  <p><label>' . __('Title:', 'yast') . ' <input type="text" name="title" id="ticketrep_title" size="60"></label></p>';
	if ($content === NULL || (false === $contents = $this->extract_fields($content))) {
	    $ret.='<p><label>
		  		' . __('Description:', 'yast') . '
		  		<textarea name="description" id="ticketrep_description" cols="50" rows="7"></textarea>
		  	</label></p>';
	}
	else {

	    foreach ($contents as $field) {
		$html_field = '';
		if ($field['type'] == 'select') {
		    $html_field .= '<label>' . $field['label'] . ' <select name="description[' . $field['name'] . ']" ' . ($field['required'] ? 'required' : '') . '>';
		    foreach ($field['values'] as $value) {
			$html_field .= '<option value="' . $value . '">' . $value . '</option>';
		    }
		    $html_field .= '</select></label>';
		}
		elseif ($field['type'] == 'radio' || $field['type'] == 'checkbox') {
		    $html_field.='<fieldset><legend>' . $field['label'] . '</legend>';
		    foreach ($field['values'] as $value) {
			$html_field .= '<label><input type="' . $field['type'] . '" name="description[' . $field['name'] . ']' . ($field['type'] == 'checkbox' ? '[' . $value . ']' : '') . '" ' . ($field['required'] ? 'required' : '') . ' ';
			$html_field .= ' value="' . $value . '">';
			$html_field .= ' ' . $value . '</label> ';
		    }
		    $html_field .= '</fieldset>';
		}
		elseif ($field['type'] == 'textarea') {
		    $html_field .= '<label>' . $field['label'] . ' <textarea name="description[' . $field['name'] . ']" ' . ($field['required'] ? 'required' : '') . '>' . $field['values'] . '</textarea></label>';
		}
		else {
		    $html_field .= '<label>' . $field['label'] . ' <input type="' . $field['type'] . '" name="description[' . $field['name'] . ']" value="' . $field['values'] . '" ' . ($field['required'] ? 'required' : '') . '></label>';
		}
		$html_field .= '';
		$content = str_replace($field['pattern'], $html_field, $content);
	    }
	    $ret.=$content;
	}
	$ret.='
		<div id="yast_techsub">
	   <h3>' . __('Technical details:', 'yast') . '</h3>
	   <p>' . __('These informations will be sent with your report:', 'yast') . '</p>
	   </div>
	   <div id="yast_techsubinfos">
	  <p>
	  	<label>
	  		' . __('Page URL:', 'yast') . '
	  		<input type="text" name="page_url" id="ticketrep_page_url" size="60" value="http://' . $currentUrl . '" ' . ($foradmin ? '' : 'readonly') . '>
	  	</label>
	  </p>
	  <p>
	  	<label>
	  		' . __('Variables:', 'yast') . '
	  		<textarea name="page_post" id="ticketrep_page_post" cols="60" rows="5"  ' . ($foradmin ? '' : 'readonly') . '>' . esc_attr(stripslashes(serialize($_POST))) . '</textarea>
	  	</label>
	  </p>
	  <p>
	  	<label>
	  		' . __('Navigator:', 'yast') . '
	  		<input type="text" name="navigator_userAgent" id="ticketrep_navigator" size="60" value="' . esc_attr($_SERVER['HTTP_USER_AGENT']) . '"  ' . ($foradmin ? '' : 'readonly') . '>
	  	</label>
	  </p>
	 </div>
		<p>
			<input type="submit" value="' . __('Submit', 'yast') . '" class="button button-primary button-large">
		</p>

		</form>';
	return $ret;
    }

    function bar($admin_bar) {
	if (is_multisite()){
	    switch_to_blog($this->options['support_site']);
	}
	$query_arg = array(
	    'post_type' => 'yast',
	    'post_status' => 'open,publish',
	    'posts_per_page' => '-1'
	);

	$tickets = new WP_query($query_arg);

	if (is_multisite()){
	    restore_current_blog();
	}
	$admin_bar->add_menu(array(
	    'id' => 'yast_support_tickets',
	    'title' => '<span class="ab-icon"></span> <span class="ab-label">' . $tickets->post_count . '</span>',
	    'href' => admin_url() . 'admin.php?page=yast_list',
	    'meta' => array(
		'title' => __('Support Tickets', 'yast'),
	    ),
	));
	//Exclude declaration button from list page
	if (is_super_admin() || !isset($_GET['page']) || $_GET['page'] != 'yast_list') {
	    $admin_bar->add_menu(array(
		'id' => 'ticket_report',
		'title' => '<span class="ab-icon"></span> <span class="ab-label">' . __('Support !', 'yast') . '</span>',
		'href' => '#TB_inline?width=600&height=550&inlineId=report_ticketbox',
		'meta' => array(
		    'title' => __('Contact technical support', 'yast'),
		    //'onclick' => 'openbox("Contacter le support", 1)',
		    'html' => '<a href="#TB_inline?width=600&height=400&inlineId=report_ticketbox" class="thickbox"><span class="ab-icon"></span> <span class="ab-label">' . __('Support !', 'yast') . '</span></a>',
		),
	    ));
	}
    }
}