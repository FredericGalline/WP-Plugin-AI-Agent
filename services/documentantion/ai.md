# AI Connector - Guide de maintenance

Cette documentation est la reference technique pour maintenir le plugin `AI Connector`.

## But du plugin

`AI Connector` centralise tout ce qui est transverse aux integrations IA:
- fournisseurs
- cles API
- catalogue de modeles
- modele actif
- facade PHP
- REST API externe
- logs, testeur et diagnostic

Le plugin ne doit pas contenir de logique metier editoriale.

## Architecture reelle

```text
ai-connector.php
includes/
  ai-connector-functions.php
  class-ai-connector-api.php
  class-ai-connector-logger.php
  loader.php
  admin-menu.php
  admin-enqueue.php
services/ai/
  ai-loader.php
  providers-config.php
  rest-api.php
  api/
    OpenAI.php
    Anthropic.php
    Gemini.php
    Mistral.php
    Grok.php
  core/
    ai-request-handler.php
    ai-admin-form-handler.php
    ai-admin-ajax.php
admin/
  dashboard.php
  models-form.php
  tester.php
  diagnostic.php
logs/
  ai-connector.log
```

## Sequence de chargement

1. `ai-connector.php`
   - declare les constantes
   - initialise le logger
   - charge les helpers transverses
   - charge le bootstrap, les services IA et la facade publique
2. `includes/loader.php`
   - charge les menus admin
   - bootstrap les handlers du formulaire et de l'AJAX admin
3. `services/ai/ai-loader.php`
   - enregistre la REST API
   - enregistre les assets lies au service IA
4. `includes/class-ai-connector-api.php`
   - expose `AI_Connector_API`
5. `services/ai/core/ai-request-handler.php`
   - choisit le fournisseur et le modele
   - charge la classe provider adequate
   - execute la requete

## Source de verite

Le catalogue de modeles n'a qu'une seule source de verite:

```text
services/ai/providers-config.php
```

Les fonctions de compatibilite et de migration de modeles vivent dans:

```text
includes/ai-connector-functions.php
```

Les badges de mise en avant dans l'UI vivent dans:

```text
admin/models-form.php
```

## Mise a jour du catalogue de modeles

Checklist maintenable:

1. verifier les catalogues officiels des fournisseurs
2. mettre a jour `services/ai/providers-config.php`
3. si des anciens identifiants disparaissent, ajouter un alias dans `includes/ai-connector-functions.php`
4. verifier qu'un modele actif legacy est bien remappe automatiquement
5. mettre a jour les badges "modeles populaires" dans `admin/models-form.php`
6. verifier les pages:
   - `AI Connector > Connecteurs IA`
   - `AI Connector > Testeur IA`
   - `AI Connector > Diagnostic IA`
7. verifier la facade PHP et la REST API

Regle importante:
- ne dupliquez pas le catalogue courant dans un plugin consommateur

## Ajout d'un nouveau fournisseur

1. ajouter le provider dans `services/ai/providers-config.php`
2. ajouter la classe provider dans `services/ai/api/`
3. declarer le mapping fichier/classe dans `services/ai/core/ai-request-handler.php`
4. ajouter l'icone si besoin dans `assets/svg/`
5. completer le mapping d'icones dans `admin/models-form.php` si le nom de fichier n'est pas standard
6. tester la configuration, la route REST et la facade PHP

## Compatibilite legacy

Compatibilites preservees a date:
- facade `AI_Connector_API`
- route REST `/wp-json/ai-connector/v1/prompt`
- constantes `WP_PLUGIN_AI_AGENT_*`
- options legacy `ai_connector_*` et `ai_redactor_*`

La compatibilite existe pour amortir la refacto. Toute nouvelle surface doit partir de `AI_Connector_API`.

## Regles de maintenabilite

- un seul plugin doit posseder les providers et modeles: `AI Connector`
- un plugin metier consomme l'API, il ne reimplemente pas le transport
- un changement de contrat public doit etre documente dans `API.md`
- une doc historique qui n'est plus vraie doit etre remplacee par un renvoi, pas laissee en l'etat

## Debug et observabilite

Mode debug:

```php
define('AI_CONNECTOR_DEBUG', true);
```

Compatibilite legacy:

```php
define('AI_AGENT_DEBUG', true);
```

Journal:

```text
logs/ai-connector.log
```

## Docs canoniques

- [../../README.md](../../README.md)
- [../../API.md](../../API.md)
- [rest-api.md](rest-api.md)
