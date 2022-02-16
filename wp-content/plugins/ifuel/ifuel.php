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

require_once PLUGIN_ROOT . '/includes/class-ifuel-investors-seeder.php';
require_once PLUGIN_ROOT . '/includes/class-ifuel-investor-posttype.php';

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
    InvestorPostType::register();
    InvestorPostType::hooks();
}

add_action('init', 'init_post_type');

register_activation_hook(__FILE__, 'activate_plugin_name');
register_deactivation_hook(__FILE__, 'deactivate_plugin_name');