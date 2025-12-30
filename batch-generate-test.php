<?php
/**
 * OneNav AI Generator - 批量生成测试脚本
 * 用于在没有WordPress环境的情况下测试批量生成逻辑
 * 
 * 使用方法：
 * php batch-generate-test.php [limit] [reset]
 */

// 设置脚本执行时间限制
set_time_limit(0);
ini_set('memory_limit', '512M');

// 获取脚本参数
$limit = isset($argv[1]) ? intval($argv[1]) : 10;
$reset = isset($argv[2]) && $argv[2] === 'reset';

// 状态文件路径
$status_file = __DIR__ . '/batch-status.json';
$log_file = __DIR__ . '/batch-generate.log';

// 日志函数
function write_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    echo $log_entry;
}

// 读取状态
function read_status() {
    global $status_file;
    if (file_exists($status_file)) {
        $content = file_get_contents($status_file);
        return json_decode($content, true);
    }
    return array(
        'last_processed_id' => 0,
        'total_processed' => 0,
        'successful' => 0,
        'failed' => 0,
        'start_time' => date('Y-m-d H:i:s')
    );
}

// 保存状态
function save_status($status) {
    global $status_file;
    file_put_contents($status_file, json_encode($status, JSON_PRETTY_PRINT), LOCK_EX);
}

// 模拟获取站点数据
function get_mock_sites($offset, $limit) {
    $sites = array();
    for ($i = 1; $i <= $limit; $i++) {
        $id = $offset + $i;
        $sites[] = array(
            'ID' => $id,
            'post_title' => "测试站点 {$id}",
            'url' => "https://example{$id}.com",
            'description' => "这是测试站点 {$id} 的描述"
        );
    }
    return $sites;
}

// 模拟内容生成
function generate_mock_content($site) {
    // 模拟90%的成功率
    if (rand(1, 100) <= 90) {
        $content = "这是为站点 '{$site['post_title']}' 生成的AI内容。\n\n";
        $content .= "网站地址：{$site['url']}\n";
        $content .= "网站描述：{$site['description']}\n\n";
        $content .= "这是一个优秀的网站，提供了丰富的内容和良好的用户体验。";
        
        return array('success' => true, 'content' => $content);
    } else {
        return array('success' => false, 'error' => 'API调用失败');
    }
}

// 主执行逻辑
write_log("=== 批量生成脚本开始执行 ===");
write_log("参数: limit={$limit}, reset=" . ($reset ? 'true' : 'false'));

// 读取当前状态
$status = read_status();

// 如果需要重置
if ($reset) {
    write_log("重置生成状态");
    $status = array(
        'last_processed_id' => 0,
        'total_processed' => 0,
        'successful' => 0,
        'failed' => 0,
        'start_time' => date('Y-m-d H:i:s')
    );
    save_status($status);
}

write_log("当前状态: 已处理 {$status['total_processed']} 个，成功 {$status['successful']} 个，失败 {$status['failed']} 个");
write_log("从ID {$status['last_processed_id']} 开始处理");

// 获取要处理的站点
$sites = get_mock_sites($status['last_processed_id'], $limit);

if (empty($sites)) {
    write_log("没有找到需要处理的站点");
    exit(0);
}

write_log("找到 " . count($sites) . " 个站点需要处理");

// 处理每个站点
foreach ($sites as $site) {
    write_log("正在处理站点 ID: {$site['ID']} - {$site['post_title']}");
    
    // 生成内容
    $result = generate_mock_content($site);
    
    if ($result['success']) {
        write_log("✓ 站点 {$site['ID']} 内容生成成功");
        $status['successful']++;
        
        // 模拟保存到数据库
        $content_file = __DIR__ . "/generated_content_{$site['ID']}.txt";
        file_put_contents($content_file, $result['content']);
        
    } else {
        write_log("✗ 站点 {$site['ID']} 内容生成失败: {$result['error']}");
        $status['failed']++;
    }
    
    // 更新状态
    $status['last_processed_id'] = $site['ID'];
    $status['total_processed']++;
    save_status($status);
    
    // 短暂延迟，模拟真实处理时间
    usleep(500000); // 0.5秒
}

write_log("=== 批量生成完成 ===");
write_log("本次处理: " . count($sites) . " 个站点");
write_log("总计处理: {$status['total_processed']} 个站点");
write_log("成功: {$status['successful']} 个，失败: {$status['failed']} 个");
write_log("成功率: " . round(($status['successful'] / $status['total_processed']) * 100, 2) . "%");

echo "\n脚本执行完成！\n";
echo "查看详细日志: cat {$log_file}\n";
echo "查看状态文件: cat {$status_file}\n";