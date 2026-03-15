# AI Connector - REST API

Documentation des endpoints REST exposes par `AI Connector`.

## Positionnement

Cette REST API est destinee aux integrations externes.

Entre plugins WordPress, il faut preferer:

```php
AI_Connector_API
```

## Routes disponibles

Routes canoniques et legacy:

```text
POST /wp-json/ai-connector/v1/prompt
POST /wp-json/ai-connector/v1/prompt
```

Les deux routes appellent le meme traitement.

## Authentification

Par defaut, la permission requise est:

```php
current_user_can('manage_options')
```

Filtres disponibles:

```php
ai_connector_rest_permission
ai_connector_rest_permission
```

Exemple:

```php
add_filter('ai_connector_rest_permission', function ($allowed) {
    return current_user_can('edit_posts');
});
```

## Corps de requete

```json
{
  "prompt": "Explique ce concept en 3 phrases.",
  "args": {
    "provider": "openai",
    "model": "gpt-5-mini"
  }
}
```

Champs:
- `prompt`: requis
- `args`: optionnel

Arguments actuellement interpretes par le coeur:
- `provider`
- `model`
- `active_model`

## Reponses

Succes:

```json
{
  "success": true,
  "response": "..."
}
```

Erreur:

```json
{
  "success": false,
  "error": "..."
}
```

## Exemple cURL

```bash
curl -X POST https://example.com/wp-json/ai-connector/v1/prompt \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "Redige un chapô de 2 phrases.",
    "args": {
      "provider": "anthropic",
      "model": "claude-sonnet-4-20250514"
    }
  }'
```

## Regles de maintenance

- si le contrat HTTP change, mettre a jour aussi `API.md`
- si une nouvelle route est ajoutee, documenter la raison et la cible
- ne pas promettre dans cette doc des arguments que `ai-request-handler.php` n'interprete pas reellement
