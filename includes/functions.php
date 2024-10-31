<?php
wp_oembed_add_provider('http://rd.io/*', 'http://www.rdio.com/api/oembed/');


if (!function_exists('hex_to_base64')) :
function hex_to_base64($str) {
	$raw = '';

	for ($i = 0; $i < strlen($str); $i += 2) {
		$raw.= chr(hexdec(substr($str, $i, 2)));
	}

	return base64_encode($raw);
}
endif;


if (!function_exists('is_hex_color')) :
function is_hex_color($str) {
	return preg_match('/^[a-f0-9]{6}$/i', $str);
}
endif;



function ma_get_active_post_types() {
	$settings = get_option( 'ma_settings' );
	
	if (isset($settings['active_post_types']) && is_array($settings['active_post_types'])) {
		$active_post_types = $settings['active_post_types'];
	} else {
		$active_post_types = array();
	}
	
	return $active_post_types;
}


function ma_get_affiliate_info( $network ) {
	$network = strtolower( $network );
	$settings = get_option( 'ma_settings' );
	$swap_rate = intval( $settings['affiliate_swap_rate'] );
	$rand = mt_rand(1, 100);
	
	if ( 'amazon_id' == $network ) {
		return ( $rand <= $swap_rate ) ? AMAZONID : $settings['amazon_id'];
	}
	
	if ( 'itunes_wrapper' == $network ) {
		return ( $rand <= $swap_rate ) ? ITUNESWRAPPER : $settings['itunes_wrapper'];
	}
	
	return '';
}


function ma_get_services() {
	$services = array(
		'amazonmp3' => 'Amazon MP3',
		'emusic' => 'eMusic',
		'itunes' => 'iTunes',
		'rdio' => 'Rdio',
		'spotify' => 'Spotify'
	);
	
	return $services;
}
?>