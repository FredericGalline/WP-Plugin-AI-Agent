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
            'gpt-5.4' => [
                'label'       => 'GPT-5.4',
                'description' => 'Meilleure intelligence à grande échelle pour les workflows agentiques, le code et les tâches professionnelles.',
                'cost'        => 'Input: $2.00 / 1M tokens — Output: $16.00 / 1M tokens',
                'max_tokens'  => 400000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gpt-5.2' => [
                'label'       => 'GPT-5.2',
                'description' => 'Modèle phare pour les tâches complexes, le raisonnement et les workflows agentiques.',
                'cost'        => 'Input: $1.75 / 1M tokens — Output: $14.00 / 1M tokens',
                'max_tokens'  => 400000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gpt-5.1' => [
                'label'       => 'GPT-5.1',
                'description' => 'Très bon équilibre entre qualité, vitesse et coût pour la rédaction et l\'automatisation.',
                'cost'        => 'Input: $1.25 / 1M tokens — Output: $10.00 / 1M tokens',
                'max_tokens'  => 400000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gpt-5' => [
                'label'       => 'GPT-5',
                'description' => 'Version précédente de GPT-5, toujours utile pour les tâches de raisonnement général.',
                'cost'        => 'Input: $1.25 / 1M tokens — Output: $10.00 / 1M tokens',
                'max_tokens'  => 400000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gpt-5-mini' => [
                'label'       => 'GPT-5 Mini',
                'description' => 'Intelligence near-frontier pour les charges sensibles au coût et à faible latence.',
                'cost'        => 'Input: $0.25 / 1M tokens — Output: $2.00 / 1M tokens',
                'max_tokens'  => 400000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gpt-5-nano' => [
                'label'       => 'GPT-5 Nano',
                'description' => 'Version la plus rapide et économique de GPT-5 pour la classification et le routage.',
                'cost'        => 'Input: $0.05 / 1M tokens — Output: $0.40 / 1M tokens',
                'max_tokens'  => 400000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'o4-mini' => [
                'label'       => 'o4-mini',
                'description' => 'Modèle de raisonnement rapide et économique pour les tâches analytiques.',
                'cost'        => 'Input: $1.10 / 1M tokens — Output: $4.40 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'o3' => [
                'label'       => 'o3',
                'description' => 'Modèle de raisonnement avancé pour les tâches complexes nécessitant une réflexion approfondie.',
                'cost'        => 'Input: $2.00 / 1M tokens — Output: $8.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'o3-pro' => [
                'label'       => 'o3-pro',
                'description' => 'Version améliorée de o3 avec un compute renforcé pour des résultats supérieurs.',
                'cost'        => 'Input: $20.00 / 1M tokens — Output: $80.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gpt-4.1' => [
                'label'       => 'GPT-4.1',
                'description' => 'Modèle non-reasoning le plus intelligent, longue fenêtre contextuelle.',
                'cost'        => 'Input: $2.00 / 1M tokens — Output: $8.00 / 1M tokens',
                'max_tokens'  => 1047576,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gpt-4.1-mini' => [
                'label'       => 'GPT-4.1 Mini',
                'description' => 'Alternative plus rapide et moins chère à GPT-4.1 pour les tâches récurrentes.',
                'cost'        => 'Input: $0.40 / 1M tokens — Output: $1.60 / 1M tokens',
                'max_tokens'  => 1047576,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gpt-4.1-nano' => [
                'label'       => 'GPT-4.1 Nano',
                'description' => 'Option très légère pour extraction, tagging et génération courte.',
                'cost'        => 'Input: $0.10 / 1M tokens — Output: $0.40 / 1M tokens',
                'max_tokens'  => 1047576,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gpt-4o' => [
                'label'       => 'GPT-4o',
                'description' => 'Modèle polyvalent et multimodal, toujours pertinent pour la génération généraliste.',
                'cost'        => 'Input: $2.50 / 1M tokens — Output: $10.00 / 1M tokens',
                'max_tokens'  => 128000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gpt-4o-mini' => [
                'label'       => 'GPT-4o Mini',
                'description' => 'Petit modèle rapide et abordable pour les tâches ciblées et les contenus courts.',
                'cost'        => 'Input: $0.15 / 1M tokens — Output: $0.60 / 1M tokens',
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
            'claude-opus-4-6' => [
                'label'       => 'Claude Opus 4.6',
                'description' => 'Le modèle le plus intelligent d\'Anthropic pour les agents, le code et le raisonnement.',
                'cost'        => 'Input: $5.00 / 1M tokens — Output: $25.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'claude-sonnet-4-5-20250929' => [
                'label'       => 'Claude Sonnet 4.5',
                'description' => 'Meilleure combinaison de vitesse et d\'intelligence chez Anthropic.',
                'cost'        => 'Input: $3.00 / 1M tokens — Output: $15.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'claude-haiku-4-5-20251001' => [
                'label'       => 'Claude Haiku 4.5',
                'description' => 'Le modèle le plus rapide avec une intelligence quasi-frontière.',
                'cost'        => 'Input: $1.00 / 1M tokens — Output: $5.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'claude-opus-4-5-20251101' => [
                'label'       => 'Claude Opus 4.5',
                'description' => 'Ancien flagship Opus, toujours performant pour les tâches complexes.',
                'cost'        => 'Input: $5.00 / 1M tokens — Output: $25.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'claude-opus-4-1-20250805' => [
                'label'       => 'Claude Opus 4.1',
                'description' => 'Modèle avancé pour les cas exigeants en raisonnement et rédaction.',
                'cost'        => 'Input: $15.00 / 1M tokens — Output: $75.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'claude-sonnet-4-20250514' => [
                'label'       => 'Claude Sonnet 4',
                'description' => 'Bon compromis entre qualité, vitesse et coût, version précédente.',
                'cost'        => 'Input: $3.00 / 1M tokens — Output: $15.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'claude-3-7-sonnet-20250219' => [
                'label'       => 'Claude Sonnet 3.7',
                'description' => 'Version performante avec extended thinking, encore utile pour certains usages.',
                'cost'        => 'Input: $3.00 / 1M tokens — Output: $15.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'claude-3-haiku-20240307' => [
                'label'       => 'Claude Haiku 3',
                'description' => 'Petit modèle compact pour les traitements simples et les budgets serrés.',
                'cost'        => 'Input: $0.25 / 1M tokens — Output: $1.25 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
        ],
    ],
    'mistral' => [
        'name'           => 'Mistral AI',
        'api_key_option' => 'ai_redactor_mistral_api_key',
        'models'         => [
            'mistral-large-2512' => [
                'label'       => 'Mistral Large 3',
                'description' => 'Grand modèle généraliste multimodal open-weight de dernière génération.',
                'cost'        => 'Input: $0.50 / 1M tokens — Output: $1.50 / 1M tokens',
                'max_tokens'  => 256000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'mistral-medium-2508' => [
                'label'       => 'Mistral Medium 3.1',
                'description' => 'Modèle frontier équilibré avec un très bon ton rédactionnel.',
                'cost'        => 'Input: $0.40 / 1M tokens — Output: $2.00 / 1M tokens',
                'max_tokens'  => 128000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'mistral-small-2506' => [
                'label'       => 'Mistral Small 3.2',
                'description' => 'Petit modèle rapide et économique pour les volumes élevés.',
                'cost'        => 'Input: $0.10 / 1M tokens — Output: $0.30 / 1M tokens',
                'max_tokens'  => 128000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'magistral-medium-2509' => [
                'label'       => 'Magistral Medium 1.2',
                'description' => 'Modèle reasoning frontier chez Mistral pour les tâches plus analytiques.',
                'cost'        => 'Input: $2.00 / 1M tokens — Output: $5.00 / 1M tokens',
                'max_tokens'  => 128000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'magistral-small-2509' => [
                'label'       => 'Magistral Small 1.2',
                'description' => 'Version plus légère de Magistral pour le raisonnement à coût contenu.',
                'cost'        => 'Input: $0.50 / 1M tokens — Output: $1.50 / 1M tokens',
                'max_tokens'  => 128000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'ministral-14b-2512' => [
                'label'       => 'Ministral 3 14B',
                'description' => 'Modèle ouvert puissant et compact, adapté aux traitements texte/vision polyvalents.',
                'cost'        => 'Input: $0.20 / 1M tokens — Output: $0.20 / 1M tokens',
                'max_tokens'  => 256000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'ministral-8b-2512' => [
                'label'       => 'Ministral 3 8B',
                'description' => 'Version edge efficace avec un excellent rapport coût/performance.',
                'cost'        => 'Input: $0.15 / 1M tokens — Output: $0.15 / 1M tokens',
                'max_tokens'  => 256000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'ministral-3b-2512' => [
                'label'       => 'Ministral 3 3B',
                'description' => 'Le plus compact de la famille Ministral pour les traitements simples à très faible coût.',
                'cost'        => 'Input: $0.10 / 1M tokens — Output: $0.10 / 1M tokens',
                'max_tokens'  => 256000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
        ],
    ],
    'gemini' => [
        'name'           => 'Google Gemini',
        'api_key_option' => 'ai_redactor_google_api_key',
        'models'         => [
            'gemini-3.1-pro-preview' => [
                'label'       => 'Gemini 3.1 Pro Preview',
                'description' => 'Le modèle Gemini le plus avancé actuellement pour l\'agentique, le code et les tâches complexes.',
                'cost'        => 'Input: $2.00 / $4.00 / 1M tokens — Output: $12.00 / $18.00 / 1M tokens',
                'max_tokens'  => 1048576,
                'enabled'     => true,
                'status'      => 'preview',
            ],
            'gemini-3-flash-preview' => [
                'label'       => 'Gemini 3 Flash Preview',
                'description' => 'Modèle Gemini 3 rapide, orienté scale, recherche et grounding.',
                'cost'        => 'Input: $0.50 / 1M tokens — Output: $3.00 / 1M tokens',
                'max_tokens'  => 1048576,
                'enabled'     => true,
                'status'      => 'preview',
            ],
            'gemini-3.1-flash-lite-preview' => [
                'label'       => 'Gemini 3.1 Flash-Lite Preview',
                'description' => 'Version ultra-économique pour les tâches agentiques simples et à gros volume.',
                'cost'        => 'Input: $0.25 / 1M tokens — Output: $1.50 / 1M tokens',
                'max_tokens'  => 1048576,
                'enabled'     => true,
                'status'      => 'preview',
            ],
            'gemini-2.5-pro' => [
                'label'       => 'Gemini 2.5 Pro',
                'description' => 'Modèle stable très puissant pour l\'analyse longue, le raisonnement et le code.',
                'cost'        => 'Input: $1.25 / $2.50 / 1M tokens — Output: $10.00 / $15.00 / 1M tokens',
                'max_tokens'  => 1048576,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gemini-2.5-flash' => [
                'label'       => 'Gemini 2.5 Flash',
                'description' => 'Excellent rapport prix/performance pour les tâches générales rapides.',
                'cost'        => 'Input: $0.30 / 1M tokens — Output: $2.50 / 1M tokens',
                'max_tokens'  => 1048576,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'gemini-2.5-flash-lite' => [
                'label'       => 'Gemini 2.5 Flash-Lite',
                'description' => 'Le plus économique de la famille 2.5 pour les opérations massives.',
                'cost'        => 'Input: $0.10 / 1M tokens — Output: $0.40 / 1M tokens',
                'max_tokens'  => 1048576,
                'enabled'     => true,
                'status'      => 'stable',
            ],
        ],
    ],
    'grok' => [
        'name'           => 'Grok (xAI)',
        'api_key_option' => 'ai_redactor_grok_api_key',
        'models'         => [
            'grok-4.20-beta-0309-reasoning' => [
                'label'       => 'Grok 4.20 Beta Reasoning',
                'description' => 'Flagship beta xAI avec gros contexte et capacités agentiques renforcées.',
                'cost'        => 'Input: $2.00 / 1M tokens — Output: $6.00 / 1M tokens',
                'max_tokens'  => 2000000,
                'enabled'     => true,
                'status'      => 'preview',
            ],
            'grok-4.20-beta-0309-non-reasoning' => [
                'label'       => 'Grok 4.20 Beta Non-Reasoning',
                'description' => 'Version beta plus directe du flagship xAI pour les réponses rapides.',
                'cost'        => 'Input: $2.00 / 1M tokens — Output: $6.00 / 1M tokens',
                'max_tokens'  => 2000000,
                'enabled'     => true,
                'status'      => 'preview',
            ],
            'grok-4-1-fast-reasoning' => [
                'label'       => 'Grok 4.1 Fast Reasoning',
                'description' => 'Modèle rapide de nouvelle génération, orienté tool calling et reasoning.',
                'cost'        => 'Input: $0.20 / 1M tokens — Output: $0.50 / 1M tokens',
                'max_tokens'  => 2000000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'grok-4-1-fast-non-reasoning' => [
                'label'       => 'Grok 4.1 Fast Non-Reasoning',
                'description' => 'Version rapide sans surcoût de raisonnement pour les tâches à fort volume.',
                'cost'        => 'Input: $0.20 / 1M tokens — Output: $0.50 / 1M tokens',
                'max_tokens'  => 2000000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'grok-4-fast-reasoning' => [
                'label'       => 'Grok 4 Fast Reasoning',
                'description' => 'Modèle rapide Grok 4 pour la réflexion guidée et les réponses plus complexes.',
                'cost'        => 'Input: $0.20 / 1M tokens — Output: $0.50 / 1M tokens',
                'max_tokens'  => 2000000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'grok-4-fast-non-reasoning' => [
                'label'       => 'Grok 4 Fast Non-Reasoning',
                'description' => 'Version la plus rentable pour les usages texte rapides chez xAI.',
                'cost'        => 'Input: $0.20 / 1M tokens — Output: $0.50 / 1M tokens',
                'max_tokens'  => 2000000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
            'grok-4' => [
                'label'       => 'Grok 4',
                'description' => 'Grand modèle Grok généraliste pour les tâches premium à haute qualité.',
                'cost'        => 'Input: $3.00 / 1M tokens — Output: $15.00 / 1M tokens',
                'max_tokens'  => 256000,
                'enabled'     => true,
                'status'      => 'stable',
            ],
        ],
    ],
];
