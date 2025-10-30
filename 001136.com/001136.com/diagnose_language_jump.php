<?php
/**
 * 全面诊断语言切换跳转问题
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>语言切换跳转诊断</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 1200px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #c7511f; border-bottom: 3px solid #007185; padding-bottom: 10px; }
        h2 { color: #007185; margin-top: 30px; border-left: 5px solid #007185; padding-left: 15px; }
        .issue { background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 15px 0; }
        .ok { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin: 15px 0; }
        .warning { background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; margin: 15px 0; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; overflow-x: auto; }
        .icon-error { color: #f44336; }
        .icon-ok { color: #4caf50; }
        .icon-warn { color: #ff9800; }
        .section { background: #fafafa; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #e0e0e0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007185; color: white; }
        .fix-btn { background: #007185; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .fix-btn:hover { background: #005a6b; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 语言切换跳转问题 - 全面诊断</h1>
    
    <?php
    $issues = [];
    $checks = [];
    
    // 检查1: menu.twig 中的 Lucky Purchase 链接
    echo "<div class='section'>";
    echo "<h2>🎯 问题1: Lucky Purchase 链接包含 Hash</h2>";
    $menu_file = __DIR__ . '/catalog/view/template/common/menu.twig';
    if (file_exists($menu_file)) {
        $menu_content = file_get_contents($menu_file);
        if (strpos($menu_content, 'href="#lucky-purchase"') !== false) {
            echo "<div class='issue'>";
            echo "<strong>❌ 发现问题！</strong><br>";
            echo "menu.twig 第19行使用了 <code>href=\"#lucky-purchase\"</code><br>";
            echo "这会在URL中添加hash，导致页面跳转！";
            echo "</div>";
            
            // 显示问题代码
            preg_match('/.*href="#lucky-purchase".*/', $menu_content, $matches);
            if ($matches) {
                echo "<div class='code'>";
                echo "问题代码：<br>";
                echo htmlspecialchars($matches[0]);
                echo "</div>";
            }
            
            echo "<div class='warning'>";
            echo "<strong>📝 修复方案：</strong><br>";
            echo "1. 首页：使用 JavaScript 平滑滚动，不要使用 hash<br>";
            echo "2. 其他页面：跳转到首页后再滚动到 Lucky Purchase";
            echo "</div>";
            
            $issues[] = 'Lucky Purchase链接使用hash';
        } else {
            echo "<div class='ok'>✓ menu.twig 中没有发现 #lucky-purchase hash</div>";
        }
        
        // 检查是否有 data-lucky-scroll 属性
        if (strpos($menu_content, 'data-lucky-scroll') !== false) {
            echo "<div class='warning'>";
            echo "<strong>⚠ 发现 data-lucky-scroll 属性</strong><br>";
            echo "这个属性可能会触发自动滚动";
            echo "</div>";
        }
    }
    echo "</div>";
    
    // 检查2: language.php 后端代码
    echo "<div class='section'>";
    echo "<h2>🔧 检查2: 后端 language.php</h2>";
    $lang_controller = __DIR__ . '/catalog/controller/common/language.php';
    if (file_exists($lang_controller)) {
        $lang_content = file_get_contents($lang_controller);
        
        // 检查是否保留了 fragment
        if (strpos($lang_content, "url_info['fragment']") !== false) {
            echo "<div class='issue'>";
            echo "<strong>❌ 后端仍在保留 hash fragment！</strong><br>";
            echo "language.php 中存在保留 fragment 的代码";
            echo "</div>";
            $issues[] = '后端保留fragment';
        } else {
            echo "<div class='ok'>✓ 后端已正确移除 fragment 保存逻辑</div>";
        }
        
        // 检查注释
        if (strpos($lang_content, '不保留hash片段') !== false || 
            strpos($lang_content, '由于锚点会在切换语言后导致页面跳转定位') !== false) {
            echo "<div class='ok'>✓ 发现防止hash跳转的注释说明</div>";
        }
    }
    echo "</div>";
    
    // 检查3: language.twig 前端代码
    echo "<div class='section'>";
    echo "<h2>📱 检查3: 前端 language.twig</h2>";
    $lang_twig = __DIR__ . '/catalog/view/template/common/language.twig';
    if (file_exists($lang_twig)) {
        $twig_content = file_get_contents($lang_twig);
        
        // 检查是否有 removeHashFromUrl 函数
        if (strpos($twig_content, 'removeHashFromUrl') !== false) {
            echo "<div class='ok'>✓ 发现 removeHashFromUrl 辅助函数</div>";
        } else {
            echo "<div class='issue'>❌ 缺少 removeHashFromUrl 函数</div>";
            $issues[] = '缺少removeHashFromUrl函数';
        }
        
        // 检查是否使用 window.location.replace
        if (strpos($twig_content, 'window.location.replace') !== false) {
            echo "<div class='ok'>✓ 使用 window.location.replace (正确)</div>";
        } else {
            echo "<div class='warning'>⚠ 未使用 window.location.replace</div>";
        }
        
        // 检查是否有 stopPropagation
        if (strpos($twig_content, 'stopPropagation') !== false) {
            echo "<div class='ok'>✓ 使用 event.stopPropagation</div>";
        } else {
            echo "<div class='warning'>⚠ 未使用 event.stopPropagation</div>";
        }
    }
    echo "</div>";
    
    // 检查4: header.twig 防护代码
    echo "<div class='section'>";
    echo "<h2>🛡️ 检查4: header.twig 防护措施</h2>";
    $header_twig = __DIR__ . '/catalog/view/template/common/header.twig';
    if (file_exists($header_twig)) {
        $header_content = file_get_contents($header_twig);
        
        // 检查是否有早期滚动防护
        if (strpos($header_content, 'preventScroll') !== false) {
            echo "<div class='ok'>✓ 发现 preventScroll 函数</div>";
        } else {
            echo "<div class='issue'>❌ 缺少早期滚动防护</div>";
            $issues[] = '缺少早期滚动防护';
        }
        
        // 检查是否在 <head> 中有防护
        if (preg_match('/<head>.*?<script>/s', $header_content)) {
            echo "<div class='ok'>✓ 在 &lt;head&gt; 中有早期防护脚本</div>";
        } else {
            echo "<div class='warning'>⚠ &lt;head&gt; 中可能缺少早期防护</div>";
        }
        
        // 检查是否在 <body> 开始处有防护
        if (strpos($header_content, '<body') !== false && 
            strpos($header_content, 'window.location.hash') !== false) {
            echo "<div class='ok'>✓ 在 &lt;body&gt; 开始处有防护脚本</div>";
        }
    }
    echo "</div>";
    
    // 检查5: stylesheet.css
    echo "<div class='section'>";
    echo "<h2>🎨 检查5: CSS scroll-behavior</h2>";
    $css_file = __DIR__ . '/catalog/view/stylesheet/stylesheet.css';
    if (file_exists($css_file)) {
        $css_content = file_get_contents($css_file);
        
        // 检查是否禁用了 smooth scroll
        if (strpos($css_content, 'scroll-behavior: auto !important') !== false) {
            echo "<div class='ok'>✓ CSS 已禁用 smooth scroll</div>";
        } else {
            echo "<div class='issue'>❌ CSS 未禁用 smooth scroll，Bootstrap 会自动滚动到hash</div>";
            $issues[] = 'CSS未禁用smooth scroll';
        }
    }
    echo "</div>";
    
    // 总结
    echo "<div class='section'>";
    echo "<h2>📊 诊断总结</h2>";
    
    if (empty($issues)) {
        echo "<div class='ok'>";
        echo "<strong>✓ 所有检查通过！</strong><br>";
        echo "代码看起来没有问题。如果仍然跳转，可能是：<br>";
        echo "1. 浏览器缓存未清除<br>";
        echo "2. 服务器缓存未清除<br>";
        echo "3. CDN缓存（如果有）";
        echo "</div>";
    } else {
        echo "<div class='issue'>";
        echo "<strong>❌ 发现 " . count($issues) . " 个问题：</strong><br>";
        echo "<ol>";
        foreach ($issues as $issue) {
            echo "<li>{$issue}</li>";
        }
        echo "</ol>";
        echo "</div>";
    }
    echo "</div>";
    
    // 修复建议
    echo "<div class='section'>";
    echo "<h2>🔧 修复建议</h2>";
    echo "<table>";
    echo "<tr><th>问题</th><th>文件</th><th>修复方法</th></tr>";
    echo "<tr>";
    echo "<td>Lucky Purchase使用hash</td>";
    echo "<td>menu.twig 第19行</td>";
    echo "<td>将 <code>href=\"#lucky-purchase\"</code> 改为 JavaScript 点击事件</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>CSS自动滚动</td>";
    echo "<td>stylesheet.css</td>";
    echo "<td>添加 <code>scroll-behavior: auto !important</code></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>浏览器缓存</td>";
    echo "<td>-</td>";
    echo "<td>Ctrl + Shift + Delete 清除所有缓存</td>";
    echo "</tr>";
    echo "</table>";
    echo "</div>";
    
    // 下一步
    echo "<div class='section'>";
    echo "<h2>🎯 下一步操作</h2>";
    echo "<div class='warning'>";
    echo "<strong>立即执行以下修复：</strong><br><br>";
    echo "1. <strong>修改 menu.twig</strong> - 移除 Lucky Purchase 的 hash 链接<br>";
    echo "2. <strong>清除所有缓存</strong> - 浏览器 + 服务器<br>";
    echo "3. <strong>测试语言切换</strong> - 检查是否还跳转";
    echo "</div>";
    echo "</div>";
    
    // 快速链接
    echo "<div class='section'>";
    echo "<h2>🔗 快速操作</h2>";
    echo "<a href='clear_all_cache.php' class='fix-btn'>清除服务器缓存</a>";
    echo "<a href='/' class='fix-btn'>访问首页测试</a>";
    echo "<a href='debug_carousel.php' class='fix-btn'>诊断轮播图</a>";
    echo "<button class='fix-btn' onclick='location.reload()'>刷新诊断</button>";
    echo "</div>";
    
    // 实时测试
    echo "<div class='section'>";
    echo "<h2>🧪 实时测试</h2>";
    echo "<p>在浏览器控制台执行以下代码测试：</p>";
    echo "<div class='code'>";
    echo "// 测试当前URL是否有hash<br>";
    echo "console.log('当前URL:', window.location.href);<br>";
    echo "console.log('Hash:', window.location.hash);<br>";
    echo "<br>";
    echo "// 测试移除hash<br>";
    echo "if (window.location.hash) {<br>";
    echo "&nbsp;&nbsp;history.replaceState(null, null, window.location.pathname + window.location.search);<br>";
    echo "&nbsp;&nbsp;console.log('Hash已移除:', window.location.href);<br>";
    echo "}";
    echo "</div>";
    echo "</div>";
    ?>
</div>

<script>
// 在页面加载时检查当前URL
window.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash) {
        console.warn('警告：当前URL包含hash:', window.location.hash);
    }
});
</script>
</body>
</html>

