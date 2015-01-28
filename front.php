<?php

$_YAST_Front = new YAST_front();

class YAST_front{
/*
 * FRONT FUNCTIONS
 *
 */
    function YAST_front(){
	add_action( 'wp', array(&$this, 'front_request'),200 );
	add_filter( 'archive_template', array(&$this,'get_custom_archive_template' ));
	add_filter( 'single_template', array(&$this,'get_custom_single_template' ));
    }
/*
 * front_request
 * Check if the current request is about a YAST's ticket and prevent from 404 error
 */
function front_request($wp) {
    if (!isset($wp->query_vars['post_type']) || $wp->query_vars['post_type'] != 'yast') {
	return;
    }
    global $wp_query, $wpdb, $_YAST;
    $ticket = $wpdb->get_col($wp_query->request);
    if (!count($ticket)) {
	return;
    }

    $wp_query->is_404 = false;
    $wp_query->post_count = 1;
    $wp_query->is_single = 1;
    $wp_query->is_singular = 1;
    $wp_query->posts[0] = $_YAST->get_ticket($ticket[0]);
    $wp_query->queried_object = $wp_query->posts[0];

    wp_enqueue_style('yast-front', plugins_url('/css/front.css', __FILE__), false, null);

}

function locate_plugin_template($template_names, $load = false, $require_once = true) {
    if (!is_array($template_names))
	return '';
    $located = '';
    $this_plugin_dir = WP_PLUGIN_DIR . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__));
    foreach ($template_names as $template_name) {
	if (!$template_name)
	    continue;
	if (file_exists(STYLESHEETPATH . '/' . $template_name)) {
	    $located = STYLESHEETPATH . '/' . $template_name;
	    break;
	}
	else if (file_exists(TEMPLATEPATH . '/' . $template_name)) {
	    $located = TEMPLATEPATH . '/' . $template_name;
	    break;
	}
	else if (file_exists($this_plugin_dir . $template_name)) {
	    $located = $this_plugin_dir . $template_name;
	    break;
	}
    }
    if ($load && '' != $located)
	load_template($located, $require_once);
    return $located;
}

function get_custom_archive_template($template) {
    global $wp_query;
    if ($wp_query->get_queried_object()->query_var == 'yast') {
	$templates = array('archive-yast.php', 'archive.php');
	$template = $this->locate_plugin_template($templates);
    }
    return $template;
}

function get_custom_single_template($template) {
    global $wp_query;
    $object = $wp_query->get_queried_object();
    if ('yast' == $object->post_type) {
	$templates = array('single-' . $object->post_type . '.php', 'single.php');
	$template = $this->locate_plugin_template($templates);
    }
    return $template;
}
}