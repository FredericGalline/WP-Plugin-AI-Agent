<?php

/**
 * Page d'administration pour tester les prompts IA
 *
 * Ce fichier contient une classe responsable de fournir une interface utilisateur
 * permettant de tester les prompts directement avec les modèles d'IA configurés.
 * Il affiche les résultats des tests, les erreurs éventuelles, et propose des outils
 * de diagnostic pour résoudre les problèmes de configuration.
 *
 * @package AI_Redactor
 * @subpackage Services\AI\UI
 *
 * @depends AI_Request_Handler
 * @depends WordPress Options API
 * @depends wp_nonce_field
 * @depends current_user_can
 *
 * @css /assets/css/admin-tester.css
 *
 * @js /assets/js/admin-tester.js
 *
 * @ai Ce fichier est exclusivement dédié à l'affichage et à la gestion de la page de test
 * des prompts IA dans l'interface d'administration WordPress. Il ne contient aucune logique
 * métier liée à la génération de contenu par IA, ni d'algorithmes d'IA. Sa responsabilité est
 * strictement limitée à : (1) permettre aux utilisateurs de tester des prompts avec les modèles
 * configurés, (2) afficher les résultats ou erreurs, et (3) fournir des outils de diagnostic
 * pour résoudre les problèmes de configuration. Toute logique de communication avec les APIs
 * est déléguée à la classe `AI_Request_Handler`.
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour la page de test des prompts IA
 */
class AI_Connector_Admin_Tester
{
    /**
     * Initialisation de la classe
     */
    public function __construct()
    {
        // Cette classe est incluse depuis ai-admin.php et n'a pas besoin d'initialisation directe
    }

    /**
     * Affiche la page d'administration pour tester les prompts IA
     */
    public function render_tester_page()
    {
        // Vérifier les autorisations
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les droits suffisants pour accéder à cette page.', 'ai-redactor'));
        }

        $prompt = '';
        $result = null;
        $start_time = 0;
        $response_time = 0;
        $show_diagnostic = isset($_GET['diagnostic']) || (isset($result) && !empty($result) && !$result['success']);

        // Traitement d'actions de diagnostic si présentes
        if (
            isset($_POST['diagnostic_action']) && !empty($_POST['diagnostic_action']) &&
            isset($_POST['ai_diagnostic_nonce']) && wp_verify_nonce($_POST['ai_diagnostic_nonce'], 'ai_diagnostic_action') &&
            current_user_can('manage_options')
        ) {

            $action = sanitize_text_field($_POST['diagnostic_action']);

            // Réinitialiser le modèle actif
            if ($action === 'reset_model') {
                ai_connector_set_active_model('');
                add_settings_error('ai_connector_tester', 'model_reset', __('Modèle actif réinitialisé avec succès!', 'ai-connector'), 'success');
                $show_diagnostic = true;
            }
            // Définir un nouveau modèle actif
            elseif (strpos($action, 'set_model:') === 0) {
                $parts = explode(':', $action);
                if (count($parts) === 3) {
                    $provider_id = $parts[1];
                    $model_id = $parts[2];

                    $new_active_model = $provider_id . ':' . $model_id;
                    ai_connector_set_active_model($new_active_model);

                    add_settings_error(
                        'ai_connector_tester',
                        'model_updated',
                        sprintf(__('Modèle actif défini avec succès: %s', 'ai-connector'), $new_active_model),
                        'success'
                    );
                    $show_diagnostic = true;
                }
            }
        }

