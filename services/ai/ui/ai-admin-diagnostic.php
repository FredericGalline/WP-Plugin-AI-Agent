<?php

/**
 * Page d'administration pour le diagnostic des APIs d'IA
 *
 * Ce fichier contient une classe responsable de fournir une interface utilisateur
 * permettant de tester les connexions avec les APIs des différents fournisseurs d'IA.
 * Il affiche les résultats des tests, les erreurs éventuelles, et permet d'exporter
 * les diagnostics pour analyse.
 *
 * @package AI_Redactor
 * @subpackage Services\AI\UI
 *
 * @depends AI_Request_Handler
 * @depends WordPress Options API
 * @depends wp_nonce_field
 * @depends current_user_can
 * @depends add_submenu_page
 *
 * @css /assets/css/admin-diagnostic.css
 *
 * @js /assets/js/admin-diagnostic.js
 *
 * @ai Ce fichier est exclusivement dédié à l'affichage et à la gestion de la page de diagnostic
 * des APIs d'IA dans l'interface d'administration WordPress. Il ne contient aucune logique métier
 * liée à la génération de contenu par IA, ni d'algorithmes d'IA. Sa responsabilité est strictement
 * limitée à : (1) afficher les résultats des tests de connexion, (2) permettre l'exécution de tests
 * via des actions utilisateur, et (3) fournir des outils d'exportation des résultats. Toute logique
 * de test ou de communication avec les APIs est déléguée à d'autres classes comme AI_Request_Handler.
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour la page de diagnostic des APIs d'IA
 */
class AI_Redactor_Admin_Diagnostic
{
    /**
     * Le prompt standard à envoyer à tous les modèles
     *
     * @var string
     */
    private $test_prompt = "Dites bonjour en français.";

    /**
     * Fichier de log pour les diagnostics
     *
     * @var string
     */
    private $log_file;

    /**
     * Initialisation de la classe
     * 
     * @param bool $register_menu Si true, enregistre son propre sous-menu (false par défaut)
     */
    public function __construct($register_menu = false)
    {
        $this->log_file = dirname(dirname(__FILE__)) . '/ai-diagnostic.log';

        // Ajouter la page au menu d'administration uniquement si demandé
        if ($register_menu) {
            add_action('admin_menu', array($this, 'add_diagnostic_page'));
        }

        // Note: Le chargement des assets a été déplacé dans ai-loader.php
        // pour centraliser la gestion des assets selon l'architecture du plugin
    }

    /**
     * Ajoute la page de diagnostic au menu d'administration
     */
    public function add_diagnostic_page()
    {
        add_submenu_page(
            'ai-redactor',
            __('Diagnostic IA', 'ai-redactor'),
            __('Diagnostic IA', 'ai-redactor'),
            'manage_options',
            'ai-redactor-diagnostic',
            array($this, 'render_diagnostic_page')
        );
    }

    /**
     * Journalise les messages de diagnostic
     * 
     * @param string $message Le message à journaliser
     */
    private function log($message)
    {
        if (defined('AI_REDACTOR_DEBUG') && AI_REDACTOR_DEBUG) {
            $timestamp = date('[Y-m-d H:i:s]');

            if (is_array($message) || is_object($message)) {
                $message = print_r($message, true);
            }

            file_put_contents($this->log_file, $timestamp . ' ' . $message . "\n", FILE_APPEND);
            error_log('[AI Diagnostic] ' . $message);
        }
    }

