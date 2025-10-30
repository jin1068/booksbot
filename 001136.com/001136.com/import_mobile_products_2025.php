<?php
/**
 * 2025年电子产品批量导入脚本 - 手机与平板
 * 导入2025年热门手机与平板产品到OpenCart数据库
 */

// 引入配置文件
require_once(__DIR__ . '/config.php');

// 连接数据库
$conn = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 查询函数
function query($sql) {
    global $conn;
    $result = $conn->query($sql);
    if ($result === false) {
        die("SQL错误: " . $conn->error . "\nSQL: $sql\n");
    }
    return $result;
}

// 获取分类ID
function getCategoryId($categoryName) {
    $sql = "SELECT c.category_id FROM " . DB_PREFIX . "category c 
            LEFT JOIN " . DB_PREFIX . "category_description cd ON c.category_id = cd.category_id 
            WHERE cd.name = '" . $categoryName . "' AND cd.language_id = 2 LIMIT 1";
    $result = query($sql);
    $row = $result->fetch_assoc();
    return $row ? $row['category_id'] : null;
}

// 获取库存状态ID
function getStockStatusId($statusName = 'In Stock') {
    $sql = "SELECT stock_status_id FROM " . DB_PREFIX . "stock_status WHERE name = '" . $statusName . "' AND language_id = 2 LIMIT 1";
    $result = query($sql);
    $row = $result->fetch_assoc();
    return $row ? $row['stock_status_id'] : 7;
}

// 转义字符串
function esc($value) {
        global $conn;
        return $conn->real_escape_string((string)$value);
}

// 缓存选项与选项值ID
$optionCache = [];
$optionValueCache = [];

function ensureOption($cacheKey, $nameEn, $nameZh, $type = 'select', $sortOrder = 0) {
        global $optionCache, $conn;

        if (isset($optionCache[$cacheKey])) {
                return $optionCache[$cacheKey];
        }

        $nameEnEsc = esc($nameEn);
        $query = query("SELECT o.option_id FROM " . DB_PREFIX . "option o LEFT JOIN " . DB_PREFIX . "option_description od ON o.option_id = od.option_id WHERE od.language_id = 1 AND od.name = '$nameEnEsc' LIMIT 1");

        if ($query->num_rows > 0) {
                $row = $query->fetch_assoc();
                $optionId = (int)$row['option_id'];
        } else {
                query("INSERT INTO " . DB_PREFIX . "option SET type = '" . esc($type) . "', sort_order = " . (int)$sortOrder);
                $optionId = (int)$conn->insert_id;

                query("INSERT INTO " . DB_PREFIX . "option_description SET option_id = $optionId, language_id = 1, name = '$nameEnEsc'");
                query("INSERT INTO " . DB_PREFIX . "option_description SET option_id = $optionId, language_id = 2, name = '" . esc($nameZh) . "'");
        }

        $optionCache[$cacheKey] = $optionId;
        return $optionId;
}

function ensureOptionValue($optionId, $nameEn, $nameZh, $sortOrder = 0) {
        global $optionValueCache, $conn;

        if (!isset($optionValueCache[$optionId])) {
                $optionValueCache[$optionId] = [];
        }

        $cacheKey = md5($nameEn . '|' . $nameZh);

        if (isset($optionValueCache[$optionId][$cacheKey])) {
                return $optionValueCache[$optionId][$cacheKey];
        }

        $nameEnEsc = esc($nameEn);
        $query = query("SELECT ov.option_value_id FROM " . DB_PREFIX . "option_value ov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON ov.option_value_id = ovd.option_value_id WHERE ov.option_id = $optionId AND ovd.language_id = 1 AND ovd.name = '$nameEnEsc' LIMIT 1");

        if ($query->num_rows > 0) {
                $row = $query->fetch_assoc();
                $optionValueId = (int)$row['option_value_id'];
                // 更新中文描述，保持最新
                query("UPDATE " . DB_PREFIX . "option_value_description SET name = '" . esc($nameZh) . "' WHERE option_value_id = $optionValueId AND language_id = 2");
        } else {
                query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = $optionId, image = '', sort_order = " . (int)$sortOrder);
                $optionValueId = (int)$conn->insert_id;

                query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = $optionValueId, language_id = 1, option_id = $optionId, name = '$nameEnEsc'");
                query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = $optionValueId, language_id = 2, option_id = $optionId, name = '" . esc($nameZh) . "'");
        }

        $optionValueCache[$optionId][$cacheKey] = $optionValueId;
        return $optionValueId;
}

function clearProductOptions($productId) {
        query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = " . (int)$productId);
        query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = " . (int)$productId);
}

function formatPrice($value) {
        return number_format((float)$value, 4, '.', '');
}

function buildSeoKeyword($string) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $string), '-'));
        return $slug ?: strtolower(uniqid('product-'));
}

$optionDefinitions = [
        'storage' => [
                'name_en' => 'Storage Capacity',
                'name_zh' => '存储容量',
                'type' => 'select',
                'sort_order' => 1,
        ],
        'color' => [
                'name_en' => 'Color',
                'name_zh' => '颜色',
                'type' => 'select',
                'sort_order' => 2,
        ],
        'carrier' => [
                'name_en' => 'Carrier / Service',
                'name_zh' => '运营商/服务商',
                'type' => 'select',
                'sort_order' => 3,
        ],
        'connectivity' => [
                'name_en' => 'Connectivity',
                'name_zh' => '网络连接方式',
                'type' => 'select',
                'sort_order' => 4,
        ],
];

