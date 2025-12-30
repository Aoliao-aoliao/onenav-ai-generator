# OneNav AI Generator - 升级指南

**当前版本**: 1.2.0
**发布日期**: 2025-12-30

---

## 📦 升级到 v1.2.0

### 从 v1.0.0 或 v1.1.0 升级

#### ✅ 升级前准备

1. **备份数据库** (重要!)
   ```bash
   # 通过 WP-CLI
   wp db export onenav-backup-$(date +%Y%m%d).sql

   # 或通过宝塔面板
   # 数据库 → 选择数据库 → 备份
   ```

2. **备份旧版本插件文件**
   ```bash
   # 进入插件目录
   cd /www/wwwroot/你的网站/wp-content/plugins/

   # 备份旧版本
   cp -r onenav-ai-generator onenav-ai-generator.backup
   ```

3. **记录当前配置**
   - 进入 **设置 → OneNav AI Generator**
   - 记录当前的 API Key (如果需要)
   - 截图保存当前配置

---

#### 🚀 升级步骤

##### 方法一: 直接覆盖 (推荐)

1. **下载新版本**
   - 解压 `onenav-ai-generator-v1.2.0.zip`

2. **通过宝塔面板上传**
   - 登录宝塔面板
   - 进入 **文件管理**
   - 导航到 `/www/wwwroot/你的网站/wp-content/plugins/`
   - 删除旧的 `onenav-ai-generator` 文件夹 (或重命名为 `.backup`)
   - 上传新的 `onenav-ai-generator` 文件夹
   - 设置权限为 `755`

3. **通过 FTP 上传**
   - 连接 FTP
   - 进入 `wp-content/plugins/`
   - 删除旧的 `onenav-ai-generator` 文件夹
   - 上传新的 `onenav-ai-generator` 文件夹

##### 方法二: 通过 WordPress 后台

1. **停用旧插件**
   - 进入 **插件 → 已安装的插件**
   - 停用 **OneNav AI Generator**

2. **删除旧插件**
   - 点击 **删除** (配置会保留在数据库中)

3. **安装新版本**
   - 进入 **插件 → 安装插件 → 上传插件**
   - 选择 `onenav-ai-generator-v1.2.0.zip`
   - 点击 **现在安装**

4. **激活插件**
   - 安装完成后点击 **启用插件**

---

#### ⚙️ 升级后配置

##### 1. 检查配置是否保留

进入 **设置 → OneNav AI Generator**,检查:

- ✅ API Key 是否还在 (已加密存储)
- ✅ 内容结构模板是否保留
- ✅ 自定义提示词是否保留
- ✅ 最大生成字数设置

##### 2. 配置新功能

您会看到两个新配置项:

**AI 服务商选择**:
- 默认选择: **DeepSeek** (保持原有功能)
- 可选项: 阿里云、七牛云、豆包、OpenAI、自定义

**AI 模型选择**:
- 默认模型: **deepseek-chat** (保持原有功能)
- 根据服务商自动显示可用模型

##### 3. 切换到其他 AI 服务商 (可选)

如果想使用阿里云或其他服务商:

1. **选择 AI 服务商**
   - 下拉菜单中选择,如 **"阿里云通义千问"**
   - API URL 会自动填充

2. **选择 AI 模型**
   - 自动显示该服务商的可用模型
   - 选择推荐模型,如 **"qwen-plus"**

3. **输入新的 API Key**
   - 清空原有的 DeepSeek API Key
   - 输入新服务商的 API Key

4. **测试连接**
   - 点击 **"测试连接"** 按钮
   - 确认配置正确

5. **保存设置**
   - 点击 **"保存更改"**

---

#### ✅ 升级验证

##### 测试清单

- [ ] 插件成功激活
- [ ] 设置页面正常显示
- [ ] 看到"AI 服务商"和"AI 模型"两个新配置项
- [ ] API 连接测试成功
- [ ] 单个网址生成功能正常
- [ ] 批量生成功能正常
- [ ] 统计信息正常显示

##### 功能测试

**1. 测试 API 连接**
```
设置 → OneNav AI Generator → 测试连接
预期结果: "API连接测试成功!"
```

**2. 测试单个生成**
```
编辑或新建 sites 文章 → 点击"AI生成正文"
预期结果: 内容成功生成并填入编辑器
```

**3. 测试批量生成**
```
设置 → OneNav AI Generator → 开始生成介绍
预期结果: 批量任务正常运行,进度条显示
```

**4. 测试服务商切换**
```
设置 → OneNav AI Generator
1. 切换 AI 服务商
2. 观察 API URL 是否自动填充
3. 观察模型列表是否更新
```

---

## 🔧 常见升级问题

### Q1: 升级后 API 连接失败?

**可能原因**: 配置未正确迁移

