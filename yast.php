<?php
/*
  Plugin Name: YAST : Yet Another Support Tool
  Plugin URI: http://ecolosites.eelv.fr/yast/
  Description: Support Tickets management, throw classic site, multisite plateform or external server
  Version: 1.3.1
  Author: bastho, n4thaniel, ecolosites
  Author URI: http://ecolosites.eelv.fr/
  License: GPLv2
  Text Domain: yast
  Domain Path: /languages/
  Tags: support, ticket, support tickets, helpdesk, multisite, help-desk
  Icons: from http://icomoon.io, under GPL/ CC BY 3.0 licences
 */

global $YAST_tools,$YAST_front;
$_YAST = new YAST_class();

class YAST_class {

    public $types;
    public $mailid;
    public $mailpriority;
    public $mailfrom;
    public $mailfromName;
    public $options;
    public $options_params;
    public $cache;
    public $plugin_url;

    /*
     * Initialize plugin
     *
     */
    function YAST_class() {
	load_plugin_textdomain('yast', false, 'yast-yet-another-support-tool/languages');
	$this->mailid = '';
	$this->mailpriority = '';
	$this->mailfrom = '';
	$this->mailfromName = '';
	$this->options_params=$this->get_conf_params();
	$this->options = $this->get_conf();
	$this->cache=array();
	$this->plugin_url = plugins_url('', __FILE__);

	include_once (plugin_dir_path(__FILE__) . 'inc/hooks.php');

	// Front
	include_once (plugin_dir_path(__FILE__) . 'front.php');
    }

