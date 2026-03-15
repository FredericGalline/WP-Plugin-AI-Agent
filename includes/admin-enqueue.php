<?php

/**
 * Gestion des fichiers CSS et JS pour le plugin AI Connector
 *
 * Ce fichier s'occupe de l'enregistrement et du chargement des assets du plugin
 * (CSS, JavaScript) dans l'administration WordPress.
 *
 * @package WP_Plugin_AI_Connector
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enregistre et charge les styles CSS pour l'administration
 */
function ai_connector_enqueue_admin_styles($hook)
{
    // Définir l'URL de base des assets
    $css_base_url = plugin_dir_url(dirname(__FILE__)) . 'assets/css/';
    $plugin_version = defined('WP_PLUGIN_AI_CONNECTOR_VERSION') ? WP_PLUGIN_AI_CONNECTOR_VERSION : '1.0.0';

    // Identifier les pages du plugin
    $is_ai_connector_page = strpos($hook, 'ai-connector') !== false;
    $is_connectors_page = strpos($hook, 'ai-connector-connectors') !== false;
    $is_diagnostic_page = strpos($hook, 'ai-connector-diagnostic') !== false;
    $is_tester_page = strpos($hook, 'ai-connector-tester') !== false;

    // Charger les styles sur toutes les pages du plugin
    if ($is_ai_connector_page) {
        // CSS commun pour toutes les pages du plugin
        wp_enqueue_style(
            'ai-connector-common',
            $css_base_url . 'common.css',
            array(),
            $plugin_version
        );

        wp_enqueue_style(
            'ai-connector-admin-dashboard',
            $css_base_url . 'admin-dashboard.css',
            array('ai-connector-common'),
            $plugin_version
        );
    }

    // Charger les styles spécifiques pour chaque page
    if ($is_diagnostic_page) {
        wp_enqueue_style(
            'ai-connector-admin-diagnostic',
            $css_base_url . 'admin-diagnostic.css',
            array('ai-connector-common'),
            $plugin_version
        );
    }

    if ($is_connectors_page) {
        wp_enqueue_style(
            'ai-connector-admin-form',
            $css_base_url . 'admin-models-form.css',
            array('ai-connector-common'),
            $plugin_version
        );
    }

    if ($is_tester_page) {
        wp_enqueue_style(
            'ai-connector-admin-tester',
            $css_base_url . 'admin-tester.css',
            array('ai-connector-common'),
            $plugin_version
        );
    }
}
add_action('admin_enqueue_scripts', 'ai_connector_enqueue_admin_styles');

/**
 * Enregistre et charge les scripts JavaScript pour l'administration
 */
function ai_connector_enqueue_admin_scripts($hook)
{
    // Définir l'URL de base des assets
    $js_base_url = plugin_dir_url(dirname(__FILE__)) . 'assets/js/';
    $plugin_version = defined('WP_PLUGIN_AI_CONNECTOR_VERSION') ? WP_PLUGIN_AI_CONNECTOR_VERSION : '1.0.0';

    // Identifier les pages du plugin
    $is_ai_connector_page = strpos($hook, 'ai-connector') !== false;
    $is_diagnostic_page = strpos($hook, 'ai-connector-diagnostic') !== false;
    $is_tester_page = strpos($hook, 'ai-connector-tester') !== false;

    // Ajouter jQuery en dépendance pour tous les scripts
    if ($is_ai_connector_page) {
        wp_enqueue_script('jquery');
    }

    // Charger les scripts spécifiques pour chaque page
    if ($is_diagnostic_page) {
        wp_enqueue_script(
            'ai-connector-admin-diagnostic',
            $js_base_url . 'admin-diagnostic.js',
            array('jquery'),
            $plugin_version,
            true
        );

        // Localiser le script pour les traductions et variables
        wp_localize_script('ai-connector-admin-diagnostic', 'aiAgentData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_connector_diagnostic_nonce'),
            'i18n' => array(
                'testing' => __('Test en cours...', 'ai-connector'),
                'success' => __('Succès', 'ai-connector'),
                'error' => __('Erreur', 'ai-connector'),
                'copied' => __('Copié !', 'ai-connector')
            )
        ));
    }

    if ($is_tester_page) {
        wp_enqueue_script(
            'ai-connector-admin-tester',
            $js_base_url . 'ai-admin-tester.js',
            array('jquery'),
            $plugin_version,
            true
        );

        // Localiser le script pour les traductions et variables
        wp_localize_script('ai-connector-admin-tester', 'aiAgentTester', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_connector_tester_nonce'),
            'i18n' => array(
                'processing' => __('Traitement en cours...', 'ai-connector'),
                'timeout' => __('La requête prend plus de temps que prévu. Veuillez patienter...', 'ai-connector'),
                'error' => __('Une erreur est survenue', 'ai-connector')
            )
        ));
    }
}
add_action('admin_enqueue_scripts', 'ai_connector_enqueue_admin_scripts');

/**
 * Fonction utilitaire pour journaliser les appels JavaScript
 * Cette fonction est utilisée par les scripts JavaScript pour enregistrer des logs
 */
function ai_connector_log_js()
{
    // Vérifier le nonce pour la sécurité
    check_ajax_referer('ai_connector_js_log', 'nonce');

    // Vérifier les permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }

    // Récupérer les données
    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    $level = isset($_POST['level']) ? sanitize_text_field($_POST['level']) : 'info';

    // Journaliser si la fonction existe
    if (function_exists('ai_connector_log')) {
        ai_connector_log('[JS] ' . $message, $level);
        wp_send_json_success();
    } else {
        wp_send_json_error('Logger not available');
    }
}
add_action('wp_ajax_ai_connector_log_js', 'ai_connector_log_js');
