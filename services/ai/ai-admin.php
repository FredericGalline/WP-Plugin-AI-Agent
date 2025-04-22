<?php

/**
 * Page d'administration des connecteurs IA
 *
 * Ce fichier contient la classe principale responsable de coordonner les différentes pages
 * de l'interface d'administration pour la gestion des connecteurs IA, le test des prompts,
 * et le diagnostic des configurations. Il enregistre les sous-menus, charge les assets
 * nécessaires et affiche les pages correspondantes.
 *
 * @package WP_Plugin_AI_Agent
 * @subpackage Services\AI\UI
 *
 * @depends AI_Agent_Form_Handler
 * @depends AI_Agent_Ajax_Handler
 * @depends AI_Agent_Admin_Form
 * @depends AI_Agent_Admin_Tester
 * @depends AI_Agent_Admin_Diagnostic
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
class AI_Agent_AI_Admin
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
     * @var AI_Agent_Form_Handler
     */
    private $form_handler;

    /**
     * Gestionnaire AJAX
     *
     * @var AI_Agent_Ajax_Handler
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
        $this->form_handler = new AI_Agent_Form_Handler($this->providers_config);
        $this->ajax_handler = new AI_Agent_Ajax_Handler($this->providers_config);

        // Ajouter la page d'administration
        add_action('admin_menu', array($this, 'register_admin_page'));

        // Enregistrer les scripts et styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enregistre la page d'administration comme sous-menu
     */
    public function register_admin_page()
    {
        // Page principale des connecteurs IA
        add_submenu_page(
            'ai-agent',
            __('Connecteurs IA', 'ai-agent'),
            __('Connecteurs IA', 'ai-agent'),
            'manage_options',
            'ai-agent-connectors',
            array($this, 'render_admin_page')
        );

        // Page de test des prompts IA
        add_submenu_page(
            'ai-agent',
            __('Testeur de Prompt IA', 'ai-agent'),
            __('Testeur IA', 'ai-agent'),
            'manage_options',
            'ai-agent-tester',
            array($this, 'render_tester_page')
        );

        // Page de diagnostic IA
        add_submenu_page(
            'ai-agent',
            __('Diagnostic IA', 'ai-agent'),
            __('Diagnostic IA', 'ai-agent'),
            'manage_options',
            'ai-agent-diagnostic',
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
        ai_agent_log('Chargement des assets - Hook actuel: ' . $hook, 'debug');

        // Enqueue global pour le script de debug
        wp_enqueue_script(
            'ai-agent-debug-js',
            WP_PLUGIN_AI_AGENT_URL . 'assets/js/ai-admin.js',
            array('jquery'),
            WP_PLUGIN_AI_AGENT_VERSION,
            true
        );

        // Localisation globale pour le debug
        wp_localize_script('ai-agent-debug-js', 'aiAgentAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_agent_ajax'),
        ));

        // Styles pour la page des connecteurs
        if (
            strpos($hook, 'ai_agent_page_ai-agent-diagnostic') !== false ||
            strpos($hook, 'ai-agent-diagnostic') !== false ||
            strpos($hook, 'ai_agent_page_ai-agent-connectors') !== false ||
            strpos($hook, 'toplevel_page_ai-agent') !== false
        ) {

            ai_agent_log('Loading assets for connectors page', 'debug');

            // Chargement des styles
            wp_enqueue_style(
                'ai-agent-admin-form-css',
                WP_PLUGIN_AI_AGENT_URL . 'assets/css/admin-models-form.css',
                array(),
                WP_PLUGIN_AI_AGENT_VERSION
            );
        }

        // Styles pour la page de diagnostic
        if (strpos($hook, 'ai-agent-diagnostic') !== false) {
            wp_enqueue_style(
                'ai-agent-admin-diagnostic-css',
                WP_PLUGIN_AI_AGENT_URL . 'assets/css/admin-diagnostic.css',
                array(),
                WP_PLUGIN_AI_AGENT_VERSION
            );
        }

        // Styles pour la page de test des prompts
        elseif (strpos($hook, 'ai-agent-tester') !== false) {
            wp_enqueue_style(
                'ai-agent-admin-tester-css',
                WP_PLUGIN_AI_AGENT_URL . 'assets/css/ai-admin-tester.css',
                array(),
                WP_PLUGIN_AI_AGENT_VERSION
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
            wp_die(__('Vous n\'avez pas les droits suffisants pour accéder à cette page.', 'ai-agent'));
        }

        // Récupérer le modèle actif
        $active_model = get_option('ai_agent_active_model', '');

        // Afficher la page avec le formulaire
        echo '<div class="wrap ai-agent-admin">';
        echo '<h1>' . esc_html__('Connecteurs IA pour AI Agent', 'ai-agent') . '</h1>';
        echo '<p class="description">' . esc_html__('Configurez vos fournisseurs d\'IA, choisissez un modèle actif et testez les connexions.', 'ai-agent') . '</p>';

        settings_errors('ai_agent_connectors');

        // Afficher le formulaire avec la nouvelle classe
        AI_Agent_Admin_Form::render_form($this->providers_config, $active_model);

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
}

// Initialiser la page d'administration
$ai_agent_ai_admin = new AI_Agent_AI_Admin();
