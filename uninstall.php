<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'ma_settings' );
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='ma_search_options' OR meta_key='ma_newsletter_subscribed'");
?>