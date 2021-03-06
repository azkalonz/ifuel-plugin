<?php

/**
 * Plugin Name: iFuel
 * Plugin URI: https://ifuel.com.ph/
 * Description: iFuel core plugin.
 * Version: 1.0
 * Author: Mark Judaya
 * Author URI: http://github.com/azkalonz/
 **/

define('PLUGIN_ROOT', plugin_dir_path(__FILE__));
define('INVESTOR_POST_TYPE', 'investor');
define('INVESTOR_TAXONOMY', 'investor_location');
define('TEMP_DIR', PLUGIN_ROOT . 'database/temp');
define('SALES_TALLY_POST_TYPE', 'sales_tally');
define('BRANCH_MANAGER_ROLE', 'Branch Manager');
define('INVENTORY_POST_TYPE', 'ifuel_inventory');


require_once PLUGIN_ROOT . '/includes/class-ifuel-investors-seeder.php';
require_once PLUGIN_ROOT . '/includes/class-ifuel-investor-posttype.php';
require_once PLUGIN_ROOT . '/includes/class-ifuel-sales-tally-posttype.php';
require_once PLUGIN_ROOT . '/includes/class-ifuel-inventory-posttype.php';

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

function activate_plugin_name()
{
    // init_post_type();
    // InvestorsSeeder::seed();
}

function deactivate_plugin_name()
{
}

function init_post_type()
{
    InventoryPosttype::register();
    InventoryPosttype::hooks();
    InventoryPosttype::shortcodes();

    InvestorPostType::register();
    InvestorPostType::hooks();

    SalesTallyPostType::register();
    SalesTallyPostType::hooks();
    SalesTallyPostType::shortcodes();
}

add_filter('acf/settings/remove_wp_meta_box', '__return_false', 20);

add_action('init', 'init_post_type');

register_activation_hook(__FILE__, 'activate_plugin_name');
register_deactivation_hook(__FILE__, 'deactivate_plugin_name');

function my_enqueue($hook)
{
    // Only add to the edit.php admin page.
    // See WP docs.
    wp_enqueue_script('my_custom_script', '/jquery.mjs.pmxe_nestedSortable.js');
}

add_action('admin_enqueue_scripts', 'my_enqueue');