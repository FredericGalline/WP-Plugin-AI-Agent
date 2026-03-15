# AI Connector API

Reference de l'API publique exposee par `AI Connector`.

## Facades disponibles

Facade canonique:

```php
AI_Connector_API
```

Alias legacy conserve:

```php
AI_Connector_API
```

Entre plugins WordPress, il faut privilegier cette API PHP. La REST API sert surtout aux clients externes.

## Contrat de reponse

Toutes les methodes d'envoi de prompt retournent un tableau de cette forme:

```php
array(
    'success'  => true|false,
    'response' => 'Texte genere' | null,
    'error'    => 'Message d erreur' | null,
)
```

Le connecteur peut ajouter des metadonnees internes plus tard, mais ce contrat minimal est la surface stable a consommer.

## Methodes

### `AI_Connector_API::is_configured(): bool`

Retourne `true` si un fournisseur actif est defini et si sa cle API est disponible.

```php
if (class_exists('AI_Connector_API') && AI_Connector_API::is_configured()) {
    // Le connecteur est pret.
}
```

### `AI_Connector_API::prompt(string $prompt, array $args = array()): array`

Envoie un prompt texte au connecteur.

Arguments supportes par le coeur:
- `provider`: surcharge ponctuelle du fournisseur
- `model`: surcharge ponctuelle du modele
- `active_model`: alternative au format `provider:model`

Exemple:

```php
$result = AI_Connector_API::prompt(
    'Resume ce texte en 5 points.',
    array(
        'provider' => 'anthropic',
        'model' => 'claude-sonnet-4-20250514',
    )
);
```

### `AI_Connector_API::request(array $request): array`

Version structuree de `prompt()`.

Le tableau doit contenir au minimum:

```php
array(
    'prompt' => '...',
)
```

Exemple:

```php
$result = AI_Connector_API::request(
    array(
        'prompt' => 'Ecris un chapô de 2 phrases.',
        'provider' => 'openai',
        'model' => 'gpt-5-mini',
    )
);
```

### `AI_Connector_API::get_active_model(): array`

Retourne le modele actif sous la forme:

```php
array(
    'provider' => 'openai',
    'model' => 'gpt-5.1',
)
```

Note de compatibilite:
- si un ancien identifiant de modele est encore stocke en base, le connecteur le normalise automatiquement vers un modele valide du catalogue courant

### `AI_Connector_API::get_providers(): array`

Retourne la configuration complete des fournisseurs et de leurs modeles, telle que definie dans `services/ai/providers-config.php`.

Cette methode est utile pour:
- afficher un selecteur de fournisseur/modele
- verifier qu'un modele existe encore
- afficher des metadonnees d'UI

La structure retournee n'est pas une API commerciale officielle des fournisseurs. C'est une structure applicative interne au connecteur.

### `AI_Connector_API::get_configured_providers(): array`

Retourne la liste des fournisseurs qui disposent d'une cle API configuree.

```php
$providers = AI_Connector_API::get_configured_providers();
```

## Hooks et filtres

### Filtres

Avant envoi du prompt:

```php
ai_connector_api_prompt
ai_connector_api_prompt
```

Exemple:

```php
add_filter('ai_connector_api_prompt', function ($prompt, $args) {
    return "[Contexte site]\n" . $prompt;
}, 10, 2);
```

Permissions REST:

```php
ai_connector_rest_permission
ai_connector_rest_permission
```

### Actions

Apres traitement d'un prompt:

```php
ai_connector_api_prompt_completed
ai_connector_api_prompt_completed
```

Exemple:

```php
add_action('ai_connector_api_prompt_completed', function ($result, $prompt, $args) {
    // Observabilite, logs, analytics...
}, 10, 3);
```

## REST API

Routes disponibles:

```text
POST /wp-json/ai-connector/v1/prompt
POST /wp-json/ai-connector/v1/prompt
```

Format de requete:

```json
{
  "prompt": "Explique ce texte en 3 phrases.",
  "args": {
    "provider": "openai",
    "model": "gpt-5-mini"
  }
}
```

Par defaut, l'acces requiert `manage_options`.

Documentation detaillee:
- [services/documentantion/rest-api.md](services/documentantion/rest-api.md)

## Exemple d'integration dans un plugin metier

```php
function citypro_generate_description($post_id)
{
    if (!class_exists('AI_Connector_API') || !AI_Connector_API::is_configured()) {
        return;
    }

    $prompt = sprintf(
        'Redige une description professionnelle de 4 lignes pour "%s".',
        get_the_title($post_id)
    );

    $result = AI_Connector_API::prompt($prompt);

    if (!empty($result['success']) && !empty($result['response'])) {
        update_post_meta($post_id, '_citypro_ai_description', sanitize_textarea_field($result['response']));
    }
}
```

## Regles de maintenance

- toute nouvelle integration interne doit cibler `AI_Connector_API`
- ne documentez pas les modeles exacts dans plusieurs fichiers: le catalogue vit dans `services/ai/providers-config.php`
- si vous remappez un ancien modele, documentez l'alias dans `includes/ai-connector-functions.php`
