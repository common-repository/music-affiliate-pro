<?php
function ma_options_init() {
	ma_subscribe_to_newsletter();
	
	if (isset($_POST['ma_settings']) && wp_verify_nonce($_POST['ma_settings_nonce'], 'update-music-affiliate-pro-settings')) {
		$settings = $_POST['ma_settings'];
		$settings['active_post_types'] = (isset($_POST['ma_settings']['active_post_types'])) ? $_POST['ma_settings']['active_post_types'] : array();
		
		update_option('ma_settings', $settings);
		wp_redirect( add_query_arg( array( 'status' => 'updated', 'msg' => urlencode( 'Settings saved.' ) ), 'options-general.php?page=music-affiliate-pro-settings' ) );
		exit;
	}
	
	
	add_action('admin_menu', 'ma_admin_options_menu', 1);
	add_action('load-settings_page_music-affiliate-pro-settings', 'ma_settings_help');
}
add_action('init', 'ma_options_init');


function ma_admin_options_menu() {
	$pagehook = add_options_page('Music Affiliate Pro Settings', 'Music Affiliate Pro', 'manage_options', 'music-affiliate-pro-settings', 'ma_settings_page');
	add_action('load-'. $pagehook, 'ma_settings_admin_load');
	
	add_meta_box('madiv-searchsettings', 'Search Settings', 'ma_search_settings_meta_box', $pagehook, 'normal');
	add_meta_box('madiv-affiliatenetworkssettings', 'Affiliate Networks', 'ma_affiliate_networks_settings_meta_box', $pagehook, 'normal');
	add_meta_box('madiv-ituneswidgetsettings', 'iTunes Widget Appearance', 'ma_itunes_widget_settings_meta_box', $pagehook, 'normal');
	add_meta_box('madiv-amazonmp3widgetsettings', 'Amazon MP3 Clips Widget Settings', 'ma_amazonmp3_widget_settings_meta_box', $pagehook, 'normal');
	add_meta_box('madiv-spotifywidgetsettings', 'Spotify Play Button Appearance', 'ma_spotify_widget_settings_meta_box', $pagehook, 'normal');
	
	add_meta_box('madiv-contribute', 'Contribute', 'ma_contribute_meta_box', $pagehook, 'side');
	add_meta_box('madiv-support', 'Support', 'ma_support_meta_box', $pagehook, 'side');
}


function ma_settings_admin_load() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('post');
	
	wp_enqueue_script( 'ma-admin-settings', plugins_url( 'js/settings.js', dirname( __FILE__ ) ), array( 'jquery' ) );
	wp_enqueue_style( 'ma-admin-style', plugins_url( 'css/admin.css', dirname( __FILE__ ) ) );
}


function ma_settings_page() {
	global $current_user, $userdata, $wpdb;
	get_currentuserinfo();
	
	$default['affiliate_swap_rate'] = 100;
	
	$settings = get_option( 'ma_settings', $default );
	?>
	<div class="wrap">
		<div id="icon-music-affiliate-pro" class="icon32"><br></div>
		<h2>Music Affiliate Pro</h2>
		
		<?php
		if (isset($_GET['status'])) {
			echo '<div class="'. esc_attr($_GET['status']) .' fade-trigger fade-1000">'. wpautop(wp_strip_all_tags($_GET['msg'])) .'</div>';
		}
		?>
		
		<form action="" method="post" id="ma-settings">
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<?php
					wp_nonce_field('update-music-affiliate-pro-settings', 'ma_settings_nonce', false);
					wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
					wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
					?>
				
					<div id="post-body-content">
						<?php do_meta_boxes('settings_page_music-affiliate-pro-settings', 'normal', ''); ?>
						
						<p class="submit">
							<button type="submit" name="ma_save_settings" class="button-primary">Save Settings</button>
						</p>
					</div>
					
					<div id="postbox-container-1" class="postbox-container">
						<?php do_meta_boxes('settings_page_music-affiliate-pro-settings', 'side', ''); ?>
						<?php ma_credit(); ?>
					</div>
				</div>
			</div>
		</form>
	</div>
	<?php
}


