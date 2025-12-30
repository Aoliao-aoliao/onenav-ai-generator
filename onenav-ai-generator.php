<?php
/**
 * Plugin Name: OneNav AI Generator
 * Plugin URI: https://github.com/Aoliao-aoliao/onenav-ai-generator
 * Description: 为OneNav导航主题的网址自动生成详情介绍信息，支持多种主流 AI 服务商（DeepSeek、阿里云通义千问、七牛云、豆包、OpenAI等）
 * Version: 1.2.3
 * Author: 草丛
 * Author URI: https://github.com/Aoliao-aoliao
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: onenav-ai-generator
 * Domain Path: /languages
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 检查WordPress环境
if (!function_exists('add_action')) {
    return;
}

// 定义插件常量
define('ONENAV_AI_GENERATOR_VERSION', '1.2.3');
define('ONENAV_AI_GENERATOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ONENAV_AI_GENERATOR_PLUGIN_PATH', plugin_dir_path(__FILE__));

class OneNavAIGenerator {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // OneNav 主题元数据字段名常量（注意：OneNav使用了非标准拼写）
    const META_LINK = '_sites_link';           // 网站链接
    const META_DESCRIPTION = '_sites_sescribe'; // 网站描述（注意拼写）
    const META_KEYWORDS = '_sites_keywords';   // 关键词

    /**
     * AI 服务商配置
     */
    private function get_ai_providers() {
        return array(
            'deepseek' => array(
                'name' => 'DeepSeek',
                'api_url' => 'https://api.deepseek.com/v1/chat/completions',
                'models' => array(
                    'deepseek-chat' => 'DeepSeek Chat',
                    'deepseek-coder' => 'DeepSeek Coder'
                ),
                'default_model' => 'deepseek-chat'
            ),
            'aliyun' => array(
                'name' => '阿里云通义千问',
                'api_url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions',
                'models' => array(
                    'qwen-plus' => '通义千问 Plus',
                    'qwen-turbo' => '通义千问 Turbo',
                    'qwen-max' => '通义千问 Max',
                    'qwen-long' => '通义千问 Long'
                ),
                'default_model' => 'qwen-plus'
            ),
            'qiniu' => array(
                'name' => '七牛云',
                'api_url' => 'https://llm-api.qiniu.com/v1/chat/completions',
                'models' => array(
                    'qwen-plus' => '通义千问 Plus',
                    'qwen-turbo' => '通义千问 Turbo',
                    'chatglm-6b' => 'ChatGLM-6B'
                ),
                'default_model' => 'qwen-plus'
            ),
            'doubao' => array(
                'name' => '豆包（字节跳动）',
                'api_url' => 'https://ark.cn-beijing.volces.com/api/v3/chat/completions',
                'models' => array(
                    'doubao-pro-128k' => '豆包 Pro 128K（推荐）',
                    'doubao-pro-32k' => '豆包 Pro 32K',
                    'doubao-lite-128k' => '豆包 Lite 128K',
                    'doubao-lite-32k' => '豆包 Lite 32K',
                    'doubao-character-128k' => '豆包角色版 128K',
                    'doubao-vision' => '豆包视觉版'
                ),
                'default_model' => 'doubao-pro-128k'
            ),
            'openai' => array(
                'name' => 'OpenAI',
                'api_url' => 'https://api.openai.com/v1/chat/completions',
                'models' => array(
                    'gpt-4' => 'GPT-4',
                    'gpt-4-turbo' => 'GPT-4 Turbo',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo'
                ),
                'default_model' => 'gpt-3.5-turbo'
            ),
            'custom' => array(
                'name' => '自定义 API',
                'api_url' => '',
                'models' => array(
                    'custom' => '自定义模型'
                ),
                'default_model' => 'custom'
            )
        );
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_generate_site_description', array($this, 'ajax_generate_site_description'));
        add_action('wp_ajax_batch_generate_descriptions', array($this, 'ajax_batch_generate_descriptions'));
        add_action('wp_ajax_preview_batch_sites', array($this, 'ajax_preview_batch_sites'));
        add_action('wp_ajax_batch_replace_content', array($this, 'ajax_batch_replace_content'));
        add_action('wp_ajax_test_api_connection', array($this, 'ajax_test_api_connection'));
        add_action('wp_ajax_get_site_statistics', array($this, 'ajax_get_site_statistics'));
        add_action('wp_ajax_generate_batch_content', array($this, 'ajax_generate_batch_content'));
        
        // 激活和停用钩子
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // 加载文本域
        load_plugin_textdomain('onenav-ai-generator', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function admin_init() {
        // 注册设置，添加数据清理回调
        register_setting(
            'onenav_ai_generator_settings',
            'onenav_ai_generator_options',
            array($this, 'sanitize_options')
        );
        
        // 添加设置部分
        add_settings_section(
            'onenav_ai_generator_api_section',
            __('API 设置', 'onenav-ai-generator'),
            array($this, 'api_section_callback'),
            'onenav_ai_generator_settings'
        );
        
        add_settings_section(
            'onenav_ai_generator_content_section',
            __('内容生成设置', 'onenav-ai-generator'),
            array($this, 'content_section_callback'),
            'onenav_ai_generator_settings'
        );
        
        // 添加设置字段
        add_settings_field(
            'ai_provider',
            __('AI 服务商', 'onenav-ai-generator'),
            array($this, 'ai_provider_field_callback'),
            'onenav_ai_generator_settings',
            'onenav_ai_generator_api_section'
        );

        add_settings_field(
            'ai_model',
            __('AI 模型', 'onenav-ai-generator'),
            array($this, 'ai_model_field_callback'),
            'onenav_ai_generator_settings',
            'onenav_ai_generator_api_section'
        );

        add_settings_field(
            'deepseek_api_key',
            __('API Key', 'onenav-ai-generator'),
            array($this, 'api_key_field_callback'),
            'onenav_ai_generator_settings',
            'onenav_ai_generator_api_section'
        );

        add_settings_field(
            'deepseek_api_url',
            __('API URL', 'onenav-ai-generator'),
            array($this, 'api_url_field_callback'),
            'onenav_ai_generator_settings',
            'onenav_ai_generator_api_section'
        );
        
        add_settings_field(
            'max_tokens',
            __('最大生成字数', 'onenav-ai-generator'),
            array($this, 'max_tokens_field_callback'),
            'onenav_ai_generator_settings',
            'onenav_ai_generator_content_section'
        );
        
        add_settings_field(
            'content_structure',
            __('内容结构模板', 'onenav-ai-generator'),
            array($this, 'content_structure_field_callback'),
            'onenav_ai_generator_settings',
            'onenav_ai_generator_content_section'
        );
        
        add_settings_field(
            'custom_prompt',
            __('自定义提示词', 'onenav-ai-generator'),
            array($this, 'custom_prompt_field_callback'),
            'onenav_ai_generator_settings',
            'onenav_ai_generator_content_section'
        );
    }
    

    
    public function add_admin_menu() {
        add_options_page(
            __('OneNav AI Generator 设置', 'onenav-ai-generator'),
            __('OneNav AI Generator', 'onenav-ai-generator'),
            'manage_options',
            'onenav-ai-generator',
            array($this, 'admin_page')
        );
        
        // 添加批量生成页面
        add_management_page(
            __('批量生成网址介绍', 'onenav-ai-generator'),
            __('批量AI生成', 'onenav-ai-generator'),
            'manage_options',
            'onenav-ai-batch-generate',
            array($this, 'batch_generate_page')
        );
    }
    
    public function enqueue_scripts() {
        // 前端脚本
    }
    
    public function admin_enqueue_scripts($hook) {
        // 只在编辑页面和插件设置页面加载脚本
        if (in_array($hook, array('post.php', 'post-new.php', 'settings_page_onenav-ai-generator', 'tools_page_onenav-ai-batch-generate'))) {
            wp_enqueue_script(
                'onenav-ai-generator-admin',
                ONENAV_AI_GENERATOR_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                ONENAV_AI_GENERATOR_VERSION,
                true
            );
            
            wp_enqueue_style(
                'onenav-ai-generator-admin',
                ONENAV_AI_GENERATOR_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                ONENAV_AI_GENERATOR_VERSION
            );
            
            // 传递数据到JavaScript
            wp_localize_script('onenav-ai-generator-admin', 'onenavAI', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('onenav_ai_generator_nonce'),
                'generating_text' => __('正在生成中...', 'onenav-ai-generator'),
                'generate_success' => __('生成成功！', 'onenav-ai-generator'),
                'generate_error' => __('生成失败，请检查设置', 'onenav-ai-generator')
            ));

            // 修复XSS：为内联脚本传递安全的nonce
            wp_add_inline_script('onenav-ai-generator-admin',
                'var onenavAINonce = ' . wp_json_encode(wp_create_nonce('onenav_ai_generator_nonce')) . ';',
                'before'
            );
        }
    }
    
    /**
     * 加密 API 密钥（使用 WordPress 标准方法）
     *
     * @param string $api_key 原始 API 密钥
     * @return string 加密后的密钥
     */
    private function encrypt_api_key($api_key) {
        if (empty($api_key)) {
            return '';
        }

        // 使用 WordPress 的 AUTH_SALT 作为加密密钥
        $salt = wp_salt('auth');
        $iv_length = 16;
        $iv = substr(wp_salt('nonce'), 0, $iv_length);

        // 使用 AES-256-CBC 加密
        $encrypted = openssl_encrypt(
            $api_key,
            'AES-256-CBC',
            $salt,
            0,
            $iv
        );

        return base64_encode($encrypted);
    }

    /**
     * 解密 API 密钥
     *
     * @param string $encrypted_key 加密的密钥
     * @return string|false 解密后的密钥，失败返回 false
     */
    private function decrypt_api_key($encrypted_key) {
        if (empty($encrypted_key)) {
            return '';
        }

        // 尝试解密，如果失败可能是旧版本未加密的密钥
        $salt = wp_salt('auth');
        $iv_length = 16;
        $iv = substr(wp_salt('nonce'), 0, $iv_length);

        $encrypted = base64_decode($encrypted_key);
        if ($encrypted === false) {
            // 可能是未加密的旧密钥
            return $encrypted_key;
        }

        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            $salt,
            0,
            $iv
        );

        // 如果解密失败，可能是未加密的密钥
        return $decrypted !== false ? $decrypted : $encrypted_key;
    }

    /**
     * 清理和验证选项数据
     *
     * @param array $input 用户输入的选项
     * @return array 清理后的选项
     */
    public function sanitize_options($input) {
        $sanitized = array();

        // 清理 AI 提供商选择
        $providers_config = $this->get_ai_providers();
        if (isset($input['ai_provider'])) {
            $providers = array_keys($providers_config);
            $provider = sanitize_text_field($input['ai_provider']);
            $sanitized['ai_provider'] = in_array($provider, $providers) ? $provider : 'deepseek';
        } else {
            $sanitized['ai_provider'] = 'deepseek';
        }

        // 清理 AI 模型选择，并验证模型是否属于选择的服务商
        if (isset($input['ai_model'])) {
            $model = sanitize_text_field($input['ai_model']);
            $current_provider = $sanitized['ai_provider'];

            // 验证模型是否在当前服务商的模型列表中
            if (isset($providers_config[$current_provider]['models'][$model])) {
                $sanitized['ai_model'] = $model;
            } else {
                // 如果模型不匹配，使用该服务商的默认模型
                $sanitized['ai_model'] = $providers_config[$current_provider]['default_model'];
            }
        } else {
            // 如果没有提供模型，使用当前服务商的默认模型
            $current_provider = $sanitized['ai_provider'];
            $sanitized['ai_model'] = $providers_config[$current_provider]['default_model'];
        }

        // 清理和加密 API 密钥
        if (isset($input['deepseek_api_key'])) {
            $api_key = sanitize_text_field($input['deepseek_api_key']);
            // 只有当密钥改变时才重新加密
            $old_options = get_option('onenav_ai_generator_options', array());
            $old_decrypted = !empty($old_options['deepseek_api_key'])
                ? $this->decrypt_api_key($old_options['deepseek_api_key'])
                : '';

            if ($api_key !== $old_decrypted) {
                $sanitized['deepseek_api_key'] = $this->encrypt_api_key($api_key);
            } else {
                $sanitized['deepseek_api_key'] = $old_options['deepseek_api_key'];
            }
        }

        // 清理 API URL
        if (isset($input['deepseek_api_url'])) {
            $sanitized['deepseek_api_url'] = esc_url_raw($input['deepseek_api_url']);
        }

        // 验证 max_tokens 范围
        if (isset($input['max_tokens'])) {
            $max_tokens = intval($input['max_tokens']);
            $sanitized['max_tokens'] = max(100, min(4000, $max_tokens));
        }

        // 清理其他文本字段
        if (isset($input['content_structure'])) {
            $sanitized['content_structure'] = sanitize_textarea_field($input['content_structure']);
        }

        if (isset($input['custom_prompt'])) {
            $sanitized['custom_prompt'] = sanitize_textarea_field($input['custom_prompt']);
        }

        return $sanitized;
    }

    public function activate() {
        // 插件激活时的操作
        $default_options = array(
            'ai_provider' => 'deepseek',
            'ai_model' => 'deepseek-chat',
            'deepseek_api_key' => '',
            'deepseek_api_url' => 'https://api.deepseek.com/v1/chat/completions',
            'max_tokens' => 2000,
            'content_structure' => "产品简介\n\n主要功能\n\n使用方法\n\n产品价格\n\n应用场景",
            'custom_prompt' => '请根据提供的网站信息，生成一份详细的网站介绍。要求内容专业、准确、有条理，包含产品简介、主要功能、使用方法、产品价格、应用场景等部分。'
        );

        add_option('onenav_ai_generator_options', $default_options);
    }
    
    public function deactivate() {
        // 插件停用时的操作
    }
    
    // 设置页面回调函数
    public function api_section_callback() {
        echo '<p>' . __('选择 AI 服务商并配置 API 相关设置', 'onenav-ai-generator') . '</p>';
    }

    public function content_section_callback() {
        echo '<p>' . __('配置内容生成相关参数', 'onenav-ai-generator') . '</p>';
    }

    public function ai_provider_field_callback() {
        $options = get_option('onenav_ai_generator_options');
        $current_provider = isset($options['ai_provider']) ? $options['ai_provider'] : 'deepseek';
        $providers = $this->get_ai_providers();

        echo '<select id="ai_provider" name="onenav_ai_generator_options[ai_provider]" class="regular-text">';
        foreach ($providers as $key => $provider) {
            $selected = ($current_provider === $key) ? 'selected' : '';
            echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($provider['name']) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('选择要使用的 AI 服务商。更换服务商后，请更新对应的 API Key 和 URL', 'onenav-ai-generator') . '</p>';

        // 添加 JavaScript 实现自动填充和动态切换
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var providers = <?php echo wp_json_encode($providers); ?>;

            function updateModelField(provider) {
                var config = providers[provider];
                if (!config) return;

                var $modelContainer = $('#ai_model').parent();
                var $description = $modelContainer.find('.description');
                var currentValue = $('#ai_model').val() || config.default_model;

                // 如果是自定义 API，显示文本框
                if (provider === 'custom') {
                    var $input = $('<input>')
                        .attr({
                            'type': 'text',
                            'id': 'ai_model',
                            'name': 'onenav_ai_generator_options[ai_model]',
                            'class': 'regular-text',
                            'placeholder': '输入模型名称，如：gpt-3.5-turbo',
                            'value': currentValue === 'custom' ? '' : currentValue
                        });
                    $('#ai_model').replaceWith($input);
                    $description.text('输入您要使用的 AI 模型名称（例如：gpt-3.5-turbo、llama2 等）');
                } else {
                    // 其他服务商，显示下拉菜单
                    var $select = $('<select>')
                        .attr({
                            'id': 'ai_model',
                            'name': 'onenav_ai_generator_options[ai_model]',
                            'class': 'regular-text'
                        });

                    $.each(config.models, function(key, name) {
                        $select.append($('<option></option>')
                            .attr('value', key)
                            .text(name));
                    });

                    $('#ai_model').replaceWith($select);
                    $select.val(config.default_model);
                    $description.text('选择要使用的 AI 模型。不同模型有不同的性能和价格');
                }
            }

            $('#ai_provider').on('change', function() {
                var provider = $(this).val();
                var config = providers[provider];

                if (config) {
                    // 自动填充 API URL
                    if (config.api_url) {
                        $('#deepseek_api_url').val(config.api_url);
                    }

                    // 更新模型字段
                    updateModelField(provider);
                }
            });
        });
        </script>
        <?php
    }

    public function ai_model_field_callback() {
        $options = get_option('onenav_ai_generator_options');
        $current_provider = isset($options['ai_provider']) ? $options['ai_provider'] : 'deepseek';
        $current_model = isset($options['ai_model']) ? $options['ai_model'] : 'deepseek-chat';
        $providers = $this->get_ai_providers();

        $models = isset($providers[$current_provider]['models']) ? $providers[$current_provider]['models'] : array();

        // 修复：如果当前模型不在当前服务商的模型列表中，使用默认模型
        if (!isset($models[$current_model])) {
            $current_model = isset($providers[$current_provider]['default_model'])
                ? $providers[$current_provider]['default_model']
                : key($models); // 使用第一个模型作为备选
        }

        // 如果是自定义 API，显示文本输入框；否则显示下拉菜单
        if ($current_provider === 'custom') {
            echo '<input type="text" id="ai_model" name="onenav_ai_generator_options[ai_model]" value="' . esc_attr($current_model) . '" class="regular-text" placeholder="输入模型名称，如：gpt-3.5-turbo" />';
            echo '<p class="description">' . __('输入您要使用的 AI 模型名称（例如：gpt-3.5-turbo、llama2 等）', 'onenav-ai-generator') . '</p>';
        } else {
            echo '<select id="ai_model" name="onenav_ai_generator_options[ai_model]" class="regular-text">';
            foreach ($models as $key => $name) {
                $selected = ($current_model === $key) ? 'selected' : '';
                echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($name) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">' . __('选择要使用的 AI 模型。不同模型有不同的性能和价格', 'onenav-ai-generator') . '</p>';
        }
    }

    public function api_key_field_callback() {
        $options = get_option('onenav_ai_generator_options');
        $encrypted_key = isset($options['deepseek_api_key']) ? $options['deepseek_api_key'] : '';

        // 修复：解密显示API密钥（仅用于编辑）
        $value = '';
        if (!empty($encrypted_key)) {
            $decrypted = $this->decrypt_api_key($encrypted_key);
            $value = $decrypted !== false ? $decrypted : $encrypted_key;
        }

        echo '<input type="password" id="deepseek_api_key" name="onenav_ai_generator_options[deepseek_api_key]" value="' . esc_attr($value) . '" class="regular-text" autocomplete="off" />';
        echo '<p class="description">' . __('请输入您的 API Key（加密存储）。不同服务商的获取方式不同', 'onenav-ai-generator') . '</p>';
    }

    public function api_url_field_callback() {
        $options = get_option('onenav_ai_generator_options');
        $value = isset($options['deepseek_api_url']) ? $options['deepseek_api_url'] : 'https://api.deepseek.com/v1/chat/completions';
        echo '<input type="url" id="deepseek_api_url" name="onenav_ai_generator_options[deepseek_api_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('AI API 接口地址。切换服务商时会自动填充，也可以手动修改', 'onenav-ai-generator') . '</p>';
    }
    
    public function max_tokens_field_callback() {
        $options = get_option('onenav_ai_generator_options');
        $value = isset($options['max_tokens']) ? $options['max_tokens'] : 2000;
        echo '<input type="number" id="max_tokens" name="onenav_ai_generator_options[max_tokens]" value="' . esc_attr($value) . '" min="100" max="4000" />';
        echo '<p class="description">' . __('生成内容的最大字数（100-4000）', 'onenav-ai-generator') . '</p>';
    }
    
    public function content_structure_field_callback() {
        $options = get_option('onenav_ai_generator_options');
        $value = isset($options['content_structure']) ? $options['content_structure'] : "产品简介\n\n主要功能\n\n使用方法\n\n产品价格\n\n应用场景";
        echo '<textarea id="content_structure" name="onenav_ai_generator_options[content_structure]" rows="8" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . __('定义生成内容的结构模板，每行一个部分标题', 'onenav-ai-generator') . '</p>';
    }
    
    public function custom_prompt_field_callback() {
        $options = get_option('onenav_ai_generator_options');
        $value = isset($options['custom_prompt']) ? $options['custom_prompt'] : '请根据提供的网站信息，生成一份详细的网站介绍。要求内容专业、准确、有条理，包含产品简介、主要功能、使用方法、产品价格、应用场景等部分。';
        echo '<textarea id="custom_prompt" name="onenav_ai_generator_options[custom_prompt]" rows="5" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . __('自定义AI生成的提示词，用于指导内容生成', 'onenav-ai-generator') . '</p>';
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('onenav_ai_generator_settings');
                do_settings_sections('onenav_ai_generator_settings');
                submit_button();
                ?>
            </form>
            
            <!-- 网址统计和生成功能区域 -->
            <div class="postbox" style="margin-top: 30px;">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('网址统计与批量生成', 'onenav-ai-generator'); ?></h2>
                </div>
                <div class="inside">
                    <div id="site-statistics" style="margin-bottom: 20px;">
                        <h3><?php _e('网址统计信息', 'onenav-ai-generator'); ?></h3>
                        <div id="stats-loading" style="display: none;">
                            <p><?php _e('正在加载统计信息...', 'onenav-ai-generator'); ?></p>
                        </div>
                        <div id="stats-content">
                            <div style="display: flex; gap: 20px; flex-wrap: wrap; align-items: center; background: #f9f9f9; padding: 15px; border-radius: 5px; border: 1px solid #ddd;">
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <strong><?php _e('总数:', 'onenav-ai-generator'); ?></strong>
                                    <span id="total-sites" style="color: #0073aa; font-weight: bold; font-size: 16px;">-</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <strong><?php _e('已完成:', 'onenav-ai-generator'); ?></strong>
                                    <span id="sites-with-content" style="color: #46b450; font-weight: bold; font-size: 16px;">-</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <strong><?php _e('待处理:', 'onenav-ai-generator'); ?></strong>
                                    <span id="sites-need-content" style="color: #dc3232; font-weight: bold; font-size: 16px;">-</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <strong><?php _e('完成度:', 'onenav-ai-generator'); ?></strong>
                                    <span id="completion-rate" style="color: #0073aa; font-weight: bold; font-size: 16px;">-</span>
                                </div>
                            </div>
                        </div>
                        <p>
                            <button id="refresh-stats" class="button"><?php _e('刷新统计', 'onenav-ai-generator'); ?></button>
                        </p>
                    </div>
                    
                    <div id="batch-generation-section">
                        <h3><?php _e('为已发布网址生成介绍', 'onenav-ai-generator'); ?></h3>
                        <p class="description"><?php _e('为所有没有详细介绍的已发布网址自动生成介绍内容。', 'onenav-ai-generator'); ?></p>
                        
                        <div id="generation-options" style="margin: 15px 0;">
                            <label>
                                <input type="number" id="generation-limit" value="10" min="1" max="100" style="width: 80px;">
                                <?php _e('每次处理数量（建议10-50个）', 'onenav-ai-generator'); ?>
                            </label>
                        </div>
                        
                        <div id="generation-controls">
                            <button id="start-generation" class="button button-primary"><?php _e('开始生成介绍', 'onenav-ai-generator'); ?></button>
                            <button id="stop-generation" class="button" style="display: none;"><?php _e('停止生成', 'onenav-ai-generator'); ?></button>
                        </div>
                        
                        <div id="generation-progress" style="display: none; margin-top: 20px;">
                            <h4><?php _e('生成进度', 'onenav-ai-generator'); ?></h4>
                            <div class="progress-bar" style="width: 100%; height: 25px; background: #f1f1f1; border-radius: 12px; overflow: hidden; border: 1px solid #ddd;">
                                <div id="progress-fill" style="height: 100%; background: linear-gradient(90deg, #0073aa, #005a87); width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold;"></div>
                            </div>
                            <div id="progress-details" style="margin-top: 10px;">
                                <p id="progress-text"><?php _e('准备开始...', 'onenav-ai-generator'); ?></p>
                                <div id="processing-info" style="display: none; background: #f9f9f9; padding: 10px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #0073aa;">
                                    <p style="margin: 0; font-size: 14px;"><strong><?php _e('正在处理:', 'onenav-ai-generator'); ?></strong> <span id="current-processing">-</span></p>
                                    <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;"><strong><?php _e('下一个:', 'onenav-ai-generator'); ?></strong> <span id="next-processing">-</span></p>
                                </div>
                                <p id="progress-stats"></p>
                            </div>
                        </div>
                        
                        <div id="generation-results" style="margin-top: 20px;"></div>
                    </div>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                var generationInProgress = false;
                var currentBatch = 0;
                var totalToProcess = 0;
                var processed = 0;
                var successful = 0;
                var failed = 0;
                
                // 页面加载时获取统计信息
                loadSiteStatistics();
                
                // 刷新统计按钮
                $('#refresh-stats').click(function() {
                    loadSiteStatistics();
                });
                
                // 开始生成按钮
                $('#start-generation').click(function() {
                    if (generationInProgress) return;
                    
                    var limit = parseInt($('#generation-limit').val()) || 10;
                    startGeneration(limit);
                });
                
                // 停止生成按钮
                $('#stop-generation').click(function() {
                    stopGeneration();
                });
                
                function loadSiteStatistics() {
                    $('#stats-loading').show();
                    $('#stats-content').hide();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'get_site_statistics',
                            nonce: '<?php echo wp_create_nonce('onenav_ai_generator_nonce'); ?>'
                        },
                        success: function(response) {
                            $('#stats-loading').hide();
                            $('#stats-content').show();
                            
                            if (response.success) {
                                var data = response.data;
                                $('#total-sites').text(data.total);
                                $('#sites-with-content').text(data.with_content);
                                $('#sites-need-content').text(data.need_content);
                                $('#completion-rate').text(data.completion_rate + '%');
                            } else {
                                alert('获取统计信息失败：' + response.data.message);
                            }
                        },
                        error: function() {
                            $('#stats-loading').hide();
                            $('#stats-content').show();
                            alert('获取统计信息时发生错误');
                        }
                    });
                }
                
                function startGeneration(limit) {
                    generationInProgress = true;
                    currentBatch = 0;
                    processed = 0;
                    successful = 0;
                    failed = 0;
                    
                    $('#start-generation').hide();
                    $('#stop-generation').show();
                    $('#generation-progress').show();
                    $('#generation-results').empty();
                    $('#processing-info').hide(); // 初始隐藏，等有数据时再显示
                    
                    updateProgress(0, '正在获取需要处理的网址...');
                    
                    // 开始批量生成
                    processBatch(limit);
                }
                
                function processBatch(limit) {
                    if (!generationInProgress) return;
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'generate_batch_content',
                            nonce: '<?php echo wp_create_nonce('onenav_ai_generator_nonce'); ?>',
                            limit: limit,
                            offset: currentBatch * limit
                        },
                        success: function(response) {
                            if (response.success) {
                                var data = response.data;
                                
                                if (currentBatch === 0) {
                                    totalToProcess = data.total_to_process;
                                }
                                
                                processed += data.processed;
                                successful += data.successful;
                                failed += data.failed;
                                
                                var progressPercent = totalToProcess > 0 ? Math.round((processed / totalToProcess) * 100) : 100;
                                updateProgress(progressPercent, data.message);
                                updateStats();
                                
                                // 更新当前处理信息
                                if (data.current_processing || data.next_processing) {
                                    $('#processing-info').show();
                                    $('#current-processing').text(data.current_processing || '-');
                                    $('#next-processing').text(data.next_processing || '无');
                                }
                                
                                // 显示本批次结果
                                if (data.results && data.results.length > 0) {
                                    displayBatchResults(data.results);
                                }
                                
                                // 检查是否还有更多需要处理
                                if (data.has_more && generationInProgress) {
                                    currentBatch++;
                                    setTimeout(function() {
                                        processBatch(limit);
                                    }, 1000); // 延迟1秒继续下一批
                                } else {
                                    completeGeneration();
                                }
                            } else {
                                alert('生成失败：' + response.data.message);
                                stopGeneration();
                            }
                        },
                        error: function() {
                            alert('生成过程中发生错误');
                            stopGeneration();
                        }
                    });
                }
                
                function stopGeneration() {
                    generationInProgress = false;
                    $('#start-generation').show();
                    $('#stop-generation').hide();
                    $('#processing-info').hide();
                    updateProgress(processed > 0 ? Math.round((processed / totalToProcess) * 100) : 0, '生成已停止');
                }
                
                function completeGeneration() {
                    generationInProgress = false;
                    $('#start-generation').show();
                    $('#stop-generation').hide();
                    $('#processing-info').hide();
                    updateProgress(100, '生成完成！');
                    
                    // 刷新统计信息
                    setTimeout(function() {
                        loadSiteStatistics();
                    }, 1000);
                }
                
                function updateProgress(percent, message) {
                    $('#progress-fill').css('width', percent + '%').text(percent + '%');
                    $('#progress-text').text(message);
                }
                
                function updateStats() {
                    var statsText = '已处理: ' + processed;
                    if (totalToProcess > 0) {
                        statsText += ' / ' + totalToProcess;
                    }
                    statsText += ' | 成功: ' + successful + ' | 失败: ' + failed;
                    $('#progress-stats').text(statsText);
                }
                
                function displayBatchResults(results) {
                    var html = '<div class="batch-result" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">';
                    html += '<h4>批次 ' + (currentBatch + 1) + ' 处理结果：</h4>';
                    html += '<ul>';
                    
                    results.forEach(function(result) {
                        var statusClass = result.success ? 'success' : 'error';
                        var statusColor = result.success ? '#46b450' : '#dc3232';
                        html += '<li style="color: ' + statusColor + '; margin-bottom: 5px;">';
                        html += '<strong>' + result.title + '</strong> - ' + result.message;
                        html += '</li>';
                    });
                    
                    html += '</ul></div>';
                    $('#generation-results').prepend(html);
                }
            });
            </script>
        </div>
        <?php
    }
    
    public function batch_generate_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('批量生成网址介绍', 'onenav-ai-generator'); ?></h1>
            <div id="batch-generate-container">
                <div class="batch-options">
                    <h3><?php _e('批量生成选项', 'onenav-ai-generator'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('生成模式', 'onenav-ai-generator'); ?></th>
                            <td>
                                <label>
                                    <input type="radio" name="batch_mode" value="skip_existing" checked>
                                    <?php _e('仅为没有内容的网址生成介绍', 'onenav-ai-generator'); ?>
                                    <p class="description"><?php _e('跳过已有详细介绍的网址（内容长度>100字符）', 'onenav-ai-generator'); ?></p>
                                </label>
                                <br><br>
                                <label>
                                    <input type="radio" name="batch_mode" value="replace_all">
                                    <span style="color: #d63638; font-weight: bold;"><?php _e('完全替换所有网址的现有内容', 'onenav-ai-generator'); ?></span>
                                    <p class="description" style="color: #d63638;"><?php _e('⚠️ 警告：此操作将完全替换所有已发布网址的现有内容，无法撤销！', 'onenav-ai-generator'); ?></p>
                                </label>
                            </td>
                        </tr>
                        <tr id="replace-confirmation" style="display:none;">
                            <th scope="row"><?php _e('安全确认', 'onenav-ai-generator'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="confirm-replace" required>
                                    <?php _e('我确认要替换所有现有内容，并理解此操作不可撤销', 'onenav-ai-generator'); ?>
                                </label>
                                <br><br>
                                <label>
                                    <input type="text" id="confirm-text" placeholder="<?php _e('请输入"确认替换"', 'onenav-ai-generator'); ?>" style="width: 200px;">
                                    <p class="description"><?php _e('请输入"确认替换"来确认此操作', 'onenav-ai-generator'); ?></p>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('处理数量', 'onenav-ai-generator'); ?></th>
                            <td>
                                <input type="number" id="batch-limit" value="0" min="0" max="1000" style="width: 100px;">
                                <p class="description"><?php _e('限制处理的网址数量，0表示处理全部（建议首次测试时设置较小数值）', 'onenav-ai-generator'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="batch-actions">
                    <button id="start-batch-generate" class="button button-primary" disabled><?php _e('开始批量生成', 'onenav-ai-generator'); ?></button>
                    <button id="preview-batch" class="button"><?php _e('预览将要处理的网址', 'onenav-ai-generator'); ?></button>
                </div>
                
                <div id="batch-preview" style="display:none; margin-top: 20px;">
                    <h3><?php _e('预览结果', 'onenav-ai-generator'); ?></h3>
                    <div id="preview-content"></div>
                </div>
                
                <div id="batch-progress" style="display:none; margin-top: 20px;">
                    <h3><?php _e('处理进度', 'onenav-ai-generator'); ?></h3>
                    <div class="progress-bar" style="width: 100%; height: 20px; background: #f1f1f1; border-radius: 10px; overflow: hidden;">
                        <div class="progress-fill" style="height: 100%; background: #0073aa; width: 0%; transition: width 0.3s;"></div>
                    </div>
                    <p id="progress-text"></p>
                    <button id="stop-batch" class="button" style="margin-top: 10px;"><?php _e('停止处理', 'onenav-ai-generator'); ?></button>
                </div>
                
                <div id="batch-results" style="margin-top: 20px;"></div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // 监听生成模式变化
                $('input[name="batch_mode"]').change(function() {
                    var mode = $(this).val();
                    if (mode === 'replace_all') {
                        $('#replace-confirmation').show();
                        $('#start-batch-generate').prop('disabled', true);
                    } else {
                        $('#replace-confirmation').hide();
                        $('#start-batch-generate').prop('disabled', false);
                    }
                    updateStartButton();
                });
                
                // 监听确认选项变化
                $('#confirm-replace, #confirm-text').on('change keyup', function() {
                    updateStartButton();
                });
                
                function updateStartButton() {
                    var mode = $('input[name="batch_mode"]:checked').val();
                    var canStart = true;
                    
                    if (mode === 'replace_all') {
                        var isChecked = $('#confirm-replace').is(':checked');
                        var textConfirm = $('#confirm-text').val() === '确认替换';
                        canStart = isChecked && textConfirm;
                    }
                    
                    $('#start-batch-generate').prop('disabled', !canStart);
                }
                
                // 预览功能
                $('#preview-batch').click(function() {
                    var mode = $('input[name="batch_mode"]:checked').val();
                    var limit = parseInt($('#batch-limit').val()) || 0;
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'preview_batch_sites',
                            nonce: '<?php echo wp_create_nonce('onenav_ai_generator_nonce'); ?>',
                            mode: mode,
                            limit: limit
                        },
                        success: function(response) {
                            if (response.success) {
                                var html = '<p><strong>将要处理 ' + response.data.total + ' 个网址：</strong></p>';
                                html += '<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">';
                                response.data.sites.forEach(function(site) {
                                    var status = site.has_content ? '（已有内容）' : '（无内容）';
                                    html += '<div style="margin-bottom: 5px;">';
                                    html += '<strong>' + site.title + '</strong> - ' + site.url + ' ' + status;
                                    html += '</div>';
                                });
                                html += '</div>';
                                $('#preview-content').html(html);
                                $('#batch-preview').show();
                            } else {
                                alert('预览失败：' + response.data.message);
                            }
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }
    
    // AJAX处理函数
    public function ajax_generate_site_description() {
        check_ajax_referer('onenav_ai_generator_nonce', 'nonce');

        // 修复：统一使用 manage_options 权限，防止普通编辑者滥用API
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('权限不足：仅管理员可以使用 AI 生成功能', 'onenav-ai-generator')
            ));
            wp_die();
        }
        
        $post_id = intval($_POST['post_id']);
        $site_url = sanitize_url($_POST['site_url']);
        $site_title = sanitize_text_field($_POST['site_title']);
        $site_description = sanitize_text_field($_POST['site_description']);
        $site_keywords = sanitize_text_field($_POST['site_keywords']);
        
        // 如果有post_id，尝试获取更多网站信息
        $additional_info = '';
        if ($post_id > 0) {
            $post_meta = get_post_meta($post_id);
            $site_country = isset($post_meta['_sites_country']) ? $post_meta['_sites_country'][0] : '';
            $site_order = isset($post_meta['_sites_order']) ? $post_meta['_sites_order'][0] : '';
            
            if ($site_country) {
                $additional_info .= "网站所属国家/地区：{$site_country}\n";
            }
        }
        
        $generated_content = $this->generate_content($site_url, $site_title, $site_description, $site_keywords, $additional_info);
        
        if ($generated_content) {
            // 更新文章内容
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $generated_content
            ));
            
            wp_send_json_success(array(
                'content' => $generated_content,
                'message' => __('生成成功！', 'onenav-ai-generator')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('生成失败，请检查API设置', 'onenav-ai-generator')
            ));
        }
    }
    
    public function ajax_batch_generate_descriptions() {
        check_ajax_referer('onenav_ai_generator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'onenav-ai-generator'));
        }
        
        // 获取需要生成介绍的文章
        $posts = get_posts(array(
            'post_type' => 'sites', // OneNav的网址文章类型
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => self::META_LINK,
                    'compare' => 'EXISTS'
                )
            )
        ));

        // 修复N+1查询：预加载所有文章的元数据
        $post_ids = wp_list_pluck($posts, 'ID');
        update_meta_cache('post', $post_ids);

        $results = array();
        $processed = 0;
        $total = count($posts);

        foreach ($posts as $post) {
            // 检查是否已有详细内容
            if (strlen(trim($post->post_content)) > 100) {
                continue; // 跳过已有内容的文章
            }

            $site_url = get_post_meta($post->ID, self::META_LINK, true);
            $site_title = $post->post_title;
            $site_description = get_post_meta($post->ID, self::META_DESCRIPTION, true);
            $site_keywords = wp_get_post_terms($post->ID, 'sites_tag', array('fields' => 'names'));
            $site_keywords = is_array($site_keywords) ? implode(', ', $site_keywords) : '';
            
            $generated_content = $this->generate_content($site_url, $site_title, $site_description, $site_keywords);
            
            if ($generated_content) {
                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_content' => $generated_content
                ));
                $results[] = array(
                    'id' => $post->ID,
                    'title' => $site_title,
                    'status' => 'success'
                );
            } else {
                $results[] = array(
                    'id' => $post->ID,
                    'title' => $site_title,
                    'status' => 'failed'
                );
            }
            
            $processed++;
            
            // 避免超时，每次处理10个后返回进度
            if ($processed % 10 == 0) {
                wp_send_json_success(array(
                    'progress' => ($processed / $total) * 100,
                    'processed' => $processed,
                    'total' => $total,
                    'results' => $results,
                    'continue' => true
                ));
            }
        }
        
        wp_send_json_success(array(
            'progress' => 100,
            'processed' => $processed,
            'total' => $total,
            'results' => $results,
            'continue' => false
        ));
    }
    
    public function ajax_test_api_connection() {
        check_ajax_referer('onenav_ai_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'onenav-ai-generator'));
        }

        $api_key = sanitize_text_field($_POST['api_key']);
        $api_url = sanitize_url($_POST['api_url']);
        $ai_model = isset($_POST['ai_model']) ? sanitize_text_field($_POST['ai_model']) : 'deepseek-chat';

        if (empty($api_key) || empty($api_url)) {
            wp_send_json_error(array(
                'message' => __('API密钥和URL不能为空', 'onenav-ai-generator')
            ));
        }

        // 发送测试请求（使用选择的模型）
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode(array(
                'model' => $ai_model,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => '测试连接'
                    )
                ),
                'max_tokens' => 10
            )),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => __('连接失败：', 'onenav-ai-generator') . $response->get_error_message()
            ));
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code === 200) {
            $data = json_decode($body, true);
            if (isset($data['choices'])) {
                wp_send_json_success(array(
                    'message' => __('API连接测试成功！', 'onenav-ai-generator')
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('API响应格式异常', 'onenav-ai-generator')
                ));
            }
        } else {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : __('未知错误', 'onenav-ai-generator');
            wp_send_json_error(array(
                'message' => __('API错误：', 'onenav-ai-generator') . $error_message
            ));
        }
    }
    
    public function ajax_preview_batch_sites() {
        check_ajax_referer('onenav_ai_generator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'onenav-ai-generator'));
        }
        
        $mode = sanitize_text_field($_POST['mode']);
        $limit = intval($_POST['limit']);
        
        $args = array(
            'post_type' => 'sites',
            'post_status' => 'publish',
            'posts_per_page' => $limit > 0 ? $limit : -1,
            'meta_query' => array(
                array(
                    'key' => 'url',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $posts = get_posts($args);
        $sites = array();
        $total = 0;
        
        foreach ($posts as $post) {
            $url = get_post_meta($post->ID, 'url', true);
            if (empty($url)) continue;
            
            $has_content = strlen($post->post_content) > 100;
            
            // 根据模式过滤
            if ($mode === 'skip_existing' && $has_content) {
                continue;
            }
            
            $sites[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => $url,
                'has_content' => $has_content,
                'content_length' => strlen($post->post_content)
            );
            $total++;
        }
        
        wp_send_json_success(array(
            'total' => $total,
            'sites' => array_slice($sites, 0, 20) // 只返回前20个用于预览
        ));
    }
    
    public function ajax_batch_replace_content() {
        check_ajax_referer('onenav_ai_generator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'onenav-ai-generator'));
        }
        
        $mode = sanitize_text_field($_POST['mode']);
        $limit = intval($_POST['limit']);
        $offset = intval($_POST['offset']);
        $batch_size = 5; // 每批处理5个
        
        $args = array(
            'post_type' => 'sites',
            'post_status' => 'publish',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'meta_query' => array(
                array(
                    'key' => 'url',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        if ($limit > 0) {
            $args['posts_per_page'] = min($batch_size, $limit - $offset);
        }
        
        $posts = get_posts($args);
        $processed = 0;
        $success = 0;
        $errors = array();
        
        foreach ($posts as $post) {
            $url = get_post_meta($post->ID, 'url', true);
            if (empty($url)) continue;
            
            // 根据模式判断是否处理
            if ($mode === 'skip_existing' && strlen($post->post_content) > 100) {
                continue;
            }
            
            $processed++;
            
            // 生成内容
            $generated_content = $this->generate_content($url, $post->post_title);
            
            if (!empty($generated_content)) {
                // 更新文章内容
                $updated = wp_update_post(array(
                    'ID' => $post->ID,
                    'post_content' => $generated_content
                ));
                
                if ($updated && !is_wp_error($updated)) {
                    $success++;
                } else {
                    $errors[] = sprintf(__('更新失败：%s', 'onenav-ai-generator'), $post->post_title);
                }
            } else {
                $errors[] = sprintf(__('生成内容失败：%s', 'onenav-ai-generator'), $post->post_title);
            }
            
            // 避免超时，每处理一个稍作延迟
            usleep(100000); // 0.1秒
        }
        
        // 获取总数用于计算进度
        $total_args = array(
            'post_type' => 'sites',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'url',
                    'compare' => 'EXISTS'
                )
            ),
            'fields' => 'ids'
        );
        
        if ($mode === 'skip_existing') {
            $total_args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key' => 'post_content',
                    'value' => '',
                    'compare' => '='
                ),
                array(
                    'key' => 'post_content',
                    'value' => str_repeat('_', 100),
                    'compare' => 'RLIKE'
                )
            );
        }
        
        $total_posts = count(get_posts($total_args));
        $has_more = ($offset + $batch_size) < $total_posts;
        
        if ($limit > 0) {
            $has_more = $has_more && ($offset + $batch_size) < $limit;
        }
        
        wp_send_json_success(array(
            'processed' => $processed,
            'success' => $success,
            'errors' => $errors,
            'has_more' => $has_more,
            'total' => $total_posts,
            'current_offset' => $offset + $batch_size
        ));
    }
    
    private function build_prompt($site_url, $site_title, $site_description, $site_keywords) {
        $options = get_option('onenav_ai_generator_options', array());
        $custom_prompt = isset($options['custom_prompt']) ? $options['custom_prompt'] : '';
        $content_structure = isset($options['content_structure']) ? $options['content_structure'] : '';
        
        if (empty($custom_prompt)) {
            $custom_prompt = "你是一个专业的网站内容编写专家。请根据提供的网站信息，生成一篇详细、准确、有吸引力的网站介绍文章。";
        }
        
        if (empty($content_structure)) {
            $content_structure = "1. 网站概述\n2. 主要功能特点\n3. 使用场景\n4. 用户评价\n5. 总结推荐";
        }
        
        $prompt = $custom_prompt . "\n\n";
        $prompt .= "网站信息：\n";
        $prompt .= "网站名称：" . $site_title . "\n";
        $prompt .= "网站地址：" . $site_url . "\n";
        $prompt .= "网站简介：" . $site_description . "\n";
        $prompt .= "关键词：" . $site_keywords . "\n\n";
        $prompt .= "请按照以下结构生成内容：\n" . $content_structure;
        
        return $prompt;
    }
    
    private function build_enhanced_prompt($site_url, $site_title, $site_description, $site_keywords, $additional_info = '') {
        $options = get_option('onenav_ai_generator_options', array());
        $custom_prompt = isset($options['custom_prompt']) ? $options['custom_prompt'] : '';
        $content_structure = isset($options['content_structure']) ? $options['content_structure'] : '';
        
        if (empty($custom_prompt)) {
            $custom_prompt = "你是一个专业的网站内容编写专家。请根据提供的网站信息，生成一篇详细、准确、有吸引力的网站介绍文章。";
        }
        
        if (empty($content_structure)) {
            $content_structure = "网站概述\n核心功能特点\n使用场景和目标用户\n网站优势和亮点\n总结推荐";
        }
        
        $prompt = $custom_prompt . "\n\n";
        $prompt .= "网站信息：\n";
        $prompt .= "网站名称：" . ($site_title ?: '待补充') . "\n";
        $prompt .= "网站地址：" . $site_url . "\n";
        $prompt .= "网站简介：" . ($site_description ?: '待分析') . "\n";
        $prompt .= "相关关键词：" . ($site_keywords ?: '待提取') . "\n";
        
        // 添加额外的元数据信息
        if (!empty($additional_info)) {
            $prompt .= "补充信息：\n" . $additional_info;
        }
        
        $prompt .= "\n生成要求：\n";
        $prompt .= "1. 直接生成网站介绍内容，不要包含任何前言、分析过程或总结性语句\n";
        $prompt .= "2. 使用Markdown格式：用##表示主要标题，用**文字**表示重点内容\n";
        $prompt .= "3. 文章长度控制在300-500字\n";
        $prompt .= "4. 语言简洁明了，客观专业\n";
        $prompt .= "5. 如果信息不完整，基于网址域名进行合理推测\n";
        $prompt .= "6. 使用中文撰写，语言自然流畅\n";
        $prompt .= "7. 按段落组织内容，每个段落之间用空行分隔\n\n";
        $prompt .= "内容结构参考：\n" . $content_structure . "\n\n";
        $prompt .= "请直接输出网站介绍内容，不要包含任何额外的说明文字：";
        
        return $prompt;
    }
    
    // 将Markdown格式转换为HTML
    private function convert_markdown_to_html($content) {
        // 转换标题 ## 为 <h2>
        $content = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $content);
        
        // 转换加粗 **text** 为 <strong>text</strong>
        $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);
        
        // 转换段落（双换行符为段落分隔）
        $content = preg_replace('/\n\n+/', '</p><p>', $content);
        
        // 添加段落标签
        if (!empty(trim($content))) {
            $content = '<p>' . $content . '</p>';
        }
        
        // 清理空段落
        $content = preg_replace('/<p><\/p>/', '', $content);
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);
        
        return $content;
    }

    private function generate_content($site_url, $site_title, $site_description, $site_keywords, $additional_info = '') {
        $options = get_option('onenav_ai_generator_options');
        $encrypted_key = isset($options['deepseek_api_key']) ? $options['deepseek_api_key'] : '';
        $api_url = isset($options['deepseek_api_url']) ? $options['deepseek_api_url'] : '';
        $max_tokens = isset($options['max_tokens']) ? $options['max_tokens'] : 2000;

        // 获取 AI 提供商和模型配置
        $ai_provider = isset($options['ai_provider']) ? $options['ai_provider'] : 'deepseek';
        $ai_model = isset($options['ai_model']) ? $options['ai_model'] : 'deepseek-chat';

        // 修复：解密 API 密钥
        $api_key = $this->decrypt_api_key($encrypted_key);

        if (empty($api_key)) {
            error_log('OneNav AI Generator - API密钥未配置或解密失败');
            return false;
        }

        // 使用增强的提示词构建方法
        $prompt = $this->build_enhanced_prompt($site_url, $site_title, $site_description, $site_keywords, $additional_info);

        // 调用 AI API (使用动态模型)
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode(array(
                'model' => $ai_model,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'max_tokens' => intval($max_tokens),
                'temperature' => 0.7
            )),
            'timeout' => 30
        ));

        // 改进：添加详细的错误日志记录
        if (is_wp_error($response)) {
            error_log('OneNav AI Generator - API请求失败: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['choices'][0]['message']['content'])) {
            $content = trim($data['choices'][0]['message']['content']);

            // 修复：限制内容长度，防止过长内容（使用多字节安全函数）
            if (mb_strlen($content, 'UTF-8') > 50000) {
                error_log('OneNav AI Generator - AI生成的内容过长，已截断');
                $content = mb_substr($content, 0, 50000, 'UTF-8');
            }

            // 转换Markdown格式为HTML
            $content = $this->convert_markdown_to_html($content);

            // 修复：使用 wp_kses_post 清理HTML，防止恶意代码
            $content = wp_kses_post($content);

            // 添加固定的注意事项到内容底部
            $notice = '<pre><span style="font-size: 10pt;">内容由AI生成，实际功能由于时间等各种因素可能有出入，请访问网站体验为准</span></pre>';
            $content .= "\n\n" . $notice;

            return $content;
        }

        // 改进：记录API响应格式错误
        $response_code = wp_remote_retrieve_response_code($response);
        error_log('OneNav AI Generator - API响应格式错误 (HTTP ' . $response_code . '): ' . substr($body, 0, 500));
        return false;
    }
    
    // 获取网址统计信息的AJAX处理函数
    public function ajax_get_site_statistics() {
        check_ajax_referer('onenav_ai_generator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'onenav-ai-generator'));
        }
        
        global $wpdb;

        // 修复SQL注入：使用 $wpdb->prepare() 参数化查询
        $total_sites = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = %s
            AND post_status = %s
        ", 'sites', 'publish'));

        // 获取有详细内容的网址数量（内容长度>100字符）
        $sites_with_content = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = %s
            AND post_status = %s
            AND CHAR_LENGTH(post_content) > %d
        ", 'sites', 'publish', 100));
        
        $sites_need_content = $total_sites - $sites_with_content;
        $completion_rate = $total_sites > 0 ? round(($sites_with_content / $total_sites) * 100, 1) : 0;
        
        wp_send_json_success(array(
            'total' => intval($total_sites),
            'with_content' => intval($sites_with_content),
            'need_content' => intval($sites_need_content),
            'completion_rate' => $completion_rate
        ));
    }
    
    // 批量生成内容的AJAX处理函数
    public function ajax_generate_batch_content() {
        check_ajax_referer('onenav_ai_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'onenav-ai-generator'));
        }

        // 修复：添加输入验证和范围限制
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        // 验证范围
        if ($limit < 1 || $limit > 100) {
            wp_send_json_error(array('message' => 'limit 参数必须在 1-100 之间'));
            return;
        }

        if ($offset < 0) {
            wp_send_json_error(array('message' => 'offset 参数不能为负数'));
            return;
        }
        
        global $wpdb;
        
        // 修复SQL注入：获取需要生成内容的网址
        $sites = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_title, post_excerpt
            FROM {$wpdb->posts}
            WHERE post_type = %s
            AND post_status = %s
            AND CHAR_LENGTH(post_content) <= %d
            ORDER BY ID ASC
            LIMIT %d OFFSET %d
        ", 'sites', 'publish', 100, $limit, $offset));

        // 获取总数（用于计算进度）
        $total_to_process = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = %s
            AND post_status = %s
            AND CHAR_LENGTH(post_content) <= %d
        ", 'sites', 'publish', 100));
        
        $results = array();
        $processed = 0;
        $successful = 0;
        $failed = 0;
        $current_processing = '';
        $next_processing = '';
        
        foreach ($sites as $index => $site) {
            $processed++;
            
            // 设置当前处理和下一个处理的网址
            $current_processing = $site->post_title;
            if (isset($sites[$index + 1])) {
                $next_processing = $sites[$index + 1]->post_title;
            } else {
                $next_processing = '';
            }
            
            // 获取网址相关信息
            $site_url = get_post_meta($site->ID, '_sites_link', true);
            $site_title = $site->post_title;
            $site_description = $site->post_excerpt;
            $site_keywords = get_post_meta($site->ID, '_sites_keywords', true);
            
            if (empty($site_url)) {
                $results[] = array(
                    'success' => false,
                    'title' => $site_title,
                    'message' => '缺少网址链接'
                );
                $failed++;
                continue;
            }
            
            // 生成内容
            $generated_content = $this->generate_content($site_url, $site_title, $site_description, $site_keywords);
            
            if ($generated_content && !is_wp_error($generated_content)) {
                // 更新文章内容
                $update_result = wp_update_post(array(
                    'ID' => $site->ID,
                    'post_content' => $generated_content
                ));
                
                if ($update_result && !is_wp_error($update_result)) {
                    $results[] = array(
                        'success' => true,
                        'title' => $site_title,
                        'message' => '生成成功'
                    );
                    $successful++;
                } else {
                    $results[] = array(
                        'success' => false,
                        'title' => $site_title,
                        'message' => '更新失败'
                    );
                    $failed++;
                }
            } else {
                $error_message = is_wp_error($generated_content) ? $generated_content->get_error_message() : '生成失败';
                $results[] = array(
                    'success' => false,
                    'title' => $site_title,
                    'message' => $error_message
                );
                $failed++;
            }
            
            // 添加延迟避免API限制
            if ($processed < count($sites)) {
                sleep(1);
            }
        }
        
        // 修复SQL注入：检查是否还有更多需要处理的
        $last_id = $sites ? end($sites)->ID : 0;
        $remaining = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = %s
            AND post_status = %s
            AND CHAR_LENGTH(post_content) <= %d
            AND ID > %d
        ", 'sites', 'publish', 100, $last_id));
        
        $has_more = $remaining > 0;
        
        $message = sprintf(
            '批次处理完成：成功 %d 个，失败 %d 个',
            $successful,
            $failed
        );
        
        wp_send_json_success(array(
            'processed' => $processed,
            'successful' => $successful,
            'failed' => $failed,
            'results' => $results,
            'has_more' => $has_more,
            'total_to_process' => $total_to_process,
            'current_processing' => $current_processing,
            'next_processing' => $next_processing,
            'message' => $message
        ));
    }
}

