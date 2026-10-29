<?php

/**
 * Plugin Name: AI Connector
 * Description: Connecteur IA transverse pour WordPress avec API publique, gestion des fournisseurs et outils de diagnostic.
 * Version: 1.2.0
 * Requires PHP: 8.2
 * Author: Frederic Galline
 * Author URI: https://example.com
 * License: GPL2
 * Text Domain: ai-connector
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
if (!defined('AI_CONNECTOR_PATH')) {
    define('AI_CONNECTOR_PATH', plugin_dir_path(__FILE__));
}
if (!defined('AI_CONNECTOR_URL')) {
    define('AI_CONNECTOR_URL', plugin_dir_url(__FILE__));
}
if (!defined('AI_CONNECTOR_VERSION')) {
    define('AI_CONNECTOR_VERSION', '1.2.0');
}
if (!defined('WP_PLUGIN_AI_CONNECTOR_PATH')) {
    define('WP_PLUGIN_AI_CONNECTOR_PATH', AI_CONNECTOR_PATH);
}
if (!defined('WP_PLUGIN_AI_CONNECTOR_URL')) {
    define('WP_PLUGIN_AI_CONNECTOR_URL', AI_CONNECTOR_URL);
}
if (!defined('WP_PLUGIN_AI_CONNECTOR_VERSION')) {
    define('WP_PLUGIN_AI_CONNECTOR_VERSION', AI_CONNECTOR_VERSION);
}

// Backward compatibility constants.
if (!defined('WP_PLUGIN_AI_AGENT_PATH')) {
    define('WP_PLUGIN_AI_AGENT_PATH', AI_CONNECTOR_PATH);
}
if (!defined('WP_PLUGIN_AI_AGENT_URL')) {
    define('WP_PLUGIN_AI_AGENT_URL', AI_CONNECTOR_URL);
}
if (!defined('WP_PLUGIN_AI_AGENT_VERSION')) {
    define('WP_PLUGIN_AI_AGENT_VERSION', AI_CONNECTOR_VERSION);
}

// Debug flags.
if (!defined('AI_CONNECTOR_DEBUG')) {
    define('AI_CONNECTOR_DEBUG', defined('AI_AGENT_DEBUG') ? AI_AGENT_DEBUG : false);
}
if (!defined('AI_AGENT_DEBUG')) {
    define('AI_AGENT_DEBUG', AI_CONNECTOR_DEBUG);
}
if (!defined('AI_REDACTOR_DEBUG')) {
    define('AI_REDACTOR_DEBUG', AI_CONNECTOR_DEBUG);
}

require_once AI_CONNECTOR_PATH . 'includes/class-ai-connector-logger.php';
require_once AI_CONNECTOR_PATH . 'includes/ai-connector-functions.php';

// Initialiser le logger global avec un chemin absolu pour etre sur.
global $ai_connector_logger;
$log_path = AI_CONNECTOR_PATH . 'logs/ai-connector.log';
$ai_connector_logger = new AI_Connector_Logger($log_path);

if (!function_exists('ai_connector_log')) {
    /**
     * Fonction helper pour journaliser des messages.
     *
     * @param string $message Le message a journaliser.
     * @param string $level   Le niveau de journalisation.
     * @param array  $context Contexte supplementaire.
     * @return void
     */
    function ai_connector_log($message, $level = 'debug', $context = [])
    {
        global $ai_connector_logger;

        if (!$ai_connector_logger) {
            return;
        }

        switch ($level) {
            case 'error':
                $ai_connector_logger->error($message, $context);
                break;
            case 'warning':
                $ai_connector_logger->warning($message, $context);
                break;
            case 'info':
                $ai_connector_logger->info($message, $context);
                break;
            case 'debug':
            default:
                $ai_connector_logger->debug($message, $context);
                break;
        }
    }
}

if (!function_exists('ai_agent_log')) {
    /**
     * Alias de compatibilite vers le logger du connecteur.
     *
     * @param string $message Le message a journaliser.
     * @param string $level   Le niveau de journalisation.
     * @param array  $context Contexte supplementaire.
     * @return void
     */
    function ai_agent_log($message, $level = 'debug', $context = [])
    {
        ai_connector_log($message, $level, $context);
    }
}

ai_connector_log('Plugin AI Connector initialisé', 'info');

/**
 * Recupere l'etat du mode debogage pour le plugin.
 *
 * @return bool True si le mode debogage est active, false sinon.
 */
function ai_connector_get_debug_mode()
{
    return defined('AI_CONNECTOR_DEBUG') && AI_CONNECTOR_DEBUG === true;
}

if (!function_exists('ai_agent_get_debug_mode')) {
    /**
     * Alias de compatibilite AI Agent.
     *
     * @return bool
     */
    function ai_agent_get_debug_mode()
    {
        return ai_connector_get_debug_mode();
    }
}

function ai_connector_load_textdomain()
{
    load_plugin_textdomain('ai-connector', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'ai_connector_load_textdomain');

require_once AI_CONNECTOR_PATH . 'includes/loader.php';
require_once AI_CONNECTOR_PATH . 'services/ai/ai-loader.php';
require_once AI_CONNECTOR_PATH . 'includes/class-ai-connector-api.php';

/**
 * Initialise le système de mise à jour automatique via GitHub.
 */
function ai_connector_init_update_checker()
{
    $update_checker_file = AI_CONNECTOR_PATH . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';

    if (!file_exists($update_checker_file)) {
        return;
    }

    require_once $update_checker_file;

    if (!class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
        return;
    }

    $update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/FredericGalline/WP-Plugin-AI-Connector/',
        __FILE__,
        'ai-connector'
    );

    $update_checker->getVcsApi()->enableReleaseAssets();

    // Support both new and legacy token constant names.
    $token = defined('AI_CONNECTOR_GITHUB_TOKEN') ? AI_CONNECTOR_GITHUB_TOKEN
           : (defined('AI_AGENT_GITHUB_TOKEN') ? AI_AGENT_GITHUB_TOKEN : '');
    if ($token) {
        $update_checker->setAuthentication($token);
    }
}
ai_connector_init_update_checker();

// Backward compatibility: class alias for AI_Agent_API.
if (!class_exists('AI_Agent_API') && class_exists('AI_Connector_API')) {
    class_alias('AI_Connector_API', 'AI_Agent_API');
}
