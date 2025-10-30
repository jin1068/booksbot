<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>轮播图诊断工具</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Microsoft YaHei', Arial, sans-serif; background: #f5f7fa; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #2c3e50; margin-bottom: 20px; font-size: 28px; }
        h2 { color: #34495e; margin: 30px 0 15px; font-size: 20px; border-left: 4px solid #3498db; padding-left: 10px; }
        .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; border-left: 4px solid #155724; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .warning { background: #fff3cd; border-left: 4px solid #856404; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .error { background: #f8d7da; border-left: 4px solid #721c24; padding: 15px; margin: 10px 0; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 4px; font-family: 'Courier New', monospace; overflow-x: auto; margin: 10px 0; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-left: 8px; }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-info { background: #17a2b8; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 轮播图模块诊断工具</h1>
    
    <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    require_once('config.php');
    
    $link = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    
    if ($link->connect_error) {
        echo "<div class='error'>❌ 数据库连接失败: " . htmlspecialchars($link->connect_error) . "</div>";
        exit;
    }
    
    $link->set_charset('utf8mb4');
    
    echo "<div class='success'>✅ 数据库连接成功</div>";
    
    // 获取当前语言
    $current_language = isset($_GET['language']) ? $_GET['language'] : 'zh-cn';
    echo "<div class='info'><strong>当前测试语言:</strong> $current_language</div>";
    ?>
    
    <div class="card">
        <h2>1️⃣ 首页布局配置</h2>
        <?php
        // 查找首页布局 - 通过layout_route表
        $sql = "SELECT lr.layout_id, l.name 
                FROM " . DB_PREFIX . "layout_route lr
                LEFT JOIN " . DB_PREFIX . "layout l ON lr.layout_id = l.layout_id
                WHERE lr.route = 'common/home' AND lr.store_id = 0
                LIMIT 1";
        $result = $link->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $layout = $result->fetch_assoc();
            $layout_id = $layout['layout_id'];
            $layout_name = $layout['name'];
            echo "<div class='success'>✅ 找到首页布局: <strong>{$layout_name}</strong> (ID: {$layout_id})</div>";
        } else {
            // 备用方案:使用layout_id=1
            $layout_id = 1;
            echo "<div class='warning'>⚠️ 未找到首页路由配置,使用默认布局ID: 1</div>";
            
            $sql = "SELECT * FROM " . DB_PREFIX . "layout WHERE layout_id = 1";
            $result = $link->query($sql);
            if ($result && $result->num_rows > 0) {
                $layout = $result->fetch_assoc();
                $layout_name = $layout['name'];
                echo "<div class='info'>使用布局: <strong>{$layout_name}</strong></div>";
            } else {
                echo "<div class='error'>❌ 找不到布局ID=1</div>";
                $layout_name = '未知';
            }
        }
        ?>
    </div>
    
    <div class="card">
        <h2>2️⃣ content_top 位置的模块</h2>
        <?php
        $sql = "SELECT * FROM " . DB_PREFIX . "layout_module 
                WHERE layout_id = $layout_id AND position = 'content_top'
                ORDER BY sort_order";
        $result = $link->query($sql);
        
        if ($result && $result->num_rows > 0) {
            echo "<div class='success'>✅ 找到 " . $result->num_rows . " 个模块</div>";
            echo "<table>";
            echo "<tr><th>模块代码</th><th>位置</th><th>排序</th><th>状态</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                $parts = explode('.', $row['code']);
                $status = '';
                
                // 检查模块状态
                if (isset($parts[2])) {
                    // 有模块实例ID
                    $module_id = $parts[2];
                    $sql2 = "SELECT * FROM " . DB_PREFIX . "module WHERE module_id = $module_id";
                    $result2 = $link->query($sql2);
                    if ($result2 && $result2->num_rows > 0) {
                        $module_info = $result2->fetch_assoc();
                        $settings = json_decode($module_info['setting'], true);
                        if (isset($settings['status']) && $settings['status']) {
                            $status = "<span class='badge badge-success'>启用</span>";
                        } else {
                            $status = "<span class='badge badge-danger'>禁用</span>";
                        }
                    } else {
                        $status = "<span class='badge badge-danger'>配置缺失</span>";
                    }
                } elseif (isset($parts[1])) {
                    // 检查扩展模块状态
                    $module_code = $parts[1];
                    $sql2 = "SELECT * FROM " . DB_PREFIX . "setting 
                             WHERE `key` = 'module_{$module_code}_status' AND store_id = 0";
                    $result2 = $link->query($sql2);
                    if ($result2 && $result2->num_rows > 0) {
                        $setting = $result2->fetch_assoc();
                        if ($setting['value']) {
                            $status = "<span class='badge badge-success'>启用</span>";
                        } else {
                            $status = "<span class='badge badge-danger'>禁用</span>";
                        }
                    } else {
                        $status = "<span class='badge badge-info'>未知</span>";
                    }
                }
                
                echo "<tr>";
                echo "<td><strong>{$row['code']}</strong></td>";
                echo "<td>{$row['position']}</td>";
                echo "<td>{$row['sort_order']}</td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='error'>❌ content_top 位置没有配置任何模块!</div>";
            echo "<div class='warning'>";
            echo "<p><strong>解决方案:</strong></p>";
            echo "<ol style='margin-left: 20px; margin-top: 10px;'>";
            echo "<li>登录后台管理</li>";
            echo "<li>进入【扩展 → 扩展 → 模块】</li>";
            echo "<li>找到Banner/轮播图模块,点击编辑</li>";
            echo "<li>进入【设计 → 布局】</li>";
            echo "<li>编辑【首页】布局</li>";
            echo "<li>在 <strong>content_top</strong> 位置添加轮播图模块</li>";
            echo "</ol>";
            echo "</div>";
        }
        ?>
    </div>
    
    <div class="card">
        <h2>3️⃣ 所有Banner模块实例</h2>
        <?php
        $sql = "SELECT * FROM " . DB_PREFIX . "module WHERE code LIKE '%banner%'";
        $result = $link->query($sql);
        
        if ($result && $result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>模块ID</th><th>名称</th><th>代码</th><th>状态</th><th>Banner ID</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                $settings = json_decode($row['setting'], true);
                $status = (isset($settings['status']) && $settings['status']) ? 
                    "<span class='badge badge-success'>启用</span>" : 
                    "<span class='badge badge-danger'>禁用</span>";
                $banner_id = isset($settings['banner_id']) ? $settings['banner_id'] : 'N/A';
                
                echo "<tr>";
                echo "<td>{$row['module_id']}</td>";
                echo "<td>{$row['name']}</td>";
                echo "<td>{$row['code']}</td>";
                echo "<td>$status</td>";
                echo "<td>$banner_id</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠️ 没有找到Banner模块实例</div>";
        }
        ?>
    </div>
    
    <div class="card">
        <h2>4️⃣ Banner内容配置</h2>
        <?php
        $sql = "SELECT * FROM " . DB_PREFIX . "banner ORDER BY banner_id";
        $result = $link->query($sql);
        
        if ($result && $result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>Banner ID</th><th>名称</th><th>状态</th><th>图片数量</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                $status = $row['status'] ? 
                    "<span class='badge badge-success'>启用</span>" : 
                    "<span class='badge badge-danger'>禁用</span>";
                
                // 查询图片数量
                $sql2 = "SELECT COUNT(*) as count FROM " . DB_PREFIX . "banner_image WHERE banner_id = {$row['banner_id']}";
                $result2 = $link->query($sql2);
                $count_row = $result2->fetch_assoc();
                $image_count = $count_row['count'];
                
                echo "<tr>";
                echo "<td>{$row['banner_id']}</td>";
                echo "<td>{$row['name']}</td>";
                echo "<td>$status</td>";
                echo "<td>$image_count 张</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠️ 没有找到任何Banner配置</div>";
        }
        ?>
    </div>
    
    <div class="card">
        <h2>5️⃣ 诊断总结</h2>
        <?php
        $link->close();
        ?>
        <div class="info">
            <p><strong>💡 轮播图不显示的常见原因:</strong></p>
            <ul style="margin-left: 20px; margin-top: 10px; line-height: 1.8;">
                <li>❌ content_top 位置没有添加轮播图模块</li>
                <li>❌ 轮播图模块实例被禁用</li>
                <li>❌ Banner内容被禁用或没有图片</li>
                <li>❌ Banner图片只配置了英文,没有配置中文</li>
                <li>❌ CSS样式冲突导致不显示</li>
            </ul>
        </div>
    </div>
    
    <div style="text-align: center; margin: 30px 0; padding: 20px; background: white; border-radius: 8px;">
        <p><strong>🌍 切换语言测试:</strong></p>
        <p style="margin-top: 15px;">
            <a href="?language=zh-cn" style="display: inline-block; padding: 10px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 0 5px;">简体中文</a>
            <a href="?language=en-gb" style="display: inline-block; padding: 10px 24px; background: #2ecc71; color: white; text-decoration: none; border-radius: 5px; margin: 0 5px;">English</a>
        </p>
    </div>
</div>

</body>
</html>
