<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>调试 content_top 输出</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .section { background: white; padding: 20px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .info { background: #d1ecf1; padding: 15px; border-left: 4px solid #0c5460; margin: 10px 0; }
        .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #856404; margin: 10px 0; }
        .success { background: #d4edda; padding: 15px; border-left: 4px solid #155724; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>

<h1>🔍 调试首页 content_top 输出</h1>

<?php
// 模拟OpenCart环境
require_once('config.php');

$link = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$link->set_charset('utf8mb4');

// 获取当前语言
$current_language_code = isset($_GET['language']) ? $_GET['language'] : 'zh-cn';

echo "<div class='info'><strong>当前语言:</strong> $current_language_code</div>";

// 1. 获取首页布局ID - 修复: layout表中没有route字段
$sql = "SELECT * FROM " . DB_PREFIX . "layout_route WHERE route = 'common/home' LIMIT 1";
$result = $link->query($sql);

if ($result && $result->num_rows > 0) {
    $layout_route = $result->fetch_assoc();
    $layout_id = $layout_route['layout_id'];
    
    // 获取布局详情
    $sql2 = "SELECT * FROM " . DB_PREFIX . "layout WHERE layout_id = $layout_id";
    $result2 = $link->query($sql2);
    if ($result2 && $result2->num_rows > 0) {
        $layout = $result2->fetch_assoc();
    } else {
        $layout = ['layout_id' => $layout_id, 'name' => '首页'];
    }
} else {
    // 如果找不到,默认使用layout_id=1
    $layout_id = 1;
    $sql = "SELECT * FROM " . DB_PREFIX . "layout WHERE layout_id = 1";
    $result = $link->query($sql);
    if ($result && $result->num_rows > 0) {
        $layout = $result->fetch_assoc();
    } else {
        $layout = ['layout_id' => 1, 'name' => '首页'];
    }
}

echo "<div class='section'>";
echo "<h2>📋 首页布局信息</h2>";
echo "<p><strong>布局ID:</strong> $layout_id</p>";
echo "<p><strong>布局名称:</strong> {$layout['name']}</p>";
echo "<p><strong>路由:</strong> {$layout['route']}</p>";
echo "</div>";

// 2. 获取content_top位置的所有模块
$sql = "SELECT * FROM " . DB_PREFIX . "layout_module 
        WHERE layout_id = $layout_id AND position = 'content_top'
        ORDER BY sort_order";
$result = $link->query($sql);

echo "<div class='section'>";
echo "<h2>📦 content_top 位置的模块</h2>";

if ($result->num_rows > 0) {
    echo "<div class='success'>找到 " . $result->num_rows . " 个模块</div>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<div style='background: #f9f9f9; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "<p><strong>模块代码:</strong> <code>{$row['code']}</code></p>";
        echo "<p><strong>排序:</strong> {$row['sort_order']}</p>";
        echo "<p><strong>模块ID:</strong> {$row['layout_module_id']}</p>";
        
        // 解析模块代码
        $parts = explode('.', $row['code']);
        echo "<p><strong>解析结果:</strong></p>";
        echo "<ul>";
        echo "<li>扩展类型: " . (isset($parts[0]) ? $parts[0] : 'N/A') . "</li>";
        echo "<li>模块名称: " . (isset($parts[1]) ? $parts[1] : 'N/A') . "</li>";
        echo "<li>模块实例ID: " . (isset($parts[2]) ? $parts[2] : 'N/A') . "</li>";
        echo "</ul>";
        
        // 检查模块状态
        if (isset($parts[1])) {
            $module_code = $parts[1];
            $sql2 = "SELECT * FROM " . DB_PREFIX . "setting 
                     WHERE `key` = 'module_{$module_code}_status' AND store_id = 0";
            $result2 = $link->query($sql2);
            
            if ($result2->num_rows > 0) {
                $setting = $result2->fetch_assoc();
                $status = $setting['value'] ? '✅ 启用' : '❌ 禁用';
                echo "<p><strong>模块状态:</strong> $status</p>";
            }
        }
        
        // 检查模块实例配置
        if (isset($parts[2])) {
            $module_id = $parts[2];
            $sql3 = "SELECT * FROM " . DB_PREFIX . "module WHERE module_id = $module_id";
            $result3 = $link->query($sql3);
            
            if ($result3->num_rows > 0) {
                $module_info = $result3->fetch_assoc();
                echo "<p><strong>模块实例名称:</strong> {$module_info['name']}</p>";
                
                $settings = json_decode($module_info['setting'], true);
                if ($settings) {
                    echo "<p><strong>实例状态:</strong> " . (isset($settings['status']) && $settings['status'] ? '✅ 启用' : '❌ 禁用') . "</p>";
                    
                    // 显示完整配置
                    echo "<details>";
                    echo "<summary>查看完整配置</summary>";
                    echo "<pre>" . print_r($settings, true) . "</pre>";
                    echo "</details>";
                }
            } else {
                echo "<div class='warning'>⚠️ 找不到模块实例配置 (module_id: $module_id)</div>";
            }
        }
        
        echo "</div>";
    }
} else {
    echo "<div class='warning'>⚠️ content_top 位置没有配置任何模块!</div>";
    echo "<p><strong>解决方案:</strong></p>";
    echo "<ol>";
    echo "<li>登录后台</li>";
    echo "<li>进入【设计 → 布局】</li>";
    echo "<li>编辑【首页】布局</li>";
    echo "<li>在 content_top 位置添加轮播图模块</li>";
    echo "</ol>";
}

echo "</div>";

// 3. 检查所有可用的banner模块
echo "<div class='section'>";
echo "<h2>🎠 所有可用的轮播图模块</h2>";

$sql = "SELECT * FROM " . DB_PREFIX . "module WHERE code LIKE '%banner%'";
$result = $link->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $settings = json_decode($row['setting'], true);
        $status = isset($settings['status']) && $settings['status'] ? '✅ 启用' : '❌ 禁用';
        
        echo "<div style='background: #f9f9f9; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff;'>";
        echo "<p><strong>模块ID:</strong> {$row['module_id']}</p>";
        echo "<p><strong>名称:</strong> {$row['name']}</p>";
        echo "<p><strong>代码:</strong> <code>{$row['code']}</code></p>";
        echo "<p><strong>状态:</strong> $status</p>";
        
        if ($settings && isset($settings['banner_id'])) {
            echo "<p><strong>Banner ID:</strong> {$settings['banner_id']}</p>";
        }
        
        echo "</div>";
    }
} else {
    echo "<div class='warning'>⚠️ 没有找到任何轮播图模块实例</div>";
}

echo "</div>";

// 4. 测试语言切换
echo "<div class='section'>";
echo "<h2>🌍 语言切换测试</h2>";
echo "<p>切换语言查看content_top配置:</p>";
echo "<p>";
echo "<a href='?language=zh-cn' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>简体中文</a>";
echo "<a href='?language=en-gb' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;'>English</a>";
echo "</p>";
echo "</div>";

$link->close();
?>

<div class="info">
    <strong>💡 诊断结论:</strong><br>
    如果切换中文后轮播图消失,可能原因:<br>
    1. ❌ content_top 位置没有配置轮播图模块<br>
    2. ❌ 轮播图模块实例被禁用<br>
    3. ❌ 轮播图的banner内容只为英文配置,中文版没有图片<br>
    4. ❌ CSS或JavaScript问题导致渲染失败
</div>

</body>
</html>