function ma_search_settings_meta_box() {
	$settings = get_option( 'ma_settings' );
	$settings['active_services'] = ( ! isset( $settings['active_services'] ) ) ? array() : $settings['active_services'];
	?>
	<table class="form-table" id="ma-search-settings">
		<tr>
			<td>
				<h4 style="margin: 0">Services</h4>
				<p>
					Choose the digital music outlets you want to search.<br />
					<em>(Additional info may be required for individual services.)</em>
				</p>
				<ul>
					<?php
					$services = ma_get_services();
					foreach ( $services as $key => $service ) {
						echo '<li>';
							echo '<input type="checkbox" name="ma_settings[active_services][]" id="service-' . esc_attr( $key ) . '" value="' . esc_attr( $key ) . '"' .
								checked( in_array( $key, $settings['active_services'] ), true, false ) . ' /> ';
							echo '<label for="service-' . esc_attr( $key ) . '">' . $service . '</label>';
						echo '</li>';
					}
					?>
				</ul>
				
				<h4 style="margin-bottom: 0">Post Types</h4>
				<p>
					On which post types should the search form be active?
				</p>
				<?php
				$post_types = get_post_types();
				$post_types_blacklist = array( 'attachment', 'nav_menu_item' ,'revision' );
				
				$active_post_types = ma_get_active_post_types();
				
				echo '<p>';
					foreach ( $post_types as $type ) {
						if ( ! in_array( $type, $post_types_blacklist ) ) {
							$post_type = get_post_type_object( $type );
							
							echo '<input type="checkbox" name="ma_settings[active_post_types][]" id="post-type_' . $type . '" value="' . $type . '"';
								echo checked( in_array( $type, $active_post_types ), 1, false ) . ' /> ';
							echo '<label for="post-type_' . $type . '">' . __( $post_type->labels->name ) . '</label><br />';
						}
					}
				echo '</p>';
				?>
			</td>
			<td width="300">
				<p class="config" id="config-amazonmp3">
					<label for="amazon-access-key">Amazon Access Key ID</label><br />
					<input type="text" name="ma_settings[config][amazon_access_key]" id="amazon-access-key" value="<?php echo esc_attr( $settings['config']['amazon_access_key'] ); ?>" class="widefat" />
					
					<label for="amazon-secret-access-key">Amazon Secret Access Key</label><br />
					<input type="text" name="ma_settings[config][amazon_secret_access_key]" id="amazon-secret-access-key" value="<?php echo esc_attr( $settings['config']['amazon_secret_access_key'] ); ?>" class="widefat" />
					
					<span class="description">Register for access to the Amazon Product Advertising API <a href="http://aws.amazon.com/" target="_blank">here</a>. Find your Access Keys <a href="http://aws-portal.amazon.com/gp/aws/developer/account/index.html?action=access-key" target="_blank">here</a>.
					<br /><br />Amazon also requires a valid Associates Tracking ID to access their API; you can enter this in the <a href="#madiv-affiliatenetworkssettings">Affiliate Networks</a> section.</span>
				</p>
				
				<p class="config" id="config-emusic">
					<label for="emusic-key">eMusic API Key</label><br />
					<input type="text" name="ma_settings[config][emusic_key]" id="emusic-key" value="<?php echo esc_attr( $settings['config']['emusic_key'] ); ?>" class="widefat" />
					<span class="description">Register for the eMusic Developer Network <a href="http://developer.emusic.com/member/register" target="_blank">here</a>.
					Find your API Key <a href="http://developer.emusic.com/apps/mykeys" target="_blank">here</a>.</span>
				</p>
				
				<p class="config" id="config-rdio">
					<label for="rdio-key">Rdio API Key</label><br />
					<input type="text" name="ma_settings[config][rdio_key]" id="rdio-key" value="<?php echo @esc_attr( $settings['config']['rdio_key'] ); ?>" class="widefat" />
					
					<label for="rdio-shared-secret">Rdio Shared Secret</label><br />
					<input type="text" name="ma_settings[config][rdio_shared_secret]" id="rdio-shared-secret" value="<?php echo @esc_attr( $settings['config']['rdio_shared_secret'] ); ?>" class="widefat" />
					
					<span class="description">Register to access the Rdio API <a href="http://developer.rdio.com/member/register">here</a>. Find your API Keys <a href="http://developer.rdio.com/apps/mykeys" target="_blank">here</a>.</span>
				</p>
				
				<p class="config" id="config-spotify">
					<?php $spotify_schema = ( isset( $settings['config']['spotify_schema'] ) ) ? true : false; ?>
					<input type="checkbox" name="ma_settings[config][spotify_schema]" id="spotify-schema" value="1" <?php checked( $spotify_schema ); ?> />
					<label for="spotify-schema">Enable "spotify:" URI schema?</label><br />
					<span class="description">Read "<a href="http://www.spotify.com/us/blog/archives/2008/01/14/linking-to-spotify/" target="_blank">Linking to Spotify</a>" for details.</span>
				</p>
			</td>
		</tr>
	</table>
	<?php
}


