<?php
/**
 * OneNav AI Generator - 批量生成计划任务脚本
 * 用于宝塔计划任务执行，支持断点续传和错误重试
 * 
 * 使用方法：
 * php batch-generate-cron.php [limit] [reset]
 * 
 * 参数说明：
 * limit: 每次处理的数量，默认10
 * reset: 重置进度状态，重新开始生成
 */

// 设置脚本执行时间限制
set_time_limit(0);
ini_set('memory_limit', '512M');

// 获取WordPress根目录
function find_wordpress_root($start_path = null) {
    if ($start_path === null) {
        $start_path = __FILE__;
    }
    
    $current_dir = dirname($start_path);
    $max_levels = 10; // 最多向上查找10级目录
    
    for ($i = 0; $i < $max_levels; $i++) {
        // 检查当前目录是否包含wp-config.php
        if (file_exists($current_dir . '/wp-config.php')) {
            return $current_dir;
        }
        
        // 检查是否还能向上一级
        $parent_dir = dirname($current_dir);
        if ($parent_dir === $current_dir) {
            // 已经到达根目录
            break;
        }
        
        $current_dir = $parent_dir;
    }
    
    return false;
}

$wp_root = find_wordpress_root();
if (!$wp_root) {
    echo "错误: 无法找到WordPress根目录\n";
    echo "请确保此脚本位于WordPress网站目录中\n\n";
    echo "使用说明:\n";
    echo "1. 将此脚本上传到WordPress网站的插件目录中\n";
    echo "2. 确保WordPress已正确安装并配置\n";
    echo "3. 在宝塔面板中创建计划任务执行此脚本\n\n";
    echo "当前脚本路径: " . __FILE__ . "\n";
    echo "查找的目录路径:\n";
    
    // 显示查找过程以便调试
    $current_dir = dirname(__FILE__);
    for ($i = 0; $i < 5; $i++) {
        echo "  - " . $current_dir . (file_exists($current_dir . '/wp-config.php') ? ' ✓ 找到wp-config.php' : '') . "\n";
        $parent_dir = dirname($current_dir);
        if ($parent_dir === $current_dir) break;
        $current_dir = $parent_dir;
    }
    
    echo "\n如果您想测试批量生成功能，可以运行:\n";
    echo "python3 batch-generate-demo.py 5\n\n";
    
    die();
}

// 加载WordPress环境
define('WP_USE_THEMES', false);

// 尝试加载wp-load.php
if (file_exists($wp_root . '/wp-load.php')) {
    require_once($wp_root . '/wp-load.php');
} else {
    // 手动加载WordPress核心文件
    if (file_exists($wp_root . '/wp-config.php')) {
        require_once($wp_root . '/wp-config.php');
    }
    
    // 安全地加载WordPress核心文件
    $wp_includes = array(
        '/wp-includes/wp-db.php',
        '/wp-includes/functions.php',
        '/wp-includes/option.php',
        '/wp-includes/meta.php',
        '/wp-includes/post.php',
        '/wp-includes/pluggable.php',
        '/wp-includes/formatting.php'
    );
    
    foreach ($wp_includes as $file) {
        if (file_exists($wp_root . $file)) {
            require_once($wp_root . $file);
        }
    }
}

// 初始化数据库连接
global $wpdb, $table_prefix;

// 如果WordPress环境未正确加载，尝试手动解析wp-config.php
if (!defined('DB_USER') || !defined('DB_PASSWORD') || !defined('DB_NAME') || !defined('DB_HOST')) {
    $wp_config_file = $wp_root . '/wp-config.php';
    if (file_exists($wp_config_file)) {
        $config_content = file_get_contents($wp_config_file);
        
        // 解析数据库配置
        if (preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"](.*?)['\"]\s*\)/", $config_content, $matches)) {
            if (!defined('DB_USER')) define('DB_USER', $matches[1]);
        }
        if (preg_match("/define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"](.*?)['\"]\s*\)/", $config_content, $matches)) {
            if (!defined('DB_PASSWORD')) define('DB_PASSWORD', $matches[1]);
        }
        if (preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"](.*?)['\"]\s*\)/", $config_content, $matches)) {
            if (!defined('DB_NAME')) define('DB_NAME', $matches[1]);
        }
        if (preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"](.*?)['\"]\s*\)/", $config_content, $matches)) {
            if (!defined('DB_HOST')) define('DB_HOST', $matches[1]);
        }
        
        // 解析表前缀
        if (preg_match("/\\\$table_prefix\s*=\s*['\"](.*?)['\"]/", $config_content, $matches)) {
            $table_prefix = $matches[1];
        }
    }
}

