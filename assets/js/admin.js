jQuery(document).ready(function($) {
    
    // AI生成按钮点击事件
    $('#generate-ai-content').on('click', function() {
        var button = $(this);
        var postId = button.data('post-id');
        var siteUrl = button.data('site-url');
        var siteTitle = button.data('site-title');
        var siteDescription = button.data('site-description');
        var siteKeywords = button.data('site-keywords');
        var statusDiv = $('#ai-generation-status');
        
        // 验证必要信息
        if (!siteUrl || !siteTitle) {
            statusDiv.html('<div class="notice notice-error"><p>缺少必要的网站信息，请先填写网站标题和链接。</p></div>');
            return;
        }
        
        // 禁用按钮并显示加载状态
        button.prop('disabled', true);
        button.html('<span class="dashicons dashicons-update spin"></span> ' + onenavAI.generating_text);
        statusDiv.html('<div class="notice notice-info"><p>正在生成内容，请稍候...</p></div>');
        
        // 发送AJAX请求
        $.ajax({
            url: onenavAI.ajax_url,
            type: 'POST',
            data: {
                action: 'generate_site_description',
                nonce: onenavAI.nonce,
                post_id: postId,
                site_url: siteUrl,
                site_title: siteTitle,
                site_description: siteDescription,
                site_keywords: siteKeywords
            },
            success: function(response) {
                if (response.success) {
                    // 更新编辑器内容
                    if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                        // 可视化编辑器
                        tinymce.get('content').setContent(response.data.content);
                    } else if ($('#content').length) {
                        // 文本编辑器
                        $('#content').val(response.data.content);
                    }
                    
                    statusDiv.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    
                    // 自动保存草稿
                    if (typeof wp !== 'undefined' && wp.autosave) {
                        wp.autosave.server.triggerSave();
                    }
                } else {
                    statusDiv.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                statusDiv.html('<div class="notice notice-error"><p>请求失败：' + error + '</p></div>');
            },
            complete: function() {
                // 恢复按钮状态
                button.prop('disabled', false);
                button.html('<span class="dashicons dashicons-admin-generic"></span> 生成AI介绍');
                
                // 3秒后隐藏状态消息
                setTimeout(function() {
                    statusDiv.fadeOut();
                }, 3000);
            }
        });
    });
    
    // 批量生成功能
    var batchStopped = false;
    
    $('#start-batch-generate').on('click', function() {
        var button = $(this);
        var progressContainer = $('#batch-progress');
        var progressBar = $('.progress-fill');
        var progressText = $('#progress-text');
        var resultsContainer = $('#batch-results');
        var stopButton = $('#stop-batch');
        
        // 获取选项
        var mode = $('input[name="batch_mode"]:checked').val();
        var limit = parseInt($('#batch-limit').val()) || 0;
        
        button.prop('disabled', true);
        button.text('正在处理...');
        progressContainer.show();
        resultsContainer.empty();
        batchStopped = false;
        
        // 开始批量生成
        batchGenerate(0, [], mode, limit);
        
        function batchGenerate(offset, allResults, mode, limit) {
            if (batchStopped) {
                button.prop('disabled', false);
                button.text('开始批量生成');
                progressText.text('处理已停止');
                return;
            }
            
            var action = mode === 'replace_all' ? 'batch_replace_content' : 'batch_generate_descriptions';
            
            $.ajax({
                url: onenavAI.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    nonce: onenavAI.nonce,
                    offset: offset,
                    mode: mode,
                    limit: limit
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        
                        // 更新结果数组
                        if (data.results) {
                            allResults = allResults.concat(data.results);
                        }
                        
                        // 计算进度
                        var totalToProcess = limit > 0 ? Math.min(limit, data.total) : data.total;
                        var currentProgress = Math.min(100, ((offset + data.processed) / totalToProcess) * 100);
                        
                        // 更新进度条
                        progressBar.css('width', currentProgress + '%');
                        progressText.text('已处理 ' + (offset + data.processed) + ' / ' + totalToProcess + ' 个网址' + 
                                        (data.success ? ' (成功: ' + data.success + ')' : ''));
                        
                        // 显示错误信息
                        if (data.errors && data.errors.length > 0) {
                            var errorHtml = '<div class="notice notice-warning"><p><strong>处理错误：</strong></p><ul>';
                            data.errors.forEach(function(error) {
                                errorHtml += '<li>' + error + '</li>';
                            });
                            errorHtml += '</ul></div>';
                            resultsContainer.append(errorHtml);
                        }
                        
                        // 显示结果
                        if (allResults.length > 0) {
                            updateResults(allResults);
                        }
                        
                        if (data.has_more && !batchStopped) {
                            // 继续处理
                            setTimeout(function() {
                                batchGenerate(data.current_offset, allResults, mode, limit);
                            }, 1000);
                        } else {
                            // 完成
                            button.prop('disabled', false);
                            button.text('开始批量生成');
                            progressText.text('批量处理完成！总共处理了 ' + (offset + data.processed) + ' 个网址');
                        }
                    } else {
                        button.prop('disabled', false);
                        button.text('开始批量生成');
                        progressText.text('处理失败：' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    button.prop('disabled', false);
                    button.text('开始批量生成');
                    progressText.text('请求失败：' + error);
                }
            });
        }
        
        function updateResults(results) {
            var html = '<h3>处理结果</h3><table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr><th>网站标题</th><th>状态</th><th>详情</th></tr></thead><tbody>';
            
            results.forEach(function(result) {
                var statusClass = result.status === 'success' ? 'success' : 'error';
                var statusText = result.status === 'success' ? '成功' : '失败';
                var details = result.message || '';
                html += '<tr><td>' + result.title + '</td><td><span class="status-' + statusClass + '">' + statusText + '</span></td><td>' + details + '</td></tr>';
            });
            
            html += '</tbody></table>';
            resultsContainer.html(html);
        }
    });
    
    // 停止批量处理
    $('#stop-batch').on('click', function() {
        batchStopped = true;
        $(this).prop('disabled', true);
        $('#progress-text').text('正在停止处理...');
    });
    
    // 设置页面的API密钥显示/隐藏切换
    $('#deepseek_api_key').after('<button type="button" id="toggle-api-key" class="button">显示</button>');
    
    $('#toggle-api-key').on('click', function() {
        var input = $('#deepseek_api_key');
        var button = $(this);
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            button.text('隐藏');
        } else {
            input.attr('type', 'password');
            button.text('显示');
        }
    });
    
    // 测试API连接
    if ($('#test-api-connection').length === 0) {
        $('#deepseek_api_url').after('<button type="button" id="test-api-connection" class="button" style="margin-left: 10px;">测试连接</button>');
    }
    
    $('#test-api-connection').on('click', function() {
        var button = $(this);
        var apiKey = $('#deepseek_api_key').val();
        var apiUrl = $('#deepseek_api_url').val();
        var aiModel = $('#ai_model').val();

        if (!apiKey || !apiUrl) {
            alert('请先填写API密钥和URL');
            return;
        }

        button.prop('disabled', true);
        button.text('测试中...');

        $.ajax({
            url: onenavAI.ajax_url,
            type: 'POST',
            data: {
                action: 'test_api_connection',
                nonce: onenavAI.nonce,
                api_key: apiKey,
                api_url: apiUrl,
                ai_model: aiModel
            },
            success: function(response) {
                if (response.success) {
                    alert('API连接测试成功！');
                } else {
                    alert('API连接测试失败：' + response.data.message);
                }
            },
            error: function() {
                alert('测试请求失败');
            },
            complete: function() {
                button.prop('disabled', false);
                button.text('测试连接');
            }
        });
    });
    
    // 内容结构预览
    $('#content_structure').after('<div id="structure-preview" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 4px solid #0073aa;"><strong>预览结构：</strong><div id="structure-content"></div></div>');
    
    function updateStructurePreview() {
        var structure = $('#content_structure').val();
        var lines = structure.split('\n');
        var html = '<ol>';
        
        lines.forEach(function(line) {
            line = line.trim();
            if (line) {
                html += '<li>' + line + '</li>';
            }
        });
        
        html += '</ol>';
        $('#structure-content').html(html);
    }
    
    $('#content_structure').on('input', updateStructurePreview);
    updateStructurePreview(); // 初始化预览
    
    // 提示词字数统计
    $('#custom_prompt').after('<div id="prompt-counter" style="margin-top: 5px; color: #666; font-size: 12px;">字数：0</div>');
    
    function updatePromptCounter() {
        var text = $('#custom_prompt').val();
        var count = text.length;
        $('#prompt-counter').text('字数：' + count);
        
        if (count > 500) {
            $('#prompt-counter').css('color', '#d63638');
        } else {
            $('#prompt-counter').css('color', '#666');
        }
    }
    
    $('#custom_prompt').on('input', updatePromptCounter);
    updatePromptCounter(); // 初始化计数
    
    // 添加旋转动画样式
    if (!$('#onenav-ai-spinner-style').length) {
        $('head').append('<style id="onenav-ai-spinner-style">.spin { animation: spin 1s linear infinite; } @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>');
    }
});

