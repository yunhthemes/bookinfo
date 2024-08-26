<?php

namespace YungBooksPlugin;

use YungBooksPlugin\Database;
use YungBooksPlugin\CustomPostType;
use YungBooksPlugin\AdminDisplay;

class Plugin {
    
    public function init() {
        $this->register_hooks();
        $this->load_textdomain();
    }

    private function register_hooks() {
        register_activation_hook(__FILE__, [Database::class, 'install']);
        add_action('init', [CustomPostType::class, 'register']);
        add_action('admin_menu', [AdminDisplay::class, 'register_admin_page']);
    }

    private function load_textdomain() {
        load_plugin_textdomain('wp-books-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}
