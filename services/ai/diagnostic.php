<?php

/**
 * Outil de diagnostic pour AI Redactor
 *
 * Ce fichier fournit une interface de diagnostic pour afficher les informations de configuration
 * des modèles IA, vérifier les options stockées dans la base de données, et résoudre les problèmes
 * liés aux fournisseurs ou modèles actifs. Il est destiné à un usage en développement uniquement.
 *
 * @package AI_Redactor
 * @subpackage Services\AI
 *
 * @depends AI_Request_Handler
 * @depends WordPress Options API
 *
 * @css /assets/css/diagnostic.css
 *
 * @ai Ce fichier est exclusivement dédié à l'affichage des informations de diagnostic pour les
 * services IA. Il ne contient aucune logique métier liée à la génération de contenu par IA, ni
 * d'algorithmes d'IA. Sa responsabilité est strictement limitée à : (1) afficher les options
 * configurées, (2) vérifier la validité des modèles et fournisseurs, et (3) fournir des outils
 * pour corriger ou réinitialiser les configurations. Ce fichier ne doit pas être utilisé en
 * production et est destiné uniquement à des fins de débogage.
 */

// Accès direct uniquement pour les administrateurs
if (!function_exists('add_action')) {
    echo 'Accès direct non autorisé.';
    exit;
}

// Vérifier que l'utilisateur est admin
if (!current_user_can('manage_options')) {
    wp_die('Vous n\'avez pas les autorisations nécessaires pour accéder à cette page.');
}

// Activer l'affichage des erreurs pour ce script uniquement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonction pour afficher les données de manière lisible
function diagnostic_dump($data, $title = '')
{
    echo '<div style="margin: 10px 0; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">';
    if (!empty($title)) {
        echo '<h3 style="margin-top: 0;">' . esc_html($title) . '</h3>';
    }
    echo '<pre style="margin: 0; overflow: auto;">';
    if (is_array($data) || is_object($data)) {
        print_r($data);
    } else {
        echo esc_html($data);
    }
    echo '</pre></div>';
}

