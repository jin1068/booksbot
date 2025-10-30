<?php
/**
 * 一键清除所有缓存
 * 用于解决语言切换和轮播图问题
 */

// 显示所有错误（调试用）
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='utf-8'><title>清除缓存</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 800px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #007185; border-bottom: 2px solid #007185; padding-bottom: 10px; }
.success { color: green; padding: 10px; background: #e8f5e9; border-left: 4px solid green; margin: 10px 0; }
.error { color: red; padding: 10px; background: #ffebee; border-left: 4px solid red; margin: 10px 0; }
.info { color: #0066cc; padding: 10px; background: #e3f2fd; border-left: 4px solid #0066cc; margin: 10px 0; }
.section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
.btn { display: inline-block; padding: 12px 24px; background: #007185; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
.btn:hover { background: #005a6b; }
.step { margin: 10px 0; padding: 10px; border-left: 3px solid #ccc; }
</style>";
echo "</head><body>";
echo "<div class='container'>";
echo "<h1>🧹 清除所有缓存</h1>";

$cleared = [];
$errors = [];

// 1. 清除系统缓存目录
echo "<div class='section'>";
echo "<h2>📁 清除系统缓存</h2>";

$cache_dirs = [
    __DIR__ . '/system/storage/cache',
    'D:/电商/001136.com/system/storage/cache',
    'D:/电商/001136.com/image/cache'
];

foreach ($cache_dirs as $dir) {
    if (is_dir($dir)) {
        $count = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && $file->getFilename() !== '.htaccess' && $file->getFilename() !== 'index.html') {
                if (@unlink($file->getRealPath())) {
                    $count++;
                }
            }
        }
        
        $cleared[] = basename($dir) . " : {$count} 个文件";
        echo "<div class='step success'>✓ " . basename($dir) . " : 清除了 {$count} 个文件</div>";
    } else {
        echo "<div class='step info'>ℹ " . basename($dir) . " : 目录不存在</div>";
    }
}

echo "</div>";

// 2. 清除图片缓存
echo "<div class='section'>";
echo "<h2>🖼️ 清除图片缓存</h2>";
$image_cache = __DIR__ . '/image/cache';
if (is_dir($image_cache)) {
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($image_cache, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            if (@unlink($file->getRealPath())) {
                $count++;
            }
        }
    }
    echo "<div class='step success'>✓ 图片缓存 : 清除了 {$count} 个文件</div>";
} else {
    echo "<div class='step info'>ℹ 图片缓存目录不存在</div>";
}
echo "</div>";

// 3. 输出浏览器缓存清除说明
echo "<div class='section'>";
echo "<h2>🌐 浏览器缓存清除说明</h2>";
echo "<div class='info'>";
echo "<strong>请手动清除浏览器缓存：</strong><br>";
echo "1. 按 <strong>Ctrl + Shift + Delete</strong><br>";
echo "2. 选择：<strong>缓存图片和文件 + Cookie</strong><br>";
echo "3. 时间范围：<strong>全部时间</strong><br>";
echo "4. 点击：<strong>清除数据</strong>";
echo "</div>";
echo "</div>";

// 4. 总结
echo "<div class='section'>";
echo "<h2>📊 清除总结</h2>";
if (!empty($cleared)) {
    echo "<div class='success'>";
    echo "<strong>成功清除：</strong><br>";
    foreach ($cleared as $item) {
        echo "✓ {$item}<br>";
    }
    echo "</div>";
}

if (!empty($errors)) {
    echo "<div class='error'>";
    echo "<strong>错误：</strong><br>";
    foreach ($errors as $error) {
        echo "✗ {$error}<br>";
    }
    echo "</div>";
}
echo "</div>";

// 5. 下一步操作
echo "<div class='section'>";
echo "<h2>🎯 下一步操作</h2>";
echo "<div class='info'>";
echo "<ol>";
echo "<li><strong>启用轮播图模块</strong>：访问 <a href='/admin67676'>后台</a> → Extensions → Modules → Banner → 启用</li>";
echo "<li><strong>清除浏览器缓存</strong>：按 Ctrl + Shift + Delete</li>";
echo "<li><strong>关闭所有浏览器窗口</strong></li>";
echo "<li><strong>重新打开浏览器</strong>，访问 <a href='/'>首页</a></li>";
echo "<li><strong>测试语言切换</strong>：点击简体中文，检查是否跳转</li>";
echo "</ol>";
echo "</div>";
echo "</div>";

// 快速链接
echo "<div class='section'>";
echo "<h2>🔗 快速链接</h2>";
echo "<a href='/admin67676' class='btn'>后台管理</a>";
echo "<a href='/' class='btn'>访问首页</a>";
echo "<a href='/debug_carousel.php' class='btn'>诊断轮播图</a>";
echo "<a href='javascript:location.reload();' class='btn'>刷新本页</a>";
echo "</div>";

echo "</div>"; // container
echo "</body></html>";
?>

