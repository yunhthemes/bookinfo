<?php
/**
 * Plugin Name:     Example Plugin - Developed by yungthemes
 * Plugin URI:      https://www.veronalabs.com
 * Plugin Prefix:   EXAMPLE_PLUGIN
 * Description:     Example WordPress Plugin Based on Rabbit Framework!
 * Author:          VeronaLabs
 * Author URI:      https://veronalabs.com
 * Text Domain:     domain-translate
 * Domain Path:     /languages
 * Version:         1.0
 */

use Rabbit\Application;
use Rabbit\Redirects\RedirectServiceProvider;
use Rabbit\Database\DatabaseServiceProvider;
use Rabbit\Logger\LoggerServiceProvider;
use Rabbit\Plugin;
use Rabbit\Redirects\AdminNotice;
use Rabbit\Templates\TemplatesServiceProvider;
use Rabbit\Utils\Singleton;
use League\Container\Container;

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require dirname(__FILE__) . '/vendor/autoload.php';
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if (!class_exists('Books_Info_List_Table')) {
    class Books_Info_List_Table extends WP_List_Table {
        public function prepare_items() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'books_info';
            $query = "SELECT * FROM $table_name";

            $data = $wpdb->get_results($query, ARRAY_A);
            
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array($columns, $hidden, $sortable);

            usort($data, array(&$this, 'usort_reorder'));

            $this->items = $data;
        }

        public function get_columns() {
            $columns = array(
                'ID'      => 'ID',
                'post_id' => 'Post ID',
                'isbn'    => 'ISBN'
            );
            return $columns;
        }

        public function usort_reorder($a, $b) {
            $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'ID';
            $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
            
            $a_val = isset($a[$orderby]) ? $a[$orderby] : '';
            $b_val = isset($b[$orderby]) ? $b[$orderby] : '';

            $result = strcmp($a_val, $b_val);

            return ($order === 'asc') ? $result : -$result;
        }

        public function column_default($item, $column_name) {
            switch ($column_name) {
                case 'ID':
                case 'post_id':
                case 'isbn':
                    return $item[$column_name];
                default:
                    return print_r($item, true);
            }
        }
    }
}

/**
 * Class Yung_Book_Store
 * @package Yung_Book_Store
 */
class Yung_Book_Store extends Singleton
{
    /**
     * @var Container
     */
    private $application;

    /**
     * Yung_Book_Store constructor.
     */
    public function __construct()
    {
        $this->application = Application::get()->loadPlugin(__DIR__, __FILE__, 'config');
        $this->init();
    }

    public function init()
    {
        try {

            /**
             * Load service providers
             */
            $this->application->addServiceProvider(RedirectServiceProvider::class);
            $this->application->addServiceProvider(DatabaseServiceProvider::class);
            $this->application->addServiceProvider(TemplatesServiceProvider::class);
            $this->application->addServiceProvider(LoggerServiceProvider::class);

            /**
             * Load actions
             */
            add_action('init', [$this, 'register_book_post_type']);
            add_action('add_meta_boxes', [$this, 'add_isbn_meta_box']);
            add_action('save_post', [$this, 'save_isbn_meta_box']);
            add_action('admin_menu', [$this, 'register_books_info_menu_page']);

            /**
             * Activation hooks
             */
            $this->application->onActivation(function () {
                global $wpdb;
                $table_name = $wpdb->prefix . 'books_info';
                $charset_collate = $wpdb->get_charset_collate();

                $sql = "CREATE TABLE $table_name (
                    ID mediumint(9) NOT NULL AUTO_INCREMENT,
                    post_id bigint(20) UNSIGNED NOT NULL,
                    isbn varchar(13) NOT NULL,
                    PRIMARY KEY (ID)
                ) $charset_collate;";

                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);
            });

            /**
             * Deactivation hooks
             */
            $this->application->onDeactivation(function () {
                // Clear events, cache or something else
            });

