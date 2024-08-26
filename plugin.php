<?php
/**
 * Plugin Name:     Example Plugin - Developed by yungthemes
 * Plugin URI:      https://www.veronalabs.com
 * Plugin Prefix:   YungBooksPlugin
 * Description:     Example WordPress Plugin Based on Rabbit Framework!
 * Author:          VeronaLabs
 * Author URI:      https://veronalabs.com
 * Text Domain:     domain-translate
 * Domain Path:     /languages
 * Version:         1.1.0
 */

 defined('ABSPATH') || exit;

 require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
 
 use YungBooksPlugin\Plugin;
 
 function wp_books_plugin_init() {
     $plugin = new Plugin();
     $plugin->init();
 }
 add_action('plugins_loaded', 'wp_books_plugin_init');
 