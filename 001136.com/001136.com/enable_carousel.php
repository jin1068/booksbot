<?php
/**
 * 自动启用轮播图模块
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 加载配置
require_once('config.php');

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='utf-8'><title>启用轮播图模块</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.container { background: white; padding: 30px; border-radius: 15px; max-width: 800px; margin: 0 auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 15px; margin-bottom: 30px; }
.success { color: green; padding: 15px; background: #e8f5e9; border-left: 5px solid green; margin: 15px 0; border-radius: 5px; }
.error { color: red; padding: 15px; background: #ffebee; border-left: 5px solid red; margin: 15px 0; border-radius: 5px; }
.info { color: #0066cc; padding: 15px; background: #e3f2fd; border-left: 5px solid #0066cc; margin: 15px 0; border-radius: 5px; }
.warning { color: #ff9800; padding: 15px; background: #fff3e0; border-left: 5px solid #ff9800; margin: 15px 0; border-radius: 5px; }
.section { margin: 30px 0; padding: 20px; background: #f9f9f9; border-radius: 10px; }
.btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: bold; transition: all 0.3s; }
.btn:hover { background: #5568d3; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #667eea; color: white; }
.status-enabled { color: green; font-weight: bold; }
.status-disabled { color: red; font-weight: bold; }
</style>";
echo "</head><body>";
echo "<div class='container'>";
echo "<h1>🎠 启用轮播图模块</h1>";

try {
    // 连接数据库
    $mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    
    if ($mysqli->connect_error) {
        throw new Exception("数据库连接失败: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    
    echo "<div class='section'>";
    echo "<h2>📊 当前模块状态</h2>";
    
    // 查询所有Banner/Carousel模块
    $query = "SELECT module_id, name, code, status FROM " . DB_PREFIX . "module WHERE code = 'opencart.banner' OR name LIKE '%Banner%' OR name LIKE '%Slideshow%' OR name LIKE '%轮播%'";
    $result = $mysqli->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>模块ID</th><th>名称</th><th>代码</th><th>状态</th></tr>";
        
        $modules_to_enable = [];
        
        while ($row = $result->fetch_assoc()) {
            $status_text = $row['status'] ? '<span class="status-enabled">✓ 启用</span>' : '<span class="status-disabled">✗ 禁用</span>';
            echo "<tr>";
            echo "<td>{$row['module_id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['code']}</td>";
            echo "<td>{$status_text}</td>";
            echo "</tr>";
            
            if (!$row['status']) {
                $modules_to_enable[] = $row['module_id'];
            }
        }
        
        echo "</table>";
        
        // 启用所有禁用的模块
        if (!empty($modules_to_enable)) {
            echo "<div class='warning'>";
            echo "<strong>⚠️ 发现 " . count($modules_to_enable) . " 个禁用的模块，正在启用...</strong>";
            echo "</div>";
            
            foreach ($modules_to_enable as $module_id) {
                $update_query = "UPDATE " . DB_PREFIX . "module SET status = 1 WHERE module_id = ?";
                $stmt = $mysqli->prepare($update_query);
                $stmt->bind_param("i", $module_id);
                
                if ($stmt->execute()) {
                    echo "<div class='success'>✓ 模块 ID {$module_id} 已启用</div>";
                } else {
                    echo "<div class='error'>✗ 模块 ID {$module_id} 启用失败: " . $stmt->error . "</div>";
                }
                
                $stmt->close();
            }
        } else {
            echo "<div class='success'>✓ 所有轮播图模块已启用</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ 未找到轮播图模块</div>";
    }
    
    echo "</div>";
    
    // 检查布局分配
    echo "<div class='section'>";
    echo "<h2>📐 检查布局分配</h2>";
    
    $layout_query = "SELECT lr.*, m.name as module_name, m.status as module_status, l.name as layout_name 
                     FROM " . DB_PREFIX . "layout_route lr
                     LEFT JOIN " . DB_PREFIX . "layout_module lm ON lr.layout_id = lm.layout_id
                     LEFT JOIN " . DB_PREFIX . "module m ON lm.code = CONCAT('opencart.banner.', m.module_id) OR lm.code = CONCAT('opencart.duobao.', m.module_id)
                     LEFT JOIN " . DB_PREFIX . "layout l ON lr.layout_id = l.layout_id
                     WHERE lr.route = 'common/home'
                     ORDER BY lm.sort_order";
    
    $layout_result = $mysqli->query($layout_query);
    
    if ($layout_result && $layout_result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>布局</th><th>模块</th><th>位置</th><th>状态</th></tr>";
        
        while ($row = $layout_result->fetch_assoc()) {
            $status_text = $row['module_status'] ? '<span class="status-enabled">启用</span>' : '<span class="status-disabled">禁用</span>';
            echo "<tr>";
            echo "<td>{$row['layout_name']}</td>";
            echo "<td>{$row['module_name']}</td>";
            echo "<td>Content Top</td>";
            echo "<td>{$status_text}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ 首页布局信息查询为空</div>";
    }
    
    echo "</div>";
    
    // 清除缓存
    echo "<div class='section'>";
    echo "<h2>🧹 清除缓存</h2>";
    
    $cache_dirs = [
        'D:/电商/001136.com/system/storage/cache'
    ];
    
    $cleared_count = 0;
    foreach ($cache_dirs as $dir) {
        if (is_dir($dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($files as $file) {
                if ($file->isFile() && $file->getFilename() !== '.htaccess' && $file->getFilename() !== 'index.html') {
                    if (@unlink($file->getRealPath())) {
                        $cleared_count++;
                    }
                }
            }
        }
    }
    
    echo "<div class='success'>✓ 清除了 {$cleared_count} 个缓存文件</div>";
    echo "</div>";
    
    $mysqli->close();
    
    // 最终说明
    echo "<div class='section' style='background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); padding: 25px; border-radius: 15px;'>";
    echo "<h2 style='color: #d63031;'>🎯 下一步操作</h2>";
    echo "<ol style='font-size: 1.1rem; line-height: 2;'>";
    echo "<li><strong>清除浏览器缓存</strong> - 按 Ctrl + Shift + Delete</li>";
    echo "<li><strong>关闭所有浏览器窗口</strong></li>";
    echo "<li><strong>重新打开浏览器</strong></li>";
    echo "<li><strong>访问首页</strong> - 应该看到轮播图</li>";
    echo "<li><strong>测试语言切换</strong> - 不应该跳转</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>错误：</strong> " . $e->getMessage() . "</div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='/' class='btn'>🏠 访问首页</a>";
echo "<a href='diagnose_language_jump.php' class='btn'>🔍 运行诊断</a>";
echo "<a href='javascript:location.reload();' class='btn'>🔄 刷新本页</a>";
echo "</div>";

echo "</div></body></html>";
?>

