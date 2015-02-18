<?php

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
add_action('admin_post_nopriv_yastupdate', array(&$this, 'update'));
add_action('admin_post_yastform', array(&$this, 'create_post'));
add_action('admin_post_nopriv_yastform', array(&$this, 'create_post'));

//AJAX
add_action('wp_ajax_yastselect', array(&$this, 'select'));
add_action('wp_ajax_yastassign', array(&$this, 'assign'));
add_action('wp_ajax_yastmerge', array(&$this, 'merge'));
add_action('wp_ajax_yastpost', array(&$this, 'create'));
add_action('wp_ajax_nopriv_yastpost', array(&$this, 'create'));
add_action('wp_ajax_yastsearch', array(&$this, 'search_ajax'));
add_action('wp_ajax_nopriv_yastsearch', array(&$this, 'search_ajax'));

add_action('wp_ajax_yast_form_js', array(&$this, 'form_js'));
add_action('wp_ajax_nopriv_yast_form_js', array(&$this, 'form_js'));

// ADMIN UI
add_action('add_meta_boxes', array(&$this, 'add_custom_box'));
add_filter('manage_yast_posts_columns', array(&$this, ''));
add_action('manage_yast_posts_custom_column', array(&$this, 'columns_content'), 10, 2);
add_action('post_submitbox_misc_actions', array(&$this, 'submitbox'));

// Scripts
add_action('admin_enqueue_scripts', array(&$this, 'scripts_admin'));
add_action('wp_enqueue_scripts', array(&$this, 'scripts_front'));

add_action('edited_term_taxonomy', array($this, '_update_post_term_count'));
