<?php
/*
Plugin Name: just_a_shopping_cart
Plugin URI: https://github.com/barrinalo/just_a_shopping_cart
Description: A simple shopping cart allowing admins to create items in a catalog and for users to add items to their shopping cart. The payment processing part is deliberately omitted so as to allow integration with any payment processing option.
Version: 1.0
Author: David Chong
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

$plugin_prefix = 'just_a_shopping_cart';

function just_a_shopping_cart_install() {
	global $wpdb;
	//global $plugin_prefix;
	$plugin_prefix = 'just_a_shopping_cart';
	$catalog_table = $wpdb->prefix . $plugin_prefix . 'catalog';
	$transaction_table = $wpdb->prefix . $plugin_prefix . 'transaction';
	$catalogsql = "CREATE TABLE IF NOT EXISTS $catalog_table (
					PID INT NOT NULL AUTO_INCREMENT,
					PRIMARY KEY(PID),
					Item_Name VARCHAR(255),
					Description TEXT,
					Price FLOAT,
					Image_Url VARCHAR(255),
					Display BOOL);";
	$transactionsql = "CREATE TABLE IF NOT EXISTS $transaction_table (
					PID INT NOT NULL AUTO_INCREMENT,
					PRIMARY KEY(PID),
					Customer_ID INT,
					Item_ID INT,
					Quantity INT,
					Finalized BOOL);";
	$wpdb->query($catalogsql);
	$wpdb->query($transactionsql);
}
register_activation_hook(__FILE__, 'just_a_shopping_cart_install');

function just_a_shopping_cart_uninstall() {
	global $wpdb;
	//global $plugin_prefix;
	$plugin_prefix = 'just_a_shopping_cart';
	$catalog_table = $wpdb->prefix . $plugin_prefix . 'catalog';
	$transaction_table = $wpdb->prefix . $plugin_prefix . 'transaction';
	$wpdb->query("DROP TABLE IF EXISTS $catalog_table");
	$wpdb->query("DROP TABLE IF EXISTS $transaction_table");
}
register_uninstall_hook(__FILE__, 'just_a_shopping_cart_uninstall');

function update_catalog() {
	global $wpdb;
	//global $plugin_prefix;
	$plugin_prefix = 'just_a_shopping_cart';
	if(!current_user_can('manage_options')) die('Forbidden');
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$data = array();
		foreach($_POST as $key => $val) {
			$pid = explode("-",$key)[0];
			$term = explode("-",$key)[1];
			if(!array_key_exists($pid,$data)) $data[$pid] = array();
			$data[$pid][$term] = sanitize_text_field($val);
		}
		foreach($data as $key => $val) {
			if ($val["Delete"] == "delete") {
				$wpdb->query("DELETE FROM " . $wpdb->prefix . $plugin_prefix . "catalog WHERE PID=$key");
			}
			else if ($key != "action") {
				$pid = $key;
				if(isset($_FILES[$pid . "-Image_Url"])) $data[$pid]["Image_Url"] = wp_handle_upload($_FILES[$pid . "-Image_Url"], array('test_form' => FALSE))["url"];
				else $data[$pid]["Image_Url"] = "";
				$sql = "INSERT INTO " . $wpdb->prefix . $plugin_prefix . "catalog (PID, Item_Name, Description, Price, Display";
				if ($data[$pid]["Image_Url"] != "") $sql .= ",Image_Url)";
				else $sql .= ")";
				$sql .= " VALUES ($pid,'" . $data[$pid]["Item_Name"] . "','" . $data[$pid]["Description"] . "'," . $data[$pid]["Price"] . ",";
				if ($data[$pid]["Display"] == "display") $sql .= "1";
				else $sql .= "0";
				if($data[$pid]["Image_Url"] != "") $sql .= ",'" . $data[$pid]["Image_Url"] . "')";
				else $sql .= ")";
				$sql .= " ON DUPLICATE KEY UPDATE Item_Name='" . $data[$pid]["Item_Name"] . "', Description='" . $data[$pid]["Description"] . "', Price=" . $data[$pid]["Price"];
				if ($data[$pid]["Display"] == "display") $sql .= ", Display=1";
				else $sql .= ", Display=0";
				if($data[$pid]["Image_Url"] != "") $sql .= ",Image_Url='" . $data[$pid]["Image_Url"] . "';";
				else $sql .= ";";
				$wpdb->query($sql);
			}
		}
		
	}
	if(wp_redirect(admin_url('admin.php?page=jasc'))) exit;
}

function just_a_shopping_cart_admin_menu_html() {
	if (!current_user_can('manage_options')) return;
	global $wpdb;
	//global $plugin_prefix;
	$plugin_prefix = 'just_a_shopping_cart';
	$catalog_table = $wpdb->prefix . $plugin_prefix . 'catalog';
	$items = $wpdb->get_results("SELECT * FROM $catalog_table", ARRAY_A);
	echo '<div class="wrap">';
	echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
	echo '<form enctype="multipart/form-data" action="admin-post.php" method="post">';
	echo '<input type="hidden" name="action" value="update_catalog">';
	echo '<right><input type="submit" value="Update Catalog" class="button button-primary"></right>';
	echo '<table><tr><th>ID</th><th>Name</th><th>Description</th><th>Image</th><th>Price</th><th>Display<th><th>Delete</th></tr>';
	foreach ($items as $catalogitem) {
		$pid = $catalogitem['PID'];
		echo "<tr><td>$pid</td>";
		echo "<td><input type='text' name='$pid-Item_Name' value='" . $catalogitem['Item_Name'] . " required='required''></td>";
		echo "<td><input type='text' name='$pid-Description' value='" . $catalogitem['Description'] . "'></td>";
		echo '<td><img src="' . $catalogitem['Image_Url'] . '" width="300">';
		echo "<br /><input type='file' name='$pid-Image_Url'></td>";
		echo "<td>£<input type='number' step='0.01' name='$pid-Price' value='" . $catalogitem['Price'] . "' required='required' min='0'></td>";
		if($catalogitem['Display']) echo "<td><input type='checkbox' name='$pid-Display' value='display' checked></td>";
		else echo "<td><input type='checkbox' name='$pid-Display' value='display'></td>";
		echo "<td><input type='checkbox' name='$pid-Delete' value='delete'></td></tr>";
	}
	echo '</table></form></div>';
}
add_action('admin_post_update_catalog','update_catalog');

function add_new_item() {
	global $wpdb;
	//global $plugin_prefix;
	$plugin_prefix = 'just_a_shopping_cart';
	if(!current_user_can('manage_options')) die('Forbidden');
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$itemname = sanitize_text_field($_POST['Item_Name']);
		$description = sanitize_text_field($_POST['Description']);
		$display = sanitize_text_field($_POST['Display']);
		$price = sanitize_text_field($_POST['Price']);
		if(isset($_FILES["Image_Url"])) {
			if(getimagesize($_FILES["Image_Url"]["tmp_name"]) !== false) {
				$filename = wp_handle_upload($_FILES["Image_Url"],array('test_form' => FALSE))["url"];
			}
		}
		$sql = 'INSERT INTO ' . $wpdb->prefix . $plugin_prefix . 'catalog (Item_Name, Description, Price, Image_Url, Display)';
		$sql .= " VALUES ('$itemname','$description',$price,'$filename',";
		if ($display == 'display') $sql .= '1);';
		else $sql .= '0);';
		$wpdb->query($sql);
	}
	if(wp_redirect(admin_url('admin.php?page=jasc'))) exit;
}

function just_a_shopping_cart_add_item_html() {
	if (!current_user_can('manage_options')) return;
	echo '<div class="wrap">';
	echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
	echo '<form enctype="multipart/form-data" action="admin-post.php" method="post">';
	echo '<input type="hidden" name="action" value="add_new_item">';
	echo 'Item Name: <input type="text" name="Item_Name" value="" required="required"><br />';
	echo 'Description: <input type="text" name="Description" value=""><br />';
	echo 'Image: <input type="file" name="Image_Url"><br />';
	echo 'Price: <input type="number" step="0.01" name="Price" required="required" min='0'><br />';
	echo 'Display: <input type="checkbox" name="Display" value="display" checked><br />';
	echo '<input type="submit" value="Add Item" class="button button-primary">';
	echo '</form></div>';
}
add_action('admin_post_add_new_item','add_new_item');

function just_a_shopping_cart_admin_menu() {
	add_menu_page('Manage Shopping Cart Catalog', 'Manage Catalog', 'manage_options','jasc','just_a_shopping_cart_admin_menu_html');
	add_submenu_page('jasc','Add Item','Add Item','manage_options','add_item','just_a_shopping_cart_add_item_html');
}
add_action('admin_menu', 'just_a_shopping_cart_admin_menu');
?>
