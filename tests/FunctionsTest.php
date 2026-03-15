<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for ai-connector-functions.php.
 */
class FunctionsTest extends TestCase {

	public function setUp(): void {
		if ( ! defined( 'AIC_WP_LOADED' ) || ! AIC_WP_LOADED ) {
			$this->markTestSkipped( 'WordPress not loaded.' );
		}
	}

	public function tearDown(): void {
		// Clean up options after each test.
		delete_option( 'ai_connector_active_model' );
		delete_option( 'ai_agent_active_model' );
		delete_option( 'ai_redactor_active_model' );
		delete_option( 'ai_redactor_active_provider' );
	}

	// --- Model aliases ---

	public function test_get_model_aliases_returns_array() {
		$aliases = ai_connector_get_model_aliases();
		$this->assertIsArray( $aliases );
		$this->assertNotEmpty( $aliases );
	}

	public function test_aliases_map_old_models_to_new() {
		$aliases = ai_connector_get_model_aliases();
		$this->assertArrayHasKey( 'openai:gpt-3.5-turbo', $aliases );
		$this->assertEquals( 'openai:gpt-4o-mini', $aliases['openai:gpt-3.5-turbo'] );
	}

	// --- Normalize active model ---

	public function test_normalize_empty_string_returns_empty() {
		$result = ai_connector_normalize_active_model_string( '' );
		$this->assertSame( '', $result );
	}

	public function test_normalize_valid_model_returns_itself() {
		$result = ai_connector_normalize_active_model_string( 'openai:gpt-4o' );
		$this->assertSame( 'openai:gpt-4o', $result );
	}

	public function test_normalize_aliased_model_returns_new_name() {
		$result = ai_connector_normalize_active_model_string( 'openai:gpt-3.5-turbo' );
		$this->assertSame( 'openai:gpt-4o-mini', $result );
	}

	public function test_normalize_invalid_format_returns_default() {
		$result = ai_connector_normalize_active_model_string( 'no-colon-here' );
		// Should return a default model (first available) or empty.
		$this->assertIsString( $result );
	}

	public function test_normalize_unknown_provider_returns_default() {
		$result = ai_connector_normalize_active_model_string( 'fakeprovider:fakemodel' );
		$default = ai_connector_get_default_model_string();
		$this->assertSame( $default, $result );
	}

	public function test_normalize_unknown_model_returns_provider_default() {
		$result = ai_connector_normalize_active_model_string( 'openai:nonexistent-model' );
		$provider_default = ai_connector_get_default_model_string( 'openai' );
		$this->assertSame( $provider_default, $result );
	}

	// --- Default model ---

	public function test_get_default_model_string_returns_string() {
		$result = ai_connector_get_default_model_string();
		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
		$this->assertStringContainsString( ':', $result );
	}

	public function test_get_default_model_for_provider() {
		$result = ai_connector_get_default_model_string( 'anthropic' );
		$this->assertStringStartsWith( 'anthropic:', $result );
	}

	// --- Active model get/set ---

	public function test_set_and_get_active_model() {
		ai_connector_set_active_model( 'anthropic:claude-opus-4-6' );

		$model = ai_connector_get_active_model();
		$this->assertSame( 'anthropic', $model['provider'] );
		$this->assertSame( 'claude-opus-4-6', $model['model'] );
	}

	public function test_set_active_model_syncs_legacy_options() {
		ai_connector_set_active_model( 'openai:gpt-4o' );

		$this->assertSame( 'openai:gpt-4o', get_option( 'ai_connector_active_model' ) );
		$this->assertSame( 'openai:gpt-4o', get_option( 'ai_agent_active_model' ) );
		$this->assertSame( 'openai:gpt-4o', get_option( 'ai_redactor_active_model' ) );
		$this->assertSame( 'openai', get_option( 'ai_redactor_active_provider' ) );
	}

	public function test_get_active_model_string_returns_string() {
		ai_connector_set_active_model( 'mistral:mistral-large-2512' );

		$result = ai_connector_get_active_model_string();
		$this->assertSame( 'mistral:mistral-large-2512', $result );
	}

	public function test_get_active_model_no_config_returns_empty() {
		delete_option( 'ai_connector_active_model' );
		delete_option( 'ai_agent_active_model' );
		delete_option( 'ai_redactor_active_model' );
		delete_option( 'ai_redactor_active_provider' );

		$model = ai_connector_get_active_model();
		$this->assertSame( '', $model['provider'] );
		$this->assertSame( '', $model['model'] );
	}

	// --- Provider API key ---

	public function test_get_provider_api_key_no_key_returns_empty() {
		$key = ai_connector_get_provider_api_key( 'openai' );
		$this->assertIsString( $key );
	}

	public function test_is_provider_configured_without_key() {
		delete_option( 'ai_redactor_openai_api_key' );
		$this->assertFalse( ai_connector_is_provider_configured( 'openai' ) );
	}

	public function test_is_provider_configured_with_key() {
		update_option( 'ai_redactor_openai_api_key', 'sk-test-12345' );
		$this->assertTrue( ai_connector_is_provider_configured( 'openai' ) );
		delete_option( 'ai_redactor_openai_api_key' );
	}

	public function test_unknown_provider_not_configured() {
		$this->assertFalse( ai_connector_is_provider_configured( 'nonexistent' ) );
	}

	// --- Active provider ---

	public function test_get_active_provider_returns_string_or_false() {
		ai_connector_set_active_model( 'gemini:gemini-2.5-pro' );
		$provider = ai_connector_get_active_provider();
		$this->assertSame( 'gemini', $provider );
	}

	public function test_get_active_provider_no_model_returns_false() {
		delete_option( 'ai_connector_active_model' );
		delete_option( 'ai_agent_active_model' );
		delete_option( 'ai_redactor_active_model' );
		delete_option( 'ai_redactor_active_provider' );

		$provider = ai_connector_get_active_provider();
		$this->assertFalse( $provider );
	}
}
