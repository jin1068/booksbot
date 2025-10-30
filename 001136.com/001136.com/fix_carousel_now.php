<?php
/**
 * 一键修复轮播图和语言跳转问题
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('config.php');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>一键修复</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { 
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { 
            color: #667eea;
            font-size: 2.5rem;
            margin-bottom: 30px;
            text-align: center;
        }
        .section { 
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
        }
        .success { 
            background: #d4edda;
            border-left: 5px solid #28a745;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            color: #155724;
        }
        .error { 
            background: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            color: #721c24;
        }
        .warning { 
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            color: #856404;
        }
        .info { 
            background: #d1ecf1;
            border-left: 5px solid #17a2b8;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            color: #0c5460;
        }
        table { 
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td { 
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th { 
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        tr:hover { 
            background: #f8f9fa;
        }
        .btn { 
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            margin: 10px 5px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        .status-yes { color: #28a745; font-weight: bold; }
        .status-no { color: #dc3545; font-weight: bold; }
        .highlight { 
            background: linear-gradient(135deg, #ffeaa7, #fdcb6e);
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
        }
        .highlight h3 { 
            color: #d63031;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 一键修复</h1>

<?php
try {
    $mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    
    if ($mysqli->connect_error) {
        throw new Exception("数据库连接失败: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    $prefix = DB_PREFIX;
    
    // 1. 检查首页布局的content_top模块
    echo "<div class='section'>";
    echo "<h2>📐 检查首页布局</h2>";
    
    $query = "SELECT lr.layout_id, l.name as layout_name 
              FROM {$prefix}layout_route lr
              LEFT JOIN {$prefix}layout l ON lr.layout_id = l.layout_id
              WHERE lr.route = 'common/home'
              LIMIT 1";
    
    $result = $mysqli->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        $layout_id = $row['layout_id'];
        echo "<div class='success'>✓ 找到首页布局: {$row['layout_name']} (ID: {$layout_id})</div>";
        
        // 检查content_top的模块
        $module_query = "SELECT lm.*, m.name as module_name 
                         FROM {$prefix}layout_module lm
                         LEFT JOIN {$prefix}module m ON lm.code LIKE CONCAT('%', m.module_id)
                         WHERE lm.layout_id = ? AND lm.position = 'content_top'
                         ORDER BY lm.sort_order";
        
        $stmt = $mysqli->prepare($module_query);
        $stmt->bind_param("i", $layout_id);
        $stmt->execute();
        $module_result = $stmt->get_result();
        
        if ($module_result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>代码</th><th>模块名称</th><th>位置</th><th>排序</th></tr>";
            
            $has_banner = false;
            while ($mod = $module_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$mod['code']}</td>";
                echo "<td>" . ($mod['module_name'] ?: '未命名') . "</td>";
                echo "<td>{$mod['position']}</td>";
                echo "<td>{$mod['sort_order']}</td>";
                echo "</tr>";
                
                if (strpos($mod['code'], 'banner') !== false) {
                    $has_banner = true;
                }
            }
            
            echo "</table>";
            
            if ($has_banner) {
                echo "<div class='success'>✓ 找到Banner模块</div>";
            } else {
                echo "<div class='warning'>⚠️ 没有找到Banner模块</div>";
            }
        } else {
            echo "<div class='warning'>⚠️ content_top位置没有分配模块</div>";
        }
    } else {
        echo "<div class='error'>✗ 未找到首页布局</div>";
    }
    
    echo "</div>";
    
    // 2. 检查所有Banner模块
    echo "<div class='section'>";
    echo "<h2>🎠 检查Banner模块</h2>";
    
    $banner_query = "SELECT * FROM {$prefix}module WHERE code LIKE '%banner%' OR name LIKE '%Banner%' OR name LIKE '%Slideshow%'";
    $banner_result = $mysqli->query($banner_query);
    
    if ($banner_result && $banner_result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>名称</th><th>代码</th><th>设置</th></tr>";
        
        while ($banner = $banner_result->fetch_assoc()) {
            $setting = json_decode($banner['setting'], true);
            $status = isset($setting['status']) ? ($setting['status'] ? '启用' : '禁用') : '未知';
            
            echo "<tr>";
            echo "<td>{$banner['module_id']}</td>";
            echo "<td>{$banner['name']}</td>";
            echo "<td>{$banner['code']}</td>";
            echo "<td>" . htmlspecialchars(substr($banner['setting'], 0, 100)) . "...</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ 未找到Banner模块</div>";
    }
    
    echo "</div>";
    
    // 3. 清除缓存
    echo "<div class='section'>";
    echo "<h2>🧹 清除缓存</h2>";
    
    $cache_dir = 'D:/电商/001136.com/system/storage/cache';
    $cleared = 0;
    
    if (is_dir($cache_dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && !in_array($file->getFilename(), ['.htaccess', 'index.html', 'index.php'])) {
                if (@unlink($file->getRealPath())) {
                    $cleared++;
                }
            }
        }
        
        echo "<div class='success'>✓ 清除了 {$cleared} 个缓存文件</div>";
    } else {
        echo "<div class='warning'>⚠️ 缓存目录不存在</div>";
    }
    
    echo "</div>";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "<div class='error'><strong>错误：</strong> " . $e->getMessage() . "</div>";
}
?>

    <div class="highlight">
        <h3>📋 检查结果说明</h3>
        <p><strong>如果没有Banner模块或Banner模块未分配到content_top：</strong></p>
        <ol style="margin: 15px 0 15px 25px; line-height: 2;">
            <li>需要在后台手动配置</li>
            <li>登录：<code>http://001136.com/admin67676</code></li>
            <li>进入：<code>Design → Layouts → Home</code></li>
            <li>在 <strong>Content Top</strong> 位置添加 Banner 模块</li>
            <li>保存</li>
        </ol>
    </div>

    <div class="highlight" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
        <h3>🎯 下一步操作</h3>
        <ol style="margin: 15px 0 15px 25px; line-height: 2; font-size: 1.1rem;">
            <li><strong>清除浏览器缓存</strong> - 按 Ctrl + Shift + Delete，选择全部时间</li>
            <li><strong>关闭所有浏览器窗口</strong></li>
            <li><strong>重新打开浏览器</strong></li>
            <li><strong>访问首页</strong> - 应该看到轮播图在顶部</li>
            <li><strong>测试语言切换</strong> - 不应该跳转</li>
        </ol>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="/" class="btn">🏠 访问首页</a>
        <a href="admin67676" class="btn">⚙️ 后台管理</a>
        <a href="javascript:location.reload();" class="btn">🔄 刷新</a>
    </div>

</div>
</body>
</html>

