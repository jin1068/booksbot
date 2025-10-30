<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>检查轮播图模块配置</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .info { background: #d1ecf1; padding: 15px; border-left: 4px solid #0c5460; margin: 10px 0; }
        .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #856404; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>

<?php
require_once('config.php');

$link = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

if ($link->connect_error) {
    die("<div class='warning'>连接失败: " . $link->connect_error . "</div>");
}

$link->set_charset('utf8mb4');

echo "<h2>🔍 检查首页布局配置</h2>";

// 1. 查找首页布局 - 修复: layout表中没有route字段,直接查找layout_id=1的首页布局
$sql = "SELECT * FROM " . DB_PREFIX . "layout WHERE layout_id = 1";
$result = $link->query($sql);

if ($result && $result->num_rows > 0) {
    $layout = $result->fetch_assoc();
    echo "<div class='info'><strong>首页布局:</strong> ID = {$layout['layout_id']}, 名称 = {$layout['name']}</div>";
    $home_layout_id = $layout['layout_id'];
} else {
    echo "<div class='warning'>⚠️ 找不到首页布局!</div>";
    exit;
}

echo "<h2>📦 首页 content_top 位置的所有模块</h2>";

// 2. 查询首页content_top的模块
$sql = "SELECT * FROM " . DB_PREFIX . "layout_module 
        WHERE layout_id = $home_layout_id AND position = 'content_top'
        ORDER BY sort_order";
$result = $link->query($sql);

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>模块ID</th><th>模块代码</th><th>位置</th><th>排序</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['layout_module_id']}</td>";
        echo "<td><strong>{$row['code']}</strong></td>";
        echo "<td>{$row['position']}</td>";
        echo "<td>{$row['sort_order']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'>⚠️ content_top 位置没有配置任何模块!</div>";
}

echo "<h2>🎠 所有轮播图/Banner模块</h2>";

// 3. 查找所有banner相关模块
$sql = "SELECT * FROM " . DB_PREFIX . "layout_module 
        WHERE code LIKE '%banner%' OR code LIKE '%carousel%' OR code LIKE '%slideshow%'
        ORDER BY layout_id, position, sort_order";
$result = $link->query($sql);

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>布局ID</th><th>模块代码</th><th>位置</th><th>排序</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['layout_id']}</td>";
        echo "<td><strong>{$row['code']}</strong></td>";
        echo "<td>{$row['position']}</td>";
        echo "<td>{$row['sort_order']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'>⚠️ 没有找到任何轮播图模块!</div>";
}

echo "<h2>🔧 所有已安装的模块</h2>";

// 4. 查看所有模块设置
$sql = "SELECT module_id, name, code, setting FROM " . DB_PREFIX . "module 
        WHERE code LIKE '%banner%' OR code LIKE '%carousel%' OR code LIKE '%slideshow%'";
$result = $link->query($sql);

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>模块ID</th><th>名称</th><th>代码</th><th>状态</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $setting = json_decode($row['setting'], true);
        $status = isset($setting['status']) && $setting['status'] ? '✅ 启用' : '❌ 禁用';
        echo "<tr>";
        echo "<td>{$row['module_id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td><strong>{$row['code']}</strong></td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
        
        // 显示详细配置
        echo "<tr><td colspan='4'><pre>" . print_r($setting, true) . "</pre></td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'>⚠️ 没有找到轮播图模块配置!</div>";
}

echo "<h2>🌍 检查语言相关配置</h2>";

// 5. 检查语言设置
$sql = "SELECT * FROM " . DB_PREFIX . "language";
$result = $link->query($sql);

echo "<table>";
echo "<tr><th>语言ID</th><th>名称</th><th>代码</th><th>状态</th></tr>";
while ($row = $result->fetch_assoc()) {
    $status = $row['status'] ? '✅ 启用' : '❌ 禁用';
    echo "<tr>";
    echo "<td>{$row['language_id']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td><strong>{$row['code']}</strong></td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}
echo "</table>";

$link->close();
?>

<div class="info">
    <strong>💡 提示:</strong> 如果content_top位置没有轮播图模块,需要在后台【设计 → 布局】中为首页的content_top位置添加轮播图模块。
</div>

</body>
</html>
