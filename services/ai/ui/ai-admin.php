<?php

/**
 * Page d'administration des connecteurs IA
 *
 * Ce fichier contient la classe principale responsable de coordonner les différentes pages
 * de l'interface d'administration pour la gestion des connecteurs IA, le test des prompts,
 * et le diagnostic des configurations. Il enregistre les sous-menus, charge les assets
 * nécessaires et affiche les pages correspondantes.
 *
 * @package AI_Redactor
 * @subpackage Services\AI\UI
 *
 * @depends AI_Redactor_Form_Handler
 * @depends AI_Redactor_Ajax_Handler
 * @depends AI_Redactor_Admin_Form
 * @depends AI_Redactor_Admin_Tester
 * @depends AI_Redactor_Admin_Diagnostic
 *
 * @css /assets/css/ai-admin.css
 * @css /assets/css/ai-admin-tester.css
 *
 * @js /assets/js/ai-admin.js
 * @js /assets/js/ai-admin-tester.js
 *
 * @ai Ce fichier est exclusivement dédié à la gestion des pages d'administration pour les connecteurs IA.
 * Il ne contient aucune logique métier liée à la génération de contenu par IA, ni d'algorithmes d'IA.
 * Sa responsabilité est strictement limitée à : (1) enregistrer les sous-menus d'administration,
 * (2) charger les assets CSS et JavaScript nécessaires, et (3) afficher les pages d'administration
 * correspondantes. Toute logique métier ou de communication avec les APIs est déléguée à d'autres classes.
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
class AI_Redactor_AI_Admin
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
     * @var AI_Redactor_Form_Handler
     */
    private $form_handler;

    /**
     * Gestionnaire AJAX
     *
     * @var AI_Redactor_Ajax_Handler
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
        $this->form_handler = new AI_Redactor_Form_Handler($this->providers_config);
        $this->ajax_handler = new AI_Redactor_Ajax_Handler($this->providers_config);

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
            'ai-agent', // Page parent mise à jour pour "IA Agent"
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
        // CSS et JS communs (admin-common.css est déjà chargé)

        // Styles et scripts pour la page des connecteurs
        if ('ai-redactor_page_ai-redactor-connectors' === $hook) {
            // Chargement des styles
            wp_enqueue_style(
                'ai-redactor-admin-css',
                AI_REDACTOR_AI_ASSETS_URL . 'css/ai-admin.css',
                array('ai-redactor-admin-common'),
                '2.0.1'
            );

            // Chargement des scripts
            wp_enqueue_script(
                'ai-redactor-admin-js',
                AI_REDACTOR_AI_ASSETS_URL . 'js/ai-admin.js',
                array('jquery'),
                '2.0.0',
                true
            );

            // Localisation du script
            wp_localize_script('ai-redactor-admin-js', 'aiRedactorAdmin', array(
                'ajaxUrl'        => admin_url('admin-ajax.php'),
                'testNonce'      => wp_create_nonce('ai_redactor_test_connection'),
                'testingText'    => __('Test en cours...', 'ai-redactor'),
                'testButtonText' => __('Tester la connexion', 'ai-redactor'),
                'successText'    => __('Connexion réussie!', 'ai-redactor'),
                'errorText'      => __('Erreur de connexion', 'ai-redactor'),
                'showKeyText'    => __('Afficher la clé API', 'ai-redactor'),
                'hideKeyText'    => __('Masquer la clé API', 'ai-redactor'),
            ));
        }

        // Styles pour la page de test des prompts
        elseif ('ai-redactor_page_ai-redactor-tester' === $hook) {
            wp_enqueue_style(
                'ai-redactor-admin-tester-css',
                AI_REDACTOR_AI_ASSETS_URL . 'css/ai-admin-tester.css',
                array('ai-redactor-admin-common'),
                '1.0.0'
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
            wp_die(__('Vous n\'avez pas les droits suffisants pour accéder à cette page.', 'ai-redactor'));
        }

        // Récupérer le modèle actif
        $active_model = get_option('ai_redactor_active_model', '');

        // Afficher la page avec le formulaire
        echo '<div class="wrap ai-redactor-admin">';
        echo '<h1>' . esc_html__('Connecteurs IA pour AI Redactor', 'ai-redactor') . '</h1>';
        echo '<p class="description">' . esc_html__('Configurez vos fournisseurs d\'IA, choisissez un modèle actif et testez les connexions.', 'ai-redactor') . '</p>';

        settings_errors('ai_redactor_connectors');

        // Afficher le formulaire
        AI_Redactor_Admin_Form::render_form($this->providers_config, $active_model);

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
        $diagnostic = new AI_Redactor_Admin_Diagnostic(false); // Le paramètre false indique de ne pas ajouter son propre sous-menu
        $diagnostic->render_diagnostic_page();
    }
}

// Initialiser la page d'administration
$ai_redactor_ai_admin = new AI_Redactor_AI_Admin();
