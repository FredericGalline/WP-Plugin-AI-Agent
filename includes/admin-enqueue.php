<?php

/**
 * Gestion des fichiers CSS et JS pour le plugin AI Agent
 *
 * Ce fichier s'occupe de l'enregistrement et du chargement des assets du plugin
 * (CSS, JavaScript) dans l'administration WordPress.
 *
 * @package WP_Plugin_AI_Agent
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enregistre et charge les styles CSS pour l'administration
 */
function ai_agent_enqueue_admin_styles($hook)
{
    // Définir l'URL de base des assets
    $css_base_url = plugin_dir_url(dirname(__FILE__)) . 'assets/css/';
    $plugin_version = defined('WP_PLUGIN_AI_AGENT_VERSION') ? WP_PLUGIN_AI_AGENT_VERSION : '1.0.0';

    // Identifier les pages du plugin
    $is_ai_agent_page = strpos($hook, 'ai-agent') !== false;

    // Charger les styles sur toutes les pages du plugin
    if ($is_ai_agent_page) {
        // CSS commun pour toutes les pages du plugin
        wp_enqueue_style(
            'ai-agent-admin-common',
            $css_base_url . 'admin-dashboard.css',
            array(),
            $plugin_version
        );
    }

    // Charger les styles spécifiques pour chaque page
    if ($hook === 'ai-agent_page_ai-agent-diagnostic') {
        wp_enqueue_style(
            'ai-agent-admin-diagnostic',
            $css_base_url . 'admin-diagnostic.css',
            array('ai-agent-admin-common'),
            $plugin_version
        );
    }

    if ($hook === 'ai-agent_page_ai-agent-connectors') {
        wp_enqueue_style(
            'ai-agent-admin-form',
            $css_base_url . 'admin-models-form.css',
            array('ai-agent-admin-common'),
            $plugin_version
        );
    }

    if ($hook === 'ai-agent_page_ai-agent-tester') {
        wp_enqueue_style(
            'ai-agent-admin-tester',
            $css_base_url . 'admin-tester.css',
            array('ai-agent-admin-common'),
            $plugin_version
        );
    }
}
add_action('admin_enqueue_scripts', 'ai_agent_enqueue_admin_styles');

/**
 * Enregistre et charge les scripts JavaScript pour l'administration
 */
function ai_agent_enqueue_admin_scripts($hook)
{
    // Définir l'URL de base des assets
    $js_base_url = plugin_dir_url(dirname(__FILE__)) . 'assets/js/';
    $plugin_version = defined('WP_PLUGIN_AI_AGENT_VERSION') ? WP_PLUGIN_AI_AGENT_VERSION : '1.0.0';

    // Identifier les pages du plugin
    $is_ai_agent_page = strpos($hook, 'ai-agent') !== false;

    // Ajouter jQuery en dépendance pour tous les scripts
    if ($is_ai_agent_page) {
        wp_enqueue_script('jquery');
    }

    // Charger les scripts spécifiques pour chaque page
    if ($hook === 'ai-agent_page_ai-agent-diagnostic') {
        wp_enqueue_script(
            'ai-agent-admin-diagnostic',
            $js_base_url . 'admin-diagnostic.js',
            array('jquery'),
            $plugin_version,
            true
        );

        // Localiser le script pour les traductions et variables
        wp_localize_script('ai-agent-admin-diagnostic', 'aiAgentData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_agent_diagnostic_nonce'),
            'i18n' => array(
                'testing' => __('Test en cours...', 'ai-agent'),
                'success' => __('Succès', 'ai-agent'),
                'error' => __('Erreur', 'ai-agent'),
                'copied' => __('Copié !', 'ai-agent')
            )
        ));
    }

    if ($hook === 'ai-agent_page_ai-agent-tester') {
        wp_enqueue_script(
            'ai-agent-admin-tester',
            $js_base_url . 'ai-admin-tester.js',
            array('jquery'),
            $plugin_version,
            true
        );

        // Localiser le script pour les traductions et variables
        wp_localize_script('ai-agent-admin-tester', 'aiAgentTester', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_agent_tester_nonce'),
            'i18n' => array(
                'processing' => __('Traitement en cours...', 'ai-agent'),
                'timeout' => __('La requête prend plus de temps que prévu. Veuillez patienter...', 'ai-agent'),
                'error' => __('Une erreur est survenue', 'ai-agent')
            )
        ));
    }
}
add_action('admin_enqueue_scripts', 'ai_agent_enqueue_admin_scripts');

/**
 * Fonction utilitaire pour journaliser les appels JavaScript
 * Cette fonction est utilisée par les scripts JavaScript pour enregistrer des logs
 */
function ai_agent_log_js()
{
    // Vérifier le nonce pour la sécurité
    check_ajax_referer('ai_agent_js_log', 'nonce');

    // Vérifier les permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }

    // Récupérer les données
    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    $level = isset($_POST['level']) ? sanitize_text_field($_POST['level']) : 'info';

    // Journaliser si la fonction existe
    if (function_exists('ai_agent_log')) {
        ai_agent_log('[JS] ' . $message, $level);
        wp_send_json_success();
    } else {
        wp_send_json_error('Logger not available');
    }
}
add_action('wp_ajax_ai_agent_log_js', 'ai_agent_log_js');
