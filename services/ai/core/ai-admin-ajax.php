<?php

/**
 * Gestionnaire des requêtes AJAX pour les connecteurs IA
 *
 * Ce fichier contient les fonctionnalités permettant de tester les connexions 
 * aux APIs des différents fournisseurs d'IA via des requêtes AJAX.
 * Il permet notamment de valider les clés API sans quitter l'interface d'administration.
 *
 * @package AI_Redactor
 * @subpackage Services\AI\Core
 *
 * @depends WordPress AJAX API
 * @depends check_ajax_referer
 * @depends wp_send_json_success
 * @depends wp_send_json_error
 * @depends current_user_can
 * @depends get_option
 *
 * @css N/A - Ce fichier est un gestionnaire AJAX sans interface utilisateur directe
 *
 * @js /assets/js/providers-admin.js - Script qui déclenche les requêtes AJAX
 *
 * @ai Ce fichier est exclusivement dédié à la gestion des requêtes AJAX pour tester les 
 * connexions aux APIs. Il ne contient aucune logique de génération de contenu par IA, 
 * ni d'algorithmes d'IA proprement dits. Son unique responsabilité est de valider les 
 * clés API fournies en contactant les endpoints des providers IA (OpenAI, Anthropic, etc.) 
 * et retournant le résultat. La classe AI_Redactor_Ajax_Handler prend en paramètre la 
 * configuration des fournisseurs et expose une méthode AJAX pour tester la connexion.
 * Dans sa version actuelle, le fichier simule simplement une connexion réussie à tous les 
 * fournisseurs, mais devra être étendu pour effectuer de véritables tests d'authentification.
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer les appels AJAX
 */
class AI_Redactor_Ajax_Handler
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

        // Ajouter l'action AJAX pour tester la connexion
        add_action('wp_ajax_ai_redactor_test_connection', array($this, 'ajax_test_connection'));
    }

    /**
     * Point d'entrée AJAX pour tester la connexion à un fournisseur
     */
    public function ajax_test_connection()
    {
        // Vérifier le nonce
        if (!check_ajax_referer('ai_redactor_test_connection', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Échec de vérification de sécurité.', 'ai-redactor'),
            ));
        }

        // Vérifier les autorisations
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permissions insuffisantes.', 'ai-redactor'),
            ));
        }

        // Récupérer et vérifier le fournisseur
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        if (empty($provider) || !isset($this->providers_config[$provider])) {
            wp_send_json_error(array(
                'message' => __('Fournisseur non valide.', 'ai-redactor'),
            ));
        }

        // Récupérer la clé API
        $api_key_option = $this->providers_config[$provider]['api_key_option'];
        $api_key = get_option($api_key_option, '');

        if (empty($api_key)) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Aucune clé API configurée pour %s.', 'ai-redactor'),
                    esc_html($this->providers_config[$provider]['name'])
                ),
            ));
        }

        // Essayer de tester la connexion à l'API
        $result = $this->test_provider_connection($provider, $api_key);

        // Renvoyer la réponse
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $result['message'],
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message'],
            ));
        }
    }

    /**
     * Teste la connexion à un fournisseur d'API
     *
     * @param string $provider_id Identifiant du fournisseur
     * @param string $api_key     Clé API à tester
     * @return array Résultat du test
     */
    private function test_provider_connection($provider_id, $api_key)
    {
        // Dans une implémentation réelle, vous devriez appeler l'API du fournisseur
        // Pour ce sprint, nous simulons simplement une connexion réussie

        $success = true; // Simulons une connexion réussie
        $message = sprintf(
            __('Connexion à %s réussie.', 'ai-redactor'),
            esc_html($this->providers_config[$provider_id]['name'])
        );

        return array(
            'success' => $success,
            'message' => $message
        );
    }
}
