<?php

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
    'public' => true,
    'show_ui' => true,
    'show_in_menu' => false,
    'capability_type' => 'post',
    'hierarchical' => true,
    'rewrite' => array('slug' => ''),
    'query_var' => true,
    'has_archive' => true,
    'supports' => array('title', 'editor', 'comments', 'custom-fields'),
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
