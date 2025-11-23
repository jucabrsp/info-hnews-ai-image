<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class INFOHNEWS_Settings {
    private static $options;
    
    public static function init() {
        // Adiciona a página de configurações ao menu do WordPress
        add_action( 'admin_menu', [ self::class, 'add_settings_page' ] );
        
        // Registra todas as configurações do plugin
        add_action( 'admin_init', [ self::class, 'register_settings' ] );
        
        // Adiciona os links na página de plugins (movido para dentro da classe)
        if (defined('INFOHNEWS_PLUGIN_DIR')) {
            $plugin_basename = plugin_basename( INFOHNEWS_PLUGIN_DIR . 'info-hnews-ai-image.php' );
            add_filter( 'plugin_action_links_' . $plugin_basename, [ self::class, 'add_settings_link' ] );
            add_filter( 'plugin_row_meta', [ self::class, 'add_meta_links' ], 10, 2 );
        }
    }
    
    /**
     * Adiciona o link de "Settings" na página de plugins.
     */
    public static function add_settings_link( $links ) {
        $settings_url = admin_url( 'admin.php?page=infohnews-settings' );
        $settings_link = sprintf( '<a href="%s">%s</a>', esc_url( $settings_url ), __( 'Settings', 'info-hnews-ai-image' ) );
        array_unshift( $links, $settings_link );
        return $links;
    }
    
    /**
     * Adiciona links de meta (Doação, Versão, etc.) na página de plugins.
     */
    public static function add_meta_links( $links, $file ) {
        $plugin_basename = plugin_basename( INFOHNEWS_PLUGIN_DIR . 'info-hnews-ai-image.php' );
        
        if ($file == $plugin_basename) {
            $donation_url = 'https://www.infohostingnews.com/thanks/';
            $donation_link = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url($donation_url), '&#9733; ' . __( 'Donation', 'info-hnews-ai-image' ) );
            
            $version_text = 'Version 1.8';
            $author_text = 'By InfoHnews';
            
            $plugin_site_url = 'https://www.infohostingnews.com/infohnews/';
            $plugin_site_link = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url($plugin_site_url), __( 'Visit plugin site', 'info-hnews-ai-image' ) );
            
            // Retorna uma nova array na ordem correta, evitando duplicação
            return [
                $donation_link,
                $version_text,
                $author_text,
                $plugin_site_link,
            ];
        }
        
        return $links;
    }
    
    public static function add_settings_page() {
        add_menu_page(
            'Info HNews AI Image',
            'Info HNews AI Image',
            'manage_options',
            'infohnews-settings',
            [ self::class, 'create_settings_page' ],
            'dashicons-format-image'
        );
    }
    
    public static function register_settings() {
        // CORREÇÃO: Função de sanitização robusta 'sanitize_settings' está sendo usada aqui
        register_setting( 'infohnews_settings_group', 'infohnews_settings', [ self::class, 'sanitize_settings' ] );
        
        add_settings_section(
            'infohnews_api_settings_section',
            __( 'API Settings', 'info-hnews-ai-image' ),
            null,
            'infohnews-settings'
        );
        
        add_settings_field(
            'infohnews_api_service',
            __( 'AI-Service', 'info-hnews-ai-image' ),
            [ self::class, 'render_api_service_field' ],
            'infohnews-settings',
            'infohnews_api_settings_section'
        );
        
        add_settings_field(
            'infohnews_huggingface_api_key',
            'Hugging Face - ' . __( 'Access Token', 'info-hnews-ai-image' ),
            [ self::class, 'render_huggingface_api_key_field' ],
            'infohnews-settings',
            'infohnews_api_settings_section',
            [ 'class' => 'infohnews-service-field-row infohnews-huggingface-field' ]
        );
        
        add_settings_field(
            'infohnews_huggingface_model_id',
            'Hugging Face - ' . __( 'Model ID', 'info-hnews-ai-image' ),
            [ self::class, 'render_huggingface_model_id_field' ],
            'infohnews-settings',
            'infohnews_api_settings_section',
            [ 'class' => 'infohnews-service-field-row infohnews-huggingface-field' ]
        );
        
        add_settings_field(
            'infohnews_huggingface_dimensions',
            'Hugging Face - ' . __( 'Image dimensions', 'info-hnews-ai-image' ),
            [ self::class, 'render_huggingface_dimensions_controls' ],
            'infohnews-settings',
            'infohnews_api_settings_section',
            [ 'class' => 'infohnews-service-field-row infohnews-huggingface-field' ]
        );
        
        add_settings_field(
            'infohnews_stabilityai_api_key',
            'Stability AI - ' . __( 'API Key', 'info-hnews-ai-image' ),
            [ self::class, 'render_stabilityai_api_key_field' ],
            'infohnews-settings',
            'infohnews_api_settings_section',
            [ 'class' => 'infohnews-service-field-row infohnews-stabilityai-field' ]
        );
        
        add_settings_field(
            'infohnews_stabilityai_engine_id',
            'Stability AI - ' . __( 'Engine ID', 'info-hnews-ai-image' ),
            [ self::class, 'render_stabilityai_engine_id_field' ],
            'infohnews-settings',
            'infohnews_api_settings_section',
            [ 'class' => 'infohnews-service-field-row infohnews-stabilityai-field' ]
        );
        
        add_settings_field(
            'infohnews_stabilityai_dimensions',
            'Stability AI - ' . __( 'Image dimensions', 'info-hnews-ai-image' ),
            [ self::class, 'render_stabilityai_dimensions_controls' ],
            'infohnews-settings',
            'infohnews_api_settings_section',
            [ 'class' => 'infohnews-service-field-row infohnews-stabilityai-field' ]
        );
        
        add_settings_field(
            'infohnews_pollinations_model',
            'Pollinations.ai - ' . __( 'Model Identifier', 'info-hnews-ai-image' ),
            [ self::class, 'render_pollinations_model_field' ],
            'infohnews-settings',
            'infohnews_api_settings_section',
            [ 'class' => 'infohnews-service-field-row infohnews-pollinations-field' ]
        );
        
        add_settings_field(
            'infohnews_pollinations_width',
            'Pollinations.ai - ' . __( 'Width', 'info-hnews-ai-image' ),
            [ self::class, 'render_pollinations_width_field' ],
            'infohnews-settings',
            'infohnews_api_settings_section',
            [ 'class' => 'infohnews-service-field-row infohnews-pollinations-field' ]
        );
        
        add_settings_field(
            'infohnews_pollinations_height',
            'Pollinations.ai - ' . __( 'Height', 'info-hnews-ai-image' ),
            [ self::class, 'render_pollinations_height_field' ],
            'infohnews-settings',
            'infohnews_api_settings_section',
            [ 'class' => 'infohnews-service-field-row infohnews-pollinations-field' ]
        );
        
        add_settings_section(
            'infohnews_prompt_settings_section',
            __( 'AI-Prompt Settings', 'info-hnews-ai-image' ),
            null,
            'infohnews-settings'
        );
        
        add_settings_field(
            'infohnews_prompt_source',
            __( 'Source for auto-prompt', 'info-hnews-ai-image' ),
            [ self::class, 'render_prompt_source_field' ],
            'infohnews-settings',
            'infohnews_prompt_settings_section'
        );
        
        add_settings_field(
            'infohnews_image_style',
            __( 'Default Image Style', 'info-hnews-ai-image' ),
            [ self::class, 'render_image_style_field' ],
            'infohnews-settings',
            'infohnews_prompt_settings_section'
        );
    }
    
    /**
     * CORREÇÃO: Função de sanitização robusta para atender aos requisitos do WordPress.org.
     */
    public static function sanitize_settings( $input ) {
        // Obter as opções antigas para usar como base.
        $old_options = get_option( 'infohnews_settings', [] );
        $sanitized_input = $old_options; // Começa com os valores antigos.

        // Verifica se $input é um array, o que deveria ser
        if ( ! is_array( $input ) ) {
            $input = [];
        }

        // Itera *apenas* sobre os novos dados recebidos do formulário.
        foreach ( $input as $key => $value ) {
            switch ( $key ) {
                // Chaves de API e IDs de modelo (texto livre, mas sanitizado)
                case 'huggingface_api_key':
                case 'stabilityai_api_key':
                case 'huggingface_model_id':
                case 'pollinations_model':
                    $sanitized_input[ $key ] = sanitize_text_field( $value );
                    break;
                
                // Chaves de seleção (devem corresponder a valores esperados)
                case 'api_service':
                    $allowed_services = [ 'huggingface', 'stabilityai', 'pollinations' ];
                    $sanitized_input[ $key ] = in_array( $value, $allowed_services, true ) ? $value : 'pollinations'; // 'pollinations' como padrão seguro
                    break;

                case 'prompt_source':
                    $allowed_sources = [ 'title', 'excerpt', 'content' ];
                    $sanitized_input[ $key ] = in_array( $value, $allowed_sources, true ) ? $value : 'title'; // 'title' como padrão
                    break;

                case 'image_style':
                    $allowed_styles = [ 'photo_ultra_realistic', 'drawing_realistic', 'drawing_simple' ];
                    $sanitized_input[ $key ] = in_array( $value, $allowed_styles, true ) ? $value : 'photo_ultra_realistic'; // padrão
                    break;
                
                // Valores numéricos (dimensões)
                case 'huggingface_width':
                case 'huggingface_height':
                case 'stabilityai_width':
                case 'stabilityai_height':
                case 'pollinations_width':
                case 'pollinations_height':
                    $sanitized_input[ $key ] = intval( $value );
                    break;

                // Lógica especial para o 'stabilityai_engine_id' (simplificada)
                case 'stabilityai_engine_id':
                    // Apenas permite valores da lista pré-definida
                    $allowed_engines = [ 'stable-diffusion-xl-1024-v1-0', 'stable-diffusion-3-medium' ];
                    if ( in_array( $value, $allowed_engines, true ) ) {
                        $sanitized_input[ $key ] = $value;
                    } else {
                        // Se for um valor inválido, reverte para o padrão
                        $sanitized_input[ $key ] = 'stable-diffusion-xl-1024-v1-0';
                    }
                    break;
                
                // Ignora o campo custom, pois ele não existe mais.
                case 'stabilityai_engine_id_custom':
                    // Não faz nada.
                    break;
                
                // Campos desconhecidos não são salvos (medida de segurança)
                default:
                    // Não adiciona campos inesperados ao array sanitizado.
                    break;
            }
        }
        
        // Limpa o campo custom para não ficar órfão no banco de dados
        if ( isset( $sanitized_input['stabilityai_engine_id_custom'] ) ) {
            unset( $sanitized_input['stabilityai_engine_id_custom'] );
        }

        return $sanitized_input;
    }
    
    private static function get_options() {
        if (is_null(self::$options)) {
            self::$options = get_option('infohnews_settings', []);
        }
        return self::$options;
    }
    
    public static function render_api_service_field( $args ) {
        $options = self::get_options();
        $current_service = $options['api_service'] ?? 'huggingface';
        
        $available_services = [
            'huggingface' => __( 'Hugging Face (Requires Token)', 'info-hnews-ai-image' ),
            'stabilityai' => __( 'Stability AI', 'info-hnews-ai-image' ),
            'pollinations' => __( 'Pollinations.ai (Free)', 'info-hnews-ai-image' )
        ];
        
        ?>
        <select name="infohnews_settings[api_service]" id="infohnews_api_service_select">
            <?php foreach ( $available_services as $service_key => $service_name ) {
                echo '<option value="' . esc_attr( $service_key ) . '" ' . selected( $current_service, $service_key, false ) . '>' . esc_html( $service_name ) . '</option>';
            } ?>
        </select>
        <?php
    }
    
    public static function render_huggingface_api_key_field() {
        $options = self::get_options();
        $api_key = $options['huggingface_api_key'] ?? '';
        
        echo '<input type="text" name="infohnews_settings[huggingface_api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text">';
        echo '<p class="description">' . esc_html__('Paste your Access Token from Hugging Face here.', 'info-hnews-ai-image') . '</p>';
    }
    
    public static function render_huggingface_model_id_field() {
        $options = self::get_options();
        $current_model = $options['huggingface_model_id'] ?? 'stabilityai/stable-diffusion-xl-base-1.0';
        
        $available_models = [
            'stabilityai/stable-diffusion-xl-base-1.0' => 'Stable Diffusion XL 1.0 (Reliable)',
            'black-forest-labs/FLUX.1-dev' => 'FLUX.1-dev (Experimental / Slow)',
        ];
        
        ?>
        <select name="infohnews_settings[huggingface_model_id]">
            <?php foreach ( $available_models as $model_key => $model_name ) {
                echo '<option value="' . esc_attr( $model_key ) . '" ' . selected( $current_model, $model_key, false ) . '>' . esc_html( $model_name ) . '</option>';
            } ?>
        </select>
        <p class="description"><?php esc_html_e( 'Choose a model. SDXL 1.0 is recommended for reliability.', 'info-hnews-ai-image' ); ?></p>
        <?php
    }
    
    public static function render_huggingface_dimensions_controls() {
        $options = self::get_options();
        $width = $options['huggingface_width'] ?? 1504;
        $height = $options['huggingface_height'] ?? 600;
        
        ?>
        <div>
            <label style="display: inline-block; width: 50px;"><?php esc_html_e('Width:', 'info-hnews-ai-image'); ?></label>
            <input type="number" name="infohnews_settings[huggingface_width]" value="<?php echo esc_attr($width); ?>" class="small-text">
            <br>
            <label style="display: inline-block; width: 50px; margin-top: 5px;"><?php esc_html_e('Height:', 'info-hnews-ai-image'); ?></label>
            <input type="number" name="infohnews_settings[huggingface_height]" value="<?php echo esc_attr($height); ?>" class="small-text">
            <p class="description"><?php esc_html_e('Recommended size for SDXL is 1024x1024.', 'info-hnews-ai-image'); ?></p>
        </div>
        <?php
    }
    
    public static function render_stabilityai_api_key_field() {
        $options = self::get_options();
        $api_key = $options['stabilityai_api_key'] ?? '';
        
        echo '<input type="text" name="infohnews_settings[stabilityai_api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text">';
    }
    
    public static function render_stabilityai_engine_id_field( $args ) {
        $options = self::get_options();
        
        $available_engines = [
            'stable-diffusion-xl-1024-v1-0' => __( 'Stable Diffusion XL 1.0 (Recommended)', 'info-hnews-ai-image' ),
            'stable-diffusion-3-medium' => __( 'Stable Diffusion 3 Medium', 'info-hnews-ai-image' )
        ];
        
        $default_engine = 'stable-diffusion-xl-1024-v1-0';
        $current_engine_id = $options['stabilityai_engine_id'] ?? $default_engine;

        // Garante que o valor selecionado seja um dos disponíveis
        if ( !array_key_exists($current_engine_id, $available_engines) ) {
            $current_engine_id = $default_engine;
        }
        
        ?>
        <select name="infohnews_settings[stabilityai_engine_id]" id="infohnews_stabilityai_engine_id_select">
            <?php foreach ( $available_engines as $engine_id_key => $engine_name ) : ?>
            <option value="<?php echo esc_attr( $engine_id_key ); ?>" <?php selected( $current_engine_id, $engine_id_key ); ?>><?php echo esc_html( $engine_name ); ?></option>
            <?php endforeach; ?>
        </select>
        
        <p class="description"><?php esc_html_e( 'SDXL 1.0 is a stable and powerful option.', 'info-hnews-ai-image' ); ?></p>
        <?php
    }
    
    public static function render_stabilityai_dimensions_controls( $args ) {
        $options = self::get_options();
        $current_width = $options['stabilityai_width'] ?? 1536;
        $current_height = $options['stabilityai_height'] ?? 640;
        
        ?>
        <div id="infohnews_dimensions_controls">
            <label style="display: inline-block; width: 50px;"><?php esc_html_e('Width:', 'info-hnews-ai-image'); ?></label>
            <input type="number" name="infohnews_settings[stabilityai_width]" value="<?php echo esc_attr($current_width); ?>" step="64" class="small-text">
            <br>
            <label style="display: inline-block; width: 50px; margin-top: 5px;"><?php esc_html_e('Height:', 'info-hnews-ai-image'); ?></label>
            <input type="number" name="infohnews_settings[stabilityai_height]" value="<?php echo esc_attr($current_height); ?>" step="64" class="small-text">
            <p class="description"><?php esc_html_e('Width/height must be in increments of 64. Total pixels cannot exceed 1,048,576.', 'info-hnews-ai-image'); ?></p>
        </div>
        <?php
    }
    
    public static function render_pollinations_model_field() {
        $options = self::get_options();
        $model = $options['pollinations_model'] ?? 'flux';
        
        echo '<input type="text" name="infohnews_settings[pollinations_model]" value="' . esc_attr( $model ) . '" class="regular-text">';
    }
    
    public static function render_pollinations_width_field() {
        $options = self::get_options();
        $width = $options['pollinations_width'] ?? 1600;
        
        echo '<input type="number" name="infohnews_settings[pollinations_width]" value="' . esc_attr( $width ) . '" class="small-text" min="64" step="64"> px';
    }
    
    public static function render_pollinations_height_field() {
        $options = self::get_options();
        $height = $options['pollinations_height'] ?? 640;
        
        echo '<input type="number" name="infohnews_settings[pollinations_height]" value="' . esc_attr( $height ) . '" class="small-text" min="64" step="64"> px';
    }
    
    public static function render_prompt_source_field() {
        $options = self::get_options();
        $current_source = $options['prompt_source'] ?? 'title';
        
        $allowed_sources = [
            'title' => __( 'Page - or Post title', 'info-hnews-ai-image' ),
            'excerpt' => __( 'Excerpt (preferred)', 'info-hnews-ai-image' ),
            'content' => __( 'Page - or Post content', 'info-hnews-ai-image' )
        ];
        
        ?>
        <select name="infohnews_settings[prompt_source]">
            <?php foreach ( $allowed_sources as $source_value => $source_label ) : ?>
            <option value="<?php echo esc_attr( $source_value ); ?>" <?php selected( $current_source, $source_value ); ?>><?php echo esc_html( $source_label ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    public static function render_image_style_field() {
        $options = self::get_options();
        $current_style = $options['image_style'] ?? 'photo_ultra_realistic';
        
        // ========================================================================
        //  CORREÇÃO: Strings agora são traduzíveis
        // ========================================================================
        $available_styles = [
            'photo_ultra_realistic' => __( 'Ultra-Realistic Photo Style', 'info-hnews-ai-image' ),
            'drawing_realistic' => __( 'Realistic Drawing Style', 'info-hnews-ai-image' ),
            'drawing_simple' => __( 'Simple Drawing Style', 'info-hnews-ai-image' )
        ];
        // ========================================================================
        //  FIM DA CORREÇÃO
        // ========================================================================
        
        ?>
        <select name="infohnews_settings[image_style]">
            <?php foreach ( $available_styles as $style_value => $style_label ) : ?>
            <option value="<?php echo esc_attr( $style_value ); ?>" <?php selected( $current_style, $style_value ); ?>><?php echo esc_html( $style_label ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    public static function create_settings_page() {
        self::$options = get_option('infohnews_settings');
        ?>
        <div class="wrap">
            <div style="background-color: #f0f0f0; border: 1px solid #000; padding: 15px; margin: 20px 0; text-align: center; border-radius: 5px;">
                <h3 style="margin: 0; color: #333;">
                    <?php esc_html_e( 'Get AI MAX Pro Version, discover all the EXCLUSIVE features and get 15% off at checkout using Coupon: MAX15', 'info-hnews-ai-image' ); ?>
                    <a href="https://www.infohostingnews.com/aimaxfi/" target="_blank" style="color: #0073aa; text-decoration: none; font-weight: bold;">
                        <?php esc_html_e( 'CHECK NOW !', 'info-hnews-ai-image' ); ?>
                    </a>
                </h3>
            </div>
            
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                <img src="<?php echo esc_url(INFOHNEWS_PLUGIN_URL . 'admin/assets/iconmax.png'); ?>" width="128" height="128" alt="Info HNews AI Image Logo" style="border-radius: 5px;">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields( 'infohnews_settings_group' ); ?>
                
                <h2><?php esc_html_e('API Settings', 'info-hnews-ai-image'); ?></h2>
                <table class="form-table" role="presentation"><tbody><?php do_settings_fields( 'infohnews-settings', 'infohnews_api_settings_section' ); ?></tbody></table>
                
                <hr>
                
                <h2><?php esc_html_e('AI-Prompt Settings', 'info-hnews-ai-image'); ?></h2>
                <table class="form-table" role="presentation"><tbody><?php do_settings_fields( 'infohnews-settings', 'infohnews_prompt_settings_section' ); ?></tbody></table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}