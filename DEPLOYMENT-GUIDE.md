# OneNav AI Generator 批量生成部署指南

## 问题解决

如果您在运行 `batch-generate-cron.php` 时遇到"错误: 无法找到WordPress根目录"的提示，这是正常的，因为当前环境不是完整的WordPress安装。

## 部署步骤

### 1. 环境要求

- WordPress 5.0 或更高版本
- PHP 7.4 或更高版本
- 宝塔面板（用于计划任务）
- OneNav AI Generator 插件已安装并配置

### 2. 文件部署

将以下文件上传到您的WordPress网站：

```
/wp-content/plugins/onenav-ai-generator/
├── batch-generate-cron.php          # 批量生成脚本
├── install-batch-cron.sh           # 安装脚本
├── BAOTA-CRON-SETUP.md            # 宝塔配置说明
└── README-BATCH-CRON.md           # 功能说明
```

### 3. 权限设置

```bash
# 设置脚本执行权限
chmod +x /path/to/wordpress/wp-content/plugins/onenav-ai-generator/batch-generate-cron.php
chmod +x /path/to/wordpress/wp-content/plugins/onenav-ai-generator/install-batch-cron.sh
```

### 4. 宝塔计划任务配置

#### 4.1 登录宝塔面板

1. 打开宝塔面板
2. 进入"计划任务"页面
3. 点击"添加任务"

#### 4.2 创建批量生成任务

**任务类型**: Shell脚本

**任务名称**: OneNav AI 批量生成

**执行周期**: 根据需要选择（建议每小时或每天）

**脚本内容**:
```bash
#!/bin/bash
cd /www/wwwroot/your-domain.com/wp-content/plugins/onenav-ai-generator
/usr/bin/php batch-generate-cron.php 10
```

**参数说明**:
- `10`: 每次处理10个站点（可根据服务器性能调整）
- 如需重置进度，添加 `reset` 参数：`/usr/bin/php batch-generate-cron.php 10 reset`

### 5. 测试和验证

#### 5.1 手动测试

```bash
# 进入插件目录
cd /www/wwwroot/your-domain.com/wp-content/plugins/onenav-ai-generator

# 测试脚本
php batch-generate-cron.php 5

# 查看日志
cat batch-generate.log

# 查看状态
cat batch-status.json
```

#### 5.2 功能验证

1. **断点续传**: 中断脚本后重新运行，应从上次停止的位置继续
2. **状态记录**: 检查 `batch-status.json` 文件记录的进度
3. **日志记录**: 查看 `batch-generate.log` 文件的详细日志
4. **内容生成**: 检查WordPress后台是否有新生成的内容

## 监控和维护

### 日志监控

```bash
# 实时查看日志
tail -f /path/to/plugin/batch-generate.log

# 查看最近的错误
grep "错误\|失败" /path/to/plugin/batch-generate.log

# 查看成功率统计
grep "成功率" /path/to/plugin/batch-generate.log
```

### 状态检查

```bash
# 查看当前进度
cat /path/to/plugin/batch-status.json

# 重置进度（谨慎使用）
php batch-generate-cron.php 0 reset
```

## 常见问题

### Q1: 脚本提示"无法找到WordPress根目录"

**解决方案**:
1. 确保脚本位于WordPress插件目录中
2. 检查WordPress是否正确安装
3. 验证 `wp-config.php` 文件是否存在

### Q2: 数据库连接失败

**解决方案**:
1. 检查 `wp-config.php` 中的数据库配置
2. 确保数据库服务正常运行
3. 验证数据库用户权限

### Q3: API调用失败

**解决方案**:
1. 检查DeepSeek API密钥配置
2. 验证网络连接
3. 查看API使用限制

### Q4: 内存不足错误

**解决方案**:
1. 调整PHP内存限制：`ini_set('memory_limit', '1024M')`
2. 减少每次处理的数量
3. 优化服务器配置

## 性能优化

### 服务器配置建议

| 服务器规格 | 建议配置 | 每次处理数量 | 执行频率 |
|-----------|---------|-------------|---------|
| 1核1G     | 基础配置 | 5-10个      | 每2小时  |
| 2核2G     | 标准配置 | 10-20个     | 每小时   |
| 4核4G+    | 高性能   | 20-50个     | 每30分钟 |

### 优化建议

1. **合理设置处理数量**: 避免一次处理过多导致超时
2. **监控API使用**: 注意API调用频率限制
3. **定期清理日志**: 避免日志文件过大
4. **备份状态文件**: 定期备份 `batch-status.json`

## 安全注意事项

1. **API密钥保护**: 确保API密钥不被泄露
2. **文件权限**: 设置适当的文件权限
3. **日志安全**: 避免在日志中记录敏感信息
4. **定期更新**: 保持插件和WordPress版本更新

## 技术支持

如果您在部署过程中遇到问题，请：

1. 查看详细的错误日志
2. 检查服务器环境配置
3. 验证WordPress和插件版本兼容性
4. 联系技术支持获取帮助

---

**注意**: 本指南基于标准的WordPress环境。如果您的环境有特殊配置，可能需要相应调整。