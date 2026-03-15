<?php

/**
 * REST API pour le plugin AI Connector
 *
 * Ce fichier gère l'enregistrement des endpoints REST pour le plugin.
 *
 * @package WP_Plugin_AI_Connector
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

function ai_connector_register_rest_routes()
{
    $route_args = array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'ai_connector_handle_prompt_request',
        'permission_callback' => function () {
            $allowed = current_user_can('manage_options');
            $allowed = apply_filters('ai_agent_rest_permission', $allowed);
            return apply_filters('ai_connector_rest_permission', $allowed);
        },
        'args' => array(
            'prompt' => array(
                'required' => true,
                'type' => 'string',
                'description' => __('Le prompt a envoyer au fournisseur d\'IA.', 'ai-connector'),
            ),
            'args' => array(
                'required' => false,
                'type' => 'array',
                'description' => __('Parametres supplementaires pour le prompt, comme temperature, format, etc.', 'ai-connector'),
            ),
        ),
    );

    register_rest_route('ai-connector/v1', '/prompt', $route_args);
    // Backward compat: keep legacy route.
    register_rest_route('ai-agent/v1', '/prompt', $route_args);
}
add_action('rest_api_init', 'ai_connector_register_rest_routes');

/**
 * Gère la requête REST pour envoyer un prompt
 *
 * @param WP_REST_Request $request La requête REST.
 * @return WP_REST_Response La réponse REST.
 */
function ai_connector_handle_prompt_request(WP_REST_Request $request)
{
    $prompt = $request->get_param('prompt');
    $args = $request->get_param('args') ?: array();

    $result = AI_Connector_API::prompt($prompt, is_array($args) ? $args : array());

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

if (!function_exists('ai_agent_handle_prompt_request')) {
    /**
     * Alias de compatibilite AI Agent.
     *
     * @param WP_REST_Request $request Requete REST.
     * @return WP_REST_Response
     */
    function ai_agent_handle_prompt_request(WP_REST_Request $request)
    {
        return ai_connector_handle_prompt_request($request);
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
 * curl -X POST https://example.com/wp-json/ai-connector/v1/prompt \
 *      -H "Content-Type: application/json" \
 *      -d '{"prompt": "Expliquez la théorie de la relativité.", "args": {"temperature": 0.7, "format": "texte"}}'
 *
 * Format de la réponse :
 * {
 *     "success": true,
 *     "response": "La théorie de la relativité est une théorie scientifique..."
 * }
 */
