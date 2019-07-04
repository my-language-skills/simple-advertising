<?php

/*
Plugin Name: Simple Advertising
Plugin URI: https://github.com/my-language-skills/simple-advertising
Description: With use of this plugin you will be able to allocate advertisements on all of your post types. Only for multisite installations!(single site soon)
Version: 1.0.2
Author: My Language Skills team
Author URI: https://github.com/my-language-skills
License: GPL 3.0
Network: True
Text Domain: simple-advertising
Domain Path: /languages
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Function for creation of settings page
 *
 * @since 0.1
 * @author Daniil Zhitnitskii @danzhik
 */
function smplads_network_set_page(){

	//creating network administration page
	add_submenu_page('settings.php', __('Simple Advirtising', 'simple-advertising'),
									__('Simple Advertising', 'simple-advertising'), 'manage_network_options', 'smplads_net_settings', 'smplads_render_net_sett');

	//adding section to main options page
	add_settings_section('smplads_locations', __('Advertisement Locations', 'simple-advertising'), '', 'smplads_net_settings');

	//getting all public post types, for Pressbooks installation only CPTs from Pressbooks will be used
	if (is_plugin_active('pressbooks/pressbooks.php')){
		$all_post_types = ['chapter', 'part', 'back-matter', 'front-matter'];
	} else {
		$all_post_types = get_post_types(['public' => true]);
	}

	//creating location options for each post type
	foreach ($all_post_types as $post_type){
		register_setting('smplads_net_settings', $post_type.'_ads_after');
		register_setting('smplads_net_settings', $post_type.'_ads_before');
		//function for creation of options fields
		$render_locations = function () use ($post_type){
			?>
			<label for="<?=$post_type.'_ads_before'?>">
				<?php _e('Before', 'simple-advertising');?>
				<input type="checkbox" name="<?=$post_type.'_ads_before'?>" value="1" id="<?=$post_type.'_ads_before'?>" <?=checked(1, get_site_option($post_type.'_ads_before'))?>>
			</label>
			<label style="margin-left: 20px" for="<?=$post_type.'_ads_after'?>">
				<?php _e('After', 'simple-advertising');?> <input type="checkbox" name="<?=$post_type.'_ads_after'?>" value="1" id="<?=$post_type.'_ads_after'?>" <?=checked(1, get_site_option($post_type.'_ads_after'))?>>
			</label>
			<?php
		};
		add_settings_field($post_type.'_ads',
				sprintf(__('In %s Content', 'simple-advertising'), ucfirst($post_type)),
				$render_locations, 'smplads_net_settings', 'smplads_locations');
	}

	//creating section for ads content
	add_settings_section('smplads_contents',
												__('Advertisement Content', 'simple-advertising'),
	 											'', 'smplads_net_settings');
	register_setting('smplads_net_settings', 'smplads_content_before');
	register_setting('smplads_net_settings', 'smplads_content_after');
	register_setting('smplads_net_settings', 'smplads_content_link_before');
	register_setting('smplads_net_settings', 'smplads_content_link_after');

	//adding content fields
	add_settings_field('smplads_content_before', __('"Before" image (source URL):', 'simple-advertising'), function (){
		?>
			<input type="url" style="width: 50%;" placeholder="https://www.example.com/wp-content/uploads/2018/01/image-example.jpg" id="smplads_content_before" name="smplads_content_before" value="<?=get_site_option('smplads_content_before') ?: ''?>">
		<?php
	}, 'smplads_net_settings', 'smplads_contents');


	add_settings_field('smplads_content_link_before', __('External link (if filled in, image becomes a link):', 'simple-advertising'), function (){
		?>
			<input type="url" style="width: 50%;" placeholder="https://www.example.com/link-to-the-post" id="smplads_content_link_before" name="smplads_content_link_before" value="<?=get_site_option('smplads_content_link_before') ?: ''?>">
		<?php
	}, 'smplads_net_settings', 'smplads_contents');

	add_settings_field('smplads_content_after', __('"After" image (source URL):', 'simple-advertising'), function (){
		?>
		<input type="url" style="width: 50%;" placeholder="https://www.example.com/wp-content/uploads/2018/01/image-example.jpg" id="smplads_content_after" name="smplads_content_after" value="<?=get_site_option('smplads_content_after') ?: ''?>">
		<?php
	}, 'smplads_net_settings', 'smplads_contents');

	add_settings_field('smplads_content_link_after', __('External link (if filled in, image becomes a link):', 'simple-advertising'), function (){
		?>
			<input type="url" style="width: 50%;" placeholder="https://www.example.com/link-to-the-post" id="smplads_content_link_after" name="smplads_content_link_after" value="<?=get_site_option('smplads_content_link_after') ?: ''?>">
		<?php
	}, 'smplads_net_settings', 'smplads_contents');

	//add section for main blog
	add_settings_section('smplads_main_blog', __('Main blog', 'simple-advertising'), '', 'smplads_net_settings');
	register_setting('smplads_net_settings', 'smplads_main_out');
	add_settings_field('smplads_main_out', __('No showing Ads', 'simple-advertising'), function (){
		?>
		<input type="checkbox" value = 1 name="smplads_main_out" id="smplads_main_out" <?= checked(1,  get_site_option('smplads_main_out'))?>>
		<?php
	}, 'smplads_net_settings' ,'smplads_main_blog');
}


