<?php

/*
Plugin Name: Simple Advertising
Plugin URI: https://github.com/my-language-skills/simple-advertising
Description: With use of this plugin you will be able to allocate advertisements on all of your post types. Only for multisite installations!(single site soon)
Version: 1.0
Author: Daniil Zhitnitskii (My Language Skills)
Author URI: https://github.com/my-language-skills
License: GPL 3.0
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
function mlsads_network_set_page(){

	//creating network administration page
	add_submenu_page('settings.php', 'MLS Advirtisement', 'MLS Advertisement', 'manage_network_options', 'mlsads_net_settings', 'mlsads_render_net_sett');

	//adding section to main options page
	add_settings_section('mlsads_locations', 'Advertisement Locations', '', 'mlsads_net_settings');

	//getting all public post types, for Pressbooks installation only CPTs from Pressbooks will be used
	if (is_plugin_active('pressbooks/pressbooks.php')){
		$all_post_types = ['chapter', 'part', 'back-matter', 'front-matter'];
	} else {
		$all_post_types = get_post_types(['public' => true]);
	}

	//creating location options for each post type
	foreach ($all_post_types as $post_type){
		register_setting('mlsads_net_settings', $post_type.'_ads_after');
		register_setting('mlsads_net_settings', $post_type.'_ads_before');
		//function for creation of options fields
		$render_locations = function () use ($post_type){
			?>
			<label for="<?=$post_type.'_ads_before'?>">
				Before <input type="checkbox" name="<?=$post_type.'_ads_before'?>" value="1" id="<?=$post_type.'_ads_before'?>" <?=checked(1, get_site_option($post_type.'_ads_before'))?>>
			</label>
			<label style="margin-left: 20px" for="<?=$post_type.'_ads_after'?>">
				After <input type="checkbox" name="<?=$post_type.'_ads_after'?>" value="1" id="<?=$post_type.'_ads_after'?>" <?=checked(1, get_site_option($post_type.'_ads_after'))?>>
			</label>
			<?php
		};
		add_settings_field($post_type.'_ads', 'In '.ucfirst($post_type).' Content', $render_locations, 'mlsads_net_settings', 'mlsads_locations');
	}

	//creating section for ads content
	add_settings_section('mlsads_contents', 'Advertisement Content', '', 'mlsads_net_settings');
	register_setting('mlsads_net_settings', 'mlsads_content_before');
	register_setting('mlsads_net_settings', 'mlsads_content_after');

	//adding content fields
	add_settings_field('mlsads_content_before', '"Before" ads content:', function (){
		?>
			<textarea rows="5" style="width: 90%" id="mlsads_content_before" name="mlsads_content_before"><?= get_site_option('mlsads_content_before') ?: ''?></textarea>
		<?php
	}, 'mlsads_net_settings', 'mlsads_contents');

	add_settings_field('mlsads_content_after', '"After" ads content:', function (){
		?>
		<textarea rows="5" style="width: 90%" id="mlsads_content_after" name="mlsads_content_after"><?= get_site_option('mlsads_content_after') ?: ''?></textarea>
		<?php
	}, 'mlsads_net_settings', 'mlsads_contents');

	//add section for main blog
	add_settings_section('mlsads_main_blog', 'Main blog', '', 'mlsads_net_settings');
	register_setting('mlsads_net_settings', 'mlsads_main_out');
	add_settings_field('mlsads_main_out', 'No showing Ads', function (){
		?>
		<input type="checkbox" value = 1 name="mlsads_main_out" id="mlsads_main_out" <?= checked(1,  get_site_option('mlsads_main_out'))?>>
		<?php
	}, 'mlsads_net_settings' ,'mlsads_main_blog');
}


/**
 * Function for rendering network settings page
 *
 * @since 0.1
 * @author Daniil Zhitnitskii @danzhik
 */
function mlsads_render_net_sett(){
	?>
	<div class="wrap">
		<form method="POST" action="edit.php?action=update_network_options_ads">
			<?php
			settings_fields('mlsads_net_settings');
			do_settings_sections('mlsads_net_settings');
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
function mlsads_update_options_ads(){

	//security check, prevents direct access to update page
	check_admin_referer('mlsads_net_settings-options');

	// This is the list of registered options.
	global $new_whitelist_options;
	$options = array_unique($new_whitelist_options['mlsads_net_settings']);

	//updating all options received from options page
	foreach ($options as $option){
		$val = isset($_POST[$option]) ? $_POST[$option] : '';
		update_site_option($option, $val);
	}

	// At the end we redirect back to our options page.
	wp_redirect(add_query_arg(array('page' => 'mlsads_net_settings',
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
function mlsads_output_ads($html){
	global $wpdb, $current_site;

	//getting option for displaying on a main site of multisite
	$advertising_main_blog = get_site_option('mlsads_main_out', 'show');
	$display_ads = 'yes';

	//if said not to display on main site and current blog is main site, do nothing
	if ( $wpdb->blogid == $current_site->id && $advertising_main_blog != 'show' ) {
		$display_ads = 'no';
	}

	if ( $display_ads == 'yes' ) {
		$post_type = get_post_type();
		if (get_site_option($post_type.'_ads_before') == 1){
			$content = get_site_option('mlsads_content_before');
			$html = $content.$html;
		}

		if (get_site_option($post_type.'_ads_after' ) == 1) {
			$content = get_site_option('mlsads_content_after');
			$html .= $content;
		}
	}
	return $html;
}

//adding actions for network settings page creation and updating
add_action('network_admin_menu', 'mlsads_network_set_page');
add_action('network_admin_edit_update_network_options_ads', 'mlsads_update_options_ads');
//output of ads
add_filter('the_content', 'mlsads_output_ads', 20, 1);