**解决方案**:
1. 重新输入 API Key
2. 点击"保存更改"
3. 再次点击"测试连接"

### Q2: 升级后看不到新功能?

**可能原因**: 浏览器缓存

**解决方案**:
1. 清空浏览器缓存 (Ctrl+Shift+Delete)
2. 强制刷新页面 (Ctrl+F5)
3. 重新登录 WordPress 后台

### Q3: 数据库错误?

**可能原因**: 文件权限问题

**解决方案**:
```bash
# 通过 SSH 设置权限
cd /www/wwwroot/你的网站/wp-content/plugins/
chmod -R 755 onenav-ai-generator
chown -R www:www onenav-ai-generator
```

### Q4: 批量生成突然停止?

**可能原因**: PHP 超时或内存不足

**解决方案**:
1. 检查 PHP 错误日志
2. 增加 PHP 执行时间和内存限制:
```php
// 在 wp-config.php 中添加
set_time_limit(300);
ini_set('memory_limit', '256M');
```

### Q5: 插件激活失败?

**可能原因**: PHP 版本不兼容或缺少扩展

**解决方案**:
1. 检查 PHP 版本 >= 7.4
2. 确保安装了 OpenSSL 扩展:
```bash
php -m | grep openssl
```

---

## 🔄 回滚到旧版本

如果升级后遇到严重问题,可以回滚:

### 方法一: 使用备份文件

```bash
# 删除新版本
rm -rf /www/wwwroot/你的网站/wp-content/plugins/onenav-ai-generator

# 恢复备份
mv onenav-ai-generator.backup onenav-ai-generator
```

### 方法二: 通过 WordPress 后台

1. 停用并删除当前插件
2. 重新上传旧版本 ZIP 文件
3. 激活插件

### 恢复数据库配置

如果需要恢复配置:

```sql
-- 查看当前配置
SELECT * FROM wp_options WHERE option_name = 'onenav_ai_generator_options';

-- 如果有备份,可以手动恢复
UPDATE wp_options
SET option_value = '旧的配置值'
WHERE option_name = 'onenav_ai_generator_options';
```

---

## 📊 版本兼容性

| 从版本 | 到版本 | 直接升级 | 需要额外步骤 |
|--------|--------|---------|-------------|
| v1.0.0 | v1.2.0 | ✅ 是 | ❌ 否 |
| v1.1.0 | v1.2.0 | ✅ 是 | ❌ 否 |
| v1.0.0 | v1.1.0 | ✅ 是 | ⚠️ 需重新保存 API Key |

---

## 📝 升级后的新功能使用

### 1. 切换到阿里云通义千问

```
1. 进入"设置 → OneNav AI Generator"
2. AI 服务商: 选择"阿里云通义千问"
3. AI 模型: 选择"qwen-plus"
4. API Key: 输入阿里云 API Key (sk-xxxxx)
5. API URL: 自动填充为阿里云地址
6. 点击"测试连接"验证
7. 点击"保存更改"
```

### 2. 使用多个 AI 服务商

虽然当前版本只支持单个活跃服务商,但您可以:

1. **记录多个服务商的 API Key**
2. **根据需求切换**:
   - 日常批量生成 → 使用 qwen-turbo (便宜)
   - 重要内容 → 使用 gpt-4 (质量高)
   - 测试新功能 → 使用 DeepSeek (免费额度)

### 3. 自定义 API 配置

如果使用其他 AI 服务:

```
1. AI 服务商: 选择"自定义 API"
2. AI 模型: 输入实际的模型名称
3. API Key: 输入 API Key
4. API URL: 输入完整的 API 地址
```

---

## 🎯 升级建议

### 推荐升级

如果您符合以下情况,强烈建议升级:

- ✅ 想要使用国内 AI 服务商 (阿里云、豆包等)
- ✅ 需要更灵活的 AI 模型选择
- ✅ 关心 API Key 安全性
- ✅ 需要更好的性能 (N+1 查询优化)

### 可选升级

如果您符合以下情况,可以选择性升级:

- ⚠️ 当前 DeepSeek API 使用正常
- ⚠️ 不需要切换 AI 服务商
- ⚠️ 生产环境,担心兼容性问题

建议先在测试环境验证后再升级。

---

## 📞 获取帮助

升级过程中遇到问题:

1. **查看日志**:
   - WordPress: `wp-content/debug.log`
   - 插件: `wp-content/plugins/onenav-ai-generator/batch-generate.log`

2. **检查文档**:
   - [AI 服务商配置指南](AI-PROVIDERS-GUIDE.md)
   - [更新日志](CHANGELOG-v1.2.0.md)
   - [安全修复报告](FINAL-SECURITY-FIXES.md)

3. **联系支持**:
   - GitHub Issues
   - WordPress 论坛
   - 插件作者

---

**升级前请务必备份!** ⚠️

祝您升级顺利! 🎉
