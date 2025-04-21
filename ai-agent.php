<?php

/**
 * Plugin Name: AI Agent
 * Description: A WordPress plugin that acts as an AI agent to handle prompts and responses from various AI providers.
 * Version: 1.0.0
 * Author: Frederic Galline
 * Author URI: https://example.com
 * License: GPL2
 * Text Domain: wp-plugin-ai-agent
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants for the plugin
if (!defined('WP_PLUGIN_AI_AGENT_PATH')) {
    define('WP_PLUGIN_AI_AGENT_PATH', plugin_dir_path(__FILE__));
}
if (!defined('WP_PLUGIN_AI_AGENT_URL')) {
    define('WP_PLUGIN_AI_AGENT_URL', plugin_dir_url(__FILE__));
}
if (!defined('WP_PLUGIN_AI_AGENT_VERSION')) {
    define('WP_PLUGIN_AI_AGENT_VERSION', '1.0.0');
}

// Définir la constante de débogage (désactivée par défaut)
// Vous pouvez l'activer dans wp-config.php en ajoutant: define('AI_AGENT_DEBUG', true);
if (!defined('AI_AGENT_DEBUG')) {
    define('AI_AGENT_DEBUG', false);
}

// Pour la rétrocompatibilité avec le code du plugin AI-Redactor
if (!defined('AI_REDACTOR_DEBUG')) {
    define('AI_REDACTOR_DEBUG', AI_AGENT_DEBUG);
}

// Inclure les fichiers nécessaires
require_once plugin_dir_path(__FILE__) . 'includes/class-ai-agent-logger.php';

// Initialiser le logger global avec un chemin absolu pour être sûr
global $ai_agent_logger;
$log_path = plugin_dir_path(__FILE__) . 'logs/ai-agent.log';
$ai_agent_logger = new AI_Agent_Logger($log_path);

// Fonction helper pour faciliter la journalisation
if (!function_exists('ai_agent_log')) {
    /**
     * Fonction helper pour journaliser des messages
     *
     * @param string $message Le message à journaliser
     * @param string $level Le niveau de journalisation (error, warning, info, debug)
     * @param array $context Contexte supplémentaire
     * @return void
     */
    function ai_agent_log($message, $level = 'debug', $context = [])
    {
        global $ai_agent_logger;

        if (!$ai_agent_logger) {
            return;
        }

        switch ($level) {
            case 'error':
                $ai_agent_logger->error($message, $context);
                break;
            case 'warning':
                $ai_agent_logger->warning($message, $context);
                break;
            case 'info':
                $ai_agent_logger->info($message, $context);
                break;
            case 'debug':
            default:
                $ai_agent_logger->debug($message, $context);
                break;
        }
    }
}

// Un premier test pour vérifier que la journalisation fonctionne
ai_agent_log('Plugin AI Agent initialisé', 'info');

// Include the AI loader file
require_once plugin_dir_path(__FILE__) . 'includes/loader.php';
require_once plugin_dir_path(__FILE__) . 'services/ai/ai-loader.php';
