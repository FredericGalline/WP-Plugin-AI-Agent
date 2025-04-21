<?php

/**
 * Gestionnaire des requêtes vers les APIs d'intelligence artificielle
 *
 * Ce fichier contient une classe responsable de gérer les interactions avec les APIs
 * des différents fournisseurs d'IA (OpenAI, Anthropic, Mistral, etc.). Il standardise
 * l'envoi des prompts et la réception des réponses, en fonction du fournisseur et du modèle
 * actuellement sélectionnés dans les paramètres du plugin.
 *
 * @package WP_Plugin_AI_Agent
 * @subpackage Services\AI\Core
 *
 * @depends WordPress Options API
 * @depends wp_remote_post
 * @depends require_once
 *
 * @css N/A - Ce fichier est un gestionnaire de requêtes sans interface utilisateur directe
 *
 * @js N/A - Ce fichier est un gestionnaire de requêtes sans interface utilisateur directe
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer les requêtes vers les API d'IA
 */
class AI_Request_Handler
{
    /**
     * Envoie un prompt au modèle d'IA actuellement sélectionné.
     *
     * @param string $prompt Le texte du prompt à envoyer à l'IA.
     * @return array Tableau avec statut de succès, réponse et éventuelle erreur.
     */
    public static function send_prompt($prompt)
    {
        // Journaliser le début de la requête
        ai_agent_log('*** DÉBUT DE LA REQUÊTE ***', 'info');
        ai_agent_log('Prompt reçu pour traitement - Longueur: ' . strlen($prompt) . ' caractères', 'debug');

        // Récupérer la configuration des fournisseurs disponibles
        $providers_config = require dirname(dirname(__FILE__)) . '/providers-config.php';

        // Récupérer le fournisseur et le modèle actifs (nouveau format: provider:model)
        $active_model_combined = get_option('ai_agent_active_model', '');
        ai_agent_log('Modèle actif configuré: ' . $active_model_combined, 'info');

        $active_provider = '';
        $active_model = '';

        if (!empty($active_model_combined)) {
            $parts = explode(':', $active_model_combined);
            if (count($parts) === 2) {
                $active_provider = $parts[0];
                $active_model = $parts[1];
            }
        }

        // Journaliser les informations sur le modèle actif
        ai_agent_log('Tentative d\'envoi de prompt à ' . $active_model_combined, 'info');
        ai_agent_log('Fournisseur extrait: ' . $active_provider, 'debug');
        ai_agent_log('Modèle extrait: ' . $active_model, 'debug');

        // Vérifier si les informations de fournisseur et modèle sont valides
        if (isset($providers_config[$active_provider])) {
            ai_agent_log('Fournisseur trouvé dans la config', 'debug');

            if (isset($providers_config[$active_provider]['models'][$active_model])) {
                ai_agent_log('Modèle trouvé dans la config', 'debug');
            } else {
                ai_agent_log('Modèle NON trouvé dans la config. Modèles disponibles: ' .
                    implode(', ', array_keys($providers_config[$active_provider]['models'])), 'warning');
            }
        } else {
            ai_agent_log('Fournisseur NON trouvé dans la config. Fournisseurs disponibles: ' .
                implode(', ', array_keys($providers_config)), 'warning');
        }

        // Si le format combiné n'est pas utilisé, utiliser l'ancienne méthode
        if (empty($active_provider) || empty($active_model)) {
            $active_provider = get_option('ai_redactor_active_provider', '');
            $active_model = get_option('ai_redactor_' . $active_provider . '_active_model', '');

            ai_agent_log('Méthode alternative - Fournisseur: ' . $active_provider, 'debug');
            ai_agent_log('Méthode alternative - Modèle: ' . $active_model, 'debug');
        }

        // Vérifier si le fournisseur est valide
        if (empty($active_provider) || !isset($providers_config[$active_provider])) {
            ai_agent_log('Erreur: Fournisseur non valide ou non configuré', 'error');

            return [
                'success' => false,
                'response' => null,
                'error' => 'Aucun fournisseur d\'IA valide sélectionné. Veuillez en configurer un dans les paramètres d\'AI Agent.'
            ];
        }

        // Vérifier si le modèle est valide
        if (empty($active_model) || !isset($providers_config[$active_provider]['models'][$active_model])) {
            ai_agent_log('Erreur: Modèle non valide ou non configuré', 'error');

            return [
                'success' => false,
                'response' => null,
                'error' => 'Aucun modèle valide sélectionné pour ' . $providers_config[$active_provider]['name'] . '. Veuillez en sélectionner un dans les paramètres d\'AI Agent.'
            ];
        }

        // Récupérer la clé API
        $api_key_option = $providers_config[$active_provider]['api_key_option'];
        $api_key = get_option($api_key_option);

        // Vérifier si la clé API est disponible
        if (empty($api_key)) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'Clé API non configurée pour ' . $providers_config[$active_provider]['name'] . '. Veuillez l\'ajouter dans les paramètres d\'AI Agent.'
            ];
        }

        // Précharger toutes les classes d'API pour éviter des require_once répétitifs
        $api_file_map = [
            'openai' => 'OpenAI.php',
            'anthropic' => 'Anthropic.php',
            'mistral' => 'Mistral.php',
            'google' => 'Gemini.php',
            'gemini' => 'Gemini.php',
            'grok' => 'Grok.php'
        ];

        // Valider que le fichier API existe pour ce fournisseur
        if (!isset($api_file_map[$active_provider])) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'Aucune implémentation trouvée pour le fournisseur: ' . $active_provider
            ];
        }

        // Charger le fichier API correspondant
        $api_file = $api_file_map[$active_provider];
        require_once __DIR__ . '/../api/' . $api_file;

        // Mapper le nom du fournisseur vers la classe API correspondante
        $api_class_map = [
            'openai' => 'AI_OpenAI_API',
            'anthropic' => 'AI_Anthropic_API',
            'mistral' => 'AI_Mistral_API',
            'google' => 'AI_Gemini_API',
            'gemini' => 'AI_Gemini_API',
            'grok' => 'AI_Grok_API'
        ];

        $api_class = $api_class_map[$active_provider];

        ai_agent_log('Délégation de la requête à la classe: ' . $api_class, 'debug');

        // Appeler la méthode send_request de la classe API correspondante
        return $api_class::send_request($prompt, $active_model, $api_key);
    }
}
