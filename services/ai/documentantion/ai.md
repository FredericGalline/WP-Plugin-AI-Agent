# AI Redactor - Documentation technique

Ce fichier documente l'architecture et le fonctionnement du plugin **AI Redactor**, en particulier le système de requêtage vers les différentes API d'intelligence artificielle.

## ✨ Objectif

Permettre à un développeur de comprendre rapidement comment sont gérées les requêtes vers les IA, comment ajouter un nouveau fournisseur, et comment diagnostiquer une erreur.

---

## ⌚ Arborescence concernée

```
/ai
├── ai-loader.php
├── core/
│   └── ai-request-handler.php      ➔ Point d'entrée central des requêtes IA
├── api/
│   ├── OpenAI.php               ➔ Classe AI_OpenAI_API
│   ├── Anthropic.php           ➔ Classe AI_Anthropic_API
│   ├── Gemini.php              ➔ Classe AI_Gemini_API
│   ├── Mistral.php             ➔ Classe AI_Mistral_API
│   └── Grok.php                ➔ Classe AI_Grok_API
├── providers-config.php           ➔ Configuration des fournisseurs et modèles
├── ui/
│   └── ai-admin-tester.php      ➔ Interface admin pour tester un prompt
├── diagnostic.php                 ➔ Outil de diagnostic manuel complet
```

---

## 🛠️ Architecture

### 1. `AI_Request_Handler` (core/ai-request-handler.php)

- **Méthode principale** : `send_prompt($prompt)`
- Elle :
  - lit le modèle actif dans `get_option('ai_redactor_active_model')` (format `provider:model`)
  - lit la clé API correspondante dans la config
  - délègue la requête à la classe du provider via :

```php
$class = 'AI_' . ucfirst($active_provider) . '_API';
return $class::send_request($prompt, $model, $api_key);
```

- Elle gère aussi les cas d'erreur (provider inconnu, modèle non configuré, clé API manquante).

### 2. Classes individuelles par provider (dossier `/api/`)

Chaque classe implémente une méthode statique `send_request($prompt, $model, $api_key)` qui :
- construit l'URL et le body spécifique à l'API
- utilise `wp_remote_post()`
- parse la réponse selon la structure de l'API (OpenAI, Claude, Gemini, etc.)
- retourne un tableau associatif :

```php
[
  'success' => true|false,
  'response' => 'texte généré' ou tableau brut,
  'error' => 'message si erreur'
]
```

---

## 📚 Exemple d'appel de prompt

```php
$response = AI_Request_Handler::send_prompt("Explique la gravitation quantique");

if ($response['success']) {
    echo $response['response'];
} else {
    error_log('Erreur IA : ' . $response['error']);
}
```

---

## ⚡ Ajouter un nouveau fournisseur IA

1. Créer un fichier dans `/api/NouveauProvider.php`
2. Nommer la classe `AI_NouveauProvider_API` avec une méthode `send_request()`
3. Ajouter une entrée dans `providers-config.php` :

```php
'nouveauprovider' => [
  'name' => 'Nouveau Provider',
  'api_key_option' => 'ai_redactor_nouveauprovider_api_key',
  'models' => [
    'modele-1' => ['label' => 'Modèle Standard'],
  ]
]
```

4. La classe sera appelée automatiquement si l'utilisateur active ce provider dans l'admin.

---

## 🔧 Outils de test et de diagnostic

### `ai-admin-tester.php`
- Page admin permettant de tester un prompt en live
- Affiche la réponse ou l'erreur, ainsi que les temps de réponse si `AI_REDACTOR_DEBUG` est activé

### `diagnostic.php`
- Script autonome de test :
  - vérifie que la config est valide
  - liste les options en base de données
  - simule l'appel à `send_prompt()` avec message de retour explicite

---

## 🎓 Bonnes pratiques

- Toujours logger les erreurs si `AI_REDACTOR_DEBUG` est défini.
- Ne jamais appeler directement les classes du dossier `/api/` ailleurs que via `AI_Request_Handler`.
- Ne jamais exposer les clés API dans l'admin (utiliser `get_option()` uniquement côté serveur).
- Utiliser `esc_html()`, `esc_attr()`, `sanitize_text_field()` pour toutes les entrées/sorties.

---

## 🚫 Production

- Le fichier `diagnostic.php` **ne doit jamais être déployé** sur un environnement en ligne.
- Le plugin fonctionne même si une IA est mal configurée, tant qu'une autre est active et valide.

---

## ✏️ TODO – Améliorations futures possibles

- Ajouter des tests automatisés de chaque classe `send_request()`
- Stocker les logs dans un fichier dédié
- Ajouter un système de fallback si une IA est en erreur
- Support multi-fournisseur (enchaînement de prompts)

---

## 🚀 Fait avec soin par l'équipe dev.

Version : 1.0.0
Date : Mars 2025

