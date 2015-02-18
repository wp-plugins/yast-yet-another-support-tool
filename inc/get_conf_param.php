<?php

return apply_filters('yast_conf_params', array(
    'alert_emails' => array(
	'default' => array(),
	'filter' => FILTER_SANITIZE_URL
    ),
    'remote_server' => array(
	'default' => '',
	'filter' => FILTER_SANITIZE_URL
    ),
    'remote_token' => array(
	'default' => '',
	'filter' => FILTER_SANITIZE_STRING
    ),
    'local_token' => array(
	'default' => '',
	'filter' => FILTER_SANITIZE_STRING
    ),
    'crypt_IV' => array(
	'default' => '',
	'filter' => FILTER_SANITIZE_STRING
    ),
    'remote_use' => array(
	'default' => 'none',
	'filter' => FILTER_SANITIZE_STRING
    ),
    'default_type' => array(
	'default' => '',
	'filter' => FILTER_SANITIZE_STRING
    ),
    'force_ssl' => array(
	'default' => 0,
	'filter' => FILTER_SANITIZE_NUMBER_INT
    ),
    'support_site' => array(
	'default' => 1,
	'filter' => FILTER_SANITIZE_NUMBER_INT
    ),
    'trusted_hosts' => array(
	'default' => '',
	'filter' => FILTER_SANITIZE_NUMBER_INT
    ),
    'display_single_in_theme' => array(
	'default' => 1,
	'filter' => FILTER_SANITIZE_NUMBER_INT
    ),
	)
);
