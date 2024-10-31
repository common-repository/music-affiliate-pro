<?php
function ma_admin_search_init() {
	add_action('admin_menu', 'ma_admin_search_menu', 1);
}
add_action('init', 'ma_admin_search_init');


function ma_admin_search_menu() {
	// only include javascript on the relevant admin pages
	add_action( 'load-post.php', 'ma_admin_search_head' );
	add_action( 'load-post-new.php', 'ma_admin_search_head' );
	
	// include the meta box on the selected post types
	$active_post_types = ma_get_active_post_types();
	if ( 0 < count( $active_post_types ) ) {
		foreach ($active_post_types as $type) {
			add_meta_box('tasearchdiv', 'Tune Search', 'ma_search_meta_box', $type, 'normal', 'core');
		}
	}
}


function ma_admin_search_head() {
	wp_enqueue_script( 'ma-tune-search', plugins_url( 'js/search.js', dirname( __FILE__ ) ), array( 'jquery' ) );
	wp_enqueue_style( 'ma-admin-style', plugins_url( 'css/admin.css', dirname( __FILE__ ) ) );
}


function ma_search_meta_box($post) {
	?>
	<div class="ma-search">
		<?php
		$current_user = wp_get_current_user();
		$settings = get_option( 'ma_settings' );
		
		$user_search_options = get_user_meta( $current_user->ID, 'ma_search_options', true );
		
		$query_entities = array( 'Artist', 'Album', 'Song' );
		$query_services = ma_get_services();
		
		if ( ! isset( $settings['active_services'] ) || empty( $settings['active_services'] ) ) {
			echo '<div class="ma-initialize-error"><p>You must <a href="' . add_query_arg( 'page', 'music-affiliate-pro-settings', 'options-general.php' ) . '">enable the music services</a> you would like to search before proceeding.</p></div>';
			return;
		}
		?>
	
		<div id="ma-search-form">
			<input type="text" name="ma_q" id="ma-q" class="ma-query" />
			
			<select name="ma_query_entity" id="ma-query-entity" class="ma-query-entity">
				<?php
				foreach ( $query_entities as $entity ) {
					$value = strtolower( $entity );
					echo '<option value="' . $value . '"' .
						selected( $value, $user_search_options['query_entity'], false ) .
						'>' . esc_html($entity) . '</option>';
				}
				?>
			</select>
			
			<select name="ma_query_service" id="ma-query-service" class="ma-query-service">
				<?php
				if (is_array($settings['active_services'])) {
					foreach ($query_services as $key=>$service) {
						if (in_array($key, $settings['active_services'])) {
							echo '<option value="'. $key .'"'.
								selected($key, $user_search_options['query_service'], false) .
								'>'. esc_html($service) .'</option>';
						}
					}
				}
				?>
			</select>
			
			<input type="checkbox" name="ma_link_target_blank" id="ma-link-target-blank" <?php checked($user_search_options['link_target']); ?> /> <label for="ma-link-target-blank">Open links in new window?</label>
			
			<img src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" class="ma-ajax-feedback" alt="">
		</div>
		<div id="ma-search-message"></div>
		<div id="ma-search-results"></div>
	</div>
	<?php
}


function ma_save_user_search_options() {
	update_user_meta($_GET['uid'], 'ma_search_options', $_GET['search_options']);
	exit;
}
add_action('wp_ajax_ma_save_user_search_options', 'ma_save_user_search_options');


function ma_search() {
	$output = '';
	$entities = array('album','artist','song');
	$services = ma_get_services();
	
	$service = ( array_key_exists( $_GET['service'], $services ) && function_exists( 'ma_search_'. $_GET['service'] ) ) ? $_GET['service'] : 'itunes';
	$search_args = array(
		'entity' => ( isset( $_GET['entity'] ) && in_array( $_GET['entity'], $entities ) ) ? $_GET['entity'] : 'album',
		'q' => (isset($_GET['q']) && !empty($_GET['q'])) ? $_GET['q'] : ''
	);
	
	$data['isValid'] = false;
	$data['message'] = 'No results were returned. Please modify your query to try searching again.';
	
	if ( ! empty( $search_args['q'] ) ) {
		$results = apply_filters('ma_search_' . $service, NULL, $search_args);
		
		if ( is_wp_error( $results ) ) {
			$data['message'] = $results->get_error_message();
		} elseif ( is_array( $results ) ) {
			$data['isValid'] = true;
			$data['results'] = ma_get_results_output($results);
		}
	}
	
	echo json_encode( $data );
	exit;
}
add_action('wp_ajax_ma_search', 'ma_search');


