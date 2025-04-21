<?php

/**
 * Page de tableau de bord pour le plugin AI Agent
 * 
 * Affiche une interface moderne et attrayante pour le tableau de bord principal
 * du plugin AI Agent avec des statistiques, des liens rapides et des informations utiles.
 * 
 * @package WP_Plugin_AI_Agent
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Vérifier que l'utilisateur a les permissions requises
if (!current_user_can('manage_options')) {
    wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.', 'ai-agent'));
}

// Charger les icônes de Dashicons
wp_enqueue_style('dashicons');

/**
 * Récupère et retourne le nombre de modèles AI disponibles
 */
function wp_plugin_ai_agent_get_available_models()
{
    // À implémenter selon votre structure de données
    // Pour l'instant, on retourne une valeur factice
    return 5;
}

/**
 * Récupère et retourne le nombre de requêtes AI effectuées
 */
function wp_plugin_ai_agent_get_request_count()
{
    // À implémenter selon votre structure de données
    // Pour l'instant, on retourne une valeur factice
    return 120;
}

/**
 * Récupère et retourne l'état de santé du système
 */
function wp_plugin_ai_agent_get_system_status()
{
    // À implémenter selon votre structure de données
    // Pour l'instant, on retourne une valeur factice
    return 'Optimal';
}

/**
 * Génère une carte de fonctionnalité pour le tableau de bord
 */
function wp_plugin_ai_agent_render_feature_card($title, $icon, $description, $button_text, $button_url, $is_primary = true)
{
?>
    <div class="ai-agent-card">
        <h2><span class="dashicons dashicons-<?php echo esc_attr($icon); ?>"></span> <?php echo esc_html($title); ?></h2>
        <p><?php echo esc_html($description); ?></p>
        <div class="ai-agent-card-actions">
            <a href="<?php echo esc_url($button_url); ?>" class="button <?php echo $is_primary ? '' : 'button-secondary'; ?>">
                <?php echo esc_html($button_text); ?>
            </a>
        </div>
    </div>
<?php
}
?>

<div class="wrap ai-agent-dashboard">
    <div class="ai-agent-dashboard-header">
        <h1><?php echo esc_html__('Tableau de bord AI Agent', 'ai-agent'); ?></h1>
        <p><?php echo esc_html__('Bienvenue dans votre tableau de bord AI Agent. Gérez vos intégrations d\'intelligence artificielle, analysez les performances et configurez vos modèles d\'IA préférés.', 'ai-agent'); ?></p>
    </div>

    <!-- Statistiques -->
    <div class="ai-agent-stats-row">
        <div class="ai-agent-stat-card">
            <h3><?php echo esc_html__('Modèles disponibles', 'ai-agent'); ?></h3>
            <div class="ai-agent-stat-value"><?php echo esc_html(wp_plugin_ai_agent_get_available_models()); ?></div>
        </div>

        <div class="ai-agent-stat-card">
            <h3><?php echo esc_html__('Requêtes AI effectuées', 'ai-agent'); ?></h3>
            <div class="ai-agent-stat-value"><?php echo esc_html(wp_plugin_ai_agent_get_request_count()); ?></div>
        </div>

        <div class="ai-agent-stat-card">
            <h3><?php echo esc_html__('État du système', 'ai-agent'); ?></h3>
            <div class="ai-agent-stat-value"><?php echo esc_html(wp_plugin_ai_agent_get_system_status()); ?></div>
        </div>
    </div>

    <!-- Fonctionnalités principales -->
    <div class="ai-agent-dashboard-grid">
        <?php
        wp_plugin_ai_agent_render_feature_card(
            __('Configuration de l\'API', 'ai-agent'),
            'admin-generic',
            __('Configurez vos clés API pour les différents fournisseurs d\'IA et personnalisez vos paramètres d\'accès.', 'ai-agent'),
            __('Configurer', 'ai-agent'),
            admin_url('admin.php?page=ai-agent-settings')
        );

        wp_plugin_ai_agent_render_feature_card(
            __('Sélection des modèles', 'ai-agent'),
            'portfolio',
            __('Choisissez parmi une variété de modèles d\'IA disponibles et configurez leur utilisation sur votre site.', 'ai-agent'),
            __('Gérer les modèles', 'ai-agent'),
            admin_url('admin.php?page=ai-agent-models')
        );

        wp_plugin_ai_agent_render_feature_card(
            __('Statistiques et rapports', 'ai-agent'),
            'chart-bar',
            __('Consultez des statistiques détaillées sur l\'utilisation de l\'IA et analysez les performances de vos intégrations.', 'ai-agent'),
            __('Voir les statistiques', 'ai-agent'),
            admin_url('admin.php?page=ai-agent-stats')
        );

        wp_plugin_ai_agent_render_feature_card(
            __('Diagnostic du système', 'ai-agent'),
            'dashboard',
            __('Effectuez des tests de diagnostic pour vous assurer que votre système est correctement configuré pour l\'IA.', 'ai-agent'),
            __('Exécuter un diagnostic', 'ai-agent'),
            admin_url('admin.php?page=ai-agent-diagnostic')
        );

        wp_plugin_ai_agent_render_feature_card(
            __('Documentation', 'ai-agent'),
            'book-alt',
            __('Consultez la documentation complète pour tirer le meilleur parti des fonctionnalités de l\'IA.', 'ai-agent'),
            __('Lire la documentation', 'ai-agent'),
            admin_url('admin.php?page=ai-agent-docs')
        );

        wp_plugin_ai_agent_render_feature_card(
            __('Support', 'ai-agent'),
            'sos',
            __('Besoin d\'aide ? Accédez à notre support pour résoudre rapidement vos problèmes.', 'ai-agent'),
            __('Contacter le support', 'ai-agent'),
            admin_url('admin.php?page=ai-agent-support'),
            false
        );
        ?>
    </div>

    <!-- Informations de version -->
    <div class="ai-agent-version">
        <?php echo sprintf(esc_html__('Version %s', 'ai-agent'), WP_PLUGIN_AI_AGENT_VERSION); ?>
    </div>
</div>