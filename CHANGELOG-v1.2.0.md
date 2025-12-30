# OneNav AI Generator - 更新日志 v1.2.0

**发布日期**: 2025-12-30
**版本号**: 1.2.0
**更新类型**: 重大功能更新

---

## 🎉 重大更新

### ✨ 新增功能

#### 1. 多 AI 服务商支持

现在插件支持 **6 种主流 AI 服务商**,可以在后台自由切换:

| AI 服务商 | 支持模型 | 推荐场景 |
|----------|---------|---------|
| 🤖 **DeepSeek** | deepseek-chat, deepseek-coder | 高性价比,中文友好 |
| ☁️ **阿里云通义千问** | qwen-plus, qwen-turbo, qwen-max, qwen-long | 国内用户,稳定快速 |
| 🎯 **七牛云** | qwen-plus, qwen-turbo, chatglm-6b | 已有七牛云服务 |
| 🚀 **豆包 (字节跳动)** | doubao-pro-4k, doubao-pro-32k, doubao-lite-4k | 长文本生成 |
| 🌟 **OpenAI** | gpt-3.5-turbo, gpt-4, gpt-4-turbo | 顶级质量 |
| ⚙️ **自定义 API** | 任意兼容 OpenAI 格式的模型 | 灵活配置 |

**功能亮点**:
- ✅ 下拉菜单一键切换服务商
- ✅ 自动填充对应的 API URL
- ✅ 动态显示可用模型列表
- ✅ 智能记忆每个服务商的配置

#### 2. 智能配置界面

**新增配置项**:
- **AI 服务商选择**: 下拉菜单选择要使用的服务商
- **AI 模型选择**: 根据服务商自动显示可用模型
- **API Key**: 统一的密钥输入框 (支持所有服务商)
- **API URL**: 自动填充,也可手动修改

**用户体验优化**:
- 切换服务商时自动填充 URL ✨
- 切换服务商时自动更新模型列表 ✨
- 实时 JavaScript 交互,无需刷新页面 ✨
- 友好的中文提示和说明 ✨

#### 3. 完善的文档支持

新增文件:
- **AI-PROVIDERS-GUIDE.md**: 详细的 AI 服务商配置指南
  - 6 个 AI 服务商的完整配置教程
  - API Key 获取方法
  - 定价参考和模型对比
  - 常见问题解答
  - 成本优化建议

---

## 🔧 技术改进

### 代码优化

**主插件文件 (onenav-ai-generator.php)**:

1. **新增方法**: `get_ai_providers()` (L49-111)
   - 定义所有支持的 AI 服务商配置
   - 包含 API URL、模型列表、默认模型

2. **新增回调函数**: `ai_provider_field_callback()` (L428-474)
   - 渲染 AI 服务商选择下拉菜单
   - JavaScript 自动填充功能

3. **新增回调函数**: `ai_model_field_callback()` (L476-491)
   - 动态显示当前服务商的可用模型

4. **更新方法**: `sanitize_options()` (L346-398)
   - 新增 `ai_provider` 字段验证 (L349-354)
   - 新增 `ai_model` 字段清理 (L356-359)

5. **更新方法**: `generate_content()` (L1413-1452)
   - 动态获取 AI 提供商和模型配置 (L1419-1421)
   - 使用配置的模型替代硬编码 (L1441)

**批处理脚本 (batch-generate-cron.php)**:

1. **更新方法**: `generateContent()` (L415-438)
   - 动态获取 AI 模型配置 (L420-422)
   - 使用配置的模型替代硬编码 (L429)

---

## 🔒 安全性

- ✅ 继承 v1.1.0 的所有安全修复
- ✅ API Key 依然使用 AES-256-CBC 加密存储
- ✅ 输入验证和清理
- ✅ SQL 注入防护
- ✅ XSS 防护

---

## 📊 性能

- ✅ 保持 v1.1.0 的 N+1 查询优化
- ✅ 无额外数据库查询
- ✅ JavaScript 动态交互,提升用户体验
- ✅ 配置数据缓存在 PHP 数组中

---

## 🔄 兼容性

### 向后兼容

