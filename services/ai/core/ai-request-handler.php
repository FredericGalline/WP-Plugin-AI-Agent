<?php

/**
 * Gestionnaire des requêtes vers les APIs d'intelligence artificielle
 *
 * Ce fichier contient une classe responsable de gérer les interactions avec les APIs
 * des différents fournisseurs d'IA (OpenAI, Anthropic, Mistral, etc.). Il standardise
 * l'envoi des prompts et la réception des réponses, en fonction du fournisseur et du modèle
 * actuellement sélectionnés dans les paramètres du plugin.
 *
 * @package WP_Plugin_AI_Connector
 * @subpackage Services\AI\Core
 *
 * @depends WordPress Options API
 * @depends wp_remote_post
 * @depends require_once
 *
 * @css N/A - Ce fichier est un gestionnaire de requêtes sans interface utilisateur directe
 *
 * @js N/A - Ce fichier est un gestionnaire de requêtes sans interface utilisateur directe
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

class AI_Connector_Request_Handler
{
    /**
     * Parse un modele combine provider:model.
     *
     * @param string $combined Modele combine.
     * @return array{provider:string,model:string}
     */
    private static function parse_active_model($combined)
    {
        $combined = is_string($combined) ? trim($combined) : '';

        if ('' === $combined) {
            return array(
                'provider' => '',
                'model' => '',
            );
        }

        $parts = explode(':', $combined, 2);

        if (2 !== count($parts)) {
            return array(
                'provider' => '',
                'model' => '',
            );
        }

        return array(
            'provider' => $parts[0],
            'model' => $parts[1],
        );
    }

    /**
     * Envoie un prompt au modèle d'IA actuellement sélectionné.
     *
     * @param string $prompt Le texte du prompt à envoyer à l'IA.
     * @param array  $args   Arguments optionnels pour surcharger le fournisseur ou le modele.
     * @return array Tableau avec statut de succès, réponse et éventuelle erreur.
     */
    public static function send_prompt($prompt, $args = array())
    {
        $prompt = is_string($prompt) ? trim($prompt) : '';

        if ('' === $prompt) {
            return array(
                'success' => false,
                'response' => null,
                'error' => __('Le prompt est vide.', 'ai-connector'),
            );
        }

        ai_connector_log('*** DEBUT DE LA REQUETE ***', 'info');
        ai_connector_log('Prompt recu pour traitement - Longueur: ' . strlen($prompt) . ' caracteres', 'debug');

        $providers_config = ai_connector_get_providers_config();

        $active_model_info = array(
            'provider' => '',
            'model' => '',
        );

        if (!empty($args['provider']) && !empty($args['model'])) {
            $active_model_info['provider'] = sanitize_key((string) $args['provider']);
            $active_model_info['model'] = sanitize_text_field((string) $args['model']);
        } elseif (!empty($args['active_model'])) {
            $active_model_info = self::parse_active_model((string) $args['active_model']);
        } else {
            $active_model_info = ai_connector_get_active_model();
        }

        $active_provider = $active_model_info['provider'];
        $active_model = $active_model_info['model'];
        $active_model_combined = '' !== $active_provider && '' !== $active_model
            ? $active_provider . ':' . $active_model
            : '';

        if (empty($active_provider) || empty($active_model)) {
            $active_provider = (string) get_option('ai_redactor_active_provider', '');
            $active_model = (string) get_option('ai_redactor_' . $active_provider . '_active_model', '');
            $active_model_combined = '' !== $active_provider && '' !== $active_model
                ? $active_provider . ':' . $active_model
                : '';
        }

        ai_connector_log('Modele actif configure: ' . $active_model_combined, 'info');
        ai_connector_log('Fournisseur extrait: ' . $active_provider, 'debug');
        ai_connector_log('Modele extrait: ' . $active_model, 'debug');

        if (empty($active_provider) || !isset($providers_config[$active_provider])) {
            ai_connector_log('Erreur: Fournisseur non valide ou non configure', 'error');

            return array(
                'success' => false,
                'response' => null,
                'error' => __('Aucun fournisseur d\'IA valide selectionne. Veuillez en configurer un dans AI Connector.', 'ai-connector'),
            );
        }

        if (empty($active_model) || !isset($providers_config[$active_provider]['models'][$active_model])) {
            ai_connector_log('Erreur: Modele non valide ou non configure', 'error');

            return array(
                'success' => false,
                'response' => null,
                'error' => sprintf(
                    __('Aucun modele valide selectionne pour %s. Veuillez en choisir un dans AI Connector.', 'ai-connector'),
                    $providers_config[$active_provider]['name']
                ),
            );
        }

        $api_key = ai_connector_get_provider_api_key($active_provider);
        if (empty($api_key)) {
            return array(
                'success' => false,
                'response' => null,
                'error' => sprintf(
                    __('Cle API non configuree pour %s. Veuillez l\'ajouter dans AI Connector.', 'ai-connector'),
                    $providers_config[$active_provider]['name']
                ),
            );
        }

        $api_file_map = array(
            'openai' => 'OpenAI.php',
            'anthropic' => 'Anthropic.php',
            'mistral' => 'Mistral.php',
            'google' => 'Gemini.php',
            'gemini' => 'Gemini.php',
            'grok' => 'Grok.php',
        );

        if (!isset($api_file_map[$active_provider])) {
            return array(
                'success' => false,
                'response' => null,
                'error' => sprintf(__('Aucune implementation trouvee pour le fournisseur: %s', 'ai-connector'), $active_provider),
            );
        }

        $api_file = $api_file_map[$active_provider];
        $api_file_path = __DIR__ . '/../api/' . $api_file;
        if (!file_exists($api_file_path)) {
            return array(
                'success' => false,
                'response' => null,
                'error' => sprintf(__('Fichier API introuvable pour le fournisseur: %s', 'ai-connector'), $active_provider),
            );
        }
        require_once $api_file_path;

        $api_class_map = array(
            'openai' => 'AI_OpenAI_API',
            'anthropic' => 'AI_Anthropic_API',
            'mistral' => 'AI_Mistral_API',
            'google' => 'AI_Gemini_API',
            'gemini' => 'AI_Gemini_API',
            'grok' => 'AI_Grok_API',
        );

        $api_class = $api_class_map[$active_provider];

        ai_connector_log('Delegation de la requete a la classe: ' . $api_class, 'debug');

        return $api_class::send_request($prompt, $active_model, $api_key);
    }
}

if (!class_exists('AI_Request_Handler', false)) {
    class_alias('AI_Connector_Request_Handler', 'AI_Request_Handler');
}
