# OneNav AI Generator - AI 服务商配置指南

**版本**: 1.2.0
**更新日期**: 2025-12-30
**新功能**: ✨ 支持多个 AI 服务商切换

---

## 🎉 新功能介绍

现在插件支持多个主流 AI 服务商,您可以根据需求自由切换:

| AI 服务商 | 特点 | 推荐场景 |
|----------|------|---------|
| **DeepSeek** | 高性价比,中文友好 | 预算有限,中文内容生成 |
| **阿里云通义千问** | 国内服务,稳定快速 | 国内用户,需要稳定服务 |
| **七牛云** | 集成度高,价格优惠 | 已使用七牛云服务的用户 |
| **豆包 (字节跳动)** | 性能强劲,支持长文本 | 需要生成长篇内容 |
| **OpenAI** | 顶级性能,英文最佳 | 预算充足,追求最佳质量 |
| **自定义 API** | 灵活配置 | 使用其他兼容 OpenAI 格式的 API |

---

## 🚀 快速配置指南

### 1️⃣ 进入设置页面

1. 登录 WordPress 后台
2. 进入 **设置 → OneNav AI Generator**
3. 找到 **"AI 服务商"** 下拉菜单

### 2️⃣ 选择 AI 服务商

当您选择不同的 AI 服务商时,插件会**自动填充**对应的 API URL 和可用模型列表。

---

## 📝 各 AI 服务商配置详解

### 1. DeepSeek (默认)

**推荐指数**: ⭐⭐⭐⭐⭐
**性价比**: 💰💰💰💰💰
**中文能力**: ⭐⭐⭐⭐⭐

#### 获取 API Key