- ✅ 完全兼容 v1.0.0 和 v1.1.0
- ✅ 旧版本配置自动迁移
- ✅ 默认使用 DeepSeek (保持原有行为)
- ✅ 未配置 `ai_provider` 时自动设为 `deepseek`
- ✅ 未配置 `ai_model` 时自动设为 `deepseek-chat`

### 升级说明

**从 v1.0.0/v1.1.0 升级到 v1.2.0**:

1. **直接覆盖文件** - 无需额外操作
2. **首次访问设置页面** - 插件会自动添加新配置项
3. **原有 API Key 保留** - 加密存储不受影响
4. **默认使用 DeepSeek** - 保持原有功能

**如果想切换到其他 AI 服务商**:

1. 进入 **设置 → OneNav AI Generator**
2. 在 **"AI 服务商"** 下拉菜单中选择新的服务商
3. 系统自动填充 API URL 和模型列表
4. 输入新服务商的 **API Key**
5. 点击 **"测试连接"** 验证配置
6. 点击 **"保存更改"**

---

## 📝 配置示例

### 示例 1: 切换到阿里云通义千问

```
AI 服务商: 阿里云通义千问
AI 模型: qwen-plus
API Key: sk-xxxxxxxxxxxxxxxxxxxx
API URL: https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions
```

### 示例 2: 使用 OpenAI GPT-4

```
AI 服务商: OpenAI
AI 模型: gpt-4
API Key: sk-xxxxxxxxxxxxxxxxxxxx
API URL: https://api.openai.com/v1/chat/completions
```

### 示例 3: 使用豆包

```
AI 服务商: 豆包 (字节跳动)
AI 模型: doubao-pro-4k
API Key: 您的豆包 API Key
API URL: https://ark.cn-beijing.volces.com/api/v3/chat/completions
```

---

## 🐛 Bug 修复

无新增 Bug 修复 (继承 v1.1.0 的所有修复)

---

## ⚠️ 已知问题

1. **豆包 API 配置复杂**: 需要在火山引擎创建推理接入点,文档中有详细说明
2. **OpenAI 国内访问**: 可能需要科学上网或使用中转 API
3. **七牛云模型有限**: 目前支持的模型较少

---

## 📋 文件变更清单

### 新增文件

- `AI-PROVIDERS-GUIDE.md` - AI 服务商配置指南 (详细文档)
- `CHANGELOG-v1.2.0.md` - 本更新日志

### 修改文件

- `onenav-ai-generator.php` (主插件文件)
  - 新增 6 处代码块
  - 修改 3 处现有代码
  - 总计约 **150 行新增代码**

- `batch-generate-cron.php` (批处理脚本)
  - 修改 1 处代码
  - 总计约 **5 行新增代码**

### 未修改文件

- `assets/js/admin.js` - 前端 JavaScript (未修改)
- `assets/css/admin.css` - 样式文件(未修改)
- `README.md` - 使用说明 (未修改)
- 所有历史文档文件

---

## 🎯 下一步计划 (v1.3.0)

可能的改进方向:

1. **多服务商同时配置**: 保存多个服务商的 API Key,快速切换
2. **智能负载均衡**: 自动在多个 API 之间分配请求
3. **成本统计**: 显示每个服务商的调用次数和预估成本
4. **失败重试**: API 失败时自动切换到备用服务商
5. **模型性能对比**: 内置不同模型的性能测试工具

---

## 📞 获取帮助

**文档**:
- [AI-PROVIDERS-GUIDE.md](AI-PROVIDERS-GUIDE.md) - AI 服务商配置详细指南
- [FINAL-SECURITY-FIXES.md](FINAL-SECURITY-FIXES.md) - 安全修复报告
- [准备上传说明.md](准备上传说明.md) - 部署指南

**支持渠道**:
- GitHub Issues
- WordPress 论坛
- 插件作者邮箱

---

## ⭐ 致谢

感谢所有使用和支持本插件的用户!

如果您觉得这个更新有用,请:
- ⭐ 给项目点个 Star
- 💬 分享给需要的朋友
- 📝 留下您的评价和建议

---

**版本**: 1.2.0
**发布日期**: 2025-12-30
**开发者**: Claude AI
**许可证**: GPL v2 or later

🎉 **感谢使用 OneNav AI Generator!**
