<?php
/**
 * Plugin Name: SaaS Profile & Lead Engine
 * Plugin URI: https://yourdomain.com
 * Description: A complete SaaS system for Link-in-Bio, Digital Business Cards, and Lead Generation.
 * Version: 1.0.0
 * Author: Elite SaaS Architect
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Define Plugin Constants
define( 'SAAS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAAS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// 1. Core Data Structures & Routing
require_once SAAS_PLUGIN_DIR . 'post-types.php';

// 2. Dashboard & Auth Logic
require_once SAAS_PLUGIN_DIR . 'dashboard.php';
require_once SAAS_PLUGIN_DIR . 'auth.php';
require_once SAAS_PLUGIN_DIR . 'ajax-handlers.php';

// 3. System Engines
require_once SAAS_PLUGIN_DIR . 'analytics.php';
require_once SAAS_PLUGIN_DIR . 'leads.php';
require_once SAAS_PLUGIN_DIR . 'payments.php';
require_once SAAS_PLUGIN_DIR . 'affiliates.php';
require_once SAAS_PLUGIN_DIR . 'messaging.php';
require_once SAAS_PLUGIN_DIR . 'licenses.php';
require_once SAAS_PLUGIN_DIR . 'utils.php';

// 4. Admin Interface
require_once SAAS_PLUGIN_DIR . 'admin-settings.php';
require_once SAAS_PLUGIN_DIR . 'admin-profile-manager.php';
require_once SAAS_PLUGIN_DIR . 'blocks.php'; // Gutenberg Integration

// Plugin Activation Hook
register_activation_hook( __FILE__, 'saas_plugin_activation' );
function saas_plugin_activation() {
    // 1. Create Analytics Table
    Saas_Analytics::create_table();

    // 2. Flush Rewrite Rules
    saas_add_rewrite_rules();
    flush_rewrite_rules();
}

// Plugin Deactivation Hook
register_deactivation_hook( __FILE__, 'saas_plugin_deactivation' );
function saas_plugin_deactivation() {
    flush_rewrite_rules();
}
