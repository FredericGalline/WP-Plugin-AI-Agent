<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for backward compatibility after AI Agent → AI Connector rename.
 */
class BackwardCompatTest extends TestCase {

	public function setUp(): void {
		if ( ! defined( 'AIC_WP_LOADED' ) || ! AIC_WP_LOADED ) {
			$this->markTestSkipped( 'WordPress not loaded.' );
		}
	}

	// --- Constants ---

	public function test_legacy_path_constant_exists() {
		$this->assertTrue( defined( 'WP_PLUGIN_AI_AGENT_PATH' ), 'WP_PLUGIN_AI_AGENT_PATH should be defined.' );
		$this->assertSame( AI_CONNECTOR_PATH, WP_PLUGIN_AI_AGENT_PATH );
	}

	public function test_legacy_url_constant_exists() {
		$this->assertTrue( defined( 'WP_PLUGIN_AI_AGENT_URL' ), 'WP_PLUGIN_AI_AGENT_URL should be defined.' );
		$this->assertSame( AI_CONNECTOR_URL, WP_PLUGIN_AI_AGENT_URL );
	}

	public function test_legacy_version_constant_exists() {
		$this->assertTrue( defined( 'WP_PLUGIN_AI_AGENT_VERSION' ), 'WP_PLUGIN_AI_AGENT_VERSION should be defined.' );
		$this->assertSame( AI_CONNECTOR_VERSION, WP_PLUGIN_AI_AGENT_VERSION );
	}

	public function test_debug_constant_exists() {
		$this->assertTrue( defined( 'AI_AGENT_DEBUG' ), 'AI_AGENT_DEBUG should be defined.' );
	}

	// --- Functions ---

	public function test_ai_agent_log_function_exists() {
		$this->assertTrue( function_exists( 'ai_agent_log' ), 'ai_agent_log() should exist.' );
	}

	public function test_ai_agent_get_debug_mode_exists() {
		$this->assertTrue( function_exists( 'ai_agent_get_debug_mode' ), 'ai_agent_get_debug_mode() should exist.' );
	}

	public function test_ai_agent_get_providers_config_exists() {
		$this->assertTrue( function_exists( 'ai_agent_get_providers_config' ), 'ai_agent_get_providers_config() should exist.' );
	}

	public function test_ai_agent_get_providers_config_returns_same_as_connector() {
		$this->assertSame(
			ai_connector_get_providers_config(),
			ai_agent_get_providers_config()
		);
	}

	public function test_ai_agent_get_debug_mode_returns_same_as_connector() {
		$this->assertSame(
			ai_connector_get_debug_mode(),
			ai_agent_get_debug_mode()
		);
	}

	// --- Class alias ---

	public function test_ai_agent_api_class_exists() {
		$this->assertTrue( class_exists( 'AI_Agent_API' ) );
	}

	public function test_ai_agent_api_is_alias_of_ai_connector_api() {
		$connector = new ReflectionClass( 'AI_Connector_API' );
		$agent     = new ReflectionClass( 'AI_Agent_API' );

		$this->assertSame( $connector->getName(), $agent->getName() );
	}

	// --- REST routes ---

	public function test_legacy_rest_route_registered() {
		$server = rest_get_server();
		$routes = $server->get_routes();

		$this->assertArrayHasKey( '/ai-connector/v1/prompt', $routes, 'New REST route should exist.' );
		$this->assertArrayHasKey( '/ai-agent/v1/prompt', $routes, 'Legacy REST route should exist.' );
	}
}
