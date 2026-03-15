<?php

/**
 * Formulaire d'administration pour la configuration des connecteurs IA
 *
 * Ce fichier contient une classe responsable de générer le formulaire HTML permettant
 * de configurer les connecteurs IA (fournisseurs, clés API, modèles actifs). Il affiche
 * les champs nécessaires et gère leur affichage dynamique en fonction des fournisseurs disponibles.
 *
 * @package WP_Plugin_AI_Connector
 * @subpackage Services\AI\UI
 *
 * @depends WordPress Options API
 * @depends wp_nonce_field
 * @depends current_user_can
 *
 * @css /assets/css/admin-models-form.css
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
class AI_Connector_Admin_Form
{
    /**
     * Retourne le premier fournisseur disponible.
     *
     * @param array $providers_config Configuration des fournisseurs.
     * @return string
     */
    private static function get_default_provider_id($providers_config)
    {
        foreach ($providers_config as $provider_id => $provider_data) {
            return (string) $provider_id;
        }

        return '';
    }

    /**
     * Retourne l'URL de l'icone SVG d'un fournisseur.
     *
     * Les fichiers historiques ne suivent pas tous la meme convention de nommage.
     *
     * @param string $provider_id Identifiant du fournisseur.
     * @return string
     */
    private static function get_provider_icon_url($provider_id)
    {
        $provider_id = sanitize_key((string) $provider_id);
        $base_path = trailingslashit(WP_PLUGIN_AI_CONNECTOR_PATH) . 'assets/svg/';
        $base_url = trailingslashit(WP_PLUGIN_AI_CONNECTOR_URL) . 'assets/svg/';

        $icon_map = [
            'openai' => 'OpenAI.svg',
            'anthropic' => 'Anthropic.svg',
            'mistral' => 'mistral.svg',
            'gemini' => 'gemini.svg',
            'grok' => 'Grok.svg',
        ];

        $candidates = [];

        if (isset($icon_map[$provider_id])) {
            $candidates[] = $icon_map[$provider_id];
        }

        $candidates[] = ucfirst($provider_id) . '.svg';
        $candidates[] = strtolower($provider_id) . '.svg';

        foreach (array_unique($candidates) as $filename) {
            if (file_exists($base_path . $filename)) {
                return $base_url . $filename;
            }
        }

        return '';
    }

    /**
     * Retourne le markup de l'icone d'un fournisseur.
     *
     * @param string $provider_id    Identifiant du fournisseur.
     * @param string $provider_name  Nom lisible du fournisseur.
     * @param string $image_class    Classe CSS appliquee a l'icone.
     * @return string
     */
    private static function get_provider_icon_markup($provider_id, $provider_name, $image_class)
    {
        $svg_path = self::get_provider_icon_url($provider_id);

        if ('' !== $svg_path) {
            return sprintf(
                '<img src="%1$s" alt="%2$s" class="%3$s" />',
                esc_url($svg_path),
                esc_attr($provider_name),
                esc_attr($image_class)
            );
        }

        return sprintf(
            '<span class="%1$s ai-connector-provider-fallback dashicons dashicons-admin-links" aria-hidden="true"></span>',
            esc_attr($image_class)
        );
    }

    /**
     * Affiche le formulaire d'administration
     *
     * @param array  $providers_config Configuration des fournisseurs
     * @param string $active_model     Modèle actif (format 'provider:model')
     */
    public static function render_form($providers_config, $active_model)
    {
        if (empty($providers_config) || !is_array($providers_config)) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Aucun fournisseur IA n\'est disponible pour le moment.', 'ai-connector') . '</p></div>';
            return;
        }

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

        if ('' === $active_provider || !isset($providers_config[$active_provider])) {
            $active_provider = self::get_default_provider_id($providers_config);
        }

        // Définir des modèles populaires pour l'affichage des badges (exemple)
        $popular_models = [
            'openai:gpt-5.1',
            'anthropic:claude-sonnet-4-20250514',
            'mistral:mistral-medium-2508',
            'gemini:gemini-2.5-pro',
            'grok:grok-4-1-fast-non-reasoning',
        ];

        // Ajout de jQuery en dépendance explicite
        wp_enqueue_script('jquery');

        // Ajout de script inline pour activer les onglets et la bascule de visibilité des clés API
        add_action('admin_footer', function () {
?>
            <script type="text/javascript">
                // Définir l'URL AJAX de WordPress si elle n'est pas déjà définie
                var ajaxurl = ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>';

                jQuery(document).ready(function($) {
                    console.log("AI Connector Tabs Ready - Inline version");

                    // Gestion du clic sur les onglets
                    $(".ai-connector-tabs-nav a").on("click", function(e) {
                        e.preventDefault();

                        // Récupérer l'ID cible
                        var target = $(this).attr("href");
                        console.log("Tab Clicked: " + target);

                        // Supprimer la classe active de tous les onglets et contenus
                        $(".ai-connector-tabs-nav a").removeClass("active");
                        $(".ai-connector-tab-content").removeClass("active");

                        // Ajouter la classe active à l'onglet cliqué et au contenu correspondant
                        $(this).addClass("active");
                        $(target).addClass("active");
                    });

                    // Log pour vérifier le nombre d'onglets
                    console.log("Tabs found: " + $(".ai-connector-tabs-nav a").length);
                    console.log("Content panels found: " + $(".ai-connector-tab-content").length);

                    // Bascule de visibilité des clés API
                    $(".ai-connector-toggle-api-key").on("click", function() {
                        var inputField = $(this).prev("input");
                        var icon = $(this).find(".dashicons");

                        if (inputField.attr("type") === "password") {
                            inputField.attr("type", "text");
                            icon.removeClass("dashicons-visibility").addClass("dashicons-hidden");
                        } else {
                            inputField.attr("type", "password");
                            icon.removeClass("dashicons-hidden").addClass("dashicons-visibility");
                        }
                    });

                    // Fonction de test de connexion API
                    $(".ai-connector-test-connection").on("click", function() {
                        var button = $(this);
                        var providerId = button.data("provider");
                        var statusElement = $("#status-" + providerId);

                        // Récupération du champ de saisie par son identifiant complet
                        var tabContent = button.closest(".ai-connector-tab-content");
                        var apiKeyInput = tabContent.find("input[type='password'], input[type='text']").first();

                        console.log("Provider ID:", providerId);
                        console.log("API Key Input found:", apiKeyInput.length > 0);
                        console.log("API Key Input ID:", apiKeyInput.attr('id'));

                        var apiKey = apiKeyInput.val();

                        if (!apiKey) {
                            statusElement.html('<span class="ai-connector-status-error">Veuillez entrer une clé API</span>');
                            return;
                        }

                        // Désactiver le bouton et afficher le chargement
                        button.attr("disabled", "disabled");
                        button.find(".dashicons").addClass("ai-connector-spinning");
                        statusElement.html('<span class="ai-connector-status-loading">Test en cours...</span>');

                        // Préparer les données pour l'appel AJAX
                        var data = {
                            action: 'ai_connector_test_connection',
                            nonce: '<?php echo wp_create_nonce("ai_connector_test_connection"); ?>',
                            provider: providerId
                        };

                        // Effectuer l'appel AJAX
                        $.post(ajaxurl, data, function(response) {
                            console.log("Réponse du test de connexion:", response);

                            // Réactiver le bouton et arrêter l'animation
                            button.removeAttr("disabled");
                            button.find(".dashicons").removeClass("ai-connector-spinning");

                            if (response.success) {
                                statusElement.html('<span class="ai-connector-status-success">Connexion réussie! ' + response.data.message + '</span>');
                            } else {
                                statusElement.html('<span class="ai-connector-status-error">Échec de la connexion: ' + response.data.message + '</span>');
                            }
                        }).fail(function() {
                            // En cas d'erreur AJAX
                            button.removeAttr("disabled");
                            button.find(".dashicons").removeClass("ai-connector-spinning");
                            statusElement.html('<span class="ai-connector-status-error">Erreur de connexion au serveur</span>');
                        });
                    });
                });
            </script>
            <style>
                .ai-connector-vertical-tabs {
                    display: flex;
                    flex-direction: row;
                    width: 100%;
                    min-height: 560px;
                    margin: 20px 0;
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    border-radius: 6px;
                    overflow: hidden;
                }

                .ai-connector-tabs-sidebar {
                    width: 300px;
                    flex-shrink: 0;
                    background: #f8f9fa;
                    border-right: 1px solid #e2e4e7;
                }

                .ai-connector-tabs-nav {
                    margin: 0;
                    padding: 0;
                    list-style: none;
                }

                .ai-connector-tabs-nav li {
                    margin: 0;
                }

                .ai-connector-tabs-nav a {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 16px 18px;
                    border-bottom: 1px solid #e2e4e7;
                    color: #50575e;
                    text-decoration: none;
                }

                .ai-connector-tabs-nav a.active {
                    background: #fff;
                    border-left: 3px solid #2271b1;
                    color: #2271b1;
                    font-weight: 600;
                }

                .ai-connector-tab-icon,
                .ai-connector-provider-icon {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 30px;
                    height: 30px;
                    flex-shrink: 0;
                }

                .ai-connector-provider-svg,
                .ai-connector-header-svg {
                    display: block;
                    width: 100%;
                    height: 100%;
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: contain;
                }

                .ai-connector-provider-fallback {
                    display: inline-flex !important;
                    align-items: center;
                    justify-content: center;
                    font-size: 20px;
                    line-height: 1;
                    color: #50575e;
                }

                .ai-connector-tab-text {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px;
                    flex: 1;
                }

                .ai-connector-tabs-content-wrapper {
                    flex: 1;
                    min-width: 0;
                    background: #fff;
                }

                .ai-connector-tabs-content {
                    padding: 26px;
                }

                .ai-connector-tab-content {
                    display: none;
                }

                .ai-connector-tab-content.active {
                    display: block;
                }

                @media screen and (max-width: 960px) {
                    .ai-connector-vertical-tabs {
                        flex-direction: column;
                    }

                    .ai-connector-tabs-sidebar {
                        width: 100%;
                        border-right: 0;
                        border-bottom: 1px solid #e2e4e7;
                    }
                }

                .ai-connector-spinning {
                    animation: ai-connector-spin 2s linear infinite;
                }

                @keyframes ai-connector-spin {
                    0% {
                        transform: rotate(0deg);
                    }

                    100% {
                        transform: rotate(360deg);
                    }
                }

                .ai-connector-status-loading {
                    color: #666;
                    font-style: italic;
                }

                .ai-connector-status-success {
                    color: #46b450;
                    font-weight: bold;
                }

                .ai-connector-status-error {
                    color: #dc3232;
                    font-weight: bold;
                }
            </style>
        <?php
        });

        ?>
        <form method="post" action="">
            <?php wp_nonce_field('ai_connector_save_connectors', 'ai_connector_connector_nonce'); ?>

            <div class="ai-connector-vertical-tabs">
                <!-- En-têtes des onglets (sidebar à gauche) -->
                <div class="ai-connector-tabs-sidebar">
                    <ul class="ai-connector-tabs-nav">
                        <?php foreach ($providers_config as $provider_id => $provider_data) :
                            $is_provider_active = ($provider_id === $active_provider);
                        ?>
                            <li>
                                <a href="#provider-<?php echo esc_attr($provider_id); ?>"
                                    class="<?php echo $is_provider_active ? 'active' : ''; ?>">
                                    <span class="ai-connector-tab-icon">
                                        <?php echo self::get_provider_icon_markup($provider_id, $provider_data['name'], 'ai-connector-provider-svg'); ?>
                                    </span>
                                    <span class="ai-connector-tab-text">
                                        <?php echo esc_html($provider_data['name']); ?>
                                        <?php if ($is_provider_active) : ?>
                                            <span class="ai-connector-tab-badge"><?php echo esc_html__('Actif', 'wp-plugin-ai-connector'); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Contenu des onglets (contenu à droite) -->
                <div class="ai-connector-tabs-content-wrapper">
                    <div class="ai-connector-tabs-content">
                        <?php foreach ($providers_config as $provider_id => $provider_data) :
                            // Récupérer la clé API
                            $api_key_option = $provider_data['api_key_option'];
                            $api_key = get_option($api_key_option, '');
                            $is_active_provider = ($provider_id === $active_provider);
                        ?>
                            <div id="provider-<?php echo esc_attr($provider_id); ?>"
                                class="ai-connector-tab-content <?php echo $is_active_provider ? 'active' : ''; ?>">

                                <h2>
                                    <span class="ai-connector-provider-icon">
                                        <?php echo self::get_provider_icon_markup($provider_id, $provider_data['name'], 'ai-connector-header-svg'); ?>
                                    </span>
                                    <?php echo esc_html($provider_data['name']); ?>
                                    <span class="ai-connector-provider-badge <?php echo $is_active_provider ? 'active' : 'inactive'; ?>">
                                        <?php echo $is_active_provider
                                            ? esc_html__('Actif', 'wp-plugin-ai-connector')
                                            : esc_html__('Inactif', 'wp-plugin-ai-connector'); ?>
                                    </span>
                                </h2>

                                <!-- Clé API -->
                                <div class="ai-connector-form-row">
                                    <label for="<?php echo esc_attr($api_key_option); ?>">
                                        <?php echo esc_html__('Clé API', 'wp-plugin-ai-connector'); ?>
                                    </label>
                                    <p class="description">
                                        <?php echo sprintf(
                                            esc_html__('Entrez votre clé API %s pour utiliser ce service.', 'wp-plugin-ai-connector'),
                                            esc_html($provider_data['name'])
                                        ); ?>
                                    </p>
                                    <div class="ai-connector-api-key-input">
                                        <input type="password"
                                            id="<?php echo esc_attr($api_key_option); ?>"
                                            name="<?php echo esc_attr($api_key_option); ?>"
                                            value="<?php echo esc_attr($api_key); ?>"
                                            class="regular-text"
                                            placeholder="<?php echo esc_attr__('Coller votre clé API ici', 'wp-plugin-ai-connector'); ?>" />
                                        <button type="button" class="button ai-connector-toggle-api-key">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Modèles disponibles (avec cartes) -->
                                <div class="ai-connector-form-row">
                                    <h3><?php echo esc_html__('Sélection du modèle actif', 'wp-plugin-ai-connector'); ?></h3>
                                    <p class="description">
                                        <?php echo esc_html__('Choisissez le modèle d\'IA que vous souhaitez utiliser pour générer du contenu.', 'wp-plugin-ai-connector'); ?>
                                    </p>

                                    <?php if (empty($provider_data['models'])) : ?>
                                        <div class="ai-connector-no-models">
                                            <p><?php echo esc_html__('Aucun modèle disponible pour ce fournisseur.', 'wp-plugin-ai-connector'); ?></p>
                                        </div>
                                    <?php else : ?>
                                        <div class="ai-connector-models-list">
                                            <?php foreach ($provider_data['models'] as $model_id => $model_data) :
                                                $combined_id = $provider_id . ':' . $model_id;
                                                $is_active = ($combined_id === $active_model);
                                                $is_popular = in_array($combined_id, $popular_models);
                                            ?>
                                                <div class="ai-connector-model-option <?php echo $is_active ? 'selected' : ''; ?>">
                                                    <label>
                                                        <div class="ai-connector-model-header">
                                                            <input type="radio"
                                                                class="ai-connector-model-radio"
                                                                name="ai_connector_active_model"
                                                                value="<?php echo esc_attr($combined_id); ?>"
                                                                <?php checked($is_active); ?>>
                                                            <span class="ai-connector-model-name"><?php echo esc_html($model_data['label']); ?></span>

                                                            <?php if ($is_active) : ?>
                                                                <span class="ai-connector-model-badge active"><?php echo esc_html__('Actif', 'wp-plugin-ai-connector'); ?></span>
                                                            <?php elseif ($is_popular) : ?>
                                                                <span class="ai-connector-model-badge popular"><?php echo esc_html__('Populaire', 'wp-plugin-ai-connector'); ?></span>
                                                            <?php endif; ?>
                                                        </div>

                                                        <?php if (!empty($model_data['description'])) : ?>
                                                            <div class="ai-connector-model-description">
                                                                <?php echo esc_html($model_data['description']); ?>
                                                            </div>
                                                        <?php endif; ?>

                                                        <div class="ai-connector-model-details">
                                                            <div class="ai-connector-model-detail-item">
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
                                <div class="ai-connector-form-row">
                                    <button type="button"
                                        class="ai-connector-test-connection"
                                        data-provider="<?php echo esc_attr($provider_id); ?>">
                                        <span class="dashicons dashicons-update"></span>
                                        <?php echo esc_html__('Tester la connexion', 'wp-plugin-ai-connector'); ?>
                                    </button>
                                    <span class="ai-connector-connection-status" id="status-<?php echo esc_attr($provider_id); ?>"></span>
                                </div>

                                <div class="ai-connector-provider-info">
                                    <h3><?php echo esc_html__('Informations sur les modèles disponibles', 'wp-plugin-ai-connector'); ?></h3>
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead>
                                            <tr>
                                                <th><?php echo esc_html__('Modèle', 'wp-plugin-ai-connector'); ?></th>
                                                <th><?php echo esc_html__('Coût', 'wp-plugin-ai-connector'); ?></th>
                                                <th><?php echo esc_html__('Tokens max', 'wp-plugin-ai-connector'); ?></th>
                                                <th><?php echo esc_html__('Description', 'wp-plugin-ai-connector'); ?></th>
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
                    name="ai_connector_connector_submit"
                    id="submit"
                    class="button button-primary"
                    value="<?php echo esc_attr__('Enregistrer les modifications', 'wp-plugin-ai-connector'); ?>">
            </p>
        </form>
<?php
    }
}
