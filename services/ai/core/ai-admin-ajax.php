<?php

/**
 * Gestionnaire des requêtes AJAX pour les connecteurs IA
 *
 * Ce fichier contient les fonctionnalités permettant de tester les connexions 
 * aux APIs des différents fournisseurs d'IA via des requêtes AJAX.
 * Il permet notamment de valider les clés API sans quitter l'interface d'administration.
 *
 * @package WP_Plugin_AI_Agent
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
class AI_Agent_Ajax_Handler
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
        add_action('wp_ajax_ai_agent_test_connection', array($this, 'ajax_test_connection'));
    }

    /**
     * Point d'entrée AJAX pour tester la connexion à un fournisseur
     */
    public function ajax_test_connection()
    {
        // Journaliser le début de la requête AJAX
        ai_agent_log('Début de la requête AJAX de test de connexion', 'info');

        // Vérifier le nonce avec des détails sur la vérification
        $nonce_verification = check_ajax_referer('ai_agent_test_connection', 'nonce', false);
        ai_agent_log('Vérification du nonce: ' . ($nonce_verification ? 'réussie' : 'échouée'), 'debug', [
            'nonce' => isset($_POST['nonce']) ? 'présent' : 'absent',
            'verification_result' => $nonce_verification
        ]);

        if (!$nonce_verification) {
            ai_agent_log('Échec de la vérification de sécurité pour le test de connexion', 'error');
            wp_send_json_error(array(
                'message' => __('Échec de vérification de sécurité.', 'wp-plugin-ai-agent'),
            ));
            return;
        }

        // Vérifier les autorisations
        $user_can_manage = current_user_can('manage_options');
        ai_agent_log('Vérification des permissions: ' . ($user_can_manage ? 'réussie' : 'échouée'), 'debug');

        if (!$user_can_manage) {
            ai_agent_log('Permissions insuffisantes pour le test de connexion', 'error');
            wp_send_json_error(array(
                'message' => __('Permissions insuffisantes.', 'wp-plugin-ai-agent'),
            ));
            return;
        }

        // Récupérer et vérifier le fournisseur
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        ai_agent_log('Fournisseur reçu: ' . $provider, 'debug');

        if (empty($provider) || !isset($this->providers_config[$provider])) {
            ai_agent_log('Fournisseur non valide: ' . $provider, 'error');
            wp_send_json_error(array(
                'message' => __('Fournisseur non valide.', 'wp-plugin-ai-agent'),
            ));
            return;
        }

        // Récupérer la clé API
        $api_key_option = $this->providers_config[$provider]['api_key_option'];
        $api_key = get_option($api_key_option, '');
        $has_api_key = !empty($api_key);

        ai_agent_log('Vérification de la clé API pour ' . $provider . ': ' . ($has_api_key ? 'présente' : 'absente'), 'debug');

        if (!$has_api_key) {
            ai_agent_log('Aucune clé API configurée pour ' . $provider, 'error');
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Aucune clé API configurée pour %s.', 'wp-plugin-ai-agent'),
                    esc_html($this->providers_config[$provider]['name'])
                ),
            ));
            return;
        }

        // Essayer de tester la connexion à l'API
        ai_agent_log('Lancement du test de connexion pour ' . $provider, 'info');
        $result = $this->test_provider_connection($provider, $api_key);
        ai_agent_log('Résultat du test: ' . ($result['success'] ? 'réussi' : 'échoué'), 'debug', [
            'message' => $result['message']
        ]);

        // Renvoyer la réponse
        if ($result['success']) {
            ai_agent_log('Test de connexion réussi pour ' . $provider, 'info');
            wp_send_json_success(array(
                'message' => $result['message'],
            ));
        } else {
            ai_agent_log('Test de connexion échoué pour ' . $provider, 'error', [
                'message' => $result['message']
            ]);
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
        ai_agent_log('Test de connexion pour le fournisseur ' . $provider_id, 'debug');

        // Définir un prompt de test simple
        $test_prompt = "Ceci est un test de connexion pour vérifier la validité de la clé API. Répondez simplement 'OK'.";

        // Mapper les fournisseurs aux fichiers API
        $api_file_map = [
            'openai' => 'OpenAI.php',
            'anthropic' => 'Anthropic.php',
            'mistral' => 'Mistral.php',
            'google' => 'Gemini.php',
            'gemini' => 'Gemini.php',
            'grok' => 'Grok.php'
        ];

        // Mapper les fournisseurs aux classes API
        $api_class_map = [
            'openai' => 'AI_OpenAI_API',
            'anthropic' => 'AI_Anthropic_API',
            'mistral' => 'AI_Mistral_API',
            'google' => 'AI_Gemini_API',
            'gemini' => 'AI_Gemini_API',
            'grok' => 'AI_Grok_API'
        ];

        // Vérifier si le fournisseur est supporté
        if (!isset($api_file_map[$provider_id]) || !isset($api_class_map[$provider_id])) {
            ai_agent_log('Fournisseur non supporté: ' . $provider_id, 'error');
            return [
                'success' => false,
                'message' => sprintf(
                    __('Le fournisseur %s n\'est pas encore supporté pour les tests de connexion.', 'wp-plugin-ai-agent'),
                    $this->providers_config[$provider_id]['name']
                )
            ];
        }

        // Charger la classe API correspondante
        $api_file = $api_file_map[$provider_id];
        $api_class = $api_class_map[$provider_id];
        $api_file_path = dirname(dirname(__FILE__)) . '/api/' . $api_file;

        ai_agent_log('Chargement du fichier API: ' . $api_file_path, 'debug');

        if (!file_exists($api_file_path)) {
            ai_agent_log('Fichier API introuvable: ' . $api_file_path, 'error');
            return [
                'success' => false,
                'message' => sprintf(
                    __('Impossible de charger l\'implémentation API pour %s.', 'wp-plugin-ai-agent'),
                    $this->providers_config[$provider_id]['name']
                )
            ];
        }

        require_once $api_file_path;

        // Sélectionner un modèle de test (prendre le premier disponible)
        $test_model = '';
        if (!empty($this->providers_config[$provider_id]['models'])) {
            // Prendre le premier modèle comme modèle de test
            $models = array_keys($this->providers_config[$provider_id]['models']);
            $test_model = reset($models);
        }

        if (empty($test_model)) {
            ai_agent_log('Aucun modèle disponible pour tester ' . $provider_id, 'error');
            return [
                'success' => false,
                'message' => sprintf(
                    __('Aucun modèle disponible pour tester la connexion à %s.', 'wp-plugin-ai-agent'),
                    $this->providers_config[$provider_id]['name']
                )
            ];
        }

        ai_agent_log('Test avec le modèle: ' . $test_model, 'debug');

        try {
            // Effectuer un appel API avec un timeout court pour tester la connexion
            add_filter('http_request_timeout', function () {
                return 10;
            }, 9999);

            ai_agent_log('Début de l\'appel API pour tester la connexion', 'debug');

            // Appeler la méthode send_request de la classe API
            $result = call_user_func([$api_class, 'send_request'], $test_prompt, $test_model, $api_key);

            // Restaurer le timeout par défaut
            remove_all_filters('http_request_timeout', 9999);

            ai_agent_log('Résultat de l\'appel API: ' . ($result['success'] ? 'succès' : 'échec'), 'debug', $result);

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => sprintf(
                        __('Connexion à %s réussie! La clé API est valide.', 'wp-plugin-ai-agent'),
                        $this->providers_config[$provider_id]['name']
                    )
                ];
            } else {
                return [
                    'success' => false,
                    'message' => sprintf(
                        __('Échec de la connexion à %s: %s', 'wp-plugin-ai-agent'),
                        $this->providers_config[$provider_id]['name'],
                        $result['error']
                    )
                ];
            }
        } catch (Exception $e) {
            ai_agent_log('Exception lors du test de connexion: ' . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => sprintf(
                    __('Erreur lors du test de connexion à %s: %s', 'wp-plugin-ai-agent'),
                    $this->providers_config[$provider_id]['name'],
                    $e->getMessage()
                )
            ];
        }
    }
}