    /*
     * Register post-types and taxonomies
     */
    function init() {
	include_once (plugin_dir_path(__FILE__) . 'tools.php');
	include_once (plugin_dir_path(__FILE__) . 'inc/register.php');


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

    /*
     * Output notices in header
     */
    function head() {
	if (isset($_GET['alert'])) {
	    if ($_GET['alert'] == 'open') {
		echo'<div class="updated"><p>' . __('Ticket has been re-open', 'yast') . '</p></div>';
	    }
	    if ($_GET['alert'] == 'closed') {
		echo'<div class="updated"><p>' . __('Ticket has been closed', 'yast') . '</p></div>';
	    }
	    if ($_GET['alert'] == 'trash') {
		echo'<div class="updated"><p>' . __('Ticket has been moved to trash', 'yast') . '</p></div>';
	    }
	}
    }

    /*
     * Menu
     * Add items in the admin menu
     */
    function menu() {
	add_menu_page(__('Support Tickets', 'yast'), __('Support Tickets', 'yast'), 'manage_options', 'yast_list', array(&$this, 'liste'));
	add_submenu_page('yast_list', __('Ticket types', 'yast'), __('Ticket types', 'yast'), 'manage_options', 'edit-tags.php?taxonomy=ticket_type');
	add_submenu_page('yast_list', __('Options', 'yast'), __('Options', 'yast'), 'manage_options', 'yast_options', array(&$this, 'options'));
    }

    /*
     * human_spent_time
     * Outputs spent time readably for humans
     *
     * @param $minutes
     * @param $format
     *
     * @return string
     */
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

    /*
     * total_spent_time
     * Calculate total time spent on a ticket
     *
     * @param $ticket_id
     *
     * @return int (minutes)
     */
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

    /*
     * recursive_sanitize
     * applies sanitize_text_field on each items of an array
     *
     * @param array $array
     *
     * @return array
     */
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
    /*
     * comment_endcallback
     * called for each comment
     *
     * @param $comment
     */
    function comment_endcallback($comment) {
	if (!is_super_admin()){
	    return;
	}
	$spent_time = get_comment_meta($comment->comment_ID, 'spent_time', true);
	echo'<span class="dashicons dashicons-backup"></span>';
	if ($spent_time) {
	    echo '<span class="yast_spet-time">' . sprintf(__('%s spent on this.', 'yast'), $this->human_spent_time($spent_time), 'tiny') . '</span>';
	}
	else {
	    echo '<span class="yast_spet-time">' . __('No time lost', 'yast') . '</span>';
	}
    }
    /*
     * single
     * displays a ticket
     *
     * @require $_GET['ticket']
     *
     */
    function single() {
	include (plugin_dir_path(__FILE__) . 'inc/view.single.php');
    }
    function single_action_button($ticket_id,$status,$label,$nonce='ticket_status'){
	$ticket=$this->get_ticket($ticket_id);
	if(!$this->can_edit($ticket)){
	    return;
	}
	$style='primary';
	if($status=='trash'){
	    $style='warning';
	}
	if($status=='closed'){
	    $style='danger';
	}
	?>
	    <form action="<?php echo admin_url() ?>admin-post.php?token=<?php echo $ticket->token ?>" method="post" id="<?php echo $status?>" class="yast_<?php echo $status?>">
		<?php wp_nonce_field($nonce, $nonce); ?>
		<input type="hidden" name="action" value="yastupdate">
		<input type="hidden" name="_wp_http_referer" value="<?php echo (\filter_input(INPUT_GET,'_wp_http_referer',FILTER_SANITIZE_URL)?:'') ?>">
		<input type="hidden" name="ticket" value="<?php echo $ticket_id ?>">
		<input type="hidden" name="post_status" value="<?php echo $status?>">
		<input type="submit" value="<?php echo $label ?>" class="button button-<?php echo $style ?> btn btn-<?php echo $style ?>">
	    </form>
	<?php
    }

    /*
     * can_view
     * checks if the given user can view the given ticket
     *
     * @param object $ticket
     * @param int $user_id
     *
     * @return boolean
     */
    function can_view($ticket,$user_id=false){
	// ok, are we dealing with a ticket ?
	if(!is_object($ticket) || !isset($ticket->navigator)){
	    return false;
	}
	$user_ID = $user_id?:$user_id = get_current_user_id();
	// If user is super admin, yes he can
	if(is_super_admin( $user_ID )){
	    return true;
	}
	// If the ticket is public, ok
	if($ticket->visibility=='public'){
	    return true;
	}
	// If the user is the reporter of the ticket, ok
	$user = get_user_by('id',$user_ID);
	if($ticket->reporter==$user->user_login){
	    return true;
	}
	// If the token is given, ok
	if(\filter_input(INPUT_GET,'token',FILTER_SANITIZE_STRING)==$ticket->token){
	    return true;
	}
	// In any other case, no, he can't
	return false;
    }
    /*
     * can_edit
     * checks if the given user can edit the given ticket
     *
     * @param object $ticket
     * @param int $user_id
     *
     * @return boolean
     */
    function can_edit($ticket,$user_id=false){
	// ok, are we dealing with a ticket ?
	if(!is_object($ticket) || !isset($ticket->navigator)){
	    return false;
	}
	$user_ID = $user_id?:$user_id = get_current_user_id();

	// If you can't view, you can't edit
	if(!$this->can_view($ticket,$user_ID)){
	    return false;
	}
	// If user is super admin, yes he can
	if(is_super_admin( $user_ID )){
	    return true;
	}
	// If the user is the reporter of the ticket, ok
	$user = get_user_by('id',$user_ID);
	if($ticket->reporter==$user->user_login){
	    return true;
	}
	// If the token is given, ok
	if(\filter_input(INPUT_GET,'token',FILTER_SANITIZE_STRING)==$ticket->token){
	    return true;
	}
	// In any other case, no, he can't
	return false;
    }
    /*
     * query
     * generate a ticket list query arguments
     * to use in WP_Query()
     *
     * @param int $pages
     * @param string $status
     * @param string orderby
     *
     * @return array
     *
     */
    function query($pages = 20, $status = 'open,publish', $orderby = null) {
	if (is_multisite()){
	    switch_to_blog($this->options['support_site']);
	}
	$paged = ( \filter_input(INPUT_GET,'paged',FILTER_SANITIZE_NUMBER_INT) && is_numeric($_GET['paged']) ) ? $_GET['paged'] : 1;
	if($post_status = \filter_input(INPUT_GET,'post_status',FILTER_SANITIZE_STRING)){
	    if (in_array($post_status, array('open','closed','trash','all'))){
		$status = $post_status;
		if($status=='open'){
		    $status.=',publish';
		}
	    }
	}
	$type = \filter_input(INPUT_GET, 'ticket_type');
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
	    'orderby' => $orderby,
	    'ticket_type'=>$type
	);
	if (is_multisite()){
	    restore_current_blog();
	}
	return $query_arg;
    }
    function liste_paginate($tickets,$echo=true){
	$paged = ( isset($_GET['paged'])&& is_numeric($_GET['paged']) ) ? $_GET['paged'] : 1;
	$big = 999999999; // need an unlikely integer
	$page_arg = array(
	    'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
	    'current' => $paged,
	    'total' => $tickets->max_num_pages
	);
	$pages = str_replace('#038;','&',paginate_links($page_arg));
	if($echo){
	    echo $pages;
	}
	return $pages;
    }
    /*
     * liste
     * outputs tickets list
     */
    function liste() {
	include (plugin_dir_path(__FILE__) . 'inc/view.list.php');
    }
    /*
     * levels
     * returns levels
     *
     * if
     * @param string $level (optional)
     * is given
     * @return the given level
     *
     * @return list of levels
     */
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
    /*
     * scripts_front
     * add scripts in front
     */
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
	    'ajaxurl' => $ajaxurl
	));
    }
    /*
     * scripts_admin
     * add scripts in admin
     */
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
	    ),
	    'lng_search_results_title' => __('These tickets have already been opened. Maybe can you find an answer to your problem ?','yast'),
	    'lng_statuses' => array(
		'open'=>__('Open','yast'),
		'closed'=>__('Closed','yast')
	    ),
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
		<a href="admin.php?page=yast_list&ticket=<?php echo $post->ID?>&_wp_http_referer=<?php echo urlencode(wp_get_referer()) ?>"><?php _e('Go back to ticket', 'yast') ?></a>
		</p>
	    </div>
	    <?php
	}
    }
    /*
     * add_custom_box
     */
    function add_custom_box() {
	#add_meta_box('ticket_content', __('Description', 'yast'), array(&$this, 'custom_boxinfo'), 'yast', 'normal', 'high');
	add_meta_box('ticket_tech', __('Technical details', 'yast'), array(&$this, 'custom_boxtech'), 'yast', 'normal', 'high');
	add_meta_box('custom_boxauthor', __('Reporter', 'yast'), array(&$this, 'custom_boxauthor'), 'yast', 'normal', 'high');
    }

    /*
     * human_nav_info
     * return a string, readable for humans, of the given navigator
     *
     * @param user_agent $nav
     *
     * @return string
     */
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

    /*
     * label
     * wrap string into a label, optionnaly add an icon
     *
     * @param string $str
     * @param string $icon
     * @param string $class
     * @param string $style
     * @param string $tag
     * @param string $iconpack
     *
     * @return html text
     */
    function label($str = '', $icon = 'admin-posts', $class = '', $style = '', $tag = 'li', $iconpack = 'dashicons') {
	return '<' . $tag . ($style != '' ? ' style="' . $style . '"' : ' ') . ($class != '' ? ' class="' . $class . '"' : ' ') . '>
                    <span class="' . $iconpack . ' ' . $iconpack . '-' . $icon . '"></span>
                    ' . $str . '
                </' . $tag . '>';
    }

    /*
     * custom_boxauthor
     * outputs an autocompleter fields to pick an author
     *
     * @param object $ticket
     */
    function custom_boxauthor($ticket = false){
	if (!$ticket || !isset($ticket->assignedto)){
	    $ticket = $this->get_ticket(isset($_GET['post']) && is_numeric($_GET['post']) ? $_GET['post']: NULL);
	}
	wp_enqueue_script('user-suggest', admin_url('/js/user-suggest.min.js'), array('jquery'), false, true);
	?>
	<input name="yast_reporter" type="text" id="yast_reporter" class="wp-suggest-user widefat" value="<?php echo  $ticket->reporter ?>" />

	<?php
    }
    /*
     * custom_boxinfo
     * outputs tickets infos
     *
     * @param object $ticket
     */
    function custom_boxinfo($ticket = false) {
	include (plugin_dir_path(__FILE__) . 'inc/view.box.info.php');
    }

    /*
     * custom_boxprocess
     * outputs tickets actions
     *
     * @param object $ticket
     */
    function custom_boxprocess($ticket = false) {
	include (plugin_dir_path(__FILE__) . 'inc/view.box.process.php');
    }

    function custom_boxtech($ticket = false) {
	include (plugin_dir_path(__FILE__) . 'inc/view.box.tech.php');
    }
    /*
     * select
     * output tickets selector
     * TODO: replace by ajax autocompleter
     */
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

    /*
     * post_options
     * saves options
     */
    function post_options() {
	if (!current_user_can('manage_options')){
	    return;
	}
	if (!isset($_POST['ticket_set_options']) || !wp_verify_nonce($_POST['ticket_set_options'], 'ticket_set_options')) {
	    wp_die(__('Security error', 'yast'));
	}

	$valid_post = array(
	    'yast_options'=>array(
		'filter' => FILTER_SANITIZE_STRING,
		'flags'  => FILTER_REQUIRE_ARRAY
	    )
	);

	foreach ($this->options_params as $item_name=>$item_attr){
	    $valid_post['yast_options'][$item_name] = isset($item_attr['filter']) ? $item_attr['filter'] : FILTER_SANITIZE_STRING;
	}

	if (false !== $base_options = \filter_input_array(INPUT_POST,$valid_post)) {
	    $yast_options = $_POST['yast_options'];
	    $yast_options['alert_emails'] = explode(',', $yast_options['alert_emails']);
	    $yast_options['trusted_hosts'] = explode("\n", $yast_options['trusted_hosts']);

	    update_site_option('yast_options', $yast_options);
	    wp_redirect('admin.php?page=yast_options&confirm=options_saved');
	    exit;
	}
    }
    /*
     * options
     * outputs settings page
     */
    function options() {
	include (plugin_dir_path(__FILE__) . 'inc/view.options.php');
    }
    /*     * *************** CORE FUNCTIONS * */

    /*
     * get_conf
     * @return array of YAST configuration
     */
    function get_conf() {
	$reg_options = get_site_option('yast_options', array());

	// Get default values
	$default = array();
	foreach ($this->options_params as $item_name=>$item_attr){
	    $default[$item_name] = $item_attr['default'];
	}
	$options = shortcode_atts($default, $reg_options);
	if ($options['local_token'] == '') {
	    $options['local_token'] = sha1(time().rand(0,128));
	    update_site_option('yast_options', $options);
	}
	if ($options['crypt_IV'] == '' && $this->crypt_support()) {
	    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	    $options['crypt_IV'] = base64_encode(mcrypt_create_iv($iv_size, MCRYPT_RAND));
	    update_site_option('yast_options', $options);
	}

	$options['crypt_IV'] =base64_decode($options['crypt_IV']);
	return $options;
    }
    /*
     * get_conf_params
     * returns array of options used by YAST with default value and valid filter
     *
     * @filter yast_conf_params
     */
    function get_conf_params() {
	return include (plugin_dir_path(__FILE__) . 'inc/get_conf_param.php');
    }

    /*
     * get_ticket
     * populate a ticket with all usefull datas
     *
     * @param int $post_id
     *
     * @return object
     */
    function get_ticket($post_id = null) {
	if(isset($this->cache[$post_id])){
	    return $this->cache[$post_id];
	}
	if (is_multisite()){
	    switch_to_blog($this->options['support_site']);
	}
	$post = get_post($post_id);
	if (is_object($post)) {
	    $post->navigator = get_post_meta($post->ID, 'navigator', true);
	    $post->page = get_post_meta($post->ID, 'page', true);
	    if(!is_array($post->page)){
		$post->page=array('url'=>'','post'=>'');
	    }
	    $post->priority = (int) get_post_meta($post->ID, 'priority', true);
	    $post->reporter = get_post_meta($post->ID, 'reporter', true);
	    $post->assignedto = get_post_meta($post->ID, 'assigned', true);
	    $post->assigned = $this->assigned($post->ID);
	    $post->visibility = get_post_meta($post->ID, 'visibility', true);
	    if (empty($post->visibility)){
		$post->visibility = 'public';
	    }
	    $post->server_ticket = get_post_meta($post->ID, 'server_ticket', true);
	    $post->spent_time = get_post_meta($post->ID, 'spent_time', true);
	    $post->token = get_post_meta($post->ID, 'token', true);
	    if (empty($post->token)){
		$post->token = sha1($post->ID.time().rand(0,128));
		update_post_meta($post->ID, 'token', $post->token);
	    }
	    $post->front_link = add_query_arg(array('token'=>$post->token),$post->guid);
	    $post->lien = is_user_logged_in() ? admin_url() . 'admin.php?page=yast_list&ticket=' . $post->ID . '&_wp_http_referer=admin.php%3Fpage%3Dyast_list' : $post->guid;

	    $types_to_display=array();
	    $types = get_the_terms($post->ID, 'ticket_type');
	    if ($types){
		foreach ($types as $type) {
		    $types_to_display[]=(!empty($type->parent)?get_term($type->parent,'ticket_type')->name.'/':'').$type->name;
		}
	    }
	    $post->type_display = implode(', ',$types_to_display);

	    $this->cache[$post_id] = $post;
	    return $post;
	}
	if (is_multisite()){
	    restore_current_blog();
	}
	return false;
    }
    /*
     * merge
     * merges 2 tickets, set one ticket child of another one.
     *
     * @require $_POST['ticket_id']
     * @require $_POST['post_parent']
     */
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

    /*
     * assign
     * assigns an user to a ticket
     *
     * @require $_REQUEST['ticket_id']
     * @require $_REQUEST['assign'] (string, username)
     */
    function assign() {
	include (plugin_dir_path(__FILE__) . 'inc/assign.php');
    }
    /*
     * assigned
     * returns the assigned user to a given ticket
     *
     * @param int $ticket_id
     *
     * @return WP_User object
     */
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
    /*
     * display_user
     * outputs username, optionnaly wrapped in html tag
     *
     * @param WP_USer object $user
     * @param bool $html
     */
    function display_user($user, $html = true) {
	if(empty($user)){
	    return __('Anonymous user','yast');
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
    /*
     * current_reporter
     * retruns current user name
     */
    function current_reporter() {
	global $current_user;
	get_currentuserinfo();
	return $current_user->user_login; // . '@' . basename(get_site_option('siteurl'));
    }
    /*
     * is_server_request
     * will be used for reamote queries
     */
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
	if (is_string($ticket->reporter) && filter_var($ticket->reporter, FILTER_VALIDATE_EMAIL)) {
	    $dests[$ticket->reporter] = $ticket->reporter;
	}
	// Notify current user
	global $current_user;
	get_currentuserinfo();
	$dests[$current_user->user_email]=$current_user->user_email;
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
	// Notify custom admin
	$dests = array_merge($dests,$this->options['alert_emails']);

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
	include (plugin_dir_path(__FILE__) . 'inc/reply.php');
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
	if (!isset($_POST['ticket']) || !is_numeric($_POST['ticket'])) {
	    wp_die(__('Missing ticket ID', 'yast'));
	}
	if (is_multisite()){
	    switch_to_blog($this->options['support_site']);
	}
	$ticket = $this->get_ticket($_POST['ticket']);
	if(!$this->can_edit($ticket)){
	    wp_die(__('What are you doing here ?', 'yast'));
	}
	$redirect_args = array(
		    'confirm_reply'=>true,
		    '_wp_http_referer'=>urlencode($_REQUEST['_wp_http_referer'])
		);
	$link = false;
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
		$link = add_query_arg($redirect_args,$_SERVER['HTTP_REFERER']);
	    }
	}
	if (isset($_POST['post_status']) && in_array($_POST['post_status'], array('open','closed','trash'))) {
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
		$redirect_args['alert']=$_POST['post_status'];
		$link = add_query_arg($redirect_args,$_SERVER['HTTP_REFERER']);
	    }
	}
	if($link){
	    wp_redirect($link . '#yast_comment_link');
	    exit;
	}
	wp_die(__('Missing content... What did you want to do ?', 'yast'));
	exit;
    }
    // refetch ticket count because orignal WP function just takes care about publish ones
    // https://core.trac.wordpress.org/browser/tags/4.1/src/wp-includes/taxonomy.php#L3943
    function _update_post_term_count( $terms, $from='term_taxonomy_id' ) {
	global $wpdb;
	$terms = (array) $terms;
	foreach ($terms as $term) {
	    $full_term = get_term_by($from,$term,'ticket_type');
	    if(!$full_term || $full_term->taxonomy!='ticket_type'){
		continue;
	    }
	    $query_arg = array(
		'post_type' => 'yast',
		'post_status' => 'open,publish',
		'posts_per_page' => '-1',
		'ticket_type' => $full_term->slug
	    );
	    $tickets = new WP_query($query_arg);
	    $count = $tickets->post_count;
	    $wpdb->update($wpdb->term_taxonomy, compact('count'), array('term_taxonomy_id' => $full_term->term_taxonomy_id));
	    if($full_term->parent && !in_array($full_term->parent,$terms)){
		$this->_update_post_term_count($full_term->parent,'id');
	    }
	}
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

    function bad_redirect(){
	if (\filter_input(INPUT_POST,'from') == 'ajax') {
	    echo json_encode(array(
		'success' => false,
		'message' => __("Capabilities error !", 'yast')
	    ));
	}
	else {
	    wp_redirect(site_url() . '?message=out');
	}
	exit;
    }
    /*
     * create
     * Creates a ticket from $_POST datas
     *
     */
    function create() {
	return include (plugin_dir_path(__FILE__) . 'inc/create.php');
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

    function search(){
	$q = esc_attr(\filter_input(INPUT_POST, 'q'));
	$type = esc_attr(\filter_input(INPUT_POST, 'type'));
	if (is_multisite()){
	    switch_to_blog($this->options['support_site']);
	}
	$query_arg = array(
	    'post_type' => 'yast',
	    'post_status' => 'open,publish,closed',
	    'posts_per_page' => '-1',
	    //'ticket_type' => $type,
	    's'=>$q
	);
	if(!is_super_admin()){
	    $query_arg['meta_key']='visibility';
	    $query_arg['meta_value']='public';
	}
	$result = new WP_query($query_arg);
	if (is_multisite()){
	    restore_current_blog();
	}
	$tickets=array();
	foreach($result->posts as $post){
	    $tickets[]=$this->get_ticket($post);
	}
	return($tickets);
    }
    function search_ajax(){
	wp_send_json($this->search());
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
	return include (plugin_dir_path(__FILE__) . 'inc/form.php');
    }
    /*
     * form_js
     * generate Javascript file fr use in a distant website
     * to call like this :
     * http://yourblog.com/wp-admin/admin-ajax.php?action=yast_form_js
     *
     * usefuill GET params :
     * @param type
     * @param username
     * @param visibility
     */
    function form_js(){
	include (plugin_dir_path(__FILE__) . 'inc/form-js.php');
    }



    /*
     * bar
     * add items in the admin bar
     */
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
	if(is_super_admin()){
	    $types = $this->default_types();
	    foreach ($types as $type){
		$args = array(
		    'id'     => 'yast_support_tickets-'.$type->slug,
		    'title'  => sprintf('%s (%s)',$type->name,$type->count),
		    'parent' => 'yast_support_tickets',
		    'href' => admin_url() . 'admin.php?page=yast_list&ticket_type='.$type->slug,
		);
		$admin_bar->add_node( $args );
	    }
	}
	//Exclude declaration button from list page
	if (is_super_admin() || !isset($_GET['page']) || $_GET['page'] != 'yast_list') {
	    $admin_bar->add_menu(array(
		'id' => 'ticket_report',
		'title' => '<span class="ab-icon dashicons dashicons-sos"></span> <span class="ab-label">' . __('Support !', 'yast') . '</span>',
		'href' => '#TB_inline?width=600&height=550&inlineId=report_ticketbox',
		'meta' => array(
		    'title' => __('Contact technical support', 'yast'),
		    //'onclick' => 'openbox("Contacter le support", 1)',
		    'html' => '<a href="#TB_inline?width=600&height=400&inlineId=report_ticketbox" class="thickbox"><span class="ab-icon dashicons dashicons-sos"></span> <span class="ab-label">' . __('Support !', 'yast') . '</span></a>',
		),
	    ));
	}
    }
    /*
     * Crypt
     * detects if encrypt/decrypt is supported
     * encrypt and decrypts strings
     *
     */
    function crypt_support(){
	return function_exists('mcrypt_encrypt') && function_exists('mcrypt_decrypt') && function_exists('pack') && function_exists('mcrypt_get_iv_size') && function_exists('mcrypt_create_iv');
    }
    function encrypt($string){
	if(!$this->crypt_support()){
	    return $string;
	}
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	$key = substr(pack('H*', $this->options['local_token']),0,$iv_size);


	$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key,
				     $string, MCRYPT_MODE_CBC, $this->options['crypt_IV']);

	$ciphertext = $this->options['crypt_IV'] . $ciphertext;
	return base64_encode($ciphertext);
    }
    function decrypt($hash){
	if(!$this->crypt_support()){
	    return $hash;
	}
	$decoded_hash = base64_decode($hash);

	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);

	$iv_dec = substr($decoded_hash, 0, $iv_size);
	$ciphertext_dec = substr($decoded_hash, $iv_size);

	$key = substr(pack('H*', $this->options['local_token']),0,$iv_size);

	return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key,
					$ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec));
    }
}