// En-tête
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>AI Redactor - Diagnostic</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            color: #444;
            line-height: 1.5;
        }

        h1 {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            color: #0073aa;
        }

        section {
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
        }

        h2 {
            margin-top: 0;
            color: #23282d;
        }

        .success {
            color: #46b450;
        }

        .error {
            color: #dc3232;
        }

        .warning {
            color: #ffb900;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f5f5f5;
            font-weight: bold;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .action-buttons {
            margin-top: 20px;
        }

        .button {
            display: inline-block;
            padding: 8px 16px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            margin-right: 10px;
        }

        .button:hover {
            background: #0085ba;
        }
    </style>
</head>

<body>
    <h1>AI Redactor - Outil de diagnostic</h1>
    <p>Cet outil affiche des informations détaillées pour vous aider à résoudre les problèmes liés aux modèles IA.</p>

    <?php
    // 1. Récupérer et afficher les informations de configuration WordPress
    echo '<section>';
    echo '<h2>Informations WordPress</h2>';
    echo '<ul>';
    echo '<li><strong>Version WordPress :</strong> ' . get_bloginfo('version') . '</li>';
    echo '<li><strong>URL du site :</strong> ' . get_bloginfo('url') . '</li>';
    echo '<li><strong>WP_DEBUG :</strong> ' . (defined('WP_DEBUG') && WP_DEBUG ? 'Activé' : 'Désactivé') . '</li>';
    echo '<li><strong>AI_REDACTOR_DEBUG :</strong> ' . (defined('AI_REDACTOR_DEBUG') && AI_REDACTOR_DEBUG ? 'Activé' : 'Désactivé') . '</li>';
    echo '</ul>';
    echo '</section>';

    // 2. Récupérer et afficher les options de la base de données liées à AI Redactor
    echo '<section>';
    echo '<h2>Options stockées dans la base de données</h2>';
    global $wpdb;
    $options_table = $wpdb->prefix . 'options';
    $ai_options = $wpdb->get_results(
        "SELECT option_name, option_value 
    FROM $options_table 
    WHERE option_name LIKE 'ai\_redactor\_%'",
        ARRAY_A
    );

    if (empty($ai_options)) {
        echo '<p class="error">Aucune option AI Redactor trouvée dans la base de données.</p>';
    } else {
        echo '<table>';
        echo '<tr><th>Option</th><th>Valeur</th></tr>';

        foreach ($ai_options as $option) {
            echo '<tr>';
            echo '<td>' . esc_html($option['option_name']) . '</td>';
            echo '<td>' . esc_html($option['option_value']) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }
    echo '</section>';

    // 3. Charger la configuration des fournisseurs
    echo '<section>';
    echo '<h2>Configuration des fournisseurs</h2>';

    $providers_config_file = ABSPATH . 'wp-content/plugins/ai-redactor/services/ai/providers-config.php';
    if (!file_exists($providers_config_file)) {
        echo '<p class="error">Fichier de configuration des fournisseurs introuvable!</p>';
        $providers_config = null;
    } else {
        try {
            $providers_config = include $providers_config_file;

            if (!is_array($providers_config)) {
                echo '<p class="error">Le fichier de configuration ne renvoie pas un tableau valide!</p>';
            } else {
                echo '<p class="success">Fichier de configuration chargé avec succès.</p>';

                // Afficher la liste des fournisseurs
                echo '<h3>Fournisseurs disponibles</h3>';
                echo '<ul>';

                foreach ($providers_config as $provider_id => $provider_data) {
                    echo '<li>';
                    echo '<strong>' . esc_html($provider_id) . '</strong> (' . esc_html($provider_data['name']) . ')';
                    echo '<ul>';

                    // Afficher la liste des modèles
                    echo '<li><strong>API Key Option:</strong> ' . esc_html($provider_data['api_key_option']) . '</li>';
                    echo '<li><strong>Modèles:</strong>';
                    echo '<ul>';

                    foreach ($provider_data['models'] as $model_id => $model_data) {
                        echo '<li>';
                        echo '<strong>' . esc_html($model_id) . '</strong>: ' . esc_html($model_data['label']);
                        echo '</li>';
                    }

                    echo '</ul></li>';
                    echo '</ul>';
                    echo '</li>';
                }

                echo '</ul>';
            }
        } catch (Exception $e) {
            echo '<p class="error">Erreur lors du chargement du fichier de configuration: ' . esc_html($e->getMessage()) . '</p>';
            $providers_config = null;
        }
    }
    echo '</section>';

    // 4. Analyser le modèle actif
    echo '<section>';
    echo '<h2>Analyse du modèle actif</h2>';

    $active_model_combined = get_option('ai_redactor_active_model', '');

    if (empty($active_model_combined)) {
        echo '<p class="warning">Aucun modèle actif n\'est configuré!</p>';
    } else {
        echo '<p><strong>Modèle actif stocké:</strong> ' . esc_html($active_model_combined) . '</p>';

        $parts = explode(':', $active_model_combined);
        if (count($parts) !== 2) {
            echo '<p class="error">Format du modèle actif incorrect! Le format attendu est "fournisseur:modèle".</p>';
        } else {
            $provider_id = $parts[0];
            $model_id = $parts[1];

            echo '<p><strong>Fournisseur extrait:</strong> ' . esc_html($provider_id) . '</p>';
            echo '<p><strong>Modèle extrait:</strong> ' . esc_html($model_id) . '</p>';

            // Vérifier si le fournisseur existe
            if (!isset($providers_config[$provider_id])) {
                echo '<p class="error">Le fournisseur "' . esc_html($provider_id) . '" n\'existe pas dans la configuration!</p>';
                echo '<p>Fournisseurs disponibles: ' . esc_html(implode(', ', array_keys($providers_config))) . '</p>';
            } else {
                echo '<p class="success">Fournisseur trouvé dans la configuration.</p>';

                // Vérifier si le modèle existe
                if (!isset($providers_config[$provider_id]['models'][$model_id])) {
                    echo '<p class="error">Le modèle "' . esc_html($model_id) . '" n\'existe pas pour ce fournisseur!</p>';
                    echo '<p>Modèles disponibles: ' . esc_html(implode(', ', array_keys($providers_config[$provider_id]['models']))) . '</p>';
                } else {
                    echo '<p class="success">Modèle trouvé dans la configuration.</p>';

                    // Vérifier si la clé API est configurée
                    $api_key_option = $providers_config[$provider_id]['api_key_option'];
                    $api_key = get_option($api_key_option, '');

                    if (empty($api_key)) {
                        echo '<p class="error">Clé API non configurée pour ce fournisseur!</p>';
                    } else {
                        echo '<p class="success">Clé API configurée.</p>';
                    }
                }
            }
        }
    }

    // 5. Fonctionnalité pour corriger ou réinitialiser la configuration
    echo '<h3>Actions de correction</h3>';

    if (!empty($providers_config)) {
        // Formulaire d'action post
        echo '<form method="post" action="">';
        wp_nonce_field('ai_diagnostic_action', 'ai_diagnostic_nonce');

        echo '<select name="diagnostic_action">';
        echo '<option value="">Sélectionnez une action...</option>';

        // Options pour définir un modèle actif
        foreach ($providers_config as $provider_id => $provider_data) {
            foreach ($provider_data['models'] as $model_id => $model_data) {
                $option_value = 'set_model:' . $provider_id . ':' . $model_id;
                echo '<option value="' . esc_attr($option_value) . '">';
                echo 'Définir comme actif: ' . esc_html($provider_data['name'] . ' - ' . $model_data['label']);
                echo '</option>';
            }
        }

        // Option pour réinitialiser le modèle actif
        echo '<option value="reset_model">Réinitialiser le modèle actif</option>';

        echo '</select>';
        echo '<button type="submit" class="button">Exécuter l\'action</button>';
        echo '</form>';
    }

    // Traitement du formulaire si soumis
    if (
        isset($_POST['diagnostic_action']) && !empty($_POST['diagnostic_action']) &&
        isset($_POST['ai_diagnostic_nonce']) && wp_verify_nonce($_POST['ai_diagnostic_nonce'], 'ai_diagnostic_action')
    ) {

        $action = sanitize_text_field($_POST['diagnostic_action']);

        // Réinitialiser le modèle actif
        if ($action === 'reset_model') {
            delete_option('ai_redactor_active_model');
            echo '<p class="success">Modèle actif réinitialisé avec succès!</p>';
            echo '<p>Rechargez la page pour voir les changements.</p>';
        }
        // Définir un nouveau modèle actif
        elseif (strpos($action, 'set_model:') === 0) {
            $parts = explode(':', $action);
            if (count($parts) === 3) {
                $provider_id = $parts[1];
                $model_id = $parts[2];

                $new_active_model = $provider_id . ':' . $model_id;
                update_option('ai_redactor_active_model', $new_active_model);

                echo '<p class="success">Modèle actif défini avec succès à: ' . esc_html($new_active_model) . '</p>';
                echo '<p>Rechargez la page pour voir les changements.</p>';
            }
        }
    }

    echo '</section>';

    // 6. Simuler l'exécution de AI_Request_Handler::send_prompt()
    echo '<section>';
    echo '<h2>Simulation de AI_Request_Handler::send_prompt()</h2>';

    $request_handler_file = ABSPATH . 'wp-content/plugins/ai-redactor/services/ai/core/ai-request-handler.php';
    if (!file_exists($request_handler_file)) {
        echo '<p class="error">Fichier AI_Request_Handler introuvable!</p>';
    } else {
        // Charger la classe AI_Request_Handler
        if (!class_exists('AI_Request_Handler')) {
            try {
                include_once $request_handler_file;
                echo '<p class="success">Classe AI_Request_Handler chargée avec succès.</p>';
            } catch (Exception $e) {
                echo '<p class="error">Erreur lors du chargement de AI_Request_Handler: ' . esc_html($e->getMessage()) . '</p>';
            }
        }

        // Simuler l'exécution de la méthode send_prompt() pour voir où elle échoue
        if (class_exists('AI_Request_Handler')) {
            $test_prompt = "Ceci est un test de diagnostic.";

            // Obtenir l'option active_model comme le fait AI_Request_Handler
            $active_model_combined = get_option('ai_redactor_active_model', '');
            $active_provider = '';
            $active_model = '';

            if (!empty($active_model_combined)) {
                $parts = explode(':', $active_model_combined);
                if (count($parts) === 2) {
                    $active_provider = $parts[0];
                    $active_model = $parts[1];
                }
            }

            echo '<h3>Paramètres extraits</h3>';
            echo '<ul>';
            echo '<li><strong>active_model_combined:</strong> ' . esc_html($active_model_combined) . '</li>';
            echo '<li><strong>active_provider:</strong> ' . esc_html($active_provider) . '</li>';
            echo '<li><strong>active_model:</strong> ' . esc_html($active_model) . '</li>';
            echo '</ul>';

            // Vérifier si le fournisseur est valide
            if (empty($active_provider) || !isset($providers_config[$active_provider])) {
                echo '<p class="error">Fournisseur non valide ou non configuré!</p>';
            } else {
                echo '<p class="success">Fournisseur valide.</p>';

                // Vérifier si le modèle est valide
                if (empty($active_model) || !isset($providers_config[$active_provider]['models'][$active_model])) {
                    echo '<p class="error">Modèle non valide ou non configuré!</p>';
                } else {
                    echo '<p class="success">Modèle valide.</p>';

                    // Vérifier la clé API
                    $api_key_option = $providers_config[$active_provider]['api_key_option'];
                    $api_key = get_option($api_key_option, '');

                    if (empty($api_key)) {
                        echo '<p class="error">Clé API non configurée!</p>';
                    } else {
                        echo '<p class="success">Clé API configurée.</p>';
                        echo '<p>La méthode send_prompt() devrait fonctionner correctement avec ces paramètres.</p>';
                    }
                }
            }
        }
    }
    echo '</section>';

    ?>

    <div class="action-buttons">
        <a href="#" class="button" onclick="window.location.reload();">Rafraîchir la page</a>
        <a href="<?php echo admin_url('admin.php?page=ai-redactor-tester'); ?>" class="button">Retour au testeur IA</a>
    </div>
</body>

</html>