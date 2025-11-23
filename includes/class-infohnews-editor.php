<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class INFOHNEWS_Editor {

    private static $metabox_content_id = 'infohnews-metabox-content';

    public static function init() {
        add_action( 'add_meta_boxes', [ self::class, 'add_metabox' ] );
        add_filter('manage_posts_columns', [ self::class, 'add_custom_column_header' ]);
        add_filter('manage_pages_columns', [ self::class, 'add_custom_column_header' ]);
        add_action('manage_posts_custom_column', [ self::class, 'add_custom_column_content' ], 10, 2);
        add_action('manage_pages_custom_column', [ self::class, 'add_custom_column_content' ], 10, 2);
    }

    public static function add_custom_column_header($columns) {
        $new_columns = [];
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            if ($key === 'title') {
                $new_columns['infohnews_generate'] = __('AI Image', 'info-hnews-ai-image');
            }
        }
        return $new_columns;
    }

    public static function add_custom_column_content($column_name, $post_id) {
        if ($column_name == 'infohnews_generate') {
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, [60, 60]);
            } else {
                echo '<button type="button" class="button infohnews-generate-from-list" data-post-id="' . esc_attr($post_id) . '">' . esc_html__('Generate', 'info-hnews-ai-image') . '</button>';
                echo '<div class="infohnews-feedback" id="infohnews-feedback-' . esc_attr($post_id) . '" style="height:22px;margin-top:5px;"></div>';
            }
        }
    }

    public static function get_metabox_content_id() {
        return self::$metabox_content_id;
    }

    public static function add_metabox() {
        $post_types = get_post_types( ['public' => true], 'names' );
        foreach ( $post_types as $post_type ) {
             if ( post_type_supports( $post_type, 'thumbnail' ) ) {
                  add_meta_box( 'infohnews_generator_metabox', 'AI Featured Image Generator', [ self::class, 'render_metabox' ], $post_type, 'side', 'low' );
             }
        }
    }

    public static function render_metabox( $post ) {
        $options = get_option('infohnews_settings');
        $api_service = $options['api_service'] ?? 'huggingface';
        $api_key_present = !empty($options[$api_service . '_api_key']) || $api_service === 'pollinations';
        $service_name = ucfirst($api_service);
        $prompt_source = $options['prompt_source'] ?? 'title';
        ?>
        <div id="<?php echo esc_attr( self::$metabox_content_id ); ?>">
			<?php if (!$api_key_present) : ?>
                <?php /* translators: %s: The name of the currently active AI service (e.g., "Hugging Face"). */ ?>
                <p style="color: red; font-weight: bold;"><?php printf( esc_html__( 'Please enter your API key for %s in the plugin settings.', 'info-hnews-ai-image' ), esc_html( $service_name ) ); ?></p>
                <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=infohnews-settings' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Settings', 'info-hnews-ai-image' ); ?></a></p>
            <?php else : ?>
                <?php /* translators: %s: The name of the currently active AI service. */ ?>
                <p><?php printf( esc_html__( 'Active AI-Service: %s', 'info-hnews-ai-image' ), '<strong>' . esc_html( $service_name ) . '</strong>' ); ?><br>
                <?php /* translators: %s: The source for automatic prompt generation (e.g., "title", "content"). */ ?>
                <?php printf( esc_html__( 'Source for auto-prompt: %s', 'info-hnews-ai-image' ), '<strong>' . esc_html( $prompt_source ) . '</strong>' ); ?></p>
                <hr style="margin: 10px 0;">
                <div>
                    <label for="infohnews-custom-prompt" style="font-weight: bold;"><?php esc_html_e( 'Alternative AI-prompt:', 'info-hnews-ai-image' ); ?></label><br>
                    <span>(<?php esc_html_e( 'Leave empty to use the auto-prompt source.', 'info-hnews-ai-image' ); ?>)</span>
                    <textarea id="infohnews-custom-prompt" name="infohnews_custom_prompt" rows="3" style="width:100%; margin-top: 3px;" placeholder="<?php esc_attr_e('Dica: Se o título gerar texto (ex: "Sobre Nós"), descreva a cena aqui.', 'info-hnews-ai-image'); ?>"></textarea>
                </div>
                <div style="margin-top: 8px;">
                    <label for="infohnews-negative-prompt"><?php esc_html_e( 'Negative AI-prompt (optional):', 'info-hnews-ai-image' ); ?></label><br>
                    <span>(<?php esc_html_e( 'What to avoid in the image?', 'info-hnews-ai-image' ); ?>)</span>
                    <textarea id="infohnews-negative-prompt" name="infohnews_negative_prompt" rows="2" style="width:100%; margin-top: 3px;" placeholder="<?php esc_attr_e('e.g. text, blurry, watermark', 'info-hnews-ai-image'); ?>"></textarea>
                </div>
                <button type="button" id="infohnews-generate-button" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="margin-top: 10px;"><?php esc_html_e( 'Generate image', 'info-hnews-ai-image' ); ?></button>
                <span class="spinner" id="infohnews-spinner" style="float: none; vertical-align: middle;"></span>
                <div id="infohnews-status-message" style="margin-top: 10px;"></div>
            <?php endif; ?>
        </div>
        <?php
    }
}