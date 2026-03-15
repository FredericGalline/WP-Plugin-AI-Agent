<?php

/**
 * Admin menu pour le plugin AI Connector
 *
 * Ce fichier gère uniquement la structure du menu d'administration
 * et les inclusions des différentes pages.
 *
 * @package WP_Plugin_AI_Connector
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enregistre les menus et sous-menus pour le plugin AI Connector
 */
function ai_connector_register_admin_menus()
{
    // Menu principal
    add_menu_page(
        __('AI Connector', 'ai-connector'),
        __('AI Connector', 'ai-connector'),
        'manage_options',
        'ai-connector',
        'ai_connector_render_dashboard_page',
        'dashicons-admin-links',
        80
    );

    // Sous-menu : Tableau de bord (duplique le menu principal pour clarté)
    add_submenu_page(
        'ai-connector',
        __('Tableau de bord', 'ai-connector'),
        __('Tableau de bord', 'ai-connector'),
        'manage_options',
        'ai-connector',
        'ai_connector_render_dashboard_page'
    );

    // Sous-menu : Connecteurs IA
    add_submenu_page(
        'ai-connector',
        __('Connecteurs IA', 'ai-connector'),
        __('Connecteurs IA', 'ai-connector'),
        'manage_options',
        'ai-connector-connectors',
        'ai_connector_render_connectors_page'
    );

    // Sous-menu : Testeur de Prompt IA
    add_submenu_page(
        'ai-connector',
        __('Testeur de Prompt IA', 'ai-connector'),
        __('Testeur IA', 'ai-connector'),
        'manage_options',
        'ai-connector-tester',
        'ai_connector_render_tester_page'
    );

    // Sous-menu : Diagnostic IA
    add_submenu_page(
        'ai-connector',
        __('Diagnostic IA', 'ai-connector'),
        __('Diagnostic IA', 'ai-connector'),
        'manage_options',
        'ai-connector-diagnostic',
        'ai_connector_render_diagnostic_page'
    );
}
add_action('admin_menu', 'ai_connector_register_admin_menus');

/**
 * Rendu de la page Tableau de bord
 */
function ai_connector_render_dashboard_page()
{
    // Pour cette page, inclure simplement le fichier car il gère son propre affichage
    require_once plugin_dir_path(__FILE__) . '/../admin/dashboard.php';
}

/**
 * Rendu de la page Connecteurs IA
 */
function ai_connector_render_connectors_page()
{
    require_once plugin_dir_path(__FILE__) . '/../admin/models-form.php';

    $providers_config = ai_connector_get_providers_config();

    $active_model = ai_connector_get_active_model_string();

    echo '<div class="wrap ai-connector-admin">';
    echo '<h1>' . esc_html__('Connecteurs IA', 'ai-connector') . '</h1>';
    echo '<p class="description">' . esc_html__('Configurez vos fournisseurs d\'IA et choisissez le modèle actif.', 'ai-connector') . '</p>';

    settings_errors('ai_connector_connectors');

    AI_Connector_Admin_Form::render_form($providers_config, $active_model);

    echo '</div>';
}

/**
 * Rendu de la page Testeur de Prompt IA
 */
function ai_connector_render_tester_page()
{
    require_once plugin_dir_path(__FILE__) . '/../admin/tester.php';

    $tester = new AI_Connector_Admin_Tester();
    $tester->render_tester_page();
}

/**
 * Rendu de la page Diagnostic IA
 */
function ai_connector_render_diagnostic_page()
{
    require_once plugin_dir_path(__FILE__) . '/../admin/diagnostic.php';

    $diagnostic = new AI_Connector_Admin_Diagnostic();
    $diagnostic->render_diagnostic_page();
}
