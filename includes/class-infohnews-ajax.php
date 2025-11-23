<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class INFOHNEWS_Ajax {

    public static function init() {
        add_action( 'wp_ajax_infohnews_generate_image', [ self::class, 'handle_generate_request' ] );
    }

    public static function handle_generate_request() {
        if (!check_ajax_referer( 'infohnews_generate_image_nonce', 'nonce', false )) { wp_send_json_error( [ 'message' => __( 'Security check failed.', 'info-hnews-ai-image' ) ] ); }
        if ( ! current_user_can( 'edit_posts' ) || ! current_user_can( 'upload_files' ) ) { wp_send_json_error( [ 'message' => __( 'Insufficient permission.', 'info-hnews-ai-image' ) ] ); }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $custom_prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';

        if ( ! $post_id || ! get_post( $post_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid or non-existent post ID.', 'info-hnews-ai-image' ) ], 400 );
        }

        $options = get_option( 'infohnews_settings' );
        if (!$options) { wp_send_json_error( [ 'message' => __( 'Plugin settings not found.', 'info-hnews-ai-image' ) ] ); }
        
        $api_service = $options['api_service'] ?? 'pollinations';
        $api_key = $options[ $api_service . '_api_key'] ?? '';

        $prompt_text = !empty(trim($custom_prompt)) 
            ? trim($custom_prompt) 
            : self::get_text_from_post($post_id, $options);
            
        $final_prompt = self::build_intelligent_prompt($prompt_text, $options);

        if (empty($final_prompt)) {
            wp_send_json_error( [ 'message' => __( 'Could not generate a prompt from the post content. Please ensure the selected source (title, excerpt, or content) is not empty.', 'info-hnews-ai-image' ) ] ); 
            return;
        }

        $negative_prompt_from_user = isset( $_POST['negative_prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['negative_prompt'] ) ) : '';

        try {
            $api_handler = new INFOHNEWS_API_Handler( $api_service, $api_key );
            $result = $api_handler->generate_image( $final_prompt, $post_id, $negative_prompt_from_user, $prompt_text );
            
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( [ 'message' => $result->get_error_message() ], 500 );
            } else {
                $attachment_id = $result;
                if ( set_post_thumbnail( $post_id, $attachment_id ) ) {
                    $image_html = get_the_post_thumbnail( $post_id, [60,60] );
                    $preview_url = get_the_post_thumbnail_url( $post_id, 'medium' );
                    wp_send_json_success( [ 'message' => __( 'Image successfully generated!', 'info-hnews-ai-image' ), 'attachment_id' => $attachment_id, 'image_html' => $image_html, 'preview_url' => $preview_url ] );
                } else {
                    wp_send_json_error( [ 'message' => __( 'Image uploaded but could not be set as featured image.', 'info-hnews-ai-image' ) ] );
                }
            }
        } catch (Exception $e) {
            wp_send_json_error( [ 'message' => __( 'An error occurred during image generation.', 'info-hnews-ai-image' ) ] );
        }
    }
    
    private static function get_text_from_post($post_id, $options) {
        $prompt_source = $options['prompt_source'] ?? 'title';
        $text = '';

        switch ($prompt_source) {
            case 'excerpt':
                $text = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : get_the_title( $post_id );
                break;
            case 'content':
                $post_obj = get_post( $post_id );
                if ( $post_obj && !empty( $post_obj->post_content ) ) {
                    $content = strip_shortcodes( $post_obj->post_content );
                    $cleaned_content = trim( wp_strip_all_tags( $content ) );
                    $text = mb_substr( $cleaned_content, 0, 300 );
                } else {
                    $text = get_the_title( $post_id );
                }
                break;
            case 'title':
            default:
                $text = get_the_title( $post_id );
                break;
        }

        return empty(trim($text)) ? 'Image for post ' . $post_id : trim($text);
    }

    private static function build_intelligent_prompt($base_prompt_text, $options) {
        if (empty($base_prompt_text)) {
            return '';
        }

        $image_style = $options['image_style'] ?? 'photo_ultra_realistic';
        $final_prompt = '';
        $no_text_instruction = " The final image must be purely visual and contain absolutely no letters, no words, no phrases or text characters of any kind.";

        switch ($image_style) {
            case 'photo_ultra_realistic':
                $final_prompt = $no_text_instruction. "Ultra-realistic professional photo of '{$base_prompt_text}'. 8K, UHD, tack sharp focus, extremely detailed, sharp details, cinematic.";
                break;
            case 'drawing_realistic':
                $final_prompt = $no_text_instruction. "A modern masterpiece digital painting of '{$base_prompt_text}', in the hyperrealistic style of Alex Ross. Emphasize vibrant colors and dramatic, modern cinematic lighting. Trending on ArtStation, high definition, intricate details, sharp focus.";
                break;
            case 'drawing_simple':
                $final_prompt = $no_text_instruction. "A minimalist cartoon-style illustration of '{$base_prompt_text}'. Style is modern retro cartoon, stylized, no realism. Thin black outlines, simple contours, flat color areas. Soft pastel tones (light blue, beige, salmon, soft green, light gray). Uniform lighting, no strong contrasts, calm and stylized mood.";
                break;
            default:
                $final_prompt = $no_text_instruction. "A high quality, detailed image of '{$base_prompt_text}'. sharp focus, high details.";
                break;
        }
        
        return $final_prompt;
    }
}