// 古腾堡编辑器支持
if (typeof wp !== 'undefined' && wp.blocks) {
    wp.domReady(function() {
        // 检查是否为sites文章类型
        var postType = wp.data.select('core/editor').getCurrentPostType();
        
        if (postType === 'sites') {
            // 在古腾堡编辑器中添加AI生成按钮
            var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
            var Button = wp.components.Button;
            var PanelBody = wp.components.PanelBody;
            var createElement = wp.element.createElement;
            var Fragment = wp.element.Fragment;
            var useState = wp.element.useState;
            
            var OneNavAIPanel = function() {
                var postId = wp.data.select('core/editor').getCurrentPostId();
                var postMeta = wp.data.select('core/editor').getEditedPostAttribute('meta') || {};
                var postTitle = wp.data.select('core/editor').getEditedPostAttribute('title');
                
                var siteUrl = postMeta._sites_link || '';
                var siteDescription = postMeta._sites_sescribe || '';
                
                var [isGenerating, setIsGenerating] = useState(false);
                var [status, setStatus] = useState('');
                
                var generateContent = function() {
                    if (!siteUrl || !postTitle) {
                        setStatus('缺少必要的网站信息');
                        return;
                    }
                    
                    setIsGenerating(true);
                    setStatus('正在生成内容...');
                    
                    jQuery.ajax({
                        url: onenavAI.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'generate_site_description',
                            nonce: onenavAI.nonce,
                            post_id: postId,
                            site_url: siteUrl,
                            site_title: postTitle,
                            site_description: siteDescription,
                            site_keywords: ''
                        },
                        success: function(response) {
                            if (response.success) {
                                // 更新古腾堡编辑器内容
                                var blocks = wp.blocks.parse(response.data.content);
                                wp.data.dispatch('core/editor').resetBlocks(blocks);
                                setStatus('生成成功！');
                            } else {
                                setStatus('生成失败：' + response.data.message);
                            }
                        },
                        error: function() {
                            setStatus('请求失败');
                        },
                        complete: function() {
                            setIsGenerating(false);
                            setTimeout(function() {
                                setStatus('');
                            }, 3000);
                        }
                    });
                };
                
                return createElement(
                    PluginDocumentSettingPanel,
                    {
                        name: 'onenav-ai-generator',
                        title: 'AI 生成网站介绍',
                        className: 'onenav-ai-generator-panel'
                    },
                    createElement(
                        Fragment,
                        null,
                        createElement(
                            'p',
                            { style: { marginBottom: '10px' } },
                            '基于网站信息自动生成详细介绍'
                        ),
                        createElement(
                            Button,
                            {
                                isPrimary: true,
                                isBusy: isGenerating,
                                disabled: isGenerating,
                                onClick: generateContent
                            },
                            isGenerating ? '生成中...' : '生成AI介绍'
                        ),
                        status && createElement(
                            'div',
                            { 
                                style: { 
                                    marginTop: '10px', 
                                    padding: '8px', 
                                    backgroundColor: status.includes('成功') ? '#d4edda' : '#f8d7da',
                                    color: status.includes('成功') ? '#155724' : '#721c24',
                                    borderRadius: '4px',
                                    fontSize: '12px'
                                } 
                            },
                            status
                        )
                    )
                );
            };
            
            wp.plugins.registerPlugin('onenav-ai-generator', {
                render: OneNavAIPanel
            });
        }
    });
}