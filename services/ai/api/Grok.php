<?php

/**
 * Client d'API pour communiquer avec le service Grok (xAI)
 *
 * Ce fichier contient une classe qui permet d'interagir avec l'API Grok de xAI,
 * d'envoyer des requêtes formatées, et de traiter les réponses ou erreurs.
 * Il implémente les spécifications de l'API Grok selon la documentation officielle.
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
 * @ai Ce fichier contient une classe qui gère exclusivement les communications HTTP avec l'API Grok de xAI.
 * Sa responsabilité est strictement limitée à : (1) formater correctement les requêtes selon l'API Grok,
 * (2) effectuer les appels HTTP en utilisant l'API WordPress, (3) parser les réponses JSON reçues et
 * (4) gérer les erreurs de communication ou de traitement. Il n'applique aucune logique métier aux prompts
 * ni aux réponses, ne modifie pas le contenu des messages, et ne prend pas de décisions sur les paramètres
 * de génération (temperature, max_tokens, etc.) qui sont passés par le code appelant. Il se contente de
 * servir de couche de transport entre le plugin WordPress et le service Grok. Tous les détails
 * d'authentification et de formatage spécifiques à l'API Grok sont encapsulés dans cette classe.
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour interagir avec l'API Grok (xAI)
 */
class AI_Grok_API
{
    public static function send_request($prompt, $model, $api_key)
    {
        if (defined('AI_AGENT_DEBUG') && AI_AGENT_DEBUG) {
            ai_agent_log('Configuration de la requête Grok - Modèle: ' . $model, 'debug');
        }

        $api_url = 'https://api.x.ai/v1/chat/completions';

        $request_body = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are Grok, a helpful assistant.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 2048,
            'stream' => false
        ];

        $args = [
            'timeout' => 90,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json'
            ],
            'body' => json_encode($request_body)
        ];

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            return [
                'success'  => false,
                'response' => null,
                'error'    => 'Erreur de connexion API : ' . $response->get_error_message()
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200 || !isset($data['choices'][0]['message']['content'])) {
            $error_message = $data['error']['message'] ?? "Erreur inconnue (code $code)";
            return [
                'success'  => false,
                'response' => $data,
                'error'    => 'Erreur API : ' . $error_message
            ];
        }

        return [
            'success'  => true,
            'response' => $data['choices'][0]['message']['content'],
            'error'    => null
        ];
    }
}