// 设置默认值
if (!defined('DB_USER')) define('DB_USER', '');
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', '');
if (!defined('DB_NAME')) define('DB_NAME', '');
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!isset($table_prefix)) $table_prefix = 'wp_';

if (!isset($wpdb)) {
    $db_user = DB_USER;
    $db_password = DB_PASSWORD;
    $db_name = DB_NAME;
    $db_host = DB_HOST;
    
    if ($db_user && $db_name) {
        try {
            // 创建简单的数据库连接
            $wpdb = new stdClass();
            $wpdb->dbh = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8", $db_user, $db_password);
            $wpdb->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 设置表前缀
            $wpdb->prefix = $table_prefix;
            $wpdb->options = $table_prefix . 'options';
            $wpdb->posts = $table_prefix . 'posts';
            $wpdb->postmeta = $table_prefix . 'postmeta';
        } catch (Exception $e) {
            die("错误: 数据库连接失败 - " . $e->getMessage() . "\n");
        }
    } else {
        die("错误: 数据库配置信息不完整\n");
    }
}

// 提供备用函数实现
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $wpdb;
        if (!$wpdb || !isset($wpdb->dbh)) return $default;
        
        try {
            $stmt = $wpdb->dbh->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = ?");
            $stmt->execute(array($option));
            $value = $stmt->fetchColumn();
            
            if ($value !== false) {
                // 修复：使用 WordPress 的 maybe_unserialize 安全处理序列化数据
                // 移除 @ 错误抑制符，使用更安全的方式
                return maybe_unserialize($value);
            }
            return $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        global $wpdb;
        if (!$wpdb || !isset($wpdb->dbh)) return $single ? '' : array();
        
        try {
            if ($key) {
                $stmt = $wpdb->dbh->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = ? AND meta_key = ?");
                $stmt->execute(array($post_id, $key));
                $value = $stmt->fetchColumn();
                
                if ($value !== false) {
                    // 修复：使用 maybe_unserialize 安全处理
                    $value = maybe_unserialize($value);
                    return $single ? $value : array($value);
                }
                return $single ? '' : array();
            } else {
                $stmt = $wpdb->dbh->prepare("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = ?");
                $stmt->execute(array($post_id));
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $meta = array();
                foreach ($results as $row) {
                    $value = $row['meta_value'];
                    // 修复：使用 maybe_unserialize 安全处理
                    $value = maybe_unserialize($value);
                    $meta[$row['meta_key']][] = $value;
                }
                return $meta;
            }
        } catch (Exception $e) {
            return $single ? '' : array();
        }
    }
}

// 检查基本环境是否可用
if (!function_exists('get_option') || !$wpdb) {
    die("错误: WordPress环境或数据库连接初始化失败\n");
}

class OneNavBatchGenerator {
    
    private $log_file;
    private $status_file;
    private $options;
    
    public function __construct() {
        $this->log_file = dirname(__FILE__) . '/batch-generate.log';
        $this->status_file = dirname(__FILE__) . '/batch-status.json';
        $this->options = get_option('onenav_ai_generator_options');
        
        // 检查API配置
        if (empty($this->options['deepseek_api_key'])) {
            $this->log("错误: DeepSeek API Key未配置");
            die("错误: DeepSeek API Key未配置\n");
        }
    }
    
    /**
     * 记录日志
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$message}\n";
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        echo $log_entry;
    }
    
    /**
     * 获取生成状态
     */
    private function getStatus() {
        if (!file_exists($this->status_file)) {
            return array(
                'last_processed_id' => 0,
                'total_processed' => 0,
                'successful' => 0,
                'failed' => 0,
                'start_time' => time()
            );
        }
        
        $status = json_decode(file_get_contents($this->status_file), true);
        return $status ? $status : array(
            'last_processed_id' => 0,
            'total_processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'start_time' => time()
        );
    }
    
    /**
     * 保存生成状态
     */
    private function saveStatus($status) {
        file_put_contents($this->status_file, json_encode($status, JSON_PRETTY_PRINT), LOCK_EX);
    }
    
    /**
     * 重置生成状态
     */
    public function resetStatus() {
        if (file_exists($this->status_file)) {
            unlink($this->status_file);
        }
        $this->log("生成状态已重置");
    }
    
