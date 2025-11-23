<?php
/**
 * Plugin Name: Info HNews AI Image
 * Plugin URI: https://www.infohostingnews.com/infohnews
 * Description: Generate featured images ( high quality ) using Free AI.
 * Version: 1.8
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: InfoHnews
 * Author URI: https://www.infohostingnews.com
 * Text Domain: info-hnews-ai-image
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://wordpress.org/about/gpl
 * Donate link: https://www.infohostingnews.com/thanks/
 * @package info_hnews_ai_image
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'INFOHNEWS_VERSION', '1.8' );
define( 'INFOHNEWS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'INFOHNEWS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

function infohnews_plugin_uninstall() {
    delete_option('infohnews_settings');
}
register_uninstall_hook(__FILE__, 'infohnews_plugin_uninstall');

require_once INFOHNEWS_PLUGIN_DIR . 'includes/class-infohnews-settings.php';
require_once INFOHNEWS_PLUGIN_DIR . 'includes/class-infohnews-editor.php';  
require_once INFOHNEWS_PLUGIN_DIR . 'includes/class-infohnews-ajax.php';    
require_once INFOHNEWS_PLUGIN_DIR . 'includes/class-infohnews-api-handler.php';

if ( is_admin() ) {
    INFOHNEWS_Settings::init(); 
    INFOHNEWS_Editor::init();   
    INFOHNEWS_Ajax::init();     

    add_action( 'admin_enqueue_scripts', function( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        $is_settings_page = ( 'toplevel_page_infohnews-settings' === $hook );
        $is_post_edit_page = ( post_type_supports( $screen->post_type, 'thumbnail' ) && ( in_array( $screen->base, [ 'post', 'edit' ], true ) ) );
        
        if ( $is_settings_page || $is_post_edit_page ) {
            wp_enqueue_style( 'infohnews-admin-css', INFOHNEWS_PLUGIN_URL . 'admin/css/infohnews-admin.css', [], INFOHNEWS_VERSION ); 
            wp_enqueue_script( 'infohnews-admin-js', INFOHNEWS_PLUGIN_URL . 'admin/js/infohnews-admin.js', ['jquery', 'wp-data', 'wp-hooks'], INFOHNEWS_VERSION, true );
            
            // ========================================================================
            //  CORREÇÃO: Adicionadas strings de erro traduzíveis para o JS
            // ========================================================================
            wp_localize_script( 'infohnews-admin-js', 'infohnews_ajax_object', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'infohnews_generate_image_nonce' ),
                'generating' => __('Generating image...', 'info-hnews-ai-image'),
                'error_prefix' => __('Error: ', 'info-hnews-ai-image'),
                'unknown_error' => __('Unknown Error.', 'info-hnews-ai-image'),
                'ajax_error_prefix' => __('AJAX Error: ', 'info-hnews-ai-image'),
                'status_prefix' => __(' (Status: ', 'info-hnews-ai-image'),
                'failed_prefix' => __('Failed: ', 'info-hnews-ai-image'),
            ]);
            // ========================================================================
            //  FIM DA CORREÇÃO
            // ========================================================================

            if ( $is_settings_page ) {
                $inline_script = "
                    jQuery(document).ready(function($) {
                        function toggleServiceFields() {
                            var selectedService = $('#infohnews_api_service_select').val();
                            $('.infohnews-service-field-row').closest('tr').hide();
                            $('.infohnews-' + selectedService + '-field').closest('tr').show();
                        }
                        toggleServiceFields();
                        $('#infohnews_api_service_select').on('change', toggleServiceFields);
                    });
                ";
                wp_add_inline_script('infohnews-admin-js', $inline_script);
            }
        }
    });
}