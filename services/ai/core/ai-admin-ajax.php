<?php

/**
 * Gestionnaire des requêtes AJAX pour les connecteurs IA
 *
 * Ce fichier contient les fonctionnalités permettant de tester les connexions 
 * aux APIs des différents fournisseurs d'IA via des requêtes AJAX.
 * Il permet notamment de valider les clés API sans quitter l'interface d'administration.
 *
 * @package WP_Plugin_AI_Connector
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
 * @js /assets/js/ai-admin.js - Script qui déclenche les requêtes AJAX
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer les appels AJAX
 */
class AI_Connector_Ajax_Handler
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
        add_action('wp_ajax_ai_connector_test_connection', array($this, 'ajax_test_connection'));
    }

    /**
     * Point d'entrée AJAX pour tester la connexion à un fournisseur
     */
    public function ajax_test_connection()
    {
        // Vérifier les autorisations en premier
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permissions insuffisantes.', 'wp-plugin-ai-connector'),
            ));
            return;
        }

        // Vérifier le nonce
        if (!check_ajax_referer('ai_connector_test_connection', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Échec de vérification de sécurité.', 'wp-plugin-ai-connector'),
            ));
            return;
        }

        // Récupérer et vérifier le fournisseur
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';

        if (empty($provider) || !isset($this->providers_config[$provider])) {
            wp_send_json_error(array(
                'message' => __('Fournisseur non valide.', 'wp-plugin-ai-connector'),
            ));
            return;
        }

        // Utiliser uniquement la clé API stockée en base (ne pas accepter de clé depuis POST)
        $api_key_option = $this->providers_config[$provider]['api_key_option'];
        $api_key = get_option($api_key_option, '');

        if (empty($api_key)) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Aucune clé API configurée pour %s.', 'wp-plugin-ai-connector'),
                    esc_html($this->providers_config[$provider]['name'])
                ),
            ));
            return;
        }

        // Tester la connexion à l'API
        $result = $this->test_provider_connection($provider, $api_key);

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
        $test_prompt = "Ceci est un test de connexion. Répondez simplement 'OK'.";

        $api_file_map = [
            'openai' => 'OpenAI.php',
            'anthropic' => 'Anthropic.php',
            'mistral' => 'Mistral.php',
            'google' => 'Gemini.php',
            'gemini' => 'Gemini.php',
            'grok' => 'Grok.php'
        ];

        $api_class_map = [
            'openai' => 'AI_OpenAI_API',
            'anthropic' => 'AI_Anthropic_API',
            'mistral' => 'AI_Mistral_API',
            'google' => 'AI_Gemini_API',
            'gemini' => 'AI_Gemini_API',
            'grok' => 'AI_Grok_API'
        ];

        if (!isset($api_file_map[$provider_id]) || !isset($api_class_map[$provider_id])) {
            return [
                'success' => false,
                'message' => sprintf(
                    __('Le fournisseur %s n\'est pas encore supporté pour les tests de connexion.', 'wp-plugin-ai-connector'),
                    $this->providers_config[$provider_id]['name']
                )
            ];
        }

        $api_file = $api_file_map[$provider_id];
        $api_class = $api_class_map[$provider_id];
        $api_file_path = dirname(dirname(__FILE__)) . '/api/' . $api_file;

        if (!file_exists($api_file_path)) {
            return [
                'success' => false,
                'message' => sprintf(
                    __('Impossible de charger l\'implémentation API pour %s.', 'wp-plugin-ai-connector'),
                    $this->providers_config[$provider_id]['name']
                )
            ];
        }

        require_once $api_file_path;

        $test_model = '';
        if (!empty($this->providers_config[$provider_id]['models'])) {
            $models = array_keys($this->providers_config[$provider_id]['models']);
            $test_model = reset($models);
        }

        if (empty($test_model)) {
            return [
                'success' => false,
                'message' => sprintf(
                    __('Aucun modèle disponible pour tester la connexion à %s.', 'wp-plugin-ai-connector'),
                    $this->providers_config[$provider_id]['name']
                )
            ];
        }

        try {
            add_filter('http_request_timeout', function () {
                return 10;
            }, 9999);

            $result = call_user_func([$api_class, 'send_request'], $test_prompt, $test_model, $api_key);

            remove_all_filters('http_request_timeout', 9999);

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => sprintf(
                        __('Connexion à %s réussie! La clé API est valide.', 'wp-plugin-ai-connector'),
                        $this->providers_config[$provider_id]['name']
                    )
                ];
            } else {
                return [
                    'success' => false,
                    'message' => sprintf(
                        __('Échec de la connexion à %s: %s', 'wp-plugin-ai-connector'),
                        $this->providers_config[$provider_id]['name'],
                        isset($result['error']) ? $result['error'] : __('Erreur inconnue', 'wp-plugin-ai-connector')
                    )
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => sprintf(
                    __('Erreur lors du test de connexion à %s.', 'wp-plugin-ai-connector'),
                    $this->providers_config[$provider_id]['name']
                )
            ];
        }
    }
}