// 初始化插件
OneNavAIGenerator::get_instance();

// 集成到OneNav主题的网址编辑器
add_action('admin_footer', 'onenav_ai_generator_integrate_theme_editor');

function onenav_ai_generator_integrate_theme_editor() {
    global $current_screen;
    
    // 只在sites文章类型的编辑页面显示
    if (!$current_screen || $current_screen->post_type !== 'sites' || $current_screen->id !== 'sites') {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // 等待OneNav主题的元数据获取按钮加载完成
        setTimeout(function() {
            // 在获取元数据按钮右侧添加AI生成按钮，使用相同的样式
            if ($('#refre-url').length > 0) {
                var aiButton = '<a href="javascript:;" id="ai-generate-content" style="margin-left:10px"><?php _e('AI生成正文', 'onenav-ai-generator'); ?></a>';
                var aiStatus = '<span id="ai-generation-status" style="display:none;margin-left:10px;color:blue;"></span>';
                $('#refre-url').after(aiButton + aiStatus);
                
                // AI生成按钮点击事件
                $('#ai-generate-content').on('click', function() {
                    generateAIContent();
                });
            }
        }, 500);
        
        function generateAIContent() {
            // 获取当前页面的网站信息
            var siteUrl = $('input[name*="_sites_link"]').val();
            var siteTitle = $('input[name="post_title"]').val() || $('#title').val();
            var siteDescription = $('textarea[name*="_sites_sescribe"]').val();
            var siteKeywords = $('#new-tag-sitetag').val() || '';
            var postId = $('input#post_ID').val() || 0;
            
            if (!siteUrl) {
                $('#ai-generation-status').html('<?php _e('请先填写网址', 'onenav-ai-generator'); ?>').css('color', 'red').show().delay(4000).hide();
                return;
            }
            
            $('#ai-generation-status').html('<?php _e('正在生成AI内容...', 'onenav-ai-generator'); ?>').css('color', 'blue').show();
            $('#ai-generate-content').prop('disabled', true).text('<?php _e('生成中...', 'onenav-ai-generator'); ?>');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'generate_site_description',
                    nonce: '<?php echo wp_create_nonce('onenav_ai_generator_nonce'); ?>',
                    post_id: postId,
                    site_url: siteUrl,
                    site_title: siteTitle,
                    site_description: siteDescription,
                    site_keywords: siteKeywords
                },
                success: function(response) {
                    $('#ai-generate-content').prop('disabled', false).text('<?php _e('AI生成正文', 'onenav-ai-generator'); ?>');
                    
                    if (response.success) {
                        var content = response.data.content;
                        
                        // 清理内容，移除可能的前言
                        content = content.replace(/^.*?根据.*?如下[：:]\s*/i, '');
                        content = content.replace(/^.*?现生成.*?如下[：:]\s*/i, '');
                        content = content.replace(/^.*?网站介绍[：:]\s*/i, '');
                        content = content.replace(/^.*?分析.*?如下[：:]\s*/i, '');
                        content = content.trim();
                        
                        // 处理内容格式化
                        function formatContentForEditor(rawContent) {
                            var formatted = rawContent;
                            
                            // 处理标题（## 转换为 <h3>，### 转换为 <h4>）
                            formatted = formatted.replace(/^###\s+(.+)$/gm, '<h4>$1</h4>');
                            formatted = formatted.replace(/^##\s+(.+)$/gm, '<h3>$1</h3>');
                            formatted = formatted.replace(/^#\s+(.+)$/gm, '<h2>$1</h2>');
                            
                            // 处理粗体和斜体
                            formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                            formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');
                            
                            // 处理段落
                            var paragraphs = formatted.split('\n\n').filter(function(p) { 
                                return p.trim() && !p.match(/^<h[2-6]>/); 
                            });
                            
                            var headings = formatted.match(/^<h[2-6]>.*?<\/h[2-6]>$/gm) || [];
                            
                            // 重新组织内容，保持标题和段落的正确顺序
                            var lines = formatted.split('\n');
                            var result = [];
                            var currentParagraph = '';
                            
                            for (var i = 0; i < lines.length; i++) {
                                var line = lines[i].trim();
                                if (!line) {
                                    if (currentParagraph) {
                                        result.push('<p>' + currentParagraph + '</p>');
                                        currentParagraph = '';
                                    }
                                } else if (line.match(/^<h[2-6]>/)) {
                                    if (currentParagraph) {
                                        result.push('<p>' + currentParagraph + '</p>');
                                        currentParagraph = '';
                                    }
                                    result.push(line);
                                } else {
                                    if (currentParagraph) {
                                        currentParagraph += '<br>' + line;
                                    } else {
                                        currentParagraph = line;
                                    }
                                }
                            }
                            
                            if (currentParagraph) {
                                result.push('<p>' + currentParagraph + '</p>');
                            }
                            
                            return result.join('\n');
                        }
                        
                        var formattedContent = formatContentForEditor(content);
                        
                        // 检测编辑器类型并插入内容
                        if ($('#post-title-0').length > 0 || $('.block-editor').length > 0) {
                            // Gutenberg编辑器
                            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                                // 解析HTML内容并创建对应的块
                                var tempDiv = $('<div>').html(formattedContent);
                                var blocks = [];
                                
                                tempDiv.children().each(function() {
                                    var element = $(this);
                                    var tagName = element.prop('tagName').toLowerCase();
                                    
                                    if (tagName === 'h2' || tagName === 'h3' || tagName === 'h4') {
                                        blocks.push(wp.blocks.createBlock('core/heading', {
                                            content: element.html(),
                                            level: parseInt(tagName.charAt(1))
                                        }));
                                    } else if (tagName === 'p') {
                                        blocks.push(wp.blocks.createBlock('core/paragraph', {
                                            content: element.html()
                                        }));
                                    }
                                });
                                
                                // 获取现有内容并追加新内容
                                var existingBlocks = wp.data.select('core/editor').getBlocks();
                                var allBlocks = existingBlocks.concat(blocks);
                                wp.data.dispatch('core/editor').resetBlocks(allBlocks);
                            }
                        } else {
                            // 经典编辑器
                            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
                                var currentContent = tinyMCE.activeEditor.getContent();
                                tinyMCE.activeEditor.setContent(currentContent + formattedContent);
                            } else {
                                // 文本编辑器 - 转换HTML为纯文本
                                var textContent = formattedContent
                                    .replace(/<h[2-6]>(.*?)<\/h[2-6]>/g, '\n\n$1\n')
                                    .replace(/<strong>(.*?)<\/strong>/g, '**$1**')
                                    .replace(/<em>(.*?)<\/em>/g, '*$1*')
                                    .replace(/<p>(.*?)<\/p>/g, '$1\n\n')
                                    .replace(/<br>/g, '\n')
                                    .replace(/\n{3,}/g, '\n\n')
                                    .trim();
                                
                                var currentContent = $('#content').val();
                                $('#content').val(currentContent + '\n\n' + textContent);
                            }
                        }
                        
                        $('#ai-generation-status').html('<?php _e('AI内容生成成功！', 'onenav-ai-generator'); ?>').css('color', 'green').show().delay(4000).hide();
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : '<?php _e('生成失败，请检查API设置', 'onenav-ai-generator'); ?>';
                        $('#ai-generation-status').html('<?php _e('生成失败：', 'onenav-ai-generator'); ?>' + errorMsg).css('color', 'red').show().delay(6000).hide();
                    }
                },
                error: function() {
                    $('#ai-generate-content').prop('disabled', false).text('<?php _e('AI生成正文', 'onenav-ai-generator'); ?>');
                    $('#ai-generation-status').html('<?php _e('网络错误，请重试', 'onenav-ai-generator'); ?>').css('color', 'red').show().delay(4000).hide();
                }
            });
        }
    });
    </script>
    <?php
}