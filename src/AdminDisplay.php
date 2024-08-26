<?php

namespace YungBooksPlugin;

use WP_List_Table;

class AdminDisplay {

    public static function register_admin_page() {
        add_menu_page(
            __('Books Info', 'wp-books-plugin'),
            __('Books Info', 'wp-books-plugin'),
            'manage_options',
            'books-info',
            [self::class, 'render_books_info_page'],
            'dashicons-book'
        );
    }

    public static function render_books_info_page() {
        echo '<div class="wrap"><h1>' . __('Books Info', 'wp-books-plugin') . '</h1>';
        $books_list_table = new Books_List_Table();
        $books_list_table->prepare_items();
        $books_list_table->display();
        echo '</div>';
    }
}

class Books_List_Table extends WP_List_Table {
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