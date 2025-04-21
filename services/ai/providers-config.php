<?php

/**
 * Configuration des fournisseurs d'IA pour AI Redactor
 *
 * Ce fichier contient la configuration des fournisseurs d'IA disponibles pour le plugin.
 * Chaque fournisseur est défini avec ses modèles, ses options de configuration, et ses
 * paramètres spécifiques. Ces informations sont utilisées pour gérer les connecteurs IA
 * dans l'interface d'administration et pour envoyer des requêtes aux APIs correspondantes.
 *
 * @package AI_Redactor
 * @subpackage Services\AI
 *
 * @depends WordPress Options API
 *
 * @ai Ce fichier est exclusivement dédié à la définition des fournisseurs d'IA et de leurs modèles.
 * Il ne contient aucune logique métier, ni de traitement des données. Sa responsabilité est strictement
 * limitée à fournir une structure de configuration centralisée pour les fournisseurs et leurs modèles.
 * Toute modification des fournisseurs ou des modèles doit être effectuée ici. Les clés principales
 * incluent : 'name', 'api_key_option', et 'models'. Chaque modèle doit inclure des informations telles
 * que 'label', 'description', 'cost', 'max_tokens', et 'status'.
 */

/**
 * Fichier de configuration des fournisseurs d'IA pour AI Redactor.
 *
 * Chaque fournisseur est représenté par une clé unique (ex: 'openai', 'grok'),
 * et contient les informations suivantes :
 * - 'name' : Le nom du fournisseur.
 * - 'api_key_option' : Le nom de l'option WordPress pour stocker la clé d'API.
 * - 'models' : Un tableau associatif des modèles disponibles pour ce fournisseur.
 *      Chaque modèle possède :
 *      - 'label' : Le nom lisible du modèle.
 *      - 'description' : Une brève description des particularités du modèle.
 *      - 'cost' : Une indication du coût (ex: '0.002$/1000 tokens').
 *      - 'max_tokens' : Le nombre maximal de tokens supporté.
 */