function ma_affiliate_networks_settings_meta_box() {
	$settings = get_option( 'ma_settings' );
	?>
	<table class="form-table" id="ma-affiliate-network-settings">
		<tr>
			<th scope="row"><label for="amazon-id">Amazon Associates Tracking ID</label></th>
			<td>
				<input type="text" name="ma_settings[amazon_id]" id="amazon-id" value="<?php echo esc_attr($settings['amazon_id']); ?>" class="regular-text" /><br />
				<span class="description">Information about Amazon tracking IDs can be <a href="https://affiliate-program.amazon.com/gp/associates/help/t10" target="_blank"> found here</a>. (Example: musaffpro-20)</span>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itunes-wrapper">iTunes Link Wrapper</label></th>
			<td>
				<input type="text" name="ma_settings[itunes_wrapper]" id="itunes-wrapper" value="<?php echo esc_url($settings['itunes_wrapper']); ?>" class="large-text">
				<span class="description">Follow the first three steps under "<a href="http://www.apple.com/itunes/affiliates/resources/documentation/linking-to-the-itunes-music-store.html#AffiliateEncodingLinkShare" target="_blank">Affiliate Encoding for LinkShare</a>" to obtain your affilliate "wrapper."</span>
			</td>
		</tr>
		<?php /*<tr>
			<th scope="row">eMusic</th>
			<td>
				<span class="description">Uses Commission Junction.</span>
			</td>
		</tr>*/ ?>
	</table>
	<?php
}