    /**
     * 获取需要生成内容的网址
     */
    private function getSitesToProcess($limit, $last_processed_id = 0) {
        global $wpdb;
        
        $sql = "
            SELECT ID, post_title, post_content 
            FROM {$wpdb->posts} 
            WHERE post_type = 'sites' 
            AND post_status = 'publish' 
            AND ID > %d
            AND (post_content IS NULL OR post_content = '' OR CHAR_LENGTH(TRIM(post_content)) < 100)
            ORDER BY ID ASC 
            LIMIT %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $last_processed_id, $limit));
    }
    
    /**
     * 获取网址的元数据
     */
    private function getSiteMetadata($post_id) {
        // 使用正确的元数据字段名（与主插件保持一致）
        $url = get_post_meta($post_id, '_sites_link', true);
        $description = get_post_meta($post_id, '_sites_sescribe', true); // 修复：使用与主插件一致的字段名
        $keywords = get_post_meta($post_id, '_sites_keywords', true);
        
        // 如果没有找到，尝试使用post_excerpt作为描述
        if (empty($description)) {
            global $wpdb;
            $post = $wpdb->get_row($wpdb->prepare("SELECT post_excerpt FROM {$wpdb->posts} WHERE ID = %d", $post_id));
            if ($post) {
                $description = $post->post_excerpt;
            }
        }
        
        return array(
            'url' => $url,
            'description' => $description,
            'keywords' => $keywords
        );
    }
    
    /**
     * 构建增强的提示词
     */
    private function buildEnhancedPrompt($site_url, $site_title, $site_description, $site_keywords, $additional_info = '') {
        $custom_prompt = isset($this->options['custom_prompt']) ? $this->options['custom_prompt'] : '';
        $content_structure = isset($this->options['content_structure']) ? $this->options['content_structure'] : '';
        
        $prompt = "你是一个专业的网站内容分析师。请根据以下网站信息，生成一份详细、专业的网站介绍。\n\n";
        
        if (!empty($custom_prompt)) {
            $prompt .= "任务要求：\n" . $custom_prompt . "\n\n";
        }
        
        $prompt .= "网站信息：\n";
        $prompt .= "网站名称：" . $site_title . "\n";
        $prompt .= "网站地址：" . $site_url . "\n";
        
        if (!empty($site_description)) {
            $prompt .= "网站描述：" . $site_description . "\n";
        }
        
        if (!empty($site_keywords)) {
            $prompt .= "关键词：" . $site_keywords . "\n";
        }
        
        if (!empty($additional_info)) {
            $prompt .= "补充信息：" . $additional_info . "\n";
        }
        
        $prompt .= "\n生成要求：\n";
        $prompt .= "1. 内容要专业、准确、有条理\n";
        $prompt .= "2. 使用Markdown格式，标题用##，重要内容用**加粗**\n";
        $prompt .= "3. 文章长度控制在800-1500字\n";
        $prompt .= "4. 语言要通俗易懂，适合普通用户阅读\n";
        $prompt .= "5. 内容要客观中性，不要过度营销\n";
        $prompt .= "6. 如果是工具类网站，重点介绍功能和使用方法\n";
        $prompt .= "7. 按段落组织内容，每个段落之间用空行分隔\n\n";
        $prompt .= "内容结构参考：\n" . $content_structure . "\n\n";
        $prompt .= "请直接输出网站介绍内容，不要包含任何额外的说明文字：";
        
        return $prompt;
    }
    
    /**
     * 将Markdown格式转换为HTML
     */
    private function convertMarkdownToHtml($content) {
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
    
    /**
     * 生成网站内容
     */
    private function generateContent($site_url, $site_title, $site_description, $site_keywords, $additional_info = '') {
        $api_key = $this->options['deepseek_api_key'];
        $api_url = isset($this->options['deepseek_api_url']) ? $this->options['deepseek_api_url'] : 'https://api.deepseek.com/v1/chat/completions';
        $max_tokens = isset($this->options['max_tokens']) ? intval($this->options['max_tokens']) : 2000;

        // 获取 AI 提供商和模型配置
        $ai_provider = isset($this->options['ai_provider']) ? $this->options['ai_provider'] : 'deepseek';
        $ai_model = isset($this->options['ai_model']) ? $this->options['ai_model'] : 'deepseek-chat';

        // 构建提示词
        $prompt = $this->buildEnhancedPrompt($site_url, $site_title, $site_description, $site_keywords, $additional_info);

        // 准备API请求数据 (使用动态模型)
        $request_data = array(
            'model' => $ai_model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $max_tokens,
            'temperature' => 0.7
        );
        
        // 发送API请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        // 修复：启用 SSL 验证提高安全性
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            $this->log("CURL错误: " . $curl_error);
            return false;
        }
        
        if ($http_code !== 200) {
            $this->log("API请求失败，HTTP状态码: " . $http_code);
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            $content = trim($data['choices'][0]['message']['content']);
            // 转换Markdown格式为HTML
            $content = $this->convertMarkdownToHtml($content);
            
            // 添加固定的注意事项到内容底部
            $notice = '<pre><span style="font-size: 10pt;">内容由AI生成，实际功能由于时间等各种因素可能有出入，请访问网站体验为准</span></pre>';
            $content .= "\n\n" . $notice;
            
            return $content;
        }
        
        $this->log("API响应格式错误: " . $response);
        return false;
    }
    
    /**
     * 执行批量生成
     */
    public function run($limit = 10) {
        $this->log("开始批量生成任务，每次处理 {$limit} 个网址");
        
        // 获取当前状态
        $status = $this->getStatus();
        $this->log("当前状态 - 已处理: {$status['total_processed']}, 成功: {$status['successful']}, 失败: {$status['failed']}");
        
        // 获取需要处理的网址
        $sites = $this->getSitesToProcess($limit, $status['last_processed_id']);
        
        if (empty($sites)) {
            $this->log("没有找到需要处理的网址，任务完成");
            return;
        }
        
        $this->log("找到 " . count($sites) . " 个需要处理的网址");
        
        foreach ($sites as $site) {
            $this->log("正在处理: {$site->post_title} (ID: {$site->ID})");
            
            // 获取网址元数据
            $metadata = $this->getSiteMetadata($site->ID);
            
            // 添加详细的调试信息
            $this->log("元数据调试 - URL: " . ($metadata['url'] ?: '空') . ", 描述: " . ($metadata['description'] ?: '空') . ", 关键词: " . ($metadata['keywords'] ?: '空'));
            
            if (empty($metadata['url'])) {
                $this->log("跳过 {$site->post_title}: 没有网址信息");
                $status['last_processed_id'] = $site->ID;
                $status['total_processed']++;
                $status['failed']++;
                continue;
            }
            
            // 生成内容
            $content = $this->generateContent(
                $metadata['url'],
                $site->post_title,
                $metadata['description'],
                $metadata['keywords']
            );
            
            if ($content !== false) {
                // 更新文章内容
                global $wpdb;
                $result = $wpdb->update(
                    $wpdb->posts,
                    array('post_content' => $content),
                    array('ID' => $site->ID),
                    array('%s'),
                    array('%d')
                );
                
                if ($result !== false) {
                    $this->log("成功生成: {$site->post_title}");
                    $status['successful']++;
                } else {
                    $this->log("数据库更新失败: {$site->post_title}");
                    $status['failed']++;
                }
            } else {
                $this->log("内容生成失败: {$site->post_title}");
                $status['failed']++;
            }
            
            // 更新状态
            $status['last_processed_id'] = $site->ID;
            $status['total_processed']++;
            $this->saveStatus($status);
            
            // 添加延迟避免API限制
            sleep(2);
        }
        
        $this->log("本次任务完成 - 处理: " . count($sites) . ", 成功: {$status['successful']}, 失败: {$status['failed']}");
    }
    
    /**
     * 获取统计信息
     */
    public function getStatistics() {
        global $wpdb;
        
        $total_sites = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'sites' 
            AND post_status = 'publish'
        ");
        
        $sites_with_content = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'sites' 
            AND post_status = 'publish' 
            AND post_content IS NOT NULL 
            AND post_content != '' 
            AND CHAR_LENGTH(TRIM(post_content)) >= 100
        ");
        
        $status = $this->getStatus();
        
        echo "=== 网址统计信息 ===\n";
        echo "总网址数: {$total_sites}\n";
        echo "已有内容: {$sites_with_content}\n";
        echo "需要处理: " . ($total_sites - $sites_with_content) . "\n";
        echo "完成度: " . ($total_sites > 0 ? round(($sites_with_content / $total_sites) * 100, 2) : 0) . "%\n";
        echo "\n=== 生成进度 ===\n";
        echo "已处理: {$status['total_processed']}\n";
        echo "成功: {$status['successful']}\n";
        echo "失败: {$status['failed']}\n";
        echo "最后处理ID: {$status['last_processed_id']}\n";
    }
}

// 主程序
if (php_sapi_name() !== 'cli') {
    die("此脚本只能在命令行模式下运行\n");
}

// 解析命令行参数
$limit = isset($argv[1]) ? intval($argv[1]) : 10;
$reset = isset($argv[2]) && $argv[2] === 'reset';

if ($limit <= 0 || $limit > 100) {
    $limit = 10;
}

$generator = new OneNavBatchGenerator();

// 如果指定了reset参数，重置状态
if ($reset) {
    $generator->resetStatus();
}

// 显示统计信息
$generator->getStatistics();

// 执行批量生成
$generator->run($limit);

echo "\n任务执行完成\n";