    /**
     * Teste un modèle spécifique
     * 
     * @param string $provider_id ID du fournisseur
     * @param string $model_id ID du modèle
     * @param array $provider_config Configuration du fournisseur
     * @param array $model_config Configuration du modèle
     * @return array Résultats du test
     */
    private function test_model($provider_id, $model_id, $provider_config, $model_config)
    {
        $result = [
            'provider_id' => $provider_id,
            'provider_name' => $provider_config['name'],
            'model_id' => $model_id,
            'model_name' => $model_config['label'],
            'api_key_option' => $provider_config['api_key_option'],
            'api_key_status' => 'missing',
            'request_status' => 'not_tested',
            'http_code' => null,
            'response_time' => null,
            'result' => null,
            'timestamp' => current_time('mysql')
        ];

        // Vérifier si la clé API est configurée
        $api_key = get_option($provider_config['api_key_option'], '');

        if (empty($api_key)) {
            $result['result'] = __('Clé API non configurée', 'ai-redactor');
            return $result;
        }

        $result['api_key_status'] = 'ok';

        // Définir le modèle actif temporairement pour le test
        $active_model_backup = get_option('ai_redactor_active_model', '');
        $test_model = $provider_id . ':' . $model_id;
        update_option('ai_redactor_active_model', $test_model);

        $this->log("Test du modèle: " . $test_model);
        $this->log("Prompt: " . $this->test_prompt);

        // Mesurer le temps de réponse
        $start_time = microtime(true);

        try {
            // Charger le gestionnaire de requêtes
            require_once dirname(dirname(__FILE__)) . '/core/ai-request-handler.php';

            // Envoyer la requête
            $api_result = AI_Request_Handler::send_prompt($this->test_prompt);

            // Calculer le temps de réponse
            $end_time = microtime(true);
            $result['response_time'] = round(($end_time - $start_time) * 1000); // en millisecondes

            $result['request_status'] = $api_result['success'] ? 'success' : 'error';
            $result['result'] = $api_result['success'] ? $api_result['response'] : $api_result['error'];

            $this->log("Résultat du test: " . ($api_result['success'] ? 'SUCCÈS' : 'ÉCHEC'));
            if (!$api_result['success']) {
                $this->log("Erreur: " . $api_result['error']);
            }
        } catch (Exception $e) {
            $end_time = microtime(true);
            $result['response_time'] = round(($end_time - $start_time) * 1000);
            $result['request_status'] = 'error';
            $result['result'] = 'Exception: ' . $e->getMessage();

            $this->log("Exception lors du test: " . $e->getMessage());
        }

        // Restaurer le modèle actif
        update_option('ai_redactor_active_model', $active_model_backup);

        return $result;
    }

    /**
     * Exécute les tests pour tous les modèles ou un modèle spécifique
     * 
     * @param string $test_provider_id ID du fournisseur à tester (optionnel)
     * @param string $test_model_id ID du modèle à tester (optionnel)
     * @return array Résultats des tests
     */
    private function run_tests($test_provider_id = null, $test_model_id = null)
    {
        $providers_config = require dirname(dirname(__FILE__)) . '/providers-config.php';
        $tests_results = [];

        foreach ($providers_config as $provider_id => $provider_config) {
            // Sauter ce fournisseur s'il ne correspond pas au test spécifique demandé
            if ($test_provider_id && $test_provider_id !== $provider_id) {
                continue;
            }

            foreach ($provider_config['models'] as $model_id => $model_config) {
                // Sauter ce modèle s'il ne correspond pas au test spécifique demandé
                if ($test_model_id && $test_model_id !== $model_id) {
                    continue;
                }

                $tests_results[] = $this->test_model($provider_id, $model_id, $provider_config, $model_config);
            }
        }

        return $tests_results;
    }

    /**
     * Affiche la page de diagnostic
     */
    public function render_diagnostic_page()
    {
        // Vérifier les autorisations
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les droits suffisants pour accéder à cette page.', 'ai-redactor'));
        }

        // Traiter les actions
        $test_results = [];
        $tests_executed = false;

