<?php
/**
 * AI Connector Public API
 *
 * Public facade for third-party plugins to send prompts to AI providers,
 * check configuration, and query available models. All methods are static.
 *
 * @package WP_Plugin_AI_Connector
 * @since   1.1.0
 *
 * @example
 * // Send a prompt
 * $result = AI_Connector_API::prompt( 'Résume ce texte en 3 lignes.' );
 * if ( $result['success'] ) {
 *     echo $result['response'];
 * }
 *
 * // Check if AI is configured
 * if ( AI_Connector_API::is_configured() ) { ... }
 *
 * // Get active model info
 * $model = AI_Connector_API::get_active_model();
 * // array( 'provider' => 'openai', 'model' => 'gpt-4.1' )
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AI_Connector_API' ) ) {
    class AI_Connector_API {

        /**
         * Check if an AI provider is properly configured.
         *
         * @return bool
         */
        public static function is_configured() {
            $model = self::get_active_model();

            if ( empty( $model['provider'] ) ) {
                return false;
            }

            return ai_connector_is_provider_configured( $model['provider'] );
        }

        /**
         * Send a prompt to the active AI provider.
         *
         * @param string $prompt Prompt text.
         * @param array  $args   Optional request arguments.
         * @return array
         */
        public static function prompt( $prompt, $args = array() ) {
            if ( ! class_exists( 'AI_Connector_Request_Handler' ) ) {
                $handler_file = AI_CONNECTOR_PATH . 'services/ai/core/ai-request-handler.php';
                if ( file_exists( $handler_file ) ) {
                    require_once $handler_file;
                }
            }

            if ( ! class_exists( 'AI_Connector_Request_Handler' ) ) {
                return array(
                    'success'  => false,
                    'response' => null,
                    'error'    => __( 'AI Connector request handler is unavailable.', 'ai-connector' ),
                );
            }

            if ( ! self::is_configured() && ( empty( $args['provider'] ) || empty( $args['model'] ) ) ) {
                return array(
                    'success'  => false,
                    'response' => null,
                    'error'    => __( 'No AI provider is configured.', 'ai-connector' ),
                );
            }

            $prompt = apply_filters( 'ai_agent_api_prompt', $prompt, $args );
            $prompt = apply_filters( 'ai_connector_api_prompt', $prompt, $args );

            $result = AI_Connector_Request_Handler::send_prompt( $prompt, $args );

            do_action( 'ai_agent_api_prompt_completed', $result, $prompt, $args );
            do_action( 'ai_connector_api_prompt_completed', $result, $prompt, $args );

            return $result;
        }

        /**
         * Envoie une requete structuree au connecteur.
         *
         * @param array $request Requete contenant au minimum prompt.
         * @return array
         */
        public static function request( array $request ) {
            $prompt = isset( $request['prompt'] ) ? (string) $request['prompt'] : '';
            unset( $request['prompt'] );

            return self::prompt( $prompt, $request );
        }

        /**
         * Retourne le modele actif.
         *
         * @return array
         */
        public static function get_active_model() {
            return ai_connector_get_active_model();
        }

        /**
         * Retourne tous les fournisseurs disponibles.
         *
         * @return array
         */
        public static function get_providers() {
            return ai_connector_get_providers_config();
        }

        /**
         * Retourne les fournisseurs deja configures.
         *
         * @return array
         */
        public static function get_configured_providers() {
            $providers  = self::get_providers();
            $configured = array();

            foreach ( array_keys( $providers ) as $provider_id ) {
                if ( ai_connector_is_provider_configured( $provider_id ) ) {
                    $configured[] = $provider_id;
                }
            }

            return $configured;
        }
    }
}

// Backward compat: AI_Agent_API alias.
if ( ! class_exists( 'AI_Agent_API', false ) ) {
    class_alias( 'AI_Connector_API', 'AI_Agent_API' );
}