        // Traitement du formulaire de test s'il est soumis
        if (isset($_POST['ai_redactor_test_prompt_submit']) && isset($_POST['test_prompt'])) {
            ai_connector_log('Formulaire de test soumis - Validation du nonce', 'info');

            check_admin_referer('ai_redactor_test_prompt', 'ai_redactor_test_prompt_nonce');

            $prompt = sanitize_textarea_field($_POST['test_prompt']);

            if (!empty($prompt)) {
                // Inclure le gestionnaire de requêtes (correction du chemin)
                require_once dirname(dirname(__FILE__)) . '/services/ai/core/ai-request-handler.php';

                ai_connector_log('========== DÉBUT TRAITEMENT TEST PROMPT ==========', 'info');
                ai_connector_log('Prompt soumis: ' . substr($prompt, 0, 50) . '... (' . strlen($prompt) . ' caractères)', 'info');

                // Mesurer le temps de réponse si le mode debug est activé
                if (defined('AI_AGENT_DEBUG') && AI_AGENT_DEBUG) {
                    $start_time = microtime(true);
                    ai_connector_log('Démarrage du chronomètre pour mesurer le temps de réponse', 'debug');
                }

                // Définir une limite de temps d'exécution plus longue pour les modèles qui peuvent être lents
                $original_time_limit = ini_get('max_execution_time');
                set_time_limit(180); // 3 minutes pour donner plus de temps aux modèles lents
                ai_connector_log('Limite de temps d\'exécution augmentée à 180 secondes (était: ' . $original_time_limit . ')', 'debug');

                try {
                    // Récupérer les informations sur le modèle actif
                    ai_connector_log('Récupération des informations sur le modèle actif', 'info');
                    $active_model_info = $this->get_active_model_info();
                    ai_connector_log('Modèle actif: ' . ($active_model_info['has_model'] ? $active_model_info['provider_name'] . ' / ' . $active_model_info['model_name'] : 'Non configuré'), 'info');

                    // Vérifier que le modèle et la clé API sont configurés avant d'envoyer
                    if (!$active_model_info['has_model']) {
                        ai_connector_log('ERREUR: Aucun modèle n\'est configuré', 'error');
                        throw new Exception('Aucun modèle n\'est configuré. Veuillez configurer un modèle dans Connecteurs IA.');
                    }

                    if (!$active_model_info['has_api_key']) {
                        ai_connector_log('ERREUR: Aucune clé API n\'est configurée pour ' . $active_model_info['provider_name'], 'error');
                        throw new Exception('Aucune clé API n\'est configurée pour ' . $active_model_info['provider_name'] . '. Veuillez configurer une clé dans Connecteurs IA.');
                    }

                    ai_connector_log('Configuration validée, préparation de l\'envoi du prompt à AI_Request_Handler', 'info');

                    // Envoi du prompt via AI_Request_Handler, la méthode centrale recommandée dans la documentation
                    ai_connector_log('Envoi du prompt à AI_Request_Handler::send_prompt()', 'info');
                    $result = AI_Request_Handler::send_prompt($prompt);

                    ai_connector_log('Réponse reçue de AI_Request_Handler', 'info');
                    ai_connector_log('Statut de la réponse: ' . ($result['success'] ? 'Succès' : 'Échec'), 'info');

                    // Journaliser des informations supplémentaires sur la réponse
                    if (isset($result['response'])) {
                        ai_connector_log('Longueur de la réponse: ' . strlen($result['response']) . ' caractères', 'debug');
                        ai_connector_log('Extrait de la réponse: ' . substr($result['response'], 0, 100) . '...', 'debug');
                    }

                    // Vérifier si des métadonnées sont disponibles (selon le fournisseur)
                    if (isset($result['meta'])) {
                        ai_connector_log('Métadonnées reçues: ' . json_encode($result['meta']), 'debug');

                        // Pour OpenAI, journaliser l'utilisation des tokens
                        if (isset($result['meta']['usage'])) {
                            $usage = $result['meta']['usage'];
                            ai_connector_log(
                                'Tokens utilisés: ' .
                                    ($usage['prompt_tokens'] ?? 0) . ' (prompt) + ' .
                                    ($usage['completion_tokens'] ?? 0) . ' (réponse)',
                                'info'
                            );
                        }
                    }

                    if (isset($result['error']) && !empty($result['error'])) {
                        ai_connector_log('Message d\'erreur: ' . $result['error'], 'error');
                    }

                    // Vérifier le résultat de la requête
                    if (!isset($result) || !is_array($result)) {
                        ai_connector_log('ERREUR: Format de réponse invalide retourné par AI_Request_Handler', 'error');
                        throw new Exception('Format de réponse invalide. La requête a échoué de manière inattendue.');
                    }

                    // Traiter la réponse
                    if ($result['success']) {
                        ai_connector_log('Traitement de la réponse réussie', 'info');
                        // Afficher un message de succès
                        add_settings_error(
                            'ai_connector_tester',
                            'response_received',
                            __('Prompt traité avec succès!', 'ai-connector'),
                            'success'
                        );
                    } else {
                        // Si le résultat n'est pas un succès mais qu'il y a un message d'erreur
                        if (isset($result['error']) && !empty($result['error'])) {
                            ai_connector_log('ERREUR retournée: ' . $result['error'], 'error');
                            throw new Exception($result['error']);
                        } else {
                            ai_connector_log('ERREUR: Aucun message d\'erreur spécifique retourné', 'error');
                            throw new Exception('Erreur inconnue lors du traitement du prompt.');
                        }
                    }

                    // Calculer le temps de réponse
                    if (defined('AI_AGENT_DEBUG') && AI_AGENT_DEBUG) {
                        $response_time = microtime(true) - $start_time;
                        ai_connector_log('Temps de réponse total: ' . $response_time . ' secondes', 'debug');
                    }
                } catch (Exception $e) {
                    // Capturer toute exception non gérée
                    ai_connector_log('EXCEPTION: ' . $e->getMessage(), 'error');
                    if (defined('AI_AGENT_DEBUG') && AI_AGENT_DEBUG) {
                        ai_connector_log('Trace de l\'exception: ' . $e->getTraceAsString(), 'debug');
                    }

                    $result = [
                        'success' => false,
                        'response' => null,
                        'error' => 'Exception: ' . $e->getMessage()
                    ];
                }

                ai_connector_log('Restauration de la limite de temps d\'exécution à ' . $original_time_limit . ' secondes', 'debug');
                set_time_limit($original_time_limit);

                // Activer automatiquement le diagnostic en cas d'erreur
                if (isset($result) && !$result['success']) {
                    ai_connector_log('Activation du diagnostic suite à une erreur', 'info');
                    $show_diagnostic = true;
                } else if (!isset($result)) {
                    // Si pour une raison quelconque $result n'est pas défini, créer un résultat d'erreur
                    ai_connector_log('ERREUR: Variable $result non définie après traitement', 'error');
                    $result = [
                        'success' => false,
                        'response' => null,
                        'error' => 'Aucune réponse n\'a été reçue du serveur. La requête a peut-être expiré ou a été interrompue.'
                    ];
                    $show_diagnostic = true;
                }

                ai_connector_log('========== FIN TRAITEMENT TEST PROMPT ==========', 'info');
            }
        }