        if (isset($_POST['action']) && $_POST['action'] === 'test_model' && check_admin_referer('ai_diagnostic_test')) {
            $provider_id = isset($_POST['provider_id']) ? sanitize_text_field($_POST['provider_id']) : null;
            $model_id = isset($_POST['model_id']) ? sanitize_text_field($_POST['model_id']) : null;

            if ($provider_id && $model_id) {
                $test_results = $this->run_tests($provider_id, $model_id);
                $tests_executed = true;

                if (!empty($test_results)) {
                    add_settings_error(
                        'ai_redactor_diagnostic',
                        'test_complete',
                        sprintf(__('Test du modèle %s (%s) terminé.', 'ai-redactor'), $test_results[0]['model_name'], $test_results[0]['provider_name']),
                        'info'
                    );
                }
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'test_all' && check_admin_referer('ai_diagnostic_test_all')) {
            $test_results = $this->run_tests();
            $tests_executed = true;

            add_settings_error(
                'ai_redactor_diagnostic',
                'test_all_complete',
                sprintf(__('Test de tous les modèles terminé. %d modèles testés.', 'ai-redactor'), count($test_results)),
                'info'
            );
        }

        // Afficher l'interface
?>
        <div class="wrap ai-redactor-wrap">
            <h1><?php echo esc_html__('Diagnostic IA', 'ai-redactor'); ?></h1>

            <?php settings_errors('ai_redactor_diagnostic'); ?>

            <p class="description">
                <?php echo esc_html__('Cette page vous permet de tester la connexion avec les différentes API d\'IA configurées dans le plugin.', 'ai-redactor'); ?>
            </p>

            <?php if (!$tests_executed): ?>
                <!-- Dashboard de diagnostic amélioré - État initial -->
                <div class="ai-diagnostic-dashboard">
                    <div class="ai-dashboard-row">
                        <!-- Colonne principale -->
                        <div class="ai-dashboard-column main">
                            <div class="ai-redactor-card ai-welcome-card">
                                <div class="ai-card-icon">
                                    <span class="dashicons dashicons-superhero"></span>
                                </div>
                                <div class="ai-card-content">
                                    <h2><?php echo esc_html__('Bienvenue dans l\'outil de diagnostic IA', 'ai-redactor'); ?></h2>
                                    <p><?php echo esc_html__('Cet outil vous permet de vérifier la connexion avec toutes vos API d\'intelligence artificielle configurées. Lancez un test complet pour vous assurer que tout fonctionne correctement.', 'ai-redactor'); ?></p>

                                    <form method="post" action="" class="ai-test-form ai-main-test-form">
                                        <?php wp_nonce_field('ai_diagnostic_test_all'); ?>
                                        <input type="hidden" name="action" value="test_all">
                                        <button type="submit" class="button button-hero button-primary">
                                            <span class="dashicons dashicons-search"></span>
                                            <?php echo esc_html__('Lancer le diagnostic complet', 'ai-redactor'); ?>
                                        </button>

                                        <div class="ai-loader-container">
                                            <div class="ai-loader"></div>
                                            <span class="ai-loader-text"><?php echo esc_html__('Exécution des tests en cours...', 'ai-redactor'); ?></span>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Explication des tests -->
                            <div class="ai-redactor-card">
                                <div class="ai-redactor-card-header">
                                    <h2>
                                        <span class="dashicons dashicons-info-outline"></span>
                                        <?php echo esc_html__('À propos du diagnostic', 'ai-redactor'); ?>
                                    </h2>
                                </div>

                                <p><?php echo esc_html__('Le diagnostic vérifiera les points suivants :', 'ai-redactor'); ?></p>

                                <div class="ai-tests-grid">
                                    <div class="ai-test-item">
                                        <div class="ai-test-icon">
                                            <span class="dashicons dashicons-admin-network"></span>
                                        </div>
                                        <div class="ai-test-info">
                                            <h4><?php echo esc_html__('Connectivité API', 'ai-redactor'); ?></h4>
                                            <p><?php echo esc_html__('Vérifie que votre serveur peut atteindre les points de terminaison API des différents fournisseurs.', 'ai-redactor'); ?></p>
                                        </div>
                                    </div>

                                    <div class="ai-test-item">
                                        <div class="ai-test-icon">
                                            <span class="dashicons dashicons-lock"></span>
                                        </div>
                                        <div class="ai-test-info">
                                            <h4><?php echo esc_html__('Clés API', 'ai-redactor'); ?></h4>
                                            <p><?php echo esc_html__('Vérifie la validité des clés API configurées pour chaque fournisseur.', 'ai-redactor'); ?></p>
                                        </div>
                                    </div>

                                    <div class="ai-test-item">
                                        <div class="ai-test-icon">
                                            <span class="dashicons dashicons-performance"></span>
                                        </div>
                                        <div class="ai-test-info">
                                            <h4><?php echo esc_html__('Performances', 'ai-redactor'); ?></h4>
                                            <p><?php echo esc_html__('Mesure le temps de réponse de chaque modèle d\'IA et vérifie leur réactivité.', 'ai-redactor'); ?></p>
                                        </div>
                                    </div>

                                    <div class="ai-test-item">
                                        <div class="ai-test-icon">
                                            <span class="dashicons dashicons-text"></span>
                                        </div>
                                        <div class="ai-test-info">
                                            <h4><?php echo esc_html__('Fonctionnement des modèles', 'ai-redactor'); ?></h4>
                                            <p><?php echo esc_html__('Vérifie que chaque modèle répond correctement à un prompt simple.', 'ai-redactor'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Colonne latérale -->
                        <div class="ai-dashboard-column side">
                            <?php
                            // Récupérer la configuration des APIs
                            $providers_config = require dirname(dirname(__FILE__)) . '/providers-config.php';
                            $api_status = [];

                            foreach ($providers_config as $provider_id => $provider) {
                                $api_key_option = $provider['api_key_option'];
                                $api_key = get_option($api_key_option, '');
                                $api_status[$provider_id] = [
                                    'name' => $provider['name'],
                                    'configured' => !empty($api_key),
                                    'models_count' => count($provider['models'])
                                ];
                            }

                            $configured_count = array_sum(array_map(function ($provider) {
                                return $provider['configured'] ? 1 : 0;
                            }, $api_status));

                            $total_providers = count($api_status);
                            $active_model = get_option('ai_redactor_active_model', '');
                            $active_provider = '';
                            $active_model_name = '';

                            if (!empty($active_model)) {
                                $parts = explode(':', $active_model);
                                if (count($parts) === 2) {
                                    $active_provider = $parts[0];
                                    $model_id = $parts[1];
                                    if (isset($providers_config[$active_provider]['models'][$model_id])) {
                                        $active_model_name = $providers_config[$active_provider]['models'][$model_id]['label'];
                                    }
                                }
                            }
                            ?>

                            <!-- État des fournisseurs -->
                            <div class="ai-redactor-card">
                                <div class="ai-redactor-card-header">
                                    <h2>
                                        <span class="dashicons dashicons-chart-pie"></span>
                                        <?php echo esc_html__('État des API', 'ai-redactor'); ?>
                                    </h2>
                                </div>

                                <div class="ai-status-summary">
                                    <div class="ai-status-circle" data-percentage="<?php echo esc_attr(round(($configured_count / $total_providers) * 100)); ?>">
                                        <span class="ai-status-value"><?php echo esc_html($configured_count); ?>/<?php echo esc_html($total_providers); ?></span>
                                        <div class="ai-status-label"><?php echo esc_html__('APIs configurées', 'ai-redactor'); ?></div>
                                    </div>
                                </div>

                                <div class="ai-providers-list">
                                    <?php foreach ($api_status as $provider_id => $provider): ?>
                                        <div class="ai-provider-item <?php echo $provider['configured'] ? 'configured' : 'not-configured'; ?>">
                                            <div class="ai-provider-status">
                                                <?php if ($provider['configured']): ?>
                                                    <span class="dashicons dashicons-yes-alt"></span>
                                                <?php else: ?>
                                                    <span class="dashicons dashicons-no-alt"></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ai-provider-details">
                                                <span class="ai-provider-name"><?php echo esc_html($provider['name']); ?></span>
                                                <span class="ai-provider-models"><?php echo sprintf(esc_html__('%d modèles disponibles', 'ai-redactor'), $provider['models_count']); ?></span>
                                            </div>
                                            <?php if ($active_provider === $provider_id): ?>
                                                <div class="ai-provider-active" title="<?php echo esc_attr__('Modèle actif', 'ai-redactor'); ?>">
                                                    <span class="dashicons dashicons-star-filled"></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if (!empty($active_model_name)): ?>
                                    <div class="ai-active-model-info">
                                        <p>
                                            <strong><?php echo esc_html__('Modèle actif:', 'ai-redactor'); ?></strong>
                                            <?php echo esc_html($active_model_name); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <div class="ai-card-footer">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-redactor-connectors')); ?>" class="button button-secondary">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                        <?php echo esc_html__('Configurer les APIs', 'ai-redactor'); ?>
                                    </a>
                                </div>
                            </div>

                            <!-- Astuces et aide -->
                            <div class="ai-redactor-card ai-tips-card">
                                <div class="ai-redactor-card-header">
                                    <h2>
                                        <span class="dashicons dashicons-lightbulb"></span>
                                        <?php echo esc_html__('Conseils', 'ai-redactor'); ?>
                                    </h2>
                                </div>

                                <ul class="ai-tips-list">
                                    <li><?php echo esc_html__('Configurez au moins une API pour exécuter les tests.', 'ai-redactor'); ?></li>
                                    <li><?php echo esc_html__('Certains tests peuvent prendre jusqu\'à 30 secondes.', 'ai-redactor'); ?></li>
                                    <li><?php echo esc_html__('Après les tests, vous pourrez exporter les résultats.', 'ai-redactor'); ?></li>
                                    <li><?php echo esc_html__('Un résumé sera généré pour faciliter l\'analyse.', 'ai-redactor'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Affichage des résultats des tests -->
                <div class="ai-redactor-card">
                    <div class="ai-redactor-card-header">
                        <h2><?php echo esc_html__('Tester tous les modèles', 'ai-redactor'); ?></h2>
                    </div>

                    <p>
                        <?php echo esc_html__('Cliquez sur le bouton ci-dessous pour tester tous les modèles pour lesquels une clé API est configurée. Les tests peuvent prendre quelques instants à s\'exécuter.', 'ai-redactor'); ?>
                    </p>

                    <form method="post" action="" class="ai-test-form">
                        <?php wp_nonce_field('ai_diagnostic_test_all'); ?>
                        <input type="hidden" name="action" value="test_all">

                        <button type="submit" class="button button-primary" id="test-all-button">
                            <span class="dashicons dashicons-search"></span>
                            <?php echo esc_html__('Tester tous les modèles', 'ai-redactor'); ?>
                        </button>

                        <div class="ai-loader-container">
                            <div class="ai-loader"></div>
                            <span class="ai-loader-text"><?php echo esc_html__('Exécution des tests en cours...', 'ai-redactor'); ?></span>
                        </div>
                    </form>
                </div>

                <?php if (empty($test_results)): ?>
                    <div class="ai-redactor-card">
                        <div class="ai-redactor-card-header">
                            <h2><?php echo esc_html__('État actuel', 'ai-redactor'); ?></h2>
                        </div>
                        <p>
                            <?php echo esc_html__('Aucun résultat disponible. Veuillez lancer un test.', 'ai-redactor'); ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="ai-redactor-card">
                        <div class="ai-redactor-card-header">
                            <h2><?php echo esc_html__('Résultats des tests', 'ai-redactor'); ?></h2>
                            <div class="ai-redactor-card-actions">
                                <button id="copy-results-btn" class="button button-secondary">
                                    <span class="dashicons dashicons-clipboard"></span>
                                    <?php echo esc_html__('Copier les résultats', 'ai-redactor'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Tableau des résultats -->
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Fournisseur', 'ai-redactor'); ?></th>
                                    <th><?php echo esc_html__('Modèle', 'ai-redactor'); ?></th>
                                    <th><?php echo esc_html__('Clé API', 'ai-redactor'); ?></th>
                                    <th><?php echo esc_html__('Statut', 'ai-redactor'); ?></th>
                                    <th><?php echo esc_html__('Temps de réponse', 'ai-redactor'); ?></th>
                                    <th><?php echo esc_html__('Résultat', 'ai-redactor'); ?></th>
                                    <th><?php echo esc_html__('Actions', 'ai-redactor'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($test_results as $result): ?>
                                    <tr>
                                        <td><?php echo esc_html($result['provider_name']); ?></td>
                                        <td><?php echo esc_html($result['model_name']); ?></td>
                                        <td>
                                            <?php if ($result['api_key_status'] === 'ok'): ?>
                                                <span class="ai-redactor-status success">✅ <?php echo esc_html__('OK', 'ai-redactor'); ?></span>
                                            <?php else: ?>
                                                <span class="ai-redactor-status error">❌ <?php echo esc_html__('Manquante', 'ai-redactor'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($result['api_key_status'] === 'ok'): ?>
                                                <?php if ($result['request_status'] === 'success'): ?>
                                                    <span class="ai-redactor-status success">✅ <?php echo esc_html__('Succès', 'ai-redactor'); ?></span>
                                                <?php elseif ($result['request_status'] === 'error'): ?>
                                                    <span class="ai-redactor-status error">❌ <?php echo esc_html__('Échec', 'ai-redactor'); ?></span>
                                                <?php else: ?>
                                                    <span class="ai-redactor-status neutral">⚪ <?php echo esc_html__('Non testé', 'ai-redactor'); ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="ai-redactor-status neutral">⚪ <?php echo esc_html__('Non testé', 'ai-redactor'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($result['response_time'] !== null) {
                                                echo esc_html($result['response_time'] . ' ms');
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($result['api_key_status'] === 'ok' && $result['request_status'] !== 'not_tested') {
                                                echo '<div class="ai-redactor-result-output">';
                                                // Tronquer la sortie si elle est trop longue
                                                $output = esc_html($result['result']);
                                                if (strlen($output) > 200) {
                                                    echo substr($output, 0, 200) . '...';
                                                } else {
                                                    echo $output;
                                                }
                                                echo '</div>';
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <form method="post" action="" style="display: inline;" class="ai-test-form">
                                                <?php wp_nonce_field('ai_diagnostic_test'); ?>
                                                <input type="hidden" name="action" value="test_model">
                                                <input type="hidden" name="provider_id" value="<?php echo esc_attr($result['provider_id']); ?>">
                                                <input type="hidden" name="model_id" value="<?php echo esc_attr($result['model_id']); ?>">

                                                <button type="submit" class="button button-small test-model-button">
                                                    <span class="dashicons dashicons-update"></span>
                                                    <?php echo esc_html__('Re-tester', 'ai-redactor'); ?>
                                                </button>

                                                <div class="ai-loader-container small">
                                                    <div class="ai-loader"></div>
                                                </div>
                                            </form>

                                            <?php if ($result['api_key_status'] !== 'ok'): ?>
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=ai-redactor-connectors')); ?>" class="button button-small">
                                                    <span class="dashicons dashicons-admin-generic"></span>
                                                    <?php echo esc_html__('Configurer', 'ai-redactor'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Résumé des tests -->
                        <div class="ai-redactor-test-summary">
                            <h3><?php echo esc_html__('Résumé des tests', 'ai-redactor'); ?></h3>
                            <?php
                            // Analyse des résultats
                            $total_tests = count($test_results);
                            $configured_apis = 0;
                            $successful_tests = 0;
                            $failed_tests = 0;
                            $not_tested = 0;
                            $total_response_time = 0;
                            $providers_tested = [];

                            foreach ($test_results as $result) {
                                // Compter les APIs configurées
                                if ($result['api_key_status'] === 'ok') {
                                    $configured_apis++;

                                    // Compter les tests réussis et échoués
                                    if ($result['request_status'] === 'success') {
                                        $successful_tests++;
                                        // Ajouter au temps de réponse total
                                        $total_response_time += $result['response_time'];
                                    } elseif ($result['request_status'] === 'error') {
                                        $failed_tests++;
                                    } else {
                                        $not_tested++;
                                    }
                                } else {
                                    $not_tested++;
                                }

                                // Collecter les fournisseurs uniques
                                $providers_tested[$result['provider_id']] = $result['provider_name'];
                            }

                            // Calculer le temps de réponse moyen
                            $avg_response_time = $successful_tests > 0 ? round($total_response_time / $successful_tests) : 0;

                            // Préparation des données pour l'export
                            $exportable_results = [];
                            foreach ($test_results as $result) {
                                $exportable_results[] = [
                                    'fournisseur' => $result['provider_name'],
                                    'modele' => $result['model_name'],
                                    'cle_api' => $result['api_key_status'] === 'ok' ? 'Configurée' : 'Manquante',
                                    'statut' => $result['request_status'] === 'success' ? 'Succès' : ($result['request_status'] === 'error' ? 'Échec' : 'Non testé'),
                                    'temps_reponse' => $result['response_time'] !== null ? $result['response_time'] . ' ms' : 'N/A',
                                    'reponse' => $result['result']
                                ];
                            }

                            // Création du texte pour l'export
                            $export_text  = "=== RAPPORT DE DIAGNOSTIC API IA ===\n";
                            $export_text .= "Date du test: " . date('Y-m-d H:i:s') . "\n\n";
                            $export_text .= "== RÉSUMÉ ==\n";
                            $export_text .= "Total des modèles testés: " . $total_tests . "\n";
                            $export_text .= "APIs configurées: " . $configured_apis . " / " . $total_tests . "\n";
                            $export_text .= "Tests réussis: " . $successful_tests . " / " . $configured_apis . "\n";
                            $export_text .= "Tests échoués: " . $failed_tests . "\n";
                            $export_text .= "Temps de réponse moyen: " . $avg_response_time . " ms\n";
                            $export_text .= "Fournisseurs testés: " . implode(', ', $providers_tested) . "\n\n";
                            $export_text .= "== RÉSULTATS DÉTAILLÉS ==\n";

                            foreach ($exportable_results as $index => $result) {
                                $export_text .= "\n[Test #" . ($index + 1) . "]\n";
                                $export_text .= "Fournisseur: " . $result['fournisseur'] . "\n";
                                $export_text .= "Modèle: " . $result['modele'] . "\n";
                                $export_text .= "Clé API: " . $result['cle_api'] . "\n";
                                $export_text .= "Statut: " . $result['statut'] . "\n";
                                $export_text .= "Temps de réponse: " . $result['temps_reponse'] . "\n";
                                $export_text .= "Réponse: " . $result['reponse'] . "\n";
                            }

                            // Création des données JSON pour l'export
                            $export_json = json_encode([
                                'meta' => [
                                    'date' => date('Y-m-d H:i:s'),
                                    'summary' => [
                                        'total_models' => $total_tests,
                                        'configured_apis' => $configured_apis,
                                        'successful_tests' => $successful_tests,
                                        'failed_tests' => $failed_tests,
                                        'avg_response_time' => $avg_response_time,
                                        'tested_providers' => array_values($providers_tested)
                                    ]
                                ],
                                'results' => $exportable_results
                            ], JSON_PRETTY_PRINT);
                            ?>

                            <div class="ai-redactor-summary-stats">
                                <p><strong><?php echo esc_html__('Total des modèles testés:', 'ai-redactor'); ?></strong> <?php echo esc_html($total_tests); ?></p>
                                <p><strong><?php echo esc_html__('APIs configurées:', 'ai-redactor'); ?></strong> <?php echo esc_html($configured_apis); ?> / <?php echo esc_html($total_tests); ?></p>
                                <p><strong><?php echo esc_html__('Tests réussis:', 'ai-redactor'); ?></strong> <?php echo esc_html($successful_tests); ?> / <?php echo esc_html($configured_apis); ?></p>
                                <p><strong><?php echo esc_html__('Tests échoués:', 'ai-redactor'); ?></strong> <?php echo esc_html($failed_tests); ?></p>
                                <p><strong><?php echo esc_html__('Temps de réponse moyen:', 'ai-redactor'); ?></strong> <?php echo esc_html($avg_response_time); ?> ms</p>
                                <p><strong><?php echo esc_html__('Fournisseurs testés:', 'ai-redactor'); ?></strong> <?php echo esc_html(implode(', ', $providers_tested)); ?></p>
                            </div>

                            <div class="ai-redactor-summary-conclusion">
                                <?php if ($successful_tests === $configured_apis && $configured_apis > 0): ?>
                                    <p class="ai-redactor-success-message">
                                        <?php echo esc_html__('✅ Tous les tests ont réussi ! Vos APIs sont correctement configurées et fonctionnelles.', 'ai-redactor'); ?>
                                    </p>
                                <?php elseif ($successful_tests > 0): ?>
                                    <p class="ai-redactor-partial-message">
                                        <?php echo esc_html__('⚠️ Certains tests ont réussi, mais d\'autres ont échoué. Vérifiez les erreurs dans le tableau ci-dessus.', 'ai-redactor'); ?>
                                    </p>
                                <?php elseif ($configured_apis > 0): ?>
                                    <p class="ai-redactor-error-message">
                                        <?php echo esc_html__('❌ Tous les tests ont échoué. Vérifiez que vos clés API sont valides et que votre connexion internet fonctionne correctement.', 'ai-redactor'); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="ai-redactor-warning-message">
                                        <?php echo esc_html__('⚠️ Aucune API n\'est configurée. Veuillez configurer au moins une API pour pouvoir effectuer des tests.', 'ai-redactor'); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- Zone d'export des résultats -->
                            <div class="ai-redactor-export-section">
                                <h3><?php echo esc_html__('Exporter les résultats', 'ai-redactor'); ?></h3>

                                <div class="ai-export-tabs">
                                    <button class="ai-export-tab active" data-tab="text"><?php echo esc_html__('Format texte', 'ai-redactor'); ?></button>
                                    <button class="ai-export-tab" data-tab="json"><?php echo esc_html__('Format JSON', 'ai-redactor'); ?></button>
                                    <button class="ai-export-tab" data-tab="email"><?php echo esc_html__('Préparer un email', 'ai-redactor'); ?></button>
                                </div>

                                <div class="ai-export-content active" id="text-content">
                                    <p class="description"><?php echo esc_html__('Résultats au format texte, prêts à être copiés.', 'ai-redactor'); ?></p>
                                    <textarea class="ai-export-textarea" id="export-text" readonly><?php echo esc_textarea($export_text); ?></textarea>
                                </div>

                                <div class="ai-export-content" id="json-content">
                                    <p class="description"><?php echo esc_html__('Résultats au format JSON, pour une analyse automatisée.', 'ai-redactor'); ?></p>
                                    <textarea class="ai-export-textarea" id="export-json" readonly><?php echo esc_textarea($export_json); ?></textarea>
                                </div>

                                <div class="ai-export-content" id="email-content">
                                    <p class="description"><?php echo esc_html__('Envoyez ces résultats par email pour assistance.', 'ai-redactor'); ?></p>

                                    <a href="mailto:support@votresite.com?subject=<?php echo urlencode('Rapport diagnostic API IA'); ?>&body=<?php echo urlencode(substr($export_text, 0, 1500) . "\n\n[Rapport complet en pièce jointe]"); ?>" class="button button-secondary">
                                        <span class="dashicons dashicons-email-alt"></span>
                                        <?php echo esc_html__('Ouvrir dans votre client email', 'ai-redactor'); ?>
                                    </a>

                                    <div class="ai-email-template">
                                        <p>À: support@votresite.com</p>
                                        <p>Objet: Rapport diagnostic API IA</p>
                                        <p>Corps:</p>
                                        <textarea class="ai-export-textarea" readonly><?php echo esc_textarea("Bonjour,\n\nVeuillez trouver ci-dessous les résultats du diagnostic des APIs d'IA:\n\n" . $export_text . "\n\nMerci de votre aide,\n[Votre nom]"); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (defined('AI_REDACTOR_DEBUG') && AI_REDACTOR_DEBUG): ?>
                    <div class="ai-redactor-card">
                        <div class="ai-redactor-card-header">
                            <h2><?php echo esc_html__('Informations de diagnostic', 'ai-redactor'); ?></h2>
                        </div>

                        <p>
                            <strong><?php echo esc_html__('Fichier de log:', 'ai-redactor'); ?></strong>
                            <?php echo esc_html($this->log_file); ?>
                        </p>

                        <p>
                            <strong><?php echo esc_html__('Prompt utilisé pour les tests:', 'ai-redactor'); ?></strong>
                            <code><?php echo esc_html($this->test_prompt); ?></code>
                        </p>

                        <?php
                        // Afficher le contenu du fichier de log s'il existe
                        if (file_exists($this->log_file) && filesize($this->log_file) > 0) {
                            $log_content = file_get_contents($this->log_file);
                            $log_entries = array_filter(explode("\n", $log_content));

                            // Limiter aux 20 dernières lignes
                            $log_entries = array_slice($log_entries, -20);
                        ?>
                            <div class="ai-redactor-debug-log">
                                <h3><?php echo esc_html__('Dernières entrées du journal de diagnostic:', 'ai-redactor'); ?></h3>
                                <pre><?php echo esc_html(implode("\n", $log_entries)); ?></pre>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
<?php
    }
}
