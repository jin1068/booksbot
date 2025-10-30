<?php
// 检查content_top位置的所有模块
require_once('config.php');

$link = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

if ($link->connect_error) {
    die("连接失败: " . $link->connect_error);
}

$link->set_charset('utf8mb4');

echo "=== 检查 content_top 位置的模块配置 ===\n\n";

// 查询所有content_top位置的模块
$sql = "SELECT 
    lm.layout_module_id,
    lm.layout_id,
    lm.code,
    lm.position,
    lm.sort_order,
    l.name as layout_name
FROM " . DB_PREFIX . "layout_module lm
LEFT JOIN " . DB_PREFIX . "layout l ON lm.layout_id = l.layout_id
WHERE lm.position = 'content_top'
ORDER BY l.layout_id, lm.sort_order";

$result = $link->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "布局ID: {$row['layout_id']} | 布局名称: {$row['layout_name']}\n";
        echo "  - 模块代码: {$row['code']}\n";
        echo "  - 位置: {$row['position']}\n";
        echo "  - 排序: {$row['sort_order']}\n";
        echo "  - 模块ID: {$row['layout_module_id']}\n";
        echo str_repeat("-", 60) . "\n";
    }
} else {
    echo "没有找到 content_top 位置的模块!\n";
}

echo "\n=== 检查所有轮播图/Banner相关模块 ===\n\n";

// 查询所有轮播图相关模块
$sql2 = "SELECT 
    lm.layout_module_id,
    lm.layout_id,
    lm.code,
    lm.position,
    lm.sort_order,
    l.name as layout_name
FROM " . DB_PREFIX . "layout_module lm
LEFT JOIN " . DB_PREFIX . "layout l ON lm.layout_id = l.layout_id
WHERE lm.code LIKE '%banner%' OR lm.code LIKE '%carousel%' OR lm.code LIKE '%slideshow%'
ORDER BY l.layout_id, lm.position, lm.sort_order";

$result2 = $link->query($sql2);

if ($result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        echo "布局ID: {$row['layout_id']} | 布局名称: {$row['layout_name']}\n";
        echo "  - 模块代码: {$row['code']}\n";
        echo "  - 位置: {$row['position']}\n";
        echo "  - 排序: {$row['sort_order']}\n";
        echo str_repeat("-", 60) . "\n";
    }
} else {
    echo "没有找到轮播图相关模块!\n";
}

echo "\n=== 检查首页布局的所有模块 ===\n\n";

// 首先找到首页布局ID
$sql3 = "SELECT layout_id, name FROM " . DB_PREFIX . "layout WHERE route = 'common/home'";
$result3 = $link->query($sql3);

if ($result3->num_rows > 0) {
    $home_layout = $result3->fetch_assoc();
    echo "首页布局ID: {$home_layout['layout_id']}, 名称: {$home_layout['name']}\n\n";
    
    // 查询首页的所有模块
    $sql4 = "SELECT 
        layout_module_id,
        code,
        position,
        sort_order
    FROM " . DB_PREFIX . "layout_module
    WHERE layout_id = {$home_layout['layout_id']}
    ORDER BY position, sort_order";
    
    $result4 = $link->query($sql4);
    
    if ($result4->num_rows > 0) {
        $positions = [];
        while ($row = $result4->fetch_assoc()) {
            if (!isset($positions[$row['position']])) {
                $positions[$row['position']] = [];
            }
            $positions[$row['position']][] = $row;
        }
        
        foreach ($positions as $position => $modules) {
            echo "位置: {$position}\n";
            foreach ($modules as $module) {
                echo "  - {$module['code']} (排序: {$module['sort_order']}, ID: {$module['layout_module_id']})\n";
            }
            echo "\n";
        }
    } else {
        echo "首页布局没有配置任何模块!\n";
    }
} else {
    echo "找不到首页布局!\n";
}

$link->close();
?>
