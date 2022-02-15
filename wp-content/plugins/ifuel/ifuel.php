<?php

/**
 * Plugin Name: iFuel
 * Plugin URI: https://ifuel.com.ph/
 * Description: iFuel core plugin.
 * Version: 1.0
 * Author: Mark Judaya
 * Author URI: http://github.com/azkalonz/
 **/

define("PLUGIN_ROOT", plugin_dir_path(__FILE__));

require_once PLUGIN_ROOT . '/includes/class-ifuel-investors-seeder.php';
require_once PLUGIN_ROOT . '/includes/class-ifuel-investor-posttype.php';

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

function activate_plugin_name()
{
    InvestorsSeeder::seed();
}

function deactivate_plugin_name()
{
}

add_action('init', function () {
    InvestorPostType::register();
    InvestorPostType::hooks();
});

register_activation_hook(__FILE__, 'activate_plugin_name');
register_deactivation_hook(__FILE__, 'deactivate_plugin_name');