        // Obtenir les informations sur le modèle actif
        $active_model_info = $this->get_active_model_info();

        // Obtenir la configuration des fournisseurs (correction du chemin)
        $providers_config = require dirname(dirname(__FILE__)) . '/services/ai/providers-config.php';

        // Afficher l'interface
?>
        <div class="wrap ai-connector-wrap">
            <div class="ai-connector-header">
                <h1><?php echo esc_html__('Testeur de Prompt IA', 'ai-redactor'); ?></h1>
                <p class="description">
                    <?php echo esc_html__('Utilisez cette page pour tester rapidement des prompts avec le modèle d\'IA actuellement configuré.', 'ai-redactor'); ?>
                    <?php if (!$show_diagnostic): ?>
                        <a href="<?php echo add_query_arg('diagnostic', '1'); ?>" class="ai-connector-diagnostic-link">
                            <?php echo esc_html__('Afficher les informations de diagnostic', 'ai-redactor'); ?>
                        </a>
                    <?php endif; ?>
                </p>
            </div>

            <?php settings_errors('ai_connector_tester'); ?>

            <!-- Informations sur le modèle actif -->
            <div class="ai-connector-card">
                <div class="ai-connector-card-header">
                    <h2><?php echo esc_html__('Modèle IA actif', 'ai-redactor'); ?></h2>
                </div>
                <div class="ai-connector-card-content">
                    <?php if ($active_model_info['has_model']): ?>
                        <div class="ai-connector-model-info">
                            <p>
                                <strong><?php echo esc_html__('Fournisseur:', 'ai-redactor'); ?></strong>
                                <?php echo esc_html($active_model_info['provider_name']); ?>
                            </p>
                            <p>
                                <strong><?php echo esc_html__('Modèle:', 'ai-redactor'); ?></strong>
                                <?php echo esc_html($active_model_info['model_name']); ?>
                            </p>
                            <p>
                                <strong><?php echo esc_html__('Clé API:', 'ai-redactor'); ?></strong>
                                <?php if ($active_model_info['has_api_key']): ?>
                                    <span class="ai-connector-badge ai-connector-badge-success"><?php echo esc_html__('Configurée', 'ai-redactor'); ?></span>
                                <?php else: ?>
                                    <span class="ai-connector-badge ai-connector-badge-error"><?php echo esc_html__('Non configurée', 'ai-redactor'); ?></span>
                                <?php endif; ?>
                            </p>

