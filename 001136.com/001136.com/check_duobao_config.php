<?php
// 检查 duobao 模块配置
require_once(__DIR__ . '/config.php');

$conn = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

echo "=== 检查 duobao 模块布局配置 ===\n\n";

// 检查layout_module表
$sql = "SELECT lm.*, l.name as layout_name 
        FROM " . DB_PREFIX . "layout_module lm 
        LEFT JOIN " . DB_PREFIX . "layout l ON lm.layout_id = l.layout_id 
        WHERE lm.code LIKE '%duobao%'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "找到 duobao 模块配置:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  Layout: {$row['layout_name']} (ID: {$row['layout_id']})\n";
        echo "  Position: {$row['position']}\n";
        echo "  Code: {$row['code']}\n";
        echo "  Sort Order: {$row['sort_order']}\n\n";
    }
} else {
    echo "❌ 未找到 duobao 模块配置!\n";
    echo "可能原因:\n";
    echo "  1. 模块未在后台布局中启用\n";
    echo "  2. 模块代码名称不匹配\n\n";
}

// 检查module表
$sql2 = "SELECT * FROM " . DB_PREFIX . "module WHERE code = 'duobao'";
$result2 = $conn->query($sql2);

if ($result2 && $result2->num_rows > 0) {
    echo "找到 duobao 模块设置:\n";
    while ($row = $result2->fetch_assoc()) {
        echo "  Module ID: {$row['module_id']}\n";
        echo "  Name: {$row['name']}\n";
        echo "  Code: {$row['code']}\n";
        echo "  Setting: " . substr($row['setting'], 0, 200) . "...\n\n";
    }
} else {
    echo "❌ 未找到 duobao 模块记录\n\n";
}

// 检查首页布局ID
$sql3 = "SELECT layout_id, name FROM " . DB_PREFIX . "layout WHERE name LIKE '%home%' OR route = 'common/home'";
$result3 = $conn->query($sql3);

if ($result3 && $result3->num_rows > 0) {
    echo "首页布局:\n";
    while ($row = $result3->fetch_assoc()) {
        echo "  Layout ID: {$row['layout_id']}, Name: {$row['name']}\n";
    }
    echo "\n";
}

$conn->close();
echo "检查完成!\n";
