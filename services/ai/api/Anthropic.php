<?php

/**
 * Client d'API pour communiquer avec le service Anthropic (Claude)
 *
 * Ce fichier contient une classe qui permet d'interagir avec l'API Anthropic,
 * d'envoyer des requêtes formatées, et de traiter les réponses ou erreurs.
 * Il implémente les spécifications de l'API Claude selon la documentation officielle.
 *
 * @package AI_Redactor
 * @subpackage Services\AI\API
 *
 * @depends WordPress HTTP API
 * @depends wp_remote_post
 * @depends wp_remote_retrieve_response_code
 * @depends wp_remote_retrieve_body
 * @depends json_encode
 * @depends json_decode
 *
 * @css N/A - Cette classe est une couche d'API sans interface utilisateur
 *
 * @js N/A - Cette classe est une couche d'API sans interface utilisateur
 *
 * @ai Ce fichier contient une classe qui gère exclusivement les communications HTTP avec l'API Claude d'Anthropic.
 * Sa responsabilité est strictement limitée à : (1) formater correctement les requêtes selon l'API Anthropic,
 * (2) effectuer les appels HTTP en utilisant l'API WordPress, (3) parser les réponses JSON reçues et
 * (4) gérer les erreurs de communication ou de traitement. Il n'applique aucune logique métier aux prompts
 * ni aux réponses, ne modifie pas le contenu des messages, et ne prend pas de décisions sur les paramètres
 * de génération (temperature, max_tokens, etc.) qui sont passés par le code appelant. Il se contente de
 * servir de couche de transport entre le plugin WordPress et le service Anthropic. Tous les détails
 * d'authentification et de formatage spécifiques à l'API Anthropic sont encapsulés dans cette classe.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour interagir avec l'API Anthropic
 */
class AI_Anthropic_API
{
    /**
     * Envoie une requête à l'API Anthropic (Claude 3).
     *
     * @param string $prompt  Le texte du prompt à envoyer.
     * @param string $model   Le modèle Anthropic à utiliser (ex: claude-3-opus-20240229).
     * @param string $api_key La clé API Anthropic.
     * @return array          Tableau avec statut de succès, réponse et éventuelle erreur.
     */
    public static function send_request($prompt, $model, $api_key)
    {
        if (defined('AI_AGENT_DEBUG') && AI_AGENT_DEBUG) {
            ai_agent_log('Configuration de la requête Anthropic - Modèle: ' . $model, 'debug');
        }

        $api_url = 'https://api.anthropic.com/v1/messages';

        $request_body = [
            'model' => $model,
            'max_tokens' => 1024, // À adapter dynamiquement si besoin
            'temperature' => 0.7,
            'system' => 'Tu es un assistant utile, précis et structuré.',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ]
            ]
        ];

        $args = [
            'timeout' => 60,
            'headers' => [
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($request_body),
        ];

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            if (defined('AI_AGENT_DEBUG') && AI_AGENT_DEBUG) {
                ai_agent_log('Erreur API Anthropic : ' . $error_message, 'error');
            }

            return [
                'success' => false,
                'response' => null,
                'error' => "Erreur de connexion API : $error_message",
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_code !== 200) {
            $error_message = $response_data['error']['message'] ?? "Erreur inconnue (code $response_code)";
            if (defined('AI_AGENT_DEBUG') && AI_AGENT_DEBUG) {
                ai_agent_log('Erreur API Anthropic : ' . $error_message, 'error');
            }

            return [
                'success' => false,
                'response' => $response_data,
                'error' => "Erreur API : $error_message",
            ];
        }

        if (isset($response_data['content'][0]['text'])) {
            $content = $response_data['content'][0]['text'];
            if (defined('AI_AGENT_DEBUG') && AI_AGENT_DEBUG) {
                ai_agent_log('Réponse Anthropic reçue avec succès.', 'info');
            }

            return [
                'success' => true,
                'response' => $content,
                'error' => null,
            ];
        } else {
            if (defined('AI_AGENT_DEBUG') && AI_AGENT_DEBUG) {
                ai_agent_log('Format de réponse inattendu : ' . $response_body, 'warning');
            }

            return [
                'success' => false,
                'response' => $response_data,
                'error' => 'Format de réponse inattendu.',
            ];
        }
    }
}