                            <?php if (defined('AI_AGENT_DEBUG') && AI_AGENT_DEBUG): ?>
                                <div class="ai-connector-debug-info">
                                    <p><strong>Debug - Valeur stockée:</strong> <?php echo esc_html(ai_connector_get_active_model_string()); ?></p>
                                    <p><strong>Debug - ID Fournisseur:</strong> <?php echo esc_html($active_model_info['provider_id']); ?></p>
                                    <p><strong>Debug - ID Modèle:</strong> <?php echo esc_html($active_model_info['model_id']); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!$active_model_info['has_api_key']): ?>
                                <div class="ai-connector-notice ai-connector-notice-warning">
                                    <?php
                                    echo sprintf(
                                        __('Pour utiliser ce modèle, vous devez configurer une clé API dans <a href="%s">Connecteurs IA</a>.', 'ai-redactor'),
                                        admin_url('admin.php?page=ai-connector-connectors')
                                    );
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="ai-connector-notice ai-connector-notice-warning">
                            <?php
                            echo sprintf(
                                __('Aucun modèle n\'est actuellement sélectionné. Veuillez en configurer un dans <a href="%s">Connecteurs IA</a>.', 'ai-redactor'),
                                admin_url('admin.php?page=ai-connector-connectors')
                            );
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section de diagnostic -->
            <?php if ($show_diagnostic): ?>
                <div class="ai-connector-card">
                    <div class="ai-connector-card-header">
                        <h2><?php echo esc_html__('Diagnostique et résolution de problèmes', 'ai-redactor'); ?></h2>
                        <a href="<?php echo remove_query_arg('diagnostic'); ?>" class="ai-connector-button ai-connector-button-secondary">
                            <?php echo esc_html__('Masquer le diagnostic', 'ai-redactor'); ?>
                        </a>
                    </div>
                    <div class="ai-connector-card-content">
                        <!-- Tableau d'analyse du modèle actif -->
                        <h3><?php echo esc_html__('Analyse de la configuration', 'ai-redactor'); ?></h3>

                        <?php
                        $active_model_combined = ai_connector_get_active_model_string();
                        $parts = explode(':', $active_model_combined);
                        $active_provider = isset($parts[0]) ? $parts[0] : '';
                        $active_model = isset($parts[1]) ? $parts[1] : '';

                        $provider_valid = !empty($active_provider) && isset($providers_config[$active_provider]);
                        $model_valid = $provider_valid && !empty($active_model) && isset($providers_config[$active_provider]['models'][$active_model]);

                        $api_key_option = $provider_valid ? $providers_config[$active_provider]['api_key_option'] : '';
                        $api_key = !empty($api_key_option) ? get_option($api_key_option, '') : '';
                        $api_key_valid = !empty($api_key);
                        ?>

                        <table class="ai-connector-table">
                            <tr>
                                <th><?php echo esc_html__('Élément', 'ai-redactor'); ?></th>
                                <th><?php echo esc_html__('Statut', 'ai-redactor'); ?></th>
                                <th><?php echo esc_html__('Détails', 'ai-redactor'); ?></th>
                            </tr>
                            <tr>
                                <td><?php echo esc_html__('Valeur de l\'option', 'ai-redactor'); ?></td>
                                <td>
                                    <?php if (!empty($active_model_combined)): ?>
                                        <span class="ai-connector-badge ai-connector-badge-success"><?php echo esc_html__('OK', 'ai-redactor'); ?></span>
                                    <?php else: ?>
                                        <span class="ai-connector-badge ai-connector-badge-error"><?php echo esc_html__('MANQUANT', 'ai-redactor'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($active_model_combined); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo esc_html__('Format', 'ai-redactor'); ?></td>
                                <td>
                                    <?php if (count($parts) === 2): ?>
                                        <span class="ai-connector-badge ai-connector-badge-success"><?php echo esc_html__('OK', 'ai-redactor'); ?></span>
                                    <?php else: ?>
                                        <span class="ai-connector-badge ai-connector-badge-error"><?php echo esc_html__('INVALIDE', 'ai-redactor'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html__('Doit être au format "fournisseur:modèle"', 'ai-redactor'); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo esc_html__('Fournisseur', 'ai-redactor'); ?></td>
                                <td>
                                    <?php if ($provider_valid): ?>
                                        <span class="ai-connector-badge ai-connector-badge-success"><?php echo esc_html__('VALIDE', 'ai-redactor'); ?></span>
                                    <?php else: ?>
                                        <span class="ai-connector-badge ai-connector-badge-error"><?php echo esc_html__('INVALIDE', 'ai-redactor'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    echo esc_html($active_provider);
                                    if (!$provider_valid && is_array($providers_config)) {
                                        echo ' - ' . esc_html__('Fournisseurs disponibles:', 'ai-redactor') . ' ' . esc_html(implode(', ', array_keys($providers_config)));
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php echo esc_html__('Modèle', 'ai-redactor'); ?></td>
                                <td>
                                    <?php if ($model_valid): ?>
                                        <span class="ai-connector-badge ai-connector-badge-success"><?php echo esc_html__('VALIDE', 'ai-redactor'); ?></span>
                                    <?php else: ?>
                                        <span class="ai-connector-badge ai-connector-badge-error"><?php echo esc_html__('INVALIDE', 'ai-redactor'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    echo esc_html($active_model);
                                    if ($provider_valid && !$model_valid && isset($providers_config[$active_provider]['models'])) {
                                        echo ' - ' . esc_html__('Modèles disponibles:', 'ai-redactor') . ' ' . esc_html(implode(', ', array_keys($providers_config[$active_provider]['models'])));
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php echo esc_html__('Clé API', 'ai-redactor'); ?></td>
                                <td>
                                    <?php if ($api_key_valid): ?>
                                        <span class="ai-connector-badge ai-connector-badge-success"><?php echo esc_html__('CONFIGURÉE', 'ai-redactor'); ?></span>
                                    <?php else: ?>
                                        <span class="ai-connector-badge ai-connector-badge-error"><?php echo esc_html__('MANQUANTE', 'ai-redactor'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if (!empty($api_key_option)) {
                                        echo esc_html__('Option:', 'ai-redactor') . ' ' . esc_html($api_key_option);
                                    } else {
                                        echo esc_html__('Impossible de déterminer l\'option pour la clé API', 'ai-redactor');
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>

                        <!-- Actions de correction -->
                        <h3><?php echo esc_html__('Actions de correction', 'ai-redactor'); ?></h3>
                        <form method="post" action="" class="ai-connector-form-row">
                            <?php wp_nonce_field('ai_diagnostic_action', 'ai_diagnostic_nonce'); ?>

                            <select name="diagnostic_action" class="ai-connector-select">
                                <option value=""><?php echo esc_html__('Sélectionnez une action...', 'ai-redactor'); ?></option>

                                <?php
                                // Options pour définir un modèle actif
                                foreach ($providers_config as $provider_id => $provider_data):
                                    foreach ($provider_data['models'] as $model_id => $model_data):
                                        $option_value = 'set_model:' . $provider_id . ':' . $model_id;
                                        $selected = ($active_provider == $provider_id && $active_model == $model_id) ? 'selected' : '';
                                ?>
                                        <option value="<?php echo esc_attr($option_value); ?>" <?php echo $selected; ?>>
                                            <?php echo esc_html__('Définir comme actif:', 'ai-redactor'); ?> <?php echo esc_html($provider_data['name'] . ' - ' . $model_data['label']); ?>
                                        </option>
                                <?php
                                    endforeach;
                                endforeach;
                                ?>

                                <option value="reset_model"><?php echo esc_html__('Réinitialiser le modèle actif', 'ai-redactor'); ?></option>
                            </select>

                            <button type="submit" class="ai-connector-button ai-connector-button-secondary">
                                <?php echo esc_html__('Exécuter', 'ai-redactor'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulaire de test -->
            <div class="ai-connector-card">
                <div class="ai-connector-card-header">
                    <h2><?php echo esc_html__('Tester un prompt', 'ai-redactor'); ?></h2>
                </div>
                <div class="ai-connector-card-content">
                    <form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" id="ai-connector-test-form" class="ai-connector-form-row">
                        <?php wp_nonce_field('ai_redactor_test_prompt', 'ai_redactor_test_prompt_nonce'); ?>

                        <label for="test_prompt">
                            <?php echo esc_html__('Saisissez votre prompt:', 'ai-redactor'); ?>
                        </label>
                        <textarea name="test_prompt" id="test_prompt" rows="6" class="large-text"
                            placeholder="<?php echo esc_attr__('Exemple: Résume l\'histoire de France en 100 mots', 'ai-redactor'); ?>"><?php echo esc_textarea($prompt); ?></textarea>

                        <div class="submit">
                            <input type="submit" name="ai_redactor_test_prompt_submit" id="ai-connector-test-submit" class="button button-primary"
                                value="<?php echo esc_attr__('Tester ce prompt', 'ai-redactor'); ?>">
                            <div id="ai-connector-loading" class="ai-connector-loading" style="display: none;">
                                <span class="spinner is-active"></span>
                                <span class="ai-connector-loading-text"><?php echo esc_html__('Traitement en cours... Veuillez patienter pendant que l\'IA génère une réponse.', 'ai-redactor'); ?></span>
                            </div>
                        </div>

                        <?php if (!$active_model_info['has_model'] || !$active_model_info['has_api_key']): ?>
                            <div class="ai-connector-notice ai-connector-notice-warning">
                                <?php echo esc_html__('Vous devez configurer un modèle et une clé API avant de pouvoir tester un prompt.', 'ai-redactor'); ?>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if ($result !== null): ?>
                <!-- Résultat du test -->
                <div class="ai-connector-card">
                    <div class="ai-connector-card-header">
                        <h2>
                            <?php
                            if ($result['success']) {
                                echo esc_html__('Réponse de l\'IA', 'ai-redactor');
                            } else {
                                echo esc_html__('Erreur', 'ai-redactor');
                            }
                            ?>
                        </h2>

                        <?php if (defined('AI_AGENT_DEBUG') && AI_AGENT_DEBUG && $response_time > 0): ?>
                            <span class="ai-connector-response-time">
                                <?php printf(esc_html__('Temps de réponse: %.2f secondes', 'ai-redactor'), $response_time); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="ai-connector-card-content">
                        <?php if ($result['success']): ?>
                            <div class="ai-connector-test-response ai-connector-test-response-success">
                                <?php echo wpautop(esc_html($result['response'])); ?>
                            </div>
                        <?php else: ?>
                            <div class="ai-connector-test-response ai-connector-test-response-error">
                                <div class="ai-connector-error-message">
                                    <strong><?php echo esc_html__('Erreur:', 'ai-redactor'); ?></strong>
                                    <?php echo esc_html($result['error']); ?>
                                </div>

                                <?php
                                // Information supplémentaire pour les timeouts
                                if (strpos($result['error'], 'timed out') !== false || strpos($result['error'], 'timeout') !== false):
                                ?>
                                    <div class="ai-connector-error-details">
                                        <p><strong><?php echo esc_html__('Suggestions:', 'ai-redactor'); ?></strong></p>
                                        <ul>
                                            <li><?php echo esc_html__('Les modèles les plus avancés (comme GPT-4 ou Claude Opus) peuvent parfois prendre plus de temps à répondre.', 'ai-redactor'); ?></li>
                                            <li><?php echo esc_html__('Essayez de raccourcir votre prompt ou d\'utiliser un modèle plus rapide.', 'ai-redactor'); ?></li>
                                            <li><?php echo esc_html__('Vérifiez votre connexion internet.', 'ai-redactor'); ?></li>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <?php if (strpos($result['error'], 'API key') !== false || strpos($result['error'], 'authentication') !== false): ?>
                                    <div class="ai-connector-error-details">
                                        <p><strong><?php echo esc_html__('Suggestions:', 'ai-redactor'); ?></strong></p>
                                        <ul>
                                            <li><?php echo esc_html__('Vérifiez que votre clé API est correcte et n\'a pas expiré.', 'ai-redactor'); ?></li>
                                            <li><?php echo esc_html__('Assurez-vous d\'avoir suffisamment de crédits sur votre compte.', 'ai-redactor'); ?></li>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <?php if (!$show_diagnostic): ?>
                                    <div class="ai-connector-diagnostic-suggestion">
                                        <?php echo sprintf(
                                            __('Pour résoudre ce problème, vous pouvez <a href="%s">afficher les informations de diagnostic</a>.', 'ai-redactor'),
                                            add_query_arg('diagnostic', '1')
                                        ); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Données brutes (utile pour le débogage) -->
                <?php if (defined('AI_REDACTOR_DEBUG') && AI_REDACTOR_DEBUG): ?>
                    <div class="ai-connector-card">
                        <div class="ai-connector-card-header">
                            <h2><?php echo esc_html__('Données brutes', 'ai-redactor'); ?></h2>
                        </div>
                        <div class="ai-connector-card-content">
                            <div class="ai-connector-raw-data">
                                <pre><?php print_r($result); ?></pre>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Afficher l'indicateur de chargement lors de la soumission du formulaire
                $('#ai-connector-test-form').on('submit', function(e) {
                    // Ajouter un log pour voir si l'événement est bien capturé
                    console.log('Formulaire soumis - Début du traitement');
                    ai_connector_log('Formulaire soumis via JavaScript', 'info');

                    if ($('#test_prompt').val().trim() !== '') {
                        $('#ai-connector-test-submit').attr('disabled', 'disabled');
                        $('#ai-connector-loading').show();

                        // Faire défiler jusqu'au bouton de soumission pour montrer le chargement
                        $('html, body').animate({
                            scrollTop: $('#ai-connector-loading').offset().top - 100
                        }, 500);

                        // S'assurer que le formulaire est bien soumis
                        console.log('Soumission du formulaire en cours avec prompt:', $('#test_prompt').val().trim().substring(0, 50) + '...');

                        // Ajouter une validation pour s'assurer que le spinner ne tourne pas indéfiniment
                        setTimeout(function() {
                            if ($('#ai-connector-loading').is(':visible')) {
                                $('#ai-connector-loading').hide();
                                $('#ai-connector-test-submit').removeAttr('disabled');
                                console.log('Timeout atteint - Arrêt du spinner');
                            }
                        }, 30000); // 30 secondes de timeout comme sécurité

                        // Laisser le formulaire se soumettre normalement
                        return true;
                    } else {
                        // Empêcher la soumission si le prompt est vide
                        e.preventDefault();
                        alert('Veuillez saisir un prompt avant de soumettre.');
                        return false;
                    }
                });

                // Si la page est rechargée avec un résultat, masquer le spinner
                if ($('.ai-connector-test-response').length > 0) {
                    $('#ai-connector-loading').hide();
                    $('#ai-connector-test-submit').removeAttr('disabled');
                    console.log('Résultat détecté - Spinner masqué');
                }

                // Ajouter un gestionnaire de clic direct sur le bouton pour s'assurer qu'il fonctionne
                $('#ai-connector-test-submit').on('click', function(e) {
                    console.log('Clic direct sur le bouton de soumission');
                    var formIsValid = $('#test_prompt').val().trim() !== '';

                    if (formIsValid) {
                        console.log('Tentative de soumission manuelle du formulaire');
                        // Ne pas appeler submit() ici car cela pourrait créer une boucle avec l'événement on('submit')
                    } else {
                        e.preventDefault();
                        alert('Veuillez saisir un prompt avant de soumettre.');
                    }
                });

                console.log('Gestionnaires d\'événements du formulaire de test initialisés');
            });
        </script>
<?php
    }

    /**
     * Récupère les informations sur le modèle actif
     *
     * @return array Informations sur le modèle actif
     */
    private function get_active_model_info()
    {
        $info = array(
            'has_model'     => false,
            'provider_id'   => '',
            'provider_name' => '',
            'model_id'      => '',
            'model_name'    => '',
            'has_api_key'   => false
        );

        // Récupérer la configuration des fournisseurs (correction du chemin)
        $providers_config = require dirname(dirname(__FILE__)) . '/services/ai/providers-config.php';

        // Récupérer le modèle actif (format provider:model)
        $active_model = ai_connector_get_active_model_string();

        if (!empty($active_model)) {
            $parts = explode(':', $active_model);
            if (count($parts) === 2) {
                $provider_id = $parts[0];
                $model_id = $parts[1];

                if (isset($providers_config[$provider_id]) && isset($providers_config[$provider_id]['models'][$model_id])) {
                    $info['has_model'] = true;
                    $info['provider_id'] = $provider_id;
                    $info['provider_name'] = $providers_config[$provider_id]['name'];
                    $info['model_id'] = $model_id;
                    $info['model_name'] = $providers_config[$provider_id]['models'][$model_id]['label'];

                    // Vérifier si une clé API est configurée
                    $api_key_option = $providers_config[$provider_id]['api_key_option'];
                    $api_key = get_option($api_key_option, '');
                    $info['has_api_key'] = !empty($api_key);
                }
            }
        }

        return $info;
    }
}

if (!class_exists('AI_Redactor_Admin_Tester', false)) {
    class_alias('AI_Connector_Admin_Tester', 'AI_Redactor_Admin_Tester');
}
