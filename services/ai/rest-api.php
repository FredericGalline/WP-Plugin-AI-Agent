<?php

/**
 * REST API pour le plugin AI Agent
 *
 * Ce fichier gère l'enregistrement des endpoints REST pour le plugin.
 *
 * @package WP_Plugin_AI_Agent
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enregistre le endpoint REST pour envoyer des prompts aux fournisseurs d'IA
 */
function ai_agent_register_rest_routes()
{
    register_rest_route('ai-agent/v1', '/prompt', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'ai_agent_handle_prompt_request',
        'permission_callback' => function () {
            return apply_filters('ai_agent_rest_permission', current_user_can('manage_options'));
        },
        'args' => array(
            'prompt' => array(
                'required' => true,
                'type' => 'string',
                'description' => __('Le prompt à envoyer au fournisseur d\'IA.', 'ai-agent'),
            ),
            'args' => array(
                'required' => false,
                'type' => 'array',
                'description' => __('Paramètres supplémentaires pour le prompt, comme temperature, format, etc.', 'ai-agent'),
            ),
        ),
    ));
}
add_action('rest_api_init', 'ai_agent_register_rest_routes');

/**
 * Gère la requête REST pour envoyer un prompt
 *
 * @param WP_REST_Request $request La requête REST.
 * @return WP_REST_Response La réponse REST.
 */
function ai_agent_handle_prompt_request(WP_REST_Request $request)
{
    $prompt = $request->get_param('prompt');
    $args = $request->get_param('args') ?: array();

    // Appeler le gestionnaire de requêtes AI
    $result = AI_Request_Handler::send_prompt($prompt, $args);

    if ($result['success']) {
        return new WP_REST_Response(
            array(
                'success' => true,
                'response' => $result['response'],
            ),
            200
        );
    } else {
        return new WP_REST_Response(
            array(
                'success' => false,
                'error' => $result['error'],
            ),
            500
        );
    }
}

/**
 * Exemple d'utilisation de l'API REST
 *
 * Corps de la requête JSON attendu :
 * {
 *     "prompt": "Expliquez la théorie de la relativité.",
 *     "args": {
 *         "temperature": 0.7,
 *         "format": "texte"
 *     }
 * }
 *
 * Exemple de commande cURL :
 * curl -X POST https://example.com/wp-json/ai-agent/v1/prompt \
 *      -H "Content-Type: application/json" \
 *      -d '{"prompt": "Expliquez la théorie de la relativité.", "args": {"temperature": 0.7, "format": "texte"}}'
 *
 * Format de la réponse :
 * {
 *     "success": true,
 *     "response": "La théorie de la relativité est une théorie scientifique..."
 * }
 */
