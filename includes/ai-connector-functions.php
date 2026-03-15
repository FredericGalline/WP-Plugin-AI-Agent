<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('ai_connector_get_providers_config')) {
    /**
     * Retourne la configuration des fournisseurs IA.
     *
     * @return array
     */
    function ai_connector_get_providers_config()
    {
        static $providers_config = null;

        if (null === $providers_config) {
            $plugin_path = defined('AI_CONNECTOR_PATH') ? AI_CONNECTOR_PATH : WP_PLUGIN_AI_CONNECTOR_PATH;
            $providers_config = require $plugin_path . 'services/ai/providers-config.php';
        }

        return $providers_config;
    }
}

if (!function_exists('ai_connector_get_model_aliases')) {
    /**
     * Retourne les alias de migration des anciens identifiants de modeles.
     *
     * @return array<string,string>
     */
    function ai_connector_get_model_aliases()
    {
        return array(
            'openai:gpt-3.5-turbo' => 'openai:gpt-4o-mini',
            'openai:gpt-4-turbo' => 'openai:gpt-4.1',
            'openai:gpt-4.5-preview' => 'openai:gpt-5.1',
            'openai:o1' => 'openai:o3',
            'openai:o1-mini' => 'openai:o4-mini',
            'anthropic:claude-3-opus' => 'anthropic:claude-opus-4-1-20250805',
            'anthropic:claude-3-opus-latest' => 'anthropic:claude-opus-4-1-20250805',
            'mistral:mistral-small' => 'mistral:mistral-small-2506',
            'mistral:mistral-small-latest' => 'mistral:mistral-small-2506',
            'mistral:mistral-medium' => 'mistral:mistral-medium-2508',
            'mistral:mistral-medium-latest' => 'mistral:mistral-medium-2508',
            'mistral:mistral-large' => 'mistral:mistral-large-2512',
            'mistral:mistral-large-latest' => 'mistral:mistral-large-2512',
            'gemini:gemini-pro' => 'gemini:gemini-2.5-pro',
            'gemini:gemini-1.5-pro-latest' => 'gemini:gemini-2.5-pro',
            'gemini:gemini-1.5-flash-latest' => 'gemini:gemini-2.5-flash',
            'grok:grok-2' => 'grok:grok-4-1-fast-non-reasoning',
            'grok:grok-2-latest' => 'grok:grok-4-1-fast-non-reasoning',
            'grok:grok-beta' => 'grok:grok-4.20-beta-0309-reasoning',
        );
    }
}

if (!function_exists('ai_connector_get_default_model_string')) {
    /**
     * Retourne le premier modele disponible, globalement ou pour un fournisseur.
     *
     * @param string $provider_id Identifiant du fournisseur cible.
     * @return string
     */
    function ai_connector_get_default_model_string($provider_id = '')
    {
        $providers = ai_connector_get_providers_config();
        $provider_id = is_string($provider_id) ? sanitize_key($provider_id) : '';

        if ('' !== $provider_id && isset($providers[$provider_id]['models']) && is_array($providers[$provider_id]['models'])) {
            foreach ($providers[$provider_id]['models'] as $model_id => $model_data) {
                return $provider_id . ':' . $model_id;
            }
        }

        foreach ($providers as $current_provider_id => $provider_data) {
            if (!isset($provider_data['models']) || !is_array($provider_data['models'])) {
                continue;
            }

            foreach ($provider_data['models'] as $model_id => $model_data) {
                return $current_provider_id . ':' . $model_id;
            }
        }

        return '';
    }
}

if (!function_exists('ai_connector_normalize_active_model_string')) {
    /**
     * Normalise un modele actif avec alias de migration et fallback par fournisseur.
     *
     * @param string $active_model Modele au format provider:model.
     * @return string
     */
    function ai_connector_normalize_active_model_string($active_model)
    {
        $active_model = is_string($active_model) ? trim($active_model) : '';

        if ('' === $active_model) {
            return '';
        }

        $aliases = ai_connector_get_model_aliases();
        if (isset($aliases[$active_model])) {
            $active_model = $aliases[$active_model];
        }

        $parts = explode(':', $active_model, 2);
        if (2 !== count($parts)) {
            return ai_connector_get_default_model_string();
        }

        $provider_id = sanitize_key($parts[0]);
        $model_id = sanitize_text_field($parts[1]);
        $providers = ai_connector_get_providers_config();

        if (!isset($providers[$provider_id])) {
            return ai_connector_get_default_model_string();
        }

        if (!isset($providers[$provider_id]['models'][$model_id])) {
            return ai_connector_get_default_model_string($provider_id);
        }

        return $provider_id . ':' . $model_id;
    }
}

