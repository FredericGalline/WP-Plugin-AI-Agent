# AI Connector

Plugin transverse WordPress pour centraliser les fournisseurs d'IA, les cles API, le catalogue de modeles, le modele actif et les outils de diagnostic.

## Positionnement

`AI Connector` est le socle technique IA partage entre plusieurs plugins metier.

Il expose:
- une facade PHP stable pour les plugins consommateurs
- une page d'administration pour configurer les fournisseurs
- un testeur et un diagnostic
- un endpoint REST pour les integrations externes

Il ne doit pas porter:
- les prompts metier
- la ligne editoriale
- la generation de posts WordPress
- la logique produit d'un plugin consommateur

Pour ces besoins, il faut un plugin metier comme `AI Redactor`.

## Responsabilites

- gerer les fournisseurs IA et leurs cles API
- maintenir le catalogue de modeles dans `services/ai/providers-config.php`
- exposer l'API publique `AI_Connector_API`
- maintenir la compatibilite legacy `AI_Connector_API`
- normaliser les anciens identifiants de modeles
- journaliser, tester et diagnostiquer les appels

## Architecture

```text
ai-connector.php
includes/
  ai-connector-functions.php
  class-ai-connector-api.php
  class-ai-connector-logger.php
  loader.php
services/ai/
  ai-loader.php
  providers-config.php
  rest-api.php
  core/
    ai-request-handler.php
    ai-admin-form-handler.php
    ai-admin-ajax.php
  api/
    OpenAI.php
    Anthropic.php
    Gemini.php
    Mistral.php
    Grok.php
admin/
  dashboard.php
  models-form.php
  tester.php
  diagnostic.php
```

## API publique

Entre plugins WordPress, l'interface recommandee est l'API PHP:

```php
if (class_exists('AI_Connector_API') && AI_Connector_API::is_configured()) {
    $result = AI_Connector_API::prompt(
        'Redige un resume en 3 phrases.',
        array(
            'provider' => 'openai',
            'model' => 'gpt-5.1',
        )
    );
}
```

Alias legacy conserve:

```php
AI_Connector_API
```

Documentation detaillee:
- [API.md](API.md)
- [services/documentantion/ai.md](services/documentantion/ai.md)
- [services/documentantion/rest-api.md](services/documentantion/rest-api.md)

## Integration avec un plugin metier

Exemple cible:
- `AI Connector` gere les providers et modeles
- `AI Redactor` construit les prompts metier et traite les reponses
- un futur plugin `Support AI` ou `Chatbot AI` pourra consommer la meme facade

## Maintenance du catalogue de modeles

Source de verite:

```text
services/ai/providers-config.php
```

Quand un fournisseur change son catalogue:

1. mettre a jour `services/ai/providers-config.php`
2. mettre a jour les alias legacy dans `includes/ai-connector-functions.php`
3. mettre a jour les badges "modeles populaires" dans `admin/models-form.php`
4. verifier les pages `Connecteurs IA`, `Testeur IA` et `Diagnostic IA`
5. verifier les routes REST et l'API PHP

Important:
- ne dupliquez pas ce catalogue dans les plugins consommateurs
- si un plugin metier conserve un fallback local, il doit deleguer vers `AI Connector` quand il est actif

## Compatibilite legacy

Le plugin conserve provisoirement:
- les constantes `WP_PLUGIN_AI_AGENT_*`
- la facade `AI_Connector_API`
- la route REST `/wp-json/ai-connector/v1/prompt`
- plusieurs options legacy `ai_connector_*` et `ai_redactor_*`

Cette compatibilite existe pour ne pas casser les plugins deja branches. Toute nouvelle integration doit partir de `AI_Connector_API`.
