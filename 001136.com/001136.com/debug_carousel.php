<?php
// 快速检查轮播图配置
require_once('config.php');

try {
    $db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
    if ($db->connect_error) {
        die("连接失败: " . $db->connect_error);
    }
    $db->set_charset('utf8mb4');
    
    echo "<h2>轮播图诊断</h2>";
    
    // 1. 检查首页布局
    $query = "SELECT lr.*, l.name FROM " . DB_PREFIX . "layout_route lr 
              LEFT JOIN " . DB_PREFIX . "layout l ON lr.layout_id = l.layout_id 
              WHERE lr.route = 'common/home'";
    $result = $db->query($query);
    
    echo "<h3>1. 首页布局:</h3>";
    $layout_id = 0;
    while ($row = $result->fetch_assoc()) {
        echo "布局ID: {$row['layout_id']}, 名称: {$row['name']}, Store ID: {$row['store_id']}<br>";
        $layout_id = $row['layout_id'];
    }
    
    // 2. 检查content_top模块
    if ($layout_id) {
        echo "<h3>2. Content Top 模块:</h3>";
        $query = "SELECT * FROM " . DB_PREFIX . "layout_module 
                  WHERE layout_id = {$layout_id} AND position = 'content_top' 
                  ORDER BY sort_order";
        $result = $db->query($query);
        
        $has_modules = false;
        while ($row = $result->fetch_assoc()) {
            $has_modules = true;
            echo "代码: {$row['code']}, 排序: {$row['sort_order']}<br>";
            
            // 检查模块详情
            $parts = explode('.', $row['code']);
            if (isset($parts[2])) {
                $module_id = $parts[2];
                $mod_query = "SELECT * FROM " . DB_PREFIX . "module WHERE module_id = {$module_id}";
                $mod_result = $db->query($mod_query);
                if ($mod_data = $mod_result->fetch_assoc()) {
                    echo "&nbsp;&nbsp;→ 模块名: {$mod_data['name']}<br>";
                    $status = isset($mod_data['status']) ? $mod_data['status'] : 0;
                    echo "&nbsp;&nbsp;→ 状态: " . ($status ? '<span style="color:green">启用</span>' : '<span style="color:red">禁用</span>') . "<br>";
                    echo "&nbsp;&nbsp;→ 代码: {$parts[0]}/{$parts[1]}<br>";
                }
            }
        }
        
        if (!$has_modules) {
            echo "<span style='color:red'>❌ 没有找到content_top模块！这就是轮播图不显示的原因。</span><br>";
            echo "<br><strong>解决方案：</strong><br>";
            echo "1. 登录后台<br>";
            echo "2. 进入 Design → Layouts<br>";
            echo "3. 编辑 Home 布局<br>";
            echo "4. 在 Content Top 位置添加 Banner 或 Slideshow 模块<br>";
        }
    }
    
    // 3. 检查所有banner模块
    echo "<h3>3. 所有Banner/Slideshow模块:</h3>";
    $query = "SELECT * FROM " . DB_PREFIX . "module WHERE name LIKE '%banner%' OR name LIKE '%slide%' OR name LIKE '%轮播%'";
    $result = $db->query($query);
    
    $found_banner = false;
    while ($row = $result->fetch_assoc()) {
        $found_banner = true;
        echo "ID: {$row['module_id']}, 名称: {$row['name']}, ";
        $status = isset($row['status']) ? $row['status'] : 0;
        echo "状态: " . ($status ? '<span style="color:green">启用</span>' : '<span style="color:red">禁用</span>') . "<br>";
    }
    
    if (!$found_banner) {
        echo "<span style='color:orange'>⚠️ 没有找到banner模块</span><br>";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage();
}
?>

<hr>
<h3>快速修复步骤：</h3>
<ol>
<li>登录后台: <a href="/admin67676" target="_blank">http://001136.com/admin67676</a></li>
<li>进入: <strong>Design → Layouts</strong></li>
<li>找到并编辑 <strong>Home</strong> 布局</li>
<li>在 <strong>Content Top</strong> 位置，点击 + 添加模块</li>
<li>选择 Banner 或 Slideshow 模块</li>
<li>保存</li>
</ol>