class MA_Search_Result {
	function get_actions_html() {
		$output = '<div class="row-actions">';
			$output.= '<a href="'. $this->url .'" target="_blank">View</a>';
			if ( isset( $this->actions ) && is_array( $this->actions ) ) {
				foreach ($this->actions as $action) {
					$output.= ' | '. $action;
				}
			}
		$output.= '</div>';
		
		return $output;
	}
}


function ma_get_results_output($results) {
	$table = '<table cellspacing="0" class="ma-results">';
		$table.= '<thead>';
			$table.= '<tr>';
				$table.= '<th>Search Results</th>';
				$table.= ( $results[0]->type != 'artist' ) ? '<th>Artist</th>' : '';
				$table.= '<th>Type</th>';
				$table.= '<th style="text-align: right"><a id="ma-hide-results"><img src="' . plugins_url( 'images/icons/close.png', dirname( __FILE__ ) ) . '" width="12" height="12" title="Close results" /></a></th>';
			$table.= '</tr>';
		$table.= '</thead>';	
		$table.= '<tbody>';
			foreach ($results as $item) {
				$has_artwork = (isset($item->artwork_url) && !empty($item->artwork_url)) ? true : false;
				
				$classes = array();
				$classes[] = 'ma-result';
				$classes[] = 'ma-result-'. $item->type;
				if ($has_artwork) { $classes[] = 'has-artwork'; }
				
				$table.= '<tr class="'. implode(' ', $classes) .'">';
					if ($item->type == 'album') {
						$table.= '<td class="title">';
							$table.= ($has_artwork) ? '<img src="'. $item->artwork_url .'">' : '';
							$table.= '<strong><em>'. $item->title .'</em></strong>';
							
							$table.= $item->get_actions_html();
						$table.= '</td>';
						$table.= '<td class="artist">'. $item->artist .'</td>';
						$table.= '<td class="type">Album</td>';
						$table.= '<td>&nbsp;</td>';
					}
					
					if ($item->type == 'artist') {
						$table.= '<td class="title">';
							$table.= '<strong>'. $item->artist .'</strong>';
							
							$table.= $item->get_actions_html();
						$table.= '</td>';
						$table.= '<td class="type">Artist</td>';
						$table.= '<td>&nbsp;</td>';
					}
					
					if ($item->type == 'song') {
						$table.= '<td class="title">';
							$table.= ($has_artwork) ? '<img src="'. $item->artwork_url .'">' : '';
							$table.= '<strong>"'. $item->title .'"</strong>';
							
							$table.= $item->get_actions_html();
						$table.= '</td>';
						$table.= '<td class="artist">'. $item->artist .'</td>';
						$table.= '<td class="type">Song</td>';
						$table.= '<td>&nbsp;</td>';
					}
				$table.= '</tr>';
			}
		$table.= '</tbody>';
	$table.= '</table>';
	
	return $table;
}