// 2025年手机与平板产品数据
$mobileProducts = [
    // iPhone 16系列
    [
        'model' => 'iPhone 16',
        'sku' => 'APPLE-IP16-128GB',
        'name_en' => 'Apple iPhone 16 128GB',
        'name_zh' => '苹果 iPhone 16 128GB',
        'description_en' => '<p>The iPhone 16 features the powerful A18 chip, advanced dual-camera system with 48MP main camera, and all-day battery life. Available in stunning colors including Black, White, Pink, Teal, and Ultramarine.</p>
<h3>Key Features:</h3>
<ul>
<li>6.1-inch Super Retina XDR display</li>
<li>A18 chip with 6-core CPU and 5-core GPU</li>
<li>Advanced dual-camera system (48MP Main, 12MP Ultra Wide)</li>
<li>Action button and Camera Control</li>
<li>Up to 22 hours video playback</li>
<li>Ceramic Shield front, aerospace-grade aluminum design</li>
<li>5G capable, Face ID, iOS 18</li>
</ul>',
        'description_zh' => '<p>iPhone 16 搭载强大的A18芯片、先进的双摄像头系统（48MP主摄）和全天候电池续航。提供黑色、白色、粉色、蓝绿色和群青色等多种配色。</p>
<h3>主要特性:</h3>
<ul>
<li>6.1英寸超视网膜XDR显示屏</li>
<li>A18芯片,配备6核CPU和5核GPU</li>
<li>先进双摄系统(48MP主摄、12MP超广角)</li>
<li>操作按钮和相机控制</li>
<li>最长22小时视频播放</li>
<li>陶瓷晶盾面板,航空级铝金属设计</li>
<li>支持5G、Face ID、iOS 18</li>
</ul>',
        'price' => 799.00,
        'quantity' => 100,
        'weight' => 0.171,
        'manufacturer' => 'Apple',
        'image' => 'catalog/1920/iphone-16.png',
        'category' => '手机与平板',
        'meta_title_en' => 'Apple iPhone 16 128GB - Latest 2025 Model',
        'meta_description_en' => 'Buy Apple iPhone 16 128GB with A18 chip, 48MP camera system, and all-day battery. Free shipping. Lowest price in USA.',
        'meta_keywords_en' => 'iPhone 16, Apple iPhone, iPhone 2025, A18 chip, 5G phone',
                'storage_variants' => [
                        [
                                'code' => 'iphone16-128gb',
                                'name_en' => '128 GB',
                                'name_zh' => '128 GB',
                                'final_price' => 799.00,
                                'quantity' => 60,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'iphone16-256gb',
                                'name_en' => '256 GB',
                                'name_zh' => '256 GB',
                                'final_price' => 899.00,
                                'quantity' => 45,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'iphone16-512gb',
                                'name_en' => '512 GB',
                                'name_zh' => '512 GB',
                                'final_price' => 1099.00,
                                'quantity' => 30,
                                'sort_order' => 3,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'iphone16-black', 'name_en' => 'Black', 'name_zh' => '黑色', 'sort_order' => 1],
                        ['code' => 'iphone16-white', 'name_en' => 'White', 'name_zh' => '白色', 'sort_order' => 2],
                        ['code' => 'iphone16-pink', 'name_en' => 'Pink', 'name_zh' => '粉色', 'sort_order' => 3],
                        ['code' => 'iphone16-teal', 'name_en' => 'Teal', 'name_zh' => '蓝绿色', 'sort_order' => 4],
                        ['code' => 'iphone16-ultramarine', 'name_en' => 'Ultramarine', 'name_zh' => '群青色', 'sort_order' => 5],
                ],
                'carrier_options' => [
                        ['code' => 'iphone16-unlocked', 'name_en' => 'Factory Unlocked (SIM-Free)', 'name_zh' => '解锁版 (Factory Unlocked)', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'iphone16-att', 'name_en' => 'AT&T Device Payment', 'name_zh' => 'AT&T 合约机', 'price_adjust' => -30.00, 'sort_order' => 2],
                        ['code' => 'iphone16-tmobile', 'name_en' => 'T-Mobile Payment Plan', 'name_zh' => 'T-Mobile 合约机', 'price_adjust' => -20.00, 'sort_order' => 3],
                        ['code' => 'iphone16-verizon', 'name_en' => 'Verizon Device Payment', 'name_zh' => 'Verizon 合约机', 'price_adjust' => -25.00, 'sort_order' => 4],
                ],
    ],
    [
        'model' => 'iPhone 16 Plus',
        'sku' => 'APPLE-IP16P-128GB',
        'name_en' => 'Apple iPhone 16 Plus 128GB',
        'name_zh' => '苹果 iPhone 16 Plus 128GB',
        'description_en' => '<p>iPhone 16 Plus delivers an expansive viewing experience with a larger 6.7-inch display, A18 chip performance, and exceptional battery life that lasts all day and beyond.</p>
<h3>Key Features:</h3>
<ul>
<li>6.7-inch Super Retina XDR display</li>
<li>A18 chip for amazing performance</li>
<li>Advanced dual-camera system (48MP Main, 12MP Ultra Wide)</li>
<li>Action button and Camera Control</li>
<li>Up to 27 hours video playback</li>
<li>Larger battery for extended use</li>
<li>5G, Face ID, Ceramic Shield, iOS 18</li>
</ul>',
        'description_zh' => '<p>iPhone 16 Plus 拥有更大的6.7英寸显示屏,提供广阔的视觉体验,搭载A18芯片性能卓越,电池续航能力超强可持续整天使用。</p>
<h3>主要特性:</h3>
<ul>
<li>6.7英寸超视网膜XDR显示屏</li>
<li>A18芯片带来惊人性能</li>
<li>先进双摄系统(48MP主摄、12MP超广角)</li>
<li>操作按钮和相机控制</li>
<li>最长27小时视频播放</li>
<li>更大容量电池,续航更持久</li>
<li>支持5G、Face ID、陶瓷晶盾、iOS 18</li>
</ul>',
        'price' => 899.00,
        'quantity' => 80,
        'weight' => 0.199,
        'manufacturer' => 'Apple',
        'image' => 'catalog/1920/iphone-16-plus.png',
        'category' => '手机与平板',
        'meta_title_en' => 'Apple iPhone 16 Plus 128GB - Large Display Model',
        'meta_description_en' => 'iPhone 16 Plus with 6.7-inch display, A18 chip, 48MP camera. Extended battery life. Buy now with free shipping.',
        'meta_keywords_en' => 'iPhone 16 Plus, Apple iPhone Plus, large screen iPhone, A18 chip',
                'storage_variants' => [
                        [
                                'code' => 'iphone16plus-128gb',
                                'name_en' => '128 GB',
                                'name_zh' => '128 GB',
                                'final_price' => 899.00,
                                'quantity' => 50,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'iphone16plus-256gb',
                                'name_en' => '256 GB',
                                'name_zh' => '256 GB',
                                'final_price' => 999.00,
                                'quantity' => 40,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'iphone16plus-512gb',
                                'name_en' => '512 GB',
                                'name_zh' => '512 GB',
                                'final_price' => 1199.00,
                                'quantity' => 30,
                                'sort_order' => 3,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'iphone16plus-black', 'name_en' => 'Black', 'name_zh' => '黑色', 'sort_order' => 1],
                        ['code' => 'iphone16plus-white', 'name_en' => 'White', 'name_zh' => '白色', 'sort_order' => 2],
                        ['code' => 'iphone16plus-pink', 'name_en' => 'Pink', 'name_zh' => '粉色', 'sort_order' => 3],
                        ['code' => 'iphone16plus-teal', 'name_en' => 'Teal', 'name_zh' => '蓝绿色', 'sort_order' => 4],
                        ['code' => 'iphone16plus-ultramarine', 'name_en' => 'Ultramarine', 'name_zh' => '群青色', 'sort_order' => 5],
                ],
                'carrier_options' => [
                        ['code' => 'iphone16plus-unlocked', 'name_en' => 'Factory Unlocked (SIM-Free)', 'name_zh' => '解锁版 (Factory Unlocked)', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'iphone16plus-att', 'name_en' => 'AT&T Device Payment', 'name_zh' => 'AT&T 合约机', 'price_adjust' => -40.00, 'sort_order' => 2],
                        ['code' => 'iphone16plus-tmobile', 'name_en' => 'T-Mobile Payment Plan', 'name_zh' => 'T-Mobile 合约机', 'price_adjust' => -25.00, 'sort_order' => 3],
                        ['code' => 'iphone16plus-verizon', 'name_en' => 'Verizon Device Payment', 'name_zh' => 'Verizon 合约机', 'price_adjust' => -30.00, 'sort_order' => 4],
                ],
    ],
    [
        'model' => 'iPhone 16 Pro',
        'sku' => 'APPLE-IP16PRO-128GB',
        'name_en' => 'Apple iPhone 16 Pro 128GB',
        'name_zh' => '苹果 iPhone 16 Pro 128GB',
        'description_en' => '<p>iPhone 16 Pro is built with titanium and features the A18 Pro chip, advanced triple-camera system with 5x telephoto zoom, and ProMotion display technology.</p>
<h3>Key Features:</h3>
<ul>
<li>6.3-inch Super Retina XDR display with ProMotion (120Hz)</li>
<li>A18 Pro chip with advanced Neural Engine</li>
<li>Pro camera system: 48MP Main, 48MP Ultra Wide, 12MP Telephoto (5x optical zoom)</li>
<li>Titanium design - lightest Pro model ever</li>
<li>Action button and Camera Control with advanced features</li>
<li>Up to 27 hours video playback</li>
<li>ProRAW, ProRes video recording, Cinematic mode</li>
<li>5G, Always-On display, Dynamic Island</li>
</ul>',
        'description_zh' => '<p>iPhone 16 Pro 采用钛金属设计,搭载A18 Pro芯片、先进的三摄系统支持5倍长焦变焦,以及ProMotion显示技术。</p>
<h3>主要特性:</h3>
<ul>
<li>6.3英寸超视网膜XDR显示屏,支持ProMotion (120Hz)</li>
<li>A18 Pro芯片,配备先进神经网络引擎</li>
<li>专业级摄像头系统: 48MP主摄、48MP超广角、12MP长焦(5倍光学变焦)</li>
<li>钛金属设计 - 最轻的Pro机型</li>
<li>操作按钮和相机控制,具备高级功能</li>
<li>最长27小时视频播放</li>
<li>支持ProRAW、ProRes视频录制、电影模式</li>
<li>5G、息屏显示、灵动岛</li>
</ul>',
        'price' => 999.00,
        'quantity' => 90,
        'weight' => 0.199,
        'manufacturer' => 'Apple',
        'image' => 'catalog/1920/iphone-16-pro.png',
        'category' => '手机与平板',
        'meta_title_en' => 'Apple iPhone 16 Pro 128GB - Titanium Design',
        'meta_description_en' => 'iPhone 16 Pro with titanium design, A18 Pro chip, triple camera system with 5x zoom. Professional photography and video.',
        'meta_keywords_en' => 'iPhone 16 Pro, iPhone Pro, titanium iPhone, A18 Pro, ProMotion',
                'storage_variants' => [
                        [
                                'code' => 'iphone16pro-128gb',
                                'name_en' => '128 GB',
                                'name_zh' => '128 GB',
                                'final_price' => 999.00,
                                'quantity' => 50,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'iphone16pro-256gb',
                                'name_en' => '256 GB',
                                'name_zh' => '256 GB',
                                'final_price' => 1099.00,
                                'quantity' => 40,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'iphone16pro-512gb',
                                'name_en' => '512 GB',
                                'name_zh' => '512 GB',
                                'final_price' => 1299.00,
                                'quantity' => 35,
                                'sort_order' => 3,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'iphone16pro-1tb',
                                'name_en' => '1 TB',
                                'name_zh' => '1 TB',
                                'final_price' => 1499.00,
                                'quantity' => 20,
                                'sort_order' => 4,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'iphone16pro-natural', 'name_en' => 'Natural Titanium', 'name_zh' => '天然钛金属', 'sort_order' => 1],
                        ['code' => 'iphone16pro-black', 'name_en' => 'Black Titanium', 'name_zh' => '黑色钛金属', 'sort_order' => 2],
                        ['code' => 'iphone16pro-white', 'name_en' => 'White Titanium', 'name_zh' => '白色钛金属', 'sort_order' => 3],
                        ['code' => 'iphone16pro-desert', 'name_en' => 'Desert Titanium', 'name_zh' => '沙漠钛金属', 'sort_order' => 4],
                ],
                'carrier_options' => [
                        ['code' => 'iphone16pro-unlocked', 'name_en' => 'Factory Unlocked (SIM-Free)', 'name_zh' => '解锁版 (Factory Unlocked)', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'iphone16pro-att', 'name_en' => 'AT&T Device Payment', 'name_zh' => 'AT&T 合约机', 'price_adjust' => -50.00, 'sort_order' => 2],
                        ['code' => 'iphone16pro-verizon', 'name_en' => 'Verizon Device Payment', 'name_zh' => 'Verizon 合约机', 'price_adjust' => -45.00, 'sort_order' => 3],
                        ['code' => 'iphone16pro-tmobile', 'name_en' => 'T-Mobile Payment Plan', 'name_zh' => 'T-Mobile 合约机', 'price_adjust' => -40.00, 'sort_order' => 4],
                ],
    ],
    [
        'model' => 'iPhone 16 Pro Max',
        'sku' => 'APPLE-IP16PMAX-256GB',
        'name_en' => 'Apple iPhone 16 Pro Max 256GB',
        'name_zh' => '苹果 iPhone 16 Pro Max 256GB',
        'description_en' => '<p>The ultimate iPhone 16 Pro Max combines the largest display, longest battery life, and most advanced camera system. Built with titanium for strength and lightness.</p>
<h3>Key Features:</h3>
<ul>
<li>6.9-inch Super Retina XDR display with ProMotion (120Hz)</li>
<li>A18 Pro chip - ultimate performance</li>
<li>Pro camera system: 48MP Main, 48MP Ultra Wide, 12MP Telephoto (5x optical zoom)</li>
<li>Titanium design with aerospace-grade aluminum</li>
<li>Up to 33 hours video playback - best battery life ever</li>
<li>Advanced Camera Control for professional photography</li>
<li>4K ProRes video, Cinematic mode, ProRAW</li>
<li>5G, Always-On display, Dynamic Island, Crash Detection</li>
</ul>',
        'description_zh' => '<p>终极旗舰iPhone 16 Pro Max集最大显示屏、最长电池续航和最先进摄像头系统于一身。钛金属设计兼具强度与轻盈。</p>
<h3>主要特性:</h3>
<ul>
<li>6.9英寸超视网膜XDR显示屏,支持ProMotion (120Hz)</li>
<li>A18 Pro芯片 - 终极性能</li>
<li>专业级摄像头系统: 48MP主摄、48MP超广角、12MP长焦(5倍光学变焦)</li>
<li>钛金属设计,搭配航空级铝金属</li>
<li>最长33小时视频播放 - 史上最强续航</li>
<li>先进相机控制,专业摄影体验</li>
<li>4K ProRes视频、电影模式、ProRAW</li>
<li>5G、息屏显示、灵动岛、车祸检测</li>
</ul>',
        'price' => 1199.00,
        'quantity' => 75,
        'weight' => 0.227,
        'manufacturer' => 'Apple',
        'image' => 'catalog/1920/iphone-16-pro-max.png',
        'category' => '手机与平板',
        'meta_title_en' => 'Apple iPhone 16 Pro Max 256GB - Ultimate iPhone',
        'meta_description_en' => 'iPhone 16 Pro Max with 6.9-inch display, A18 Pro chip, best battery life. Professional camera system. Free shipping.',
        'meta_keywords_en' => 'iPhone 16 Pro Max, iPhone Max, best iPhone, titanium phone, Pro Max',
                'storage_variants' => [
                        [
                                'code' => 'iphone16promax-256gb',
                                'name_en' => '256 GB',
                                'name_zh' => '256 GB',
                                'final_price' => 1199.00,
                                'quantity' => 60,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'iphone16promax-512gb',
                                'name_en' => '512 GB',
                                'name_zh' => '512 GB',
                                'final_price' => 1399.00,
                                'quantity' => 40,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'iphone16promax-1tb',
                                'name_en' => '1 TB',
                                'name_zh' => '1 TB',
                                'final_price' => 1599.00,
                                'quantity' => 30,
                                'sort_order' => 3,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'iphone16promax-2tb',
                                'name_en' => '2 TB',
                                'name_zh' => '2 TB',
                                'final_price' => 1799.00,
                                'quantity' => 15,
                                'sort_order' => 4,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'iphone16promax-natural', 'name_en' => 'Natural Titanium', 'name_zh' => '天然钛金属', 'sort_order' => 1],
                        ['code' => 'iphone16promax-black', 'name_en' => 'Black Titanium', 'name_zh' => '黑色钛金属', 'sort_order' => 2],
                        ['code' => 'iphone16promax-white', 'name_en' => 'White Titanium', 'name_zh' => '白色钛金属', 'sort_order' => 3],
                        ['code' => 'iphone16promax-desert', 'name_en' => 'Desert Titanium', 'name_zh' => '沙漠钛金属', 'sort_order' => 4],
                ],
                'carrier_options' => [
                        ['code' => 'iphone16promax-unlocked', 'name_en' => 'Factory Unlocked (SIM-Free)', 'name_zh' => '解锁版 (Factory Unlocked)', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'iphone16promax-att', 'name_en' => 'AT&T Device Payment', 'name_zh' => 'AT&T 合约机', 'price_adjust' => -60.00, 'sort_order' => 2],
                        ['code' => 'iphone16promax-verizon', 'name_en' => 'Verizon Device Payment', 'name_zh' => 'Verizon 合约机', 'price_adjust' => -55.00, 'sort_order' => 3],
                        ['code' => 'iphone16promax-tmobile', 'name_en' => 'T-Mobile Payment Plan', 'name_zh' => 'T-Mobile 合约机', 'price_adjust' => -45.00, 'sort_order' => 4],
                ],
    ],
    
    // Samsung Galaxy S25系列
    [
        'model' => 'Galaxy S25',
        'sku' => 'SAMSUNG-S25-256GB',
        'name_en' => 'Samsung Galaxy S25 256GB',
        'name_zh' => '三星 Galaxy S25 256GB',
        'description_en' => '<p>Samsung Galaxy S25 brings cutting-edge AI features, Snapdragon 8 Gen 4 processor, and stunning Dynamic AMOLED 2X display. Experience the future of mobile innovation.</p>
<h3>Key Features:</h3>
<ul>
<li>6.2-inch Dynamic AMOLED 2X display (120Hz adaptive refresh rate)</li>
<li>Snapdragon 8 Gen 4 for Galaxy processor</li>
<li>Triple camera: 50MP Wide, 12MP Ultra Wide, 10MP Telephoto (3x)</li>
<li>Galaxy AI features for enhanced photography and productivity</li>
<li>4,000mAh battery with 25W fast charging</li>
<li>Armor Aluminum frame with Gorilla Glass Victus 3</li>
<li>IP68 water and dust resistance</li>
<li>One UI 7 based on Android 15</li>
</ul>',
        'description_zh' => '<p>三星Galaxy S25搭载前沿AI功能、骁龙8 Gen 4处理器和惊艳的Dynamic AMOLED 2X显示屏。体验移动创新的未来。</p>
<h3>主要特性:</h3>
<ul>
<li>6.2英寸Dynamic AMOLED 2X显示屏(120Hz自适应刷新率)</li>
<li>为Galaxy优化的骁龙8 Gen 4处理器</li>
<li>三摄系统: 50MP广角、12MP超广角、10MP长焦(3倍)</li>
<li>Galaxy AI功能增强摄影和生产力</li>
<li>4000mAh电池,支持25W快充</li>
<li>装甲铝框架,配备大猩猩玻璃Victus 3</li>
<li>IP68防水防尘</li>
<li>基于Android 15的One UI 7</li>
</ul>',
        'price' => 799.99,
        'quantity' => 85,
        'weight' => 0.168,
        'manufacturer' => 'Samsung',
        'image' => 'catalog/1920/galaxy-s25.png',
        'category' => '手机与平板',
        'meta_title_en' => 'Samsung Galaxy S25 256GB - AI-Powered Smartphone',
        'meta_description_en' => 'Galaxy S25 with Snapdragon 8 Gen 4, AI features, 120Hz display. Buy at best price with free shipping.',
        'meta_keywords_en' => 'Galaxy S25, Samsung S25, Android phone, Snapdragon 8 Gen 4',
                'storage_variants' => [
                        [
                                'code' => 'galaxys25-256gb',
                                'name_en' => '256 GB',
                                'name_zh' => '256 GB',
                                'final_price' => 799.99,
                                'quantity' => 70,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'galaxys25-512gb',
                                'name_en' => '512 GB',
                                'name_zh' => '512 GB',
                                'final_price' => 899.99,
                                'quantity' => 40,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'galaxys25-onyx-black', 'name_en' => 'Onyx Black', 'name_zh' => '缟玛瑙黑', 'sort_order' => 1],
                        ['code' => 'galaxys25-ice-blue', 'name_en' => 'Ice Blue', 'name_zh' => '冰蓝色', 'sort_order' => 2],
                        ['code' => 'galaxys25-sandstone', 'name_en' => 'Sandstone Orange', 'name_zh' => '砂岩橙', 'sort_order' => 3],
                ],
                'carrier_options' => [
                        ['code' => 'galaxys25-unlocked', 'name_en' => 'Factory Unlocked (SIM-Free)', 'name_zh' => '解锁版 (Factory Unlocked)', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'galaxys25-att', 'name_en' => 'AT&T 5G', 'name_zh' => 'AT&T 5G', 'price_adjust' => -50.00, 'sort_order' => 2],
                        ['code' => 'galaxys25-verizon', 'name_en' => 'Verizon 5G', 'name_zh' => 'Verizon 5G', 'price_adjust' => -40.00, 'sort_order' => 3],
                        ['code' => 'galaxys25-tmobile', 'name_en' => 'T-Mobile 5G', 'name_zh' => 'T-Mobile 5G', 'price_adjust' => -40.00, 'sort_order' => 4],
                ],
    ],
    [
        'model' => 'Galaxy S25+',
        'sku' => 'SAMSUNG-S25P-256GB',
        'name_en' => 'Samsung Galaxy S25+ 256GB',
        'name_zh' => '三星 Galaxy S25+ 256GB',
        'description_en' => '<p>Galaxy S25+ offers a larger 6.7-inch display, enhanced battery capacity, and premium design. Perfect balance of size, performance, and innovation.</p>
<h3>Key Features:</h3>
<ul>
<li>6.7-inch Dynamic AMOLED 2X display (120Hz)</li>
<li>Snapdragon 8 Gen 4 for Galaxy</li>
<li>Advanced triple camera system with improved night photography</li>
<li>4,900mAh battery with 45W super fast charging</li>
<li>12GB RAM for seamless multitasking</li>
<li>Enhanced Galaxy AI capabilities</li>
<li>Premium metal and glass design</li>
<li>IP68 rated, wireless charging, reverse wireless charging</li>
</ul>',
        'description_zh' => '<p>Galaxy S25+配备更大的6.7英寸显示屏、增强的电池容量和高端设计。完美平衡尺寸、性能和创新。</p>
<h3>主要特性:</h3>
<ul>
<li>6.7英寸Dynamic AMOLED 2X显示屏(120Hz)</li>
<li>为Galaxy优化的骁龙8 Gen 4</li>
<li>先进三摄系统,夜景拍摄能力提升</li>
<li>4900mAh电池,支持45W超级快充</li>
<li>12GB内存,实现无缝多任务处理</li>
<li>增强的Galaxy AI能力</li>
<li>高端金属玻璃设计</li>
<li>IP68防护、无线充电、反向无线充电</li>
</ul>',
        'price' => 999.99,
        'quantity' => 70,
        'weight' => 0.190,
        'manufacturer' => 'Samsung',
        'image' => 'catalog/1920/galaxy-s25-plus.png',
        'category' => '手机与平板',
        'meta_title_en' => 'Samsung Galaxy S25+ 256GB - Premium Android Phone',
        'meta_description_en' => 'Galaxy S25+ with 6.7-inch display, 45W fast charging, enhanced AI. Best price guaranteed.',
        'meta_keywords_en' => 'Galaxy S25 Plus, Samsung S25+, large screen phone, premium Android',
                'storage_variants' => [
                        [
                                'code' => 'galaxys25plus-256gb',
                                'name_en' => '256 GB',
                                'name_zh' => '256 GB',
                                'final_price' => 999.99,
                                'quantity' => 50,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'galaxys25plus-512gb',
                                'name_en' => '512 GB',
                                'name_zh' => '512 GB',
                                'final_price' => 1099.99,
                                'quantity' => 35,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'galaxys25plus-onyx-black', 'name_en' => 'Onyx Black', 'name_zh' => '缟玛瑙黑', 'sort_order' => 1],
                        ['code' => 'galaxys25plus-titanium-silver', 'name_en' => 'Titanium Silver', 'name_zh' => '钛银色', 'sort_order' => 2],
                        ['code' => 'galaxys25plus-sandstone', 'name_en' => 'Sandstone Orange', 'name_zh' => '砂岩橙', 'sort_order' => 3],
                ],
                'carrier_options' => [
                        ['code' => 'galaxys25plus-unlocked', 'name_en' => 'Factory Unlocked (SIM-Free)', 'name_zh' => '解锁版 (Factory Unlocked)', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'galaxys25plus-att', 'name_en' => 'AT&T 5G', 'name_zh' => 'AT&T 5G', 'price_adjust' => -60.00, 'sort_order' => 2],
                        ['code' => 'galaxys25plus-verizon', 'name_en' => 'Verizon 5G', 'name_zh' => 'Verizon 5G', 'price_adjust' => -50.00, 'sort_order' => 3],
                        ['code' => 'galaxys25plus-tmobile', 'name_en' => 'T-Mobile 5G', 'name_zh' => 'T-Mobile 5G', 'price_adjust' => -45.00, 'sort_order' => 4],
                ],
    ],
    [
        'model' => 'Galaxy S25 Ultra',
        'sku' => 'SAMSUNG-S25U-512GB',
        'name_en' => 'Samsung Galaxy S25 Ultra 512GB',
        'name_zh' => '三星 Galaxy S25 Ultra 512GB',
        'description_en' => '<p>The ultimate Galaxy S25 Ultra features a titanium frame, integrated S Pen, quad camera system with 200MP main sensor, and the most advanced Galaxy AI features.</p>
<h3>Key Features:</h3>
<ul>
<li>6.8-inch Dynamic AMOLED 2X display with anti-reflective coating (120Hz)</li>
<li>Snapdragon 8 Gen 4 for Galaxy - peak performance</li>
<li>Quad camera: 200MP Wide, 50MP Periscope Telephoto (5x), 10MP Telephoto (3x), 12MP Ultra Wide</li>
<li>Integrated S Pen with advanced note-taking features</li>
<li>5,000mAh battery with 45W super fast charging</li>
<li>Titanium frame - incredibly durable and lightweight</li>
<li>16GB RAM, up to 1TB storage options</li>
<li>Advanced Galaxy AI for photo editing, translation, and productivity</li>
<li>IP68, Gorilla Armor glass, One UI 7</li>
</ul>',
        'description_zh' => '<p>终极旗舰Galaxy S25 Ultra采用钛金属框架、集成S Pen、配备200MP主摄的四摄系统,以及最先进的Galaxy AI功能。</p>
<h3>主要特性:</h3>
<ul>
<li>6.8英寸Dynamic AMOLED 2X显示屏,配备防反光涂层(120Hz)</li>
<li>为Galaxy优化的骁龙8 Gen 4 - 巅峰性能</li>
<li>四摄系统: 200MP广角、50MP潜望式长焦(5倍)、10MP长焦(3倍)、12MP超广角</li>
<li>集成S Pen,具备先进笔记功能</li>
<li>5000mAh电池,支持45W超级快充</li>
<li>钛金属框架 - 超强耐用且轻盈</li>
<li>16GB内存,最高1TB存储选项</li>
<li>先进Galaxy AI用于照片编辑、翻译和生产力</li>
<li>IP68防护、大猩猩装甲玻璃、One UI 7</li>
</ul>',
        'price' => 1299.99,
        'quantity' => 60,
        'weight' => 0.232,
        'manufacturer' => 'Samsung',
        'image' => 'catalog/1920/galaxy-s25-ultra.png',
        'category' => '手机与平板',
        'meta_title_en' => 'Samsung Galaxy S25 Ultra 512GB - Ultimate Flagship',
        'meta_description_en' => 'Galaxy S25 Ultra with titanium design, 200MP camera, S Pen, and advanced AI. The ultimate Android flagship.',
        'meta_keywords_en' => 'Galaxy S25 Ultra, Samsung Ultra, S Pen, 200MP camera, titanium phone',
                'storage_variants' => [
                        [
                                'code' => 'galaxys25ultra-512gb',
                                'name_en' => '512 GB',
                                'name_zh' => '512 GB',
                                'final_price' => 1299.99,
                                'quantity' => 40,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'galaxys25ultra-1tb',
                                'name_en' => '1 TB',
                                'name_zh' => '1 TB',
                                'final_price' => 1499.99,
                                'quantity' => 25,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'galaxys25ultra-titanium-gray', 'name_en' => 'Titanium Gray', 'name_zh' => '钛灰色', 'sort_order' => 1],
                        ['code' => 'galaxys25ultra-titanium-black', 'name_en' => 'Titanium Black', 'name_zh' => '钛黑色', 'sort_order' => 2],
                        ['code' => 'galaxys25ultra-titanium-white', 'name_en' => 'Titanium White', 'name_zh' => '钛白色', 'sort_order' => 3],
                        ['code' => 'galaxys25ultra-titanium-green', 'name_en' => 'Titanium Green', 'name_zh' => '钛绿色', 'sort_order' => 4],
                ],
                'carrier_options' => [
                        ['code' => 'galaxys25ultra-unlocked', 'name_en' => 'Factory Unlocked (SIM-Free)', 'name_zh' => '解锁版 (Factory Unlocked)', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'galaxys25ultra-att', 'name_en' => 'AT&T 5G', 'name_zh' => 'AT&T 5G', 'price_adjust' => -70.00, 'sort_order' => 2],
                        ['code' => 'galaxys25ultra-verizon', 'name_en' => 'Verizon 5G', 'name_zh' => 'Verizon 5G', 'price_adjust' => -60.00, 'sort_order' => 3],
                        ['code' => 'galaxys25ultra-tmobile', 'name_en' => 'T-Mobile 5G', 'name_zh' => 'T-Mobile 5G', 'price_adjust' => -55.00, 'sort_order' => 4],
                ],
    ],
    
    // Google Pixel 9系列
    [
        'model' => 'Pixel 9',
        'sku' => 'GOOGLE-P9-128GB',
        'name_en' => 'Google Pixel 9 128GB',
        'name_zh' => '谷歌 Pixel 9 128GB',
        'description_en' => '<p>Google Pixel 9 powered by Google Tensor G4 delivers exceptional AI photography, pure Android experience, and advanced machine learning capabilities.</p>
<h3>Key Features:</h3>
<ul>
<li>6.3-inch Actua display (120Hz OLED)</li>
<li>Google Tensor G4 chip with Titan M2 security</li>
<li>Dual camera: 50MP Wide with OIS, 48MP Ultra Wide</li>
<li>Magic Eraser, Best Take, Photo Unblur powered by AI</li>
<li>4,700mAh battery with fast wireless charging</li>
<li>7 years of OS, security, and Feature Drop updates</li>
<li>Gemini AI assistant integration</li>
<li>Pure Android 15 experience</li>
</ul>',
        'description_zh' => '<p>谷歌Pixel 9搭载Tensor G4芯片,提供卓越的AI摄影、纯净Android体验和先进的机器学习功能。</p>
<h3>主要特性:</h3>
<ul>
<li>6.3英寸Actua显示屏(120Hz OLED)</li>
<li>谷歌Tensor G4芯片,配备Titan M2安全芯片</li>
<li>双摄系统: 50MP广角支持OIS、48MP超广角</li>
<li>AI驱动的魔术橡皮擦、最佳拍摄、照片去模糊</li>
<li>4700mAh电池,支持快速无线充电</li>
<li>7年操作系统、安全和功能更新</li>
<li>Gemini AI助手集成</li>
<li>纯净Android 15体验</li>
</ul>',
        'price' => 699.00,
        'quantity' => 75,
        'weight' => 0.198,
        'manufacturer' => 'Google',
        'image' => 'catalog/1920/pixel-9.png',
        'category' => '手机与平板',
        'meta_title_en' => 'Google Pixel 9 128GB - AI Photography Expert',
        'meta_description_en' => 'Pixel 9 with Tensor G4, advanced AI camera, 7 years of updates. Pure Android experience.',
        'meta_keywords_en' => 'Google Pixel 9, Pixel phone, Tensor G4, AI camera, pure Android',
                'storage_variants' => [
                        [
                                'code' => 'pixel9-128gb',
                                'name_en' => '128 GB',
                                'name_zh' => '128 GB',
                                'final_price' => 699.00,
                                'quantity' => 60,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'pixel9-256gb',
                                'name_en' => '256 GB',
                                'name_zh' => '256 GB',
                                'final_price' => 799.00,
                                'quantity' => 40,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'pixel9-obsidian', 'name_en' => 'Obsidian', 'name_zh' => '黑曜石', 'sort_order' => 1],
                        ['code' => 'pixel9-hazel', 'name_en' => 'Hazel', 'name_zh' => '榛子色', 'sort_order' => 2],
                        ['code' => 'pixel9-porcelain', 'name_en' => 'Porcelain', 'name_zh' => '瓷白色', 'sort_order' => 3],
                ],
                'carrier_options' => [
                        ['code' => 'pixel9-unlocked', 'name_en' => 'Factory Unlocked (SIM-Free)', 'name_zh' => '解锁版 (Factory Unlocked)', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'pixel9-googlefi', 'name_en' => 'Google Fi Wireless', 'name_zh' => 'Google Fi Wireless', 'price_adjust' => -25.00, 'sort_order' => 2],
                        ['code' => 'pixel9-verizon', 'name_en' => 'Verizon 5G', 'name_zh' => 'Verizon 5G', 'price_adjust' => -30.00, 'sort_order' => 3],
                        ['code' => 'pixel9-att', 'name_en' => 'AT&T 5G', 'name_zh' => 'AT&T 5G', 'price_adjust' => -20.00, 'sort_order' => 4],
                        ['code' => 'pixel9-tmobile', 'name_en' => 'T-Mobile 5G', 'name_zh' => 'T-Mobile 5G', 'price_adjust' => -20.00, 'sort_order' => 5],
                ],
    ],
    [
        'model' => 'Pixel 9 Pro',
        'sku' => 'GOOGLE-P9PRO-256GB',
        'name_en' => 'Google Pixel 9 Pro 256GB',
        'name_zh' => '谷歌 Pixel 9 Pro 256GB',
        'description_en' => '<p>Pixel 9 Pro combines professional-grade photography with Google AI innovation. Features triple camera system and Super Actua display.</p>
<h3>Key Features:</h3>
<ul>
<li>6.3-inch Super Actua display (120Hz LTPO OLED, up to 3000 nits)</li>
<li>Google Tensor G4 with advanced AI capabilities</li>
<li>Triple camera: 50MP Wide, 48MP Ultra Wide, 48MP Telephoto (5x optical zoom)</li>
<li>Video Boost, Night Sight, Astrophotography mode</li>
<li>4,700mAh battery with 30W fast charging</li>
<li>12GB RAM for seamless performance</li>
<li>Gemini Advanced AI features</li>
<li>Premium matte glass and polished metal design</li>
</ul>',
        'description_zh' => '<p>Pixel 9 Pro将专业级摄影与谷歌AI创新相结合。配备三摄系统和Super Actua显示屏。</p>
<h3>主要特性:</h3>
<ul>
<li>6.3英寸Super Actua显示屏(120Hz LTPO OLED,最高3000尼特)</li>
<li>谷歌Tensor G4,具备先进AI能力</li>
<li>三摄系统: 50MP广角、48MP超广角、48MP长焦(5倍光学变焦)</li>
<li>视频增强、夜视、天文摄影模式</li>
<li>4700mAh电池,支持30W快充</li>
<li>12GB内存,流畅性能</li>
<li>Gemini高级AI功能</li>
<li>高端磨砂玻璃和抛光金属设计</li>
</ul>',
        'price' => 999.00,
        'quantity' => 65,
        'weight' => 0.199,
        'manufacturer' => 'Google',
        'image' => 'catalog/1920/pixel-9-pro.png',
        'category' => '手机与平板',
        'meta_title_en' => 'Google Pixel 9 Pro 256GB - Professional AI Camera',
        'meta_description_en' => 'Pixel 9 Pro with triple camera, Tensor G4, Gemini AI. Professional photography in your pocket.',
        'meta_keywords_en' => 'Pixel 9 Pro, Google Pro phone, AI photography, Tensor G4, 5x zoom',
                'storage_variants' => [
                        [
                                'code' => 'pixel9pro-256gb',
                                'name_en' => '256 GB',
                                'name_zh' => '256 GB',
                                'final_price' => 999.00,
                                'quantity' => 45,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'pixel9pro-512gb',
                                'name_en' => '512 GB',
                                'name_zh' => '512 GB',
                                'final_price' => 1099.00,
                                'quantity' => 30,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'pixel9pro-obsidian', 'name_en' => 'Obsidian', 'name_zh' => '黑曜石', 'sort_order' => 1],
                        ['code' => 'pixel9pro-porcelain', 'name_en' => 'Porcelain', 'name_zh' => '瓷白色', 'sort_order' => 2],
                        ['code' => 'pixel9pro-bay', 'name_en' => 'Bay', 'name_zh' => '海湾蓝', 'sort_order' => 3],
                        ['code' => 'pixel9pro-hazel', 'name_en' => 'Hazel', 'name_zh' => '榛子色', 'sort_order' => 4],
                ],
                'carrier_options' => [
                        ['code' => 'pixel9pro-unlocked', 'name_en' => 'Factory Unlocked (SIM-Free)', 'name_zh' => '解锁版 (Factory Unlocked)', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'pixel9pro-googlefi', 'name_en' => 'Google Fi Wireless', 'name_zh' => 'Google Fi Wireless', 'price_adjust' => -30.00, 'sort_order' => 2],
                        ['code' => 'pixel9pro-verizon', 'name_en' => 'Verizon 5G', 'name_zh' => 'Verizon 5G', 'price_adjust' => -35.00, 'sort_order' => 3],
                        ['code' => 'pixel9pro-att', 'name_en' => 'AT&T 5G', 'name_zh' => 'AT&T 5G', 'price_adjust' => -25.00, 'sort_order' => 4],
                        ['code' => 'pixel9pro-tmobile', 'name_en' => 'T-Mobile 5G', 'name_zh' => 'T-Mobile 5G', 'price_adjust' => -25.00, 'sort_order' => 5],
                ],
    ],
    
    // iPad系列
    [
        'model' => 'iPad Pro 11-inch M4',
        'sku' => 'APPLE-IPADPRO11-M4-256GB',
        'name_en' => 'Apple iPad Pro 11-inch M4 256GB Wi-Fi',
        'name_zh' => '苹果 iPad Pro 11英寸 M4 256GB Wi-Fi',
        'description_en' => '<p>The all-new iPad Pro with M4 chip delivers unprecedented performance in an impossibly thin design. Features Ultra Retina XDR display with tandem OLED technology.</p>
<h3>Key Features:</h3>
<ul>
<li>11-inch Ultra Retina XDR display with ProMotion (120Hz)</li>
<li>Apple M4 chip - up to 10-core CPU and 10-core GPU</li>
<li>Tandem OLED technology for extreme brightness and contrast</li>
<li>12MP Wide camera, 10MP Ultra Wide, LiDAR Scanner</li>
<li>12MP Ultra Wide front camera with Center Stage</li>
<li>Face ID, Apple Pencil Pro support</li>
<li>All-day battery life</li>
<li>5.3mm thin - thinnest Apple product ever</li>
<li>iPadOS 18 with desktop-class apps</li>
</ul>',
        'description_zh' => '<p>全新iPad Pro搭载M4芯片,在超薄设计中提供前所未有的性能。配备采用串联OLED技术的Ultra Retina XDR显示屏。</p>
<h3>主要特性:</h3>
<ul>
<li>11英寸Ultra Retina XDR显示屏,支持ProMotion (120Hz)</li>
<li>Apple M4芯片 - 最高10核CPU和10核GPU</li>
<li>串联OLED技术带来极致亮度和对比度</li>
<li>12MP广角摄像头、10MP超广角、LiDAR扫描仪</li>
<li>12MP超广角前置摄像头,支持人物居中</li>
<li>Face ID、支持Apple Pencil Pro</li>
<li>全天候电池续航</li>
<li>5.3mm超薄 - 史上最薄的Apple产品</li>
<li>iPadOS 18,支持桌面级应用</li>
</ul>',
        'price' => 999.00,
        'quantity' => 50,
        'weight' => 0.444,
        'manufacturer' => 'Apple',
        'image' => 'catalog/1920/ipad-pro-11-m4.png',
        'category' => '手机与平板',
        'meta_title_en' => 'iPad Pro 11-inch M4 256GB - Ultra Thin Powerhouse',
        'meta_description_en' => 'iPad Pro 11-inch with M4 chip, Ultra Retina XDR display, Apple Pencil Pro support. Ultimate tablet experience.',
        'meta_keywords_en' => 'iPad Pro, M4 chip, iPad 2025, OLED iPad, Apple Pencil Pro',
                'storage_variants' => [
                        [
                                'code' => 'ipadpro11m4-256gb',
                                'name_en' => '256 GB',
                                'name_zh' => '256 GB',
                                'final_price' => 999.00,
                                'quantity' => 40,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'ipadpro11m4-512gb',
                                'name_en' => '512 GB',
                                'name_zh' => '512 GB',
                                'final_price' => 1199.00,
                                'quantity' => 30,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'ipadpro11m4-1tb',
                                'name_en' => '1 TB',
                                'name_zh' => '1 TB',
                                'final_price' => 1599.00,
                                'quantity' => 20,
                                'sort_order' => 3,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'ipadpro11m4-silver', 'name_en' => 'Silver', 'name_zh' => '银色', 'sort_order' => 1],
                        ['code' => 'ipadpro11m4-spaceblack', 'name_en' => 'Space Black', 'name_zh' => '深空黑', 'sort_order' => 2],
                ],
                'connectivity_options' => [
                        ['code' => 'ipadpro11m4-wifi', 'name_en' => 'Wi-Fi Only', 'name_zh' => '仅 Wi-Fi', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'ipadpro11m4-cellular-unlocked', 'name_en' => 'Wi-Fi + Cellular (Unlocked)', 'name_zh' => 'Wi-Fi + 蜂窝版 (解锁)', 'price_adjust' => 200.00, 'sort_order' => 2],
                        ['code' => 'ipadpro11m4-cellular-verizon', 'name_en' => 'Wi-Fi + Cellular (Verizon)', 'name_zh' => 'Wi-Fi + 蜂窝版 (Verizon)', 'price_adjust' => 220.00, 'sort_order' => 3],
                        ['code' => 'ipadpro11m4-cellular-tmobile', 'name_en' => 'Wi-Fi + Cellular (T-Mobile)', 'name_zh' => 'Wi-Fi + 蜂窝版 (T-Mobile)', 'price_adjust' => 220.00, 'sort_order' => 4],
                ],
    ],
    [
        'model' => 'iPad Pro 13-inch M4',
        'sku' => 'APPLE-IPADPRO13-M4-512GB',
        'name_en' => 'Apple iPad Pro 13-inch M4 512GB Wi-Fi',
        'name_zh' => '苹果 iPad Pro 13英寸 M4 512GB Wi-Fi',
        'description_en' => '<p>The largest iPad Pro ever with stunning 13-inch Ultra Retina XDR display and M4 chip performance. Perfect for creative professionals and power users.</p>
<h3>Key Features:</h3>
<ul>
<li>13-inch Ultra Retina XDR display with ProMotion (120Hz)</li>
<li>M4 chip with up to 10-core CPU and 10-core GPU</li>
<li>Tandem OLED for incredible HDR content</li>
<li>Advanced camera system with LiDAR</li>
<li>Four-speaker audio system with spatial audio</li>
<li>Thunderbolt / USB 4 support</li>
<li>Magic Keyboard and Apple Pencil Pro compatible</li>
<li>Up to 2TB storage options</li>
<li>All-day battery, 5.1mm thin</li>
</ul>',
        'description_zh' => '<p>史上最大的iPad Pro,配备惊艳的13英寸Ultra Retina XDR显示屏和M4芯片性能。创意专业人士和高级用户的完美之选。</p>
<h3>主要特性:</h3>
<ul>
<li>13英寸Ultra Retina XDR显示屏,支持ProMotion (120Hz)</li>
<li>M4芯片,最高10核CPU和10核GPU</li>
<li>串联OLED带来惊艳HDR内容</li>
<li>先进摄像头系统,配备LiDAR</li>
<li>四扬声器音频系统,支持空间音频</li>
<li>支持Thunderbolt / USB 4</li>
<li>兼容妙控键盘和Apple Pencil Pro</li>
<li>最高2TB存储选项</li>
<li>全天候电池续航,5.1mm超薄</li>
</ul>',
        'price' => 1299.00,
        'quantity' => 45,
        'weight' => 0.579,
        'manufacturer' => 'Apple',
        'image' => 'catalog/1920/ipad-pro-13-m4.png',
        'category' => '手机与平板',
        'meta_title_en' => 'iPad Pro 13-inch M4 512GB - Largest iPad Pro',
        'meta_description_en' => 'iPad Pro 13-inch with M4 chip, massive display, professional features. For creators and power users.',
        'meta_keywords_en' => 'iPad Pro 13, M4 iPad, large tablet, professional iPad, OLED tablet',
                'storage_variants' => [
                        [
                                'code' => 'ipadpro13m4-256gb',
                                'name_en' => '256 GB',
                                'name_zh' => '256 GB',
                                'final_price' => 1299.00,
                                'quantity' => 30,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'ipadpro13m4-512gb',
                                'name_en' => '512 GB',
                                'name_zh' => '512 GB',
                                'final_price' => 1499.00,
                                'quantity' => 25,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'ipadpro13m4-1tb',
                                'name_en' => '1 TB',
                                'name_zh' => '1 TB',
                                'final_price' => 1899.00,
                                'quantity' => 20,
                                'sort_order' => 3,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'ipadpro13m4-2tb',
                                'name_en' => '2 TB',
                                'name_zh' => '2 TB',
                                'final_price' => 2299.00,
                                'quantity' => 10,
                                'sort_order' => 4,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'ipadpro13m4-silver', 'name_en' => 'Silver', 'name_zh' => '银色', 'sort_order' => 1],
                        ['code' => 'ipadpro13m4-spaceblack', 'name_en' => 'Space Black', 'name_zh' => '深空黑', 'sort_order' => 2],
                ],
                'connectivity_options' => [
                        ['code' => 'ipadpro13m4-wifi', 'name_en' => 'Wi-Fi Only', 'name_zh' => '仅 Wi-Fi', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'ipadpro13m4-cellular-unlocked', 'name_en' => 'Wi-Fi + Cellular (Unlocked)', 'name_zh' => 'Wi-Fi + 蜂窝版 (解锁)', 'price_adjust' => 200.00, 'sort_order' => 2],
                        ['code' => 'ipadpro13m4-cellular-verizon', 'name_en' => 'Wi-Fi + Cellular (Verizon)', 'name_zh' => 'Wi-Fi + 蜂窝版 (Verizon)', 'price_adjust' => 220.00, 'sort_order' => 3],
                        ['code' => 'ipadpro13m4-cellular-tmobile', 'name_en' => 'Wi-Fi + Cellular (T-Mobile)', 'name_zh' => 'Wi-Fi + 蜂窝版 (T-Mobile)', 'price_adjust' => 220.00, 'sort_order' => 4],
                ],
    ],
    [
        'model' => 'iPad Air 11-inch M2',
        'sku' => 'APPLE-IPADAIR11-M2-128GB',
        'name_en' => 'Apple iPad Air 11-inch M2 128GB Wi-Fi',
        'name_zh' => '苹果 iPad Air 11英寸 M2 128GB Wi-Fi',
        'description_en' => '<p>iPad Air with M2 chip brings serious performance and versatility in a gorgeous, portable design. Perfect balance of power and portability.</p>
<h3>Key Features:</h3>
<ul>
<li>11-inch Liquid Retina display</li>
<li>Apple M2 chip with 8-core CPU and 10-core GPU</li>
<li>12MP Wide camera with Smart HDR 4</li>
<li>12MP Ultra Wide front camera with Center Stage</li>
<li>Compatible with Apple Pencil Pro and Magic Keyboard</li>
<li>All-day battery life</li>
<li>Touch ID, USB-C, Wi-Fi 6E</li>
<li>Available in stunning colors</li>
</ul>',
        'description_zh' => '<p>搭载M2芯片的iPad Air在精美便携的设计中提供强大性能和多功能性。性能与便携性的完美平衡。</p>
<h3>主要特性:</h3>
<ul>
<li>11英寸Liquid Retina显示屏</li>
<li>Apple M2芯片,配备8核CPU和10核GPU</li>
<li>12MP广角摄像头,支持Smart HDR 4</li>
<li>12MP超广角前置摄像头,支持人物居中</li>
<li>兼容Apple Pencil Pro和妙控键盘</li>
<li>全天候电池续航</li>
<li>Touch ID、USB-C、Wi-Fi 6E</li>
<li>提供多种绚丽配色</li>
</ul>',
        'price' => 599.00,
        'quantity' => 80,
        'weight' => 0.462,
        'manufacturer' => 'Apple',
        'image' => 'catalog/1920/ipad-air-11-m2.png',
        'category' => '手机与平板',
        'meta_title_en' => 'iPad Air 11-inch M2 128GB - Powerful & Portable',
        'meta_description_en' => 'iPad Air with M2 chip, 11-inch display, Apple Pencil Pro support. Perfect balance of performance and portability.',
        'meta_keywords_en' => 'iPad Air, M2 chip, iPad 11 inch, Apple tablet, versatile iPad',
                'storage_variants' => [
                        [
                                'code' => 'ipadair11m2-128gb',
                                'name_en' => '128 GB',
                                'name_zh' => '128 GB',
                                'final_price' => 599.00,
                                'quantity' => 60,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'ipadair11m2-256gb',
                                'name_en' => '256 GB',
                                'name_zh' => '256 GB',
                                'final_price' => 749.00,
                                'quantity' => 45,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'ipadair11m2-blue', 'name_en' => 'Blue', 'name_zh' => '蓝色', 'sort_order' => 1],
                        ['code' => 'ipadair11m2-purple', 'name_en' => 'Purple', 'name_zh' => '紫色', 'sort_order' => 2],
                        ['code' => 'ipadair11m2-starlight', 'name_en' => 'Starlight', 'name_zh' => '星光色', 'sort_order' => 3],
                        ['code' => 'ipadair11m2-spacegray', 'name_en' => 'Space Gray', 'name_zh' => '深空灰', 'sort_order' => 4],
                ],
                'connectivity_options' => [
                        ['code' => 'ipadair11m2-wifi', 'name_en' => 'Wi-Fi Only', 'name_zh' => '仅 Wi-Fi', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'ipadair11m2-cellular-unlocked', 'name_en' => 'Wi-Fi + Cellular (Unlocked)', 'name_zh' => 'Wi-Fi + 蜂窝版 (解锁)', 'price_adjust' => 150.00, 'sort_order' => 2],
                        ['code' => 'ipadair11m2-cellular-att', 'name_en' => 'Wi-Fi + Cellular (AT&T)', 'name_zh' => 'Wi-Fi + 蜂窝版 (AT&T)', 'price_adjust' => 150.00, 'sort_order' => 3],
                        ['code' => 'ipadair11m2-cellular-verizon', 'name_en' => 'Wi-Fi + Cellular (Verizon)', 'name_zh' => 'Wi-Fi + 蜂窝版 (Verizon)', 'price_adjust' => 150.00, 'sort_order' => 4],
                ],
    ],
    [
        'model' => 'iPad Air 13-inch M2',
        'sku' => 'APPLE-IPADAIR13-M2-256GB',
        'name_en' => 'Apple iPad Air 13-inch M2 256GB Wi-Fi',
        'name_zh' => '苹果 iPad Air 13英寸 M2 256GB Wi-Fi',
        'description_en' => '<p>The all-new 13-inch iPad Air provides more screen space and M2 performance for immersive creativity and productivity.</p>
<h3>Key Features:</h3>
<ul>
<li>13-inch Liquid Retina display - largest iPad Air ever</li>
<li>M2 chip for incredible performance</li>
<li>Advanced cameras with Smart HDR and Center Stage</li>
<li>Apple Pencil Pro and Magic Keyboard support</li>
<li>All-day battery life</li>
<li>Landscape stereo speakers</li>
<li>Fast Wi-Fi 6E and USB-C connectivity</li>
<li>Thin and light design in beautiful colors</li>
</ul>',
        'description_zh' => '<p>全新13英寸iPad Air提供更大屏幕空间和M2性能,为沉浸式创作和高效工作助力。</p>
<h3>主要特性:</h3>
<ul>
<li>13英寸Liquid Retina显示屏 - 史上最大iPad Air</li>
<li>M2芯片带来惊人性能</li>
<li>先进摄像头,支持Smart HDR和人物居中</li>
<li>支持Apple Pencil Pro和妙控键盘</li>
<li>全天候电池续航</li>
<li>横向立体声扬声器</li>
<li>快速Wi-Fi 6E和USB-C连接</li>
<li>轻薄设计,提供多种精美配色</li>
</ul>',
        'price' => 799.00,
        'quantity' => 70,
        'weight' => 0.617,
        'manufacturer' => 'Apple',
        'image' => 'catalog/1920/ipad-air-13-m2.png',
        'category' => '手机与平板',
        'meta_title_en' => 'iPad Air 13-inch M2 256GB - Largest iPad Air',
        'meta_description_en' => 'iPad Air 13-inch with M2 chip, expansive display, all-day battery. Perfect for creativity and productivity.',
        'meta_keywords_en' => 'iPad Air 13, M2 tablet, large iPad Air, 13 inch tablet, Apple iPad',
                'storage_variants' => [
                        [
                                'code' => 'ipadair13m2-256gb',
                                'name_en' => '256 GB',
                                'name_zh' => '256 GB',
                                'final_price' => 799.00,
                                'quantity' => 50,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'ipadair13m2-512gb',
                                'name_en' => '512 GB',
                                'name_zh' => '512 GB',
                                'final_price' => 949.00,
                                'quantity' => 35,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'ipadair13m2-blue', 'name_en' => 'Blue', 'name_zh' => '蓝色', 'sort_order' => 1],
                        ['code' => 'ipadair13m2-purple', 'name_en' => 'Purple', 'name_zh' => '紫色', 'sort_order' => 2],
                        ['code' => 'ipadair13m2-starlight', 'name_en' => 'Starlight', 'name_zh' => '星光色', 'sort_order' => 3],
                        ['code' => 'ipadair13m2-spacegray', 'name_en' => 'Space Gray', 'name_zh' => '深空灰', 'sort_order' => 4],
                ],
                'connectivity_options' => [
                        ['code' => 'ipadair13m2-wifi', 'name_en' => 'Wi-Fi Only', 'name_zh' => '仅 Wi-Fi', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'ipadair13m2-cellular-unlocked', 'name_en' => 'Wi-Fi + Cellular (Unlocked)', 'name_zh' => 'Wi-Fi + 蜂窝版 (解锁)', 'price_adjust' => 150.00, 'sort_order' => 2],
                        ['code' => 'ipadair13m2-cellular-att', 'name_en' => 'Wi-Fi + Cellular (AT&T)', 'name_zh' => 'Wi-Fi + 蜂窝版 (AT&T)', 'price_adjust' => 150.00, 'sort_order' => 3],
                        ['code' => 'ipadair13m2-cellular-verizon', 'name_en' => 'Wi-Fi + Cellular (Verizon)', 'name_zh' => 'Wi-Fi + 蜂窝版 (Verizon)', 'price_adjust' => 150.00, 'sort_order' => 4],
                ],
    ],
    
    // Samsung Galaxy Tab S10系列
    [
        'model' => 'Galaxy Tab S10+',
        'sku' => 'SAMSUNG-TABS10P-256GB',
        'name_en' => 'Samsung Galaxy Tab S10+ 256GB Wi-Fi',
        'name_zh' => '三星 Galaxy Tab S10+ 256GB Wi-Fi',
        'description_en' => '<p>Galaxy Tab S10+ features a stunning Dynamic AMOLED 2X display, powerful MediaTek processor, and S Pen included. The ultimate Android tablet experience.</p>
<h3>Key Features:</h3>
<ul>
<li>12.4-inch Dynamic AMOLED 2X display (120Hz)</li>
<li>MediaTek Dimensity 9300+ processor</li>
<li>Dual camera: 13MP Wide, 8MP Ultra Wide</li>
<li>12MP Ultra Wide front camera</li>
<li>S Pen included - premium note-taking and drawing</li>
<li>10,090mAh battery with 45W super fast charging</li>
<li>Galaxy AI features for productivity</li>
<li>Book Cover Keyboard compatible</li>
<li>IP68 water and dust resistance</li>
</ul>',
        'description_zh' => '<p>Galaxy Tab S10+配备惊艳的Dynamic AMOLED 2X显示屏、强大的联发科处理器,并附赠S Pen。终极Android平板体验。</p>
<h3>主要特性:</h3>
<ul>
<li>12.4英寸Dynamic AMOLED 2X显示屏(120Hz)</li>
<li>联发科天玑9300+处理器</li>
<li>双摄系统: 13MP广角、8MP超广角</li>
<li>12MP超广角前置摄像头</li>
<li>附赠S Pen - 高端笔记和绘画</li>
<li>10090mAh电池,支持45W超级快充</li>
<li>Galaxy AI功能提升生产力</li>
<li>兼容书本式键盘保护套</li>
<li>IP68防水防尘</li>
</ul>',
        'price' => 999.99,
        'quantity' => 55,
        'weight' => 0.571,
        'manufacturer' => 'Samsung',
        'image' => 'catalog/1920/galaxy-tab-s10-plus.png',
        'category' => '手机与平板',
        'meta_title_en' => 'Samsung Galaxy Tab S10+ 256GB - Premium Android Tablet',
        'meta_description_en' => 'Galaxy Tab S10+ with AMOLED display, S Pen included, AI features. Ultimate productivity tablet.',
        'meta_keywords_en' => 'Galaxy Tab S10, Samsung tablet, S Pen tablet, Android tablet, AMOLED tablet',
                'storage_variants' => [
                        [
                                'code' => 'galaxytabs10plus-256gb',
                                'name_en' => '256 GB',
                                'name_zh' => '256 GB',
                                'final_price' => 999.99,
                                'quantity' => 50,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'galaxytabs10plus-512gb',
                                'name_en' => '512 GB',
                                'name_zh' => '512 GB',
                                'final_price' => 1149.99,
                                'quantity' => 30,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'galaxytabs10plus-graphite', 'name_en' => 'Graphite', 'name_zh' => '石墨灰', 'sort_order' => 1],
                        ['code' => 'galaxytabs10plus-beige', 'name_en' => 'Beige', 'name_zh' => '米色', 'sort_order' => 2],
                        ['code' => 'galaxytabs10plus-mint', 'name_en' => 'Mint', 'name_zh' => '薄荷绿', 'sort_order' => 3],
                ],
                'connectivity_options' => [
                        ['code' => 'galaxytabs10plus-wifi', 'name_en' => 'Wi-Fi Only', 'name_zh' => '仅 Wi-Fi', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'galaxytabs10plus-5g-unlocked', 'name_en' => 'Wi-Fi + 5G (Unlocked)', 'name_zh' => 'Wi-Fi + 5G (解锁)', 'price_adjust' => 150.00, 'sort_order' => 2],
                        ['code' => 'galaxytabs10plus-5g-verizon', 'name_en' => 'Wi-Fi + 5G (Verizon)', 'name_zh' => 'Wi-Fi + 5G (Verizon)', 'price_adjust' => 170.00, 'sort_order' => 3],
                        ['code' => 'galaxytabs10plus-5g-tmobile', 'name_en' => 'Wi-Fi + 5G (T-Mobile)', 'name_zh' => 'Wi-Fi + 5G (T-Mobile)', 'price_adjust' => 170.00, 'sort_order' => 4],
                ],
    ],
    [
        'model' => 'Galaxy Tab S10 Ultra',
        'sku' => 'SAMSUNG-TABS10U-512GB',
        'name_en' => 'Samsung Galaxy Tab S10 Ultra 512GB Wi-Fi',
        'name_zh' => '三星 Galaxy Tab S10 Ultra 512GB Wi-Fi',
        'description_en' => '<p>The ultimate Galaxy Tab S10 Ultra with massive 14.6-inch display, flagship performance, and advanced S Pen functionality. Perfect laptop replacement.</p>
<h3>Key Features:</h3>
<ul>
<li>14.6-inch Dynamic AMOLED 2X display (120Hz) - largest Samsung tablet</li>
<li>MediaTek Dimensity 9300+ flagship processor</li>
<li>Dual front cameras with auto-framing</li>
<li>S Pen with ultra-low latency for professional drawing</li>
<li>11,200mAh massive battery with 45W charging</li>
<li>16GB RAM for desktop-class multitasking</li>
<li>Galaxy AI with enhanced productivity features</li>
<li>Book Cover Keyboard for laptop-like experience</li>
<li>Premium metal design, IP68 rated</li>
</ul>',
        'description_zh' => '<p>终极Galaxy Tab S10 Ultra配备巨大的14.6英寸显示屏、旗舰性能和先进S Pen功能。完美的笔记本替代品。</p>
<h3>主要特性:</h3>
<ul>
<li>14.6英寸Dynamic AMOLED 2X显示屏(120Hz) - 三星最大平板</li>
<li>联发科天玑9300+旗舰处理器</li>
<li>双前置摄像头,支持自动取景</li>
<li>S Pen超低延迟,专业绘画体验</li>
<li>11200mAh超大电池,支持45W充电</li>
<li>16GB内存,桌面级多任务处理</li>
<li>Galaxy AI增强生产力功能</li>
<li>书本式键盘保护套带来类笔记本体验</li>
<li>高端金属设计,IP68防护</li>
</ul>',
        'price' => 1199.99,
        'quantity' => 40,
        'weight' => 0.718,
        'manufacturer' => 'Samsung',
        'image' => 'catalog/1920/galaxy-tab-s10-ultra.png',
        'category' => '手机与平板',
        'meta_title_en' => 'Samsung Galaxy Tab S10 Ultra 512GB - Laptop Replacement',
        'meta_description_en' => 'Galaxy Tab S10 Ultra with 14.6-inch display, S Pen, desktop-class performance. Ultimate productivity tablet.',
        'meta_keywords_en' => 'Galaxy Tab S10 Ultra, Samsung Ultra tablet, laptop replacement, 14 inch tablet',
                'storage_variants' => [
                        [
                                'code' => 'galaxytabs10ultra-512gb',
                                'name_en' => '512 GB',
                                'name_zh' => '512 GB',
                                'final_price' => 1199.99,
                                'quantity' => 40,
                                'sort_order' => 1,
                                'subtract' => 1,
                        ],
                        [
                                'code' => 'galaxytabs10ultra-1tb',
                                'name_en' => '1 TB',
                                'name_zh' => '1 TB',
                                'final_price' => 1399.99,
                                'quantity' => 25,
                                'sort_order' => 2,
                                'subtract' => 1,
                        ],
                ],
                'color_options' => [
                        ['code' => 'galaxytabs10ultra-graphite', 'name_en' => 'Graphite', 'name_zh' => '石墨灰', 'sort_order' => 1],
                        ['code' => 'galaxytabs10ultra-titanium-gray', 'name_en' => 'Titanium Gray', 'name_zh' => '钛灰色', 'sort_order' => 2],
                ],
                'connectivity_options' => [
                        ['code' => 'galaxytabs10ultra-wifi', 'name_en' => 'Wi-Fi Only', 'name_zh' => '仅 Wi-Fi', 'price_adjust' => 0.00, 'sort_order' => 1],
                        ['code' => 'galaxytabs10ultra-5g-unlocked', 'name_en' => 'Wi-Fi + 5G (Unlocked)', 'name_zh' => 'Wi-Fi + 5G (解锁)', 'price_adjust' => 180.00, 'sort_order' => 2],
                        ['code' => 'galaxytabs10ultra-5g-verizon', 'name_en' => 'Wi-Fi + 5G (Verizon)', 'name_zh' => 'Wi-Fi + 5G (Verizon)', 'price_adjust' => 200.00, 'sort_order' => 3],
                        ['code' => 'galaxytabs10ultra-5g-tmobile', 'name_en' => 'Wi-Fi + 5G (T-Mobile)', 'name_zh' => 'Wi-Fi + 5G (T-Mobile)', 'price_adjust' => 200.00, 'sort_order' => 4],
                ],
    ],
];