function ma_itunes_widget_settings_meta_box() {
	$settings = get_option( 'ma_settings' );
	$iws = $settings['itunes_widget'];
	
	$width = (isset($iws['width'])) ? $iws['width'] : 250;
	$height = (isset($iws['height'])) ? $iws['height'] : 300;
	?>
	<table class="form-table" id="ma-itunes-widget-settings">
		<tr>
			<td>
				<table class="form-table">
					<tr>
						<td colspan="5">
							<h4 style="margin-top: 0">Corner Background Color</h4>
							<p>
								To provide seamless integration of the widget with your site, choose matching background colors below for the four corners of the widget.
							</p>
						</td>
					</tr>
					<tr>
						<td width="120"><label for="iws-upper-left-color">Upper Left Color</label></td>
						<td width="100">#<input type="text" name="ma_settings[itunes_widget][upper_left_color]" id="iws-upper-left-color" class="small-text" value="<?php echo $iws['upper_left_color']; ?>"></td>
						<td width="120"><label for="iws-upper-right-color">Upper Right  Color</label></td>
						<td width="100">#<input type="text" name="ma_settings[itunes_widget][upper_right_color]" id="iws-upper-right-color" class="small-text" value="<?php echo $iws['upper_right_color']; ?>"></td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td><label for="iws-lower-left-color">Lower Left Color</label></td>
						<td>#<input type="text" name="ma_settings[itunes_widget][lower_left_color]" id="iws-lower-left-color" class="small-text" value="<?php echo $iws['lower_left_color']; ?>"></td>
						<td><label for="iws-lower-right-color">Lower Right Color</label></td>
						<td>#<input type="text" name="ma_settings[itunes_widget][lower_right_color]" id="iws-lower-right-color" class="small-text" value="<?php echo $iws['lower_right_color']; ?>"></td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td colspan="5">
							<h4>Widget Size</h4>
							<p>
								Widgets can range from 250 to 325 pixels in width and 300 to 370 pixels in height.
							</p>
						</td>
					</tr>
					<tr>
						<td><label for="iws-width">Width</label></td>
						<td><input type="text" name="ma_settings[itunes_widget][width]" id="iws-width" class="small-text" value="<?php echo $width; ?>"></td>
						<td><label for="iws-height">Height</label></td>
						<td><input type="text" name="ma_settings[itunes_widget][height]" id="iws-height" class="small-text" value="<?php echo $height; ?>"></td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td colspan="5">
							<h4>HTML Template</h4>
							<p>
								<textarea name="ma_settings[itunes_widget][template]" id="iws-template" class="widefat"><?php echo esc_textarea(stripslashes($iws['template'])); ?></textarea>
								<span class="description"></span>
							</p>
						</td>
					</tr>
				</table>
			</td>
			<td style="text-align: right">
				<?php
				$sample_albums = array(
					'Jason Eady - When the Money\'s All Gone'=>'http://widgets.itunes.apple.com/itunes.html?wtype=2&app_id=327413065&country=us',
					'Rodney Hayden - Rodney Hayden'=>'http://widgets.itunes.apple.com/itunes.html?wtype=2&app_id=447102372&country=us',
					'Drew Kennedy - Fresh Water In the Salton Sea'=>'http://widgets.itunes.apple.com/itunes.html?wtype=2&app_id=460082055&country=us',
					'Corb Lund - Losin\' Lately Gambler'=>'http://widgets.itunes.apple.com/itunes.html?wtype=2&app_id=332248492&country=us'
				);
				
				$widget_args = array(
					'cul' => $iws['upper_left_color'],
					'cur' => $iws['upper_right_color'],
					'cll' => $iws['lower_left_color'],
					'clr' => $iws['lower_right_color'],
					'ww' => $width,
					'wh' => $height,
					'partnerID' => 30,
					'affiliate_id' => rawurlencode( ITUNESWRAPPER )
				);
				$widget_src = add_query_arg($widget_args, $sample_albums[array_rand($sample_albums)]);
				?>
				<iframe src="<?php echo $widget_src; ?>" frameborder="0" id="itunes-widget" style="overflow-x: hidden; overflow-y: hidden; width: <?php echo $width; ?>px; height: <?php echo $height; ?>px; border: 0"></iframe>
			</td>
		</tr>
	</table>
	<?php
}


