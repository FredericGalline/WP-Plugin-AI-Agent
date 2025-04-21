<?php

/**
 * Client d'API pour communiquer avec le service OpenAI
 *
 * Ce fichier contient une classe qui permet d'interagir avec l'API OpenAI,
 * d'envoyer des requêtes formatées, et de traiter les réponses ou erreurs.
 * Il implémente les spécifications de l'API OpenAI selon la documentation officielle.
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
 * @ai Ce fichier contient une classe qui gère exclusivement les communications HTTP avec l'API OpenAI.
 * Sa responsabilité est strictement limitée à : (1) formater correctement les requêtes selon l'API OpenAI,
 * (2) effectuer les appels HTTP en utilisant l'API WordPress, (3) parser les réponses JSON reçues et
 * (4) gérer les erreurs de communication ou de traitement. Il n'applique aucune logique métier aux prompts
 * ni aux réponses, ne modifie pas le contenu des messages, et ne prend pas de décisions sur les paramètres
 * de génération (temperature, max_tokens, etc.) qui sont passés par le code appelant. Il se contente de
 * servir de couche de transport entre le plugin WordPress et le service OpenAI. Tous les détails
 * d'authentification et de formatage spécifiques à l'API OpenAI sont encapsulés dans cette classe.
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour interagir avec l'API OpenAI
 */
class AI_OpenAI_API
{
    public static function send_request($prompt, $model, $api_key)
    {
        if (defined('AI_AGENT_DEBUG') && AI_AGENT_DEBUG) {
            ai_agent_log('Configuration de la requête OpenAI - Modèle: ' . $model, 'debug');
        }

        $api_url = 'https://api.openai.com/v1/chat/completions';

        $request_body = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 2048
        ];

        $args = [
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($request_body)
        ];

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'Erreur de connexion API: ' . $response->get_error_message()
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200) {
            $error_message = $data['error']['message'] ?? "Erreur inconnue (code $code)";
            return [
                'success' => false,
                'response' => $data,
                'error' => 'Erreur API: ' . $error_message
            ];
        }

        if (isset($data['choices'][0]['message']['content'])) {
            return [
                'success' => true,
                'response' => $data['choices'][0]['message']['content'],
                'error' => null
            ];
        }

        return [
            'success' => false,
            'response' => $data,
            'error' => 'Format de réponse inattendu'
        ];
    }
}
