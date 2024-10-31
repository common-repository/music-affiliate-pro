<?php
/*
Plugin Name: Music Affiliate Pro
Plugin URI: http://wordpress.org/extend/plugins/music-affiliate-pro/
Description: Search and insert affiliate links for music in seconds. Works with major digital music outlets like iTunes, Amazon MP3, Spotify, and eMusic.
Version: 0.1.5
Author: Blazer Six, Inc.
Author URI: http://www.blazersix.com/
*/


// Leave these values, they'll be overwritten during an update.
// Add your affiliate info in the settings panel.
define('AMAZONID', 'musaffpro-20');
define('ITUNESWRAPPER', 'http://click.linksynergy.com/fs-bin/stat?id=5bEuKyBiyPg&offerid=146261&type=3&subid=0&tmpid=1826&RD_PARM1=');


require_once( plugin_dir_path(__FILE__) . 'includes/functions.php' );
require_once( plugin_dir_path(__FILE__) . 'includes/shortcodes.php' );

if (is_admin()) {
	require_once( plugin_dir_path(__FILE__) . 'admin/settings.php' );
	require_once( plugin_dir_path(__FILE__) . 'admin/meta-box-tune-search.php' );
	
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ma_plugin_action_links', 10, 4);
	function ma_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		$actions[] = '<a href="' . add_query_arg( 'page', 'music-affiliate-pro-settings', 'options-general.php' ) . '">Settings</a>';
		return $actions;
	}
}


// Searches content for links when loaded and adds affiliate info when necessary
function ma_music_link_filter($content) {
	$content = preg_replace_callback('/<a\b(?>\s+(?:href="([^"]*)"|rel="([^"]*)")|[^\s>]+|\s+)*>/', 'ma_affiliatize_links', $content);
	
	return $content;
}
add_filter('the_content', 'ma_music_link_filter', 1);
add_filter('comment_text', 'ma_music_link_filter', 10);

function ma_affiliatize_links($matches) {
	$link_tag = $matches[0];
	
	if (isset($matches[1])) {
		if (false !== strpos($matches[1], 'www.amazon.com')) {
			$amazon_id = ma_get_affiliate_info('amazon_id');
			if ( ! empty( $amazon_id ) ) {
				$new_url = add_query_arg('tag', $amazon_id, $matches[1]);
				$link_tag = str_replace($matches[1], $new_url, $matches[0]);
			}
		} elseif (false !== strpos($matches[1], 'itunes.apple.com') || false !== strpos($matches[1], 'phobos.apple.com')) {
			$wrapper = ma_get_affiliate_info('itunes_wrapper');
			if ( ! empty( $wrapper ) ) {
				$new_url = add_query_arg('partnerId', 30, $matches[1]);
				$new_url = $wrapper . rawurlencode(rawurlencode($new_url));
				$link_tag = str_replace($matches[1], $new_url, $matches[0]);
			}
		}
	}
	
	return $link_tag;
}



function ma_get_settings($settings) {
	// Default iTunes widget settings
	$iws = $settings['itunes_widget'];
	$itunes_widget_corners = array( 'upper_left_color', 'upper_right_color', 'lower_left_color', 'lower_right_color' );
	foreach ( $itunes_widget_corners as $corner ) {
		if ( ! isset( $iws[$corner] ) || ! is_hex_color( $iws[$corner] )) {
			$settings['itunes_widget'][$corner] = 'ffffff';
		}
	}
	
	if ( ! isset( $iws['width'] ) || intval( $iws['width'] ) < 250 || intval( $iws['width'] ) > 325 ) {
		$settings['itunes_widget']['width'] = 250;
	}
	
	if ( ! isset( $iws['height'] ) || intval( $iws['height'] ) < 300 || intval( $iws['height'] ) > 370 ) {
		$settings['itunes_widget']['height'] = 300;
	}
	
	// Default Amazon MP3 widget settings
	$aws = $settings['amazonmp3_widget'];
	$amazon_widget_sizes = array( '250x250', '336x280', '120x300', '160x300', '125x125', '120x90', '234x60' );
	if ( ! isset( $aws['size'] ) || ! in_array( $aws['size'], $amazon_widget_sizes ) ) {
		$settings['amazonmp3_widget']['size'] = '250x250';
	}
	
	if ( ! isset( $setttings['amazonmp3_widget']['shuffle'] ) || ! in_array( $setttings['amazonmp3_widget']['shuffle'], array( 'false', 'true' ) ) ) {
		$setttings['amazonmp3_widget']['shuffle'] = 'false';
	}
	
	// Other default settings
	if ( ! isset( $settings['affiliate_swap_rate'] ) || intval( $settings['affiliate_swap_rate'] ) < 0 || intval( $settings['affiliate_swap_rate'] ) > 100 ) {
		$settings['affiliate_swap_rate'] = 100;
	}
	
	return $settings;
}
add_filter( 'option_ma_settings', 'ma_get_settings' );



register_activation_hook(__FILE__, 'ma_install'); 
function ma_install() {
	// Set a couple of default settings, otherwise the get_option filter won't run
	$settings = get_option( 'ma_settings' );
	if ( empty( $settings ) ) {
		$settings['active_services'][] = 'itunes';
		$settings['active_post_types'][] = 'post';
		$settings['active_post_types'][] = 'page';
		$settings['amazonmp3_widget']['template'] = '%WIDGET%';
		$settings['itunes_widget']['template'] = '%WIDGET%';
		$settings['affiliate_swap_rate'] = 0;
		update_option( 'ma_settings', $settings );
	}
}
?>