if (!function_exists('ai_connector_get_active_model_string')) {
    /**
     * Retourne le modele actif au format provider:model.
     *
     * @return string
     */
    function ai_connector_get_active_model_string()
    {
        $option_names = array(
            'ai_connector_active_model',
            'ai_agent_active_model',
            'ai_redactor_active_model',
        );

        foreach ($option_names as $option_name) {
            $value = get_option($option_name, '');

            if (is_string($value) && '' !== trim($value)) {
                $normalized_value = ai_connector_normalize_active_model_string(trim($value));

                if ('' !== $normalized_value && $normalized_value !== trim($value)) {
                    ai_connector_set_active_model($normalized_value);
                }

                return $normalized_value;
            }
        }

        return '';
    }
}

if (!function_exists('ai_connector_set_active_model')) {
    /**
     * Enregistre le modele actif et synchronise les anciennes options.
     *
     * @param string $active_model Modele au format provider:model.
     * @return void
     */
    function ai_connector_set_active_model($active_model)
    {
        $active_model = ai_connector_normalize_active_model_string($active_model);

        update_option('ai_connector_active_model', $active_model);
        update_option('ai_agent_active_model', $active_model);
        update_option('ai_redactor_active_model', $active_model);

        $parts = explode(':', $active_model, 2);
        $active_provider = count($parts) === 2 ? $parts[0] : '';
        $active_provider_model = count($parts) === 2 ? $parts[1] : '';

        update_option('ai_redactor_active_provider', $active_provider);

        if ('' !== $active_provider && '' !== $active_provider_model) {
            update_option('ai_redactor_' . $active_provider . '_active_model', $active_provider_model);
        }
    }
}

if (!function_exists('ai_connector_get_active_model')) {
    /**
     * Retourne le fournisseur et le modele actifs.
     *
     * @return array{provider:string,model:string}
     */
    function ai_connector_get_active_model()
    {
        $combined = ai_connector_get_active_model_string();

        if ('' !== $combined) {
            $parts = explode(':', $combined, 2);
            if (2 === count($parts)) {
                return array(
                    'provider' => $parts[0],
                    'model' => $parts[1],
                );
            }
        }

        $legacy_provider = get_option('ai_redactor_active_provider', '');
        if ('' !== $legacy_provider) {
            $legacy_model = get_option('ai_redactor_' . $legacy_provider . '_active_model', '');
            if ('' !== $legacy_model) {
                $normalized_legacy_model = ai_connector_normalize_active_model_string($legacy_provider . ':' . $legacy_model);

                if ('' !== $normalized_legacy_model) {
                    $parts = explode(':', $normalized_legacy_model, 2);

                    if (2 === count($parts)) {
                        ai_connector_set_active_model($normalized_legacy_model);

                        return array(
                            'provider' => $parts[0],
                            'model' => $parts[1],
                        );
                    }
                }
            }
        }

        return array(
            'provider' => '',
            'model' => '',
        );
    }
}

if (!function_exists('ai_connector_get_active_provider')) {
    /**
     * Retourne l'identifiant du fournisseur actif.
     *
     * @return string|false
     */
    function ai_connector_get_active_provider()
    {
        $active_model = ai_connector_get_active_model();

        return '' !== $active_model['provider'] ? $active_model['provider'] : false;
    }
}

if (!function_exists('ai_connector_get_provider_api_key')) {
    /**
     * Retourne la cle API d'un fournisseur.
     *
     * @param string $provider_id Identifiant du fournisseur.
     * @return string
     */
    function ai_connector_get_provider_api_key($provider_id)
    {
        $providers = ai_connector_get_providers_config();

        if (!isset($providers[$provider_id]['api_key_option'])) {
            return '';
        }

        return (string) get_option($providers[$provider_id]['api_key_option'], '');
    }
}

if (!function_exists('ai_connector_is_provider_configured')) {
    /**
     * Verifie si un fournisseur dispose d'une cle API.
     *
     * @param string $provider_id Identifiant du fournisseur.
     * @return bool
     */
    function ai_connector_is_provider_configured($provider_id)
    {
        return '' !== ai_connector_get_provider_api_key($provider_id);
    }
}

if (!function_exists('ai_redactor_get_providers_config')) {
    /**
     * Wrapper de compatibilite AI Redactor.
     *
     * @return array
     */
    function ai_redactor_get_providers_config()
    {
        return ai_connector_get_providers_config();
    }
}

if (!function_exists('ai_redactor_get_active_model')) {
    /**
     * Wrapper de compatibilite AI Redactor.
     *
     * @return array{provider:string,model:string}
     */
    function ai_redactor_get_active_model()
    {
        return ai_connector_get_active_model();
    }
}

if (!function_exists('ai_redactor_get_active_provider')) {
    /**
     * Wrapper de compatibilite AI Redactor.
     *
     * @return string|false
     */
    function ai_redactor_get_active_provider()
    {
        return ai_connector_get_active_provider();
    }
}

if (!function_exists('ai_redactor_is_provider_configured')) {
    /**
     * Wrapper de compatibilite AI Redactor.
     *
     * @param string $provider_id Identifiant du fournisseur.
     * @return bool
     */
    function ai_redactor_is_provider_configured($provider_id)
    {
        return ai_connector_is_provider_configured($provider_id);
    }
}
