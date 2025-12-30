#!/bin/bash

# OneNav AI Generator 批量生成计划任务安装脚本
# 用于快速部署宝塔计划任务功能

echo "=== OneNav AI Generator 批量生成计划任务安装脚本 ==="
echo ""

# 检查是否为root用户
if [ "$EUID" -ne 0 ]; then
    echo "请使用root权限运行此脚本"
    echo "使用方法: sudo bash install-batch-cron.sh"
    exit 1
fi

# 获取当前脚本所在目录
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
echo "脚本目录: $SCRIPT_DIR"

# 检查必要文件是否存在
if [ ! -f "$SCRIPT_DIR/batch-generate-cron.php" ]; then
    echo "错误: 找不到 batch-generate-cron.php 文件"
    exit 1
fi

# 设置文件权限
echo "设置文件权限..."
chmod 755 "$SCRIPT_DIR/batch-generate-cron.php"
chmod 755 "$SCRIPT_DIR"

# 查找PHP路径
echo "查找PHP路径..."
PHP_PATH=""
for path in /usr/bin/php /www/server/php/74/bin/php /www/server/php/80/bin/php /www/server/php/81/bin/php /opt/remi/php74/root/usr/bin/php /opt/remi/php80/root/usr/bin/php; do
    if [ -x "$path" ]; then
        PHP_PATH="$path"
        echo "找到PHP: $PHP_PATH"
        break
    fi
done

if [ -z "$PHP_PATH" ]; then
    echo "警告: 未找到PHP可执行文件，请手动指定PHP路径"
    PHP_PATH="/usr/bin/php"
fi

# 测试PHP脚本
echo "测试PHP脚本..."
if $PHP_PATH "$SCRIPT_DIR/batch-generate-cron.php" 0 > /dev/null 2>&1; then
    echo "PHP脚本测试成功"
else
    echo "警告: PHP脚本测试失败，请检查WordPress配置"
fi

# 生成宝塔计划任务命令
echo ""
echo "=== 宝塔计划任务配置 ==="
echo ""
echo "请在宝塔面板中创建计划任务，使用以下配置："
echo ""
echo "任务类型: Shell脚本"
echo "任务名称: OneNav AI 批量生成"
echo "执行周期: 每5分钟执行一次（或根据需要调整）"
echo ""
echo "脚本内容:"
echo "#!/bin/bash"
echo "cd $SCRIPT_DIR"
echo "$PHP_PATH batch-generate-cron.php 10"
echo ""

# 生成配置文件
CONFIG_FILE="$SCRIPT_DIR/baota-cron-config.txt"
cat > "$CONFIG_FILE" << EOF
# OneNav AI Generator 宝塔计划任务配置

## 基本信息
脚本路径: $SCRIPT_DIR/batch-generate-cron.php
PHP路径: $PHP_PATH
配置时间: $(date)

## 宝塔计划任务配置
任务类型: Shell脚本
任务名称: OneNav AI 批量生成
执行周期: 每5分钟执行一次

## 脚本内容
#!/bin/bash
cd $SCRIPT_DIR
$PHP_PATH batch-generate-cron.php 10

## 常用命令
# 处理10个网址（默认）
$PHP_PATH batch-generate-cron.php 10

# 处理5个网址（适合低配置服务器）
$PHP_PATH batch-generate-cron.php 5

# 重置生成进度
$PHP_PATH batch-generate-cron.php 10 reset

# 查看统计信息
$PHP_PATH batch-generate-cron.php 0

## 日志文件
生成日志: $SCRIPT_DIR/batch-generate.log
状态文件: $SCRIPT_DIR/batch-status.json

## 监控命令
# 查看最新日志
tail -f $SCRIPT_DIR/batch-generate.log

# 查看状态文件
cat $SCRIPT_DIR/batch-status.json
EOF

echo "配置信息已保存到: $CONFIG_FILE"
echo ""

# 创建快捷脚本
SHORTCUT_SCRIPT="$SCRIPT_DIR/run-batch.sh"
cat > "$SHORTCUT_SCRIPT" << EOF
#!/bin/bash
# OneNav AI Generator 批量生成快捷脚本

cd "$SCRIPT_DIR"

case "\$1" in
    "start")
        echo "开始批量生成（每次处理10个）..."
        $PHP_PATH batch-generate-cron.php 10
        ;;
    "start5")
        echo "开始批量生成（每次处理5个）..."
        $PHP_PATH batch-generate-cron.php 5
        ;;
    "start20")
        echo "开始批量生成（每次处理20个）..."
        $PHP_PATH batch-generate-cron.php 20
        ;;
    "reset")
        echo "重置生成进度..."
        $PHP_PATH batch-generate-cron.php 10 reset
        ;;
    "status")
        echo "查看统计信息..."
        $PHP_PATH batch-generate-cron.php 0
        ;;
    "log")
        echo "查看最新日志..."
        tail -20 "$SCRIPT_DIR/batch-generate.log"
        ;;
    "logf")
        echo "实时查看日志..."
        tail -f "$SCRIPT_DIR/batch-generate.log"
        ;;
    *)
        echo "OneNav AI Generator 批量生成快捷脚本"
        echo ""
        echo "使用方法:"
        echo "  \$0 start     - 开始批量生成（每次10个）"
        echo "  \$0 start5    - 开始批量生成（每次5个）"
        echo "  \$0 start20   - 开始批量生成（每次20个）"
        echo "  \$0 reset     - 重置生成进度"
        echo "  \$0 status    - 查看统计信息"
        echo "  \$0 log       - 查看最新日志"
        echo "  \$0 logf      - 实时查看日志"
        ;;
esac
EOF

chmod +x "$SHORTCUT_SCRIPT"
echo "快捷脚本已创建: $SHORTCUT_SCRIPT"
echo ""

# 显示使用说明
echo "=== 安装完成 ==="
echo ""
echo "1. 请在宝塔面板中创建计划任务，使用上述配置"
echo "2. 可以使用快捷脚本进行测试: bash $SHORTCUT_SCRIPT status"
echo "3. 查看详细配置说明: cat $CONFIG_FILE"
echo "4. 查看完整文档: cat $SCRIPT_DIR/BAOTA-CRON-SETUP.md"
echo ""
echo "测试命令:"
echo "  bash $SHORTCUT_SCRIPT status   # 查看统计信息"
echo "  bash $SHORTCUT_SCRIPT start5   # 测试处理5个网址"
echo ""
echo "安装完成！"