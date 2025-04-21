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
            'gpt-4.1' => [
                'label'       => 'GPT-4.1',
                'description' => 'Modèle phare avec une fenêtre de contexte étendue à 1 million de tokens, idéal pour les tâches complexes.',
                'cost'        => 'Prompt: $2.00 / 1M tokens — Completion: $8.00 / 1M tokens',
                'max_tokens'  => 1000000,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'long_form_content',
                    'seo_optimized_articles',
                    'complex_instruction_following',
                    'multilingual_writing',
                    'technical_documentation',
                    'creative_writing',
                    'structured_content_generation'
                ],
            ],
            'gpt-4.1-mini' => [
                'label'       => 'GPT-4.1 Mini',
                'description' => 'Version allégée de GPT-4.1, offrant un bon compromis entre coût et performance.',
                'cost'        => 'Prompt: $1.00 / 1M tokens — Completion: $4.00 / 1M tokens',
                'max_tokens'  => 1000000,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'short_form_content',
                    'seo_snippets',
                    'product_descriptions',
                    'email_copywriting',
                    'social_media_posts',
                    'fast_content_generation'
                ],
            ],
            'gpt-4.1-nano' => [
                'label'       => 'GPT-4.1 Nano',
                'description' => 'Version ultra-légère de GPT-4.1, parfaite pour des tâches peu gourmandes ou répétitives.',
                'cost'        => 'Prompt: $0.10 / 1M tokens — Completion: $0.40 / 1M tokens',
                'max_tokens'  => 1000000,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'meta_descriptions',
                    'title_generation',
                    'real_time_classification',
                    'autocomplete',
                    'content_tagging',
                    'lightweight_customer_support',
                    'workflow_automation'
                ],
            ],
            'gpt-4o' => [
                'label'       => 'GPT-4 Omni (gpt-4o)',
                'description' => 'Modèle multimodal performant, adapté pour des articles optimisés et la génération avec sources.',
                'cost'        => 'Prompt: $5.00 / 1M tokens — Completion: $15.00 / 1M tokens',
                'max_tokens'  => 128000,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'multimodal_content',
                    'seo_optimized_articles',
                    'comparative_analysis',
                    'source_generation',
                    'structured_content_generation'
                ],
            ],
            'gpt-4o-mini' => [
                'label'       => 'GPT-4o Mini',
                'description' => 'Version légère de GPT-4o, idéale pour générer des introductions, titres ou méta-descriptions.',
                'cost'        => 'Prompt: $0.15 / 1M tokens — Completion: $0.60 / 1M tokens',
                'max_tokens'  => 8192,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'short_form_content',
                    'meta_descriptions',
                    'title_generation',
                    'email_copywriting',
                    'social_media_posts',
                    'fast_content_generation'
                ],
            ],
            'gpt-3.5-turbo' => [
                'label'       => 'GPT-3.5 Turbo',
                'description' => 'Modèle rapide et économique, idéal pour les tâches simples, résumés ou articles courts.',
                'cost'        => 'Prompt: $0.50 / 1M tokens — Completion: $1.50 / 1M tokens',
                'max_tokens'  => 16385,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'short_form_content',
                    'summarization',
                    'basic_seo_tasks',
                    'email_copywriting',
                    'social_media_posts'
                ],
            ],
        ],
    ],
    'anthropic' => [
        'name'           => 'Anthropic',
        'api_key_option' => 'ai_redactor_anthropic_api_key',
        'models'         => [
            'claude-3-opus-latest' => [
                'label'       => 'Claude 3 Opus',
                'description' => 'Modèle le plus avancé de la gamme Claude, conçu pour des tâches complexes nécessitant une compréhension approfondie.',
                'cost'        => 'Prompt: $15.00 / 1M tokens — Completion: $75.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'long_form_content',
                    'seo_optimized_articles',
                    'complex_instruction_following',
                    'technical_documentation',
                    'multilingual_writing',
                    'structured_content_generation'
                ],
            ],
            'claude-3-5-haiku-latest' => [
                'label'       => 'Claude 3.5 Haiku',
                'description' => 'Modèle rapide et économique, idéal pour des tâches simples et répétitives avec une efficacité optimale.',
                'cost'        => 'Prompt: $0.80 / 1M tokens — Completion: $4.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'short_form_content',
                    'seo_snippets',
                    'product_descriptions',
                    'email_copywriting',
                    'social_media_posts',
                    'fast_content_generation'
                ],
            ],
            'claude-3-7-sonnet-latest' => [
                'label'       => 'Claude 3.7 Sonnet',
                'description' => 'Modèle équilibré offrant un bon compromis entre coût et performance, adapté pour des articles de fond à budget maîtrisé.',
                'cost'        => 'Prompt: $3.00 / 1M tokens — Completion: $15.00 / 1M tokens',
                'max_tokens'  => 200000,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'long_form_content',
                    'seo_optimized_articles',
                    'technical_documentation',
                    'structured_content_generation',
                    'multilingual_writing'
                ],
            ],
        ],
    ],
    'mistral' => [
        'name'           => 'Mistral AI',
        'api_key_option' => 'ai_redactor_mistral_api_key',
        'models'         => [
            'mistral-small-latest' => [
                'label'       => 'Mistral Small',
                'description' => 'Modèle économique et rapide, idéal pour des tâches simples ou en masse.',
                'cost'        => 'Prompt: $0.20 / 1M tokens — Completion: $0.60 / 1M tokens',
                'max_tokens'  => 33000,
                'enabled'     => false,
                'status'      => 'missing_api_key',
                'use_cases'   => [
                    'short_form_content',
                    'seo_snippets',
                    'product_descriptions',
                    'email_copywriting',
                    'social_media_posts',
                    'fast_content_generation'
                ],
            ],
            'mistral-medium-latest' => [
                'label'       => 'Mistral Medium',
                'description' => 'Bon rapport qualité/prix, adapté pour des articles de fond à budget maîtrisé.',
                'cost'        => 'Prompt: $2.75 / 1M tokens — Completion: $8.10 / 1M tokens',
                'max_tokens'  => 33000,
                'enabled'     => false,
                'status'      => 'missing_api_key',
                'use_cases'   => [
                    'long_form_content',
                    'seo_optimized_articles',
                    'technical_documentation',
                    'structured_content_generation',
                    'multilingual_writing'
                ],
            ],
            'mistral-large-latest' => [
                'label'       => 'Mistral Large',
                'description' => 'Modèle très avancé (SOTA), convient pour la rédaction analytique ou de niveau expert.',
                'cost'        => 'Prompt: $4.00 / 1M tokens — Completion: $12.00 / 1M tokens',
                'max_tokens'  => 128000,
                'enabled'     => false,
                'status'      => 'missing_api_key',
                'use_cases'   => [
                    'complex_instruction_following',
                    'long_form_content',
                    'seo_optimized_articles',
                    'technical_documentation',
                    'creative_writing',
                    'structured_content_generation',
                    'multilingual_writing'
                ],
            ],
        ],
    ],
    'gemini' => [
        'name'           => 'Google Gemini',
        'api_key_option' => 'ai_redactor_google_api_key',
        'models'         => [
            'gemini-1.5-pro-latest' => [
                'label'       => 'Gemini 1.5 Pro',
                'description' => 'Modèle multimodal avancé avec une fenêtre de contexte étendue, idéal pour des contenus longs et complexes.',
                'cost'        => 'Prompt: $1.25 / 1M tokens (≤128k) — $2.50 / 1M tokens (>128k) | Completion: $5.00 / 1M tokens (≤128k) — $10.00 / 1M tokens (>128k)',
                'max_tokens'  => 2097152,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'long_form_content',
                    'seo_optimized_articles',
                    'complex_instruction_following',
                    'multilingual_writing',
                    'technical_documentation',
                    'structured_content_generation'
                ],
            ],
            'gemini-1.5-flash-latest' => [
                'label'       => 'Gemini 1.5 Flash',
                'description' => 'Modèle rapide et polyvalent, adapté pour des tâches diverses avec une grande fenêtre de contexte.',
                'cost'        => 'Prompt: $0.075 / 1M tokens (≤128k) — $0.15 / 1M tokens (>128k) | Completion: $0.30 / 1M tokens (≤128k) — $0.60 / 1M tokens (>128k)',
                'max_tokens'  => 1048576,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'short_form_content',
                    'seo_snippets',
                    'product_descriptions',
                    'email_copywriting',
                    'social_media_posts',
                    'fast_content_generation'
                ],
            ],
            'gemini-1.5-flash-8b-latest' => [
                'label'       => 'Gemini 1.5 Flash-8B',
                'description' => 'Modèle léger conçu pour des tâches simples et répétitives avec une efficacité optimale.',
                'cost'        => 'Prompt: $0.0375 / 1M tokens (≤128k) — $0.075 / 1M tokens (>128k) | Completion: $0.15 / 1M tokens (≤128k) — $0.30 / 1M tokens (>128k)',
                'max_tokens'  => 1048576,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'meta_descriptions',
                    'title_generation',
                    'real_time_classification',
                    'autocomplete',
                    'content_tagging',
                    'lightweight_customer_support',
                    'workflow_automation'
                ],
            ],
        ],
    ],
    'grok' => [
        'name'           => 'Grok (xAI)',
        'api_key_option' => 'ai_redactor_grok_api_key',
        'models'         => [
            'grok-2' => [
                'label'       => 'Grok 2',
                'description' => 'Modèle avancé avec une fenêtre de contexte de 128k tokens, adapté pour la rédaction contextuelle longue.',
                'cost'        => 'Prompt: $2.00 / 1M tokens — Completion: $10.00 / 1M tokens',
                'max_tokens'  => 131072,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'long_form_content',
                    'seo_optimized_articles',
                    'technical_documentation',
                    'structured_content_generation',
                    'multilingual_writing'
                ],
            ],
            'grok-2-latest' => [
                'label'       => 'Grok 2 (Latest)',
                'description' => 'Version la plus récente du modèle Grok 2 avec les dernières mises à jour internes.',
                'cost'        => 'Prompt: $2.00 / 1M tokens — Completion: $10.00 / 1M tokens',
                'max_tokens'  => 131072,
                'enabled'     => true,
                'status'      => 'stable',
                'use_cases'   => [
                    'long_form_content',
                    'seo_optimized_articles',
                    'technical_documentation',
                    'structured_content_generation',
                    'multilingual_writing'
                ],
            ],
            'grok-3-beta' => [
                'label'       => 'Grok 3 (Bêta)',
                'description' => 'Modèle de nouvelle génération avec une fenêtre de contexte de 1 million de tokens, offrant des capacités de raisonnement avancées.',
                'cost'        => 'Prompt: $3.00 / 1M tokens — Completion: $15.00 / 1M tokens',
                'max_tokens'  => 1048576,
                'enabled'     => false,
                'status'      => 'beta',
                'use_cases'   => [
                    'complex_instruction_following',
                    'long_form_content',
                    'seo_optimized_articles',
                    'technical_documentation',
                    'creative_writing',
                    'structured_content_generation',
                    'multilingual_writing'
                ],
            ],
        ],
    ],



];