/**
 * Function for rendering network settings page
 *
 * @since 0.1
 * @author Daniil Zhitnitskii @danzhik
 */
function smplads_render_net_sett(){
	?>
	<div class="wrap">
		<div class="notice updated is-dismissible">
				<p><strong> <?php _e('Settings saved.', 'simple-advertising'); ?> </strong></p>
		</div>
		<form method="POST" action="edit.php?action=update_network_options_ads">
			<?php
			settings_fields('smplads_net_settings');
			do_settings_sections('smplads_net_settings');
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Function for updating network options
 *
 * @since 0.1
 * @author Daniil Zhitnitskii @danzhik
 */
function smplads_update_options_ads(){

	//security check, prevents direct access to update page
	check_admin_referer('smplads_net_settings-options');

	// This is the list of registered options.
	global $new_whitelist_options;
	$options = array_unique($new_whitelist_options['smplads_net_settings']);

	//updating all options received from options page
	foreach ($options as $option){
		if (stripos($option, 'content')){
			$val = isset($_POST[$option]) ? esc_url_raw($_POST[$option]) : '';
		} else {
			$val = isset($_POST[$option]) ? filter_var( $_POST[$option], FILTER_SANITIZE_NUMBER_INT ) : '';
		}

		if ($val) {
			update_site_option($option, $val);
		} else {
			delete_site_option($option);
		}
	}

	// At the end we redirect back to our options page.
	wp_redirect(add_query_arg(array('page' => 'smplads_net_settings',
	                                'updated' => 'true'), network_admin_url('settings.php')));

	exit;
}

/**
 * Function for ads front-end output
 * @param string $html code of post content
 *
 * @since 0.1
 * @author Daniil Zhitnitskii
 *
 * @return $html updated post content
 */
function smplads_output_ads($html){
	global $wpdb, $current_site;

	//getting option for displaying on a main site of multisite
	$advertising_main_blog = get_site_option('smplads_main_out', 'show');
	$display_ads = 'yes';

	//if said not to display on main site and current blog is main site, do nothing
	if ( $wpdb->blogid == $current_site->id && $advertising_main_blog != 'show' ) {
		$display_ads = 'no';
	}

	if ( $display_ads == 'yes' ) {
		$post_type = get_post_type();
		if (get_site_option($post_type.'_ads_before') == 1){

			if (get_site_option('smplads_content_link_before')){
				$content = '<a href="'.get_site_option('smplads_content_link_before').'"><img src="'.get_site_option('smplads_content_before').'"></a>';
			} else {
				$content = '<img src="'.get_site_option('smplads_content_before').'">';
			}
			$html = $content.$html;
		}

		if (get_site_option($post_type.'_ads_after' ) == 1) {

			if (get_site_option('smplads_content_link_after')){
				$content = '<a href="'.get_site_option('smplads_content_link_after').'"><img src="'.get_site_option('smplads_content_after').'"></a>';
			} else {
				$content = '<img src="'.get_site_option('smplads_content_after').'">';
			}
			$html .= $content;
		}
	}
	return $html;
}

//adding actions for network settings page creation and updating
add_action('network_admin_menu', 'smplads_network_set_page');
add_action('network_admin_edit_update_network_options_ads', 'smplads_update_options_ads');
//output of ads
add_filter('the_content', 'smplads_output_ads', 20, 1);

/**
 * For Internalization
 * It loads the MO file for plugin's translation
 *
 * @param void
 *
 * @since 1.0.2
 * @author Davide Cazzorla @davideC00
 *
 * @return void
 */
	function smplads_load_plugin_textdomain() {
    load_plugin_textdomain( 'simple-advertising', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
/**
 * For Internalization
 * Called when the plugin activated has been loaded
 */
add_action( 'plugins_loaded', 'smplads_load_plugin_textdomain' );
