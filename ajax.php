<?php

/*
 * Copyright (C) 2013-2015 CaerCam
 * License: http://opensource.org/licenses/MIT MIT
 */

define( 'FORUM_ROOT', dirname( __FILE__ ) . '/' );
require FORUM_ROOT.'include/common.php';
define( 'DOING_AJAX', true );

// Load AJAX handlers
require FORUM_ROOT . 'include/ajax_actions.php';

$allowed_actions = array(
	'get'  => array( 'foo' ),
	'post' => array( 'foo' )
);

// Register core Ajax calls.
if ( ! empty( $_GET['action'] ) && in_array( $_GET['action'], $allowed_actions['get'] ) )
	$action = 'luna_ajax_' . str_replace( '-', '_', $_GET['action'] );

if ( ! empty( $_POST['action'] ) && in_array( $_POST['action'], $allowed_actions['post'] ) )
	$action = 'luna_ajax_' . str_replace( '-', '_', $_POST['action'] );

if ( ! is_null( $action ) && function_exists( $action ) ) {
	call_user_func( $action );
}

// Default status
die( '0' );
