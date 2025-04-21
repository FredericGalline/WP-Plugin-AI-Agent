<?php

/**
 * Formulaire d'administration pour la configuration des connecteurs IA
 *
 * Ce fichier contient une classe responsable de générer le formulaire HTML permettant
 * de configurer les connecteurs IA (fournisseurs, clés API, modèles actifs). Il affiche
 * les champs nécessaires et gère leur affichage dynamique en fonction des fournisseurs disponibles.
 *
 * @package WP_Plugin_AI_Agent
 * @subpackage Services\AI\UI
 *
 * @depends WordPress Options API
 * @depends wp_nonce_field
 * @depends current_user_can
 *
 * @css /assets/css/ai-admin-form.css
 *
 * @js /assets/js/ai-admin.js
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour le rendu du formulaire d'administration des connecteurs IA
 */
class AI_Agent_Admin_Form
{

    /**
     * Affiche le formulaire d'administration
     *
     * @param array  $providers_config Configuration des fournisseurs
     * @param string $active_model     Modèle actif (format 'provider:model')
     */
    public static function render_form($providers_config, $active_model)
    {
        // Extraire le fournisseur et le modèle actif
        $active_provider = '';
        $active_model_id = '';

        if (!empty($active_model)) {
            $parts = explode(':', $active_model);
            if (count($parts) === 2) {
                $active_provider = $parts[0];
                $active_model_id = $parts[1];
            }
        }

        // Définir des modèles populaires pour l'affichage des badges (exemple)
        $popular_models = [
            'openai:gpt-4-turbo',
            'anthropic:claude-3-opus',
            'mistral:mistral-large',
            'gemini:gemini-pro'
        ];

        // Enqueue des styles CSS
        wp_enqueue_style(
            'ai-agent-admin-form',
            WP_PLUGIN_AI_AGENT_URL . 'assets/css/ai-admin-form.css',
            array(),
            WP_PLUGIN_AI_AGENT_VERSION
        );

        // Ajout de jQuery en dépendance explicite
        wp_enqueue_script('jquery');

        // Ajout de script inline pour activer les onglets
        add_action('admin_footer', function () {
?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    console.log("AI Agent Tabs Ready - Inline version");

                    // Gestion du clic sur les onglets
                    $(".ai-agent-tabs-nav a").on("click", function(e) {
                        e.preventDefault();

                        // Récupérer l'ID cible
                        var target = $(this).attr("href");
                        console.log("Tab Clicked: " + target);

                        // Supprimer la classe active de tous les onglets et contenus
                        $(".ai-agent-tabs-nav a").removeClass("active");
                        $(".ai-agent-tab-content").removeClass("active");

                        // Ajouter la classe active à l'onglet cliqué et au contenu correspondant
                        $(this).addClass("active");
                        $(target).addClass("active");
                    });

                    // Log pour vérifier le nombre d'onglets
                    console.log("Tabs found: " + $(".ai-agent-tabs-nav a").length);
                    console.log("Content panels found: " + $(".ai-agent-tab-content").length);
                });
            </script>
        <?php
        });

        ?>
        <form method="post" action="">
            <?php wp_nonce_field('ai_agent_save_connectors', 'ai_agent_connector_nonce'); ?>

            <div class="ai-agent-vertical-tabs">
                <!-- En-têtes des onglets (sidebar à gauche) -->
                <div class="ai-agent-tabs-sidebar">
                    <ul class="ai-agent-tabs-nav">
                        <?php foreach ($providers_config as $provider_id => $provider_data) :
                            $is_provider_active = ($provider_id === $active_provider);

                            // Chemin vers l'icône SVG (format du nom de fichier: [ProviderName].svg)
                            $provider_name = ucfirst($provider_id); // Première lettre en majuscule
                            $svg_path = WP_PLUGIN_AI_AGENT_URL . 'assets/svg/' . $provider_name . '.svg';
                        ?>
                            <li>
                                <a href="#provider-<?php echo esc_attr($provider_id); ?>"
                                    class="<?php echo $is_provider_active ? 'active' : ''; ?>">
                                    <span class="ai-agent-tab-icon">
                                        <img src="<?php echo esc_url($svg_path); ?>"
                                            alt="<?php echo esc_attr($provider_data['name']); ?>"
                                            class="ai-agent-provider-svg">
                                    </span>
                                    <span class="ai-agent-tab-text">
                                        <?php echo esc_html($provider_data['name']); ?>
                                        <?php if ($is_provider_active) : ?>
                                            <span class="ai-agent-tab-badge"><?php echo esc_html__('Actif', 'wp-plugin-ai-agent'); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Contenu des onglets (contenu à droite) -->
                <div class="ai-agent-tabs-content-wrapper">
                    <div class="ai-agent-tabs-content">
                        <?php foreach ($providers_config as $provider_id => $provider_data) :
                            // Récupérer la clé API
                            $api_key_option = $provider_data['api_key_option'];
                            $api_key = get_option($api_key_option, '');
                            $is_active_provider = ($provider_id === $active_provider);
                            $provider_name = ucfirst($provider_id);
                            $svg_path = WP_PLUGIN_AI_AGENT_URL . 'assets/svg/' . $provider_name . '.svg';
                        ?>
                            <div id="provider-<?php echo esc_attr($provider_id); ?>"
                                class="ai-agent-tab-content <?php echo $is_active_provider ? 'active' : ''; ?>">

                                <h2>
                                    <span class="ai-agent-provider-icon">
                                        <img src="<?php echo esc_url($svg_path); ?>"
                                            alt="<?php echo esc_attr($provider_data['name']); ?>"
                                            class="ai-agent-header-svg">
                                    </span>
                                    <?php echo esc_html($provider_data['name']); ?>
                                    <span class="ai-agent-provider-badge <?php echo $is_active_provider ? 'active' : 'inactive'; ?>">
                                        <?php echo $is_active_provider
                                            ? esc_html__('Actif', 'wp-plugin-ai-agent')
                                            : esc_html__('Inactif', 'wp-plugin-ai-agent'); ?>
                                    </span>
                                </h2>

                                <!-- Clé API -->
                                <div class="ai-agent-form-row">
                                    <label for="<?php echo esc_attr($api_key_option); ?>">
                                        <?php echo esc_html__('Clé API', 'wp-plugin-ai-agent'); ?>
                                    </label>
                                    <p class="description">
                                        <?php echo sprintf(
                                            esc_html__('Entrez votre clé API %s pour utiliser ce service.', 'wp-plugin-ai-agent'),
                                            esc_html($provider_data['name'])
                                        ); ?>
                                    </p>
                                    <div class="ai-agent-api-key-input">
                                        <input type="password"
                                            id="<?php echo esc_attr($api_key_option); ?>"
                                            name="<?php echo esc_attr($api_key_option); ?>"
                                            value="<?php echo esc_attr($api_key); ?>"
                                            class="regular-text"
                                            placeholder="<?php echo esc_attr__('Coller votre clé API ici', 'wp-plugin-ai-agent'); ?>" />
                                        <button type="button" class="button ai-agent-toggle-api-key">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Modèles disponibles (avec cartes) -->
                                <div class="ai-agent-form-row">
                                    <h3><?php echo esc_html__('Sélection du modèle actif', 'wp-plugin-ai-agent'); ?></h3>
                                    <p class="description">
                                        <?php echo esc_html__('Choisissez le modèle d\'IA que vous souhaitez utiliser pour générer du contenu.', 'wp-plugin-ai-agent'); ?>
                                    </p>

                                    <?php if (empty($provider_data['models'])) : ?>
                                        <div class="ai-agent-no-models">
                                            <p><?php echo esc_html__('Aucun modèle disponible pour ce fournisseur.', 'wp-plugin-ai-agent'); ?></p>
                                        </div>
                                    <?php else : ?>
                                        <div class="ai-agent-models-list">
                                            <?php foreach ($provider_data['models'] as $model_id => $model_data) :
                                                $combined_id = $provider_id . ':' . $model_id;
                                                $is_active = ($combined_id === $active_model);
                                                $is_popular = in_array($combined_id, $popular_models);
                                            ?>
                                                <div class="ai-agent-model-option <?php echo $is_active ? 'selected' : ''; ?>">
                                                    <label>
                                                        <div class="ai-agent-model-header">
                                                            <input type="radio"
                                                                class="ai-agent-model-radio"
                                                                name="ai_agent_active_model"
                                                                value="<?php echo esc_attr($combined_id); ?>"
                                                                <?php checked($is_active); ?>>
                                                            <span class="ai-agent-model-name"><?php echo esc_html($model_data['label']); ?></span>

                                                            <?php if ($is_active) : ?>
                                                                <span class="ai-agent-model-badge active"><?php echo esc_html__('Actif', 'wp-plugin-ai-agent'); ?></span>
                                                            <?php elseif ($is_popular) : ?>
                                                                <span class="ai-agent-model-badge popular"><?php echo esc_html__('Populaire', 'wp-plugin-ai-agent'); ?></span>
                                                            <?php endif; ?>
                                                        </div>

                                                        <?php if (!empty($model_data['description'])) : ?>
                                                            <div class="ai-agent-model-description">
                                                                <?php echo esc_html($model_data['description']); ?>
                                                            </div>
                                                        <?php endif; ?>

                                                        <div class="ai-agent-model-details">
                                                            <div class="ai-agent-model-detail-item">
                                                                <span><?php echo esc_html($model_data['cost']); ?></span>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Bouton de test -->
                                <div class="ai-agent-form-row">
                                    <button type="button"
                                        class="ai-agent-test-connection"
                                        data-provider="<?php echo esc_attr($provider_id); ?>">
                                        <span class="dashicons dashicons-update"></span>
                                        <?php echo esc_html__('Tester la connexion', 'wp-plugin-ai-agent'); ?>
                                    </button>
                                    <span class="ai-agent-connection-status" id="status-<?php echo esc_attr($provider_id); ?>"></span>
                                </div>

                                <div class="ai-agent-provider-info">
                                    <h3><?php echo esc_html__('Informations sur les modèles disponibles', 'wp-plugin-ai-agent'); ?></h3>
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead>
                                            <tr>
                                                <th><?php echo esc_html__('Modèle', 'wp-plugin-ai-agent'); ?></th>
                                                <th><?php echo esc_html__('Coût', 'wp-plugin-ai-agent'); ?></th>
                                                <th><?php echo esc_html__('Tokens max', 'wp-plugin-ai-agent'); ?></th>
                                                <th><?php echo esc_html__('Description', 'wp-plugin-ai-agent'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($provider_data['models'] as $model_id => $model_data) : ?>
                                                <tr>
                                                    <td><?php echo esc_html($model_data['label']); ?></td>
                                                    <td><?php echo esc_html($model_data['cost']); ?></td>
                                                    <td><?php echo esc_html(number_format($model_data['max_tokens'])); ?></td>
                                                    <td><?php echo !empty($model_data['description']) ? esc_html($model_data['description']) : ''; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <p class="submit">
                <input type="submit"
                    name="ai_agent_connector_submit"
                    id="submit"
                    class="button button-primary"
                    value="<?php echo esc_attr__('Enregistrer les modifications', 'wp-plugin-ai-agent'); ?>">
            </p>
        </form>
<?php
    }
}
