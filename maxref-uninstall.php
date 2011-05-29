<?php
if ( !defined('WP_UNINSTALL_PLUGIN') ) {
	exit();
}

if ( function_exists('register_uninstall_hook') )
	register_uninstall_hook(__FILE__, 'wrmaxref_uninstall_hook');
 
function wrmaxref_uninstall_hook() {
	delete_option('mref-widget');
}

?>