function ma_amazonmp3_widget_settings_meta_box() {
	$settings = get_option( 'ma_settings' );
	$aws = $settings['amazonmp3_widget'];
	
	// Crazy stuff! Just setting sizes.
	if (isset($aws['size'])) {
		list($width, $height) = split('x', $aws['size']);
	} else {
		$width = $height = 250;
	}
	
	$sizes = array(
		'Large' => array( '250x250', '336x280' ),
		'Sidebar' => array( '120x300', '160x300' ),
		'Compact' => array( '125x125', '120x90', '234x60' )
	);
	?>
	<table class="form-table" id="ma-amazon-widget-settings">
		<tr>
			<td>
				<table class="form-table">
					<tr>
						<td width="120"><label for="aws-title">Default Title</label></td>
						<td width="120"><input type="text" name="ma_settings[amazonmp3_widget][title]" id="aws-title" class="regular-text" value="<?php echo esc_attr(stripslashes($aws['title'])); ?>"></td>
						<td rowspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td><label for="aws-size">Widget Size</label></td>
						<td>
							<select name="ma_settings[amazonmp3_widget][size]" id="aws-size">
								<?php
								foreach ($sizes as $group => $options) {
									echo '<optgroup label="'. $group .'">';
										foreach ($options as $size) {
											echo '<option value="'. $size .'"'. selected($size, $aws['size'], false) .'>'. $size .'</option>';
										}
									echo '</optgroup>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td><label for="aws-shuffle">Shuffle Tracks</label></td>
						<td>
							<select name="ma_settings[amazonmp3_widget][shuffle]" id="aws-size">
								<option value="true" <?php selected($aws['shuffle'], 'true'); ?>>Yes</option>
								<option value="false"<?php selected($aws['shuffle'], 'false'); ?>>No</option>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<h4>HTML Template</h4>
							<p>
								<textarea name="ma_settings[amazonmp3_widget][template]" id="aws-template" class="widefat"><?php echo esc_textarea(stripslashes($aws['template'])); ?></textarea>
								<span class="description"></span>
							</p>
						</td>
					</tr>
				</table>
			</td>
			<td style="text-align: right" id="aws-preview">
				<script type='text/javascript'>
				var amzn_wdgt={widget:'MP3Clips'};
				amzn_wdgt.tag='<?php echo AMAZONID; ?>';
				amzn_wdgt.widgetType='ASINList';
				amzn_wdgt.ASIN='B005JBBB8U,B0059CN9EI,B002MD1QHY,B002NNU84K';
				amzn_wdgt.title='<?php echo addslashes($aws['title']); ?>';
				amzn_wdgt.width='<?php echo $width; ?>';
				amzn_wdgt.height='<?php echo $height; ?>';
				amzn_wdgt.shuffleTracks='<?php echo ucwords($aws['shuffle']); ?>';
				amzn_wdgt.marketPlace='US';
				</script>
				<script type="text/javascript" src="http://wms.assoc-amazon.com/20070822/US/js/swfobject_1_5.js"></script>
			</td>
		</tr>
	</table>
	<?php
}


function ma_spotify_widget_settings_meta_box() {
	$sws = array(
		'height' => 380,
		'template' => '',
		'theme' => 'black',
		'title' => '',
		'view' => 'list',
		'width' => 300
	);
	
	$settings = get_option( 'ma_settings' );
	if ( isset( $settings['spotify_widget'] ) ) {
		$sws = wp_parse_args( $settings['spotify_widget'], $sws );
	}
	extract( $sws, EXTR_SKIP );
	
	?>
	<table class="form-table" id="ma-spotify-widget-settings">
		<tr>
			<td>
				<table class="form-table">
					<tr>
						<td><label for="sws-theme">Theme</label></td>
						<td>
							<select name="ma_settings[spotify_widget][theme]" id="sws-theme">
								<option value="black"<?php selected($sws['theme'], 'black'); ?>>Black</option>
								<option value="white"<?php selected($sws['theme'], 'white'); ?>>White</option>
							</select>
						</td>
					</tr>
					<tr>
						<td><label for="sws-view">View</label></td>
						<td>
							<select name="ma_settings[spotify_widget][view]" id="sws-view">
								<option value="list"<?php selected($sws['view'], 'list'); ?>>List</option>
								<option value="coverart"<?php selected($sws['view'], 'coverart'); ?>>Cover Art</option>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="4">
							<h4>Widget Size</h4>
							<p>
								The Spotify Play Button can range from 250 to 640 pixels in width and 80 to 720 pixels in height. If the height is 80 pixels more than the width, a larger player will be rendered, otherwise the compact player will be rendered.
							</p>
						</td>
					</tr>
					<tr>
						<td><label for="sws-width">Width</label></td>
						<td><input type="text" name="ma_settings[spotify_widget][width]" id="sws-width" class="small-text" value="<?php echo $width; ?>"></td>
						<td><label for="sws-height">Height</label></td>
						<td><input type="text" name="ma_settings[spotify_widget][height]" id="sws-height" class="small-text" value="<?php echo $height; ?>"></td>
					</tr>
					<tr>
						<td colspan="4">
							<h4>HTML Template</h4>
							<p>
								<textarea name="ma_settings[spotify_widget][template]" id="sws-template" class="widefat"><?php echo esc_textarea(stripslashes($sws['template'])); ?></textarea>
								<span class="description"></span>
							</p>
						</td>
					</tr>
				</table>
			</td>
			<td style="text-align: right">
				<?php
				$widget_args = array(
					'theme' => $sws['theme'],
					'uri' => 'spotify:user:bradyvercher:playlist:6sf3aR88QKbZBPw1YVkXvr',
					'view' => $sws['view']
				);
				$widget_src = add_query_arg($widget_args, 'https://embed.spotify.com/');
				?>
				<iframe src="<?php echo $widget_src; ?>" id="spotify-widget" width="<?php echo $width; ?>" height="<?php echo $height; ?>" frameborder="0" allowtransparency="true"></iframe>
			</td>
		</tr>
	</table>
	<?php
}


function ma_contribute_meta_box() {
	$settings = get_option( 'ma_settings' );
	?>
	<p>
		Help support development of this plugin by substituting our affiliate info for a few of your link impressions.
	</p>
	<p>
		Choose how often our affiliate info will be used by entering a percentage below.
	</p>
	<p>
		<input type="text" name="ma_settings[affiliate_swap_rate]" value="<?php echo esc_attr($settings['affiliate_swap_rate']); ?>" class="small-text" />%<br />
	</p>
	<p>
		Or consider <a href="http://bit.ly/tyB87g" target="_blank">donating a few dollars</a> if you feel this plugin has helped you.
	</p>
	<?php
}


function ma_support_meta_box() {
	?>
	<p>
		If you need help with this plugin, feel free to ask a question in the <a href="http://wordpress.org/tags/music-affiliate-pro?forum_id=10" target="_blank">support forum</a>.
	</p>
	<p>
		If you find this plugin useful, please consider sharing it or rating it in the <a href="http://wordpress.org/extend/plugins/music-affiliate-pro/" target="_blank">WordPress plugin directory</a>.
	</p>
	<?php
}


function ma_credit() {
	$current_user = wp_get_current_user();
	?>
	<div class="bscard">
		<h3>
			<a href="http://www.blazersix.com/">
				<img src="<?php echo plugins_url( 'images/blazersix.png', dirname( __FILE__ ) ); ?>" width="50" height="50" alt="Blazer Six" />
				<span><?php _e( 'Created by', 'music-affiliate-pro' ); ?> <strong>Blazer Six, Inc.</strong></span>
			</a>
		</h3>
		<div class="inside">
			<ul class="bscard-social">
				<li class="bscard-social-twitter"><a href="http://twitter.com/BlazerSix" target="_blank">@BlazerSix</a></li>
				<li class="bscard-social-facebook"><a href="https://www.facebook.com/pages/Blazer-Six/241713012554129" target="_blank">Facebook</a></li>
			</ul>
			
			<?php if ( ! get_user_meta( $current_user->ID, 'ma_newsletter_subscribed', true ) ) : ?>
				<p class="bscard-newsletter-toggle">
					<a href="#"><?php _e( "Subscribe to our newsletter to keep up with what we're doing.", 'music-affiliate-pro' ); ?></a>
				</p>
				
				<div id="bscard-newsletter" class="bscard-form bscard-newsletter hide-if-js">
					<p class="bscard-field">
						<label for="bscard-newsletter-name" class="bscard-label"><?php _e( 'Name:', 'music-affiliate-pro' ); ?></label>
						<input type="text" name="ma_newsletter_name" id="bscard-newsletter-name" value="<?php echo ( $current_user->display_name != 'admin' ) ? esc_attr( $current_user->display_name ) : ''; ?>" />
					</p>
					<p class="bscard-field">
						<label for="bscard-newsletter-email" class="bscard-label"><?php _e( 'Email:', 'music-affiliate-pro' ); ?></label>
						<input type="text" name="ma_newsletter_email" id="bscard-newsletter-email" value="<?php echo esc_attr( $current_user->user_email ); ?>" />										
					</p>
					<p class="bscard-submit">
						<input type="submit" name="ma_newsletter_register" value="<?php esc_attr_e( 'Subscribe', 'music-affiliate-pro' ); ?>" class="button" />
					</p>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<br class="clear" />
	<?php
}


function ma_subscribe_to_newsletter() {
	if (isset($_POST['ma_newsletter_register']) && is_email($_POST['ma_newsletter_email'])) {
		$args = array(
			'cm-bdijuj-bdijuj' => urlencode( $_POST['ma_newsletter_email'] ),
			'cm-name' => urlencode( $_POST['ma_newsletter_name'] ),
			'cm-f-hkyuhj' => urlencode( 'Music Affiliate Pro' ),
			'callback' => urlencode( 'callback' )
		);
		
		$request = new WP_Http;
		$response = $request->request( add_query_arg( $args, 'http://blazersix.createsend.com/t/y/s/bdijuj/' ) );
		
		// remove callback from JSONP
		$json = json_decode( substr( $response['body'], 9, strlen( $response['body'] ) - 10 ) );
		
		if ($json->Status == 200) {
			$current_user = wp_get_current_user();
			update_user_meta( $current_user->ID, 'ma_newsletter_subscribed', 1 );
			$status = array( 'status' => 'updated', 'msg' => urlencode( 'Thanks for subscribing!' ) );
		} else {
			$status = array( 'status' => 'error', 'msg' => urlencode( 'Subscription Failed: ' . $json->Message ) );
		}
		
		wp_redirect( add_query_arg( $status, 'options-general.php?page=music-affiliate-pro-settings' ) );
		exit;
	} elseif (isset($_POST['ma_newsletter_register']) && ! is_email($_POST['ma_newsletter_email'])) {
		$status = array( 'status' => 'error', 'msg' => urlencode( 'Invalid email address provided.' ) );
		wp_redirect( add_query_arg( $status, 'options-general.php?page=music-affiliate-pro-settings' ) );
		exit;
	}
}


function ma_settings_help() {
	$screen = get_current_screen();
	
	$screen->add_help_tab( array(
		'id' => 'search-settings',
		'title' => 'Search Settings',
		'content' => '<p>Options in this section allow you to choose which music services you want to search and on which post types <em>Tune Search</em> appears. Individual services may require additional information, such as access credentials.</p>'
	) );
	
	$screen->add_help_tab( array(
		'id' => 'affiliate-networks',
		'title' => 'Affiliate Networks',
		'content' => '<p>You can enter your affiliate info for related networks so that your links can be tracked, and most importantly, so you get paid!</p>
			<p>If you haven\'t already registered for the accounts below, go ahead and do so if you want to use the affiliate functionality in this plugin.</p>
			<p><em><strong>Note:</strong> Some of the following links are affiliate links; we\'ve provided alternative "clean" links where appropriate for your convenience.</em></p>
			<ul>
				<li>Register for <a href="https://affiliate-program.amazon.com/gp/flex/associates/apply-login.html" target="_blank">Amazon Associates</a>.</li>
				<li>Register for <a href="http://click.linksynergy.com/fs-bin/click?id=5bEuKyBiyPg&offerid=7097.10000025&type=3&subid=0" target="_blank">LinkShare</a> <em>(<a href="https://cli.linksynergy.com/cli/publisher/registration/registration.php" target="_blank">Clean Link</a>)</em> to participate in the iTunes affiliate program.</li>
			</ul>'
	) );
	
	#$text.= '<li>Register for <a href="http://www.emusic.com/info/affiliate/partner/" target="_blank">Commission Junction</a> to participate in the eMusic affiliate program.</li>
	#$text.= '<li>Register for eMusic</li>
	
	$screen->add_help_tab( array(
		'id' => 'itunes-widget',
		'title' => 'iTunes Widget',
		'content' => '<p>When you search for an album on iTunes using <em>Tune Search</em> on a post or page, you have the option of inserting a shortcode instead of a link to an album by clicking the "Add Widget" link in a search result. If you choose to use this widget, you can customize it\'s default appearance here.</p>
			<p>The typical <code>[itunes]</code> shortcode will usually only have an <code>id</code> attribute that references an album on iTunes, but all of the settings can be overridden by modifying the following optional attributes:</p>
			<p><code>[itunes id="123456789" width="300" height="335" upper_left_corner="000000" upper_right_corner="000000" lower_left_corner="000000" lower_right_corner="000000"]</code></p>'
	) );
	
	$screen->add_help_tab( array(
		'id' => 'amazonmp3-widget',
		'title' => 'Amazon MP3 Widget',
		'content' => '<p>The Amazon MP3 Widget functions similar to the iTunes Album Widget.</p>
			<p>The typical <code>[amazonmp3]</code> shortcode will usually only have an <code>asin</code> attribute referencing an album(s) <em>or</em> song(s) on Amazon. Note however, that the <code>asin</code> attribute may contain a comma separated list of ASIN identifiers. Like the <code>[itunes]</code> shortcode, you can modify the following optional attributes:
			<p><code>[amazonmp3 asin="B005JBBB8U,B0059CN9EI,B002MD1QHY,B002NNU84K" width="125" height="125" shuffletracks="true" title="My Super Awesome Widget"]</code></p>
			<p><em><strong>Note:</strong> The width and height values must correspond to the pre-defined values in the Widget Size dropdown.</em></p>
			<p><em><strong>Tip:</strong> Try selecting an existing <code>[amazonmp3]</code> shortcode in the editor and clicking the "Add Widget" link under a different result. The list of ASINs will be updated automatically, rather than replaced.</em></p>'
	) );
	
	$screen->add_help_tab( array(
		'id' => 'spotify-widget',
		'title' => 'Spotify Widget',
		'content' => '<p>The Spotify Play Button is an embeddable widget that plays music in the Spotify desktop app. You can customize the default appearance of the embedded widget in this section.</p>
			<p>The typical <code>[spotify]</code> shortcode requires either an <code>uri</code> attribute referencing an album, song, or artist on Spotify; or a <code>tracks</code> attribute is required with a comma separated list of track identifiers. You can override default attribute values or specify additional options by modifying the <code>[spotify]</code> shortcode:
			<p><code>[spotify uri="spotify:album:2VUhEo4tz9Mi6aR9qtfVoa" tracks="3ForsHC3pgvChpYsFVF4Pt,1nloDq2nT9vaY7LDEPHAzy,7tR4fJkG65n5sooU9ZLxMI" width="300" height="380" theme="black" view="list" title"Awesome Songs"]</code></p>
			<p><em><strong>Tip:</strong> Try selecting an existing <code>[spotify tracks="*"]</code> shortcode in the editor and clicking the "Add Widget" link under a different result. The list of tracks will be updated automatically, rather than replaced.</em></p>'
	) );
	
	$screen->add_help_tab( array(
		'id' => 'html-templates',
		'title' => 'Widget Templates',
		'content' => '<p>You can use the HTML template fields to provide any extra markup for positioning or styling widgets on your site. Supported replacement strings are <code>%HEIGHT%</code>, <code>%WIDGET%</code>, and <code>%WIDTH%</code>.</p>'
	) );
}
?>