<?php

/**
 * Admin menu pour le plugin AI Agent
 * 
 * Ce fichier gère uniquement la structure du menu d'administration
 * et les inclusions des différentes pages.
 * 
 * @package WP_Plugin_AI_Agent
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajoute les éléments de menu pour le plugin AI Agent
 */
function wp_plugin_ai_agent_add_admin_menu()
{
    // Menu principal
    add_menu_page(
        __('AI Agent', 'ai-agent'),
        __('AI Agent', 'ai-agent'),
        'manage_options',
        'ai-agent',
        'wp_plugin_ai_agent_dashboard_page',
        'dashicons-art',
        80
    );

    // Sous-menu pour le tableau de bord (même page que le menu principal)
    add_submenu_page(
        'ai-agent',
        __('Tableau de bord', 'ai-agent'),
        __('Tableau de bord', 'ai-agent'),
        'manage_options',
        'ai-agent',
        'wp_plugin_ai_agent_dashboard_page'
    );

    // Vous pourrez ajouter d'autres sous-menus ici
}
add_action('admin_menu', 'wp_plugin_ai_agent_add_admin_menu');

/**
 * Enregistre et charge les styles CSS et scripts JS pour les pages d'administration
 */
function wp_plugin_ai_agent_admin_enqueue_scripts($hook)
{
    // Vérifier si nous sommes sur une page de notre plugin
    if (strpos($hook, 'ai-agent') === false) {
        return;
    }

    // Enregistrer et charger le CSS pour le tableau de bord
    wp_register_style(
        'ai-agent-admin-dashboard',
        plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin-dashboard.css',
        array(),
        WP_PLUGIN_AI_AGENT_VERSION
    );
    wp_enqueue_style('ai-agent-admin-dashboard');
}
add_action('admin_enqueue_scripts', 'wp_plugin_ai_agent_admin_enqueue_scripts');

/**
 * Fonction qui charge la page de tableau de bord
 */
function wp_plugin_ai_agent_dashboard_page()
{
    // Inclusion du fichier de la page tableau de bord
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/pages/dashboard.php';
}