return [
    'openai' => [
        'name'           => 'OpenAI',
        'api_key_option' => 'ai_redactor_openai_api_key',
        'models'         => [
            'gpt-3.5-turbo' => [
                'label'       => 'GPT-3.5 Turbo',
                'description' => 'Modèle rapide et économique, idéal pour les tâches simples, résumés ou articles courts.',
                'cost'        => 'Prompt: $0.50 / 1M tokens — Completion: $1.50 / 1M tokens',
                'max_tokens'  => 16385,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gpt-4o' => [
                'label'       => 'GPT-4 Omni (gpt-4o)',
                'description' => 'Modèle multimodal ultra performant, excellent pour les articles optimisés, les comparatifs ou la génération avec sources.',
                'cost'        => 'Prompt: $5.00 / 1M tokens — Completion: $15.00 / 1M tokens',
                'max_tokens'  => 128000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gpt-4o-mini' => [
                'label'       => 'GPT-4o Mini',
                'description' => 'Version légère du GPT-4o, idéale pour générer des introductions, titres ou méta-descriptions.',
                'cost'        => 'Prompt: $1.00 / 1M tokens — Completion: $2.00 / 1M tokens',
                'max_tokens'  => 8192,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gpt-4.5-preview' => [
                'label'       => 'GPT-4.5 Preview',
                'description' => 'Excellente capacité de contexte (128K), idéal pour créer des articles longs, guides ou briefs SEO.',
                'cost'        => 'Prompt: $15.00 / 1M tokens — Completion: $45.00 / 1M tokens',
                'max_tokens'  => 128000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
        ],
    ],

    'anthropic' => [
        'name'           => 'Anthropic',
        'api_key_option' => 'ai_redactor_anthropic_api_key',
        'models'         => [
            'claude-3-opus-latest' => [
                'label'       => 'Claude 3 Opus',
                'description' => 'Le plus intelligent des Claude. Idéal pour l’analyse poussée et la rédaction stratégique.',
                'cost'        => 'Prompt: $15.00 / 1M tokens — Completion: $75.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'claude-3-5-haiku-latest' => [
                'label'       => 'Claude 3 Haiku',
                'description' => 'Le plus rapide et économique des modèles Claude. Parfait pour les contenus courts ou fréquents.',
                'cost'        => 'Prompt: $0.80 / 1M tokens — Completion: $4.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'claude-3-7-sonnet-latest' => [
                'label'       => 'Claude 3 Sonnet',
                'description' => 'Bon équilibre coût/perf, mais actuellement indisponible via l’API ou renvoie une erreur.',
                'cost'        => 'Prompt: $3.00 / 1M tokens — Completion: $15.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => false,
                'status'      => 'error',
            ],
        ],
    ],

    'mistral' => [
        'name'           => 'Mistral AI',
        'api_key_option' => 'ai_redactor_mistral_api_key',
        'models'         => [
            'mistral-small-latest' => [
                'label'       => 'Mistral Small',
                'description' => 'Économique et rapide, bon pour des tâches simples ou en masse.',
                'cost'        => 'Prompt: $0.15 / 1M tokens — Completion: $0.40 / 1M tokens',
                'max_tokens'  => 32000,
                'enabled'     => false,
                'status'      => 'missing_api_key',
            ],
            'mistral-medium-latest' => [
                'label'       => 'Mistral Medium',
                'description' => 'Bon rapport qualité/prix. Idéal pour articles de fond à budget maîtrisé.',
                'cost'        => 'Prompt: $0.60 / 1M tokens — Completion: $1.50 / 1M tokens',
                'max_tokens'  => 32000,
                'enabled'     => false,
                'status'      => 'missing_api_key',
            ],
            'mistral-large-latest' => [
                'label'       => 'Mistral Large',
                'description' => 'Modèle très avancé (SOTA). Convient pour rédaction analytique ou expert-level.',
                'cost'        => 'Prompt: $8.00 / 1M tokens — Completion: $24.00 / 1M tokens',
                'max_tokens'  => 32000,
                'enabled'     => false,
                'status'      => 'missing_api_key',
            ],
        ],
    ],

    'gemini' => [
        'name'           => 'Google Gemini',
        'api_key_option' => 'ai_redactor_google_api_key',
        'models'         => [
            'gemini-1.5-pro-latest' => [
                'label'       => 'Gemini 1.5 Pro (Preview)',
                'description' => 'Modèle ultra contextuel (1M tokens). Excellent pour des contenus longs et complexes.',
                'cost'        => 'Gratuit (preview)',
                'max_tokens'  => 1048576,
                'enabled'     => true,
                'status'      => 'preview',
            ],
            'gemini-1.5-flash-latest' => [
                'label'       => 'Gemini 1.5 Flash (Preview)',
                'description' => 'Version plus rapide du 1.5 Pro. Réactif pour contenus courts ou temps réel.',
                'cost'        => 'Gratuit (preview)',
                'max_tokens'  => 1048576,
                'enabled'     => true,
                'status'      => 'preview',
            ],
        ],
    ],

    'grok' => [
        'name'           => 'Grok (xAI)',
        'api_key_option' => 'ai_redactor_grok_api_key',
        'models'         => [

            'grok-2' => [
                'label'       => 'Grok 2',
                'description' => 'Version la plus récente du modèle Grok 2 avec les dernières mises à jour internes. Excellent pour la rédaction contextuelle longue.',
                'cost'        => 'Prompt: $2.00 / 1M tokens — Completion: $10.00 / 1M tokens',
                'max_tokens'  => 131072,
                'enabled'     => true,
                'status'      => 'stable',
            ],

            // 🔹 VERSION STABLE RECOMMANDÉE
            'grok-2-latest' => [
                'label'       => 'Grok 2 (Latest)',
                'description' => 'Version la plus récente du modèle Grok 2 avec les dernières mises à jour internes. Excellent pour la rédaction contextuelle longue.',
                'cost'        => 'Prompt: $2.00 / 1M tokens — Completion: $10.00 / 1M tokens',
                'max_tokens'  => 131072,
                'enabled'     => true,
                'status'      => 'stable',
            ],

            // 🔹 VERSION BÊTA POUR TESTS AVANCÉS
            'grok-beta' => [
                'label'       => 'Grok 3 (Bêta)',
                'description' => 'Modèle de nouvelle génération de xAI, actuellement en version bêta. Offre des capacités de raisonnement avancées avec une fenêtre contextuelle étendue.',
                'cost'        => 'Non communiqué',
                'max_tokens'  => 1048576, // 1M tokens
                'enabled'     => false,
                'status'      => 'beta',
            ],
        ],
    ],


];
