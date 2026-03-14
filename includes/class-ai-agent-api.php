<?php
/**
 * AI Agent Public API
 *
 * Public facade for third-party plugins to send prompts to AI providers,
 * check configuration, and query available models. All methods are static.
 *
 * @package WP_Plugin_AI_Agent
 * @since   1.1.0
 *
 * @example
 * // Send a prompt
 * $result = AI_Agent_API::prompt( 'Résume ce texte en 3 lignes.' );
 * if ( $result['success'] ) {
 *     echo $result['response'];
 * }
 *
 * // Check if AI is configured
 * if ( AI_Agent_API::is_configured() ) { ... }
 *
 * // Get active model info
 * $model = AI_Agent_API::get_active_model();
 * // array( 'provider' => 'openai', 'model' => 'gpt-4.1' )
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Agent_API {

	/**
	 * Check if an AI provider is properly configured.
	 *
	 * Verifies that a provider is selected and its API key is set.
	 *
	 * @since 1.1.0
	 *
	 * @return bool True if a provider with a valid API key is active.
	 */
	public static function is_configured() {
		$model = self::get_active_model();

		if ( empty( $model['provider'] ) ) {
			return false;
		}

		if ( function_exists( 'ai_redactor_is_provider_configured' ) ) {
			return ai_redactor_is_provider_configured( $model['provider'] );
		}

		return false;
	}

	/**
	 * Send a prompt to the active AI provider.
	 *
	 * @since 1.1.0
	 *
	 * @param string $prompt The prompt text.
	 * @param array  $args   Optional. Additional arguments (reserved for future use:
	 *                       temperature, max_tokens, format, etc.).
	 *
	 * @return array {
	 *     @type bool        $success  Whether the request succeeded.
	 *     @type string|null $response AI-generated text on success, null on failure.
	 *     @type string      $error    Error message on failure, empty on success.
	 * }
	 */
	public static function prompt( $prompt, $args = array() ) {
		if ( ! class_exists( 'AI_Request_Handler' ) ) {
			return array(
				'success'  => false,
				'response' => null,
				'error'    => __( 'AI Agent plugin is not active.', 'wp-plugin-ai-agent' ),
			);
		}

		if ( ! self::is_configured() ) {
			return array(
				'success'  => false,
				'response' => null,
				'error'    => __( 'No AI provider is configured.', 'wp-plugin-ai-agent' ),
			);
		}

		/**
		 * Filter the prompt before sending it to the AI provider.
		 *
		 * @since 1.1.0
		 *
		 * @param string $prompt Original prompt text.
		 * @param array  $args   Additional arguments.
		 */
		$prompt = apply_filters( 'ai_agent_api_prompt', $prompt, $args );

		$result = AI_Request_Handler::send_prompt( $prompt );

		/**
		 * Fires after an AI prompt has been processed.
		 *
		 * @since 1.1.0
		 *
		 * @param array  $result The API result array.
		 * @param string $prompt The original prompt.
		 * @param array  $args   Additional arguments.
		 */
		do_action( 'ai_agent_api_prompt_completed', $result, $prompt, $args );

		return $result;
	}

	/**
	 * Get the currently active provider and model.
	 *
	 * @since 1.1.0
	 *
	 * @return array {
	 *     @type string $provider Provider identifier (e.g. 'openai', 'anthropic').
	 *     @type string $model    Model identifier (e.g. 'gpt-4.1', 'claude-3-opus').
	 * }
	 */
	public static function get_active_model() {
		if ( function_exists( 'ai_redactor_get_active_model' ) ) {
			return ai_redactor_get_active_model();
		}

		// Manual fallback if helpers aren't loaded yet.
		$combined = get_option( 'ai_agent_active_model', '' );

		if ( ! empty( $combined ) ) {
			$parts = explode( ':', $combined, 2 );
			if ( 2 === count( $parts ) ) {
				return array(
					'provider' => $parts[0],
					'model'    => $parts[1],
				);
			}
		}

		return array(
			'provider' => '',
			'model'    => '',
		);
	}

	/**
	 * Get all available AI providers and their models.
	 *
	 * @since 1.1.0
	 *
	 * @return array Associative array keyed by provider ID. Each entry contains
	 *               'name', 'models' (array of model definitions).
	 */
	public static function get_providers() {
		if ( function_exists( 'ai_redactor_get_providers_config' ) ) {
			return ai_redactor_get_providers_config();
		}

		$config_file = WP_PLUGIN_AI_AGENT_PATH . 'services/ai/providers-config.php';
		if ( file_exists( $config_file ) ) {
			return (array) require $config_file;
		}

		return array();
	}

	/**
	 * Get the list of configured (ready-to-use) providers.
	 *
	 * @since 1.1.0
	 *
	 * @return array Provider IDs that have a valid API key configured.
	 */
	public static function get_configured_providers() {
		$providers  = self::get_providers();
		$configured = array();

		foreach ( array_keys( $providers ) as $provider_id ) {
			if ( function_exists( 'ai_redactor_is_provider_configured' ) && ai_redactor_is_provider_configured( $provider_id ) ) {
				$configured[] = $provider_id;
			}
		}

		return $configured;
	}
}
