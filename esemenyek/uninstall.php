<?php 
    // If uninstall/delete not called from WordPress then exit 
    if( !defined( ‘ABSPATH’ ) AND !defined( ‘WP_UNINSTALL_PLUGIN’ ) ) exit(); 

    // Delete options array from options table 
    delete_option( 'es_options' ); 
?> 
