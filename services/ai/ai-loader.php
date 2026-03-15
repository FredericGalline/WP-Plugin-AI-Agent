<?php

/**
 * Chargeur des services IA pour AI Redactor
 *
 * Ce fichier centralise le chargement de tous les composants liés aux services IA, y compris
 * les pages d'administration, les scripts et styles nécessaires, ainsi que la configuration
 * des fournisseurs d'IA. Il agit comme point d'entrée pour initialiser les fonctionnalités
 * liées aux services IA dans le plugin.
 *
 * @package AI_Redactor
 * @subpackage Services\AI
 *
 * @depends AI_Redactor_Form_Handler
 * @depends AI_Redactor_Ajax_Handler
 * @depends AI_Redactor_Admin_Form
 * @depends AI_Redactor_Admin_Tester
 * @depends AI_Redactor_Admin_Diagnostic
 *
 * @css /assets/css/ai-admin.css
 * @css /assets/css/ai-admin-diagnostic.css
 * @css /assets/css/ai-admin-tester.css
 *
 * @js /assets/js/ai-admin.js
 * @js /assets/js/ai-admin-diagnostic.js
 * @js /assets/js/ai-admin-tester.js
 *
 * @ai Ce fichier est exclusivement dédié à la gestion et au chargement des services IA pour le plugin.
 * Il ne contient aucune logique métier liée à la génération de contenu par IA, ni d'algorithmes d'IA.
 * Sa responsabilité est strictement limitée à : (1) charger les fichiers nécessaires pour les services IA,
 * (2) enregistrer les scripts et styles CSS/JS pour les pages d'administration, et (3) fournir des fonctions
 * utilitaires pour récupérer la configuration des fournisseurs et le modèle actif. Toute logique métier
 * ou de communication avec les APIs est déléguée à d'autres classes.
 */

/**
 * Chargeur des services IA pour AI Redactor
 *
 * Ce fichier centralise le chargement de tous les composants liés aux services IA.
 *
 * @package AIRedactor
 */

// Empêcher l'accès direct au fichier
if (! defined('ABSPATH')) {
    exit;
}

// Définir les constantes pour les chemins des assets.
if (!defined('AI_CONNECTOR_AI_PATH')) {
    define('AI_CONNECTOR_AI_PATH', dirname(__FILE__));
}
if (!defined('AI_CONNECTOR_AI_URL')) {
    define('AI_CONNECTOR_AI_URL', plugin_dir_url(__FILE__));
}
if (!defined('AI_CONNECTOR_AI_ASSETS_URL')) {
    define('AI_CONNECTOR_AI_ASSETS_URL', AI_CONNECTOR_AI_URL . 'assets/');
}

// Compatibilite transitoire avec l'ancien nommage interne.
if (!defined('AI_REDACTOR_AI_PATH')) {
    define('AI_REDACTOR_AI_PATH', AI_CONNECTOR_AI_PATH);
}
if (!defined('AI_REDACTOR_AI_URL')) {
    define('AI_REDACTOR_AI_URL', AI_CONNECTOR_AI_URL);
}
if (!defined('AI_REDACTOR_AI_ASSETS_URL')) {
    define('AI_REDACTOR_AI_ASSETS_URL', AI_CONNECTOR_AI_ASSETS_URL);
}

// Enregistrement du fichier rest-api.php.
require_once plugin_dir_path(__FILE__) . 'rest-api.php';

/**
 * Initialise les services IA.
 */
function ai_connector_init_ai_services()
{
    // Point d'entree reserve pour des initialisations futures.
}
add_action('init', 'ai_connector_init_ai_services');

/**
 * Enregistre les scripts et styles pour les services IA dans l'admin.
 */
function ai_connector_admin_enqueue_ia_assets($hook)
{
    if ('ai-connector_page_ai-connector-diagnostic' === $hook) {
        wp_enqueue_style(
            'ai-redactor-diagnostic-css',
            AI_CONNECTOR_AI_ASSETS_URL . 'css/ai-admin-diagnostic.css',
            array('ai-connector-common'),
            '1.0.0'
        );

        wp_enqueue_script(
            'ai-redactor-diagnostic-js',
            AI_CONNECTOR_AI_ASSETS_URL . 'js/ai-admin-diagnostic.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('ai-redactor-diagnostic-js', 'aiDiagnosticL10n', array(
            'longWait' => __('Les tests prennent plus de temps que prevu. Veuillez patienter...', 'ai-connector'),
            'copied' => __('Copie !', 'ai-connector'),
            'copyResults' => __('Copier les resultats', 'ai-connector')
        ));
    }
}
add_action('admin_enqueue_scripts', 'ai_connector_admin_enqueue_ia_assets');

if (!function_exists('ai_redactor_init_ai_services')) {
    /**
     * Wrapper de compatibilite avec l'ancien bootstrap.
     *
     * @return void
     */
    function ai_redactor_init_ai_services()
    {
        ai_connector_init_ai_services();
    }
}

if (!function_exists('ai_redactor_admin_enqueue_ia_assets')) {
    /**
     * Wrapper de compatibilite pour les anciens hooks admin.
     *
     * @param string $hook Hook courant.
     * @return void
     */
    function ai_redactor_admin_enqueue_ia_assets($hook)
    {
        ai_connector_admin_enqueue_ia_assets($hook);
    }
}