add_filter('ma_search_amazonmp3', 'ma_search_amazonmp3', 10, 2);
function ma_search_amazonmp3($results, $args) {
	$settings = get_option( 'ma_settings' );
	
	if ( ! isset( $settings['config']['amazon_access_key'] ) || ! isset( $settings['config']['amazon_secret_access_key'] ) || empty( $settings['config']['amazon_access_key'] ) || empty( $settings['config']['amazon_secret_access_key'] ) ) {
		return new WP_Error( 'AWS.InvalidKeys', 'Amazon Access Keys have not been set. You can enter them on the <a href="' . add_query_arg( 'page', 'music-affiliate-pro-settings', 'options-general.php' ) . '">Settings panel</a>.' );
	}
	
	if ( ! isset( $settings['amazon_id'] ) || empty( $settings['amazon_id'] ) ) {
		return new WP_Error( 'AWS.InvalidAssociatesID', 'Amazon requires a valid Associates Tracking ID to access their API. You can enter this in the Affiliate Networks section on the <a href="' . add_query_arg( 'page', 'music-affiliate-pro-settings#madiv-affiliatenetworkssettings', 'options-general.php' ) . '">Settings panel</a>.' );
	}
	
	$search_args = array(
		'Service' => 'AWSECommerceService',
		'Operation' => 'ItemSearch',
		'AWSAccessKeyId' => $settings['config']['amazon_access_key'],
		'AssociateTag' => 'the9513-20',
		'SearchIndex' => 'MP3Downloads',
		'Keywords' => rawurlencode( stripslashes( $args['q'] ) ),
		'Timestamp' => urlencode( gmdate( 'Y-m-d\TH:i:s\Z', current_time( 'timestamp', 1 ) ) ),
		'Version' => '2011-08-01',
		'ResponseGroup' => 'Medium'
	);
	ksort( $search_args );
	
	$request_uri = 'http://webservices.amazon.com/onca/xml';
	$tosign = "GET\nwebservices.amazon.com\n/onca/xml\n" . build_query( $search_args );
	$search_args['Signature'] = urlencode( hex_to_base64( hash_hmac( 'sha256', $tosign, $settings['config']['amazon_secret_access_key'] ) ) );
	$request_uri = add_query_arg( $search_args, $request_uri );
	
	
	$request = new WP_Http;
	$response = $request->request( $request_uri );
	if (!is_wp_error($response)) {
		$xml = new SimpleXMLElement($response['body']);
		#echo '<pre>'; print_r($xml->Items); echo '</pre>';
		
		// Check for errors
		if ( $xml->Error ) {
			return new WP_Error( (string) $xml->Error->Code, (string) $xml->Error->Message );
		}
		
		if ( isset( $xml->OperationRequest->Errors->Error ) ) {
			foreach($xml->OperationRequest->Errors->Error as $error){
				return new WP_Error( (string) $error->Code, (string) $error->Message );
			}
		}
		
		if ( isset( $xml->Items->Request->Errors ) ) {
			foreach($xml->Items->Request->Errors->Error as $error){
				return new WP_Error( (string) $error->Code, (string) $error->Message );
			}
		}
		
		// Process items and return in a recognizable format
		foreach ($xml->Items->Item as $item) {
			$result = new MA_Search_Result();
			$result->type = ($item->ItemAttributes->ProductGroup == 'Digital Music Album') ? 'album' : 'song';
			$result->artist = (string) $item->ItemAttributes->Creator;
			$result->title = (string) $item->ItemAttributes->Title;
			$result->url = 'http://www.amazon.com/gp/product/'. $item->ASIN;
			$result->artwork_url = $item->SmallImage->URL;
			$result->source = 'Amazon MP3';
			
			if ($result->type == 'album') {
				$result->album_title = (string) $item->ItemAttributes->Title;
			}
			
			$result->actions[] = '<a href="#'. $item->ASIN .'" class="add-amazonmp3-widget">Add Widget</a>';
			
			$results[] = $result;
		}
	}
	
	return $results;
}



add_filter('ma_search_emusic', 'ma_search_emusic', 10, 2);
function ma_search_emusic($results, $args) {
	$settings = get_option( 'ma_settings' );
	
	if ( ! isset( $settings['config']['emusic_key'] ) || empty( $settings['config']['emusic_key'] ) ) {
		return new WP_Error( 'eMusic.InvalidKey', 'eMusic requires an API Key to access their API. You can enter this on the <a href="' . add_query_arg( 'page', 'music-affiliate-pro-settings', 'options-general.php' ) . '">Settings panel</a>.' );
	}
	
	$entities = array(
		'album' => 'album',
		'artist' => 'artist',
		'song' => 'track'
	);
	
	$search_args = array(
		'apiKey' => $settings['config']['emusic_key'],
		'format' => 'json',
		'imageSize' => 'thumbnail',
		'term' => urlencode( $args['q'] )
	);
	
	$request_uri = add_query_arg( $search_args, 'http://api.emusic.com/' . $entities[ $args['entity'] ] . '/search' );
	
	$response = wp_remote_get( $request_uri );
	if ( is_wp_error( $response ) )
		return $response;
	
	$json = json_decode( wp_remote_retrieve_body( $response ) );
	if ( ! empty( $json ) ) {
		if ($args['entity'] == 'album') {
			foreach ($json->albums as $item) {
				$result = new MA_Search_Result();
				$result->type = 'album';
				$result->artist = $item->artist->name;
				$result->title = $item->name;
				$result->album_title = $item->name;
				$result->url = $item->url;
				$result->artwork_url = $item->image;
				$result->source = 'eMusic';
				
				$results[] = $result;
			}
		}
		
		if ($args['entity'] == 'artist') {
			foreach ($json->artists as $item) {
				$result = new MA_Search_Result();
				$result->type = 'artist';
				$result->artist = $item->name;
				$result->title = $item->name;
				$result->url = $item->url;
				$result->source = 'eMusic';
				
				$results[] = $result;
			}
		}
		
		if ($args['entity'] == 'song') {
			foreach ($json->tracks as $item) {
				$result = new MA_Search_Result();
				$result->type = 'song';
				$result->artist = $item->album->artist->name;
				$result->title = $item->name;
				$result->album_title = $item->album->name;
				$result->url = $item->album->url;
				$result->artwork_url = $item->album->image;
				$result->source = 'eMusic';
				
				$results[] = $result;
			}
		}
	}
	
	return $results;
}



