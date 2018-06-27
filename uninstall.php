<?php
/**
 * Fired when plugin is uninstalled
 *
 * @since 0.1
 * @author Daniil Zhitnitskii
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

//declaring global DB connection variable
global $wpdb;

$wpdb->query( "DELETE FROM `".$wpdb->prefix."sitemeta` WHERE (`meta_key` LIKE 'mlsads_%') OR (`meta_key` LIKE '%_ads_%');");