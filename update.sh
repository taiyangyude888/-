#!/bin/bash
# 玄象自动更新脚本

echo "===== 玄象 v4.0 自动更新 ====="

# 备份旧数据
if [ -d "data" ]; then
  echo "正在备份数据..."
  cp -r data data_backup_$(date +%Y%m%d_%H%M%S)
  echo "✓ 数据已备份"
fi

# 解压新文件（覆盖除data外的所有文件）
echo "正在更新文件..."
unzip -o xuanxiang-php-v4.0-final.zip -x "data/*"

# 恢复权限
chmod 755 data
chmod 644 data/*.json 2>/dev/null

echo "✓ 更新完成！"
echo ""
echo "访问地址："
echo "前台: http://ai.olbaba.com"
echo "后台: http://ai.olbaba.com/admin/"