add_filter('ma_search_itunes', 'ma_search_itunes', 10, 2);
function ma_search_itunes($results, $args) {
	$entities = array(
		'album' => 'album',
		'artist' => 'musicArtist',
		'song' => 'song'
	);
	
	$search_args = array(
		'entity' => $entities[$args['entity']],
		'limit' => 10,
		'media' => 'music',
		'term' => urlencode($args['q'])
	);
	
	$request_uri = add_query_arg($search_args, 'http://itunes.apple.com/search');
	
	$request = new WP_Http;
	$response = $request->request($request_uri);
	if (!is_wp_error($response)) {
		$json = json_decode($response['body']);
		#echo '<pre>'; print_r($json->results); echo '</pre>';
		
		foreach ($json->results as $item) {
			$result = new MA_Search_Result();
			$result->type = $args['entity'];
			$result->artist = $item->artistName;
			
			if ($args['entity'] == 'album') {
				$result->title = $item->collectionName;
				$result->url = $item->collectionViewUrl;
				$result->album_title = $item->collectionName;
				$result->artwork_url = $item->artworkUrl60;
				$result->preview_url = '';
				$result->source = 'iTunes';
				
				$result->actions[] = '<a href="#'. $item->collectionId .'" class="add-itunes-widget">Add Widget</a>';
			}
			
			if ($args['entity'] == 'artist') {
				$result->title = $item->artistName;
				$result->url = $item->artistLinkUrl;
				$result->source = 'iTunes';
			}
			
			if ($args['entity'] == 'song') {
				$result->title = $item->trackName;
				$result->url = $item->trackViewUrl;
				$result->album_title = $item->collectionName;
				$result->artwork_url = $item->artworkUrl60;
				$result->preview_url = $item->previewUrl;
				$result->source = 'iTunes';
			}
			
			$results[] = $result;
		}
	}
	
	return $results;
}



/*add_filter('ma_search_rhapsody', 'ma_search_rhapsody', 10, 2);
function ma_search_rhapsody($results, $args) {
	$entities = array(
		'album' => 'RhapAlbum',
		'artist' => 'RhapArtist',
		'song' => 'RhapTrack'
	);
	
	$search_args = array(
		'query' => urlencode($args['q']),
		'searchtype' => $entities[$args['entity']],
		'size' => 10
	);
	
	$request_uri = add_query_arg($search_args, 'http://realsearch.real.com/search/');
	
	$request = new WP_Http;
	$response = $request->request($request_uri);
	
	if (!is_wp_error($response)) {
		#$xml = new SimpleXMLElement($response['body']);
		#echo '<pre>'; print_r($response['body']); echo '</pre>';
		
		foreach ($xml->Items->Item as $item) {
			$result = new MA_Search_Result();
			$result->type = ($item->ItemAttributes->ProductGroup == 'Digital Music Album') ? 'album' : 'song';
			$result->artist = (string) $item->ItemAttributes->Creator;
			$result->title = (string) $item->ItemAttributes->Title;
			$result->url = 'http://www.amazon.com/gp/product/'. $item->ASIN;
			$result->artwork_url = $item->SmallImage->URL;
			$result->source = 'Amazon MP3';
			
			if ($result->type == 'album') {
				$result->album_title = (string) $item->ItemAttributes->Title;
			}
			
			$results[] = $result;
		}
	}
	
	return $results;
}*/



