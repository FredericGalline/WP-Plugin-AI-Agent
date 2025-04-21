<?php

/**
 * Plugin Name: AI Agent
 * Description: A WordPress plugin that acts as an AI agent to handle prompts and responses from various AI providers.
 * Version: 1.0.0
 * Author: Frederic Galline
 * Author URI: https://example.com
 * License: GPL2
 * Text Domain: wp-plugin-ai-agent
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants for the plugin
if (!defined('WP_PLUGIN_AI_AGENT_PATH')) {
    define('WP_PLUGIN_AI_AGENT_PATH', plugin_dir_path(__FILE__));
}
if (!defined('WP_PLUGIN_AI_AGENT_URL')) {
    define('WP_PLUGIN_AI_AGENT_URL', plugin_dir_url(__FILE__));
}
if (!defined('WP_PLUGIN_AI_AGENT_VERSION')) {
    define('WP_PLUGIN_AI_AGENT_VERSION', '1.0.0');
}

// Include the AI loader file
require_once plugin_dir_path(__FILE__) . 'includes/loader.php';
require_once plugin_dir_path(__FILE__) . 'services/ai/ai-loader.php';
