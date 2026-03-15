<?php

/**
 * Page d'administration des connecteurs IA
 *
 * Ce fichier contient la classe principale responsable de coordonner les différentes pages
 * de l'interface d'administration pour la gestion des connecteurs IA, le test des prompts,
 * et le diagnostic des configurations. Il enregistre les sous-menus, charge les assets
 * nécessaires et affiche les pages correspondantes.
 *
 * @package WP_Plugin_AI_Connector
 * @subpackage Services\AI\UI
 *
 * @depends AI_Connector_Form_Handler
 * @depends AI_Connector_Ajax_Handler
 * @depends AI_Connector_Admin_Form
 * @depends AI_Connector_Admin_Tester
 * @depends AI_Connector_Admin_Diagnostic
 *
 * @css /assets/css/admin-models-form.css
 * @css /assets/css/ai-admin-tester.css
 *
 * @js /assets/js/ai-admin.js
 * @js /assets/js/ai-admin-tester.js
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Charger les fichiers nécessaires
require_once dirname(dirname(__FILE__)) . '/core/ai-admin-form-handler.php';
require_once dirname(__FILE__) . '/ai-admin-form.php';
require_once dirname(dirname(__FILE__)) . '/core/ai-admin-ajax.php';

/**
 * Classe pour gérer la page d'administration des connecteurs IA
 */
class AI_Connector_AI_Admin
{

    /**
     * Configuration des fournisseurs IA
     *
     * @var array
     */
    private $providers_config;

    /**
     * Gestionnaire de formulaire
     *
     * @var AI_Connector_Form_Handler
     */
    private $form_handler;

    /**
     * Gestionnaire AJAX
     *
     * @var AI_Connector_Ajax_Handler
     */
    private $ajax_handler;

    /**
     * Initialisation de la classe
     */
    public function __construct()
    {
        // Charger la configuration des fournisseurs
        $this->providers_config = require_once dirname(dirname(__FILE__)) . '/providers-config.php';

        // Initialiser les gestionnaires
        $this->form_handler = new AI_Connector_Form_Handler($this->providers_config);
        $this->ajax_handler = new AI_Connector_Ajax_Handler($this->providers_config);

        // Ajouter la page d'administration
        add_action('admin_menu', array($this, 'register_admin_page'));

        // Enregistrer les scripts et styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // Ajouter le gestionnaire AJAX pour le test de connexion API
        add_action('wp_ajax_ai_connector_test_connection', array($this, 'handle_test_connection'));
    }

    /**
     * Enregistre la page d'administration comme sous-menu
     */
    public function register_admin_page()
    {
        // Page principale des connecteurs IA
        add_submenu_page(
            'ai-connector',
            __('Connecteurs IA', 'ai-connector'),
            __('Connecteurs IA', 'ai-connector'),
            'manage_options',
            'ai-connector-connectors',
            array($this, 'render_admin_page')
        );

        // Page de test des prompts IA
        add_submenu_page(
            'ai-connector',
            __('Testeur de Prompt IA', 'ai-connector'),
            __('Testeur IA', 'ai-connector'),
            'manage_options',
            'ai-connector-tester',
            array($this, 'render_tester_page')
        );

        // Page de diagnostic IA
        add_submenu_page(
            'ai-connector',
            __('Diagnostic IA', 'ai-connector'),
            __('Diagnostic IA', 'ai-connector'),
            'manage_options',
            'ai-connector-diagnostic',
            array($this, 'render_diagnostic_page')
        );
    }

