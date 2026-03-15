<?php

/**
 * Loader for AI Connector plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once AI_CONNECTOR_PATH . 'includes/admin-menu.php';
require_once AI_CONNECTOR_PATH . 'includes/admin-enqueue.php';

if (!function_exists('ai_agent_get_providers_config')) {
    /**
     * Alias de compatibilite AI Agent.
     *
     * @return array
     */
    function ai_agent_get_providers_config()
    {
        return ai_connector_get_providers_config();
    }
}

/**
 * Initialise les handlers admin pour la page des connecteurs IA.
 *
 * @return void
 */
function ai_connector_bootstrap_admin_handlers()
{
    static $bootstrapped = false;

    if ($bootstrapped || !is_admin()) {
        return;
    }

    require_once AI_CONNECTOR_PATH . 'services/ai/core/ai-admin-form-handler.php';
    require_once AI_CONNECTOR_PATH . 'services/ai/core/ai-admin-ajax.php';

    $providers_config = ai_connector_get_providers_config();

    new AI_Connector_Form_Handler($providers_config);
    new AI_Connector_Ajax_Handler($providers_config);

    $bootstrapped = true;
}

ai_connector_bootstrap_admin_handlers();
