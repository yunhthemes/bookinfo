<?php

namespace YungBooksPlugin;

class CustomPostType {

    public static function register() {
        register_post_type('book', [
            'labels' => [
                'name' => __('Books', 'wp-books-plugin'),
                'singular_name' => __('Book', 'wp-books-plugin'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor'],
            'rewrite' => ['slug' => 'books'],
            'show_in_rest' => true,
        ]);

        register_taxonomy('publisher', 'book', [
            'labels' => [
                'name' => __('Publishers', 'wp-books-plugin'),
                'singular_name' => __('Publisher', 'wp-books-plugin'),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);

        register_taxonomy('author', 'book', [
            'labels' => [
                'name' => __('Authors', 'wp-books-plugin'),
                'singular_name' => __('Author', 'wp-books-plugin'),
            ],
            'hierarchical' => false,
            'show_in_rest' => true,
        ]);

        add_action('add_meta_boxes', [self::class, 'add_isbn_meta_box']);
        add_action('save_post', [self::class, 'save_isbn_meta_box']);
    }

    public static function add_isbn_meta_box() {
        add_meta_box(
            'isbn_meta_box',
            __('ISBN Number', 'wp-books-plugin'),
            [self::class, 'render_isbn_meta_box'],
            'book',
            'side'
        );
    }

    public static function render_isbn_meta_box($post) {
        wp_nonce_field('save_isbn_meta_box_data', 'isbn_meta_box_nonce');
        $value = get_post_meta($post->ID, '_isbn', true);
        echo '<label for="isbn_field">' . __('ISBN Number', 'wp-books-plugin') . '</label>';
        echo '<input type="text" id="isbn_field" name="isbn_field" value="' . esc_attr($value) . '" size="25" />';
    }

    public static function save_isbn_meta_box($post_id) {
        if (!isset($_POST['isbn_meta_box_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['isbn_meta_box_nonce'], 'save_isbn_meta_box_data')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['post_type']) && 'book' == $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        if (!isset($_POST['isbn_field'])) {
            return;
        }

        $isbn = sanitize_text_field($_POST['isbn_field']);
        update_post_meta($post_id, '_isbn', $isbn);

        global $wpdb;
        $table_name = $wpdb->prefix . 'books_info';
        $wpdb->replace($table_name, [
            'post_id' => $post_id,
            'isbn' => $isbn
        ]);
    }
}
