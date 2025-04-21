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
  - lit le modèle actif dans `get_option('ai_agent_active_model')` (format `provider:model`)
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

## 📝 Guide complet pour effectuer des requêtes IA

### Configuration préalable

Avant de pouvoir envoyer des requêtes, assurez-vous que :

1. Un modèle d'IA est correctement configuré dans l'option `ai_agent_active_model` au format `provider:model`
2. Une clé API valide est enregistrée dans l'option correspondante (ex: `ai_redactor_openai_api_key`)
3. Le fournisseur et le modèle sont correctement référencés dans `providers-config.php`

### Étapes pour envoyer une requête IA

#### 1. Préparation du prompt

```php
// Formatez votre prompt selon vos besoins
$prompt = "Voici un article sur [SUJET]. Peux-tu améliorer son style ?";
$prompt .= "\n\nContenu original:\n" . $content;

// Si nécessaire, ajoutez des instructions spécifiques
$prompt .= "\n\nInstructions: Conserve le message principal, mais améliore le style et la clarté.";
```

#### 2. Envoi du prompt via le gestionnaire central

```php
// Inclure le gestionnaire de requêtes si nécessaire
require_once PLUGIN_PATH . '/services/ai/core/ai-request-handler.php';

// Mesurer le temps de réponse (optionnel)
$start_time = microtime(true);

// Envoyer la requête
$result = AI_Request_Handler::send_prompt($prompt);

// Calcul du temps de réponse (optionnel)
$response_time = microtime(true) - $start_time;
```

#### 3. Traitement de la réponse

```php
// Vérifier si la requête a réussi
if ($result['success']) {
    // Accéder à la réponse générée
    $ai_response = $result['response'];
    
    // Utiliser la réponse (par exemple, l'afficher ou la sauvegarder)
    echo '<div class="ai-response">' . esc_html($ai_response) . '</div>';
    
    // Journaliser le succès (optionnel)
    if (function_exists('ai_agent_log')) {
        ai_agent_log('Requête IA réussie - Longueur: ' . strlen($ai_response) . ' caractères', 'info');
    }
} else {
    // Gérer l'erreur
    $error_message = $result['error'];
    
    // Afficher un message d'erreur à l'utilisateur
    echo '<div class="ai-error">Erreur: ' . esc_html($error_message) . '</div>';
    
    // Journaliser l'erreur (optionnel)
    if (function_exists('ai_agent_log')) {
        ai_agent_log('Erreur requête IA: ' . $error_message, 'error');
    }
}
```

### Gestion des erreurs communes

1. **Modèle non configuré** :
   - Vérifiez que `get_option('ai_agent_active_model')` retourne une valeur au format `provider:model`
   - Solution : configurez un modèle dans l'interface admin

2. **Clé API manquante** :
   - Vérifiez que la clé associée au fournisseur est enregistrée
   - Solution : ajoutez la clé API dans les paramètres de connecteurs IA

3. **Erreur d'API externe** :
   - Les erreurs des fournisseurs (quotas, problèmes de réseau, etc.) sont capturées dans `$result['error']`
   - Solution : vérifiez les limites de votre compte et la connectivité réseau

4. **Timeout** :
   - Les modèles volumineux peuvent dépasser le temps d'exécution PHP par défaut
   - Solution : utilisez `set_time_limit(180)` avant les appels longs

### Exemple de cas d'utilisation avancé

```php
// Augmenter temporairement la limite de temps d'exécution
$original_time_limit = ini_get('max_execution_time');
set_time_limit(180); // 3 minutes

try {
    // Envoyer la requête
    $result = AI_Request_Handler::send_prompt($prompt);
    
    if ($result['success']) {
        // Traiter la réponse réussie
        update_post_meta($post_id, '_ai_enhanced_content', $result['response']);
        
        // Notifier l'utilisateur du succès
        add_settings_error(
            'ai_agent_processing',
            'content_enhanced',
            __('Contenu amélioré avec succès par l\'IA!', 'ai-agent'),
            'success'
        );
    } else {
        // Gérer l'erreur et fournir un retour détaillé
        throw new Exception($result['error']);
    }
} catch (Exception $e) {
    // Capturer toute exception
    add_settings_error(
        'ai_agent_processing',
        'ai_error',
        sprintf(__('Erreur lors du traitement IA: %s', 'ai-agent'), $e->getMessage()),
        'error'
    );
    
    // Journaliser l'erreur
    if (function_exists('ai_agent_log')) {
        ai_agent_log('EXCEPTION: ' . $e->getMessage(), 'error');
    }
} finally {
    // Restaurer la limite de temps d'origine
    set_time_limit($original_time_limit);
}
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

## 🔍 Structure de réponse détaillée

Lorsque vous appelez `AI_Request_Handler::send_prompt()`, le résultat est toujours un tableau avec cette structure :

```php
[
    // Indique si la requête a réussi (true) ou échoué (false)
    'success' => true|false,
    
    // Contient la réponse de l'IA si success est true
    // Peut être une chaîne de caractères ou un tableau selon le fournisseur
    'response' => 'Texte de réponse généré par l'IA...',
    
    // Contient un message d'erreur si success est false
    // Peut aussi être présent avec success=true pour les avertissements
    'error' => 'Description détaillée de l'erreur'
]
```

### Variations par fournisseur

Bien que la structure de base soit standardisée, certains fournisseurs peuvent ajouter des informations supplémentaires :

- **OpenAI** : Peut inclure des métadonnées comme le nombre de tokens utilisés
- **Anthropic** : Peut inclure des métadonnées sur le modèle utilisé
- **Gemini** : Peut contenir des informations sur la confiance de la réponse

Accédez à ces informations supplémentaires via le tableau `$result` :

```php
// Exemple avec OpenAI qui peut inclure des métadonnées
if ($result['success'] && isset($result['meta'])) {
    $usage = $result['meta']['usage'] ?? [];
    $prompt_tokens = $usage['prompt_tokens'] ?? 0;
    $completion_tokens = $usage['completion_tokens'] ?? 0;
    
    echo "Tokens utilisés: $prompt_tokens (prompt) + $completion_tokens (réponse)";
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

### Utilisation du testeur de prompt dans l'admin
1. Accédez à `AI Agent > Testeur de Prompt` dans le menu admin WordPress
2. Entrez votre prompt dans la zone de texte
3. Cliquez sur "Tester ce Prompt"
4. Consultez la réponse ou les messages d'erreur
5. En cas d'erreur, activez le diagnostic pour voir les détails techniques

---

## 🔎 Conseils pour le débogage

### Activer les logs de diagnostic
Ajoutez dans votre fichier wp-config.php :
```php
define('AI_AGENT_DEBUG', true);
```

### Consulter les logs
Les logs sont enregistrés dans `/logs/ai-agent.log` et contiennent :
- Requêtes envoyées
- Réponses reçues
- Erreurs et avertissements
- Performance (temps de réponse)

### Problèmes fréquents et solutions
1. **"Aucun modèle n'est configuré"**
   - Vérifiez que l'option `ai_agent_active_model` est correctement définie
   - Accédez à AI Agent > Connecteurs IA pour sélectionner un modèle

2. **"Format de réponse invalide"**
   - Vérifiez que la classe API du fournisseur traite correctement le format de réponse
   - Consultez les logs pour voir la réponse brute

3. **"Clé API non configurée"**
   - Vérifiez que la clé API est bien enregistrée pour le fournisseur actif
   - Accédez à AI Agent > Connecteurs IA pour configurer la clé

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

Version : 1.1.0
Date : Avril 2025

---
