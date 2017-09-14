<?php
/*
Plugin: just_a_shopping_cart
Plugin URI: https://github.com/barrinalo/just_a_shopping_cart
Description: A simple shopping cart allowing admins to create items in a catalog and for users to add items to their shopping cart. The payment processing part is deliberately omitted so as to allow integration with any payment processing option.
Version: 1.0
Author: David Chong

just_a_shopping_cart is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
just_a_shopping_cart is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with just_a_shopping_cart. If not, see https://www.gnu.org/licenses/gpl.html.
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

$plugin_prefix = 'just_a_shopping_cart';

function just_a_shopping_cart_install() {
	global $wpdb;
	global $plugin_prefix;
	$catalog_table = $wpdb->prefix . $plugin_prefix . 'catalog';
	$transaction_table = $wpdb->prefix . $plugin_prefix . 'transaction';
	$catalogsql = 'CREATE TABLE IF NOT EXISTS $catalog_table (
					PID INT NOT NULL AUTO_INCREMENT,
					PRIMARY KEY(PID),
					Item_Name VARCHAR(255),
					Description TEXT,
					Price FLOAT,
					Image_Url VARCHAR(255),
					Display BOOL);';
	$transactionsql = 'CREATE TABLE IF NOT EXISTS $transaction_table (
					PID INT NOT NULL AUTO_INCREMENT,
					PRIMARY KEY(PID),
					Customer_ID INT,
					Item_ID INT,
					Quantity INT,
					Finalized BOOL);';
	dbDelta($catalogsql);
	dbDelta($transactionsql);
}
register_activation_hook(__FILE__, 'just_a_shopping_cart_install');

function just_a_shopping_cart_uninstall() {
	global $wpdb;
	global $plugin_prefix;
	$catalog_table = $wpdb->prefix . $plugin_prefix . 'catalog';
	$transaction_table = $wpdb->prefix . $plugin_prefix . 'transaction';
	$wpdb->query('DROP TABLE IF EXISTS $catalog_table');
	$wpdb->query('DROP TABLE IF EXISTS $transaction_table');
}
register_uninstall_hook(__FILE__, 'just_a_shopping_cart_uninstall');

function just_a_shopping_cart_admin_menu_html() {
	if (!current_user_can('manage_options')) return;
	echo '<div class="wrap">';
	echo '<h1>' . esc_html(get_admin_page_title()); . '</h1>';
	echo '</div>';
}

function just_a_shopping_cart_admin_menu() {
	add_menu_page('Manage Shopping Cart Catalog', 'Manage Catalog', 'manage_options','jasc','just_a_shopping_cart_admin_menu_html');
}
add_action('admin_menu', 'just_a_shopping_cart_admin_menu');
?>
