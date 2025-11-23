document.addEventListener('DOMContentLoaded', function() {
    (function($) {
        'use strict';

        if (typeof infohnews_ajax_object === 'undefined') {
            console.error('INFOHNEWS Admin JS: infohnews_ajax_object not found.');
            return;
        }

        const $metabox = $('#infohnews-metabox-content');
        if ($metabox.length) {
            const $button = $metabox.find('#infohnews-generate-button');
            $button.on('click', function() {
                const postId = $(this).data('post-id');
                const $spinner = $metabox.find('#infohnews-spinner');
                const $statusMessage = $metabox.find('#infohnews-status-message');
                const customPromptValue = $metabox.find('#infohnews-custom-prompt').val();
                const negativePromptValue = $metabox.find('#infohnews-negative-prompt').val();
                
                $spinner.addClass('is-active');
                $button.prop('disabled', true);
                $statusMessage.text(infohnews_ajax_object.generating).removeClass('infohnews-error infohnews-success').show();
                
                $.ajax({
                    url: infohnews_ajax_object.ajax_url,
                    type: 'POST',
                    timeout: 300000,
                    data: {
                        action: 'infohnews_generate_image',
                        nonce: infohnews_ajax_object.nonce,
                        post_id: postId,
                        prompt: customPromptValue,
                        negative_prompt: negativePromptValue
                    },
                    success: function(response) {
                        if (response.success) {
                            $statusMessage.text(response.data.message).removeClass('infohnews-error').addClass('infohnews-success');
                            
                            if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                                const coreEditorDispatch = wp.data.dispatch('core/editor');
                                if (coreEditorDispatch && typeof coreEditorDispatch.editPost === 'function') {
                                    coreEditorDispatch.editPost({ featured_media: 0 }); 
                                    setTimeout(function() { 
                                        coreEditorDispatch.editPost({ featured_media: response.data.attachment_id }); 
                                    }, 100);
                                    return;
                                }
                            } 
                            
                            if ($('#postimagediv').length && response.data.preview_url) {
                                const $postImageDiv = $('#postimagediv');
                                const $insideDiv = $postImageDiv.find('.inside');
                                const removeImageText = 'Remover imagem destacada'; // Nota: Esta string ainda está hardcoded pois não veio do wp_localize_script
                                const newHtml = '<p><a href="#" id="set-post-thumbnail"><img src="' + response.data.preview_url + '" alt="" style="max-width:100%; height:auto;" /></a></p>' +
                                                '<p class="hide-if-no-js"><a href="#" id="remove-post-thumbnail">' + removeImageText + '</a></p>';
                                
                                $insideDiv.html(newHtml);
                                if ($('#_thumbnail_id').length) {
                                    $('#_thumbnail_id').val(response.data.attachment_id);
                                } else {
                                    $insideDiv.append('<input type="hidden" id="_thumbnail_id" name="_thumbnail_id" value="' + response.data.attachment_id + '">');
                                }
                            }
                        } else {
                            // ========================================================================
                            //  CORREÇÃO: Usa variável traduzível
                            // ========================================================================
                            $statusMessage.text(infohnews_ajax_object.error_prefix + (response.data ? response.data.message : infohnews_ajax_object.unknown_error)).removeClass('infohnews-success').addClass('infohnews-error');
                            // ========================================================================
                            //  FIM DA CORREÇÃO
                            // ========================================================================
                        }
                    },
                    // MODIFICADO: Esta função agora lê a resposta JSON em caso de erro.
                    error: function(jqXHR, textStatus, errorThrown) {
                        // ========================================================================
                        //  CORREÇÃO: Usa variáveis traduzíveis
                        // ========================================================================
                        let errorMessage = infohnews_ajax_object.ajax_error_prefix + textStatus + infohnews_ajax_object.status_prefix + jqXHR.status + ')';
                        if (jqXHR.responseText) {
                            try {
                                const response = JSON.parse(jqXHR.responseText);
                                if (response && response.data && response.data.message) {
                                    // Exibe a mensagem de erro real vinda do PHP
                                    errorMessage = infohnews_ajax_object.error_prefix + response.data.message; 
                                }
                            } catch (e) {
                                // A resposta não foi JSON, exibe o erro HTTP padrão
                                errorMessage = infohnews_ajax_object.ajax_error_prefix + textStatus + infohnews_ajax_object.status_prefix + jqXHR.status + ' ' + jqXHR.statusText + ')';
                            }
                        }
                        // ========================================================================
                        //  FIM DA CORREÇÃO
                        // ========================================================================
                        $statusMessage.text(errorMessage).addClass('infohnews-error');
                    },
                    complete: function() {
                        $spinner.removeClass('is-active');
                        $button.prop('disabled', false);
                    }
                });
            });
        }

        $(document).on('click', '.infohnews-generate-from-list', function(e) {
            e.preventDefault();
            const $button = $(this);
            const postId = $button.data('post-id');
            const $feedbackDiv = $('#infohnews-feedback-' + postId);
            const postTitle = $button.closest('tr').find('.row-title').text().trim();
            const $td = $button.closest('td');

            if (!postId || !postTitle) { return; }
            
            $button.hide();
            $feedbackDiv.html('<span class="spinner is-active" style="float:left; margin-top:0;"></span>' + infohnews_ajax_object.generating);
            
            $.ajax({
                url: infohnews_ajax_object.ajax_url,
                type: 'POST',
                timeout: 300000,
                data: {
                    action: 'infohnews_generate_image',
                    nonce: infohnews_ajax_object.nonce,
                    post_id: postId,
                    prompt: postTitle,
                    is_from_list: 'true'
                },
                success: function(response) {
                    if (response && response.success && response.data && response.data.image_html) {
                        $td.html(response.data.image_html);
                    } else {
                        // ========================================================================
                        //  CORREÇÃO: Usa variáveis traduzíveis
                        // ========================================================================
                        const errorMsg = (response && response.data && response.data.message) ? response.data.message : infohnews_ajax_object.unknown_error;
                        $feedbackDiv.html('<span style="color:red;">' + infohnews_ajax_object.failed_prefix + errorMsg + '</span>');
                        // ========================================================================
                        //  FIM DA CORREÇÃO
                        // ========================================================================
                        $button.show();
                    }
                },
                // MODIFICADO: Esta função também agora lê a resposta JSON em caso de erro.
                error: function(jqXHR, textStatus, errorThrown) {
                    // ========================================================================
                    //  CORREÇÃO: Usa variáveis traduzíveis
                    // ========================================================================
                    let errorMessage = infohnews_ajax_object.ajax_error_prefix + textStatus + infohnews_ajax_object.status_prefix + jqXHR.status + ')';
                    if (jqXHR.responseText) {
                        try {
                            const response = JSON.parse(jqXHR.responseText);
                            if (response && response.data && response.data.message) {
                                // Exibe a mensagem de erro real vinda do PHP
                                errorMessage = infohnews_ajax_object.failed_prefix + response.data.message;
                            }
                        } catch (e) {
                            // A resposta não foi JSON, exibe o erro HTTP padrão
                            errorMessage = infohnews_ajax_object.ajax_error_prefix + textStatus + infohnews_ajax_object.status_prefix + jqXHR.status + ' ' + jqXHR.statusText + ')';
                        }
                    }
                    // ========================================================================
                    //  FIM DA CORREÇÃO
                    // ========================================================================
                    $feedbackDiv.html('<span style="color:red;">' + errorMessage + '</span>');
                    $button.show();
                }
            });
        });

    })(jQuery);
});