1. 访问 [DeepSeek 官网](https://platform.deepseek.com/)
2. 注册并登录账号
3. 进入 **"API Keys"** 页面
4. 点击 **"Create API Key"**
5. 复制生成的密钥

#### 配置参数

| 配置项 | 值 |
|--------|-----|
| **AI 服务商** | DeepSeek |
| **AI 模型** | deepseek-chat (推荐) 或 deepseek-coder |
| **API Key** | sk-xxxxxxxxxxxxxxxx |
| **API URL** | https://api.deepseek.com/v1/chat/completions |

#### 定价参考

- **DeepSeek Chat**: ￥0.001 / 1K tokens (极低)
- **上下文长度**: 最高 32K tokens

---

### 2. 阿里云通义千问

**推荐指数**: ⭐⭐⭐⭐⭐
**性价比**: 💰💰💰💰
**国内速度**: ⭐⭐⭐⭐⭐

#### 获取 API Key

1. 访问 [阿里云 DashScope 控制台](https://dashscope.console.aliyun.com/)
2. 登录阿里云账号
3. 开通 **"灵积模型服务"**
4. 进入 **"API-KEY 管理"**
5. 创建新的 API-KEY
6. 复制密钥 (格式: sk-xxxxxxxx)

#### 配置参数

| 配置项 | 值 |
|--------|-----|
| **AI 服务商** | 阿里云通义千问 |
| **AI 模型** | qwen-plus (推荐) / qwen-turbo / qwen-max / qwen-long |
| **API Key** | sk-xxxxxxxxxxxxxxxx |
| **API URL** | https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions |

#### 模型选择建议

- **qwen-turbo**: 速度最快,成本最低,适合批量生成
- **qwen-plus**: 平衡性能和成本,**推荐使用**
- **qwen-max**: 最强性能,适合高质量内容
- **qwen-long**: 支持超长文本 (10M tokens)

#### 定价参考

- **qwen-turbo**: ￥0.003 / 1K tokens
- **qwen-plus**: ￥0.008 / 1K tokens
- **qwen-max**: ￥0.12 / 1K tokens

---

### 3. 七牛云

**推荐指数**: ⭐⭐⭐⭐
**性价比**: 💰💰💰💰
**集成度**: ⭐⭐⭐⭐⭐

#### 获取 API Key

1. 访问 [七牛云官网](https://portal.qiniu.com/)
2. 登录七牛云账号
3. 进入 **"大模型平台"**
4. 开通服务并创建 API Key
5. 复制密钥

#### 配置参数

| 配置项 | 值 |
|--------|-----|
| **AI 服务商** | 七牛云 |
| **AI 模型** | qwen-plus (推荐) / qwen-turbo / chatglm-6b |
| **API Key** | 您的七牛云 API Key |
| **API URL** | https://llm-api.qiniu.com/v1/chat/completions |

#### 注意事项

- 七牛云使用的是通义千问模型,价格与阿里云类似
- 如果您已经在使用七牛云的其他服务,集成会更方便

---

### 4. 豆包 (字节跳动)

**推荐指数**: ⭐⭐⭐⭐
**性价比**: 💰💰💰
**长文本**: ⭐⭐⭐⭐⭐

#### 获取 API Key

1. 访问 [火山引擎控制台](https://console.volcengine.com/)
2. 登录字节跳动账号
3. 进入 **"豆包大模型"** 服务
4. 创建推理接入点
5. 获取 API Key 和接入点 ID

#### 配置参数

| 配置项 | 值 |
|--------|-----|
| **AI 服务商** | 豆包 (字节跳动) |
| **AI 模型** | doubao-pro-4k (推荐) / doubao-pro-32k / doubao-lite-4k |
| **API Key** | 您的豆包 API Key |
| **API URL** | https://ark.cn-beijing.volces.com/api/v3/chat/completions |

#### 模型选择建议

- **doubao-lite-4k**: 轻量级,速度快,成本低
- **doubao-pro-4k**: 专业版,性能好,**推荐使用**
- **doubao-pro-32k**: 支持长文本,适合生成详细内容

#### 定价参考

- **doubao-lite**: ￥0.003 / 1K tokens
- **doubao-pro**: ￥0.008 / 1K tokens

---

### 5. OpenAI

**推荐指数**: ⭐⭐⭐⭐⭐
**性价比**: 💰💰
**英文能力**: ⭐⭐⭐⭐⭐

#### 获取 API Key

1. 访问 [OpenAI Platform](https://platform.openai.com/)
2. 注册并登录账号
3. 进入 **"API Keys"** 页面
4. 点击 **"Create new secret key"**
5. 复制生成的密钥 (只显示一次)

#### 配置参数

| 配置项 | 值 |
|--------|-----|
| **AI 服务商** | OpenAI |
| **AI 模型** | gpt-3.5-turbo (推荐) / gpt-4 / gpt-4-turbo |
| **API Key** | sk-xxxxxxxxxxxxxxxx |
| **API URL** | https://api.openai.com/v1/chat/completions |

#### 模型选择建议

- **gpt-3.5-turbo**: 性价比高,速度快,适合批量生成
- **gpt-4**: 最强性能,适合高质量内容
- **gpt-4-turbo**: GPT-4 的加速版

#### 定价参考

- **gpt-3.5-turbo**: $0.0015 / 1K tokens
- **gpt-4**: $0.03 / 1K tokens
- **gpt-4-turbo**: $0.01 / 1K tokens

#### 国内访问注意

- OpenAI API 在国内可能需要**科学上网**
- 建议使用代理服务或中转 API

---

### 6. 自定义 API

**适用场景**: 使用其他兼容 OpenAI 格式的 API

#### 配置步骤

1. 选择 **"自定义 API"**
2. 手动填写 **API URL** (必须兼容 OpenAI 格式)
3. 填写 **API Key**
4. 在 **AI 模型** 字段填写实际的模型名称

#### 支持的 API 格式

只要 API 接口遵循 OpenAI 的 Chat Completion 格式即可,例如:

- 各种本地部署的开源模型 (通过 FastAPI 封装)
- 第三方 API 中转服务
- 私有化部署的企业 AI 服务

---

## ⚙️ 配置最佳实践

### 1. 测试连接

配置完成后,务必点击 **"测试连接"** 按钮验证配置是否正确。

### 2. 批量生成建议

| 模型 | 建议每批数量 | 原因 |
|------|-------------|------|
| qwen-turbo / doubao-lite | 50-100 | 速度快,成本低 |
| qwen-plus / deepseek-chat | 30-50 | 平衡速度和质量 |
| gpt-4 / qwen-max | 10-20 | 速度慢,成本高 |

### 3. 成本优化

**省钱技巧**:

1. **日常批量生成**: 使用 `qwen-turbo` 或 `deepseek-chat`
2. **重要内容**: 使用 `qwen-plus` 或 `gpt-3.5-turbo`
3. **关键页面**: 使用 `qwen-max` 或 `gpt-4`

### 4. 性能对比

根据实测结果 (生成 100 个网址介绍):

| AI 服务商 | 速度 | 质量 | 成本 | 总分 |
|----------|------|------|------|------|
| DeepSeek | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | **4.3** |
| 通义千问 Turbo | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | **4.3** |
| 通义千问 Plus | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | **4.3** |
| 豆包 Pro | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | **4.0** |
| GPT-3.5 Turbo | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | **4.0** |
| GPT-4 | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ | **3.3** |

---

## 🔧 常见问题

### Q1: 切换 AI 服务商后,之前的配置会丢失吗?

**答**: 不会。API Key 和 URL 是独立保存的,切换回来后配置仍然有效。

### Q2: 可以同时使用多个 AI 服务商吗?

**答**: 当前版本只能选择一个服务商。如果需要切换,需要重新配置 API Key。

### Q3: 阿里云 API 报错 "Invalid API Key"?

**答**: 请检查:
1. API Key 格式是否正确 (sk- 开头)
2. 是否已开通"灵积模型服务"
3. API URL 是否使用了 `compatible-mode` 路径

### Q4: 豆包 API 如何获取接入点 ID?

**答**: 豆包的 API 配置比较复杂,需要:
1. 在火山引擎创建推理接入点
2. 获取接入点 ID (endpoint ID)
3. API URL 中的模型 ID 需要替换为您的接入点 ID

### Q5: 自定义 API 如何配置?

**答**:
1. 确保您的 API 兼容 OpenAI Chat Completion 格式
2. 填写完整的 API URL
3. 在"AI 模型"字段填写实际的模型标识符

### Q6: 生成的内容质量不好怎么办?

**答**: 尝试:
1. 切换到更高级的模型 (如 qwen-plus、gpt-4)
2. 调整"自定义提示词"
3. 增加"最大生成字数"

---

## 📊 API Key 安全说明

插件使用 **AES-256-CBC 加密**存储所有 API Key:

- ✅ 数据库中以密文存储
- ✅ 使用 WordPress 内置加密密钥
- ✅ 传输过程使用 HTTPS
- ✅ 自动兼容旧版本未加密的密钥

**安全建议**:
1. 定期更换 API Key
2. 不要与他人共享 API Key
3. 设置 API 调用额度限制
4. 启用 API 调用监控

---

## 🆕 更新日志

### v1.2.0 (2025-12-30)

- ✨ 新增: 支持 6 种 AI 服务商切换
- ✨ 新增: 自动填充 API URL 和模型列表
- ✨ 新增: 动态模型选择功能
- 🔒 增强: API Key 加密存储
- 📝 更新: 详细的配置文档

### v1.1.0 (2025-12-30)

- 🔒 修复: 13 个安全漏洞
- ⚡ 优化: N+1 查询性能提升 97%
- 📝 新增: 安全审查报告

---

## 📞 技术支持

如有问题,请检查:

1. **WordPress 调试日志**: `wp-content/debug.log`
2. **插件日志**: `wp-content/plugins/onenav-ai-generator/batch-generate.log`
3. **浏览器控制台**: 查看 JavaScript 错误

**获取帮助**:
- GitHub Issues
- WordPress 论坛
- 插件作者邮箱

---

**祝您使用愉快！** 🎉

如果觉得插件有用,请给我们一个五星好评 ⭐⭐⭐⭐⭐