            $this->application->boot(function (Plugin $plugin) {
                $plugin->loadPluginTextDomain();

            });

        } catch (Exception $e) {
            /**
             * Print the exception message to admin notice area
             */
            add_action('admin_notices', function () use ($e) {
                AdminNotice::permanent(['type' => 'error', 'message' => $e->getMessage()]);
            });

            /**
             * Log the exception to file
             */
            add_action('init', function () use ($e) {
                if ($this->application->has('logger')) {
                    $this->application->get('logger')->warning($e->getMessage());
                }
            });
        }
    }

    /**
     * Register post type
     */
    public function register_book_post_type() {
        $labels = array(
            'name'               => _x('Books', 'post type general name', 'domain-translate'),
            'singular_name'      => _x('Book', 'post type singular name', 'domain-translate'),
            'menu_name'          => _x('Books', 'admin menu', 'domain-translate'),
            'name_admin_bar'     => _x('Book', 'add new on admin bar', 'domain-translate'),
            'add_new'            => _x('Add New', 'book', 'domain-translate'),
            'add_new_item'       => __('Add New Book', 'domain-translate'),
            'new_item'           => __('New Book', 'domain-translate'),
            'edit_item'          => __('Edit Book', 'domain-translate'),
            'view_item'          => __('View Book', 'domain-translate'),
            'all_items'          => __('All Books', 'domain-translate'),
            'search_items'       => __('Search Books', 'domain-translate'),
            'parent_item_colon'  => __('Parent Books:', 'domain-translate'),
            'not_found'          => __('No books found.', 'domain-translate'),
            'not_found_in_trash' => __('No books found in Trash.', 'domain-translate')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'book'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments')
        );

        register_post_type('book', $args);

        $this->register_book_taxonomies();
    }

    /**
     * Register post taxonomy
     */
    public function register_book_taxonomies() {
        $labels = array(
            'name'              => _x('Publishers', 'taxonomy general name', 'domain-translate'),
            'singular_name'     => _x('Publisher', 'taxonomy singular name', 'domain-translate'),
            'search_items'      => __('Search Publishers', 'domain-translate'),
            'all_items'         => __('All Publishers', 'domain-translate'),
            'parent_item'       => __('Parent Publisher', 'domain-translate'),
            'parent_item_colon' => __('Parent Publisher:', 'domain-translate'),
            'edit_item'         => __('Edit Publisher', 'domain-translate'),
            'update_item'       => __('Update Publisher', 'domain-translate'),
            'add_new_item'      => __('Add New Publisher', 'domain-translate'),
            'new_item_name'     => __('New Publisher Name', 'domain-translate'),
            'menu_name'         => __('Publisher', 'domain-translate')
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'publisher')
        );

        register_taxonomy('publisher', array('book'), $args);

        $labels = array(
            'name'              => _x('Authors', 'taxonomy general name', 'domain-translate'),
            'singular_name'     => _x('Author', 'taxonomy singular name', 'domain-translate'),
            'search_items'      => __('Search Authors', 'domain-translate'),
            'all_items'         => __('All Authors', 'domain-translate'),
            'parent_item'       => __('Parent Author', 'domain-translate'),
            'parent_item_colon' => __('Parent Author:', 'domain-translate'),
            'edit_item'         => __('Edit Author', 'domain-translate'),
            'update_item'       => __('Update Author', 'domain-translate'),
            'add_new_item'      => __('Add New Author', 'domain-translate'),
            'new_item_name'     => __('New Author Name', 'domain-translate'),
            'menu_name'         => __('Author', 'domain-translate')
        );

        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'author')
        );

        register_taxonomy('author', array('book'), $args);
    }

    /**
     * Register post meta
     */
    public function add_isbn_meta_box() {
        add_meta_box(
            'book_isbn_meta_box',
            __('Book ISBN', 'domain-translate'),
            [$this, 'display_isbn_meta_box'],
            'book',
            'side',
            'high'
        );
    }

    /**
     * Return post meta
     */
    public function display_isbn_meta_box($post) {
        wp_nonce_field('book_isbn_meta_box', 'book_isbn_meta_box_nonce');

        $isbn = get_post_meta($post->ID, '_book_isbn', true);

        echo '<label for="book_isbn">';
        _e('ISBN', 'domain-translate');
        echo '</label> ';
        echo '<input type="text" id="book_isbn" name="book_isbn" value="' . esc_attr($isbn) . '" size="13" />';
    }

    /**
     * Save post meta
     */
    public function save_isbn_meta_box($post_id) {
        if (!isset($_POST['book_isbn_meta_box_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['book_isbn_meta_box_nonce'], 'book_isbn_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $isbn = sanitize_text_field($_POST['book_isbn']);

        update_post_meta($post_id, '_book_isbn', $isbn);

        global $wpdb;
        $table_name = $wpdb->prefix . 'books_info';

        $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_id = %d", $post_id));

        if ($existing) {
            $wpdb->update(
                $table_name,
                array('isbn' => $isbn),
                array('post_id' => $post_id),
                array('%s'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'isbn' => $isbn
                ),
                array(
                    '%d',
                    '%s'
                )
            );
        }
    }

    /**
     * Add menu page
     */
    public function register_books_info_menu_page() {
        add_menu_page(
            __('Books Info', 'domain-translate'),
            __('Books Info', 'domain-translate'),
            'manage_options',
            'books-info',
            [$this, 'display_books_info_table'],
            'dashicons-book',
            6
        );
    }

    /**
     * Return book data
     */
    public function display_books_info_table() {
        $list_table = new Books_Info_List_Table();
        $list_table->prepare_items();
        echo '<div class="wrap"><h2>' . __('Books Info', 'domain-translate') . '</h2>';
        $list_table->display();
        echo '</div>';
    }

    /**
     * @return Container
     */
    public function getApplication()
    {
        return $this->application;
    }
}

/**
 * Returns the main instance of Yung_Book_Store.
 *
 * @return Yung_Book_Store
 */
function yung_book_info()
{
    return Yung_Book_Store::get();
}

yung_book_info();
