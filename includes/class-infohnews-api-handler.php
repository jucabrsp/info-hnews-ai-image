<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once( ABSPATH . 'wp-admin/includes/image.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/media.php' );

class INFOHNEWS_API_Handler {
    private $api_service;
    private $api_key;
    
    public function __construct( $service, $key ) { 
        $this->api_service = $service; 
        $this->api_key = $key; 
    }
    
    public function generate_image( $prompt, $post_id, $negative_prompt = '', $base_text = '' ) {
        $key_required_services = ['huggingface', 'stabilityai'];
        if (in_array($this->api_service, $key_required_services) && empty($this->api_key)) {
            return new WP_Error('api_key_missing', __( 'API key/token not configured for ', 'info-hnews-ai-image' ) . ucfirst($this->api_service));
        }

        switch ($this->api_service) {
            case 'huggingface':
                return $this->generate_with_huggingface($prompt, $post_id, $negative_prompt, $base_text);
            case 'stabilityai':
                return $this->generate_with_stabilityai($prompt, $post_id, $negative_prompt, $base_text);
            case 'pollinations':
                return $this->generate_with_pollinations($prompt, $post_id, $negative_prompt, $base_text);
            default:
                return new WP_Error('invalid_service', __( 'Invalid AI service: ', 'info-hnews-ai-image' ) . $this->api_service);
        }
    }
    
