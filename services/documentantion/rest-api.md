
# Documentation de l'API REST AI Agent

## Introduction
L'API REST AI Agent permet aux développeurs d'envoyer des prompts à différents fournisseurs d'IA et de recevoir des réponses structurées. Cette API est conçue pour être utilisée par d'autres plugins WordPress ou des applications externes.

## Endpoint
**URL** : `/wp-json/ai-agent/v1/prompt`

**Méthode HTTP** : `POST`

## Authentification
L'accès à l'API est restreint aux administrateurs WordPress par défaut. Cependant, cette restriction peut être modifiée via le filtre `ai_agent_rest_permission`.

## Paramètres de la requête
La requête doit être envoyée au format JSON avec les paramètres suivants :

| Nom      | Type   | Obligatoire | Description |
|----------|--------|-------------|-------------|
| `prompt` | string | Oui         | Le prompt à envoyer au fournisseur d'IA. |
| `args`   | array  | Non         | Paramètres supplémentaires pour personnaliser la requête (ex. : `temperature`, `format`, etc.). |

### Exemple de requête JSON
```json
{
    "prompt": "Expliquez la théorie de la relativité.",
    "args": {
        "temperature": 0.7,
        "format": "texte"
    }
}
```

## Réponse
La réponse est renvoyée au format JSON avec les champs suivants :

| Nom       | Type    | Description |
|-----------|---------|-------------|
| `success` | boolean | Indique si la requête a réussi. |
| `response`| mixed   | La réponse du fournisseur d'IA (présente uniquement si `success` est `true`). |
| `error`   | string  | Le message d'erreur (présent uniquement si `success` est `false`). |

### Exemple de réponse en cas de succès
```json
{
    "success": true,
    "response": "La théorie de la relativité est une théorie scientifique..."
}
```

### Exemple de réponse en cas d'échec
```json
{
    "success": false,
    "error": "Clé API invalide ou manquante."
}
```

## Exemple d'utilisation avec cURL
```bash
curl -X POST https://example.com/wp-json/ai-agent/v1/prompt \
     -H "Content-Type: application/json" \
     -d '{"prompt": "Expliquez la théorie de la relativité.", "args": {"temperature": 0.7, "format": "texte"}}'
```

## Personnalisation des permissions
Le filtre `ai_agent_rest_permission` permet de personnaliser les permissions d'accès à l'API. Par exemple, pour autoriser les éditeurs à utiliser l'API :

```php
add_filter('ai_agent_rest_permission', function($default_permission) {
    return current_user_can('edit_posts');
});
```

## Notes supplémentaires
- Assurez-vous que les clés API des fournisseurs d'IA sont correctement configurées dans les options WordPress.
- Cette API est conçue pour des appels machine-à-machine et ne nécessite pas de nonce pour la validation.