// 开始导入
echo "===== 2025年手机与平板产品导入开始 =====\n\n";

$categoryId = getCategoryId('手机与平板');
if (!$categoryId) {
    die("错误: 无法找到'手机与平板'分类\n");
}
echo "分类ID: {$categoryId} (手机与平板)\n\n";

$stockStatusId = getStockStatusId('In Stock');
echo "库存状态ID: {$stockStatusId}\n\n";

$successCount = 0;
$errorCount = 0;

foreach ($mobileProducts as $index => $product) {
        echo "正在导入 " . ($index + 1) . "/" . count($mobileProducts) . ": {$product['name_en']}...\n";

        $modelEsc = esc($product['model']);
        $skuEsc = esc($product['sku']);

        $existingQuery = query("SELECT product_id FROM " . DB_PREFIX . "product WHERE sku = '$skuEsc' OR model = '$modelEsc' LIMIT 1");
        $productId = 0;
        if ($existingQuery->num_rows > 0) {
                $row = $existingQuery->fetch_assoc();
                $productId = (int)$row['product_id'];
        }
        $isUpdate = $productId > 0;

        $storageVariants = $product['storage_variants'] ?? [];
        $colorOptions = $product['color_options'] ?? [];
        $carrierOptions = $product['carrier_options'] ?? [];
        $connectivityOptions = $product['connectivity_options'] ?? [];

        $basePrice = isset($product['price']) ? (float)$product['price'] : 0.0;
        $totalQuantity = 0;

        if (!empty($storageVariants)) {
                foreach ($storageVariants as $variant) {
                        $variantPrice = isset($variant['final_price']) ? (float)$variant['final_price'] : 0.0;
                        if ($basePrice <= 0 || ($variantPrice > 0 && $variantPrice < $basePrice)) {
                                $basePrice = $variantPrice;
                        }
                        $totalQuantity += (int)($variant['quantity'] ?? 0);
                }
        }

        if ($totalQuantity <= 0) {
                $totalQuantity = (int)($product['quantity'] ?? 0);
        }
        if ($totalQuantity <= 0) {
                $totalQuantity = 50;
        }

        $productPrice = $basePrice > 0 ? $basePrice : (float)$product['price'];
        if ($productPrice <= 0) {
                $productPrice = 10.00;
        }

        $weight = isset($product['weight']) ? (float)$product['weight'] : 0.0;
        $imageEsc = esc($product['image'] ?? '');

        if ($isUpdate) {
                query("UPDATE " . DB_PREFIX . "product SET model = '$modelEsc', sku = '$skuEsc', quantity = $totalQuantity, stock_status_id = $stockStatusId, image = '$imageEsc', manufacturer_id = 0, shipping = 1, price = $productPrice, tax_class_id = 0, date_available = '" . date('Y-m-d') . "', weight = $weight, weight_class_id = 1, length = 0, width = 0, height = 0, length_class_id = 1, subtract = 1, minimum = 1, sort_order = 0, status = 1, date_modified = NOW() WHERE product_id = $productId");
        } else {
                query("INSERT INTO " . DB_PREFIX . "product SET model = '$modelEsc', sku = '$skuEsc', quantity = $totalQuantity, stock_status_id = $stockStatusId, image = '$imageEsc', manufacturer_id = 0, shipping = 1, price = $productPrice, tax_class_id = 0, date_available = '" . date('Y-m-d') . "', weight = $weight, weight_class_id = 1, length = 0, width = 0, height = 0, length_class_id = 1, subtract = 1, minimum = 1, sort_order = 0, status = 1, date_added = NOW(), date_modified = NOW()");
                $productId = (int)$conn->insert_id;
        }

        // 更新描述
        query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = $productId");
        query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = $productId, language_id = 1, name = '" . esc($product['name_en']) . "', description = '" . esc($product['description_en']) . "', tag = '', meta_title = '" . esc($product['meta_title_en']) . "', meta_description = '" . esc($product['meta_description_en']) . "', meta_keyword = '" . esc($product['meta_keywords_en']) . "'");
        query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = $productId, language_id = 2, name = '" . esc($product['name_zh']) . "', description = '" . esc($product['description_zh']) . "', tag = '', meta_title = '" . esc($product['meta_title_en']) . "', meta_description = '" . esc($product['meta_description_en']) . "', meta_keyword = '" . esc($product['meta_keywords_en']) . "'");

        // 分类
        query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = $productId");
        query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = $productId, category_id = $categoryId");

        // 店铺
        query("REPLACE INTO " . DB_PREFIX . "product_to_store SET product_id = $productId, store_id = 0");

        // SEO
                $seoKeywordEn = buildSeoKeyword($product['model'] . '-' . $product['sku']);
                $seoKeywordCn = buildSeoKeyword($product['name_zh'] . '-' . $product['sku'] . '-cn');
                $seoKeywordEnEsc = esc($seoKeywordEn);
                $seoKeywordCnEsc = esc($seoKeywordCn);
                query("DELETE FROM " . DB_PREFIX . "seo_url WHERE `key` = 'product_id' AND value = '$productId' AND store_id = 0");
                query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = 0, language_id = 1, `key` = 'product_id', value = '$productId', keyword = '$seoKeywordEnEsc'");
                query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = 0, language_id = 2, `key` = 'product_id', value = '$productId', keyword = '$seoKeywordCnEsc'");

        // 选项
        clearProductOptions($productId);

        $optionGroups = [
                'storage' => $storageVariants,
                'color' => $colorOptions,
                'carrier' => $carrierOptions,
                'connectivity' => $connectivityOptions,
        ];

        $summaryParts = [];

        foreach ($optionGroups as $optionKey => $values) {
                if (empty($values)) {
                        continue;
                }

                if (!isset($optionDefinitions[$optionKey])) {
                        continue;
                }

                $definition = $optionDefinitions[$optionKey];
                $optionId = ensureOption($optionKey, $definition['name_en'], $definition['name_zh'], $definition['type'], $definition['sort_order']);
                query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = $productId, option_id = $optionId, value = '', required = 1");
                $productOptionId = (int)$conn->insert_id;

                foreach ($values as $variant) {
                        $nameEn = $variant['name_en'];
                        $nameZh = $variant['name_zh'] ?? $variant['name_en'];
                        $sortOrder = (int)($variant['sort_order'] ?? 0);
                        $optionValueId = ensureOptionValue($optionId, $nameEn, $nameZh, $sortOrder);

                                $imagePath = '';
                                if (!empty($variant['image'])) {
                                        $imagePath = esc($variant['image']);
                                } elseif (!empty($variant['swatch_image'])) {
                                        $imagePath = esc($variant['swatch_image']);
                                }

                        if ($optionKey === 'storage') {
                                $variantPrice = (float)($variant['final_price'] ?? $productPrice);
                                $priceAdjust = $variantPrice - $productPrice;
                                $quantityVal = (int)($variant['quantity'] ?? 0);
                                if ($quantityVal <= 0) {
                                        $quantityVal = $totalQuantity;
                                }
                                $subtractFlag = isset($variant['subtract']) ? (int)$variant['subtract'] : 1;
                        } else {
                                $priceAdjust = (float)($variant['price_adjust'] ?? 0.0);
                                $quantityVal = (int)($variant['quantity'] ?? $totalQuantity);
                                $subtractFlag = isset($variant['subtract']) ? (int)$variant['subtract'] : 0;
                        }

                        if ($quantityVal <= 0) {
                                $quantityVal = $totalQuantity;
                        }
                        if ($quantityVal <= 0) {
                                $quantityVal = 999;
                        }

                        $pricePrefix = '+';
                        if ($priceAdjust < 0) {
                                $pricePrefix = '-';
                        }
                        $priceValue = formatPrice(abs($priceAdjust));

                                        query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = $productOptionId, product_id = $productId, option_id = $optionId, option_value_id = $optionValueId, quantity = $quantityVal, subtract = $subtractFlag, price = '$priceValue', price_prefix = '$pricePrefix', points = 0, points_prefix = '+', weight = 0, weight_prefix = '+', image = '$imagePath'");
                }

                $summaryParts[] = $definition['name_zh'] . 'x' . count($values);
        }

        $summaryText = $summaryParts ? implode('，', $summaryParts) : '无选项';
        echo "  ✓ " . ($isUpdate ? '已更新' : '已新增') . " Product ID: {$productId}, 基础售价: $" . number_format($productPrice, 2) . ", 选项: {$summaryText}\n\n";
        $successCount++;
}

echo "\n===== 导入完成 =====\n";
echo "成功: {$successCount} 个产品\n";
echo "失败: {$errorCount} 个产品\n";
echo "总计: " . count($mobileProducts) . " 个产品\n\n";

// 清理缓存
echo "缓存已清理!\n";
echo "\n请访问后台商品管理页面查看导入的产品。\n";
    $conn->close();
echo "缓存已清理!\n";
echo "\n请访问后台商品管理页面查看导入的产品。\n";
?>
