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
 * Enregistre les menus et sous-menus pour le plugin AI Agent
 */
function ai_agent_register_admin_menus()
{
    // Menu principal
    add_menu_page(
        __('AI Agent', 'ai-agent'),
        __('AI Agent', 'ai-agent'),
        'manage_options',
        'ai-agent',
        'ai_agent_render_dashboard_page',
        'dashicons-robot',
        80
    );

    // Sous-menu : Tableau de bord (duplique le menu principal pour clarté)
    add_submenu_page(
        'ai-agent',
        __('Tableau de bord', 'ai-agent'),
        __('Tableau de bord', 'ai-agent'),
        'manage_options',
        'ai-agent',
        'ai_agent_render_dashboard_page'
    );

    // Sous-menu : Connecteurs IA
    add_submenu_page(
        'ai-agent',
        __('Connecteurs IA', 'ai-agent'),
        __('Connecteurs IA', 'ai-agent'),
        'manage_options',
        'ai-agent-connectors',
        'ai_agent_render_connectors_page'
    );

    // Sous-menu : Testeur de Prompt IA
    add_submenu_page(
        'ai-agent',
        __('Testeur de Prompt IA', 'ai-agent'),
        __('Testeur IA', 'ai-agent'),
        'manage_options',
        'ai-agent-tester',
        'ai_agent_render_tester_page'
    );

    // Sous-menu : Diagnostic IA
    add_submenu_page(
        'ai-agent',
        __('Diagnostic IA', 'ai-agent'),
        __('Diagnostic IA', 'ai-agent'),
        'manage_options',
        'ai-agent-diagnostic',
        'ai_agent_render_diagnostic_page'
    );
}
add_action('admin_menu', 'ai_agent_register_admin_menus');

/**
 * Rendu de la page Tableau de bord
 */
function ai_agent_render_dashboard_page()
{
    // Pour cette page, inclure simplement le fichier car il gère son propre affichage
    require_once plugin_dir_path(__FILE__) . '/../admin/dashboard.php';
}

/**
 * Rendu de la page Connecteurs IA
 */
function ai_agent_render_connectors_page()
{
    // Pour cette page, nous devons appeler la méthode statique de la classe AI_Agent_Admin_Form
    require_once plugin_dir_path(__FILE__) . '/../admin/models-form.php';

    // Récupérer la configuration des fournisseurs
    $providers_config = require_once plugin_dir_path(__FILE__) . '/../services/ai/providers-config.php';

    // Récupérer le modèle actif
    $active_model = get_option('ai_agent_active_model', '');

    // Appeler la méthode de rendu du formulaire
    AI_Agent_Admin_Form::render_form($providers_config, $active_model);
}

/**
 * Rendu de la page Testeur de Prompt IA
 */
function ai_agent_render_tester_page()
{
    // Inclure le bon fichier depuis le dossier admin
    require_once plugin_dir_path(__FILE__) . '/../admin/tester.php';

    // Instancier la classe et appeler sa méthode de rendu
    $tester = new AI_Redactor_Admin_Tester();
    $tester->render_tester_page();
}

/**
 * Rendu de la page Diagnostic IA
 */
function ai_agent_render_diagnostic_page()
{
    // Charger le fichier de diagnostic
    require_once plugin_dir_path(__FILE__) . '/../admin/diagnostic.php';

    // Instancier la classe et appeler sa méthode de rendu
    $diagnostic = new AI_Redactor_Admin_Diagnostic();
    $diagnostic->render_diagnostic_page();
}
