<?php

/**
 * Formulaire d'administration pour la configuration des connecteurs IA
 *
 * Ce fichier contient une classe responsable de générer le formulaire HTML permettant
 * de configurer les connecteurs IA (fournisseurs, clés API, modèles actifs). Il affiche
 * les champs nécessaires et gère leur affichage dynamique en fonction des fournisseurs disponibles.
 *
 * @package AI_Redactor
 * @subpackage Services\AI\UI
 *
 * @depends WordPress Options API
 * @depends wp_nonce_field
 * @depends current_user_can
 *
 * @css /assets/css/admin-form.css
 * @css /assets/css/admin-connectors.css
 *
 * @js /assets/js/admin-form.js
 * @js /assets/js/admin-connectors.js
 *
 * @ai Ce fichier est exclusivement dédié à l'affichage du formulaire d'administration pour configurer
 * les connecteurs IA. Il ne contient aucune logique métier liée à la génération de contenu par IA, ni
 * d'algorithmes d'IA. Sa responsabilité est strictement limitée à : (1) afficher les champs de configuration
 * des connecteurs, (2) permettre la sélection des modèles actifs, et (3) fournir des outils d'interaction
 * utilisateur comme les tests de connexion. Toute logique de traitement ou de validation des données est
 * déléguée à d'autres classes.
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour le rendu du formulaire d'administration des connecteurs IA
 */
class AI_Redactor_Admin_Form
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

