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

// Définir les constantes pour les chemins des assets
define('AI_REDACTOR_AI_PATH', dirname(__FILE__));
define('AI_REDACTOR_AI_URL', plugin_dir_url(__FILE__));
define('AI_REDACTOR_AI_ASSETS_URL', AI_REDACTOR_AI_URL . 'assets/');

// Charger la page d'administration des connecteurs IA
if (is_admin()) {
    require_once dirname(__FILE__) . '/ui/ai-admin.php';
}

/**
 * Initialise les services IA
 */
function ai_redactor_init_ai_services()
{
    // Initialisation supplémentaire si nécessaire
}
add_action('init', 'ai_redactor_init_ai_services');

/**
 * Enregistre les scripts et styles pour les services IA dans l'admin
 */
function ai_redactor_admin_enqueue_ia_assets($hook)
{
    // Enregistrer le CSS et JS du diagnostic
    if ('ai-redactor_page_ai-redactor-diagnostic' === $hook) {
        // Charger le CSS spécifique à la page de diagnostic
        wp_enqueue_style(
            'ai-redactor-diagnostic-css',
            AI_REDACTOR_AI_ASSETS_URL . 'css/ai-admin-diagnostic.css',
            array('ai-redactor-admin-common'),
            '1.0.0'
        );

        // Charger le JavaScript
        wp_enqueue_script(
            'ai-redactor-diagnostic-js',
            AI_REDACTOR_AI_ASSETS_URL . 'js/ai-admin-diagnostic.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Localiser le script pour les traductions
        wp_localize_script('ai-redactor-diagnostic-js', 'aiDiagnosticL10n', array(
            'longWait' => __('Les tests prennent plus de temps que prévu. Veuillez patienter...', 'ai-redactor'),
            'copied' => __('Copié !', 'ai-redactor'),
            'copyResults' => __('Copier les résultats', 'ai-redactor')
        ));
    }

    // Ajouter ici d'autres conditions pour d'autres pages du service IA
}
add_action('admin_enqueue_scripts', 'ai_redactor_admin_enqueue_ia_assets');

/**
 * Récupère la configuration des fournisseurs d'IA
 *
 * @return array Configuration des fournisseurs
 */
function ai_redactor_get_providers_config()
{
    return require_once dirname(__FILE__) . '/providers-config.php';
}

/**
 * Obtient le fournisseur IA actif
 *
 * @return string|false L'identifiant du fournisseur actif ou false si aucun
 */
function ai_redactor_get_active_provider()
{
    // D'abord essayer de l'extraire du modèle actif
    $active_model = get_option('ai_redactor_active_model', '');
    if (!empty($active_model)) {
        $parts = explode(':', $active_model);
        if (count($parts) === 2) {
            return $parts[0];
        }
    }

    // Sinon, utiliser l'ancienne option
    return get_option('ai_redactor_active_provider', false);
}

/**
 * Obtient le modèle actif
 *
 * @return array|false Un tableau avec 'provider' et 'model', ou false si aucun
 */
function ai_redactor_get_active_model()
{
    $active_model_combined = get_option('ai_redactor_active_model', '');

    if (!empty($active_model_combined)) {
        $parts = explode(':', $active_model_combined);
        if (count($parts) === 2) {
            return array(
                'provider' => $parts[0],
                'model' => $parts[1]
            );
        }
    }

    // Ancienne méthode (rétrocompatibilité)
    $provider = get_option('ai_redactor_active_provider', false);
    if ($provider) {
        $model = get_option('ai_redactor_' . $provider . '_active_model', false);
        if ($model) {
            return array(
                'provider' => $provider,
                'model' => $model
            );
        }
    }

    return false;
}

/**
 * Vérifie si un fournisseur d'IA est correctement configuré
 *
 * @param string $provider_id Identifiant du fournisseur à vérifier
 * @return bool True si configuré, false sinon
 */
function ai_redactor_is_provider_configured($provider_id)
{
    $providers_config = ai_redactor_get_providers_config();

    if (! isset($providers_config[$provider_id])) {
        return false;
    }

    $api_key_option = $providers_config[$provider_id]['api_key_option'];
    $api_key = get_option($api_key_option, '');

    return ! empty($api_key);
}
