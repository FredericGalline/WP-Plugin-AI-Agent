<?php

/**
 * Page de tableau de bord pour le plugin AI Connector
 * 
 * Affiche une interface moderne et attrayante pour le tableau de bord principal
 * du plugin AI Connector avec des liens rapides et des informations utiles.
 * 
 * @package WP_Plugin_AI_Connector
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Vérifier que l'utilisateur a les permissions requises
if (!current_user_can('manage_options')) {
    wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.', 'ai-connector'));
}

// Charger les icônes de Dashicons
wp_enqueue_style('dashicons');

/**
 * Récupère et retourne le nombre de modèles AI disponibles
 */
function wp_plugin_ai_connector_get_available_models()
{
    $providers = ai_connector_get_providers_config();
    $count = 0;
    foreach ($providers as $provider_data) {
        if (isset($provider_data['models']) && is_array($provider_data['models'])) {
            $count += count($provider_data['models']);
        }
    }
    return $count;
}

/**
 * Récupère et retourne le nombre de fournisseurs configurés
 */
function wp_plugin_ai_connector_get_request_count()
{
    $providers = ai_connector_get_providers_config();
    $count = 0;
    foreach ($providers as $provider_id => $provider_data) {
        if (ai_connector_is_provider_configured($provider_id)) {
            $count++;
        }
    }
    return $count;
}

/**
 * Récupère et retourne l'état de santé du système
 */
function wp_plugin_ai_connector_get_system_status()
{
    $active = ai_connector_get_active_model();
    if (empty($active['provider']) || empty($active['model'])) {
        return __('Non configuré', 'ai-connector');
    }
    if (!ai_connector_is_provider_configured($active['provider'])) {
        return __('Clé API manquante', 'ai-connector');
    }
    return __('Opérationnel', 'ai-connector');
}

/**
 * Génère une carte de fonctionnalité pour le tableau de bord
 */
function wp_plugin_ai_connector_render_feature_card($title, $icon, $description, $button_text, $button_url, $is_primary = true)
{
?>
    <div class="ai-connector-card">
        <div class="ai-connector-card-header">
            <h2><span class="dashicons dashicons-<?php echo esc_attr($icon); ?>"></span> <?php echo esc_html($title); ?></h2>
        </div>
        <div class="ai-connector-card-content">
            <p><?php echo esc_html($description); ?></p>
        </div>
        <div class="ai-connector-card-footer">
            <a href="<?php echo esc_url($button_url); ?>" class="ai-connector-button <?php echo $is_primary ? 'ai-connector-button-primary' : 'ai-connector-button-secondary'; ?>">
                <?php echo esc_html($button_text); ?>
            </a>
        </div>
    </div>
<?php
}
?>

<div class="wrap ai-connector-wrap">
    <div class="ai-connector-header">
        <h1><?php echo esc_html__('Tableau de bord AI Connector', 'ai-connector'); ?></h1>
        <p><?php echo esc_html__('Configurez vos fournisseurs, testez vos connexions et exposez une API IA reutilisable par vos plugins metier.', 'ai-connector'); ?></p>
    </div>

    <!-- Statistiques -->
    <div class="ai-connector-grid ai-connector-stats-grid">
        <div class="ai-connector-card ai-connector-stat-card">
            <div class="ai-connector-card-content">
                <h3><?php echo esc_html__('Modèles disponibles', 'ai-connector'); ?></h3>
                <div class="ai-connector-stat-value"><?php echo esc_html(wp_plugin_ai_connector_get_available_models()); ?></div>
            </div>
        </div>

        <div class="ai-connector-card ai-connector-stat-card">
            <div class="ai-connector-card-content">
                <h3><?php echo esc_html__('Requêtes AI effectuées', 'ai-connector'); ?></h3>
                <div class="ai-connector-stat-value"><?php echo esc_html(wp_plugin_ai_connector_get_request_count()); ?></div>
            </div>
        </div>

        <div class="ai-connector-card ai-connector-stat-card">
            <div class="ai-connector-card-content">
                <h3><?php echo esc_html__('État du système', 'ai-connector'); ?></h3>
                <div class="ai-connector-stat-value"><?php echo esc_html(wp_plugin_ai_connector_get_system_status()); ?></div>
            </div>
        </div>
    </div>

    <!-- Fonctionnalités principales -->
    <div class="ai-connector-grid"><?php
                                wp_plugin_ai_connector_render_feature_card(
                                    __('Connecteurs IA', 'ai-connector'),
                                    'admin-generic',
                                    __('Configurez vos cles API et choisissez le modele actif du connecteur.', 'ai-connector'),
                                    __('Configurer', 'ai-connector'),
                                    admin_url('admin.php?page=ai-connector-connectors')
                                );

                                wp_plugin_ai_connector_render_feature_card(
                                    __('Testeur IA', 'ai-connector'),
                                    'portfolio',
                                    __('Validez rapidement un prompt sur le modele actif du connecteur.', 'ai-connector'),
                                    __('Ouvrir le testeur', 'ai-connector'),
                                    admin_url('admin.php?page=ai-connector-tester')
                                );

                                wp_plugin_ai_connector_render_feature_card(
                                    __('Diagnostic IA', 'ai-connector'),
                                    'chart-bar',
                                    __('Testez chaque fournisseur et verifiez votre configuration avant consommation par un plugin metier.', 'ai-connector'),
                                    __('Lancer le diagnostic', 'ai-connector'),
                                    admin_url('admin.php?page=ai-connector-diagnostic')
                                );

                                wp_plugin_ai_connector_render_feature_card(
                                    __('API transverse', 'ai-connector'),
                                    'dashboard',
                                    __('Exposez une facade PHP unique pour AI Redactor, chatbot, support ou d\'autres plugins metier.', 'ai-connector'),
                                    __('Voir les connecteurs', 'ai-connector'),
                                    admin_url('admin.php?page=ai-connector-connectors')
                                );

                                wp_plugin_ai_connector_render_feature_card(
                                    __('Compatibilite', 'ai-connector'),
                                    'book-alt',
                                    __('Le connecteur conserve une compatibilite transitoire avec l\'ancien nom AI Connector et les anciennes options.', 'ai-connector'),
                                    __('Verifier la configuration', 'ai-connector'),
                                    admin_url('admin.php?page=ai-connector-connectors')
                                );

                                wp_plugin_ai_connector_render_feature_card(
                                    __('Support', 'ai-connector'),
                                    'sos',
                                    __('Le diagnostic integre suffit a isoler la plupart des erreurs de provider, modele ou cle API.', 'ai-connector'),
                                    __('Ouvrir le diagnostic', 'ai-connector'),
                                    admin_url('admin.php?page=ai-connector-diagnostic'),
                                    false
                                );
                                ?></div>

    <!-- Informations de version -->
    <div class="ai-connector-version">
        <?php echo sprintf(esc_html__('Version %s', 'ai-connector'), WP_PLUGIN_AI_CONNECTOR_VERSION); ?>
    </div>
</div>