?>
        <form method="post" action="">
            <?php wp_nonce_field('ai_redactor_save_connectors', 'ai_redactor_connector_nonce'); ?>

            <div class="ai-redactor-provider-tabs ai-redactor-vertical-tabs">
                <!-- En-têtes des onglets (maintenant à gauche) -->
                <div class="ai-redactor-tabs-sidebar">
                    <ul class="ai-redactor-tabs-nav">
                        <?php foreach ($providers_config as $provider_id => $provider_data) :
                            $is_provider_active = ($provider_id === $active_provider);

                            // Chemin vers l'icône SVG (format du nom de fichier: [ProviderName].svg)
                            $provider_name = ucfirst($provider_id); // Première lettre en majuscule
                            $svg_path = WP_PLUGIN_AI_AGENT_URL . 'assets/svg/' . $provider_name . '.svg';
                        ?>
                            <li>
                                <a href="#provider-<?php echo esc_attr($provider_id); ?>"
                                    class="<?php echo $is_provider_active ? 'active' : ''; ?>">
                                    <span class="ai-redactor-tab-icon">
                                        <img src="<?php echo esc_url($svg_path); ?>"
                                            alt="<?php echo esc_attr($provider_data['name']); ?>"
                                            class="ai-redactor-provider-svg">
                                    </span>
                                    <span class="ai-redactor-tab-text">
                                        <?php echo esc_html($provider_data['name']); ?>
                                        <?php if ($is_provider_active) : ?>
                                            <span class="ai-redactor-tab-badge"><?php echo esc_html__('Actif', 'ai-redactor'); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Contenu des onglets (maintenant à droite) -->
                <div class="ai-redactor-tabs-content-wrapper">
                    <div class="ai-redactor-tabs-content">
                        <?php foreach ($providers_config as $provider_id => $provider_data) :
                            // Récupérer la clé API
                            $api_key_option = $provider_data['api_key_option'];
                            $api_key = get_option($api_key_option, '');
                            $is_active_provider = ($provider_id === $active_provider);
                            $provider_name = ucfirst($provider_id);
                            $svg_path = WP_PLUGIN_AI_AGENT_URL . 'assets/svg/' . $provider_name . '.svg';
                        ?>
                            <div id="provider-<?php echo esc_attr($provider_id); ?>"
                                class="ai-redactor-tab-content <?php echo $is_active_provider ? 'active' : ''; ?>">

                                <h2>
                                    <span class="ai-redactor-provider-icon">
                                        <img src="<?php echo esc_url($svg_path); ?>"
                                            alt="<?php echo esc_attr($provider_data['name']); ?>"
                                            class="ai-redactor-header-svg">
                                    </span>
                                    <?php echo esc_html($provider_data['name']); ?>
                                    <span class="ai-redactor-provider-badge <?php echo $is_active_provider ? 'active' : 'inactive'; ?>">
                                        <?php echo $is_active_provider
                                            ? esc_html__('Actif', 'ai-redactor')
                                            : esc_html__('Inactif', 'ai-redactor'); ?>
                                    </span>
                                </h2>

                                <!-- Clé API -->
                                <div class="ai-redactor-form-row">
                                    <label for="<?php echo esc_attr($api_key_option); ?>">
                                        <?php echo esc_html__('Clé API', 'ai-redactor'); ?>
                                    </label>
                                    <p class="description">
                                        <?php echo sprintf(
                                            esc_html__('Entrez votre clé API %s pour utiliser ce service.', 'ai-redactor'),
                                            esc_html($provider_data['name'])
                                        ); ?>
                                    </p>
                                    <div class="ai-redactor-api-key-input">
                                        <input type="password"
                                            id="<?php echo esc_attr($api_key_option); ?>"
                                            name="<?php echo esc_attr($api_key_option); ?>"
                                            value="<?php echo esc_attr($api_key); ?>"
                                            class="regular-text"
                                            placeholder="<?php echo esc_attr__('Coller votre clé API ici', 'ai-redactor'); ?>" />
                                        <button type="button" class="button ai-redactor-toggle-api-key">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Modèles disponibles (avec cartes) -->
                                <div class="ai-redactor-form-row">
                                    <h3><?php echo esc_html__('Sélection du modèle actif', 'ai-redactor'); ?></h3>
                                    <p class="description">
                                        <?php echo esc_html__('Choisissez le modèle d\'IA que vous souhaitez utiliser pour générer du contenu.', 'ai-redactor'); ?>
                                    </p>

                                    <?php if (empty($provider_data['models'])) : ?>
                                        <div class="ai-redactor-no-models">
                                            <p><?php echo esc_html__('Aucun modèle disponible pour ce fournisseur.', 'ai-redactor'); ?></p>
                                        </div>
                                    <?php else : ?>
                                        <div class="ai-redactor-models-list">
                                            <?php foreach ($provider_data['models'] as $model_id => $model_data) :
                                                $combined_id = $provider_id . ':' . $model_id;
                                                $is_active = ($combined_id === $active_model);
                                                $is_popular = in_array($combined_id, $popular_models);
                                            ?>
                                                <div class="ai-redactor-model-option <?php echo $is_active ? 'selected' : ''; ?>">
                                                    <label>
                                                        <div class="ai-redactor-model-header">
                                                            <input type="radio"
                                                                class="ai-redactor-model-radio"
                                                                name="ai_redactor_active_model"
                                                                value="<?php echo esc_attr($combined_id); ?>"
                                                                <?php checked($is_active); ?>>
                                                            <span class="ai-redactor-model-name"><?php echo esc_html($model_data['label']); ?></span>

                                                            <?php if ($is_active) : ?>
                                                                <span class="ai-redactor-model-badge active"><?php echo esc_html__('Actif', 'ai-redactor'); ?></span>
                                                            <?php elseif ($is_popular) : ?>
                                                                <span class="ai-redactor-model-badge popular"><?php echo esc_html__('Populaire', 'ai-redactor'); ?></span>
                                                            <?php endif; ?>
                                                        </div>

                                                        <?php if (!empty($model_data['description'])) : ?>
                                                            <div class="ai-redactor-model-description">
                                                                <?php echo esc_html($model_data['description']); ?>
                                                            </div>
                                                        <?php endif; ?>

                                                        <div class="ai-redactor-model-details">
                                                            <div class="ai-redactor-model-detail-item">
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
                                <div class="ai-redactor-form-row">
                                    <button type="button"
                                        class="ai-redactor-test-connection"
                                        data-provider="<?php echo esc_attr($provider_id); ?>">
                                        <span class="dashicons dashicons-update"></span>
                                        <?php echo esc_html__('Tester la connexion', 'ai-redactor'); ?>
                                    </button>
                                    <span class="ai-redactor-connection-status" id="status-<?php echo esc_attr($provider_id); ?>"></span>
                                </div>

                                <div class="ai-redactor-provider-info">
                                    <h3><?php echo esc_html__('Informations sur les modèles disponibles', 'ai-redactor'); ?></h3>
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead>
                                            <tr>
                                                <th><?php echo esc_html__('Modèle', 'ai-redactor'); ?></th>
                                                <th><?php echo esc_html__('Coût', 'ai-redactor'); ?></th>
                                                <th><?php echo esc_html__('Tokens max', 'ai-redactor'); ?></th>
                                                <th><?php echo esc_html__('Description', 'ai-redactor'); ?></th>
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
                    name="ai_redactor_connector_submit"
                    id="submit"
                    class="button button-primary"
                    value="<?php echo esc_attr__('Enregistrer les modifications', 'ai-redactor'); ?>">
            </p>
        </form>
<?php
    }
}
