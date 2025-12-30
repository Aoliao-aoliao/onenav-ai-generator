# OneNav AI Generator 批量生成计划任务解决方案

## 问题背景

原有的后台批量生成功能经常出错，无法在失败后继续生成，导致需要重新开始整个过程。为了解决这个问题，我们开发了基于宝塔计划任务的批量生成解决方案。

## 解决方案特点

### ✅ 断点续传
- 任务失败后可以从上次停止的地方继续
- 自动记录生成进度和状态
- 支持重置功能，可以重新开始生成

### ✅ 稳定可靠
- 不依赖浏览器连接，避免网络中断
- 独立的PHP脚本，资源占用可控
- 完善的错误处理和日志记录

### ✅ 灵活配置
- 可调整每次处理的数量（5-20个推荐）
- 支持不同的执行频率（3-30分钟）
- 可以根据服务器性能优化参数

### ✅ 监控友好
- 详细的日志记录
- 实时状态跟踪
- 统计信息展示

## 文件说明

| 文件名 | 说明 |
|--------|------|
| `batch-generate-cron.php` | 主要的批量生成脚本 |
| `BAOTA-CRON-SETUP.md` | 详细的宝塔计划任务配置指南 |
| `install-batch-cron.sh` | 自动安装和配置脚本 |
| `test-batch-logic.py` | 逻辑测试脚本（用于验证功能） |
| `README-BATCH-CRON.md` | 本说明文件 |

## 快速开始

### 1. 安装部署

```bash
# 上传所有文件到插件目录
# /www/wwwroot/your-domain.com/wp-content/plugins/onenav-ai-generator/

# 运行安装脚本（可选）
sudo bash install-batch-cron.sh
```

### 2. 配置宝塔计划任务

在宝塔面板中创建计划任务：

- **任务类型**: Shell脚本
- **任务名称**: OneNav AI 批量生成
- **执行周期**: 每5分钟执行一次
- **脚本内容**:
  ```bash
  #!/bin/bash
  cd /www/wwwroot/your-domain.com/wp-content/plugins/onenav-ai-generator/
  /usr/bin/php batch-generate-cron.php 10
  ```

### 3. 监控执行

```bash
# 查看统计信息
php batch-generate-cron.php 0

# 查看日志
tail -f batch-generate.log

# 查看状态文件
cat batch-status.json
```

## 使用场景

### 场景1：首次批量生成
```bash
# 开始生成，每次处理10个网址
php batch-generate-cron.php 10
```

### 场景2：任务中断后继续
```bash
# 直接运行，会自动从上次停止的地方继续
php batch-generate-cron.php 10
```

### 场景3：重新开始生成
```bash
# 重置状态，重新开始
php batch-generate-cron.php 10 reset
```

### 场景4：服务器性能较低
```bash
# 减少每次处理数量
php batch-generate-cron.php 5
```

## 配置建议

### 服务器性能配置

| 服务器配置 | 建议设置 |
|------------|----------|
| 1核1G | 每10分钟执行，每次5个 |
| 2核2G | 每5分钟执行，每次10个 |
| 4核4G+ | 每3分钟执行，每次15-20个 |

### 执行频率建议

- **快速完成**: 每3-5分钟执行一次
- **平衡模式**: 每10分钟执行一次
- **轻量模式**: 每30分钟执行一次

## 监控和维护

### 日志文件
- `batch-generate.log`: 详细的执行日志
- `batch-status.json`: 当前生成状态

### 状态信息
```json
{
  "last_processed_id": 150,
  "total_processed": 150,
  "successful": 142,
  "failed": 8,
  "start_time": 1634567890
}
```

### 清理维护
```bash
# 清理日志文件（保留最近1000行）
tail -1000 batch-generate.log > batch-generate.log.tmp
mv batch-generate.log.tmp batch-generate.log

# 重置状态（重新开始）
rm batch-status.json
```

## 故障排除

### 常见问题

1. **权限错误**
   ```bash
   chmod 755 batch-generate-cron.php
   ```

2. **PHP路径错误**
   ```bash
   which php  # 查找正确的PHP路径
   ```

3. **WordPress加载失败**
   - 检查脚本路径是否正确
   - 确保WordPress配置文件存在

4. **API配置问题**
   - 检查DeepSeek API Key是否正确配置
   - 确认API额度是否充足

### 调试模式
```bash
# 只处理1个网址，便于调试
php batch-generate-cron.php 1
```

## 性能优化

### 1. 内存优化
- 确保PHP内存限制至少512M
- 脚本已设置无时间限制

### 2. 并发控制
- 避免同时运行多个生成任务
- 可以通过锁文件机制防止重复执行

### 3. API限制
- 脚本内置2秒延迟，避免API限制
- 可根据API提供商要求调整

## 安全注意事项

1. **文件权限**: 确保脚本文件权限设置正确
2. **日志安全**: 定期清理日志文件
3. **API安全**: 保护API密钥不被泄露

## 技术支持

如果遇到问题，请按以下顺序检查：

1. 查看宝塔计划任务执行日志
2. 查看脚本生成的详细日志
3. 检查WordPress和PHP错误日志
4. 验证API配置和网络连接

通过这个解决方案，您可以实现稳定、可靠的批量内容生成，即使在任务失败的情况下也能快速恢复和继续执行。