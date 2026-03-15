<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for AI_Connector_API class.
 */
class ApiClassTest extends TestCase {

	public function setUp(): void {
		if ( ! defined( 'AIC_WP_LOADED' ) || ! AIC_WP_LOADED ) {
			$this->markTestSkipped( 'WordPress not loaded.' );
		}
	}

	public function tearDown(): void {
		delete_option( 'ai_connector_active_model' );
		delete_option( 'ai_agent_active_model' );
		delete_option( 'ai_redactor_active_model' );
		delete_option( 'ai_redactor_active_provider' );
	}

	public function test_class_exists() {
		$this->assertTrue( class_exists( 'AI_Connector_API' ) );
	}

	public function test_backward_compat_class_alias() {
		$this->assertTrue( class_exists( 'AI_Agent_API' ), 'AI_Agent_API class alias should exist.' );
	}

	public function test_get_providers_returns_array() {
		$providers = AI_Connector_API::get_providers();
		$this->assertIsArray( $providers );
		$this->assertNotEmpty( $providers );
	}

	public function test_get_active_model_returns_array_with_keys() {
		$model = AI_Connector_API::get_active_model();
		$this->assertIsArray( $model );
		$this->assertArrayHasKey( 'provider', $model );
		$this->assertArrayHasKey( 'model', $model );
	}

	public function test_is_configured_without_key_returns_false() {
		ai_connector_set_active_model( 'openai:gpt-4o' );
		delete_option( 'ai_redactor_openai_api_key' );

		$this->assertFalse( AI_Connector_API::is_configured() );
	}

	public function test_is_configured_with_key_returns_true() {
		ai_connector_set_active_model( 'openai:gpt-4o' );
		update_option( 'ai_redactor_openai_api_key', 'sk-test-key' );

		$this->assertTrue( AI_Connector_API::is_configured() );
		delete_option( 'ai_redactor_openai_api_key' );
	}

	public function test_get_configured_providers_returns_array() {
		$configured = AI_Connector_API::get_configured_providers();
		$this->assertIsArray( $configured );
	}

	public function test_prompt_without_config_returns_error() {
		delete_option( 'ai_connector_active_model' );
		delete_option( 'ai_agent_active_model' );
		delete_option( 'ai_redactor_active_model' );

		$result = AI_Connector_API::prompt( 'Hello' );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
		$this->assertNotEmpty( $result['error'] );
	}

	public function test_request_delegates_to_prompt() {
		delete_option( 'ai_connector_active_model' );
		delete_option( 'ai_agent_active_model' );
		delete_option( 'ai_redactor_active_model' );

		$result = AI_Connector_API::request( array( 'prompt' => 'Hello' ) );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
	}

	public function test_alias_api_class_has_same_methods() {
		$connector_methods = get_class_methods( 'AI_Connector_API' );
		$agent_methods     = get_class_methods( 'AI_Agent_API' );

		$this->assertEquals( $connector_methods, $agent_methods );
	}
}