    private function generate_with_huggingface( $prompt, $post_id, $negative_prompt = '', $base_text = '' ) {
        $options = get_option('infohnews_settings');
        $model_id = $options['huggingface_model_id'] ?? 'stabilityai/stable-diffusion-xl-base-1.0';
        $width = intval($options['huggingface_width'] ?? 1024);
        $height = intval($options['huggingface_height'] ?? 1024);

        // ========================================================================
        //  CORREÇÃO: Nova URL e estrutura da API Hugging Face (Novembro 2025)
        // ========================================================================

        // 1. Nova URL do router com o caminho correto para o modelo
        $api_url = 'https://router.huggingface.co/hf-inference/models/' . $model_id;
        
        // 2. Nova estrutura do payload conforme documentação atualizada
        // O prompt vai em 'inputs' como string simples
        // Os parâmetros vão dentro de um objeto 'parameters'
        $body = [
            'inputs' => $prompt,
            'parameters' => [
                'width' => $width,
                'height' => $height,
            ]
        ];
        
        // 3. Adiciona o 'negative_prompt' dentro do objeto 'parameters' se não estiver vazio
        if ( ! empty( $negative_prompt ) ) {
            $body['parameters']['negative_prompt'] = $negative_prompt;
        }

        // ========================================================================
        //  FIM DA CORREÇÃO
        // ========================================================================

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => json_encode($body),
            'timeout' => 300,
        ]);

        if (is_wp_error($response)) { return $response; }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            // error_log('Hugging Face API Error. Code: ' . $response_code . '. Full Response: ' . print_r($response_body, true));
            $error_data = json_decode($response_body, true);
            $error_message = $error_data['error'] ?? 'Unknown error.';
            if (isset($error_data['estimated_time'])) {
                $error_message = 'Model is currently loading, please try again in a minute. (Est. time: ' . round($error_data['estimated_time']) . 's)';
            }
            return new WP_Error('huggingface_error', 'Hugging Face Error: ' . $error_message);
        }

        if (empty($response_body)) {
            return new WP_Error('huggingface_empty_response', 'Hugging Face returned an empty image.');
        }

        return $this->save_image_to_media_library($response_body, $base_text, $post_id);
    }

    private function generate_with_pollinations( $prompt, $post_id, $negative_prompt = '', $base_text = '' ) {
        $options = get_option('infohnews_settings');
        $model = $options['pollinations_model'] ?? 'flux';
        $width = intval($options['pollinations_width'] ?? 1024);
        $height = intval($options['pollinations_height'] ?? 1024);

        // ========================================================================
        //  CORREÇÃO: URL atualizada + Sistema de retry para erro 530 (Nov 2025)
        // ========================================================================

        // A URL correta é: https://image.pollinations.ai/prompt/{prompt}
        $api_url = add_query_arg([
            'width' => $width,
            'height' => $height,
            'seed' => wp_rand(1, 1000000),
            'model' => sanitize_text_field($model),
            'nologo' => 'true'
        ], 'https://image.pollinations.ai/prompt/' . rawurlencode($prompt));

        // Sistema de retry: tenta até 3 vezes com delay crescente
        $max_attempts = 3;
        $attempt = 0;
        $last_error = null;

        while ($attempt < $max_attempts) {
            $attempt++;
            
            // Delay crescente entre tentativas (5s, 10s, 15s) - respeitando rate limit
            if ($attempt > 1) {
                sleep(5 * $attempt);
            }

            $response = wp_remote_get($api_url, ['timeout' => 120]);
            
            if (is_wp_error($response)) {
                $last_error = new WP_Error('pollinations_request_error', 'Pollinations API request failed: ' . $response->get_error_message());
                continue;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            
            // Erro 530 específico - indica sobrecarga do servidor
            if ($response_code === 530) {
                $last_error = new WP_Error('pollinations_server_overload', 'Pollinations.ai is temporarily overloaded (Error 530). Attempt ' . $attempt . ' of ' . $max_attempts);
                continue; // Tenta novamente
            }
            
            // Outros erros HTTP
            if ($response_code !== 200) {
                $last_error = new WP_Error('pollinations_api_error', 'Pollinations API returned code ' . $response_code . ' (Attempt ' . $attempt . ')');
                continue;
            }
            
            // Sucesso - verifica se tem dados
            $image_data = wp_remote_retrieve_body($response);
            if (empty($image_data)) {
                $last_error = new WP_Error('pollinations_empty_response', 'Empty image data from Pollinations API');
                continue;
            }
            
            // Tudo OK - retorna a imagem
            return $this->save_image_to_media_library($image_data, $base_text, $post_id);
        }

        // Se chegou aqui, todas as tentativas falharam
        return $last_error;

        // ========================================================================
        //  FIM DA CORREÇÃO
        // ========================================================================
    }
    
    private function generate_with_stabilityai( $prompt, $post_id, $negative_prompt = '', $base_text = '' ) {
        $options = get_option('infohnews_settings');
        $engine_id = $options['stabilityai_engine_id'] ?? 'stable-diffusion-xl-1024-v1-0';
        $width = intval($options['stabilityai_width'] ?? 1536);
        $height = intval($options['stabilityai_height'] ?? 640);
        $image_style = $options['image_style'] ?? 'photo_ultra_realistic';

        // ========================================================================
        //  Stability.AI está correto - API v1 ainda funcional em Novembro 2025
        // ========================================================================

        $api_url = 'https://api.stability.ai/v1/generation/' . $engine_id . '/text-to-image';
        
        $body = [
            'text_prompts' => [['text' => $prompt]],
            'cfg_scale'    => 7,
            'height'       => $height,
            'width'        => $width,
            'samples'      => 1,
            'steps'        => 30,
        ];
        
        // Adiciona style_preset se o estilo for photographic
        if (strpos($image_style, 'photo') !== false) {
            $body['style_preset'] = 'photographic';
        }

        // Adiciona negative prompt se fornecido
        if (!empty($negative_prompt)) { 
            $body['text_prompts'][] = ['text' => $negative_prompt, 'weight' => -1]; 
        }

        // ========================================================================
        //  FIM DA VERIFICAÇÃO
        // ========================================================================

        $response = wp_remote_post($api_url, [
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'Authorization' => 'Bearer ' . $this->api_key,],
            'body'    => json_encode($body),
            'timeout' => 120,
        ]);
        if (is_wp_error($response)) { return $response; }
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        if ($response_code !== 200) { return new WP_Error('stabilityai_error', 'Stability AI Error: ' . ($response_body['message'] ?? 'Unknown API error')); }
        if (empty($response_body['artifacts'][0]['base64'])) { return new WP_Error('stabilityai_no_image', 'Stability AI API did not return an image.'); }
        $image_data = base64_decode($response_body['artifacts'][0]['base64']);
        return $this->save_image_to_media_library($image_data, $base_text, $post_id);
    }

    private function save_image_to_media_library( $image_data, $base_text, $post_id ) {
        $clean_base_text = sanitize_text_field($base_text);
        $filename = 'ai-image-' . $post_id . '-' . time() . '.png';
        $upload = wp_upload_bits($filename, null, $image_data);

        if (!empty($upload['error'])) {
            return new WP_Error('upload_error', __( 'Could not save image file: ', 'info-hnews-ai-image' ) . $upload['error']);
        }

        $attachment = [
            'guid'           => $upload['url'],
            'post_mime_type' => 'image/png',
            'post_title'     => $clean_base_text, // Título da imagem
            'post_content'   => $clean_base_text, // Descrição da imagem
            'post_excerpt'   => $clean_base_text, // Legenda (Caption) da imagem
            'post_status'    => 'inherit',
            'post_parent'    => $post_id
        ];

        $attachment_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
        if (is_wp_error($attachment_id)) { return $attachment_id; }
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        
        // Adiciona o Texto Alternativo (Alt Text)
        if(!empty($clean_base_text)) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $clean_base_text);
        }

        wp_update_attachment_metadata($attachment_id, $attachment_data);
        return $attachment_id;
    }
}