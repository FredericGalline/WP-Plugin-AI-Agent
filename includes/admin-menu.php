<?php

/**
 * Admin menu for AI Agent plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add a menu item for the AI Agent plugin
 */
function wp_plugin_ai_agent_add_admin_menu()
{
    add_menu_page(
        __('AI Agent', 'ai-agent'),
        __('AI Agent', 'ai-agent'),
        'manage_options',
        'ai-agent',
        'wp_plugin_ai_agent_admin_page',
        'dashicons-art',
        80
    );
}
add_action('admin_menu', 'wp_plugin_ai_agent_add_admin_menu');

/**
 * Display the admin page for the AI Agent plugin
 */
function wp_plugin_ai_agent_admin_page()
{
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('AI Agent Settings', 'ai-agent') . '</h1>';
    echo '<p>' . esc_html__('Welcome to the AI Agent plugin settings page.', 'ai-agent') . '</p>';
    echo '</div>';
}
