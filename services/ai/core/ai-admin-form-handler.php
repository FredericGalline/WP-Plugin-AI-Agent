<?php

/**
 * Gestionnaire du formulaire d'administration des connecteurs IA
 *
 * Ce fichier contient une classe responsable de traiter les données soumises via le formulaire
 * d'administration des connecteurs IA. Il gère la validation des entrées, l'enregistrement des
 * clés API et la configuration des modèles actifs pour les différents fournisseurs d'IA.
 *
 * @package AI_Redactor
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
 * @js /assets/js/providers-admin.js - Script qui déclenche les actions liées au formulaire
 *
 * @ai Ce fichier est exclusivement dédié à la gestion des soumissions du formulaire d'administration
 * des connecteurs IA. Il ne contient aucune logique de génération de contenu par IA, ni d'algorithmes
 * d'IA proprement dits. Sa responsabilité est strictement limitée à : (1) valider les données soumises
 * par l'utilisateur, (2) enregistrer les clés API et les modèles actifs dans les options WordPress,
 * et (3) afficher des messages d'erreur ou de succès en fonction des résultats. Toute logique métier
 * liée à la communication avec les APIs des fournisseurs est gérée dans d'autres classes.
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer le traitement du formulaire
 */
class AI_Redactor_Form_Handler
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
        // Vérifier si une soumission de formulaire a eu lieu
        if (!isset($_POST['ai_redactor_connector_submit'])) {
            return;
        }

        // Vérifier le nonce
        if (!check_admin_referer('ai_redactor_save_connectors', 'ai_redactor_connector_nonce')) {
            add_settings_error(
                'ai_redactor_connectors',
                'nonce_error',
                __('Échec de la vérification de sécurité. Veuillez réessayer.', 'ai-redactor'),
                'error'
            );
            return;
        }

        // Vérifier les autorisations
        if (!current_user_can('manage_options')) {
            add_settings_error(
                'ai_redactor_connectors',
                'permissions_error',
                __('Vous n\'avez pas les droits suffisants pour modifier ces paramètres.', 'ai-redactor'),
                'error'
            );
            return;
        }

        // Enregistrer le modèle actif (au format 'provider:model')
        if (isset($_POST['ai_redactor_active_model'])) {
            $active_model = sanitize_text_field($_POST['ai_redactor_active_model']);
            update_option('ai_redactor_active_model', $active_model);

            // Extraire le fournisseur du modèle actif sélectionné
            $parts = explode(':', $active_model);
            if (count($parts) === 2) {
                $active_provider = $parts[0];
                update_option('ai_redactor_active_provider', $active_provider);
            }
        }

        // Parcourir chaque fournisseur pour enregistrer les clés API
        foreach ($this->providers_config as $provider_id => $provider_data) {
            // Enregistrer la clé API
            $api_key_option = $provider_data['api_key_option'];
            if (isset($_POST[$api_key_option])) {
                $api_key = sanitize_text_field($_POST[$api_key_option]);
                update_option($api_key_option, $api_key);
            }
        }

        // Ajouter un message de succès
        add_settings_error(
            'ai_redactor_connectors',
            'settings_updated',
            __('Paramètres des connecteurs IA enregistrés avec succès.', 'ai-redactor'),
            'success'
        );
    }
}
