<?php

/**
 * Gestionnaire du formulaire d'administration des connecteurs IA
 *
 * Ce fichier contient une classe responsable de traiter les données soumises via le formulaire
 * d'administration des connecteurs IA. Il gère la validation des entrées, l'enregistrement des
 * clés API et la configuration des modèles actifs pour les différents fournisseurs d'IA.
 *
 * @package WP_Plugin_AI_Connector
 * @subpackage Services\AI\Core
 *
 * @depends WordPress Settings API
 * @depends add_action
 * @depends check_admin_referer
 * @depends add_settings_error
 * @depends update_option
 * @depends current_user_can
 *
 * @css N/A - Ce fichier est un gestionnaire de formulaire sans interface utilisateur directe
 *
 * @js N/A - Pas de JavaScript requis pour ce gestionnaire
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer le traitement du formulaire
 */
class AI_Connector_Form_Handler
{
    /**
     * Configuration des fournisseurs IA
     *
     * @var array
     */
    private $providers_config;

    /**
     * Initialisation de la classe
     *
     * @param array $providers_config Configuration des fournisseurs
     */
    public function __construct($providers_config)
    {
        $this->providers_config = $providers_config;

        // Initialiser le traitement du formulaire
        add_action('admin_init', array($this, 'process_form_submission'));
    }

    /**
     * Traite la soumission du formulaire de configuration
     */
    public function process_form_submission()
    {
        $request_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(wp_unslash($_SERVER['REQUEST_METHOD'])) : 'GET';
        $current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

        if (
            'POST' !== $request_method ||
            'ai-connector-connectors' !== $current_page ||
            !isset($_POST['ai_connector_connector_nonce'])
        ) {
            return;
        }

        // Vérifier les autorisations en premier
        if (!current_user_can('manage_options')) {
            add_settings_error(
                'ai_connector_connectors',
                'permissions_error',
                __('Vous n\'avez pas les droits suffisants pour modifier ces paramètres.', 'wp-plugin-ai-connector'),
                'error'
            );
            return;
        }

        // Vérifier le nonce
        if (!check_admin_referer('ai_connector_save_connectors', 'ai_connector_connector_nonce')) {
            add_settings_error(
                'ai_connector_connectors',
                'nonce_error',
                __('Échec de la vérification de sécurité. Veuillez réessayer.', 'wp-plugin-ai-connector'),
                'error'
            );
            return;
        }

        // Enregistrer le modèle actif (au format 'provider:model')
        if (isset($_POST['ai_connector_active_model'])) {
            $active_model = sanitize_text_field(wp_unslash($_POST['ai_connector_active_model']));
            $providers = ai_connector_get_providers_config();
            $parts = explode(':', $active_model, 2);
            if (2 === count($parts) && isset($providers[$parts[0]]['models'][$parts[1]])) {
                ai_connector_set_active_model($active_model);
            }
        }

        // Parcourir chaque fournisseur pour enregistrer les clés API
        foreach ($this->providers_config as $provider_id => $provider_data) {
            // Enregistrer la clé API
            $api_key_option = $provider_data['api_key_option'];
            if (isset($_POST[$api_key_option])) {
                $api_key = sanitize_text_field(wp_unslash($_POST[$api_key_option]));
                update_option($api_key_option, $api_key);
            }
        }

        // Ajouter un message de succès
        add_settings_error(
            'ai_connector_connectors',
            'settings_updated',
            __('Paramètres des connecteurs IA enregistrés avec succès.', 'wp-plugin-ai-connector'),
            'success'
        );
    }
}
