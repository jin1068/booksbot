<?php
require_once('config.php');

// 连接数据库
$conn = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "=== 商品分类列表 ===\n\n";
$result = $conn->query("SELECT c.category_id, cd.name, c.parent_id 
                        FROM " . DB_PREFIX . "category c 
                        LEFT JOIN " . DB_PREFIX . "category_description cd ON c.category_id = cd.category_id 
                        WHERE cd.language_id = 2 
                        ORDER BY c.parent_id, cd.name");

while($row = $result->fetch_assoc()) {
    echo sprintf("ID: %3d - Parent: %3d - Name: %s\n", 
                 $row['category_id'], 
                 $row['parent_id'], 
                 $row['name']);
}

echo "\n=== 语言列表 ===\n\n";
$result = $conn->query("SELECT * FROM " . DB_PREFIX . "language");
while($row = $result->fetch_assoc()) {
    echo "ID: {$row['language_id']} - Code: {$row['code']} - Name: {$row['name']}\n";
}

echo "\n=== 现有产品统计 ===\n\n";
$result = $conn->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "product");
$row = $result->fetch_assoc();
echo "总产品数: {$row['count']}\n";

$conn->close();
?>