add_filter('ma_search_rdio', 'ma_search_rdio', 10, 2);
function ma_search_rdio($results, $args) {
	$settings = get_option( 'ma_settings' );
	
	if ( ! isset( $settings['config']['rdio_key'] ) || ! isset( $settings['config']['rdio_key'] ) || empty( $settings['config']['rdio_shared_secret'] ) || empty( $settings['config']['rdio_shared_secret'] ) ) {
		return new WP_Error( 'Rdio.InvalidKey', 'Rdio API Keys have not been set. You can enter them on the <a href="' . add_query_arg( 'page', 'music-affiliate-pro-settings', 'options-general.php' ) . '">Settings panel</a>.' );
	}
	
	$entities = array(
		'album' => 'Album',
		'artist' => 'Artist',
		'song' => 'Track'
	);
	
	$search_args = array(
		'method' => 'search',
		'query' => stripslashes( $args['q'] ),
		'types' => $entities[$args['entity']]
	);
	
	$url = 'http://api.rdio.com/1/';
	
	
	require_once( plugin_dir_path(dirname(__FILE__)) . 'includes/OAuth.php' );
	$consumer = new OAuthConsumer( $settings['config']['rdio_key'], $settings['config']['rdio_shared_secret'] );
	$oauth = OAuthRequest::from_consumer_and_token($consumer, NULL, 'POST', $url, $search_args); 
	$oauth->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL);
	
	
	$response = wp_remote_post( $url, array( 'body' => $oauth->to_postdata() ) );
	if ( ! is_wp_error( $response ) ) {
		$json = json_decode($response['body']);
		#echo '<pre>'; print_r($json); echo '</pre>';
		
		foreach ($json->result->results as $item) {
			$result = new MA_Search_Result();
			$result->type = $args['entity'];
			$result->title = $item->name;
			
			if ($args['entity'] == 'artist') {
				$result->artist = $item->name;
			} else {
				$result->artist = $item->artist;
				$result->album_title = $item->name;
				$result->artwork_url = $item->icon;
				$result->preview_url = $item->embedUrl;
				
				$result->actions[] = '<a href="' . $item->embedUrl . '" class="add-rdio-widget">Add Widget</a>';
			}
			
			$result->url = 'http://www.rdio.com' . $item->url;
			$result->source = 'Rdio';
			$results[] = $result;
		}
	}
	
	return $results;
}



add_filter('ma_search_spotify', 'ma_search_spotify', 10, 2);
function ma_search_spotify($results, $args) {
	$settings = get_option( 'ma_settings' );
	$spotify_schema = ( isset( $settings['config']['spotify_scheme'] ) ) ? true : false;
	
	$entities = array(
		'album' => 'album',
		'artist' => 'artist',
		'song' => 'track'
	);
	
	$search_args = array(
		'q' => urlencode($args['q'])
	);
	
	$request_uri = add_query_arg( $search_args, 'http://ws.spotify.com/search/1/' . $entities[ $args['entity'] ] .'.json' );
	
	$response = wp_remote_get( $request_uri );
	if ( is_wp_error( $response ) )
		return $response;
	
	$json = json_decode( wp_remote_retrieve_body( $response ) );
	if ( ! empty( $json ) ) {
		#echo '<pre>'; print_r($json); echo '</pre>';
		
		if ($args['entity'] == 'album') {
			foreach ($json->albums as $item) {
				$result = new MA_Search_Result();
				$result->type = 'album';
				$result->artist = $item->artists[0]->name;
				$result->title = $item->name;
				$result->album_title = $item->name;
				$result->url = ( $spotify_schema ) ? $item->href : 'http://open.spotify.com/album/' . str_replace( 'spotify:album:', '', $item->href );
				$result->source = 'Spotify';
				
				$result->actions[] = '<a href="#'. $item->href .'" class="add-spotify-widget">Add Widget</a>';
				
				$results[] = $result;
			}
		}
		
		if ($args['entity'] == 'artist') {
			foreach ($json->artists as $item) {
				$result = new MA_Search_Result();
				$result->type = 'artist';
				$result->artist = $item->name;
				$result->title = $item->name;
				$result->url = ( $spotify_schema ) ? $item->href : 'http://open.spotify.com/artist/' . str_replace( 'spotify:artist:', '', $item->href );
				$result->source = 'Spotify';
				
				$result->actions[] = '<a href="#'. $item->href .'" class="add-spotify-widget">Add Widget</a>';
				
				$results[] = $result;
			}
		}
		
		if ($args['entity'] == 'song') {
			foreach ($json->tracks as $item) {
				$result = new MA_Search_Result();
				$result->type = 'song';
				$result->artist = $item->artists[0]->name;
				$result->title = $item->name;
				$result->album_title = $item->album->name;
				$result->url = ( $spotify_schema ) ? $item->href : 'http://open.spotify.com/track/' . str_replace( 'spotify:track:', '', $item->href );
				$result->source = 'Spotify';
				
				$result->actions[] = '<a href="#'. str_replace( 'spotify:track:', '', $item->href ) .'" class="add-spotify-widget append">Add Widget</a>';
				
				$results[] = $result;
			}
		}
	}
	
	return $results;
}
?>