# AI Agent — API Reference

Public API for third-party plugins to send prompts to AI providers, check configuration, and discover available models.

**Facade class** : `AI_Agent_API`
**Guard pattern** :

```php
if ( class_exists( 'AI_Agent_API' ) && AI_Agent_API::is_configured() ) {
    // AI is ready to use
}
```

---

## Methods

### `is_configured(): bool`

Checks if an AI provider is active and has a valid API key.

```php
if ( AI_Agent_API::is_configured() ) {
    // Safe to send prompts
}
```

---

### `prompt( string $prompt, array $args = [] ): array`

Sends a prompt to the active AI provider and returns the response.

**Parameters** :

| Param     | Type   | Description                                                  |
|-----------|--------|--------------------------------------------------------------|
| `$prompt` | string | The prompt text to send                                      |
| `$args`   | array  | Reserved for future use (temperature, max_tokens, format...) |

**Return** :

```php
// Success
array(
    'success'  => true,
    'response' => 'The AI-generated text response...',
    'error'    => '',
)

// Failure
array(
    'success'  => false,
    'response' => null,
    'error'    => 'Error description',
)
```

**Example** :

```php
$result = AI_Agent_API::prompt(
    'Rédige une description professionnelle de 3 lignes pour une boulangerie artisanale nommée "Au Pain Doré" située à Lyon.'
);

if ( $result['success'] ) {
    update_post_meta( $post_id, '_pro_description', sanitize_textarea_field( $result['response'] ) );
}
```

---

### `get_active_model(): array`

Returns the currently selected provider and model.

```php
$model = AI_Agent_API::get_active_model();
// array( 'provider' => 'openai', 'model' => 'gpt-4.1' )
```

---

### `get_providers(): array`

Returns all available AI providers and their models with metadata.

```php
$providers = AI_Agent_API::get_providers();
/*
array(
    'openai' => array(
        'name'           => 'OpenAI',
        'api_key_option' => 'ai_redactor_openai_api_key',
        'models'         => array(
            'gpt-4.1' => array(
                'label'       => 'GPT-4.1',
                'description' => '...',
                'max_tokens'  => 1000000,
                'enabled'     => true,
                'status'      => 'stable',
            ),
            // ...
        ),
    ),
    'anthropic' => array( ... ),
    'mistral'   => array( ... ),
    'gemini'    => array( ... ),
    'grok'      => array( ... ),
)
*/
```

---

### `get_configured_providers(): array`

Returns only the provider IDs that have a valid API key set.

```php
$ready = AI_Agent_API::get_configured_providers();
// array( 'openai', 'anthropic' )
```

---

## Supported Providers

| Provider     | Models                                              | Status |
|-------------|------------------------------------------------------|--------|
| **OpenAI**   | GPT-4.1, GPT-4.1 Mini, GPT-4.1 Nano, GPT-4o, GPT-4o Mini, GPT-3.5 Turbo | Stable |
| **Anthropic**| Claude 3 Opus, Claude 3.5 Haiku, Claude 3.7 Sonnet  | Stable |
| **Mistral**  | Mistral Small, Medium, Large                         | Stable |
| **Gemini**   | Gemini 1.5 Pro, Flash, Flash-8B                      | Stable |
| **Grok**     | Grok 2, Grok 2 Latest, Grok 3 (Beta)                | Mixed  |

---

## Hooks

### Filters

| Filter                | Parameters          | Description                        |
|-----------------------|---------------------|------------------------------------|
| `ai_agent_api_prompt` | `$prompt`, `$args`  | Modify the prompt before sending   |
| `ai_agent_rest_permission` | `$allowed`     | Control REST API endpoint access   |

### Actions

| Action                          | Parameters                    | Description                     |
|---------------------------------|-------------------------------|---------------------------------|
| `ai_agent_api_prompt_completed` | `$result`, `$prompt`, `$args` | Fires after a prompt is processed |

---

## REST API

The plugin also exposes a REST endpoint for external integrations :

```
POST /wp-json/ai-agent/v1/prompt
```

**Request body** :

```json
{
    "prompt": "Explain quantum computing in 2 sentences.",
    "args": {
        "temperature": 0.7
    }
}
```

**Response** :

```json
{
    "success": true,
    "response": "Quantum computing uses quantum bits..."
}
```

**Authentication** : requires `manage_options` capability by default. Customizable via the `ai_agent_rest_permission` filter.

---

## Integration Example (CityPro Auto-Description)

```php
// Generate a business description from SIRENE data
function citypro_generate_description( $post_id ) {
    if ( ! class_exists( 'AI_Agent_API' ) || ! AI_Agent_API::is_configured() ) {
        return;
    }

    $name     = get_the_title( $post_id );
    $activity = wp_get_post_terms( $post_id, 'activite', array( 'fields' => 'names' ) );
    $city     = get_post_meta( $post_id, '_city', true );

    $prompt = sprintf(
        'Rédige une description professionnelle de 4-5 lignes pour l\'entreprise "%s", '
        . 'secteur d\'activité : %s, située à %s. '
        . 'Ton professionnel, factuel, sans superlatifs.',
        $name,
        implode( ', ', $activity ),
        $city
    );

    $result = AI_Agent_API::prompt( $prompt );

    if ( $result['success'] ) {
        update_post_meta( $post_id, '_pro_description', sanitize_textarea_field( $result['response'] ) );
    }
}
```
