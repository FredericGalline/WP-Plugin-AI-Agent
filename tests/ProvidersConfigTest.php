<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for providers-config.php structure.
 */
class ProvidersConfigTest extends TestCase {

	public function setUp(): void {
		if ( ! defined( 'AIC_WP_LOADED' ) || ! AIC_WP_LOADED ) {
			$this->markTestSkipped( 'WordPress not loaded.' );
		}
	}

	public function test_providers_config_returns_array() {
		$config = ai_connector_get_providers_config();
		$this->assertIsArray( $config );
		$this->assertNotEmpty( $config );
	}

	public function test_all_expected_providers_exist() {
		$config   = ai_connector_get_providers_config();
		$expected = array( 'openai', 'anthropic', 'mistral', 'gemini', 'grok' );

		foreach ( $expected as $provider ) {
			$this->assertArrayHasKey( $provider, $config, "Provider '{$provider}' is missing." );
		}
	}

	public function test_each_provider_has_required_keys() {
		$config = ai_connector_get_providers_config();

		foreach ( $config as $provider_id => $provider ) {
			$this->assertArrayHasKey( 'name', $provider, "{$provider_id} missing 'name'." );
			$this->assertArrayHasKey( 'api_key_option', $provider, "{$provider_id} missing 'api_key_option'." );
			$this->assertArrayHasKey( 'models', $provider, "{$provider_id} missing 'models'." );
			$this->assertIsArray( $provider['models'], "{$provider_id} 'models' should be an array." );
			$this->assertNotEmpty( $provider['models'], "{$provider_id} should have at least one model." );
		}
	}

	public function test_each_model_has_required_keys() {
		$config       = ai_connector_get_providers_config();
		$required_keys = array( 'label', 'description', 'cost', 'max_tokens' );

		foreach ( $config as $provider_id => $provider ) {
			foreach ( $provider['models'] as $model_id => $model ) {
				foreach ( $required_keys as $key ) {
					$this->assertArrayHasKey(
						$key,
						$model,
						"{$provider_id}:{$model_id} missing '{$key}'."
					);
				}
				$this->assertIsInt( $model['max_tokens'], "{$provider_id}:{$model_id} max_tokens should be int." );
				$this->assertGreaterThan( 0, $model['max_tokens'], "{$provider_id}:{$model_id} max_tokens should be > 0." );
			}
		}
	}

	public function test_api_key_options_are_strings() {
		$config = ai_connector_get_providers_config();

		foreach ( $config as $provider_id => $provider ) {
			$this->assertIsString( $provider['api_key_option'], "{$provider_id} api_key_option should be string." );
			$this->assertNotEmpty( $provider['api_key_option'], "{$provider_id} api_key_option should not be empty." );
		}
	}
}