    /**
     * Chargement des assets (CSS et JavaScript)
     *
     * @param string $hook Hook du menu actuel
     */
    public function enqueue_assets($hook)
    {
        // Debug pour afficher le hook actuel dans les logs
        ai_connector_log('Chargement des assets - Hook actuel: ' . $hook, 'debug');

        // Enqueue global pour le script de debug
        wp_enqueue_script(
            'ai-connector-debug-js',
            WP_PLUGIN_AI_CONNECTOR_URL . 'assets/js/ai-admin.js',
            array('jquery'),
            WP_PLUGIN_AI_CONNECTOR_VERSION,
            true
        );

        // Localisation globale pour le debug
        wp_localize_script('ai-connector-debug-js', 'aiAgentAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_connector_ajax'),
        ));

        // Styles pour la page des connecteurs
        if (
            strpos($hook, 'ai_connector_page_ai-connector-diagnostic') !== false ||
            strpos($hook, 'ai-connector-diagnostic') !== false ||
            strpos($hook, 'ai_connector_page_ai-connector-connectors') !== false ||
            strpos($hook, 'toplevel_page_ai-connector') !== false
        ) {

            ai_connector_log('Loading assets for connectors page', 'debug');

            // Chargement des styles
            wp_enqueue_style(
                'ai-connector-admin-form-css',
                WP_PLUGIN_AI_CONNECTOR_URL . 'assets/css/admin-models-form.css',
                array(),
                WP_PLUGIN_AI_CONNECTOR_VERSION
            );
        }

        // Styles pour la page de diagnostic
        if (strpos($hook, 'ai-connector-diagnostic') !== false) {
            wp_enqueue_style(
                'ai-connector-admin-diagnostic-css',
                WP_PLUGIN_AI_CONNECTOR_URL . 'assets/css/admin-diagnostic.css',
                array(),
                WP_PLUGIN_AI_CONNECTOR_VERSION
            );
        }

        // Styles pour la page de test des prompts
        elseif (strpos($hook, 'ai-connector-tester') !== false) {
            wp_enqueue_style(
                'ai-connector-admin-tester-css',
                WP_PLUGIN_AI_CONNECTOR_URL . 'assets/css/ai-admin-tester.css',
                array(),
                WP_PLUGIN_AI_CONNECTOR_VERSION
            );
        }
    }

    /**
     * Affiche la page d'administration
     */
    public function render_admin_page()
    {
        // Vérifier les autorisations
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les droits suffisants pour accéder à cette page.', 'ai-connector'));
        }

        // Récupérer le modèle actif
        $active_model = get_option('ai_connector_active_model', '');

        // Afficher la page avec le formulaire
        echo '<div class="wrap ai-connector-admin">';
        echo '<h1>' . esc_html__('Connecteurs IA pour AI Connector', 'ai-connector') . '</h1>';
        echo '<p class="description">' . esc_html__('Configurez vos fournisseurs d\'IA, choisissez un modèle actif et testez les connexions.', 'ai-connector') . '</p>';

        settings_errors('ai_connector_connectors');

        // Afficher le formulaire avec la nouvelle classe
        AI_Connector_Admin_Form::render_form($this->providers_config, $active_model);

        echo '</div>';
    }

    /**
     * Affiche la page de test des prompts IA
     */
    public function render_tester_page()
    {
        // Inclure et instancier la classe du testeur IA
        require_once dirname(__FILE__) . '/ai-admin-tester.php';
        $tester = new AI_Redactor_Admin_Tester();
        $tester->render_tester_page();
    }

    /**
     * Affiche la page de diagnostic IA
     */
    public function render_diagnostic_page()
    {
        // Inclure et instancier la classe du diagnostic IA
        require_once dirname(__FILE__) . '/ai-admin-diagnostic.php';
        $diagnostic = new AI_Redactor_Admin_Diagnostic();
        $diagnostic->render_diagnostic_page();
    }

    /**
     * Gère les requêtes AJAX pour tester la connexion avec un fournisseur d'IA
     */
    public function handle_test_connection()
    {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_connector_test_connection')) {
            wp_send_json_error([
                'message' => __('Erreur de sécurité. Veuillez actualiser la page et réessayer.', 'ai-connector')
            ]);
            return;
        }

        // Vérifier que l'utilisateur a les droits nécessaires
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Vous n\'avez pas les droits suffisants pour effectuer cette action.', 'ai-connector')
            ]);
            return;
        }

        // Récupérer le fournisseur et la clé API
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

        if (empty($provider) || empty($api_key)) {
            wp_send_json_error([
                'message' => __('Fournisseur ou clé API manquante.', 'ai-connector')
            ]);
            return;
        }

        // Vérifier que le fournisseur existe dans la configuration
        if (!isset($this->providers_config[$provider])) {
            wp_send_json_error([
                'message' => sprintf(__('Fournisseur %s non trouvé dans la configuration.', 'ai-connector'), $provider)
            ]);
            return;
        }

        ai_connector_log('Test de connexion demandé pour le fournisseur: ' . $provider, 'info');

        // Sauvegarder temporairement la clé API pour le test
        $api_key_option = $this->providers_config[$provider]['api_key_option'];
        $old_api_key = get_option($api_key_option, '');
        update_option($api_key_option, $api_key);

        // Sélectionner un modèle pour le test
        $models = $this->providers_config[$provider]['models'];
        $model_id = key($models); // Premier modèle comme modèle de test

        // Créer un modèle actif temporaire pour le test
        $old_active_model = get_option('ai_connector_active_model', '');
        $test_model = $provider . ':' . $model_id;
        update_option('ai_connector_active_model', $test_model);

        // Préparer le prompt de test simple
        $test_prompt = "Dites bonjour en français.";

        try {
            // Inclure le gestionnaire de requêtes IA
            require_once dirname(dirname(dirname(__FILE__))) . '/services/ai/core/ai-request-handler.php';

            // Mesurer le temps de réponse
            $start_time = microtime(true);

            // Effectuer l'appel à l'API
            $result = AI_Request_Handler::send_prompt($test_prompt);

            // Mesurer le temps de réponse
            $response_time = round((microtime(true) - $start_time) * 1000); // en millisecondes

            // Restaurer les valeurs d'origine
            update_option($api_key_option, $old_api_key);
            update_option('ai_connector_active_model', $old_active_model);

            if ($result['success']) {
                ai_connector_log('Test de connexion réussi pour ' . $provider, 'info');
                wp_send_json_success([
                    'message' => sprintf(
                        __('Modèle %s testé en %d ms.', 'ai-connector'),
                        $models[$model_id]['label'],
                        $response_time
                    ),
                    'response' => $result['response'],
                    'time' => $response_time
                ]);
            } else {
                ai_connector_log('Échec du test de connexion pour ' . $provider . ': ' . $result['error'], 'error');
                wp_send_json_error([
                    'message' => $result['error'],
                    'time' => $response_time
                ]);
            }
        } catch (Exception $e) {
            // Restaurer les valeurs d'origine en cas d'erreur
            update_option($api_key_option, $old_api_key);
            update_option('ai_connector_active_model', $old_active_model);

            ai_connector_log('Exception lors du test de connexion pour ' . $provider . ': ' . $e->getMessage(), 'error');
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }

        // Assurer que WordPress termine correctement la requête
        wp_die();
    }
}

// Initialiser la page d'administration
$ai_connector_ai_admin = new AI_Connector_AI_Admin();
