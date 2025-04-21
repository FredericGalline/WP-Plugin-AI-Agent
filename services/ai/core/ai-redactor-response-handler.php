<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe de traitement de la réponse de l'API IA.
 */
class AI_Redactor_Response_Handler
{
    /**
     * Traite la réponse de l'IA et crée un article WordPress.
     *
     * @param array $response Réponse de l'API.
     * @param array $args Options de création de l'article.
     * @return array|WP_Error Tableau contenant l'ID du post ou une erreur WP_Error.
     */
    public static function handle($response, $args)
    {
        $post_data = [
            'post_title'   => 'Article généré',
            'post_content' => $response['response'] ?? '',
            'post_status'  => $args['post_status'] ?? 'draft',
            'post_type'    => $args['post_type'] ?? 'post',
        ];
        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        return ['post_id' => $post_id];
    }
}
