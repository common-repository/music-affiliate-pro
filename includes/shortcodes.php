<?php
function amazonmp3_widget_shortcode($atts, $content=NULL) {
	$settings = get_option( 'ma_settings' );
	$size = split( 'x', $settings['amazonmp3_widget']['size'] );
	
	extract(shortcode_atts(array(
		'asin' => '',
		'shuffletracks' => $settings['amazonmp3_widget']['shuffle'],
		'title' => $settings['amazonmp3_widget']['title'],
		'height' => $size[1],
		'width' => $size[0]
	), $atts));
	
	if (!is_array($atts) || empty($asin)) {
		$output = '';
	} else {
		$output = '<script type="text/javascript">
			var amzn_wdgt={widget:\'MP3Clips\'};
			amzn_wdgt.tag=\''. ma_get_affiliate_info( 'amazon_id' ) .'\';
			amzn_wdgt.widgetType=\'ASINList\';
			amzn_wdgt.ASIN=\''. $asin .'\';
			amzn_wdgt.title=\''. addslashes($title) .'\';
			amzn_wdgt.width=\''. $width .'\';
			amzn_wdgt.height=\''. $height .'\';
			amzn_wdgt.shuffleTracks=\''. $shuffletracks .'\';
			amzn_wdgt.marketPlace=\'US\';
			</script>
			<script type="text/javascript" src="http://wms.assoc-amazon.com/20070822/US/js/swfobject_1_5.js"></script>';
		
		if ( ! empty( $settings['amazonmp3_widget']['template'] ) && false !== strpos( $settings['amazonmp3_widget']['template'], '%WIDGET%' ) ) {
			$tags = array( '%HEIGHT%', '%WIDGET%', '%WIDTH%' );
			$tag_values = array( $height, $output, $width );
			$output = str_replace( $tags, $tag_values, stripslashes( $settings['amazonmp3_widget']['template'] ) );
		}
	}
	
	return $output;
}
add_shortcode('amazonmp3', 'amazonmp3_widget_shortcode');


function itunes_widget_shortcode($atts, $content=NULL) {
	$settings = get_option( 'ma_settings' );
	$iws = $settings['itunes_widget'];
	
	extract(shortcode_atts(array(
		'id' => '',
		'height' => $iws['height'],
		'width' => $iws['width'],
		'upper_left_color' => $iws['upper_left_color'],
		'upper_right_color' => $iws['upper_right_color'],
		'lower_left_color' => $iws['lower_left_color'],
		'lower_right_color' => $iws['lower_right_color']
	), $atts));
	
	$output = '';
	if (!empty($id)) {
		$itunes_widget_corners = array( 'upper_left_color', 'upper_right_color', 'lower_left_color', 'lower_right_color' );
		foreach ( $itunes_widget_corners as $corner ) {
			if ( ! is_hex_color( ${$corner} ) ) {
				${$corner} = 'ffffff';
			}
		}
		
		$height = ( intval( $height ) < 300 || intval( $height ) > 370 ) ? 300 : intval( $height );		
		$width = ( intval( $width ) < 250 || intval( $width ) > 325 ) ? 250 : intval( $width );
		
		$widget_args = array(
			'app_id' => $id,
			'cul' => $upper_left_color,
			'cur' => $upper_right_color,
			'cll' => $lower_left_color,
			'clr' => $lower_right_color,
			'ww' => $width,
			'wh' => $height,
			'partnerId' => 30,
			'affiliate_id' => rawurlencode( ma_get_affiliate_info( 'itunes_wrapper' ) )
		);
		
		$widget_src = 'http://widgets.itunes.apple.com/itunes.html?wtype=2&country=us';
		$widget_src = add_query_arg( $widget_args, $widget_src );
		
		$output = '<iframe src="'. $widget_src .'" frameborder="0" style="overflow-x: hidden; overflow-y: hidden; width: ' . $width . 'px; height: ' . $height . 'px; border: 0"></iframe>';
		
		if ( ! empty( $iws['template'] ) && false !== strpos( $iws['template'], '%WIDGET%' ) ) {
			$tags = array( '%HEIGHT%', '%WIDGET%', '%WIDTH%' );
			$tag_values = array( $height, $output, $width );
			$output = str_replace( $tags, $tag_values, stripslashes( $iws['template'] ) );
		}
	}
	
	return $output;
}
add_shortcode('itunes', 'itunes_widget_shortcode');


function spotify_widget_shortcode($atts, $content=NULL) {
	global $content_width;
	
	$settings = get_option( 'ma_settings' );
	$sws = ( ! empty( $settings['spotify_widget'] ) ) ? $settings['spotify_widget'] : array();
	
	extract(shortcode_atts(array(
		'height' => '',
		'theme' => ( empty( $sws['theme'] ) ) ? 'black' : $sws['theme'],
		'title' => '',
		'tracks' => '',
		'uri' => '',
		'view' => ( empty( $sws['view'] ) ) ? 'list' : $sws['view'],
		'width' => '',
	), $atts));
	
	$widget_src = '';
	if ( ! empty( $uri ) ) {
		$widget_src = 'https://embed.spotify.com/';
		$widget_src = add_query_arg( 'uri', $uri, $widget_src );
	} elseif ( ! empty( $tracks ) ) {
		$tracks = explode( ',', $tracks );
		foreach ( $tracks as  $key => $track ) {
			if ( 22 != strlen( $track ) ) {
				unset( $tracks[ $key ] );
			}
		}
		
		// use the compact player for a single track; otherwise attempt to accomodate as many tracks as possible
		if ( empty( $height ) && 1 == count( $tracks ) ) {
			$height = 80;
		} elseif ( empty( $height ) && empty( $sws['height'] ) && ! empty( $tracks ) ) {
			$height = 80 + ( count( $tracks ) * 36 );
			$height = ( $height < 380 ) ? 380 : $height;
			$height = ( $height > 720 ) ? 720 : $height;
		}
		
		$tracks = ( empty( $tracks ) ) ? '' : join( $tracks, ',' );
		$widget_src = ( empty( $tracks ) ) ? '' : 'https://embed.spotify.com/?uri=spotify:trackset:' . $title . ':' . $tracks;
	}
	
	if ( empty( $width ) ) {
		$width = ( empty( $sws['width'] ) ) ? 300 : $sws['width'];
	}
	
	if ( empty( $height ) ) {
		// defaults to using the larger player
		$height = ( empty( $sws['height'] ) ) ? $width + 80 : $sws['height'];
	}
	
	$output = '';
	if ( ! empty( $widget_src ) ) {
		$widget_args = array(
			'theme' => $theme,
			'view' => $view
		);
		
		$output = '<iframe src="' . add_query_arg( $widget_args, $widget_src ) . '" width="' . $width . '" height="' . $height . '" frameborder="0" allowtransparency="true"></iframe>';
		
		if ( ! empty( $sws['template'] ) && false !== strpos( $sws['template'], '%WIDGET%' ) ) {
			$tags = array( '%HEIGHT%', '%WIDGET%', '%WIDTH%' );
			$tag_values = array( $height, $output, $width );
			$output = str_replace( $tags, $tag_values, stripslashes( $sws['template'] ) );
		}
	}
	
	return $output;
}
add_shortcode('spotify', 'spotify_widget_shortcode');
?>