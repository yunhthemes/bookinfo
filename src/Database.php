<?php

namespace YungBooksPlugin;

class Database {

    public static function install() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'books_info';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            ID bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            isbn varchar(13) NOT NULL,
            PRIMARY KEY  (ID)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
