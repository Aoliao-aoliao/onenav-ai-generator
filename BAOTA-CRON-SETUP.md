# OneNav AI Generator 宝塔计划任务配置指南

## 概述

本指南将帮助您在宝塔面板中设置计划任务，实现OneNav AI Generator的自动批量生成功能。使用计划任务的优势：

- **断点续传**：任务失败后可以从上次停止的地方继续
- **稳定性**：不依赖浏览器连接，避免网络中断导致的任务失败
- **自动化**：可以设置定时执行，无需人工干预
- **资源控制**：可以控制每次处理的数量，避免服务器负载过高

## 前置要求

1. 已安装OneNav AI Generator插件
2. 已配置DeepSeek API Key
3. 服务器已安装宝塔面板
4. PHP版本 >= 7.4

## 配置步骤

### 1. 上传脚本文件

将 `batch-generate-cron.php` 文件上传到插件目录：
```
/www/wwwroot/your-domain.com/wp-content/plugins/onenav-ai-generator/
```

### 2. 设置文件权限

在宝塔面板的文件管理中，设置脚本文件权限为 755：
```bash
chmod 755 batch-generate-cron.php
```

### 3. 创建计划任务

1. 登录宝塔面板
2. 点击左侧菜单 "计划任务"
3. 点击 "添加任务"
4. 填写任务信息：

**任务类型**：Shell脚本

**任务名称**：OneNav AI 批量生成

**执行周期**：根据需要选择，建议：
- 每5分钟执行一次（快速处理）
- 每10分钟执行一次（平衡处理）
- 每30分钟执行一次（轻量处理）

**脚本内容**：
```bash
#!/bin/bash
cd /www/wwwroot/your-domain.com/wp-content/plugins/onenav-ai-generator/
/usr/bin/php batch-generate-cron.php 10
```

**注意**：
- 请将 `your-domain.com` 替换为您的实际域名
- 数字 `10` 表示每次处理10个网址，可根据服务器性能调整（建议5-20之间）

### 4. 高级配置选项

#### 自定义处理数量
```bash
# 每次处理5个网址（适合配置较低的服务器）
/usr/bin/php batch-generate-cron.php 5

# 每次处理20个网址（适合配置较高的服务器）
/usr/bin/php batch-generate-cron.php 20
```

#### 重置生成进度
如果需要重新开始生成所有内容：
```bash
/usr/bin/php batch-generate-cron.php 10 reset
```

#### 查看统计信息
创建一个单独的任务来查看统计：
```bash
/usr/bin/php batch-generate-cron.php 0
```

## 监控和日志

### 查看执行日志

1. 在宝塔面板的计划任务列表中，点击任务后的 "日志" 按钮
2. 查看脚本生成的详细日志文件：
   ```
   /www/wwwroot/your-domain.com/wp-content/plugins/onenav-ai-generator/batch-generate.log
   ```

### 查看生成状态

状态文件位置：
```
/www/wwwroot/your-domain.com/wp-content/plugins/onenav-ai-generator/batch-status.json
```

状态文件包含：
- `last_processed_id`：最后处理的网址ID
- `total_processed`：总处理数量
- `successful`：成功数量
- `failed`：失败数量
- `start_time`：开始时间

## 故障排除

### 常见问题

1. **权限错误**
   ```bash
   chmod 755 batch-generate-cron.php
   chmod 755 /www/wwwroot/your-domain.com/wp-content/plugins/onenav-ai-generator/
   ```

2. **PHP路径错误**
   
   查找正确的PHP路径：
   ```bash
   which php
   # 或
   whereis php
   ```
   
   常见PHP路径：
   - `/usr/bin/php`
   - `/www/server/php/74/bin/php`
   - `/www/server/php/80/bin/php`

3. **WordPress加载失败**
   
   确保脚本能找到WordPress根目录，检查路径是否正确。

4. **API配置问题**
   
   确保在WordPress后台已正确配置DeepSeek API Key。

### 调试模式

如果遇到问题，可以手动执行脚本进行调试：

```bash
cd /www/wwwroot/your-domain.com/wp-content/plugins/onenav-ai-generator/
php batch-generate-cron.php 1
```

这将只处理1个网址，便于观察错误信息。

## 性能优化建议

### 服务器配置建议

1. **内存限制**：确保PHP内存限制至少512M
2. **执行时间**：脚本已设置无时间限制，但建议服务器支持长时间运行
3. **并发控制**：避免同时运行多个生成任务

### 任务频率建议

根据服务器性能和网址数量调整：

- **小型站点**（<1000个网址）：每10分钟执行，每次处理10个
- **中型站点**（1000-5000个网址）：每5分钟执行，每次处理15个
- **大型站点**（>5000个网址）：每3分钟执行，每次处理20个

## 安全注意事项

1. **文件权限**：确保脚本文件权限设置正确，避免安全风险
2. **日志清理**：定期清理日志文件，避免占用过多磁盘空间
3. **API密钥**：确保API密钥安全，不要在日志中暴露

## 完成后的清理

当所有网址都生成完内容后：

1. 可以停用计划任务
2. 保留脚本文件以备将来使用
3. 清理日志文件（可选）

## 技术支持

如果在配置过程中遇到问题，请检查：

1. 宝塔面板的计划任务日志
2. 脚本生成的详细日志文件
3. WordPress错误日志
4. 服务器PHP错误日志

通过这些日志信息，可以快速定位和解决问题。